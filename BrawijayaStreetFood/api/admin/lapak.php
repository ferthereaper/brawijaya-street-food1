<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'List Lapak';
$tab_aktif = 'list';
require __DIR__ . '/../../includes/init_admin.php';

$daftar = $pdo->query('SELECT id, nama_lapak, kategori, ukuran FROM ' . SQL_LAPAK . ' l ORDER BY nama_lapak')->fetchAll();
const TAMPIL_AWAL = 9;
?>
<div class="judul-bar">List Lapak</div>
<div class="panel">
    <?php if (!$daftar): ?>
        <p class="kosong">Belum ada lapak. Tambahkan lapak pertama.</p>
        <a class="tombol" href="<?= e(url('api/admin/lapak_form.php')) ?>">+ Tambah Lapak</a>
    <?php else: ?>
        <p class="petunjuk" style="color:#fff;font-size:.8rem;text-align:center">Pilih lapak untuk mengubah data.</p>
        <div class="grid-lapak" id="grid-lapak">
            <?php foreach ($daftar as $i => $l): ?>
                <a class="lapak-item<?= $i >= TAMPIL_AWAL ? ' lebih' : '' ?>"
                   href="<?= e(url('api/admin/lapak_form.php?id=' . (int) $l['id'])) ?>"
                   title="<?= e($l['kategori'] . ' · ukuran ' . $l['ukuran']) ?>">
                    <span class="lingkaran"><?= e(inisial($l['nama_lapak'])) ?></span>
                    <span class="lapak-label"><?= e($l['nama_lapak']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (count($daftar) > TAMPIL_AWAL): ?>
            <button type="button" class="toggle-lebih" data-lebih-toggle="#grid-lapak">Selengkapnya...</button>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
