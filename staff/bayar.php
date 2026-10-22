<?php
/** File: staff/bayar.php - Pembayaran pesanan (Tunai / QRIS / Debit). */
require_once __DIR__ . '/../includes/auth.php';
$u = wajibLogin();

$id = (int)($_GET['id'] ?? 0);
$p  = Pesanan::byId($id);
if (!$p) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? null)) {
        $error = 'Sesi kedaluwarsa, muat ulang halaman lalu coba lagi.';
    } else {
        try {
            Transaksi::bayar($id, (string)($_POST['metode'] ?? ''), (int)preg_replace('/\D/', '', (string)($_POST['diterima'] ?? '')), $u['id']);
            header('Location: struk.php?id=' . $id);
            exit;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('bayar.php: ' . $e->getMessage());
            $error = 'Terjadi kesalahan di server. Coba lagi.';
        }
        $p = Pesanan::byId($id);
    }
}

$judul = 'Pembayaran';
require __DIR__ . '/../includes/staff_head.php';
?>
<div class="s-bar"><h1>Pembayaran <small><?= e($p['kode']) ?></small></h1><a class="btn btn-line sm" href="pesanan.php">&larr; Antrian</a></div>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

<div class="pay">
  <section class="panel">
    <p class="muted"><?= e($p['nama_meja']) ?> &middot; <?= e($p['nama_pelanggan']) ?> &middot; <?= e(date('H:i', strtotime($p['dibuat']))) ?></p>
    <?php foreach ($p['items'] as $d): ?>
      <div class="row"><span><?= (int)$d['qty'] ?>&times; <?= e($d['nama_produk']) ?><?= $d['opsi'] ? ' <em>(' . e($d['opsi']) . ')</em>' : '' ?></span><span><?= rupiah($d['subtotal']) ?></span></div>
    <?php endforeach; ?>
    <?php if ($p['catatan'] !== ''): ?><p class="muted">Catatan: <?= e($p['catatan']) ?></p><?php endif; ?>
    <div class="row total"><span>Total</span><span><?= rupiah($p['total']) ?></span></div>
  </section>

  <section class="panel">
  <?php if ($p['status_bayar'] === 'Lunas'): ?>
    <div class="alert ok">Pesanan ini sudah dibayar.</div>
    <a class="btn btn-red btn-block" href="struk.php?id=<?= (int)$p['id_pesanan'] ?>">Lihat struk</a>
  <?php elseif ($p['status'] === 'Batal'): ?>
    <div class="alert">Pesanan ini dibatalkan, tidak bisa dibayar.</div>
  <?php else: ?>
    <form method="post" id="payForm">
      <?= csrfField() ?>
      <div class="metode">
        <label class="mt"><input type="radio" name="metode" value="Tunai" checked><span>Tunai</span></label>
        <label class="mt"><input type="radio" name="metode" value="QRIS"><span>QRIS</span></label>
        <label class="mt"><input type="radio" name="metode" value="Debit"><span>Debit</span></label>
      </div>
      <div id="tunai">
        <label class="f">Uang diterima (Rp)<input name="diterima" id="diterima" inputmode="numeric" autocomplete="off" placeholder="0"></label>
        <div class="quick">
          <button type="button" data-v="<?= (int)$p['total'] ?>">Uang pas</button>
          <button type="button" data-v="20000">20.000</button>
          <button type="button" data-v="50000">50.000</button>
          <button type="button" data-v="100000">100.000</button>
        </div>
        <p class="kembali">Kembalian: <b id="kembali">Rp 0</b></p>
      </div>
      <p class="muted" id="nontunai" hidden>Pastikan pembayaran sudah masuk di QRIS / mesin EDC sebelum menekan tombol.</p>
      <button class="btn btn-red btn-block">Terima pembayaran &middot; <?= rupiah($p['total']) ?></button>
    </form>
    <script>
    (function () {
      var total = <?= (int)$p['total'] ?>;
      var inp = document.getElementById('diterima'), out = document.getElementById('kembali');
      var rp = function (n) { return 'Rp ' + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
      function hitung() {
        var v = parseInt((inp.value || '').replace(/\D/g, ''), 10) || 0;
        out.textContent = v >= total ? rp(v - total) : 'kurang ' + rp(total - v);
      }
      inp.addEventListener('input', hitung);
      document.querySelector('.quick').addEventListener('click', function (e) {
        var v = e.target.getAttribute('data-v');
        if (v) { inp.value = v; hitung(); }
      });
      document.getElementById('payForm').addEventListener('change', function () {
        var tunai = document.querySelector('input[name=metode]:checked').value === 'Tunai';
        document.getElementById('tunai').hidden = !tunai;
        document.getElementById('nontunai').hidden = tunai;
      });
    })();
    </script>
  <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/../includes/staff_foot.php'; ?>
