<?php
/** File: status.php - Status pesanan untuk pelanggan (halaman + JSON untuk pembaruan otomatis). */
require_once __DIR__ . '/includes/helpers.php';

$kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['kode'] ?? '')));
$p    = $kode !== '' ? Pesanan::byKode($kode) : null;

if (isset($_GET['json'])) {
    if (!$p) {
        jsonOut(['ok' => false], 404);
    }
    jsonOut(['ok' => true, 'status' => $p['status'], 'bayar' => $p['status_bayar']]);
}

$langkah = ['Baru' => 'Diterima', 'Diproses' => 'Sedang dibuat', 'Siap' => 'Siap diantar', 'Selesai' => 'Selesai'];
$urut    = array_keys($langkah);
$posisi  = $p ? array_search($p['status'], $urut, true) : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Status Pesanan | Mandatory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="cust">
<?php if (!$p): ?>
<main class="c-center">
  <img class="c-logo big" src="assets/img/logo.jpeg" alt="Mandatory">
  <h1>Pesanan tidak ditemukan</h1>
  <p>Periksa kembali kode pesananmu, atau tanyakan ke kasir.</p>
</main>
<?php else: ?>
<header class="c-head">
  <img class="c-logo" src="assets/img/logo.jpeg" alt="Mandatory">
  <div><strong>Mandatory</strong><span><?= e($p['nama_meja']) ?> &middot; <?= e($p['nama_pelanggan']) ?></span></div>
</header>
<main class="c-main narrow">
  <p class="kode-label">Kode pesanan</p>
  <p class="kode"><?= e($p['kode']) ?></p>

  <?php if ($p['status'] === 'Batal'): ?>
    <div class="alert">Pesanan ini dibatalkan. Silakan hubungi kasir bila ada pertanyaan.</div>
  <?php else: ?>
    <ol class="steps">
      <?php foreach ($langkah as $k => $label):
          $i = array_search($k, $urut, true);
          $cls = $i < $posisi ? 'done' : ($i === $posisi ? 'now' : ''); ?>
        <li class="<?= $cls ?>"><span><?= $i + 1 ?></span><?= e($label) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php if ($p['status'] === 'Siap'): ?><div class="alert ok">Pesananmu siap! Sebentar lagi kami antar ke meja.</div><?php endif; ?>
  <?php endif; ?>

  <div class="panel">
    <?php foreach ($p['items'] as $d): ?>
      <div class="row"><span><?= (int)$d['qty'] ?>&times; <?= e($d['nama_produk']) ?><?= $d['opsi'] ? ' <em>(' . e($d['opsi']) . ')</em>' : '' ?></span><span><?= rupiah($d['subtotal']) ?></span></div>
    <?php endforeach; ?>
    <div class="row total"><span>Total</span><span><?= rupiah($p['total']) ?></span></div>
  </div>
  <?php if ($p['catatan'] !== ''): ?><p class="muted">Catatan: <?= e($p['catatan']) ?></p><?php endif; ?>

  <?php if ($p['status'] !== 'Batal'): ?>
    <div class="alert <?= $p['status_bayar'] === 'Lunas' ? 'ok' : '' ?>">
      <?= $p['status_bayar'] === 'Lunas' ? 'Pembayaran lunas. Terima kasih!' : 'Belum dibayar. Kamu bisa membayar langsung dengan QRIS dinamis tanpa ke kasir.' ?>
    </div>
  <?php endif; ?>

  <?php if ($p['status_bayar'] !== 'Lunas' && $p['status'] !== 'Batal'): ?><a class="btn btn-red btn-block" href="bayar_qris.php?kode=<?= urlencode($p['kode']) ?>">Bayar sekarang dengan QRIS</a><?php endif; ?>
  <a class="btn btn-line btn-block" href="pesan.php?meja=<?= urlencode($p['kode_meja']) ?>">Pesan lagi</a>
</main>
<script>
(function () {
  var s = <?= json_encode($p['status']) ?>, b = <?= json_encode($p['status_bayar']) ?>;
  if (s === 'Batal' || (s === 'Selesai' && b === 'Lunas')) return;   // tidak ada yang berubah lagi
  setInterval(function () {
    fetch('status.php?kode=<?= urlencode($p['kode']) ?>&json=1', { cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d.ok && (d.status !== s || d.bayar !== b)) location.reload(); })
      .catch(function () {});
  }, 6000);
})();
</script>
<?php endif; ?>
</body>
</html>
