<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_peran('penjual');
wajib_post('api/penjual/stok.php');

$lapak = lapak_saya($pdo);
if (!$lapak) {
    redirect('api/penjual/stok.php');
}

$aksi = (string) ($_POST['aksi'] ?? '');
$id = (int) ($_POST['id'] ?? 0);

if ($aksi === 'hapus' && $id) {
    try {
        $st = $pdo->prepare('DELETE FROM menu WHERE id_menu = ? AND id_pedagang = ?');
        $st->execute([$id, $lapak['id']]);
        flash_set('success', 'Menu dihapus.');
    } catch (PDOException $e) {
        // 23503 = foreign_key_violation: menu sudah pernah dipesan, riwayatnya harus tetap utuh
        if ($e->getCode() === '23503') {
            flash_set('error', 'Menu ini sudah pernah dipesan sehingga tidak bisa dihapus. Atur stoknya menjadi 0 agar tidak bisa dipesan.');
        } else {
            error_log('produk hapus: ' . $e->getMessage());
            flash_set('error', 'Gagal menghapus menu.');
        }
    }
    redirect('api/penjual/stok.php');
}

$nama = trim((string) ($_POST['nama_produk'] ?? ''));
$kategori = trim((string) ($_POST['kategori'] ?? ''));
$harga = $_POST['harga'] ?? '';
$stok = $_POST['stok'] ?? '';

$balik = 'api/penjual/produk_form.php' . ($id ? '?id=' . $id : '');
$errors = [];
if ($nama === '' || mb_strlen($nama) > 150) { $errors[] = 'Nama menu wajib diisi (maks. 150 karakter).'; }
if ($kategori === '' || mb_strlen($kategori) > 100) { $errors[] = 'Kategori wajib diisi (maks. 100 karakter).'; }
if (!ctype_digit((string) $harga) || (int) $harga > 100000000) { $errors[] = 'Harga harus berupa angka bulat ≥ 0.'; }
if (!ctype_digit((string) $stok) || (int) $stok > 100000) { $errors[] = 'Stok harus berupa angka bulat ≥ 0.'; }
if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($balik);
}

if ($id) {
    $st = $pdo->prepare('UPDATE menu SET nama_menu = ?, kategori = ?, harga = ?, stok = ? WHERE id_menu = ? AND id_pedagang = ?');
    $st->execute([$nama, $kategori, $harga, $stok, $id, $lapak['id']]);
    flash_set('success', 'Menu diperbarui.');
} else {
    $st = $pdo->prepare('INSERT INTO menu (id_pedagang, nama_menu, kategori, harga, stok) VALUES (?, ?, ?, ?, ?)');
    $st->execute([$lapak['id'], $nama, $kategori, $harga, $stok]);
    flash_set('success', 'Menu ditambahkan.');
}
redirect('api/penjual/stok.php');
