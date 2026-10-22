<?php
/** File: staff/menu.php - Daftar menu. Kasir & admin: tandai Habis/Tersedia. Admin: tambah & ubah menu. */
require_once __DIR__ . '/../includes/auth.php';
$u     = wajibLogin();
$admin = $u['role'] === 'admin';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? null)) {
        $error = 'Sesi kedaluwarsa, muat ulang halaman lalu coba lagi.';
    } else {
        try {
            if (($_POST['aksi'] ?? '') === 'toggle') {
                Menu::ubahTersedia((int)($_POST['id'] ?? 0));
            } elseif (($_POST['aksi'] ?? '') === 'simpan' && $admin) {
                Menu::simpan(!empty($_POST['id']) ? (int)$_POST['id'] : null, $_POST);
            }
            header('Location: menu.php?ok=1');
            exit;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('menu.php: ' . $e->getMessage());
            $error = 'Terjadi kesalahan di server.';
        }
    }
}

$kategori = Menu::kategori();

function formProduk(?array $p, array $kategori): string
{
    ob_start(); ?>
    <form method="post" class="f-grid">
      <?= csrfField() ?><input type="hidden" name="aksi" value="simpan">
      <?php if ($p): ?><input type="hidden" name="id" value="<?= (int)$p['id_produk'] ?>"><?php endif; ?>
      <label class="f">Nama<input name="nama" maxlength="100" required value="<?= e($p['nama_produk'] ?? '') ?>"></label>
      <label class="f">Kategori<select name="id_kategori">
        <?php foreach ($kategori as $k): ?>
          <option value="<?= (int)$k['id_kategori'] ?>" <?= (int)($p['id_kategori'] ?? 0) === (int)$k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select></label>
      <label class="f wide">Deskripsi<input name="deskripsi" maxlength="200" value="<?= e($p['deskripsi'] ?? '') ?>"></label>
      <label class="f">Harga Hot (Rp)<input name="harga_panas" inputmode="numeric" value="<?= e($p['harga_panas'] ?? '') ?>"></label>
      <label class="f">Harga Iced (Rp)<input name="harga_dingin" inputmode="numeric" value="<?= e($p['harga_dingin'] ?? '') ?>"></label>
      <label class="f">Harga tunggal (Rp)<input name="harga" inputmode="numeric" value="<?= e($p['harga'] ?? '') ?>"></label>
      <label class="f">Label (mis. Signature)<input name="tag" maxlength="20" value="<?= e($p['tag'] ?? '') ?>"></label>
      <p class="muted wide">Isi harga Hot dan/atau Iced untuk minuman, atau harga tunggal untuk makanan. Kosongkan yang tidak dipakai.</p>
      <button class="btn btn-red sm">Simpan</button>
    </form>
    <?php return ob_get_clean();
}

$judul = 'Menu';
require __DIR__ . '/../includes/staff_head.php';
?>
<div class="s-bar"><h1>Menu</h1></div>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if (isset($_GET['ok'])): ?><div class="alert ok">Tersimpan.</div><?php endif; ?>

<?php if ($admin): ?>
<details class="panel"><summary>+ Tambah menu</summary><?= formProduk(null, $kategori) ?></details>
<?php endif; ?>

<?php $kini = null; foreach (Menu::semua() as $p):
    if ($kini !== $p['nama_kategori']) {
        if ($kini !== null) echo '</div>';
        $kini = $p['nama_kategori'];
        echo '<h2 class="s-h2">' . e($kini) . '</h2><div class="panel list">';
    }
    $hrg = implode(' &middot; ', array_map(fn($o) => ($o['k'] !== '' ? e($o['k']) . ' ' : '') . rupiah($o['h']), Menu::opsi($p))); ?>
  <div class="m-row <?= $p['tersedia'] ? '' : 'off' ?>">
    <div><b><?= e($p['nama_produk']) ?></b><?= $p['tag'] ? ' <em>' . e($p['tag']) . '</em>' : '' ?><br><small><?= $hrg ?></small></div>
    <form method="post">
      <?= csrfField() ?><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id_produk'] ?>">
      <button class="tag <?= $p['tersedia'] ? 'ok' : 'no' ?>"><?= $p['tersedia'] ? 'Tersedia' : 'Habis' ?></button>
    </form>
    <?php if ($admin): ?><details class="edit"><summary>Ubah</summary><?= formProduk($p, $kategori) ?></details><?php endif; ?>
  </div>
<?php endforeach; if ($kini !== null) echo '</div>'; ?>
<?php require __DIR__ . '/../includes/staff_foot.php'; ?>
