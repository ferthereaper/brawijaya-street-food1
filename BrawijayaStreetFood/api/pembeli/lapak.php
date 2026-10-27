<?php
require_once __DIR__ . '/../../includes/functions.php';
wajib_pembeli_atau_guest();
bersihkan_kedaluwarsa($pdo);

$daftar = $pdo->query(
    'SELECT l.id, l.nama_lapak, l.kategori FROM ' . SQL_LAPAK . ' l WHERE ' . sql_lapak_aktif('l') . ' ORDER BY l.nama_lapak'
)->fetchAll();

$page_title = 'List Lapak';
include __DIR__ . '/../../includes/header.php';
const TAMPIL_AWAL = 9;
?>
<div class="judul-bar">List Lapak</div>
<div class="panel">
    <?php if (!$daftar): ?>
        <p class="kosong">Belum ada lapak yang buka saat ini.</p>
    <?php else: ?>
        <div class="grid-lapak" id="grid-lapak">
            <?php foreach ($daftar as $i => $l): ?>
                <a class="lapak-item<?= $i >= TAMPIL_AWAL ? ' lebih' : '' ?>"
                   href="<?= e(url('api/pembeli/menu.php?lapak=' . (int) $l['id'])) ?>"
                   title="<?= e($l['nama_lapak'] . ' — ' . $l['kategori']) ?>">
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
