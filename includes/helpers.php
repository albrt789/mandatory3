<?php
/**
 * File  : includes/helpers.php
 * Fungsi: Fungsi bantu umum, sesi, CSRF, dan autoload class di folder models/.
 */
require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../models/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** Escape output HTML (cegah XSS). */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 28000 -> "Rp 28.000". */
function rupiah($n): string
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

/** Kirim respons JSON lalu hentikan program. */
function jsonOut(array $data, int $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrfCheck($token): bool
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

/** URL publik aplikasi (dipakai untuk QR). Dipanggil dari halaman di folder staff/. */
function publicUrl(): string
{
    if (PUBLIC_URL !== '') {
        return rtrim(PUBLIC_URL, '/');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $dir   = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir;
}
