# Brawijaya Street Food — Web App PHP

Implementasi desain Figma **UI-SizeME** menjadi aplikasi PHP + PostgreSQL (Supabase), mengikuti struktur proyek `WebUMKM`
(`includes/`, `auth/`, `api/`, `assets/`, `sql/`, PDO, session di database).

## Fitur per peran
| Peran | Halaman |
|---|---|
| **Guest / Pembeli** | List Lapak → Menu (+ keranjang) → Checkout → Pembayaran QRIS (hitung mundur 5 menit) |
| **Penjual** | Tab: Stok · Kasir · Daftar Pesanan · Riwayat · Pendapatan (+ kelola menu) |
| **Admin** | Menu · List Lapak · Tambah/Edit Lapak · Status Sewa |

## Instalasi
Aplikasi ini memakai skema Supabase yang sudah ada: `pengguna`, `pedagang`, `sewa_lapak`, `menu`, `pesanan`, `item_pesanan`, `pembayaran` (+ `meja`).

1. **Migrasi database** — jalankan `sql/migrasi_supabase.sql` di SQL Editor Supabase. File ini hanya MENAMBAH
   (nilai enum yang dibutuhkan, kolom `pesanan.tgl_selesai`, tabel `php_sessions`, index, dan RLS). Aman dijalankan berulang.
2. **Kredensial** — salin `includes/config.local.php.example` → `includes/config.local.php` dan isi,
   *atau* set environment variable: `DB_HOST DB_PORT DB_NAME DB_USER DB_PASS DB_SSLMODE`.
3. **Akun admin** — `php sql/seed.php admin <username> <password> [email]`
   (opsional data contoh: `php sql/seed.php demo` → hapus sebelum produksi).
4. **Jalankan lokal** — dari folder yang berisi `index.php`: `php -S localhost:8000` lalu buka http://localhost:8000
   (butuh PHP ≥ 8.1 dengan ekstensi `pdo_pgsql`; `mbstring` tidak wajib).
5. Jika dipasang di subfolder (XAMPP, dsb) path terdeteksi otomatis; bisa dipaksa dengan env `APP_BASE=/nama-folder`.

## Pemetaan ke skema database
| Konsep aplikasi | Tabel / kolom |
|---|---|
| Akun (pembeli/penjual/admin) | `pengguna` — peran `pelanggan` / `pedagang` / `admin` (dipetakan di `PERAN_DB` pada `includes/config.php`) |
| Lapak | `pedagang` (+ akun pemilik di `pengguna`) |
| Masa sewa | `sewa_lapak` — baris TERBARU dipakai; "Perpanjang" menambah baris baru sehingga riwayat sewa tetap ada |
| Buka/tutup toko | `pedagang.status_toko` (penjual bisa mengubah di tab Stok) |
| Menu & stok | `menu` |
| Pesanan | `pesanan` (`menunggu` → `diproses` → `selesai` / `dibatalkan`) + `item_pesanan` |
| Pembayaran QRIS/tunai | `pembayaran` (`pending` → `lunas` / `kedaluwarsa`, batas waktu di `kedaluwarsa_pada`) |

Lapak tampil bagi pembeli jika: `status_toko = 'buka'` **dan** sewa terbaru `aktif` **dan** belum melewati `tgl_berakhir`.
Nilai enum yang dipakai aplikasi tercantum di bagian 1 file migrasi. Jika enum Anda memakai kata lain untuk "pembayaran berhasil",
ubah konstanta `ST_BAYAR_LUNAS` di `includes/config.php` (nilainya harus ada di enum `status_pembayaran`).

## Penting — keamanan
* **`includes/koneksi.php` pada proyek WebUMKM asli berisi password database Supabase tertulis langsung.**
  Karena file itu sudah dibagikan, anggap password tersebut bocor: **reset password database di Supabase**, lalu pakai
  env var / `config.local.php` (sudah masuk `.gitignore`).
* Semua form POST memakai token CSRF, password di-hash (`password_hash`), query memakai prepared statement,
  output di-escape, ID session diganti saat login, dan akses per peran dicek di setiap halaman.

## Catatan implementasi
* **Pembayaran QRIS masih simulasi.** Tombol “Simulasi: Saya Sudah Bayar” (aktif jika `SIMULASI_PEMBAYARAN=true`)
  menggantikan notifikasi payment gateway. Untuk produksi, buat endpoint webhook (Midtrans/Xendit, dsb) yang memverifikasi
  tanda tangan lalu mengubah status pesanan, dan letakkan gambar QRIS asli di `assets/img/qris.png`.
* Stok dikurangi saat pesanan dibuat; bila QRIS tidak dibayar dalam 5 menit, pesanan dibatalkan otomatis dan stok kembali
  (diproses setiap halaman relevan dibuka — tidak butuh cron).
* Pendapatan = total pesanan berstatus **Selesai** (waktu `tgl_selesai`), dihitung menurut WIB.
* Satu akun penjual ↔ satu lapak (`pedagang.id_pengguna` UNIQUE). Akun penjual dibuat admin bersama lapaknya (form Tambah Lapak: username, email, password, biaya sewa).
* Menu yang pernah dipesan tidak bisa dihapus (menjaga riwayat); atur stoknya ke 0.
* `pesanan.id_meja` dan tabel `meja` tidak dipakai karena desain Figma tidak punya fitur meja.
* Tabel di schema `public` Supabase bisa diakses lewat Data API; migrasi mengaktifkan RLS tanpa policy agar tertutup. Aplikasi PHP tetap bisa akses karena konek sebagai role `postgres`.

## Perbedaan/tambahan dari Figma
* Form Tambah Lapak: ditambah **biaya & masa sewa** dan **akun penjual** (username, email, password) — dibutuhkan agar Status Sewa dan Login Penjual berfungsi.
* Penjual: ditambah form **Tambah/Edit/Hapus Menu** dan tombol **Buka/Tutup Toko** (tidak ada di Figma, tapi diperlukan). Menu memakai kolom `kategori` (wajib); tabel `menu` tidak punya kolom deskripsi.
* Checkout guest: kolom opsional “Nama pemesan”.
* Navigasi tab penjual dibuat konsisten (5 tab di semua halaman); frame duplikat di Figma tidak dibuat ulang.
* Halaman dibuat responsif dalam kolom lebar maks. 480px (layout ponsel seperti desain).

## Struktur
```
auth/        login, register, logout (+ proses_*)
api/index.php          halaman utama
api/keranjang.php      endpoint keranjang (pembeli & kasir)
api/pembeli/ penjual/ admin/
includes/    config, koneksi, session (DB), functions, header/footer, init_penjual/admin
assets/      css/style.css, js/app.js, img/
sql/         migrasi_supabase.sql, seed.php
```
