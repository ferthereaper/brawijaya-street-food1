<?php
/**
 * Koneksi PostgreSQL (Supabase).
 *
 * Isi kredensial lewat SALAH SATU cara:
 *  1. Environment variable: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, (opsional) DB_SSLMODE
 *  2. File includes/config.local.php (ada di .gitignore), isinya:
 *       <?php return ['DB_HOST'=>'...','DB_PORT'=>'6543','DB_NAME'=>'postgres','DB_USER'=>'...','DB_PASS'=>'...'];
 *
 * Jangan menulis password langsung di file ini.
 */
require_once __DIR__ . '/config.php';

$lokal = is_file(__DIR__ . '/config.local.php') ? (require __DIR__ . '/config.local.php') : [];
$ambil = static function (string $key, string $default = '') use ($lokal): string {
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        return $v;
    }
    return (string) ($lokal[$key] ?? $default);
};

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
    $ambil('DB_HOST', 'localhost'),
    $ambil('DB_PORT', '5432'),
    $ambil('DB_NAME', 'postgres'),
    $ambil('DB_SSLMODE', 'prefer')
);

try {
    $pdo = new PDO($dsn, $ambil('DB_USER', 'postgres'), $ambil('DB_PASS'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Supabase pooler (port 6543, transaction mode) tidak mendukung prepared statement server-side.
        PDO::ATTR_EMULATE_PREPARES   => true,
    ]);
} catch (PDOException $e) {
    error_log('Koneksi database gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database.');
}
