<?php
require_once __DIR__ . '/../includes/functions.php';

wajib_post('auth/login.php');

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$sebagai  = (string) ($_POST['sebagai'] ?? 'pembeli');
if (!in_array($sebagai, ['pembeli', 'penjual', 'admin'], true)) {
    $sebagai = 'pembeli';
}

// Pembatasan percobaan login sederhana (per sesi): 5 gagal -> tunggu 60 detik
if (($_SESSION['blok_login_sampai'] ?? 0) > time()) {
    flash_set('error', 'Terlalu banyak percobaan. Coba lagi sebentar lagi.');
    redirect('auth/login.php');
}

$st = $pdo->prepare(
    'SELECT id_pengguna AS id, nama_pengguna AS username, kata_sandi AS password, peran::text AS peran
     FROM pengguna WHERE LOWER(nama_pengguna) = LOWER(?)'
);
$st->execute([$username]);
$user = $st->fetch();
$roleApp = $user ? array_search($user['peran'], PERAN_DB, true) : false; // 'pelanggan' -> 'pembeli', dst.

// Jika username tidak ada, tetap hitung satu hash agar waktu respons mirip
$hash  = $user['password'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
$cocok = password_verify($password, $hash) && $user;

if ($user && $cocok && $roleApp === $sebagai) {
    session_regenerate_id(true);
    unset($_SESSION['gagal_login'], $_SESSION['blok_login_sampai']);
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['nama']      = $user['username'];
    $_SESSION['role']      = $roleApp;
    unset($_SESSION['csrf']);

    if ($roleApp === 'pembeli' && cart_jumlah('cart') > 0) {
        redirect('api/pembeli/checkout.php'); // lanjutkan pesanan yang sudah dipilih sebagai guest
    }
    redirect(beranda_peran($roleApp));
}

$_SESSION['gagal_login'] = ($_SESSION['gagal_login'] ?? 0) + 1;
if ($_SESSION['gagal_login'] >= 5) {
    $_SESSION['blok_login_sampai'] = time() + 60;
    $_SESSION['gagal_login'] = 0;
}

$pesan = ($user && $cocok && $roleApp !== $sebagai)
    ? 'Akun ini bukan akun ' . $sebagai . '. Gunakan tombol login yang sesuai.'
    : 'Username atau password salah.';
flash_set('error', $pesan);
redirect('auth/login.php');
