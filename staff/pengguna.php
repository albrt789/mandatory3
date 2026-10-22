<?php
/** File: staff/pengguna.php - (Admin) Kelola akun staff: tambah, ganti password, hapus. */
require_once __DIR__ . '/../includes/auth.php';
$u     = wajibAdmin();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? null)) {
        $error = 'Sesi kedaluwarsa, muat ulang halaman lalu coba lagi.';
    } else {
        try {
            $aksi = $_POST['aksi'] ?? '';
            $id   = (int)($_POST['id'] ?? 0);
            if ($aksi === 'tambah') {
                Pengguna::tambah((string)($_POST['username'] ?? ''), (string)($_POST['nama'] ?? ''), (string)($_POST['password'] ?? ''), (string)($_POST['role'] ?? ''));
            } elseif ($aksi === 'password') {
                Pengguna::ubahPassword($id, (string)($_POST['password'] ?? ''));
            } elseif ($aksi === 'hapus') {
                if ($id === $u['id']) {
                    throw new InvalidArgumentException('Akun yang sedang dipakai tidak bisa dihapus.');
                }
                Pengguna::hapus($id);
            }
            header('Location: pengguna.php?ok=1');
            exit;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log('pengguna.php: ' . $e->getMessage());
            $error = 'Terjadi kesalahan di server.';
        }
    }
}

$judul = 'Pengguna';
require __DIR__ . '/../includes/staff_head.php';
?>
<div class="s-bar"><h1>Pengguna</h1></div>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if (isset($_GET['ok'])): ?><div class="alert ok">Tersimpan.</div><?php endif; ?>

<form method="post" class="panel f-grid">
  <?= csrfField() ?><input type="hidden" name="aksi" value="tambah">
  <label class="f">Username<input name="username" maxlength="30" required></label>
  <label class="f">Nama<input name="nama" maxlength="60" required></label>
  <label class="f">Password (min. 8)<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
  <label class="f">Role<select name="role"><option value="kasir">Kasir</option><option value="admin">Admin</option></select></label>
  <button class="btn btn-red sm">Tambah pengguna</button>
</form>

<div class="panel list">
  <?php foreach (Pengguna::semua() as $p): ?>
    <div class="m-row">
      <div><b><?= e($p['nama']) ?></b> <em><?= e($p['role']) ?></em><br><small><?= e($p['username']) ?></small></div>
      <form method="post" class="inline-form">
        <?= csrfField() ?><input type="hidden" name="aksi" value="password"><input type="hidden" name="id" value="<?= (int)$p['id_user'] ?>">
        <input type="password" name="password" placeholder="Password baru" minlength="8" required autocomplete="new-password">
        <button class="btn btn-line sm">Ganti</button>
      </form>
      <?php if ((int)$p['id_user'] !== $u['id']): ?>
      <form method="post" onsubmit="return confirm('Hapus pengguna ini?')">
        <?= csrfField() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$p['id_user'] ?>">
        <button class="btn-x">Hapus</button>
      </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/staff_foot.php'; ?>
