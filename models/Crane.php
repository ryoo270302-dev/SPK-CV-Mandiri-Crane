<?php
require_once __DIR__ . '/../config/koneksi.php';

class Crane
{
    public static function all(string $search = '', string $sortBy = 'kode', string $sortDir = 'asc'): array
    {
        $allowedSort = ['kode', 'nama', 'jenis', 'kapasitas_ton', 'tahun', 'harga_sewa', 'jangkauan_m', 'status'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'kode';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM cranes WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (kode LIKE ? OR nama LIKE ? OR jenis LIKE ?)";
            $like = "%$search%";
            $params = [$like, $like, $like];
        }
        $sql .= " ORDER BY $sortBy $sortDir";

        $stmt = getDb()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = getDb()->prepare('SELECT * FROM cranes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function count(): int
    {
        return (int) getDb()->query('SELECT COUNT(*) c FROM cranes')->fetch()['c'];
    }

    public static function create(array $d): int
    {
        $stmt = getDb()->prepare(
            'INSERT INTO cranes (kode, nama, jenis, kapasitas_ton, tahun, harga_sewa, jangkauan_m, keterangan, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['kode'], $d['nama'], $d['jenis'], $d['kapasitas_ton'], $d['tahun'],
            $d['harga_sewa'], $d['jangkauan_m'], $d['keterangan'], $d['status'],
        ]);
        return (int) getDb()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = getDb()->prepare(
            'UPDATE cranes SET kode=?, nama=?, jenis=?, kapasitas_ton=?, tahun=?, harga_sewa=?, jangkauan_m=?, keterangan=?, status=?
             WHERE id=?'
        );
        $stmt->execute([
            $d['kode'], $d['nama'], $d['jenis'], $d['kapasitas_ton'], $d['tahun'],
            $d['harga_sewa'], $d['jangkauan_m'], $d['keterangan'], $d['status'], $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = getDb()->prepare('DELETE FROM cranes WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function kodeExists(string $kode, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = getDb()->prepare('SELECT id FROM cranes WHERE kode = ? AND id != ?');
            $stmt->execute([$kode, $excludeId]);
        } else {
            $stmt = getDb()->prepare('SELECT id FROM cranes WHERE kode = ?');
            $stmt->execute([$kode]);
        }
        return (bool) $stmt->fetch();
    }
}
