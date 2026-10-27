<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_pembeli_atau_guest();
bersihkan_kedaluwarsa($pdo);

$kode = (string) ($_GET['kode'] ?? '');
$st = $pdo->prepare(
    'SELECT p.*, l.nama_lapak, GREATEST(0, CEIL(EXTRACT(EPOCH FROM (p.kedaluwarsa_at - NOW())))) AS sisa
     FROM ' . SQL_PESANAN . ' p JOIN ' . SQL_LAPAK . ' l ON l.id = p.lapak_id WHERE p.kode = ?'
);
$st->execute([$kode]);
$p = $st->fetch();

$milikSaya = $p && (
    in_array($kode, $_SESSION['pesanan_saya'] ?? [], true)
    || (sudah_login() && (int) $p['user_id'] === (int) $_SESSION['user_id'])
);
if (!$milikSaya) {
    flash_set('error', 'Pesanan tidak ditemukan.');
    redirect('api/pembeli/lapak.php');
}

$items = ambil_items_pesanan($pdo, [(int) $p['id']])[(int) $p['id']] ?? [];
$qrisFile = is_file(__DIR__ . '/../../assets/img/qris.png') ? 'assets/img/qris.png' : 'assets/img/qris-contoh.svg';

$page_title = 'Pembayaran';
$judul_header = 'Pembayaran';
$lapak_header = ['nama_lapak' => $p['nama_lapak']];
include __DIR__ . '/../../includes/header.php';
?>
<?php if ($p['status'] === 'menunggu'): ?>
    <div class="qr-box"><img src="<?= e(url($qrisFile)) ?>" alt="Kode QRIS pembayaran"></div>

    <div class="kotak-bayar">
        <h2>Selesaikan Pembayaran</h2>
        <p class="petunjuk">Scan Qris dan Bayar Sebelum</p>
        <div class="hitung-mundur"><span data-sisa="<?= (int) $p['sisa'] ?>">--</span></div>

        <div class="total-transaksi">Total Transaksi<strong><?= e(rupiah($p['total_harga'])) ?></strong></div>
        <p class="petunjuk">Buka aplikasi pembayaran kamu melalui aplikasi LinkAja, Dana, GoPay dan E-Wallet lainnya, scan QRIS, kemudian bayar.</p>

        <?php if (SIMULASI_PEMBAYARAN): ?>
            <form method="post" action="<?= e(url('api/pembeli/bayar_konfirmasi.php')) ?>" style="margin-top:14px">
                <?= csrf_field() ?>
                <input type="hidden" name="kode" value="<?= e($p['kode']) ?>">
                <button type="submit" class="pil-besar">Simulasi: Saya Sudah Bayar</button>
                <p class="petunjuk" style="font-size:.72rem">Mode demo — belum terhubung ke payment gateway.</p>
            </form>
        <?php endif; ?>
    </div>

<?php elseif ($p['status'] === 'dibatalkan'): ?>
    <div class="kotak-bayar">
        <div class="centang">✕</div>
        <h2>Pesanan Dibatalkan</h2>
        <p class="petunjuk">Waktu pembayaran habis atau pesanan dibatalkan. Stok sudah dikembalikan.</p>
        <p style="margin-top:12px"><a class="pil" href="<?= e(url('api/pembeli/lapak.php')) ?>">Pesan Lagi</a></p>
    </div>

<?php else: ?>
    <div class="kotak-bayar" <?= $p['status'] === 'diproses' ? 'data-refresh="15"' : '' ?>>
        <div class="centang"><?= $p['status'] === 'selesai' ? '✔' : '⏳' ?></div>
        <h2><?= $p['status'] === 'selesai' ? 'Pesanan Selesai' : 'Pembayaran Diterima' ?></h2>
        <p class="petunjuk">
            <?= $p['status'] === 'selesai'
                ? 'Silakan ambil pesanan Anda di lapak. Terima kasih!'
                : 'Pesanan Anda sedang disiapkan oleh lapak. Halaman ini memperbarui status otomatis.' ?>
        </p>
        <p style="margin-top:12px"><a class="pil" href="<?= e(url('api/pembeli/lapak.php')) ?>">Kembali ke List Lapak</a></p>
    </div>
<?php endif; ?>

<div class="panel" style="margin-top:16px">
    <div class="kartu" style="display:block">
        <div class="kepala" style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap">
            <strong>No Pesanan <?= e($p['kode']) ?></strong>
            <span class="chip"><?= e(label_status($p['status'])) ?></span>
        </div>
        <div style="font-size:.8rem;opacity:.85"><?= e($p['nama_lapak']) ?> · <?= e(waktu_wib($p['created_at'])) ?></div>
    </div>
    <?php foreach ($items as $it): ?>
        <div class="baris-item">
            <div class="isi"><?= e($it['nama_produk']) ?> <span class="chip">×<?= (int) $it['qty'] ?></span></div>
            <div class="harga"><?= e(rupiah($it['harga'] * $it['qty'])) ?></div>
        </div>
    <?php endforeach; ?>
    <div class="total-bar">Total: <?= e(rupiah($p['total_harga'])) ?></div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
