<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $_SESSION = [];
    session_destroy();
    session_start();           // sesi baru hanya untuk membawa pesan
    session_regenerate_id(true);
    flash_set('success', 'Anda sudah logout.');
}
redirect('auth/login.php');
