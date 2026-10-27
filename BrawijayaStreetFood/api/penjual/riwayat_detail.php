<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Detail Riwayat';
$tab_aktif = 'riwayat';
require __DIR__ . '/../../includes/init_penjual.php';

$st = $pdo->prepare('SELECT * FROM ' . SQL_PESANAN . ' p WHERE id = ? AND lapak_id = ?');
$st->execute([(int) ($_GET['id'] ?? 0), $lapak['id']]);
$p = $st->fetch();
if (!$p) {
    flash_set('error', 'Pesanan tidak ditemukan.');
    redirect('api/penjual/riwayat.php');
}
$items = ambil_items_pesanan($pdo, [(int) $p['id']])[(int) $p['id']] ?? [];
?>
<div class="judul-bar">Detail Pesanan</div>
<div class="panel">
    <div class="kartu-pesanan">
        <div class="kepala">
            <span class="no"><?= e($p['kode']) ?></span>
            <span class="chip <?= $p['status'] === 'selesai' ? 'chip-ok' : 'chip-bad' ?>"><?= e(label_status($p['status'])) ?></span>
        </div>
        <div class="meta">
            <?= e($p['nama_pemesan']) ?> · <?= $p['metode'] === 'tunai' ? 'Tunai' : 'QRIS' ?><br>
            Dibuat: <?= e(waktu_wib($p['created_at'])) ?><br>
            Dibayar: <?= e(waktu_wib($p['dibayar_at'])) ?><br>
            Selesai: <?= e(waktu_wib($p['selesai_at'])) ?>
        </div>
        <ul>
            <?php foreach ($items as $it): ?>
                <li><span><?= e($it['nama_produk']) ?> ×<?= (int) $it['qty'] ?></span><span><?= e(rupiah($it['harga'] * $it['qty'])) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <strong>Total: <?= e(rupiah($p['total_harga'])) ?></strong>
    </div>
    <a class="tombol tombol-tipis" href="<?= e(url('api/penjual/riwayat.php')) ?>">&larr; Kembali</a>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
