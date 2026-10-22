<?php
/** Halaman pembayaran QRIS dinamis pelanggan. */
require_once __DIR__ . '/includes/helpers.php';
$kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['kode'] ?? '')));
$p = $kode !== '' ? Pesanan::byKode($kode) : null;
if (!$p) { http_response_code(404); exit('Pesanan tidak ditemukan.'); }
$error = '';
if ($p['status_bayar'] !== 'Lunas' && $p['status'] !== 'Batal') {
    try { $p = Midtrans::buatQR($p); }
    catch (Throwable $e) { error_log('bayar_qris.php: ' . $e->getMessage()); $error = DEBUG ? $e->getMessage() : 'QRIS belum dapat dibuat. Silakan coba lagi atau hubungi kasir.'; }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Bayar QRIS | Mandatory</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="cust">
<header class="c-head"><img class="c-logo" src="assets/img/logo.jpeg" alt="Mandatory"><div><strong>Mandatory</strong><span>Pembayaran QRIS</span></div></header>
<main class="c-main narrow"><h1>Pembayaran pesanan</h1><p>Kode pesanan: <strong><?= e($p['kode']) ?></strong></p>
<div class="panel"><div class="row total"><span>Total pembayaran</span><strong><?= rupiah($p['total']) ?></strong></div>
<?php if ($p['status_bayar'] === 'Lunas'): ?><div class="alert ok">Pembayaran berhasil diterima. Kamu tidak perlu ke kasir untuk membayar.</div>
<?php elseif ($p['status'] === 'Batal'): ?><div class="alert">Pesanan dibatalkan. Hubungi staf kafe jika membutuhkan bantuan.</div>
<?php elseif ($error): ?><div class="alert"><?= e($error) ?></div><a class="btn btn-red btn-block" href="bayar_qris.php?kode=<?= urlencode($p['kode']) ?>">Coba buat QRIS lagi</a>
<?php elseif (!empty($p['payment_qr_url'])): ?><div style="text-align:center;padding:1rem"><p>Scan QR code ini menggunakan aplikasi pembayaran yang mendukung QRIS.</p><img src="<?= e($p['payment_qr_url']) ?>" alt="QRIS pembayaran <?= e($p['kode']) ?>" style="width:min(100%,320px);height:auto;background:#fff;padding:12px;border-radius:8px"><p><strong><?= rupiah($p['total']) ?></strong></p><p class="muted">Jangan tutup halaman ini sampai pembayaran terkonfirmasi.</p><div id="payStatus" class="alert">Menunggu pembayaran...</div></div>
<?php else: ?><div class="alert">QRIS belum tersedia. Silakan muat ulang halaman.</div><?php endif; ?></div>
<a class="btn btn-line btn-block" href="status.php?kode=<?= urlencode($p['kode']) ?>">Lihat status pesanan</a></main>
<script>(function(){var done=<?= $p['status_bayar']==='Lunas'?'true':'false' ?>;if(done)return;setInterval(function(){fetch('status.php?kode=<?= urlencode($p['kode']) ?>&json=1',{cache:'no-store'}).then(function(r){return r.json()}).then(function(d){if(d.ok&&d.bayar==='Lunas'){var el=document.getElementById('payStatus');if(el){el.className='alert ok';el.textContent='Pembayaran berhasil! Kamu tidak perlu ke kasir untuk membayar.';}setTimeout(function(){location.href='status.php?kode=<?= urlencode($p['kode']) ?>'},1500);}}).catch(function(){});},4000);})();</script>
</body></html>
