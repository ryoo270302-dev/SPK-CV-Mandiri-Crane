<?php
require_once __DIR__ . '/../config/koneksi.php';

class User
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = getDb()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDb()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(): array
    {
        return getDb()->query('SELECT id, username, nama, role, created_at FROM users ORDER BY nama')->fetchAll();
    }

    public static function create(string $username, string $password, string $nama, string $role): int
    {
        $stmt = getDb()->prepare(
            'INSERT INTO users (username, password_hash, nama, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nama, $role]);
        return (int) getDb()->lastInsertId();
    }

    public static function updateProfile(int $id, string $nama, string $role): void
    {
        $stmt = getDb()->prepare('UPDATE users SET nama = ?, role = ? WHERE id = ?');
        $stmt->execute([$nama, $role, $id]);
    }

    public static function updatePassword(int $id, string $newPassword): void
    {
        $stmt = getDb()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = getDb()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = getDb()->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = getDb()->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
        }
        return (bool) $stmt->fetch();
    }
}
