<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Menu';
$tab_aktif = 'stok';
require __DIR__ . '/../../includes/init_penjual.php';

$produk = ['id' => '', 'nama_produk' => '', 'kategori' => '', 'harga' => '', 'stok' => '0'];
$edit = false;
if (isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM ' . SQL_MENU . ' m WHERE id = ? AND lapak_id = ?');
    $st->execute([(int) $_GET['id'], $lapak['id']]);
    if ($data = $st->fetch()) {
        $produk = $data;
        $edit = true;
    }
}
?>
<div class="judul-bar"><?= $edit ? 'Edit Menu' : 'Tambah Menu' ?></div>
<div class="panel">
    <form method="post" action="<?= e(url('api/penjual/produk_proses.php')) ?>" class="panel-krem">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="simpan">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $produk['id'] ?>"><?php endif; ?>

        <label for="nama_produk">Nama Menu</label>
        <input type="text" id="nama_produk" name="nama_produk" value="<?= e($produk['nama_produk']) ?>" required maxlength="150">

        <label for="kategori">Kategori</label>
        <input type="text" id="kategori" name="kategori" value="<?= e($produk['kategori']) ?>" required maxlength="100" list="daftar-kategori" placeholder="mis. Makanan, Minuman, Camilan">
        <datalist id="daftar-kategori"><option value="Makanan"><option value="Minuman"><option value="Camilan"></datalist>

        <label for="harga">Harga (Rp)</label>
        <input type="number" id="harga" name="harga" value="<?= e($produk['harga'] === '' ? '' : (int) $produk['harga']) ?>" min="0" max="100000000" required inputmode="numeric">

        <label for="stok">Stok</label>
        <input type="number" id="stok" name="stok" value="<?= (int) $produk['stok'] ?>" min="0" max="100000" required inputmode="numeric">

        <div class="aksi">
            <button type="submit" class="tombol">Simpan</button>
            <a class="tombol tombol-tipis" href="<?= e(url('api/penjual/stok.php')) ?>">Batal</a>
        </div>
    </form>

    <?php if ($edit): ?>
        <form method="post" action="<?= e(url('api/penjual/produk_proses.php')) ?>" data-konfirmasi="Hapus menu ini? Riwayat pesanan tetap tersimpan.">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int) $produk['id'] ?>">
            <button type="submit" class="tombol tombol-bahaya">Hapus Menu</button>
        </form>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
