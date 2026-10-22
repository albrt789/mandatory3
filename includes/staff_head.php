<?php
/** File: includes/staff_head.php - Kepala + navigasi halaman staff. Butuh variabel $judul dan $u. */
$halaman = basename($_SERVER['SCRIPT_NAME']);
$nav = [['pesanan.php', 'Antrian'], ['menu.php', 'Menu']];
if ($u['role'] === 'admin') {
    $nav[] = ['meja.php', 'Meja & QR'];
    $nav[] = ['laporan.php', 'Laporan'];
    $nav[] = ['pengguna.php', 'Pengguna'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> | Mandatory Staff</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="staff">
<header class="s-nav">
  <a class="s-brand" href="pesanan.php"><img src="../assets/img/logo.jpeg" alt="">Mandatory <small>Staff</small></a>
  <nav>
    <?php foreach ($nav as [$href, $label]): ?>
      <a href="<?= $href ?>" class="<?= $halaman === $href ? 'on' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <span class="s-user"><?= e($u['nama']) ?> (<?= e($u['role']) ?>) &middot; <a href="login.php?keluar=1">Keluar</a></span>
</header>
<main class="s-main">
