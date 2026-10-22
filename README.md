# Mandatory - Sistem Pemesanan QR

Pelanggan scan QR di meja, pilih menu, kirim pesanan. Barista/kasir melihat antrian secara
langsung, memproses pesanan, lalu menerima pembayaran dan mencetak struk.

Teknologi: PHP 7.4+ (disarankan 8.x) + **PostgreSQL 10+** (PDO pgsql). Butuh ekstensi PHP `pdo_pgsql` aktif.

## Struktur
```
mandatory_order/
├── index.php            Halaman depan
├── pesan.php            Menu & keranjang pelanggan (dibuka dari QR)
├── kirim.php            Menerima pesanan (JSON)
├── status.php           Status pesanan pelanggan (update otomatis)
├── install.php          Membuat akun admin pertama (HAPUS setelah dipakai)
├── staff/               Login, antrian, bayar, struk, menu, meja & QR, laporan, pengguna
├── config/config.php    Pengaturan database PostgreSQL + koneksi
├── models/              Menu, Pesanan, Transaksi, Pengguna
├── includes/            Fungsi bantu, login, layout staff
├── assets/              CSS, JS, logo
└── db/schema.sql        Struktur tabel (PostgreSQL) + menu awal + 10 meja
```

## Pemasangan
1. Buat database PostgreSQL kosong, contoh di psql: `CREATE DATABASE db_mandatory_order ENCODING 'UTF8';`
2. Jalankan `db/schema.sql` pada database itu:
   `psql -U postgres -d db_mandatory_order -f db/schema.sql`
   (atau tempel isinya di pgAdmin > Query Tool, Adminer, atau SQL Editor Supabase).
3. Edit `config/config.php`: isi `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
   - Layanan PostgreSQL online (Supabase, Neon, dll.): ubah `DB_SSLMODE` menjadi `'require'`.
     Pakai koneksi langsung / session mode (port 5432), bukan transaction pooler (port 6543),
     karena aplikasi memakai prepared statement.
   - Isi `PUBLIC_URL` (mis. `https://order.mandatory.id`) bila alamat QR di halaman Meja & QR salah.
4. Pastikan ekstensi PHP `pdo_pgsql` aktif (cPanel: *Select PHP Version* > Extensions; XAMPP: aktifkan
   `extension=pdo_pgsql` dan `extension=pgsql` di php.ini).
5. Upload semua file ke hosting (mis. folder `public_html/order`).
6. Buka `https://domainmu/order/install.php`, buat akun **admin**, lalu **hapus file install.php**.
7. Login di `https://domainmu/order/staff/login.php`.
8. Menu **Pengguna**: tambahkan akun untuk kasir/barista.
9. Menu **Meja & QR**: cek alamat tiap QR, lalu **Cetak semua QR** dan tempel di meja.

Pakai HTTPS agar sesi login dan data pelanggan aman.

## Alur harian
- Pelanggan: scan QR -> pilih menu (Hot/Iced) -> kirim pesanan -> bayar dengan QRIS dinamis -> melihat status langsung.
- Barista/kasir: buka **Antrian** (menyala terus di tablet/laptop). Pesanan baru muncul otomatis
  (aktifkan tombol Suara). Tekan Proses -> Siap -> Selesai.
- Pembayaran di kasir tetap tersedia untuk Tunai / QRIS manual / Debit. Pelanggan dapat membayar sendiri lewat QRIS dinamis jika Midtrans telah dikonfigurasi.
- Menu habis: **Menu** -> klik status *Tersedia* menjadi *Habis* (langsung tidak bisa dipesan).
- Admin: **Laporan** (omzet, metode bayar, menu terlaris), **Menu** (tambah/ubah harga), **Meja**, **Pengguna**.

## Keamanan yang sudah diterapkan
- Harga selalu dihitung ulang di server; harga dari browser tidak dipercaya.
- Prepared statement PDO, escape output HTML, token CSRF di semua form staff, password di-hash.
- QR memakai kode acak per meja (bukan nomor meja), meja bisa dinonaktifkan.
- Pembatasan kirim pesanan (15 detik per perangkat) + kolom jebakan bot.
- Folder internal diblokir lewat `.htaccess` (Apache).

## Belum termasuk (bisa ditambah)
- QRIS dinamis via Midtrans Core API sudah disiapkan. Wajib isi kredensial Midtrans, jalankan migrasi, dan pasang URL webhook publik sebelum digunakan.
- Cetak struk ke printer thermal otomatis (struk saat ini dicetak lewat browser).
- Ekspor laporan ke Excel/CSV, pengelolaan kategori menu, stok per jumlah (saat ini hanya Tersedia/Habis).
- Menu awal diambil dari desain company profile; **cek ulang harga Quick Bites** di halaman Menu.


## Mengaktifkan QRIS dinamis (Midtrans)

1. Daftar/aktifkan QRIS di akun merchant Midtrans. Mulai uji coba dengan kredensial **Sandbox**.
2. Edit `config/config.php`: isi `MIDTRANS_SERVER_KEY`, biarkan `MIDTRANS_IS_PRODUCTION = false` untuk sandbox. Jangan pernah menaruh Server Key di JavaScript/frontend.
3. Jalankan `db/migration_qris_dinamis.sql` pada database yang sudah ada. Untuk instalasi baru, `db/schema.sql` sudah mencakup kolom tambahan.
4. Aktifkan ekstensi PHP `curl` di `php.ini`, lalu restart Apache.
5. Pastikan aplikasi dapat diakses melalui URL publik HTTPS. Atur `PUBLIC_URL` ke URL dasar aplikasi (contoh `https://domainanda.com/mandatory_order`) agar URL notifikasi benar. Midtrans harus dapat mengakses `https://domainanda.com/mandatory_order/webhook_midtrans.php`; localhost biasa tidak dapat menerima webhook dari Midtrans.
6. Di dashboard Midtrans, atur URL notifikasi ke URL tersebut jika diperlukan. Uji alur sandbox: buat pesanan -> QRIS dibuat dengan nominal total -> lakukan pembayaran sandbox -> webhook mengubah status pesanan menjadi Lunas.
7. Setelah akun merchant siap produksi, isi Server Key produksi dan ubah `MIDTRANS_IS_PRODUCTION = true`. Lakukan pengujian produksi bernilai kecil terlebih dahulu.

**Penting:** QRIS dinamis membutuhkan akun payment gateway/merchant yang disetujui dan biaya transaksi mengikuti ketentuan provider. Jangan menandai transaksi Lunas berdasarkan tampilan browser; aplikasi hanya menandai Lunas setelah notifikasi Midtrans dengan signature dan nominal yang valid. Selalu backup database sebelum menjalankan migrasi.
