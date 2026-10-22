<?php
/**
 * File  : models/Pesanan.php
 * Fungsi: Membuat pesanan dari QR meja, membaca pesanan, dan mengubah statusnya.
 * Catatan keamanan: harga SELALU dihitung ulang di server dari tabel produk,
 * harga dari browser tidak pernah dipercaya.
 */
class Pesanan
{
    /** Alur status yang diizinkan. */
    private const ALUR = [
        'Baru'     => ['Diproses', 'Batal'],
        'Diproses' => ['Siap', 'Batal'],
        'Siap'     => ['Selesai', 'Batal'],
        'Selesai'  => [],
        'Batal'    => [],
    ];

    /**
     * Menyimpan pesanan baru dan mengembalikan kodenya.
     * @param array $items daftar ['id'=>int, 'opsi'=>string, 'qty'=>int]
     * @throws InvalidArgumentException bila data tidak valid (pesan aman ditampilkan ke pelanggan)
     */
    public static function buat(string $tokenMeja, string $nama, string $catatan, array $items): string
    {
        $nama    = trim(mb_substr($nama, 0, 60));
        $catatan = trim(mb_substr($catatan, 0, 200));
        if ($nama === '') {
            throw new InvalidArgumentException('Nama wajib diisi.');
        }
        if (!$items) {
            throw new InvalidArgumentException('Pesanan masih kosong.');
        }
        if (count($items) > 30) {
            throw new InvalidArgumentException('Terlalu banyak item dalam satu pesanan.');
        }

        $db = Database::pdo();
        $st = $db->prepare('SELECT id_meja FROM meja WHERE kode = ? AND aktif = 1');
        $st->execute([$tokenMeja]);
        $idMeja = $st->fetchColumn();
        if (!$idMeja) {
            throw new InvalidArgumentException('QR meja tidak valid. Silakan hubungi kasir.');
        }

        $db->beginTransaction();
        try {
            $baris = [];
            $total = 0;
            foreach ($items as $it) {
                $qty = (int)($it['qty'] ?? 0);
                if ($qty < 1 || $qty > 20) {
                    throw new InvalidArgumentException('Jumlah item tidak valid.');
                }
                $p = Menu::cari((int)($it['id'] ?? 0));
                if (!$p || !$p['tersedia']) {
                    throw new InvalidArgumentException('Ada menu yang sudah tidak tersedia. Muat ulang halaman lalu coba lagi.');
                }
                $opsi  = (string)($it['opsi'] ?? '');
                $harga = Menu::hargaUntuk($p, $opsi);
                if ($harga === null) {
                    throw new InvalidArgumentException('Pilihan menu tidak valid.');
                }
                $baris[] = [(int)$p['id_produk'], $p['nama_produk'], $opsi, $harga, $qty, $harga * $qty];
                $total  += $harga * $qty;
            }

            $kode = self::kodeBaru($db);
            $simpan = $db->prepare('INSERT INTO pesanan (kode, id_meja, nama_pelanggan, catatan, status, status_bayar, total, dibuat)
                                    VALUES (?,?,?,?,?,?,?,?) RETURNING id_pesanan');
            $simpan->execute([$kode, $idMeja, $nama, $catatan, 'Baru', 'Belum', $total, date('Y-m-d H:i:s')]);
            $idPesanan = (int)$simpan->fetchColumn();

            $ins = $db->prepare('INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, opsi, harga, qty, subtotal)
                                 VALUES (?,?,?,?,?,?,?)');
            foreach ($baris as $b) {
                $ins->execute(array_merge([$idPesanan], $b));
            }
            $db->commit();
            return $kode;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** Kode 6 karakter yang mudah dibaca (tanpa 0/O/1/I), dijamin unik. */
    private static function kodeBaru(PDO $db): string
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $cek   = $db->prepare('SELECT 1 FROM pesanan WHERE kode = ?');
        for ($i = 0; $i < 10; $i++) {
            $kode = '';
            for ($j = 0; $j < 6; $j++) {
                $kode .= $abjad[random_int(0, strlen($abjad) - 1)];
            }
            $cek->execute([$kode]);
            if (!$cek->fetchColumn()) {
                return $kode;
            }
        }
        throw new RuntimeException('Gagal membuat kode pesanan.');
    }

    public static function byKode(string $kode): ?array
    {
        return self::ambil('p.kode', strtoupper($kode));
    }

    public static function byId(int $id): ?array
    {
        return self::ambil('p.id_pesanan', $id);
    }

    /** $kolom hanya diisi konstanta dari dalam class ini (bukan input pengguna). */
    private static function ambil(string $kolom, $nilai): ?array
    {
        $st = Database::pdo()->prepare(
            "SELECT p.*, m.nama AS nama_meja, m.kode AS kode_meja
             FROM pesanan p JOIN meja m ON m.id_meja = p.id_meja WHERE $kolom = ?"
        );
        $st->execute([$nilai]);
        $p = $st->fetch();
        if (!$p) {
            return null;
        }
        $it = Database::pdo()->prepare('SELECT nama_produk, opsi, harga, qty, subtotal FROM detail_pesanan WHERE id_pesanan = ? ORDER BY id_detail');
        $it->execute([$p['id_pesanan']]);
        $p['items'] = $it->fetchAll();
        return $p;
    }

    /** Pesanan yang masih perlu ditangani staff (aktif, atau selesai tapi belum dibayar). */
    public static function aktif(): array
    {
        $db   = Database::pdo();
        $rows = $db->query(
            "SELECT p.*, m.nama AS nama_meja FROM pesanan p JOIN meja m ON m.id_meja = p.id_meja
             WHERE p.status IN ('Baru','Diproses','Siap') OR (p.status = 'Selesai' AND p.status_bayar = 'Belum')
             ORDER BY p.dibuat ASC"
        )->fetchAll();
        if (!$rows) {
            return [];
        }
        $ids = array_column($rows, 'id_pesanan');
        $st  = $db->prepare('SELECT id_pesanan, nama_produk, opsi, qty FROM detail_pesanan WHERE id_pesanan IN ('
            . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY id_detail');
        $st->execute($ids);
        $items = [];
        foreach ($st->fetchAll() as $d) {
            $items[$d['id_pesanan']][] = $d;
        }
        foreach ($rows as &$r) {
            $r['items'] = $items[$r['id_pesanan']] ?? [];
        }
        unset($r);
        return $rows;
    }

    public static function ubahStatus(int $id, string $baru): void
    {
        $p = self::byId($id);
        if (!$p) {
            throw new InvalidArgumentException('Pesanan tidak ditemukan.');
        }
        if (!in_array($baru, self::ALUR[$p['status']] ?? [], true)) {
            throw new InvalidArgumentException('Perubahan status tidak diizinkan.');
        }
        if ($baru === 'Batal' && $p['status_bayar'] === 'Lunas') {
            throw new InvalidArgumentException('Pesanan sudah dibayar, tidak bisa dibatalkan.');
        }
        Database::pdo()->prepare('UPDATE pesanan SET status = ? WHERE id_pesanan = ?')->execute([$baru, $id]);
    }
}
