<?php
/**
 * File  : includes/auth.php
 * Fungsi: Pemeriksaan login dan role untuk halaman staff.
 */
require_once __DIR__ . '/helpers.php';

/** Data pengguna yang sedang login, atau null. */
function pengguna(): ?array
{
    if (!isset($_SESSION['uid'])) {
        return null;
    }
    return ['id' => (int)$_SESSION['uid'], 'nama' => $_SESSION['nama'], 'role' => $_SESSION['role']];
}

function wajibLogin(): array
{
    $u = pengguna();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function wajibAdmin(): array
{
    $u = wajibLogin();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('Halaman ini khusus admin.');
    }
    return $u;
}
