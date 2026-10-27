<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Daftar Pesanan';
$tab_aktif = 'pesanan';
require __DIR__ . '/../../includes/init_penjual.php';

$st = $pdo->prepare("SELECT * FROM " . SQL_PESANAN . " p WHERE lapak_id = ? AND status = 'diproses' ORDER BY COALESCE(dibayar_at, created_at) ASC, id ASC");
$st->execute([$lapak['id']]);
$daftar = $st->fetchAll();
$items = ambil_items_pesanan($pdo, array_column($daftar, 'id'));

$st = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE id_pedagang = ? AND status_pesanan = 'menunggu'");
$st->execute([$lapak['id']]);
$menunggu = (int) $st->fetchColumn();
?>
<div class="judul-bar">Daftar Pesanan</div>
<div class="panel" <?= '' ?> data-refresh="30">
    <?php if ($menunggu): ?>
        <p class="flash flash-info" style="margin:0"><?= $menunggu ?> pesanan menunggu pembayaran QRIS.</p>
    <?php endif; ?>
    <?php if (!$daftar): ?>
        <p class="kosong">Belum ada pesanan yang perlu diproses.</p>
    <?php endif; ?>

    <?php foreach ($daftar as $p): ?>
        <div class="kartu-pesanan">
            <div class="kepala">
                <span class="no"><?= e($p['kode']) ?></span>
                <span class="chip"><?= $p['metode'] === 'tunai' ? 'Tunai' : 'QRIS' ?></span>
            </div>
            <div class="meta"><?= e($p['nama_pemesan']) ?> · <?= e(waktu_wib($p['dibayar_at'] ?? $p['created_at'], 'd M H:i')) ?></div>
            <ul>
                <?php foreach ($items[(int) $p['id']] ?? [] as $it): ?>
                    <li><span><?= e($it['nama_produk']) ?> ×<?= (int) $it['qty'] ?></span><span><?= e(rupiah($it['harga'] * $it['qty'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <strong>Total: <?= e(rupiah($p['total_harga'])) ?></strong>
            <div class="aksi">
                <form method="post" action="<?= e(url('api/penjual/pesanan_aksi.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <input type="hidden" name="aksi" value="selesai">
                    <button type="submit" class="tombol" style="background:var(--putih);color:var(--coklat)">Tandai Selesai</button>
                </form>
                <form method="post" action="<?= e(url('api/penjual/pesanan_aksi.php')) ?>" data-konfirmasi="Batalkan pesanan ini? Stok akan dikembalikan.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <input type="hidden" name="aksi" value="batal">
                    <button type="submit" class="tombol tombol-bahaya">Batalkan</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
