<?php
require_once __DIR__ . '/../config/koneksi.php';

class Score
{
    /** Ambil matriks skor: [crane_id][criterion_id] = nilai */
    public static function matrix(): array
    {
        $rows = getDb()->query('SELECT crane_id, criterion_id, nilai FROM scores')->fetchAll();
        $matrix = [];
        foreach ($rows as $r) {
            $matrix[(int) $r['crane_id']][(int) $r['criterion_id']] = (float) $r['nilai'];
        }
        return $matrix;
    }

    public static function save(int $craneId, int $criterionId, float $nilai): void
    {
        $stmt = getDb()->prepare(
            'INSERT INTO scores (crane_id, criterion_id, nilai) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)'
        );
        $stmt->execute([$craneId, $criterionId, $nilai]);
    }

    /**
     * Cek apakah semua crane sudah punya nilai untuk semua kriteria aktif.
     * Mengembalikan array kekurangan, kosong jika lengkap (FR-04).
     */
    public static function findMissing(array $cranes, array $criteria): array
    {
        $matrix = self::matrix();
        $missing = [];
        foreach ($cranes as $crane) {
            foreach ($criteria as $crit) {
                if (!isset($matrix[$crane['id']][$crit['id']])) {
                    $missing[] = $crane['nama'] . ' - ' . $crit['nama'];
                }
            }
        }
        return $missing;
    }
}
