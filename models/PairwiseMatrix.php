<?php
require_once __DIR__ . '/../config/koneksi.php';

class PairwiseMatrix
{
    /** Simpan seluruh sel segitiga atas; sel bawah otomatis dihitung sebagai 1/x. */
    public static function saveMatrix(int $sessionId, array $criteriaIds, array $values): void
    {
        $db = getDb();
        $db->beginTransaction();
        try {
            $del = $db->prepare('DELETE FROM pairwise_matrix WHERE session_id = ?');
            $del->execute([$sessionId]);

            $ins = $db->prepare(
                'INSERT INTO pairwise_matrix (session_id, criterion_row, criterion_col, nilai) VALUES (?, ?, ?, ?)'
            );
            $n = count($criteriaIds);
            for ($i = 0; $i < $n; $i++) {
                for ($j = 0; $j < $n; $j++) {
                    $rowId = $criteriaIds[$i];
                    $colId = $criteriaIds[$j];
                    if ($i === $j) {
                        $val = 1.0;
                    } elseif ($i < $j) {
                        $key = $rowId . '_' . $colId;
                        $val = isset($values[$key]) ? (float) $values[$key] : 1.0;
                    } else {
                        $key = $colId . '_' . $rowId;
                        $inv = isset($values[$key]) ? (float) $values[$key] : 1.0;
                        $val = $inv != 0 ? 1.0 / $inv : 0.0;
                    }
                    $ins->execute([$sessionId, $rowId, $colId, $val]);
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Ambil matriks sebagai array asosiatif [criterion_id][criterion_id] = nilai */
    public static function getMatrix(int $sessionId): array
    {
        $stmt = getDb()->prepare('SELECT criterion_row, criterion_col, nilai FROM pairwise_matrix WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $matrix = [];
        foreach ($stmt->fetchAll() as $r) {
            $matrix[(int) $r['criterion_row']][(int) $r['criterion_col']] = (float) $r['nilai'];
        }
        return $matrix;
    }
}
