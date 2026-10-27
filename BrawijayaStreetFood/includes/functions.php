<?php
require_once __DIR__ . '/session.php';

/* Polyfill ringan agar aplikasi tetap jalan di hosting tanpa ekstensi mbstring */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s): int { return (int) preg_match_all('/./us', $s); }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $s, int $start, ?int $length = null): string
    {
        $c = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return implode('', array_slice($c, $start, $length));
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $s): string { return strtoupper($s); }
}

/* ============================================================
 * Umum
 * ============================================================ */

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function inisial(string $teks): string
{
    $teks = trim($teks);
    return $teks === '' ? '?' : mb_strtoupper(mb_substr($teks, 0, 1));
}

function waktu_wib(?string $ts, string $format = 'd M Y H:i'): string
{
    if (!$ts) {
        return '-';
    }
    return (new DateTime($ts))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format($format);
}

/* ============================================================
 * Flash message
 * ============================================================ */

function flash_set(string $type, string $pesan): void
{
    $_SESSION['flash'] = ['type' => $type, 'pesan' => $pesan];
}

function flash_ambil(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ============================================================
 * CSRF
 * ============================================================ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $kirim = $_POST['csrf'] ?? '';
    return is_string($kirim) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $kirim);
}

/** Wajib dipanggil di awal setiap handler POST. */
function wajib_post(string $kembali): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($kembali);
    }
    if (!csrf_valid()) {
        flash_set('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
        redirect($kembali);
    }
}

/** Alamat "kembali" dari form, hanya path internal. */
function kembali_aman(string $default): string
{
    $k = $_POST['kembali'] ?? '';
    if (is_string($k) && preg_match('~^/[^/\\\\]~', $k) && (BASE === '' || str_starts_with($k, BASE . '/'))) {
        return $k;
    }
    return url($default);
}

/* ============================================================
 * Autentikasi & peran
 * ============================================================ */

function sudah_login(): bool
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

function peran(): string
{
    return sudah_login() ? (string) ($_SESSION['role'] ?? '') : 'guest';
}

function beranda_peran(string $role): string
{
    return match ($role) {
        'penjual' => 'api/penjual/stok.php',
        'admin'   => 'api/admin/index.php',
        default   => 'api/pembeli/lapak.php',
    };
}

function wajib_peran(string ...$roles): void
{
    if (!sudah_login()) {
        flash_set('error', 'Silakan login terlebih dahulu.');
        redirect('auth/login.php');
    }
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        redirect(beranda_peran((string) $_SESSION['role']));
    }
}

/** Halaman pembeli boleh diakses guest maupun pembeli, tapi tidak oleh penjual/admin. */
function wajib_pembeli_atau_guest(): void
{
    if (sudah_login() && ($_SESSION['role'] ?? '') !== 'pembeli') {
        redirect(beranda_peran((string) $_SESSION['role']));
    }
}

/* ============================================================
 * Lapak
 * ============================================================ */

/** Kondisi SQL (alias dari SQL_LAPAK): toko buka, sewa aktif, dan masa sewa belum berakhir. */
function sql_lapak_aktif(string $alias = 'l'): string
{
    return "$alias.status_toko = 'buka' AND $alias.status_sewa = 'aktif' AND $alias.sewa_berakhir >= " . SQL_HARI_INI;
}

function lapak_saya(PDO $pdo): ?array
{
    static $cache = false;
    if ($cache === false) {
        $st = $pdo->prepare('SELECT l.* FROM ' . SQL_LAPAK . ' l WHERE l.owner_id = ?');
        $st->execute([$_SESSION['user_id'] ?? 0]);
        $cache = $st->fetch() ?: null;
    }
    return $cache;
}

/* ============================================================
 * Keranjang (disimpan di session)
 * ns = 'cart'       -> pembeli / guest
 * ns = 'kasir_cart' -> penjual (pesanan di kasir)
 * ============================================================ */

function cart_ns(): string
{
    return peran() === 'penjual' ? 'kasir_cart' : 'cart';
}

function cart_ambil(string $ns): array
{
    $c = $_SESSION[$ns] ?? [];
    $items = [];
    foreach (($c['items'] ?? []) as $pid => $qty) {
        if ((int) $qty > 0) {
            $items[(int) $pid] = (int) $qty;
        }
    }
    return ['lapak_id' => $items ? (int) ($c['lapak_id'] ?? 0) : 0, 'items' => $items];
}

function cart_simpan(string $ns, int $lapakId, array $items): void
{
    $_SESSION[$ns] = $items ? ['lapak_id' => $lapakId, 'items' => $items] : [];
}

function cart_jumlah(string $ns): int
{
    return array_sum(cart_ambil($ns)['items']);
}

/** Tombol kecil yang mengirim POST ke api/keranjang.php (tambah/kurang/hapus). */
function form_keranjang(string $aksi, int $produkId, string $teks, string $kembali, string $kelas = '', string $konfirmasi = '', bool $ajax = false): string
{
    $k = ($konfirmasi !== '' ? ' data-konfirmasi="' . e($konfirmasi) . '"' : '') . ($ajax ? ' data-ajax' : '');
    return '<form method="post" action="' . e(url('api/keranjang.php')) . '" class="form-inline form-keranjang-ajax"' . $k . '>'
        . csrf_field()
        . '<input type="hidden" name="aksi" value="' . e($aksi) . '">'
        . '<input type="hidden" name="produk_id" value="' . (int) $produkId . '">'
        . '<input type="hidden" name="kembali" value="' . e($kembali) . '">'
        . '<button type="submit" class="' . e($kelas) . '" aria-label="' . e($aksi) . '">' . $teks . '</button>'
        . '</form>';
}

/* ============================================================
 * Pesanan
 * ============================================================ */

function label_status(string $status): string
{
    return match ($status) {
        'menunggu'   => 'Menunggu Pembayaran',
        'diproses'   => 'Diproses',
        'selesai'    => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
        default      => ucfirst($status),
    };
}

/** Kembalikan stok & tandai pesanan dibatalkan. Panggil DI DALAM transaksi. */
function batalkan_pesanan_tx(PDO $pdo, int $pesananId): void
{
    $pdo->prepare(
        'UPDATE menu m SET stok = m.stok + i.jumlah
         FROM item_pesanan i
         WHERE i.id_pesanan = ? AND i.id_menu = m.id_menu'
    )->execute([$pesananId]);
    $pdo->prepare("UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ?")->execute([$pesananId]);
    // Pembayaran yang belum dibayar ikut kedaluwarsa (yang sudah lunas dibiarkan untuk dicatat/refund manual)
    $pdo->prepare("UPDATE pembayaran SET status_pembayaran = 'kedaluwarsa' WHERE id_pesanan = ? AND status_pembayaran = 'pending'")
        ->execute([$pesananId]);
}

/** Batalkan pesanan QRIS yang lewat batas waktu bayar dan kembalikan stoknya. */
function bersihkan_kedaluwarsa(PDO $pdo): void
{
    try {
        $pdo->beginTransaction();
        $ids = $pdo->query(
            "SELECT o.id_pesanan
             FROM pesanan o JOIN pembayaran b ON b.id_pesanan = o.id_pesanan
             WHERE o.status_pesanan = 'menunggu' AND b.status_pembayaran = 'pending' AND b.kedaluwarsa_pada < NOW()
             FOR UPDATE OF o SKIP LOCKED"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            batalkan_pesanan_tx($pdo, (int) $id);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('bersihkan_kedaluwarsa: ' . $e->getMessage());
    }
}

/** Ambil item untuk banyak pesanan sekaligus: [pesanan_id => [item, ...]] */
function ambil_items_pesanan(PDO $pdo, array $ids): array
{
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_map('intval', $ids));
    $hasil = [];
    foreach ($pdo->query(
        "SELECT id_item_pesanan AS id, id_pesanan AS pesanan_id, id_menu AS produk_id, nama_menu AS nama_produk,
                harga_satuan AS harga, jumlah AS qty, subtotal_item, catatan
         FROM item_pesanan WHERE id_pesanan IN ($in) ORDER BY id_item_pesanan"
    ) as $r) {
        $hasil[(int) $r['pesanan_id']][] = $r;
    }
    return $hasil;
}

/**
 * Buat pesanan + pembayaran + kurangi stok secara atomik.
 * $items = [id_menu => qty]. Melempar RuntimeException berisi pesan untuk pengguna.
 *
 * @return array{id:int,kode:string,total:float}
 */
function buat_pesanan(PDO $pdo, int $lapakId, array $items, string $metode, ?int $userId, string $namaPemesan, bool $wajibSewaAktif): array
{
    if (!$items) {
        throw new RuntimeException('Keranjang masih kosong.');
    }
    ksort($items); // urutan kunci konsisten -> hindari deadlock

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT l.id, l.nama_lapak, (' . sql_lapak_aktif('l') . ') AS aktif FROM ' . SQL_LAPAK . ' l WHERE l.id = ?');
        $st->execute([$lapakId]);
        $lapak = $st->fetch();
        if (!$lapak) {
            throw new RuntimeException('Lapak tidak ditemukan.');
        }
        if ($wajibSewaAktif && !$lapak['aktif']) {
            throw new RuntimeException('Lapak sedang tidak menerima pesanan.');
        }

        $in = implode(',', array_map('intval', array_keys($items)));
        $st = $pdo->prepare(
            "SELECT id_menu AS id, nama_menu AS nama_produk, harga, stok FROM menu
             WHERE id_pedagang = ? AND id_menu IN ($in) ORDER BY id_menu FOR UPDATE"
        );
        $st->execute([$lapakId]);
        $produk = [];
        foreach ($st->fetchAll() as $p) {
            $produk[(int) $p['id']] = $p;
        }

        $total = 0.0;
        foreach ($items as $pid => $qty) {
            if (!isset($produk[$pid])) {
                throw new RuntimeException('Ada menu di keranjang yang sudah tidak tersedia.');
            }
            if ($produk[$pid]['stok'] < $qty) {
                throw new RuntimeException('Stok "' . $produk[$pid]['nama_produk'] . '" tinggal ' . $produk[$pid]['stok'] . '.');
            }
            $total += (float) $produk[$pid]['harga'] * $qty;
        }

        $kode = 'BSF-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $qris = $metode === 'qris';

        $st = $pdo->prepare(
            "INSERT INTO pesanan (kode_pesanan, id_pengguna, nama_pemesan, id_pedagang, total_harga, status_pesanan)
             VALUES (?, ?, ?, ?, ?, ?) RETURNING id_pesanan"
        );
        $st->execute([$kode, $userId, $namaPemesan !== '' ? $namaPemesan : 'Guest', $lapakId, $total, $qris ? 'menunggu' : 'diproses']);
        $pesananId = (int) $st->fetchColumn();

        if ($qris) {
            $pdo->prepare(
                "INSERT INTO pembayaran (id_pesanan, metode_pembayaran, jumlah_bayar, status_pembayaran, kedaluwarsa_pada)
                 VALUES (?, 'qris', ?, 'pending', NOW() + INTERVAL '1 second' * CAST(? AS INTEGER))"
            )->execute([$pesananId, $total, BATAS_BAYAR_DETIK]);
        } else {
            $pdo->prepare(
                "INSERT INTO pembayaran (id_pesanan, metode_pembayaran, jumlah_bayar, status_pembayaran, tgl_pembayaran)
                 VALUES (?, 'tunai', ?, ?, NOW())"
            )->execute([$pesananId, $total, ST_BAYAR_LUNAS]);
        }

        $insItem = $pdo->prepare(
            'INSERT INTO item_pesanan (id_pesanan, id_menu, id_pedagang, nama_menu, jumlah, harga_satuan, subtotal_item)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $updStok = $pdo->prepare('UPDATE menu SET stok = stok - ? WHERE id_menu = ?');
        foreach ($items as $pid => $qty) {
            $insItem->execute([$pesananId, $pid, $lapakId, $produk[$pid]['nama_produk'], $qty, $produk[$pid]['harga'], (float) $produk[$pid]['harga'] * $qty]);
            $updStok->execute([$qty, $pid]);
        }

        $pdo->commit();
        return ['id' => $pesananId, 'kode' => $kode, 'total' => $total];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
