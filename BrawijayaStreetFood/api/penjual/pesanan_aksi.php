<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_peran('penjual');
wajib_post('api/penjual/pesanan.php');

$lapak = lapak_saya($pdo);
$id = (int) ($_POST['id'] ?? 0);
$aksi = (string) ($_POST['aksi'] ?? '');
if (!$lapak || !$id || !in_array($aksi, ['selesai', 'batal'], true)) {
    redirect('api/penjual/pesanan.php');
}

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT id_pesanan AS id, status_pesanan::text AS status FROM pesanan WHERE id_pesanan = ? AND id_pedagang = ? FOR UPDATE');
    $st->execute([$id, $lapak['id']]);
    $p = $st->fetch();

    if (!$p || $p['status'] !== 'diproses') {
        $pdo->rollBack();
        flash_set('error', 'Pesanan tidak ditemukan atau statusnya sudah berubah.');
        redirect('api/penjual/pesanan.php');
    }

    if ($aksi === 'selesai') {
        $pdo->prepare("UPDATE pesanan SET status_pesanan = 'selesai', tgl_selesai = NOW() WHERE id_pesanan = ?")->execute([$id]);
        flash_set('success', 'Pesanan ditandai selesai.');
    } else {
        batalkan_pesanan_tx($pdo, $id);
        flash_set('success', 'Pesanan dibatalkan dan stok dikembalikan.');
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('pesanan_aksi: ' . $e->getMessage());
    flash_set('error', 'Gagal memperbarui pesanan.');
}
redirect('api/penjual/pesanan.php');
