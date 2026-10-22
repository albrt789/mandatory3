<?php
/**
 * File  : config/config.php
 * Fungsi: Pengaturan aplikasi dan koneksi database PostgreSQL (PDO pgsql, pola Singleton).
 * Butuh ekstensi PHP: pdo_pgsql.
 * Ubah bagian PENGATURAN di bawah sesuai hosting Anda.
 */

/* ===================== PENGATURAN ===================== */
const DB_HOST    = 'localhost';
const DB_PORT    = '5432';
const DB_NAME    = 'db_mandatory_order';
const DB_USER    = 'postgres';
const DB_PASS    = '';
const DB_SSLMODE = 'prefer';       // 'require' untuk layanan PostgreSQL online (Supabase, Neon, dll.)
const PUBLIC_URL = '';             // contoh: 'https://order.mandatory.id' (tanpa garis miring akhir). Kosong = terdeteksi otomatis.
const TIMEZONE   = 'Asia/Jakarta';
const DEBUG      = true;          // true hanya saat pengembangan

// Midtrans QRIS Dinamis. Mulai dengan Sandbox; jangan pernah kirim Server Key ke browser.
const MIDTRANS_SERVER_KEY = '';    // isi Server Key dari dashboard Midtrans
const MIDTRANS_IS_PRODUCTION = false; // false = sandbox, true = production
const MIDTRANS_QRIS_ACQUIRER = 'gopay'; // gopay atau airpay_shopee
/* ====================================================== */

date_default_timezone_set(TIMEZONE);
mb_internal_encoding('UTF-8');

class Database
{
    private static ?PDO $pdo = null;

    /** Mengambil koneksi PDO tunggal. */
    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=' . DB_SSLMODE,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
                self::$pdo->exec("SET client_encoding TO 'UTF8'");
            } catch (PDOException $e) {
                error_log('Koneksi DB gagal: ' . $e->getMessage());
                http_response_code(500);
                die('<p style="font-family:sans-serif;padding:2rem">Sistem sedang bermasalah. Silakan hubungi kasir.'
                    . (DEBUG ? '<br><small>' . htmlspecialchars($e->getMessage()) . '</small>' : '') . '</p>');
            }
        }
        return self::$pdo;
    }
}
