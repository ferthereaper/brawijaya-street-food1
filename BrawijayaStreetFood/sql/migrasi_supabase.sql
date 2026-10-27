-- ============================================================
-- Migrasi untuk skema Supabase yang sudah ada (pengguna, pedagang,
-- sewa_lapak, menu, pesanan, item_pesanan, pembayaran, meja).
--
-- AMAN dijalankan berulang: hanya MENAMBAH (nilai enum, kolom, tabel),
-- tidak menghapus / mengubah data yang sudah ada.
--
-- Jalankan di SQL Editor Supabase. Jika editor menolak ALTER TYPE
-- karena berjalan dalam satu transaksi, jalankan BAGIAN 1 sendiri dulu
-- (blok ALTER TYPE), lalu BAGIAN 2 dan 3.
-- ============================================================

-- ---------- BAGIAN 1: nilai enum yang dipakai aplikasi ----------
-- (nilai yang sudah ada dilewati; nilai lain milik Anda tidak disentuh)
ALTER TYPE peran_pengguna    ADD VALUE IF NOT EXISTS 'pelanggan';
ALTER TYPE peran_pengguna    ADD VALUE IF NOT EXISTS 'pedagang';
ALTER TYPE peran_pengguna    ADD VALUE IF NOT EXISTS 'admin';

ALTER TYPE ukuran_tenant     ADD VALUE IF NOT EXISTS 'kecil';
ALTER TYPE ukuran_tenant     ADD VALUE IF NOT EXISTS 'sedang';
ALTER TYPE ukuran_tenant     ADD VALUE IF NOT EXISTS 'besar';

ALTER TYPE status_toko       ADD VALUE IF NOT EXISTS 'buka';
ALTER TYPE status_toko       ADD VALUE IF NOT EXISTS 'tutup';

ALTER TYPE status_sewa       ADD VALUE IF NOT EXISTS 'aktif';
ALTER TYPE status_sewa       ADD VALUE IF NOT EXISTS 'nonaktif';

ALTER TYPE status_pesanan    ADD VALUE IF NOT EXISTS 'menunggu';     -- menunggu pembayaran
ALTER TYPE status_pesanan    ADD VALUE IF NOT EXISTS 'diproses';
ALTER TYPE status_pesanan    ADD VALUE IF NOT EXISTS 'selesai';
ALTER TYPE status_pesanan    ADD VALUE IF NOT EXISTS 'dibatalkan';

ALTER TYPE status_pembayaran ADD VALUE IF NOT EXISTS 'pending';
ALTER TYPE status_pembayaran ADD VALUE IF NOT EXISTS 'lunas';
ALTER TYPE status_pembayaran ADD VALUE IF NOT EXISTS 'kedaluwarsa';

ALTER TYPE metode_pembayaran ADD VALUE IF NOT EXISTS 'qris';
ALTER TYPE metode_pembayaran ADD VALUE IF NOT EXISTS 'tunai';

-- ---------- BAGIAN 2: kolom & tabel tambahan ----------
-- Waktu pesanan ditandai selesai (dipakai halaman Pendapatan)
ALTER TABLE public.pesanan ADD COLUMN IF NOT EXISTS tgl_selesai timestamptz;

-- Session PHP disimpan di database
CREATE TABLE IF NOT EXISTS public.php_sessions (
    id         VARCHAR(128) PRIMARY KEY,
    data       TEXT NOT NULL DEFAULT '',
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Index pendukung
CREATE INDEX IF NOT EXISTS idx_pesanan_pedagang_status ON public.pesanan (id_pedagang, status_pesanan);
CREATE INDEX IF NOT EXISTS idx_sewa_pedagang           ON public.sewa_lapak (id_pedagang, tgl_berakhir DESC);
CREATE INDEX IF NOT EXISTS idx_menu_pedagang           ON public.menu (id_pedagang);

-- ---------- BAGIAN 3: keamanan (sangat disarankan) ----------
-- Tabel di schema "public" Supabase otomatis bisa diakses lewat Data API (anon key).
-- Aplikasi PHP ini konek langsung sebagai role postgres (melewati RLS), jadi cukup aktifkan RLS
-- TANPA policy agar tabel (termasuk hash kata_sandi) tertutup dari Data API.
ALTER TABLE public.pengguna      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pedagang      ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sewa_lapak    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.menu          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pesanan       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.item_pesanan  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pembayaran    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.meja          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.php_sessions  ENABLE ROW LEVEL SECURITY;
