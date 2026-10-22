<?php
/**
 * File  : models/Pengguna.php
 * Fungsi: Akun staff (admin/kasir): login, tambah, ganti password, hapus.
 */
class Pengguna
{
    /** Mengembalikan data pengguna bila username & password cocok, selain itu null. */
    public static function masuk(string $username, string $password): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        return ($u && password_verify($password, $u['password_hash'])) ? $u : null;
    }

    public static function semua(): array
    {
        return Database::pdo()->query('SELECT id_user, username, nama, role FROM users ORDER BY role, username')->fetchAll();
    }

    public static function jumlah(): int
    {
        return (int)Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** @throws InvalidArgumentException bila data tidak valid / username sudah dipakai */
    public static function tambah(string $username, string $nama, string $password, string $role): void
    {
        $username = strtolower(trim($username));
        $nama     = trim(mb_substr($nama, 0, 60));
        if (!preg_match('/^[a-z0-9_.]{3,30}$/', $username)) {
            throw new InvalidArgumentException('Username 3-30 karakter: huruf kecil, angka, titik, atau garis bawah.');
        }
        if ($nama === '') {
            throw new InvalidArgumentException('Nama wajib diisi.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password minimal 8 karakter.');
        }
        if (!in_array($role, ['admin', 'kasir'], true)) {
            throw new InvalidArgumentException('Role tidak valid.');
        }
        $db = Database::pdo();
        $st = $db->prepare('SELECT 1 FROM users WHERE username = ?');
        $st->execute([$username]);
        if ($st->fetchColumn()) {
            throw new InvalidArgumentException('Username sudah dipakai.');
        }
        $db->prepare('INSERT INTO users (username, nama, password_hash, role) VALUES (?,?,?,?)')
           ->execute([$username, $nama, password_hash($password, PASSWORD_DEFAULT), $role]);
    }

    public static function ubahPassword(int $id, string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password minimal 8 karakter.');
        }
        Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id_user = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function hapus(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM users WHERE id_user = ?')->execute([$id]);
    }
}
