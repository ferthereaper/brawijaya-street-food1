<?php
/**
 * Konfigurasi umum aplikasi.
 * Kredensial database JANGAN ditaruh di sini — lihat koneksi.php
 * (environment variable atau includes/config.local.php).
 */
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Brawijaya Street Food');
    define('ALAMAT_LENGKAP', 'Jl. Besar Ijen No.25A, Gading Kasri, Kec. Klojen, Kota Malang, Jawa Timur 65115');
    define('ALAMAT_SINGKAT', 'Jl. Besar Ijen No.25A, Klojen, Kota Malang');
    define('JAM_BUKA', 'Buka setiap hari • 10.00 – 22.00 WIB');
    define('KONTAK_WA', '08xx-xxxx-xxxx');
    define('KONTAK_TELP', '08xx-xxxx-xxxx');
    define('KONTAK_EMAIL', 'halo@brawijayastreetfood.id');

    // Batas waktu bayar QRIS (detik) — sesuai desain: 5 menit.
    define('BATAS_BAYAR_DETIK', 300);

    // true  = tombol "Simulasi bayar" aktif (untuk demo/skripsi, belum ada payment gateway).
    // false = pesanan hanya bisa dikonfirmasi lewat integrasi payment gateway sungguhan.
    $sim = getenv('SIMULASI_PEMBAYARAN');
    define('SIMULASI_PEMBAYARAN', $sim === false || $sim === '' ? true : filter_var($sim, FILTER_VALIDATE_BOOLEAN));

    // Hari ini menurut WIB (dipakai di query SQL agar tidak bergeser oleh zona waktu server)
    define('SQL_HARI_INI', "(NOW() AT TIME ZONE 'Asia/Jakarta')::date");

    // Peran di aplikasi  =>  nilai enum peran_pengguna di database
    define('PERAN_DB', ['pembeli' => 'pelanggan', 'penjual' => 'pedagang', 'admin' => 'admin']);
    // Nilai enum status_pembayaran untuk pembayaran yang sudah diterima.
    // Ganti jika enum di database Anda memakai kata lain (mis. 'berhasil'/'sukses') — harus ada di enum.
    define('ST_BAYAR_LUNAS', 'lunas');

    /*
     * Pemetaan skema database -> nama kolom yang dipakai halaman PHP.
     * Dipakai sebagai sub-query:  FROM " . SQL_LAPAK . " l
     * (Hanya untuk BACA; tulis ke tabel aslinya langsung.)
     */
    // "Lapak" = pedagang + akun pengguna + baris sewa_lapak TERBARU
    define('SQL_LAPAK',
        "(SELECT p.id_pedagang AS id, p.nama_tenant AS nama_lapak, p.kategori_lapak AS kategori,
                 p.ukuran_tenant::text AS ukuran, p.nomor_telepon AS no_telepon, p.id_pengguna AS owner_id,
                 p.status_toko::text AS status_toko, u.nama_pengguna AS username, u.email_pengguna AS email,
                 s.id_sewa, s.tgl_mulai AS sewa_mulai, s.tgl_berakhir AS sewa_berakhir,
                 s.status_sewa::text AS status_sewa, s.biaya_sewa
          FROM pedagang p
          JOIN pengguna u ON u.id_pengguna = p.id_pengguna
          LEFT JOIN LATERAL (SELECT x.* FROM sewa_lapak x WHERE x.id_pedagang = p.id_pedagang
                             ORDER BY x.tgl_berakhir DESC, x.id_sewa DESC LIMIT 1) s ON TRUE)");
    // Menu
    define('SQL_MENU',
        "(SELECT id_menu AS id, id_pedagang AS lapak_id, nama_menu AS nama_produk, kategori, harga, stok FROM menu)");
    // Pesanan + pembayarannya
    define('SQL_PESANAN',
        "(SELECT o.id_pesanan AS id, o.kode_pesanan AS kode, o.id_pedagang AS lapak_id, o.id_pengguna AS user_id,
                 o.nama_pemesan, o.tgl_pesanan AS created_at, o.total_harga, o.status_pesanan::text AS status,
                 o.tgl_selesai AS selesai_at, b.metode_pembayaran::text AS metode, b.status_pembayaran::text AS status_bayar,
                 b.kedaluwarsa_pada AS kedaluwarsa_at, b.tgl_pembayaran AS dibayar_at
          FROM pesanan o LEFT JOIN pembayaran b ON b.id_pesanan = o.id_pesanan)");
}

date_default_timezone_set('Asia/Jakarta');

/**
 * BASE = awalan URL tempat proyek ini berada.
 * Kosong jika proyek di root domain (php -S, Vercel, Supabase hosting, dsb).
 * Contoh XAMPP: "/BrawijayaStreetFood". Bisa dipaksa lewat env APP_BASE.
 */
if (!defined('BASE')) {
    $env = getenv('APP_BASE');
    if ($env !== false) {
        define('BASE', rtrim($env, '/'));
    } else {
        $root = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
        $doc  = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        define('BASE', ($doc !== '' && str_starts_with($root, $doc)) ? substr($root, strlen($doc)) : '');
    }
}
