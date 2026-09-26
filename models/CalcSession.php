<?php
require_once __DIR__ . '/../config/koneksi.php';

class CalcSession
{
    public static function create(string $nama, ?int $userId): int
    {
        $stmt = getDb()->prepare('INSERT INTO calc_sessions (nama_sesi, status, created_by) VALUES (?, ?, ?)');
        $stmt->execute([$nama, 'draft', $userId]);
        return (int) getDb()->lastInsertId();
    }

    public static function markSelesai(int $id): void
    {
        $stmt = getDb()->prepare("UPDATE calc_sessions SET status = 'selesai' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = getDb()->prepare('SELECT * FROM calc_sessions WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        return getDb()->query(
            'SELECT cs.*, u.nama AS pembuat FROM calc_sessions cs
             LEFT JOIN users u ON u.id = cs.created_by
             ORDER BY cs.tanggal DESC'
        )->fetchAll();
    }

    public static function latestSelesai(): ?array
    {
        $row = getDb()->query(
            "SELECT * FROM calc_sessions WHERE status = 'selesai' ORDER BY tanggal DESC LIMIT 1"
        )->fetch();
        return $row ?: null;
    }

    public static function delete(int $id): void
    {
        $stmt = getDb()->prepare('DELETE FROM calc_sessions WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function count(): int
    {
        return (int) getDb()->query('SELECT COUNT(*) c FROM calc_sessions')->fetch()['c'];
    }
}

class AhpResult
{
    public static function saveAll(int $sessionId, array $rows): void
    {
        $db = getDb();
        $del = $db->prepare('DELETE FROM ahp_results WHERE session_id = ?');
        $del->execute([$sessionId]);
        $ins = $db->prepare(
            'INSERT INTO ahp_results (session_id, criterion_id, bobot, lambda_max, ci, cr) VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $r) {
            $ins->execute([$sessionId, $r['criterion_id'], $r['bobot'], $r['lambda_max'], $r['ci'], $r['cr']]);
        }
    }

    public static function forSession(int $sessionId): array
    {
        $stmt = getDb()->prepare(
            'SELECT ar.*, c.kode, c.nama, c.tipe FROM ahp_results ar
             JOIN criteria c ON c.id = ar.criterion_id
             WHERE ar.session_id = ? ORDER BY c.urutan'
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }
}

class AhpRanking
{
    public static function saveAll(int $sessionId, array $rows): void
    {
        $db = getDb();
        $del = $db->prepare('DELETE FROM ahp_ranking WHERE session_id = ?');
        $del->execute([$sessionId]);
        $ins = $db->prepare('INSERT INTO ahp_ranking (session_id, crane_id, skor, peringkat) VALUES (?, ?, ?, ?)');
        foreach ($rows as $r) {
            $ins->execute([$sessionId, $r['crane_id'], $r['skor'], $r['peringkat']]);
        }
    }

    public static function forSession(int $sessionId): array
    {
        $stmt = getDb()->prepare(
            'SELECT ar.*, c.kode, c.nama FROM ahp_ranking ar
             JOIN cranes c ON c.id = ar.crane_id
             WHERE ar.session_id = ? ORDER BY ar.peringkat'
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }
}

class TopsisResult
{
    public static function saveAll(int $sessionId, array $rows): void
    {
        $db = getDb();
        $del = $db->prepare('DELETE FROM topsis_results WHERE session_id = ?');
        $del->execute([$sessionId]);
        $ins = $db->prepare(
            'INSERT INTO topsis_results (session_id, crane_id, d_plus, d_minus, preferensi, peringkat) VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $r) {
            $ins->execute([$sessionId, $r['crane_id'], $r['d_plus'], $r['d_minus'], $r['preferensi'], $r['peringkat']]);
        }
    }

    public static function forSession(int $sessionId): array
    {
        $stmt = getDb()->prepare(
            'SELECT tr.*, c.kode, c.nama FROM topsis_results tr
             JOIN cranes c ON c.id = tr.crane_id
             WHERE tr.session_id = ? ORDER BY tr.peringkat'
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }
}

class Comparison
{
    public static function saveAll(int $sessionId, array $rows): void
    {
        $db = getDb();
        $del = $db->prepare('DELETE FROM comparisons WHERE session_id = ?');
        $del->execute([$sessionId]);
        $ins = $db->prepare(
            'INSERT INTO comparisons (session_id, crane_id, peringkat_ahp, peringkat_topsis, selisih) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($rows as $r) {
            $ins->execute([$sessionId, $r['crane_id'], $r['peringkat_ahp'], $r['peringkat_topsis'], $r['selisih']]);
        }
    }

    public static function forSession(int $sessionId): array
    {
        $stmt = getDb()->prepare(
            'SELECT cm.*, c.kode, c.nama FROM comparisons cm
             JOIN cranes c ON c.id = cm.crane_id
             WHERE cm.session_id = ? ORDER BY cm.peringkat_topsis'
        );
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }
}
