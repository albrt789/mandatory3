<?php
/**
 * File  : models/Transaksi.php
 * Fungsi: Pembayaran (Tunai/QRIS/Debit), data struk, dan laporan penjualan.
 * Catatan: konfirmasi QRIS/Debit dilakukan manual oleh kasir (belum terhubung payment gateway).
 */
class Transaksi
{
    public const METODE = ['Tunai', 'QRIS', 'Debit'];

    /**
     * Mencatat pembayaran sebuah pesanan. Mengembalikan id transaksi.
     * @throws InvalidArgumentException bila pembayaran tidak valid
     */
    public static function bayar(int $idPesanan, string $metode, int $diterima, int $idUser): int
    {
        if (!in_array($metode, self::METODE, true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $st = $db->prepare('SELECT total, status, status_bayar FROM pesanan WHERE id_pesanan = ? FOR UPDATE');
            $st->execute([$idPesanan]);
            $p = $st->fetch();
            if (!$p) {
                throw new InvalidArgumentException('Pesanan tidak ditemukan.');
            }
            if ($p['status'] === 'Batal') {
                throw new InvalidArgumentException('Pesanan ini sudah dibatalkan.');
            }
            if ($p['status_bayar'] === 'Lunas') {
                throw new InvalidArgumentException('Pesanan ini sudah dibayar.');
            }
            $total = (int)$p['total'];
            if ($metode === 'Tunai') {
                if ($diterima < $total) {
                    throw new InvalidArgumentException('Uang diterima (' . rupiah($diterima) . ') kurang dari total ' . rupiah($total) . '.');
                }
                $kembalian = $diterima - $total;
            } else {
                $diterima  = $total;
                $kembalian = 0;
            }
            $simpan = $db->prepare('INSERT INTO transaksi (id_pesanan, metode, total, diterima, kembalian, id_user, dibayar)
                                    VALUES (?,?,?,?,?,?,?) RETURNING id_transaksi');
            $simpan->execute([$idPesanan, $metode, $total, $diterima, $kembalian, $idUser, date('Y-m-d H:i:s')]);
            $id = (int)$simpan->fetchColumn();
            $db->prepare("UPDATE pesanan SET status_bayar = 'Lunas' WHERE id_pesanan = ?")->execute([$idPesanan]);
            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function byPesanan(int $idPesanan): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT t.*, u.nama AS nama_kasir FROM transaksi t LEFT JOIN users u ON u.id_user = t.id_user WHERE t.id_pesanan = ?'
        );
        $st->execute([$idPesanan]);
        $t = $st->fetch();
        return $t ?: null;
    }

    /** Laporan penjualan untuk rentang tanggal (format Y-m-d, sudah divalidasi pemanggil). */
    public static function laporan(string $dari, string $sampai): array
    {
        $db = Database::pdo();
        $a  = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

        $st = $db->prepare('SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS omzet FROM transaksi WHERE dibayar BETWEEN ? AND ?');
        $st->execute($a);
        $ringkas = $st->fetch();

        $st = $db->prepare('SELECT metode, COUNT(*) AS n, SUM(total) AS omzet FROM transaksi WHERE dibayar BETWEEN ? AND ? GROUP BY metode');
        $st->execute($a);
        $metode = $st->fetchAll();

        $st = $db->prepare(
            'SELECT d.nama_produk, SUM(d.qty) AS qty, SUM(d.subtotal) AS omzet
             FROM detail_pesanan d JOIN transaksi t ON t.id_pesanan = d.id_pesanan
             WHERE t.dibayar BETWEEN ? AND ? GROUP BY d.nama_produk ORDER BY SUM(d.qty) DESC, SUM(d.subtotal) DESC LIMIT 10'
        );
        $st->execute($a);
        $terlaris = $st->fetchAll();

        $st = $db->prepare(
            'SELECT t.*, p.kode, m.nama AS nama_meja FROM transaksi t
             JOIN pesanan p ON p.id_pesanan = t.id_pesanan JOIN meja m ON m.id_meja = p.id_meja
             WHERE t.dibayar BETWEEN ? AND ? ORDER BY t.dibayar DESC LIMIT 200'
        );
        $st->execute($a);

        return ['ringkas' => $ringkas, 'metode' => $metode, 'terlaris' => $terlaris, 'daftar' => $st->fetchAll()];
    }
}
