<?php
/** File: staff/api.php - Endpoint JSON untuk papan antrian (daftar pesanan + ubah status). */
require_once __DIR__ . '/../includes/helpers.php';

if (!isset($_SESSION['uid'])) {
    jsonOut(['ok' => false, 'error' => 'Sesi habis, silakan login ulang.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['aksi'] ?? '') === 'antrian') {
    $out = [];
    foreach (Pesanan::aktif() as $r) {
        $out[] = [
            'id'     => (int)$r['id_pesanan'],
            'kode'   => $r['kode'],
            'meja'   => $r['nama_meja'],
            'nama'   => $r['nama_pelanggan'],
            'catatan'=> $r['catatan'],
            'status' => $r['status'],
            'bayar'  => $r['status_bayar'],
            'total'  => (int)$r['total'],
            'lama'   => max(0, (int)floor((time() - strtotime($r['dibuat'])) / 60)),
            'items'  => array_map(fn($d) => ['n' => $d['nama_produk'], 'o' => $d['opsi'], 'q' => (int)$d['qty']], $r['items']),
        ];
    }
    jsonOut(['ok' => true, 'pesanan' => $out]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in) || !csrfCheck($in['csrf'] ?? null)) {
        jsonOut(['ok' => false, 'error' => 'Sesi tidak valid, muat ulang halaman.'], 403);
    }
    if (($in['aksi'] ?? '') === 'status') {
        try {
            Pesanan::ubahStatus((int)($in['id'] ?? 0), (string)($in['status'] ?? ''));
            jsonOut(['ok' => true]);
        } catch (InvalidArgumentException $e) {
            jsonOut(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('api.php: ' . $e->getMessage());
            jsonOut(['ok' => false, 'error' => 'Terjadi kesalahan di server.'], 500);
        }
    }
}
jsonOut(['ok' => false, 'error' => 'Permintaan tidak dikenal.'], 400);
