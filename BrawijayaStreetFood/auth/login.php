<?php
require_once __DIR__ . '/../includes/functions.php';

if (sudah_login()) {
    redirect(beranda_peran((string) $_SESSION['role']));
}

$page_title = 'Login';
$mode = 'welcome';
include __DIR__ . '/../includes/header.php';
?>
<section class="hero">
    <h2 class="judul-besar"><?= e(APP_NAME) ?></h2>
    <p class="alamat"><?= e(ALAMAT_LENGKAP) ?></p>
</section>

<div class="kartu-gelap">
    <h2>LOGIN</h2>
    <form method="post" action="<?= e(url('auth/proses_login.php')) ?>" class="panel-krem" autocomplete="on">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username" maxlength="50">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <div style="margin-top:18px">
            <button class="pil-besar" type="submit" name="sebagai" value="pembeli">Login Pengguna</button>
            <button class="pil-besar" type="submit" name="sebagai" value="penjual">Login Penjual</button>
            <button class="pil-besar" type="submit" name="sebagai" value="admin">Login Admin</button>
        </div>
        <p class="kecil">Belum Punya Akun? <a href="<?= e(url('auth/register.php')) ?>">Buat Akun</a></p>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
