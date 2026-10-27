<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Kasir';
$tab_aktif = 'kasir';
require __DIR__ . '/../../includes/init_penjual.php';

$st = $pdo->prepare('SELECT * FROM ' . SQL_MENU . ' m WHERE lapak_id = ? ORDER BY nama_produk');
$st->execute([$lapak['id']]);
$produk = $st->fetchAll();

$cart = cart_ambil('kasir_cart');
if ($cart['lapak_id'] !== (int) $lapak['id']) {
    $cart = ['lapak_id' => 0, 'items' => []];
}
$map = [];
foreach ($produk as $p) { $map[(int) $p['id']] = $p; }

$baris = [];
$total = 0.0;
foreach ($cart['items'] as $pid => $qty) {
    if (isset($map[$pid])) {
        $sub = (float) $map[$pid]['harga'] * $qty;
        $baris[] = ['p' => $map[$pid], 'qty' => $qty, 'sub' => $sub];
        $total += $sub;
    }
}
$kembali = url('api/penjual/kasir.php');
?>
<div class="judul-bar">Kasir</div>
<div class="panel">
    <?php if (!$produk): ?>
        <p class="kosong">Belum ada menu. Tambahkan di tab Stok.</p>
    <?php endif; ?>
    <?php foreach ($produk as $p): $habis = (int) $p['stok'] <= 0; ?>
        <div class="kartu">
            <div class="thumb" aria-hidden="true"><?= e(inisial($p['nama_produk'])) ?></div>
            <div class="isi">
                <div class="nama"><?= e($p['nama_produk']) ?></div>
                <div class="harga"><?= e(rupiah($p['harga'])) ?></div>
                <div class="stok"><?= $habis ? 'Stok habis' : 'Stok: ' . (int) $p['stok'] ?></div>
            </div>
            <?php if ($habis): ?>
                <button type="button" class="btn-plus" disabled>+</button>
            <?php else: ?>
                <?= form_keranjang('add', (int) $p['id'], '+', $kembali, 'btn-plus') ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($baris): ?>
<div class="panel" style="margin-top:16px">
    <div class="sub-judul">Pesanan di kasir</div>
    <?php foreach ($baris as $b): ?>
        <div class="baris-item">
            <div class="isi">
                <div style="font-weight:700"><?= e($b['p']['nama_produk']) ?></div>
                <div class="qty-ctrl" style="margin-top:6px">
                    <?= form_keranjang('dec', (int) $b['p']['id'], '−', $kembali, 'btn-kecil') ?>
                    <span class="qty-box"><?= (int) $b['qty'] ?></span>
                    <?= form_keranjang('inc', (int) $b['p']['id'], '+', $kembali, 'btn-kecil') ?>
                    <?= form_keranjang('hapus', (int) $b['p']['id'], '🗑', $kembali, 'btn-kecil') ?>
                </div>
            </div>
            <div class="harga"><?= e(rupiah($b['sub'])) ?></div>
        </div>
    <?php endforeach; ?>
    <div class="total-bar">Total: <?= e(rupiah($total)) ?></div>

    <form method="post" action="<?= e(url('api/penjual/kasir_proses.php')) ?>">
        <?= csrf_field() ?>
        <label for="nama" style="color:#fff">Nama pemesan (opsional)</label>
        <input type="text" id="nama" name="nama" maxlength="100" placeholder="Pembeli di tempat">
        <button type="submit" class="tombol" style="margin-top:12px;background:var(--putih);color:var(--coklat)">Proses Pesanan (Tunai)</button>
    </form>
    <form method="post" action="<?= e(url('api/keranjang.php')) ?>" data-konfirmasi="Kosongkan pesanan kasir?">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="kosong">
        <input type="hidden" name="kembali" value="<?= e($kembali) ?>">
        <button type="submit" class="tombol tombol-tipis">Kosongkan</button>
    </form>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
