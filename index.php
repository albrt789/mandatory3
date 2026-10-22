<?php
/** File: index.php - Halaman depan. Pelanggan masuk lewat QR di meja (pesan.php?meja=...). */
require_once __DIR__ . '/includes/helpers.php';
if (!empty($_GET['meja'])) {
    header('Location: pesan.php?meja=' . urlencode((string)$_GET['meja']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mandatory | Pesan dari meja</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="cust">
<main class="c-center">
  <img class="c-logo big" src="assets/img/logo.jpeg" alt="Mandatory">
  <h1>Coffee is <b>MANDATORY</b></h1>
  <p>Scan QR yang ada di mejamu untuk melihat menu dan memesan langsung dari kursimu.</p>
  <a class="btn btn-line" href="staff/login.php">Login staff</a>
</main>
</body>
</html>
