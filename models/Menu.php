<?php
/**
 * File  : models/Menu.php
 * Fungsi: Data menu (kategori + produk), pilihan Hot/Iced, dan pengelolaan menu oleh staff.
 */
class Menu
{
    /** Daftar pilihan harga sebuah produk: [['k'=>'Hot','h'=>25000], ...]. k kosong = tanpa pilihan. */
    public static function opsi(array $p): array
    {
        $o = [];
        if ($p['harga_panas'] !== null) {
            $o[] = ['k' => 'Hot', 'h' => (int)$p['harga_panas']];
        }
        if ($p['harga_dingin'] !== null) {
            $o[] = ['k' => 'Iced', 'h' => (int)$p['harga_dingin']];
        }
        if (!$o && $p['harga'] !== null) {
            $o[] = ['k' => '', 'h' => (int)$p['harga']];
        }
        return $o;
    }

    /** Harga produk untuk pilihan tertentu, atau null bila pilihan tidak ada. */
    public static function hargaUntuk(array $p, string $opsi): ?int
    {
        foreach (self::opsi($p) as $o) {
            if ($o['k'] === $opsi) {
                return $o['h'];
            }
        }
        return null;
    }

    public static function cari(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM produk WHERE id_produk = ?');
        $st->execute([$id]);
        $p = $st->fetch();
        return $p ?: null;
    }

    /** Katalog untuk halaman pelanggan: kategori berisi produk (yang punya harga). */
    public static function katalog(): array
    {
        $db  = Database::pdo();
        $out = [];
        foreach ($db->query('SELECT id_kategori, nama_kategori FROM kategori_produk ORDER BY urutan, id_kategori') as $k) {
            $out[(int)$k['id_kategori']] = ['id' => (int)$k['id_kategori'], 'nama' => $k['nama_kategori'], 'produk' => []];
        }
        foreach ($db->query('SELECT * FROM produk ORDER BY urutan, id_produk') as $p) {
            $opsi = self::opsi($p);
            if (!$opsi || !isset($out[(int)$p['id_kategori']])) {
                continue;
            }
            $out[(int)$p['id_kategori']]['produk'][] = [
                'id'    => (int)$p['id_produk'],
                'nama'  => $p['nama_produk'],
                'desk'  => $p['deskripsi'],
                'tag'   => $p['tag'],
                'habis' => !$p['tersedia'],
                'opsi'  => $opsi,
            ];
        }
        return array_values(array_filter($out, fn($k) => $k['produk']));
    }

    public static function kategori(): array
    {
        return Database::pdo()->query('SELECT id_kategori, nama_kategori FROM kategori_produk ORDER BY urutan, id_kategori')->fetchAll();
    }

    /** Semua produk (untuk halaman staff), diurutkan per kategori. */
    public static function semua(): array
    {
        return Database::pdo()->query(
            'SELECT p.*, k.nama_kategori FROM produk p JOIN kategori_produk k ON k.id_kategori = p.id_kategori
             ORDER BY k.urutan, k.id_kategori, p.urutan, p.id_produk'
        )->fetchAll();
    }

    public static function ubahTersedia(int $id): void
    {
        Database::pdo()->prepare('UPDATE produk SET tersedia = 1 - tersedia WHERE id_produk = ?')->execute([$id]);
    }

    /** Tambah (id null) atau ubah produk. Melempar InvalidArgumentException bila data tidak valid. */
    public static function simpan(?int $id, array $d): void
    {
        $nama = trim(mb_substr((string)($d['nama'] ?? ''), 0, 100));
        if ($nama === '') {
            throw new InvalidArgumentException('Nama menu wajib diisi.');
        }
        $harga = [];
        foreach (['harga_panas', 'harga_dingin', 'harga'] as $f) {
            $v = trim((string)($d[$f] ?? ''));
            if ($v === '') {
                $harga[$f] = null;
            } elseif (ctype_digit($v) && (int)$v <= 10000000) {
                $harga[$f] = (int)$v;
            } else {
                throw new InvalidArgumentException('Harga harus berupa angka (tanpa titik/koma).');
            }
        }
        if ($harga['harga_panas'] === null && $harga['harga_dingin'] === null && $harga['harga'] === null) {
            throw new InvalidArgumentException('Isi minimal satu harga.');
        }
        $v = [
            (int)($d['id_kategori'] ?? 0), $nama,
            trim(mb_substr((string)($d['deskripsi'] ?? ''), 0, 200)),
            trim(mb_substr((string)($d['tag'] ?? ''), 0, 20)),
            $harga['harga_panas'], $harga['harga_dingin'], $harga['harga'],
        ];
        $db = Database::pdo();
        if ($id) {
            $db->prepare('UPDATE produk SET id_kategori=?, nama_produk=?, deskripsi=?, tag=?, harga_panas=?, harga_dingin=?, harga=? WHERE id_produk=?')
               ->execute(array_merge($v, [$id]));
        } else {
            $db->prepare('INSERT INTO produk (id_kategori, nama_produk, deskripsi, tag, harga_panas, harga_dingin, harga, urutan) VALUES (?,?,?,?,?,?,?,999)')
               ->execute($v);
        }
    }
}
