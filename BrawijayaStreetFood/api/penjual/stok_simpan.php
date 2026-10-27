<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_peran('penjual');
wajib_post('api/penjual/stok.php');

$lapak = lapak_saya($pdo);
if (!$lapak) {
    redirect('api/penjual/stok.php');
}

$stokBaru = $_POST['stok'] ?? [];
$stokAwal = $_POST['awal'] ?? [];
if (!is_array($stokBaru) || !is_array($stokAwal)) {
    redirect('api/penjual/stok.php');
}

// Terapkan SELISIH (bukan nilai mutlak) agar penjualan yang terjadi saat halaman terbuka tidak tertimpa.
$upd = $pdo->prepare('UPDATE menu SET stok = GREATEST(0, stok + :selisih) WHERE id_menu = :id AND id_pedagang = :lapak');
$diubah = 0;
try {
    $pdo->beginTransaction();
    foreach ($stokBaru as $id => $nilai) {
        $id = (int) $id;
        if (!isset($stokAwal[$id]) || !is_numeric($nilai) || !is_numeric($stokAwal[$id])) {
            continue;
        }
        $baru = max(0, min(100000, (int) $nilai));
        $selisih = $baru - (int) $stokAwal[$id];
        if ($selisih !== 0) {
            $upd->execute(['selisih' => $selisih, 'id' => $id, 'lapak' => $lapak['id']]);
            $diubah += $upd->rowCount();
        }
    }
    $pdo->commit();
    flash_set('success', $diubah ? "Stok $diubah menu berhasil diperbarui." : 'Tidak ada perubahan stok.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('stok_simpan: ' . $e->getMessage());
    flash_set('error', 'Gagal menyimpan stok.');
}
redirect('api/penjual/stok.php');
