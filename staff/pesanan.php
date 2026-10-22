<?php
/** File: staff/pesanan.php - Antrian pesanan (papan Baru / Diproses / Siap / Selesai belum bayar), diperbarui otomatis. */
require_once __DIR__ . '/../includes/auth.php';
$u = wajibLogin();
$judul = 'Antrian Pesanan';
require __DIR__ . '/../includes/staff_head.php';
?>
<div class="s-bar">
  <h1>Antrian Pesanan</h1>
  <div><span class="muted" id="sync">Memuat&hellip;</span> <button type="button" class="btn btn-line sm" id="snd">&#128263; Suara mati</button></div>
</div>
<div class="board" id="board" data-csrf="<?= e(csrfToken()) ?>">
  <section><h2>Baru <b data-n="Baru">0</b></h2><div class="cards" data-col="Baru"></div></section>
  <section><h2>Diproses <b data-n="Diproses">0</b></h2><div class="cards" data-col="Diproses"></div></section>
  <section><h2>Siap <b data-n="Siap">0</b></h2><div class="cards" data-col="Siap"></div></section>
  <section><h2>Selesai, belum bayar <b data-n="Selesai">0</b></h2><div class="cards" data-col="Selesai"></div></section>
</div>
<script src="../assets/js/antrian.js"></script>
<?php require __DIR__ . '/../includes/staff_foot.php'; ?>
