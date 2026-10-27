<?php
/** Dipanggil di awal setiap halaman admin (setelah $page_title & $tab_aktif diisi). */
require_once __DIR__ . '/functions.php';
wajib_peran('admin');

$tabs = [
    ['key' => 'beranda', 'label' => 'Beranda',       'href' => 'api/admin/index.php'],
    ['key' => 'list',    'label' => 'List Lapak',    'href' => 'api/admin/lapak.php'],
    ['key' => 'tambah',  'label' => 'Tambah Lapak',  'href' => 'api/admin/lapak_form.php'],
    ['key' => 'sewa',    'label' => 'Status Sewa',   'href' => 'api/admin/status_sewa.php'],
];
include __DIR__ . '/header.php';
