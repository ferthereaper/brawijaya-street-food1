<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_pembeli_atau_guest();
wajib_post('api/pembeli/checkout.php');

$cart = cart_ambil('cart');
if (!$cart['items']) {
    flash_set('error', 'Keranjang masih kosong.');
    redirect('api/pembeli/lapak.php');
}

$nama = sudah_login() ? (string) $_SESSION['nama'] : trim((string) ($_POST['nama'] ?? ''));
$nama = mb_substr($nama, 0, 100);

try {
    $hasil = buat_pesanan(
        $pdo, $cart['lapak_id'], $cart['items'], 'qris',
        sudah_login() ? (int) $_SESSION['user_id'] : null, $nama, true
    );
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
    redirect('api/pembeli/checkout.php');
} catch (Throwable $e) {
    error_log('buat_pesanan: ' . $e->getMessage());
    flash_set('error', 'Pesanan gagal dibuat. Silakan coba lagi.');
    redirect('api/pembeli/checkout.php');
}

cart_simpan('cart', 0, []);
// Catat pesanan ini milik sesi saat ini (agar guest bisa membuka halaman pembayaran)
$_SESSION['pesanan_saya'] = array_slice(array_merge($_SESSION['pesanan_saya'] ?? [], [$hasil['kode']]), -20);

redirect('api/pembeli/bayar.php?kode=' . urlencode($hasil['kode']));
