-- Jalankan SATU KALI pada database PostgreSQL yang sudah dipakai aplikasi.
-- Backup database sebelum migrasi.
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS payment_provider_order_id VARCHAR(64) UNIQUE;
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS payment_transaction_id VARCHAR(100);
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS payment_qr_url TEXT;
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS payment_gateway_status VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS payment_expiry TIMESTAMP NULL;
