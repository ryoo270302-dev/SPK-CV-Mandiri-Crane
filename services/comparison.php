<?php
/**
 * Perbandingan hasil AHP vs TOPSIS (FR-07):
 *  - peringkat berdampingan + selisih peringkat tiap alternatif
 *  - korelasi Spearman sebagai indikator tingkat kesesuaian peringkat
 *
 * rho = 1 - (6 * sum(d_i^2)) / (n * (n^2 - 1))
 * d_i = selisih peringkat AHP dan TOPSIS untuk alternatif ke-i
 */
function buildComparison(array $ahpRanking, array $topsisRanking): array
{
    $ahpRankByCrane = [];
    foreach ($ahpRanking as $r) {
        $ahpRankByCrane[$r['crane_id']] = $r['peringkat'];
    }
    $topsisRankByCrane = [];
    foreach ($topsisRanking as $r) {
        $topsisRankByCrane[$r['crane_id']] = $r['peringkat'];
    }

    $rows = [];
    $sumDsq = 0;
    $n = count($topsisRankByCrane);
    foreach ($topsisRankByCrane as $craneId => $rankTopsis) {
        $rankAhp = $ahpRankByCrane[$craneId] ?? null;
        $selisih = $rankAhp !== null ? $rankAhp - $rankTopsis : null;
        if ($selisih !== null) {
            $sumDsq += $selisih ** 2;
        }
        $rows[] = [
            'crane_id'         => $craneId,
            'peringkat_ahp'    => $rankAhp,
            'peringkat_topsis' => $rankTopsis,
            'selisih'          => $selisih,
        ];
    }

    usort($rows, fn($a, $b) => $a['peringkat_topsis'] <=> $b['peringkat_topsis']);

    $spearman = ($n > 1) ? 1 - (6 * $sumDsq) / ($n * ($n ** 2 - 1)) : 1.0;

    $bestAhpCraneId = null;
    foreach ($ahpRanking as $r) {
        if ($r['peringkat'] === 1) {
            $bestAhpCraneId = $r['crane_id'];
            break;
        }
    }
    $bestTopsisCraneId = null;
    foreach ($topsisRanking as $r) {
        if ($r['peringkat'] === 1) {
            $bestTopsisCraneId = $r['crane_id'];
            break;
        }
    }

    return [
        'rows'                 => $rows,
        'spearman'             => $spearman,
        'sum_d_squared'        => $sumDsq,
        'n'                    => $n,
        'best_ahp_crane_id'    => $bestAhpCraneId,
        'best_topsis_crane_id' => $bestTopsisCraneId,
        'same_top_pick'        => $bestAhpCraneId === $bestTopsisCraneId,
    ];
}

/** Interpretasi kekuatan korelasi Spearman (untuk narasi hasil). */
function interpretSpearman(float $rho): string
{
    $abs = abs($rho);
    if ($abs >= 0.9) return 'sangat kuat';
    if ($abs >= 0.7) return 'kuat';
    if ($abs >= 0.5) return 'sedang';
    if ($abs >= 0.3) return 'lemah';
    return 'sangat lemah';
}
