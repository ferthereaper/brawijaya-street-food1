<?php
require_once __DIR__ . '/../includes/functions.php';

wajib_post('auth/register.php');

$username = trim((string) ($_POST['username'] ?? ''));
$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$errors = [];
if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
    $errors[] = 'Username 3–30 karakter (huruf, angka, titik, garis bawah).';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    $errors[] = 'Format email tidak valid.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password minimal 8 karakter.';
}
if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('auth/register.php');
}

try {
    $cek = $pdo->prepare('SELECT 1 FROM pengguna WHERE LOWER(nama_pengguna) = LOWER(?) OR LOWER(email_pengguna) = LOWER(?)');
    $cek->execute([$username, $email]);
    if ($cek->fetchColumn()) {
        flash_set('error', 'Username atau email sudah digunakan.');
        redirect('auth/register.php');
    }

    $pdo->prepare('INSERT INTO pengguna (nama_pengguna, email_pengguna, kata_sandi, peran) VALUES (?, ?, ?, ?)')
        ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), PERAN_DB['pembeli']]);

    flash_set('success', 'Akun berhasil dibuat. Silakan login.');
    redirect('auth/login.php');
} catch (PDOException $e) {
    error_log('Register: ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
    redirect('auth/register.php');
}
