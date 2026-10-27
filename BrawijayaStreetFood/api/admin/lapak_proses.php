<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_peran('admin');
wajib_post('api/admin/lapak.php');

$aksi = (string) ($_POST['aksi'] ?? '');
$id = (int) ($_POST['id'] ?? 0); // id_pedagang

/* ---------- Hapus ---------- */
if ($aksi === 'hapus' && $id) {
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT id_pengguna FROM pedagang WHERE id_pedagang = ? FOR UPDATE');
        $st->execute([$id]);
        $owner = $st->fetchColumn();

        // Urutan hapus mengikuti relasi (tabel di database tidak memakai ON DELETE CASCADE)
        $pdo->prepare('DELETE FROM pembayaran WHERE id_pesanan IN (SELECT id_pesanan FROM pesanan WHERE id_pedagang = ?)')->execute([$id]);
        $pdo->prepare('DELETE FROM item_pesanan WHERE id_pedagang = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM pesanan WHERE id_pedagang = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM menu WHERE id_pedagang = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM sewa_lapak WHERE id_pedagang = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM pedagang WHERE id_pedagang = ?')->execute([$id]);
        if ($owner) {
            $pdo->prepare('DELETE FROM pengguna WHERE id_pengguna = ? AND peran = ?')->execute([$owner, PERAN_DB['penjual']]);
        }
        $pdo->commit();
        flash_set('success', 'Lapak dan akun penjualnya dihapus.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('lapak hapus: ' . $e->getMessage());
        flash_set('error', 'Gagal menghapus lapak.');
    }
    redirect('api/admin/lapak.php');
}

/* ---------- Simpan (tambah / edit) ---------- */
$in = [
    'nama_lapak'    => trim((string) ($_POST['nama_lapak'] ?? '')),
    'kategori'      => trim((string) ($_POST['kategori'] ?? '')),
    'ukuran'        => (string) ($_POST['ukuran'] ?? ''),
    'no_telepon'    => trim((string) ($_POST['no_telepon'] ?? '')),
    'biaya_sewa'    => trim((string) ($_POST['biaya_sewa'] ?? '')),
    'sewa_mulai'    => (string) ($_POST['sewa_mulai'] ?? ''),
    'sewa_berakhir' => (string) ($_POST['sewa_berakhir'] ?? ''),
    'username'      => trim((string) ($_POST['username'] ?? '')),
    'email'         => trim((string) ($_POST['email'] ?? '')),
];
$password = (string) ($_POST['password'] ?? '');
$balik = 'api/admin/lapak_form.php' . ($id ? '?id=' . $id : '');

$tglValid = static fn(string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

$errors = [];
if ($in['nama_lapak'] === '' || mb_strlen($in['nama_lapak']) > 150) { $errors[] = 'Nama lapak wajib diisi (maks. 150 karakter).'; }
if ($in['kategori'] === '' || mb_strlen($in['kategori']) > 100) { $errors[] = 'Kategori wajib diisi (maks. 100 karakter).'; }
if (!in_array($in['ukuran'], ['kecil', 'sedang', 'besar'], true)) { $errors[] = 'Pilih ukuran tenant.'; }
if (!preg_match('/^[0-9+\- ]{6,20}$/', $in['no_telepon'])) { $errors[] = 'No telepon tidak valid.'; }
if (!ctype_digit($in['biaya_sewa']) || (int) $in['biaya_sewa'] > 1000000000) { $errors[] = 'Biaya sewa harus berupa angka bulat ≥ 0.'; }
if (!$tglValid($in['sewa_mulai']) || !$tglValid($in['sewa_berakhir'])) {
    $errors[] = 'Tanggal sewa tidak valid.';
} elseif ($in['sewa_berakhir'] < $in['sewa_mulai']) {
    $errors[] = 'Tanggal berakhir tidak boleh sebelum tanggal mulai.';
}
if (!$id) {
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $in['username'])) { $errors[] = 'Username penjual 3–30 karakter (huruf, angka, titik, garis bawah).'; }
    if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || strlen($in['email']) > 255) { $errors[] = 'Email penjual tidak valid.'; }
    if (strlen($password) < 8) { $errors[] = 'Password penjual minimal 8 karakter.'; }
} elseif ($password !== '' && strlen($password) < 8) {
    $errors[] = 'Password baru minimal 8 karakter.';
}
if ($errors) {
    $_SESSION['old_lapak'] = $in;
    flash_set('error', implode(' ', $errors));
    redirect($balik);
}

try {
    $pdo->beginTransaction();

    if ($id) {
        $st = $pdo->prepare('SELECT l.owner_id, l.id_sewa FROM ' . SQL_LAPAK . ' l WHERE l.id = ?');
        $st->execute([$id]);
        $lama = $st->fetch();
        if (!$lama) {
            throw new RuntimeException('Lapak tidak ditemukan.');
        }
        $pdo->prepare(
            'UPDATE pedagang SET nama_tenant = ?, kategori_lapak = ?, ukuran_tenant = ?, nomor_telepon = ? WHERE id_pedagang = ?'
        )->execute([$in['nama_lapak'], $in['kategori'], $in['ukuran'], $in['no_telepon'], $id]);

        if ($lama['id_sewa']) {
            $pdo->prepare('UPDATE sewa_lapak SET tgl_mulai = ?, tgl_berakhir = ?, biaya_sewa = ? WHERE id_sewa = ?')
                ->execute([$in['sewa_mulai'], $in['sewa_berakhir'], $in['biaya_sewa'], $lama['id_sewa']]);
        } else {
            $pdo->prepare("INSERT INTO sewa_lapak (id_pedagang, tgl_mulai, tgl_berakhir, biaya_sewa, status_sewa) VALUES (?, ?, ?, ?, 'aktif')")
                ->execute([$id, $in['sewa_mulai'], $in['sewa_berakhir'], $in['biaya_sewa']]);
        }
        if ($password !== '' && $lama['owner_id']) {
            $pdo->prepare('UPDATE pengguna SET kata_sandi = ? WHERE id_pengguna = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $lama['owner_id']]);
        }
        $pesan = 'Data lapak diperbarui.';
    } else {
        $cek = $pdo->prepare('SELECT 1 FROM pengguna WHERE LOWER(nama_pengguna) = LOWER(?) OR LOWER(email_pengguna) = LOWER(?)');
        $cek->execute([$in['username'], $in['email']]);
        if ($cek->fetchColumn()) {
            throw new RuntimeException('Username atau email penjual sudah digunakan.');
        }
        $st = $pdo->prepare(
            'INSERT INTO pengguna (nama_pengguna, email_pengguna, kata_sandi, nomor_telepon, peran) VALUES (?, ?, ?, ?, ?) RETURNING id_pengguna'
        );
        $st->execute([$in['username'], $in['email'], password_hash($password, PASSWORD_DEFAULT), $in['no_telepon'], PERAN_DB['penjual']]);
        $ownerId = (int) $st->fetchColumn();

        $st = $pdo->prepare(
            'INSERT INTO pedagang (id_pengguna, nama_tenant, kategori_lapak, ukuran_tenant, nomor_telepon) VALUES (?, ?, ?, ?, ?) RETURNING id_pedagang'
        );
        $st->execute([$ownerId, $in['nama_lapak'], $in['kategori'], $in['ukuran'], $in['no_telepon']]);
        $pedagangId = (int) $st->fetchColumn();

        $pdo->prepare("INSERT INTO sewa_lapak (id_pedagang, tgl_mulai, tgl_berakhir, biaya_sewa, status_sewa) VALUES (?, ?, ?, ?, 'aktif')")
            ->execute([$pedagangId, $in['sewa_mulai'], $in['sewa_berakhir'], $in['biaya_sewa']]);
        $pesan = 'Lapak dan akun penjual berhasil ditambahkan.';
    }

    $pdo->commit();
    flash_set('success', $pesan);
    redirect('api/admin/lapak.php');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION['old_lapak'] = $in;
    flash_set('error', $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('lapak simpan: ' . $e->getMessage());
    $_SESSION['old_lapak'] = $in;
    flash_set('error', 'Gagal menyimpan data lapak.');
}
redirect($balik);
