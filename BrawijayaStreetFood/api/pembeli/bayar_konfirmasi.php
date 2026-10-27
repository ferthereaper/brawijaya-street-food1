<?php
/**
 * Konfirmasi pembayaran SIMULASI (demo). Untuk produksi, ganti dengan webhook payment gateway
 * (Midtrans/Xendit, dsb) yang memverifikasi tanda tangan notifikasi lalu mengubah status pesanan.
 */
require_once __DIR__ . '/../../includes/functions.php';
wajib_pembeli_atau_guest();
wajib_post('api/pembeli/lapak.php');

if (!SIMULASI_PEMBAYARAN) {
    http_response_code(403);
    exit('Simulasi pembayaran dinonaktifkan.');
}

$kode = (string) ($_POST['kode'] ?? '');
$kembali = 'api/pembeli/bayar.php?kode=' . urlencode($kode);

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare(
        'SELECT o.id_pesanan AS id, o.id_pengguna AS user_id, o.status_pesanan::text AS status, (b.kedaluwarsa_pada < NOW()) AS lewat
         FROM pesanan o JOIN pembayaran b ON b.id_pesanan = o.id_pesanan
         WHERE o.kode_pesanan = ? FOR UPDATE OF o'
    );
    $st->execute([$kode]);
    $p = $st->fetch();

    $milik = $p && (in_array($kode, $_SESSION['pesanan_saya'] ?? [], true)
        || (sudah_login() && (int) $p['user_id'] === (int) $_SESSION['user_id']));
    if (!$milik) {
        $pdo->rollBack();
        flash_set('error', 'Pesanan tidak ditemukan.');
        redirect('api/pembeli/lapak.php');
    }

    if ($p['status'] === 'menunggu') {
        if ($p['lewat']) {
            batalkan_pesanan_tx($pdo, (int) $p['id']);
            flash_set('error', 'Waktu pembayaran sudah habis. Pesanan dibatalkan.');
        } else {
            $pdo->prepare("UPDATE pesanan SET status_pesanan = 'diproses' WHERE id_pesanan = ?")->execute([$p['id']]);
            $pdo->prepare('UPDATE pembayaran SET status_pembayaran = ?, tgl_pembayaran = NOW() WHERE id_pesanan = ?')
                ->execute([ST_BAYAR_LUNAS, $p['id']]);
            flash_set('success', 'Pembayaran diterima.');
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('bayar_konfirmasi: ' . $e->getMessage());
    flash_set('error', 'Gagal memproses pembayaran.');
}
redirect($kembali);
