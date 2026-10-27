<?php
/**
 * Seeder (hanya CLI) — untuk skema: pengguna, pedagang, sewa_lapak, menu.
 *   php sql/seed.php admin <username> <password> [email]   -> buat akun admin
 *   php sql/seed.php demo                                   -> data contoh, HAPUS sebelum produksi
 * Prasyarat: sudah menjalankan sql/migrasi_supabase.sql.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/koneksi.php';

function buat_pengguna(PDO $pdo, string $username, string $email, string $password, string $peranApp): int
{
    $st = $pdo->prepare('SELECT id_pengguna FROM pengguna WHERE LOWER(nama_pengguna) = LOWER(?)');
    $st->execute([$username]);
    if ($id = $st->fetchColumn()) {
        return (int) $id;
    }
    $st = $pdo->prepare('INSERT INTO pengguna (nama_pengguna, email_pengguna, kata_sandi, peran) VALUES (?, ?, ?, ?) RETURNING id_pengguna');
    $st->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), PERAN_DB[$peranApp]]);
    return (int) $st->fetchColumn();
}

$cmd = $argv[1] ?? '';

if ($cmd === 'admin') {
    [$u, $p] = [$argv[2] ?? '', $argv[3] ?? ''];
    $email = $argv[4] ?? ($u . '@brawijayastreetfood.id');
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $u) || strlen($p) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "Pemakaian: php sql/seed.php admin <username> <password-min-8-karakter> [email]\n");
        exit(1);
    }
    buat_pengguna($pdo, $u, $email, $p, 'admin');
    echo "Admin '$u' siap dipakai.\n";
    exit(0);
}

if ($cmd === 'demo') {
    buat_pengguna($pdo, 'admin', 'admin@demo.local', 'admin12345', 'admin');
    buat_pengguna($pdo, 'pembeli1', 'pembeli1@demo.local', 'pembeli123', 'pembeli');

    $data = [
        ['Sate Pak Slamet', 'Makanan berat', 'sedang', 'sate_slamet', 500000, [
            ['Sate Ayam 10 tusuk', 'Makanan', 25000, 30],
            ['Sate Kambing 10 tusuk', 'Makanan', 38000, 20],
            ['Lontong', 'Makanan', 4000, 50],
        ]],
        ['Bakso Cak Man', 'Makanan berat', 'besar', 'bakso_cakman', 750000, [
            ['Bakso Urat', 'Makanan', 18000, 40],
            ['Bakso Campur', 'Makanan', 22000, 35],
        ]],
        ['Es Campur Bu Ani', 'Minuman', 'kecil', 'es_buani', 300000, [
            ['Es Campur', 'Minuman', 12000, 40],
            ['Es Teh Manis', 'Minuman', 5000, 100],
            ['Es Jeruk', 'Minuman', 7000, 60],
        ]],
        ['Pisang Goreng Mbak Tika', 'Camilan', 'kecil', 'pisgor_tika', 300000, [
            ['Pisang Goreng (5 pcs)', 'Camilan', 10000, 40],
            ['Pisang Coklat Keju', 'Camilan', 15000, 25],
        ]],
    ];
    foreach ($data as [$nama, $kat, $ukuran, $user, $biaya, $menu]) {
        $cek = $pdo->prepare('SELECT 1 FROM pedagang WHERE nama_tenant = ?');
        $cek->execute([$nama]);
        if ($cek->fetchColumn()) {
            continue;
        }
        $owner = buat_pengguna($pdo, $user, $user . '@demo.local', 'penjual123', 'penjual');
        $st = $pdo->prepare(
            'INSERT INTO pedagang (id_pengguna, nama_tenant, kategori_lapak, ukuran_tenant, nomor_telepon) VALUES (?, ?, ?, ?, ?) RETURNING id_pedagang'
        );
        $st->execute([$owner, $nama, $kat, $ukuran, '0812-3456-7890']);
        $pid = (int) $st->fetchColumn();

        $pdo->prepare("INSERT INTO sewa_lapak (id_pedagang, tgl_mulai, tgl_berakhir, biaya_sewa, status_sewa)
                       VALUES (?, CURRENT_DATE, CURRENT_DATE + 30, ?, 'aktif')")->execute([$pid, $biaya]);

        $ins = $pdo->prepare('INSERT INTO menu (id_pedagang, nama_menu, kategori, harga, stok) VALUES (?, ?, ?, ?, ?)');
        foreach ($menu as [$n, $k, $h, $s]) {
            $ins->execute([$pid, $n, $k, $h, $s]);
        }
    }
    echo "Data demo siap.\n";
    echo "  admin    : admin / admin12345\n";
    echo "  penjual  : sate_slamet, bakso_cakman, es_buani, pisgor_tika / penjual123\n";
    echo "  pembeli  : pembeli1 / pembeli123\n";
    echo "PERINGATAN: ganti/hapus akun demo sebelum dipakai di produksi.\n";
    exit(0);
}

fwrite(STDERR, "Perintah: admin <user> <pass> [email] | demo\n");
exit(1);
