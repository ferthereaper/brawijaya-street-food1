<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Pendapatan';
$tab_aktif = 'pendapatan';
require __DIR__ . '/../../includes/init_penjual.php';

// Hanya pesanan berstatus 'selesai', dihitung berdasarkan waktu selesai (WIB)
$tz = "AT TIME ZONE 'Asia/Jakarta'";
$st = $pdo->prepare(
    "SELECT
        COALESCE(SUM(total_harga) FILTER (WHERE (COALESCE(tgl_selesai, tgl_pesanan) $tz)::date = (NOW() $tz)::date), 0) AS hari,
        COALESCE(SUM(total_harga) FILTER (WHERE date_trunc('week',  COALESCE(tgl_selesai, tgl_pesanan) $tz) = date_trunc('week',  NOW() $tz)), 0) AS minggu,
        COALESCE(SUM(total_harga) FILTER (WHERE date_trunc('month', COALESCE(tgl_selesai, tgl_pesanan) $tz) = date_trunc('month', NOW() $tz)), 0) AS bulan,
        COALESCE(SUM(total_harga) FILTER (WHERE date_trunc('year',  COALESCE(tgl_selesai, tgl_pesanan) $tz) = date_trunc('year',  NOW() $tz)), 0) AS tahun,
        COUNT(*) FILTER (WHERE (COALESCE(tgl_selesai, tgl_pesanan) $tz)::date = (NOW() $tz)::date) AS jumlah_hari
     FROM pesanan WHERE id_pedagang = ? AND status_pesanan = 'selesai'"
);
$st->execute([$lapak['id']]);
$r = $st->fetch();
?>
<div class="judul-bar">Pendapatan</div>
<div class="panel">
    <div class="kartu-pendapatan">
        <div class="label">Pendapatan hari ini:</div>
        <div class="nilai"><?= e(rupiah($r['hari'])) ?></div>
        <div style="font-size:.8rem;opacity:.85"><?= (int) $r['jumlah_hari'] ?> pesanan selesai</div>
    </div>
    <div class="grid-2">
        <div class="kartu-pendapatan"><div class="label">Minggu ini</div><div class="nilai"><?= e(rupiah($r['minggu'])) ?></div></div>
        <div class="kartu-pendapatan"><div class="label">Bulan ini</div><div class="nilai"><?= e(rupiah($r['bulan'])) ?></div></div>
        <div class="kartu-pendapatan grid-1-penuh"><div class="label">Tahun ini</div><div class="nilai"><?= e(rupiah($r['tahun'])) ?></div></div>
    </div>
    <p class="petunjuk" style="color:#fff;font-size:.75rem;text-align:center">Dihitung dari pesanan berstatus Selesai (zona waktu WIB).</p>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
