<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_pembeli_atau_guest();
bersihkan_kedaluwarsa($pdo);

$cart = cart_ambil('cart');
$lapak = null;
$baris = [];
$total = 0.0;

if ($cart['items']) {
    $st = $pdo->prepare('SELECT l.*, (' . sql_lapak_aktif('l') . ') AS aktif FROM ' . SQL_LAPAK . ' l WHERE l.id = ?');
    $st->execute([$cart['lapak_id']]);
    $lapak = $st->fetch();

    $in = implode(',', array_map('intval', array_keys($cart['items'])));
    $st = $pdo->prepare("SELECT id, nama_produk, kategori, harga, stok FROM " . SQL_MENU . " m WHERE lapak_id = ? AND id IN ($in) ORDER BY nama_produk");
    $st->execute([$cart['lapak_id']]);
    foreach ($st->fetchAll() as $p) {
        $qty = $cart['items'][(int) $p['id']];
        $baris[] = $p + ['qty' => $qty, 'subtotal' => (float) $p['harga'] * $qty];
        $total += (float) $p['harga'] * $qty;
    }
}

$kembali = url('api/pembeli/checkout.php');
$page_title = 'Checkout';
if ($lapak) {
    $judul_header = $lapak['nama_lapak'];
    $lapak_header = $lapak;
}
include __DIR__ . '/../../includes/header.php';
?>
<div class="judul-bar">Halaman Checkout</div>
<div class="panel">
<?php if (!$baris || !$lapak): ?>
    <p class="kosong">Keranjang masih kosong.</p>
    <a class="tombol" href="<?= e(url('api/pembeli/lapak.php')) ?>">Pilih Lapak</a>
<?php else: ?>
    <div class="kartu" style="justify-content:center"><div class="nama"><?= e($lapak['nama_lapak']) ?></div></div>
    <div class="sub-judul">Detail pesanan:</div>

    <?php foreach ($baris as $b): ?>
        <div class="baris-item">
            <div class="isi">
                <div class="nama" style="font-weight:700"><?= e($b['nama_produk']) ?></div>
                <div class="stok" style="font-size:.75rem;opacity:.8"><?= e(rupiah($b['harga'])) ?> / porsi</div>
                <div class="qty-ctrl" style="margin-top:6px">
                    <?= form_keranjang('dec', (int) $b['id'], '−', $kembali, 'btn-kecil') ?>
                    <span class="qty-box"><?= (int) $b['qty'] ?></span>
                    <?= form_keranjang('inc', (int) $b['id'], '+', $kembali, 'btn-kecil') ?>
                    <?= form_keranjang('hapus', (int) $b['id'], '🗑', $kembali, 'btn-kecil', 'Hapus item ini?') ?>
                </div>
            </div>
            <div class="harga"><?= e(rupiah($b['subtotal'])) ?></div>
        </div>
    <?php endforeach; ?>

    <div class="total-bar">Total Pesanan: <?= e(rupiah($total)) ?></div>

    <?php if (!$lapak['aktif']): ?>
        <p class="flash flash-error">Lapak ini sedang tidak menerima pesanan.</p>
    <?php else: ?>
        <form method="post" action="<?= e(url('api/pembeli/buat_pesanan.php')) ?>">
            <?= csrf_field() ?>
            <?php if (!sudah_login()): ?>
                <label for="nama" style="color:#fff">Nama pemesan (opsional)</label>
                <input type="text" id="nama" name="nama" maxlength="100" placeholder="Guest">
            <?php endif; ?>
            <button type="submit" class="tombol" style="margin-top:12px;background:var(--putih);color:var(--coklat)">BAYAR</button>
        </form>
    <?php endif; ?>
    <form method="post" action="<?= e(url('api/keranjang.php')) ?>" data-konfirmasi="Kosongkan seluruh keranjang?">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="kosong">
        <input type="hidden" name="kembali" value="<?= e(url('api/pembeli/lapak.php')) ?>">
        <button type="submit" class="tombol tombol-tipis">Kosongkan Keranjang</button>
    </form>
    <a href="<?= e(url('api/pembeli/menu.php?lapak=' . (int) $lapak['id'])) ?>" style="color:#fff;display:block;text-align:center">+ Tambah menu lain</a>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
