<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_peran('penjual');
wajib_post('api/penjual/stok.php');

$lapak = lapak_saya($pdo);
if ($lapak) {
    $baru = $lapak['status_toko'] === 'buka' ? 'tutup' : 'buka';
    $pdo->prepare('UPDATE pedagang SET status_toko = ? WHERE id_pedagang = ?')->execute([$baru, $lapak['id']]);
    flash_set('success', $baru === 'buka' ? 'Toko dibuka.' : 'Toko ditutup.');
}
redirect('api/penjual/stok.php');
