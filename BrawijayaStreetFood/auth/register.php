<?php
require_once __DIR__ . '/../includes/functions.php';

if (sudah_login()) {
    redirect(beranda_peran((string) $_SESSION['role']));
}

$page_title = 'Buat Akun';
$mode = 'welcome';
include __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <h2 class="judul-besar"><?= e(APP_NAME) ?></h2>
    <p class="alamat"><?= e(ALAMAT_LENGKAP) ?></p>
</section>

<div class="kartu-gelap">
    <h2>Buat Akun</h2>
    <form method="post" action="<?= e(url('auth/proses_register.php')) ?>" class="panel-krem">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required minlength="3" maxlength="30"
               pattern="[A-Za-z0-9_.]+" autocomplete="username">
        <p class="bantuan">3–30 karakter: huruf, angka, titik, atau garis bawah.</p>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="255" autocomplete="email">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
        <p class="bantuan">Minimal 8 karakter.</p>

        <div style="margin-top:18px">
            <button class="pil-besar" type="submit">Buat Akun</button>
        </div>
        <p class="kecil">Sudah Punya Akun? <a href="<?= e(url('auth/login.php')) ?>">Login</a></p>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
