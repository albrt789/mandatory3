<?php
/** File: staff/struk.php - Struk pembayaran (siap cetak). */
require_once __DIR__ . '/../includes/auth.php';
$u = wajibLogin();

$id = (int)($_GET['id'] ?? 0);
$p  = Pesanan::byId($id);
$t  = $p ? Transaksi::byPesanan($id) : null;
if (!$p) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}
if (!$t) {
    header('Location: bayar.php?id=' . $id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Struk <?= e($p['kode']) ?> | Mandatory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="struk-page">
<div class="struk">
  <img src="../assets/img/logo.jpeg" alt="Mandatory">
  <h1>MANDATORY</h1>
  <p class="c">Kopi, obrolan, dan jeda kecil.</p>
  <hr>
  <div class="row"><span>Kode</span><span><?= e($p['kode']) ?></span></div>
  <div class="row"><span>Meja</span><span><?= e($p['nama_meja']) ?></span></div>
  <div class="row"><span>Nama</span><span><?= e($p['nama_pelanggan']) ?></span></div>
  <div class="row"><span>Waktu</span><span><?= e(date('d/m/Y H:i', strtotime($t['dibayar']))) ?></span></div>
  <div class="row"><span>Kasir</span><span><?= e($t['nama_kasir'] ?? '-') ?></span></div>
  <hr>
  <?php foreach ($p['items'] as $d): ?>
    <div class="row"><span><?= (int)$d['qty'] ?>&times; <?= e($d['nama_produk']) ?><?= $d['opsi'] ? ' (' . e($d['opsi']) . ')' : '' ?></span><span><?= rupiah($d['subtotal']) ?></span></div>
  <?php endforeach; ?>
  <hr>
  <div class="row b"><span>Total</span><span><?= rupiah($t['total']) ?></span></div>
  <div class="row"><span>Bayar (<?= e($t['metode']) ?>)</span><span><?= rupiah($t['diterima']) ?></span></div>
  <div class="row"><span>Kembalian</span><span><?= rupiah($t['kembalian']) ?></span></div>
  <hr>
  <p class="c">Terima kasih sudah mampir!<br>@kopimandatory</p>
</div>
<div class="struk-act">
  <button class="btn btn-red" onclick="window.print()">Cetak</button>
  <a class="btn btn-line" href="pesanan.php">Kembali ke antrian</a>
</div>
</body>
</html>
