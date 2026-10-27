<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_peran('admin');
wajib_post('api/admin/status_sewa.php');

$id = (int) ($_POST['id'] ?? 0); // id_pedagang
$aksi = (string) ($_POST['aksi'] ?? '');

$st = $pdo->prepare('SELECT l.*, ' . SQL_HARI_INI . ' AS hari_ini FROM ' . SQL_LAPAK . ' l WHERE l.id = ?');
$st->execute([$id]);
$l = $st->fetch();
if (!$l) {
    flash_set('error', 'Lapak tidak ditemukan.');
    redirect('api/admin/status_sewa.php');
}

try {
    if ($aksi === 'perpanjang') {
        // Tambah catatan sewa baru (riwayat sewa tetap tersimpan): mulai dari tgl berakhir lama / hari ini bila sudah lewat
        $dasar = ($l['sewa_berakhir'] !== null && $l['sewa_berakhir'] > $l['hari_ini']) ? $l['sewa_berakhir'] : $l['hari_ini'];
        $pdo->prepare(
            "INSERT INTO sewa_lapak (id_pedagang, tgl_mulai, tgl_berakhir, biaya_sewa, status_sewa)
             VALUES (?, ?::date, ?::date + 30, ?, 'aktif')"
        )->execute([$id, $dasar, $dasar, $l['biaya_sewa'] ?? 0]);
        flash_set('success', 'Sewa diperpanjang 30 hari (biaya sewa sama dengan periode sebelumnya).');
    } elseif ($aksi === 'toggle' && $l['id_sewa']) {
        $baru = $l['status_sewa'] === 'aktif' ? 'nonaktif' : 'aktif';
        $pdo->prepare('UPDATE sewa_lapak SET status_sewa = ? WHERE id_sewa = ?')->execute([$baru, $l['id_sewa']]);
        flash_set('success', 'Status sewa diperbarui.');
    }
} catch (Throwable $e) {
    error_log('sewa_aksi: ' . $e->getMessage());
    flash_set('error', 'Gagal memperbarui sewa.');
}
redirect('api/admin/status_sewa.php');
