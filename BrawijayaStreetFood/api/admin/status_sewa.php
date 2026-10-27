<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Status Sewa';
$tab_aktif = 'sewa';
require __DIR__ . '/../../includes/init_admin.php';

$daftar = $pdo->query(
    'SELECT l.*, (l.sewa_berakhir - ' . SQL_HARI_INI . ') AS sisa_hari
     FROM ' . SQL_LAPAK . ' l
     ORDER BY l.sewa_berakhir ASC NULLS FIRST, l.nama_lapak'
)->fetchAll();

function chip_sewa(array $l): array
{
    if ($l['sewa_berakhir'] === null)  { return ['Belum ada sewa', 'chip-bad']; }
    if ($l['status_sewa'] !== 'aktif') { return ['Nonaktif', 'chip-bad']; }
    if ((int) $l['sisa_hari'] < 0)     { return ['Berakhir', 'chip-bad']; }
    if ((int) $l['sisa_hari'] <= 7)    { return ['Segera berakhir', 'chip-warn']; }
    return ['Aktif', 'chip-ok'];
}
?>
<div class="judul-bar">Status Sewa</div>
<div class="panel">
    <?php if (!$daftar): ?><p class="kosong">Belum ada lapak.</p><?php endif; ?>
    <?php foreach ($daftar as $l): [$label, $kelas] = chip_sewa($l); ?>
        <div class="kartu-sewa">
            <div class="kepala">
                <span class="nama"><?= e($l['nama_lapak']) ?></span>
                <span class="chip <?= $kelas ?>"><?= e($label) ?></span>
            </div>
            <div class="meta">
                <?= e($l['kategori']) ?> · ukuran <?= e($l['ukuran']) ?> · <?= e($l['no_telepon']) ?><br>
                <?php if ($l['sewa_berakhir'] !== null): ?>
                    Sewa: <?= e(date('d M Y', strtotime($l['sewa_mulai']))) ?> – <?= e(date('d M Y', strtotime($l['sewa_berakhir']))) ?>
                    · <?= e(rupiah($l['biaya_sewa'])) ?>
                    <?php if ((int) $l['sisa_hari'] >= 0 && $l['status_sewa'] === 'aktif'): ?>
                        (sisa <?= (int) $l['sisa_hari'] ?> hari)
                    <?php endif; ?>
                <?php endif; ?>
                <?= $l['status_toko'] !== 'buka' ? '<br>Toko sedang ditutup oleh penjual.' : '' ?>
            </div>
            <div class="aksi">
                <form method="post" action="<?= e(url('api/admin/sewa_aksi.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                    <input type="hidden" name="aksi" value="perpanjang">
                    <button type="submit" class="tombol" style="background:var(--putih);color:var(--coklat);padding:8px">Perpanjang 30 hari</button>
                </form>
                <form method="post" action="<?= e(url('api/admin/sewa_aksi.php')) ?>"
                      <?= $l['status_sewa'] === 'aktif' ? 'data-konfirmasi="Nonaktifkan lapak ini? Lapak tidak akan tampil bagi pembeli."' : '' ?>>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                    <input type="hidden" name="aksi" value="toggle">
                    <button type="submit" class="tombol tombol-tipis" style="padding:8px"><?= $l['status_sewa'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
