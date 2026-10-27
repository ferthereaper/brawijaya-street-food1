<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Atur Stok';
$tab_aktif = 'stok';
require __DIR__ . '/../../includes/init_penjual.php';

$st = $pdo->prepare('SELECT * FROM ' . SQL_MENU . ' m WHERE lapak_id = ? ORDER BY nama_produk');
$st->execute([$lapak['id']]);
$produk = $st->fetchAll();
?>
<div class="judul-bar">Atur Stok</div>
<form method="post" action="<?= e(url('api/penjual/toko_aksi.php')) ?>" class="panel" style="margin-bottom:10px">
    <?= csrf_field() ?>
    <div class="kartu" style="justify-content:space-between">
        <span>Status toko: <strong><?= $lapak['status_toko'] === 'buka' ? 'BUKA' : 'TUTUP' ?></strong></span>
        <button type="submit" class="pil"><?= $lapak['status_toko'] === 'buka' ? 'Tutup Toko' : 'Buka Toko' ?></button>
    </div>
</form>
<form method="post" action="<?= e(url('api/penjual/stok_simpan.php')) ?>" class="panel">
    <?= csrf_field() ?>
    <?php if (!$produk): ?>
        <p class="kosong">Belum ada menu. Tambahkan menu pertama Anda.</p>
    <?php endif; ?>

    <?php foreach ($produk as $p): ?>
        <div class="kartu">
            <div class="thumb" aria-hidden="true"><?= e(inisial($p['nama_produk'])) ?></div>
            <div class="isi">
                <div class="nama"><?= e($p['nama_produk']) ?></div>
                <div class="harga"><?= e(rupiah($p['harga'])) ?></div>
                <div class="stepper" style="margin-top:6px">
                    <button type="button" data-step="-1" aria-label="Kurangi">−</button>
                    <input type="number" name="stok[<?= (int) $p['id'] ?>]" value="<?= (int) $p['stok'] ?>" min="0" max="100000" inputmode="numeric">
                    <button type="button" data-step="1" aria-label="Tambah">+</button>
                    <input type="hidden" name="awal[<?= (int) $p['id'] ?>]" value="<?= (int) $p['stok'] ?>">
                </div>
            </div>
            <a class="pil" href="<?= e(url('api/penjual/produk_form.php?id=' . (int) $p['id'])) ?>">Edit</a>
        </div>
    <?php endforeach; ?>

    <div class="aksi">
        <?php if ($produk): ?><button type="submit" class="tombol" style="background:var(--putih);color:var(--coklat)">Confirm</button><?php endif; ?>
        <a class="tombol" href="<?= e(url('api/penjual/produk_form.php')) ?>">+ Tambah Menu</a>
    </div>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
