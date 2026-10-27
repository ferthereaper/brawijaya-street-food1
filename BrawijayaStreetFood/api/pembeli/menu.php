<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_pembeli_atau_guest();
bersihkan_kedaluwarsa($pdo);

$lapakId = (int) ($_GET['lapak'] ?? 0);
$st = $pdo->prepare('SELECT l.* FROM ' . SQL_LAPAK . ' l WHERE l.id = ? AND ' . sql_lapak_aktif('l'));
$st->execute([$lapakId]);
$lapak = $st->fetch();
if (!$lapak) {
    flash_set('error', 'Lapak tidak ditemukan atau sedang tutup.');
    redirect('api/pembeli/lapak.php');
}

$st = $pdo->prepare('SELECT * FROM ' . SQL_MENU . ' m WHERE lapak_id = ? ORDER BY kategori, nama_produk');
$st->execute([$lapakId]);
$produk = $st->fetchAll();

$cart = cart_ambil('cart');
$jumlahCart = array_sum($cart['items']);
$bedaLapak = $jumlahCart > 0 && $cart['lapak_id'] !== $lapakId;
$kembali = url('api/pembeli/menu.php?lapak=' . $lapakId);

$page_title = $lapak['nama_lapak'];
$judul_header = $lapak['nama_lapak'];
$lapak_header = $lapak;
include __DIR__ . '/../../includes/header.php';
?>
<div class="judul-bar">Halaman Pesanan</div>
<div class="panel">
    <?php if (!$produk): ?>
        <p class="kosong">Lapak ini belum memiliki menu.</p>
    <?php endif; ?>

    <?php foreach ($produk as $p): $habis = (int) $p['stok'] <= 0; $dipesan = $cart['items'][(int) $p['id']] ?? 0; ?>
        <div class="kartu">
            <div class="thumb" aria-hidden="true"><?= e(inisial($p['nama_produk'])) ?></div>
            <div class="isi">
                <div class="nama"><?= e($p['nama_produk']) ?><?= $dipesan && $cart['lapak_id'] === $lapakId ? ' <span class="chip">×' . (int) $dipesan . '</span>' : '' ?></div>
                <div class="desk"><?= e($p['kategori']) ?></div>
                <div class="harga"><?= e(rupiah($p['harga'])) ?></div>
                <div class="stok"><?= $habis ? 'Stok habis' : 'Stok: ' . (int) $p['stok'] ?></div>
            </div>
            <?php if ($habis): ?>
                <button type="button" class="btn-plus" disabled aria-label="Stok habis">+</button>
            <?php else: ?>
                <?= form_keranjang(
                    'add', (int) $p['id'], '+', $kembali, 'btn-plus',
                    $bedaLapak ? 'Keranjang Anda berisi pesanan dari lapak lain. Kosongkan dan mulai pesanan baru dari lapak ini?' : '',
                    true
                ) ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<p style="margin-top:14px"><a href="<?= e(url('api/pembeli/lapak.php')) ?>">&larr; Kembali ke List Lapak</a></p>

<a class="fab" href="<?= e(url('api/pembeli/checkout.php')) ?>" aria-label="Lihat keranjang">
    <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M2 3h3l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 7H6"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>
    </svg>
    <span class="fab-badge" data-cart-count><?= (int) $jumlahCart ?></span>
</a>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
