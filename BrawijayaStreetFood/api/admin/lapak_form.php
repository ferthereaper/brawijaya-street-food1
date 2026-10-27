<?php
require_once __DIR__ . '/../../includes/functions.php';
$page_title = 'Form Lapak';
$tab_aktif = 'tambah';
require __DIR__ . '/../../includes/init_admin.php';

$hari = (new DateTime('today', new DateTimeZone('Asia/Jakarta')));
$lapak = [
    'id' => '', 'nama_lapak' => '', 'kategori' => '', 'ukuran' => '', 'no_telepon' => '',
    'sewa_mulai' => $hari->format('Y-m-d'),
    'sewa_berakhir' => (clone $hari)->modify('+30 days')->format('Y-m-d'),
    'biaya_sewa' => '', 'username' => '', 'email' => '',
];
$edit = false;
if (isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT l.* FROM ' . SQL_LAPAK . ' l WHERE l.id = ?');
    $st->execute([(int) $_GET['id']]);
    if ($data = $st->fetch()) {
        $lapak = $data;
        $edit = true;
    }
}
// Isi ulang form jika validasi gagal
$old = $_SESSION['old_lapak'] ?? null;
unset($_SESSION['old_lapak']);
if (is_array($old)) {
    $lapak = array_merge($lapak, $old);
}
?>
<div class="judul-bar"><?= $edit ? 'Edit Lapak' : 'Form Tambah' ?></div>
<div class="panel">
    <form method="post" action="<?= e(url('api/admin/lapak_proses.php')) ?>" class="panel-krem">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="simpan">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $lapak['id'] ?>"><?php endif; ?>

        <label for="nama_lapak">Nama Lapak</label>
        <input type="text" id="nama_lapak" name="nama_lapak" value="<?= e($lapak['nama_lapak']) ?>" required maxlength="150">

        <label for="kategori">Kategori Lapak</label>
        <input type="text" id="kategori" name="kategori" value="<?= e($lapak['kategori']) ?>" required maxlength="100" placeholder="mis. Makanan berat, Minuman, Camilan">

        <label for="ukuran">Ukuran Tenant</label>
        <select id="ukuran" name="ukuran" required>
            <option value="">Pilih ukuran</option>
            <?php foreach (['besar' => 'Besar', 'sedang' => 'Sedang', 'kecil' => 'Kecil'] as $v => $t): ?>
                <option value="<?= $v ?>" <?= $lapak['ukuran'] === $v ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>

        <label for="no_telepon">No Telepon</label>
        <input type="tel" id="no_telepon" name="no_telepon" value="<?= e($lapak['no_telepon']) ?>" required maxlength="20" pattern="[0-9+\- ]{6,20}" inputmode="tel">

        <label for="biaya_sewa">Biaya Sewa (Rp)</label>
        <input type="number" id="biaya_sewa" name="biaya_sewa" value="<?= e($lapak['biaya_sewa'] === '' || $lapak['biaya_sewa'] === null ? '' : (int) $lapak['biaya_sewa']) ?>" min="0" max="1000000000" required inputmode="numeric">

        <label for="sewa_mulai">Sewa Mulai</label>
        <input type="date" id="sewa_mulai" name="sewa_mulai" value="<?= e($lapak['sewa_mulai']) ?>" required>

        <label for="sewa_berakhir">Sewa Berakhir</label>
        <input type="date" id="sewa_berakhir" name="sewa_berakhir" value="<?= e($lapak['sewa_berakhir']) ?>" required>

        <hr style="border:0;border-top:1px solid var(--taupe);margin:18px 0 4px">
        <p class="bantuan"><strong>Akun penjual</strong> — dipakai pemilik lapak untuk “Login Penjual”.</p>

        <label for="username">Username Penjual</label>
        <input type="text" id="username" name="username" value="<?= e($lapak['username'] ?? '') ?>"
               <?= $edit ? 'readonly' : 'required' ?> maxlength="30" pattern="[A-Za-z0-9_.]{3,30}" autocomplete="off">

        <label for="email">Email Penjual</label>
        <input type="email" id="email" name="email" value="<?= e($lapak['email'] ?? '') ?>" <?= $edit ? 'readonly' : 'required' ?> maxlength="255" autocomplete="off">

        <label for="password"><?= $edit ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password Penjual' ?></label>
        <input type="password" id="password" name="password" <?= $edit ? '' : 'required' ?> minlength="8" autocomplete="new-password">
        <p class="bantuan">Minimal 8 karakter.</p>

        <div class="aksi">
            <button type="submit" class="tombol">submit</button>
            <?php if ($edit): ?><a class="tombol tombol-tipis" href="<?= e(url('api/admin/lapak.php')) ?>">Batal</a><?php endif; ?>
        </div>
    </form>

    <?php if ($edit): ?>
        <form method="post" action="<?= e(url('api/admin/lapak_proses.php')) ?>"
              data-konfirmasi="Hapus lapak ini beserta akun penjual, menu, dan SELURUH riwayat pesanannya? Tindakan ini tidak bisa dibatalkan.">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int) $lapak['id'] ?>">
            <button type="submit" class="tombol tombol-bahaya">Hapus Lapak</button>
        </form>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
