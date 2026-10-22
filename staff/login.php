<?php
/** File: staff/login.php - Login staff (admin/kasir) dan logout (?keluar=1). */
require_once __DIR__ . '/../includes/helpers.php';

if (isset($_GET['keluar'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
if (isset($_SESSION['uid'])) {
    header('Location: pesanan.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? null)) {
        $error = 'Sesi kedaluwarsa, muat ulang halaman lalu coba lagi.';
    } else {
        $u = Pengguna::masuk(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''));
        if ($u) {
            session_regenerate_id(true);
            $_SESSION['uid']   = (int)$u['id_user'];
            $_SESSION['nama']  = $u['nama'];
            $_SESSION['role']  = $u['role'];
            header('Location: pesanan.php');
            exit;
        }
        sleep(1);   // memperlambat tebak-tebakan password
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Staff | Mandatory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="cust">
<main class="c-center">
  <img class="c-logo big" src="../assets/img/logo.jpeg" alt="Mandatory">
  <h1>Login staff</h1>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="login-form">
    <?= csrfField() ?>
    <label class="f">Username<input name="username" autocomplete="username" required autofocus></label>
    <label class="f">Password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn-red btn-block">Masuk</button>
  </form>
</main>
</body>
</html>
