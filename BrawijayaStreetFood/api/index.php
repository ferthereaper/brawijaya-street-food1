<?php
require_once __DIR__ . '/../includes/functions.php';

// Sudah login -> langsung ke beranda sesuai peran
if (sudah_login()) {
    redirect(beranda_peran((string) $_SESSION['role']));
}

$page_title = 'Selamat Datang';
$mode = 'welcome';
include __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <h2 class="judul-besar"><?= e(APP_NAME) ?></h2>
    <p class="alamat"><?= e(ALAMAT_LENGKAP) ?></p>
</section>

<a class="tombol-besar" href="<?= e(url('auth/login.php')) ?>">LOGIN</a>
<a class="tombol-besar" href="<?= e(url('api/pembeli/lapak.php')) ?>">GUEST</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
