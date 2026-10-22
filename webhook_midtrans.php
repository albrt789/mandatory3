<?php
/** Endpoint notifikasi pembayaran Midtrans. Harus bisa diakses publik via HTTPS. */
require_once __DIR__ . '/includes/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonOut(['ok' => false], 405);
$notification = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($notification)) jsonOut(['ok' => false, 'error' => 'Invalid JSON'], 400);
try {
    Midtrans::prosesNotifikasi($notification);
    jsonOut(['ok' => true]);
} catch (Throwable $e) {
    error_log('webhook_midtrans.php: ' . $e->getMessage());
    jsonOut(['ok' => false], 400);
}
