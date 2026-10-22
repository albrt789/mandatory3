<?php
/** File: pesan.php - Halaman pelanggan: lihat menu, isi keranjang, kirim pesanan. Dibuka dari QR meja. */
require_once __DIR__ . '/includes/helpers.php';

$token = (string)($_GET['meja'] ?? '');
$st    = Database::pdo()->prepare('SELECT nama FROM meja WHERE kode = ? AND aktif = 1');
$st->execute([$token]);
$namaMeja = $st->fetchColumn();
$data = $namaMeja ? ['meja' => $token, 'namaMeja' => $namaMeja, 'katalog' => Menu::katalog()] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pesan | Mandatory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="cust">
<?php if (!$data): ?>
<main class="c-center">
  <img class="c-logo big" src="assets/img/logo.jpeg" alt="Mandatory">
  <h1>QR tidak valid</h1>
  <p>Kode meja tidak dikenali atau sudah tidak aktif. Silakan scan ulang QR di mejamu, atau hubungi kasir.</p>
</main>
<?php else: ?>
<header class="c-head">
  <img class="c-logo" src="assets/img/logo.jpeg" alt="Mandatory">
  <div><strong>Mandatory</strong><span><?= e($data['namaMeja']) ?></span></div>
</header>

<main class="c-main">
  <section class="c-hero">
    <h1>Pesan dari mejamu.</h1>
    <p>Pilih menu dan kirim pesanan. Setelah itu, bayar langsung dengan QRIS dinamis tanpa perlu ke kasir.</p>
    <a class="c-last" id="last" href="#" hidden>Lihat status pesanan terakhirmu &rarr;</a>
  </section>
  <nav class="c-tabs" id="tabs" aria-label="Kategori menu"></nav>
  <input class="c-search" id="cari" type="search" placeholder="Cari menu, contoh: matcha" aria-label="Cari menu">
  <div id="menu"></div>
  <p class="c-empty" id="kosong" hidden>Menu tidak ditemukan.</p>
</main>

<button class="cartbar" id="cartbar" type="button" hidden>
  <span><b id="cbN">0 item</b> <small>Lihat pesanan</small></span><strong id="cbT">Rp 0</strong>
</button>

<div class="sheet" id="sheet" hidden>
  <div class="sheet-bg" data-close></div>
  <form class="sheet-box" id="form" autocomplete="off">
    <div class="sheet-top"><h2>Pesananmu</h2><button type="button" class="x" data-close aria-label="Tutup">&times;</button></div>
    <div id="lines"></div>
    <label class="f">Nama<input id="nama" name="nama" maxlength="60" placeholder="Untuk dipanggil saat pesanan siap" required></label>
    <label class="f">Catatan <i>(opsional)</i><textarea id="catatan" name="catatan" maxlength="200" rows="2" placeholder="Contoh: less sugar, tanpa es"></textarea></label>
    <input id="hp" type="text" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0">
    <p class="err" id="err" hidden></p>
    <button class="btn btn-red btn-block" id="kirim" type="submit">Kirim pesanan</button>
    <p class="muted">Setelah pesanan dikirim, kamu akan diarahkan ke halaman QRIS dengan nominal otomatis sesuai total pesanan.</p>
  </form>
</div>

<script type="application/json" id="data"><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="assets/js/pesan.js"></script>
<?php endif; ?>
</body>
</html>
