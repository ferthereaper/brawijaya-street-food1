<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_peran('penjual');
wajib_post('api/penjual/kasir.php');

$lapak = lapak_saya($pdo);
$cart = cart_ambil('kasir_cart');
if (!$lapak || !$cart['items'] || $cart['lapak_id'] !== (int) $lapak['id']) {
    flash_set('error', 'Pesanan kasir masih kosong.');
    redirect('api/penjual/kasir.php');
}

$nama = mb_substr(trim((string) ($_POST['nama'] ?? '')), 0, 100);
try {
    $hasil = buat_pesanan($pdo, (int) $lapak['id'], $cart['items'], 'tunai', null, $nama !== '' ? $nama : 'Pembeli di tempat', false);
    cart_simpan('kasir_cart', 0, []);
    flash_set('success', 'Pesanan ' . $hasil['kode'] . ' (' . rupiah($hasil['total']) . ') masuk ke Daftar Pesanan.');
    redirect('api/penjual/pesanan.php');
} catch (RuntimeException $e) {
    flash_set('error', $e->getMessage());
} catch (Throwable $e) {
    error_log('kasir_proses: ' . $e->getMessage());
    flash_set('error', 'Pesanan gagal diproses.');
}
redirect('api/penjual/kasir.php');
