<?php
/**
 * Mesin perhitungan TOPSIS (Technique for Order Preference by Similarity
 * to Ideal Solution). Bobot kriteria diambil dari hasil AHP (FR-06, sesuai
 * keputusan pada Bagian 13 PRD agar kedua metode setara).
 *
 * Alur:
 *  1. Matriks keputusan (nilai mentah alternatif x kriteria)
 *  2. Normalisasi vektor: r_ij = x_ij / sqrt(sum_i x_ij^2)
 *  3. Matriks ternormalisasi terbobot: v_ij = w_j * r_ij
 *  4. Solusi ideal positif (A+) & negatif (A-) sesuai tipe kriteria
 *  5. Jarak D+ dan D- (Euclidean distance)
 *  6. Nilai preferensi V = D- / (D- + D+)
 *  7. Peringkat berdasarkan V (semakin besar semakin baik)
 *
 * @param array $cranes      daftar crane
 * @param array $criteria    daftar kriteria aktif (berisi id & tipe)
 * @param array $scoreMatrix [crane_id][criterion_id] = nilai mentah
 * @param array $weights     [criterion_id] = bobot AHP
 */
function topsisCalculate(array $cranes, array $criteria, array $scoreMatrix, array $weights): array
{
    // 1. Matriks keputusan X
    $X = [];
    foreach ($cranes as $crane) {
        foreach ($criteria as $crit) {
            $X[$crane['id']][$crit['id']] = $scoreMatrix[$crane['id']][$crit['id']] ?? 0.0;
        }
    }

    // Akar jumlah kuadrat per kolom kriteria
    $denom = [];
    foreach ($criteria as $crit) {
        $cid = $crit['id'];
        $sumSq = 0.0;
        foreach ($cranes as $crane) {
            $sumSq += $X[$crane['id']][$cid] ** 2;
        }
        $denom[$cid] = sqrt($sumSq);
    }

    // 2. Normalisasi vektor R
    $R = [];
    foreach ($cranes as $crane) {
        foreach ($criteria as $crit) {
            $cid = $crit['id'];
            $R[$crane['id']][$cid] = $denom[$cid] != 0 ? $X[$crane['id']][$cid] / $denom[$cid] : 0.0;
        }
    }

    // 3. Matriks ternormalisasi terbobot V
    $V = [];
    foreach ($cranes as $crane) {
        foreach ($criteria as $crit) {
            $cid = $crit['id'];
            $V[$crane['id']][$cid] = $R[$crane['id']][$cid] * ($weights[$cid] ?? 0.0);
        }
    }

    // 4. Solusi ideal positif (A+) dan negatif (A-)
    $idealPos = [];
    $idealNeg = [];
    foreach ($criteria as $crit) {
        $cid = $crit['id'];
        $col = array_map(fn($crane) => $V[$crane['id']][$cid], $cranes);
        if ($crit['tipe'] === 'benefit') {
            $idealPos[$cid] = max($col);
            $idealNeg[$cid] = min($col);
        } else {
            $idealPos[$cid] = min($col);
            $idealNeg[$cid] = max($col);
        }
    }

    // 5. Jarak D+ dan D-
    $dPlus = [];
    $dMinus = [];
    foreach ($cranes as $crane) {
        $sumPos = 0.0;
        $sumNeg = 0.0;
        foreach ($criteria as $crit) {
            $cid = $crit['id'];
            $sumPos += ($V[$crane['id']][$cid] - $idealPos[$cid]) ** 2;
            $sumNeg += ($V[$crane['id']][$cid] - $idealNeg[$cid]) ** 2;
        }
        $dPlus[$crane['id']] = sqrt($sumPos);
        $dMinus[$crane['id']] = sqrt($sumNeg);
    }

    // 6. Nilai preferensi V_i
    $preference = [];
    foreach ($cranes as $crane) {
        $id = $crane['id'];
        $total = $dPlus[$id] + $dMinus[$id];
        $preference[$id] = $total != 0 ? $dMinus[$id] / $total : 0.0;
    }

    // 7. Peringkat (descending)
    $sorted = $preference;
    arsort($sorted);
    $ranking = [];
    $rank = 1;
    foreach ($sorted as $craneId => $pref) {
        $ranking[] = [
            'crane_id'   => $craneId,
            'd_plus'     => $dPlus[$craneId],
            'd_minus'    => $dMinus[$craneId],
            'preferensi' => $pref,
            'peringkat'  => $rank++,
        ];
    }

    return [
        'decision_matrix'   => $X,
        'denom'             => $denom,
        'normalized'        => $R,
        'weighted'          => $V,
        'ideal_positive'    => $idealPos,
        'ideal_negative'    => $idealNeg,
        'd_plus'            => $dPlus,
        'd_minus'           => $dMinus,
        'preference'        => $preference,
        'ranking'           => $ranking,
    ];
}
