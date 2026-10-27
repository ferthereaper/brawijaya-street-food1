<?php
/**
 * Variabel yang bisa diisi halaman sebelum include:
 *   $page_title     judul tab browser
 *   $mode           'app' (default) | 'welcome' (halaman utama/login/daftar)
 *   $judul_header   teks judul di header (default nama aplikasi)
 *   $lapak_header   array lapak -> tampilkan lingkaran logo lapak di header
 *   $tabs           [['label'=>..,'href'=>..,'key'=>..], ...] -> bar tab
 *   $tab_aktif      key tab yang aktif
 *   $judul_welcome  teks besar di mode welcome (default "Selamat Datang")
 */
require_once __DIR__ . '/functions.php';

$mode = $mode ?? 'app';
$flash = flash_ambil();
$login = sudah_login();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?><?= isset($page_title) ? ' | ' . e($page_title) : '' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app">
<?php if ($mode === 'welcome'): ?>
    <header class="hdr-welcome">
        <h1><?= e($judul_welcome ?? 'Selamat Datang') ?></h1>
    </header>
<?php else: ?>
    <header class="hdr">
        <div class="hdr-kiri">
            <?php if (!empty($lapak_header)): ?>
                <span class="logo-lapak" aria-hidden="true"><?= e(inisial($lapak_header['nama_lapak'])) ?></span>
            <?php endif; ?>
            <a class="hdr-judul" href="<?= e(url($login ? beranda_peran((string) $_SESSION['role']) : 'api/index.php')) ?>">
                <?= e($judul_header ?? APP_NAME) ?>
            </a>
        </div>
        <div class="hdr-kanan">
            <?php if ($login): ?>
                <span class="hdr-user"><?= e($_SESSION['username'] ?? 'User') ?></span>
                <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="form-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="pil">Logout</button>
                </form>
            <?php else: ?>
                <span class="hdr-user">Guest</span>
                <a class="pil" href="<?= e(url('auth/login.php')) ?>">Login</a>
            <?php endif; ?>
        </div>
    </header>
    <?php if (!empty($tabs)): ?>
        <nav class="tabs" aria-label="Navigasi">
            <?php foreach ($tabs as $t): ?>
                <a href="<?= e(url($t['href'])) ?>" class="tab<?= ($tab_aktif ?? '') === $t['key'] ? ' aktif' : '' ?>"><?= e($t['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<main>
<?php if ($flash): ?>
    <p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['pesan']) ?></p>
<?php endif; ?>
