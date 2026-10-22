<?php
/** Integrasi QRIS dinamis via Midtrans Core API. Server Key hanya di server. */
class Midtrans
{
    private static function apiBase(): string
    {
        return MIDTRANS_IS_PRODUCTION ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    private static function request(string $path, ?array $body = null): array
    {
        if (MIDTRANS_SERVER_KEY === '') {
            throw new RuntimeException('Server Key Midtrans belum diisi di config/config.php.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi PHP cURL belum aktif. Aktifkan extension=curl di php.ini.');
        }
        $ch = curl_init(self::apiBase() . $path);
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERPWD => MIDTRANS_SERVER_KEY . ':',
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT => 25,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('Gagal terhubung ke Midtrans: ' . $err);
        $data = json_decode($raw, true);
        if (!is_array($data)) throw new RuntimeException('Respons Midtrans tidak valid.');
        if ($code < 200 || $code >= 300) {
            error_log('Midtrans API error HTTP ' . $code . ': ' . $raw);
            throw new RuntimeException('Midtrans menolak pembuatan QRIS. Periksa konfigurasi dan dashboard Midtrans.');
        }
        return $data;
    }

    /** Membuat QRIS sekali per pesanan dan menyimpan URL QR dari Midtrans. */
    public static function buatQR(array $p): array
    {
        if (!empty($p['payment_qr_url']) && ($p['payment_gateway_status'] ?? '') === 'pending') return $p;
        if (($p['status_bayar'] ?? '') === 'Lunas') return $p;
        if (($p['status'] ?? '') === 'Batal') throw new InvalidArgumentException('Pesanan dibatalkan dan tidak dapat dibayar.');

        $db = Database::pdo();
        $orderId = $p['payment_provider_order_id'] ?: ('MANDATORY-' . $p['id_pesanan'] . '-' . strtoupper($p['kode']));
        if (in_array(($p['payment_gateway_status'] ?? ''), ['expire', 'cancel', 'deny', 'failure'], true)) {
            // Transaksi yang sudah kedaluwarsa/gagal harus memakai order_id baru.
            $orderId = 'MANDATORY-' . $p['id_pesanan'] . '-' . strtoupper($p['kode']) . '-' . date('His');
        }
        $baseUrl = PUBLIC_URL !== '' ? rtrim(PUBLIC_URL, '/') : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/'));
        $notifyUrl = $baseUrl . '/webhook_midtrans.php';
        $items = [];
        foreach (($p['items'] ?? []) as $item) {
            $items[] = ['id' => (string)($item['id_produk'] ?? 'menu'), 'price' => (int)$item['harga'], 'quantity' => (int)$item['qty'], 'name' => mb_substr((string)$item['nama_produk'], 0, 50)];
        }
        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int)$p['total']],
            'customer_details' => ['first_name' => mb_substr((string)$p['nama_pelanggan'], 0, 50)],
            'qris' => ['acquirer' => MIDTRANS_QRIS_ACQUIRER],
        ];
        if ($items) $payload['item_details'] = $items;
        $res = self::request('/v2/charge', $payload);
        $qrUrl = '';
        foreach (($res['actions'] ?? []) as $action) {
            if (($action['name'] ?? '') === 'generate-qr-code' || ($action['name'] ?? '') === 'generate-qr-code-v2') { $qrUrl = (string)($action['url'] ?? ''); if ($qrUrl !== '') break; }
        }
        if ($qrUrl === '' || empty($res['transaction_id'])) throw new RuntimeException('Midtrans tidak mengembalikan QR code.');
        $expiry = null;
        if (!empty($res['expiry_time'])) { $expiry = date('Y-m-d H:i:s', strtotime($res['expiry_time'])); }
        $st = $db->prepare("UPDATE pesanan SET payment_provider_order_id = ?, payment_transaction_id = ?, payment_qr_url = ?, payment_gateway_status = ?, payment_expiry = ? WHERE id_pesanan = ? AND status_bayar = 'Belum'");
        $st->execute([$orderId, $res['transaction_id'], $qrUrl, (string)($res['transaction_status'] ?? 'pending'), $expiry, (int)$p['id_pesanan']]);
        return Pesanan::byId((int)$p['id_pesanan']) ?? $p;
    }

    /** Verifikasi signature webhook berdasarkan formula resmi Midtrans. */
    public static function validasiNotifikasi(array $n): bool
    {
        foreach (['order_id','status_code','gross_amount','signature_key'] as $k) if (!isset($n[$k])) return false;
        $expected = hash('sha512', (string)$n['order_id'] . (string)$n['status_code'] . (string)$n['gross_amount'] . MIDTRANS_SERVER_KEY);
        return hash_equals($expected, (string)$n['signature_key']);
    }

    public static function prosesNotifikasi(array $n): void
    {
        if (!self::validasiNotifikasi($n)) throw new RuntimeException('Signature notifikasi tidak valid.');
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $st = $db->prepare('SELECT id_pesanan, total, status_bayar FROM pesanan WHERE payment_provider_order_id = ? FOR UPDATE');
            $st->execute([(string)$n['order_id']]);
            $p = $st->fetch();
            if (!$p) throw new RuntimeException('Pesanan untuk notifikasi tidak ditemukan.');
            if ((int)round((float)$n['gross_amount']) !== (int)$p['total']) throw new RuntimeException('Nominal notifikasi tidak cocok.');
            $status = (string)($n['transaction_status'] ?? '');
            $db->prepare('UPDATE pesanan SET payment_gateway_status = ?, payment_transaction_id = COALESCE(?, payment_transaction_id) WHERE id_pesanan = ?')
               ->execute([$status, $n['transaction_id'] ?? null, $p['id_pesanan']]);
            if ($status === 'settlement' && $p['status_bayar'] !== 'Lunas') {
                $st = $db->prepare("INSERT INTO transaksi (id_pesanan, metode, total, diterima, kembalian, id_user, dibayar) VALUES (?, 'QRIS', ?, ?, 0, NULL, ?) ON CONFLICT (id_pesanan) DO NOTHING");
                $st->execute([$p['id_pesanan'], $p['total'], $p['total'], date('Y-m-d H:i:s')]);
                $db->prepare("UPDATE pesanan SET status_bayar = 'Lunas' WHERE id_pesanan = ?")->execute([$p['id_pesanan']]);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
}
