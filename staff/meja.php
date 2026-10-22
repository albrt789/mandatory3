<?php
/** File: staff/meja.php - (Admin) Kelola meja dan cetak QR pemesanan per meja. */
require_once __DIR__ . '/../includes/auth.php';
$u     = wajibAdmin();
$error = '';
$db    = Database::pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? null)) {
        $error = 'Sesi kedaluwarsa, muat ulang halaman lalu coba lagi.';
    } else {
        try {
            if (($_POST['aksi'] ?? '') === 'tambah') {
                $nama = trim(mb_substr((string)($_POST['nama'] ?? ''), 0, 30));
                if ($nama === '') {
                    throw new InvalidArgumentException('Nama meja wajib diisi.');
                }
                $db->prepare('INSERT INTO meja (nama, kode) VALUES (?, ?)')->execute([$nama, bin2hex(random_bytes(5))]);
            } elseif (($_POST['aksi'] ?? '') === 'toggle') {
                $db->prepare('UPDATE meja SET aktif = 1 - aktif WHERE id_meja = ?')->execute([(int)($_POST['id'] ?? 0)]);
            }
            header('Location: meja.php');
            exit;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('meja.php: ' . $e->getMessage());
            $error = 'Terjadi kesalahan di server.';
        }
    }
}

$meja = $db->query('SELECT * FROM meja ORDER BY id_meja')->fetchAll();
$base = publicUrl();
$judul = 'Meja & QR';
require __DIR__ . '/../includes/staff_head.php';
?>
<div class="s-bar noprint"><h1>Meja &amp; QR</h1><button class="btn btn-red sm" onclick="window.print()">Cetak semua QR</button></div>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<div class="panel noprint">
  <form method="post" class="inline-form">
    <?= csrfField() ?><input type="hidden" name="aksi" value="tambah">
    <label class="f">Tambah meja<input name="nama" maxlength="30" placeholder="Contoh: Meja 11 / Bar 1" required></label>
    <button class="btn btn-red sm">Tambah</button>
  </form>
  <div class="list">
    <?php foreach ($meja as $m): ?>
      <div class="m-row <?= $m['aktif'] ? '' : 'off' ?>">
        <div><b><?= e($m['nama']) ?></b><br><small><?= e($base . '/pesan.php?meja=' . $m['kode']) ?></small></div>
        <form method="post"><?= csrfField() ?><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?= (int)$m['id_meja'] ?>">
          <button class="tag <?= $m['aktif'] ? 'ok' : 'no' ?>"><?= $m['aktif'] ? 'Aktif' : 'Nonaktif' ?></button></form>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="muted">Meja yang dinonaktifkan tidak bisa dipakai memesan (QR-nya otomatis tidak berlaku). Pastikan alamat di atas sudah alamat website yang benar sebelum QR dicetak.</p>
</div>

<div class="qr-grid">
  <?php foreach ($meja as $m): if (!$m['aktif']) continue; ?>
    <figure class="qr-card">
      <img src="../assets/img/logo.jpeg" alt="">
      <div class="qr" data-url="<?= e($base . '/pesan.php?meja=' . $m['kode']) ?>"></div>
      <figcaption><b><?= e($m['nama']) ?></b><span>Scan untuk memesan</span></figcaption>
    </figure>
  <?php endforeach; ?>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.querySelectorAll('.qr').forEach(function (el) {
  new QRCode(el, { text: el.getAttribute('data-url'), width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
});
</script>
<?php require __DIR__ . '/../includes/staff_foot.php'; ?>
