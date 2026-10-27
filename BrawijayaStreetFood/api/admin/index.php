<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Menu Admin';
$tab_aktif = 'beranda';
require __DIR__ . '/../../includes/init_admin.php';

$s = $pdo->query(
    "SELECT COUNT(*) AS total,
            COUNT(*) FILTER (WHERE " . sql_lapak_aktif('l') . ") AS aktif,
            COUNT(*) FILTER (WHERE status_sewa = 'aktif' AND sewa_berakhir >= " . SQL_HARI_INI . "
                              AND sewa_berakhir <= " . SQL_HARI_INI . " + 7) AS segera
     FROM " . SQL_LAPAK . " l"
)->fetch();
?>
<div class="judul-bar">Menu</div>
<div class="panel">
    <div class="menu-admin">
        <a href="<?= e(url('api/admin/lapak.php')) ?>">List Lapak</a>
        <a href="<?= e(url('api/admin/lapak_form.php')) ?>">Tambah Lapak</a>
        <a href="<?= e(url('api/admin/status_sewa.php')) ?>">Status Sewa</a>
    </div>
</div>
<div class="grid-2" style="margin-top:14px">
    <div class="kartu-pendapatan"><div class="label">Total Lapak</div><div class="nilai"><?= (int) $s['total'] ?></div></div>
    <div class="kartu-pendapatan"><div class="label">Sewa Aktif</div><div class="nilai"><?= (int) $s['aktif'] ?></div></div>
    <?php if ((int) $s['segera'] > 0): ?>
        <div class="kartu-pendapatan grid-1-penuh"><div class="label">Sewa berakhir ≤ 7 hari</div><div class="nilai"><?= (int) $s['segera'] ?></div></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
