<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Riwayat';
$tab_aktif = 'riwayat';
require __DIR__ . '/../../includes/init_penjual.php';

$st = $pdo->prepare(
    "SELECT * FROM " . SQL_PESANAN . " p WHERE lapak_id = ? AND status IN ('selesai', 'dibatalkan')
     ORDER BY COALESCE(selesai_at, created_at) DESC, id DESC LIMIT 100"
);
$st->execute([$lapak['id']]);
$daftar = $st->fetchAll();
?>
<div class="judul-bar">Riwayat</div>
<div class="panel">
    <?php if (!$daftar): ?>
        <p class="kosong">Belum ada riwayat pesanan.</p>
    <?php endif; ?>
    <?php foreach ($daftar as $p): ?>
        <a class="baris-riwayat" href="<?= e(url('api/penjual/riwayat_detail.php?id=' . (int) $p['id'])) ?>">
            <div>
                <div class="no"><?= e($p['kode']) ?></div>
                <div class="tgl"><?= e(waktu_wib($p['selesai_at'] ?? $p['created_at'])) ?>
                    <?php if ($p['status'] === 'dibatalkan'): ?><span class="chip chip-bad">Dibatalkan</span><?php endif; ?>
                </div>
            </div>
            <div class="harga"><?= e(rupiah($p['total_harga'])) ?></div>
            <span class="panah">&gt;</span>
        </a>
    <?php endforeach; ?>
    <?php if (count($daftar) === 100): ?><p class="kosong">Menampilkan 100 transaksi terbaru.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
