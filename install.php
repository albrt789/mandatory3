<?php
/**
 * File: install.php - Pemasangan awal: membuat akun ADMIN pertama.
 * Jalankan SEKALI setelah db/schema.sql diimpor, lalu HAPUS file ini dari server.
 */
require_once __DIR__ . '/includes/helpers.php';

try {
    $jumlah = Pengguna::jumlah();
} catch (Throwable $e) {
    $jumlah = -1;   // tabel belum ada
}

$pesan = '';
$error = '';
if ($jumlah === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? null)) {
        $error = 'Sesi kedaluwarsa, muat ulang halaman.';
    } else {
        try {
            Pengguna::tambah((string)($_POST['username'] ?? ''), (string)($_POST['nama'] ?? ''), (string)($_POST['password'] ?? ''), 'admin');
            $jumlah = 1;
            $pesan  = 'Akun admin dibuat. Sekarang HAPUS file install.php dari server, lalu login di staff/login.php.';
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pemasangan | Mandatory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="cust">
<main class="c-center">
  <img class="c-logo big" src="assets/img/logo.jpeg" alt="Mandatory">
  <h1>Pemasangan</h1>
  <?php if ($pesan): ?><div class="alert ok"><?= e($pesan) ?></div><a class="btn btn-red" href="staff/login.php">Ke halaman login</a>
  <?php elseif ($jumlah < 0): ?><div class="alert">Tabel belum ada. Jalankan <b>db/schema.sql</b> di database PostgreSQL (pgAdmin / psql / Adminer) terlebih dahulu, lalu muat ulang halaman ini.</div>
  <?php elseif ($jumlah > 0): ?><div class="alert">Sistem sudah terpasang. Hapus file <b>install.php</b> dari server demi keamanan.</div>
  <?php else: ?>
    <p>Buat akun admin pertama.</p>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="login-form">
      <?= csrfField() ?>
      <label class="f">Username<input name="username" maxlength="30" required autofocus></label>
      <label class="f">Nama<input name="nama" maxlength="60" required></label>
      <label class="f">Password (min. 8 karakter)<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
      <button class="btn btn-red btn-block">Buat akun admin</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
