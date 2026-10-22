<?php
/** File: kirim.php - Menerima pesanan dari pelanggan (JSON) dan menyimpannya. */
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok' => false, 'error' => 'Metode tidak diizinkan.'], 405);
}
$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) {
    jsonOut(['ok' => false, 'error' => 'Data tidak valid.'], 400);
}
if (!empty($in['website'])) {                      // kolom jebakan bot terisi
    jsonOut(['ok' => true, 'kode' => 'XXXXXX']);
}
if (isset($_SESSION['last_order']) && time() - $_SESSION['last_order'] < 15) {
    jsonOut(['ok' => false, 'error' => 'Mohon tunggu sebentar sebelum mengirim pesanan lagi.'], 429);
}

try {
    $kode = Pesanan::buat(
        (string)($in['meja'] ?? ''),
        (string)($in['nama'] ?? ''),
        (string)($in['catatan'] ?? ''),
        is_array($in['items'] ?? null) ? $in['items'] : []
    );
    $_SESSION['last_order'] = time();
    jsonOut(['ok' => true, 'kode' => $kode]);
} catch (InvalidArgumentException $e) {
    jsonOut(['ok' => false, 'error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('kirim.php: ' . $e->getMessage());
    jsonOut(['ok' => false, 'error' => 'Terjadi kesalahan di server. Coba lagi atau hubungi kasir.'], 500);
}
