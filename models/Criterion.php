<?php
require_once __DIR__ . '/../config/koneksi.php';

class Criterion
{
    public static function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM criteria';
        if ($onlyActive) {
            $sql .= ' WHERE aktif = 1';
        }
        $sql .= ' ORDER BY urutan, kode';
        return getDb()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = getDb()->prepare('SELECT * FROM criteria WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function countActive(): int
    {
        return (int) getDb()->query('SELECT COUNT(*) c FROM criteria WHERE aktif = 1')->fetch()['c'];
    }

    public static function create(array $d): int
    {
        $maxUrutan = (int) getDb()->query('SELECT COALESCE(MAX(urutan),0) m FROM criteria')->fetch()['m'];
        $stmt = getDb()->prepare(
            'INSERT INTO criteria (kode, nama, tipe, satuan, keterangan, aktif, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['kode'], $d['nama'], $d['tipe'], $d['satuan'], $d['keterangan'], $d['aktif'], $maxUrutan + 1,
        ]);
        return (int) getDb()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = getDb()->prepare(
            'UPDATE criteria SET kode=?, nama=?, tipe=?, satuan=?, keterangan=?, aktif=? WHERE id=?'
        );
        $stmt->execute([$d['kode'], $d['nama'], $d['tipe'], $d['satuan'], $d['keterangan'], $d['aktif'], $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = getDb()->prepare('DELETE FROM criteria WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function kodeExists(string $kode, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = getDb()->prepare('SELECT id FROM criteria WHERE kode = ? AND id != ?');
            $stmt->execute([$kode, $excludeId]);
        } else {
            $stmt = getDb()->prepare('SELECT id FROM criteria WHERE kode = ?');
            $stmt->execute([$kode]);
        }
        return (bool) $stmt->fetch();
    }
}
