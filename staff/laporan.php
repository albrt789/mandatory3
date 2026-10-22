<?php
/** File: staff/laporan.php - (Admin) Laporan penjualan per rentang tanggal. */
require_once __DIR__ . '/../includes/auth.php';
$u = wajibAdmin();

function tanggalValid($s, string $default): string
{
    $d = DateTime::createFromFormat('Y-m-d', (string)$s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : $default;
}
$dari   = tanggalValid($_GET['dari'] ?? '', date('Y-m-d'));
$sampai = tanggalValid($_GET['sampai'] ?? '', date('Y-m-d'));
if ($sampai < $dari) {
    $sampai = $dari;
}
$r = Transaksi::laporan($dari, $sampai);
$n = (int)$r['ringkas']['n'];
$omzet = (int)$r['ringkas']['omzet'];

$judul = 'Laporan';
require __DIR__ . '/../includes/staff_head.php';
?>
<div class="s-bar"><h1>Laporan Penjualan</h1></div>
<form method="get" class="panel inline-form">
  <label class="f">Dari<input type="date" name="dari" value="<?= e($dari) ?>"></label>
  <label class="f">Sampai<input type="date" name="sampai" value="<?= e($sampai) ?>"></label>
  <button class="btn btn-red sm">Tampilkan</button>
</form>

<div class="stats">
  <div class="panel"><small>Omzet</small><strong><?= rupiah($omzet) ?></strong></div>
  <div class="panel"><small>Jumlah transaksi</small><strong><?= $n ?></strong></div>
  <div class="panel"><small>Rata-rata per transaksi</small><strong><?= rupiah($n ? round($omzet / $n) : 0) ?></strong></div>
</div>

<div class="two">
  <section class="panel"><h2 class="s-h2">Per metode bayar</h2>
    <?php foreach ($r['metode'] as $m): ?><div class="row"><span><?= e($m['metode']) ?> (<?= (int)$m['n'] ?>)</span><span><?= rupiah($m['omzet']) ?></span></div><?php endforeach; ?>
    <?php if (!$r['metode']): ?><p class="muted">Belum ada transaksi.</p><?php endif; ?>
  </section>
  <section class="panel"><h2 class="s-h2">Menu terlaris</h2>
    <?php foreach ($r['terlaris'] as $t): ?><div class="row"><span><?= e($t['nama_produk']) ?> &times;<?= (int)$t['qty'] ?></span><span><?= rupiah($t['omzet']) ?></span></div><?php endforeach; ?>
    <?php if (!$r['terlaris']): ?><p class="muted">Belum ada data.</p><?php endif; ?>
  </section>
</div>

<section class="panel"><h2 class="s-h2">Daftar transaksi <small class="muted">(maks. 200 terbaru)</small></h2>
  <div class="scroll"><table class="tbl">
    <tr><th>Waktu</th><th>Kode</th><th>Meja</th><th>Metode</th><th class="r">Total</th></tr>
    <?php foreach ($r['daftar'] as $t): ?>
      <tr><td><?= e(date('d/m H:i', strtotime($t['dibayar']))) ?></td><td><a href="struk.php?id=<?= (int)$t['id_pesanan'] ?>"><?= e($t['kode']) ?></a></td><td><?= e($t['nama_meja']) ?></td><td><?= e($t['metode']) ?></td><td class="r"><?= rupiah($t['total']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>
<?php require __DIR__ . '/../includes/staff_foot.php'; ?>
