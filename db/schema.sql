-- =====================================================================
-- SKEMA DATABASE (PostgreSQL 10+): Sistem Pemesanan QR Mandatory
--
-- 1) Buat database kosong, contoh (di psql):
--      CREATE DATABASE db_mandatory_order ENCODING 'UTF8';
-- 2) Jalankan file ini pada database tersebut:
--      psql -U postgres -d db_mandatory_order -f db/schema.sql
--    (atau tempel isinya di pgAdmin > Query Tool / Adminer / SQL Editor Supabase)
--
-- Menu awal diambil dari desain company profile; sesuaikan di halaman Staff > Menu.
-- =====================================================================

CREATE TABLE IF NOT EXISTS users (
    id_user       SERIAL PRIMARY KEY,
    username      VARCHAR(30)  NOT NULL UNIQUE,
    nama          VARCHAR(60)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10)  NOT NULL DEFAULT 'kasir' CHECK (role IN ('admin', 'kasir'))
);

CREATE TABLE IF NOT EXISTS kategori_produk (
    id_kategori   SERIAL PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL,
    urutan        INT NOT NULL DEFAULT 0
);

-- Harga: isi harga_panas / harga_dingin bila menu punya pilihan Hot/Iced,
-- atau isi harga saja bila tidak ada pilihan. Kolom yang NULL = tidak ditawarkan.
-- tersedia: 1 = tersedia, 0 = habis.
CREATE TABLE IF NOT EXISTS produk (
    id_produk    SERIAL PRIMARY KEY,
    id_kategori  INT NOT NULL REFERENCES kategori_produk(id_kategori) ON UPDATE CASCADE ON DELETE RESTRICT,
    nama_produk  VARCHAR(100) NOT NULL,
    deskripsi    VARCHAR(200) NOT NULL DEFAULT '',
    harga_panas  INT NULL,
    harga_dingin INT NULL,
    harga        INT NULL,
    tag          VARCHAR(20) NOT NULL DEFAULT '',
    tersedia     SMALLINT NOT NULL DEFAULT 1 CHECK (tersedia IN (0, 1)),
    urutan       INT NOT NULL DEFAULT 0
);

-- kode = token acak yang ada di QR (supaya meja tidak bisa ditebak orang luar)
CREATE TABLE IF NOT EXISTS meja (
    id_meja SERIAL PRIMARY KEY,
    nama    VARCHAR(30) NOT NULL,
    kode    VARCHAR(20) NOT NULL UNIQUE,
    aktif   SMALLINT NOT NULL DEFAULT 1 CHECK (aktif IN (0, 1))
);

CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan     SERIAL PRIMARY KEY,
    kode           VARCHAR(8) NOT NULL UNIQUE,
    id_meja        INT NOT NULL REFERENCES meja(id_meja) ON UPDATE CASCADE ON DELETE RESTRICT,
    nama_pelanggan VARCHAR(60) NOT NULL,
    catatan        VARCHAR(200) NOT NULL DEFAULT '',
    status         VARCHAR(10) NOT NULL DEFAULT 'Baru'  CHECK (status IN ('Baru', 'Diproses', 'Siap', 'Selesai', 'Batal')),
    status_bayar   VARCHAR(5)  NOT NULL DEFAULT 'Belum' CHECK (status_bayar IN ('Belum', 'Lunas')),
    total          INT NOT NULL,
    dibuat         TIMESTAMP NOT NULL,
    payment_provider_order_id VARCHAR(64) UNIQUE,
    payment_transaction_id VARCHAR(100),
    payment_qr_url TEXT,
    payment_gateway_status VARCHAR(20) NOT NULL DEFAULT '',
    payment_expiry TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_pesanan_status ON pesanan (status, status_bayar);

-- nama_produk, opsi, dan harga disalin saat pesan (snapshot) agar laporan tetap
-- benar walaupun menu/harga diubah kemudian.
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail   SERIAL PRIMARY KEY,
    id_pesanan  INT NOT NULL REFERENCES pesanan(id_pesanan) ON UPDATE CASCADE ON DELETE CASCADE,
    id_produk   INT NOT NULL REFERENCES produk(id_produk) ON UPDATE CASCADE ON DELETE RESTRICT,
    nama_produk VARCHAR(100) NOT NULL,
    opsi        VARCHAR(10) NOT NULL DEFAULT '',
    harga       INT NOT NULL,
    qty         INT NOT NULL,
    subtotal    INT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_detail_pesanan ON detail_pesanan (id_pesanan);

CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi SERIAL PRIMARY KEY,
    id_pesanan   INT NOT NULL UNIQUE REFERENCES pesanan(id_pesanan) ON UPDATE CASCADE ON DELETE RESTRICT,
    metode       VARCHAR(5) NOT NULL CHECK (metode IN ('Tunai', 'QRIS', 'Debit')),
    total        INT NOT NULL,
    diterima     INT NOT NULL,
    kembalian    INT NOT NULL DEFAULT 0,
    id_user      INT NULL REFERENCES users(id_user) ON UPDATE CASCADE ON DELETE SET NULL,
    dibayar      TIMESTAMP NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_transaksi_dibayar ON transaksi (dibayar);

-- ===================== DATA AWAL =====================
INSERT INTO kategori_produk (nama_kategori, urutan) VALUES
    ('Mandatory Picks', 1),
    ('Essentials', 2),
    ('Alternatives', 3),
    ('Quick Bites', 4);

INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga_panas, harga_dingin, harga, tag, urutan) VALUES
    (1, 'Kopi Mandatory', 'Espresso + milk + homemade palm sugar.', NULL, 23000, NULL, 'Signature', 1),
    (1, 'Blue Jam Latte', 'Espresso + milk + pure blueberry.', NULL, 28000, NULL, '', 2),
    (1, 'Banana Latte', 'Espresso + milk + banana syrup.', NULL, 28000, NULL, '', 3),
    (1, 'Salted Butter', 'Espresso + milk + butterscotch syrup + sea salt.', NULL, 30000, NULL, '', 4),
    (1, 'Pistachio Latte', 'Espresso + milk + pistachio syrup.', NULL, 28000, NULL, '', 5),
    (1, 'Peachycano', 'Americano + peach syrup + water.', NULL, 25000, NULL, '', 6),
    (1, 'Tropicano', 'Americano + passion fruit + water.', NULL, 25000, NULL, '', 7),
    (2, 'Espresso', 'Single shot, rich extraction.', 20000, NULL, NULL, '', 8),
    (2, 'Long Black', 'Single shot of espresso + water.', 23000, 25000, NULL, '', 9),
    (2, 'Latte', 'Single shot of espresso + steamed milk.', 25000, 27000, NULL, '', 10),
    (2, 'Cappuccino', 'Double ristretto + milk + foam.', 25000, 27000, NULL, '', 11),
    (3, 'Chocolate', 'Chocolate powder + milk + sugar.', 28000, 28000, NULL, '', 12),
    (3, 'Banana Chocolate', 'Chocolate powder + milk + banana syrup.', NULL, 30000, NULL, '', 13),
    (3, 'Matcha', 'Matcha powder + milk + simple syrup.', 28000, 28000, NULL, '', 14),
    (3, 'Matcha Berry', 'Pure blueberry + milk + pure strawberry.', NULL, 32000, NULL, '', 15),
    (3, 'Matcha Pistachio', 'Matcha powder + milk + pistachio syrup.', NULL, 32000, NULL, '', 16),
    (3, 'Blueberry Yakult', 'Pure blueberry + milk + yakult + boba.', NULL, 28000, NULL, '', 17),
    (3, 'Strawberry Yakult', 'Strawberry syrup + milk + yakult + boba.', NULL, 28000, NULL, '', 18),
    (3, 'Lychee Tea', 'Tea powder + lychee fruit + water.', NULL, 25000, NULL, '', 19),
    (3, 'Lemon Tea', 'Tea powder + lemon slice + water.', 23000, 25000, NULL, '', 20),
    (3, 'Mineral Water', '', NULL, NULL, 10000, '', 21),
    (4, 'Butter Croissant', '', NULL, NULL, 22000, '', 22),
    (4, 'Chocolate Croissant', '', NULL, NULL, 24000, '', 23),
    (4, 'Tuna Puff', '', NULL, NULL, 22000, '', 24),
    (4, 'Beef & Cheese Quiche', '', NULL, NULL, 24000, '', 25),
    (4, 'French Fries', '', NULL, NULL, 22000, '', 26),
    (4, 'French Fries Cheesy Mayo', '', NULL, NULL, 27000, '', 27),
    (4, 'Mix Platter', 'French fries, sosis, chicken karaage.', NULL, NULL, 35000, '', 28),
    (4, 'Pisang Goreng (palm sugar)', '', NULL, NULL, 25000, '', 29),
    (4, 'Pisang Goreng Srikaya', '', NULL, NULL, 25000, '', 30),
    (4, 'Otak-otak', '', NULL, NULL, 20000, '', 31),
    (4, 'Tahu Isi', '', NULL, NULL, 20000, '', 32),
    (4, 'Nasi Goreng', '', NULL, NULL, 30000, '', 33),
    (4, 'Mie Goreng', '', NULL, NULL, 30000, '', 34),
    (4, 'Spaghetti Aglio Olio', '', NULL, NULL, 35000, '', 35),
    (4, 'Spaghetti Carbonara', '', NULL, NULL, 35000, '', 36);

INSERT INTO meja (nama, kode) VALUES
    ('Meja 1', '6849668766'),
    ('Meja 2', '33317ad2bb'),
    ('Meja 3', '9651503c4c'),
    ('Meja 4', '9788cd6390'),
    ('Meja 5', '78aa6499e7'),
    ('Meja 6', '05ae5ef970'),
    ('Meja 7', 'a0684fb90b'),
    ('Meja 8', '1fa84f2200'),
    ('Meja 9', '99deddd3da'),
    ('Meja 10', 'f8bdb7dfb1');
