<?php
/**
 * Endpoint keranjang (POST): aksi = add | inc | dec | hapus | kosong
 * - Pembeli/guest memakai keranjang 'cart', penjual memakai 'kasir_cart' (kasir).
 * - Mengembalikan JSON jika dipanggil via fetch (header X-Requested-With: fetch).
 */
require_once __DIR__ . '/../includes/functions.php';

$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

function balas(bool $ok, string $pesan, string $ns, bool $ajax, bool $muatUlang = false): never
{
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'pesan' => $pesan, 'jumlah' => cart_jumlah($ns), 'muat_ulang' => $muatUlang]);
        exit;
    }
    flash_set($ok ? 'success' : 'error', $pesan);
    header('Location: ' . kembali_aman('api/pembeli/lapak.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('api/pembeli/lapak.php');
}
if (sudah_login() && !in_array($_SESSION['role'], ['pembeli', 'penjual'], true)) {
    redirect(beranda_peran((string) $_SESSION['role']));
}

$ns = cart_ns();
if (!csrf_valid()) {
    balas(false, 'Sesi tidak valid. Muat ulang halaman.', $ns, $ajax);
}

$aksi = (string) ($_POST['aksi'] ?? '');
$cart = cart_ambil($ns);

if ($aksi === 'kosong') {
    cart_simpan($ns, 0, []);
    balas(true, 'Keranjang dikosongkan.', $ns, $ajax, true);
}

$pid = (int) ($_POST['produk_id'] ?? 0);
$st = $pdo->prepare(
    'SELECT p.id_menu AS id, p.id_pedagang AS lapak_id, p.stok, p.nama_menu AS nama_produk, (' . sql_lapak_aktif('l') . ') AS aktif
     FROM menu p JOIN ' . SQL_LAPAK . ' l ON l.id = p.id_pedagang WHERE p.id_menu = ?'
);
$st->execute([$pid]);
$p = $st->fetch();
if (!$p) {
    balas(false, 'Menu tidak ditemukan.', $ns, $ajax);
}

// Pembatasan per peran
if ($ns === 'kasir_cart') {
    $lapak = lapak_saya($pdo);
    if (!$lapak || (int) $lapak['id'] !== (int) $p['lapak_id']) {
        balas(false, 'Menu ini bukan milik lapak Anda.', $ns, $ajax);
    }
} elseif (!$p['aktif']) {
    balas(false, 'Lapak ini sedang tidak menerima pesanan.', $ns, $ajax);
}

$lapakId = (int) $p['lapak_id'];
$items = $cart['items'];
if ($cart['lapak_id'] !== $lapakId) {
    $items = []; // keranjang hanya boleh berisi satu lapak
}
$qty = $items[$pid] ?? 0;

switch ($aksi) {
    case 'add':
    case 'inc':
        if ($qty + 1 > (int) $p['stok']) {
            cart_simpan($ns, $lapakId, $items);
            balas(false, 'Stok "' . $p['nama_produk'] . '" tidak mencukupi.', $ns, $ajax);
        }
        $items[$pid] = $qty + 1;
        $pesan = '"' . $p['nama_produk'] . '" ditambahkan.';
        break;
    case 'dec':
        if ($qty <= 1) { unset($items[$pid]); } else { $items[$pid] = $qty - 1; }
        $pesan = 'Jumlah diperbarui.';
        break;
    case 'hapus':
        unset($items[$pid]);
        $pesan = 'Item dihapus.';
        break;
    default:
        balas(false, 'Aksi tidak dikenal.', $ns, $ajax);
}

cart_simpan($ns, $lapakId, $items);
// Setelah dec/hapus halaman perlu dimuat ulang agar total ikut berubah
balas(true, $pesan, $ns, $ajax, in_array($aksi, ['dec', 'hapus', 'inc'], true));
