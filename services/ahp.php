<?php
/**
 * Mesin perhitungan AHP (Analytical Hierarchy Process).
 * Logika murni, terpisah dari tampilan, agar mudah diuji/diverifikasi (Bagian 9 PRD).
 *
 * Alur:
 *  1. Normalisasi matriks perbandingan berpasangan (bagi tiap sel dengan jumlah kolom)
 *  2. Bobot prioritas = rata-rata tiap baris matriks ternormalisasi
 *  3. Weighted sum vector = matriks asli x bobot
 *  4. Lambda per baris = weighted_sum / bobot
 *  5. Lambda max = rata-rata lambda
 *  6. CI = (lambda_max - n) / (n - 1)
 *  7. CR = CI / RI (Random Index Saaty)
 *  8. Peringkat alternatif = sintesis bobot kriteria x nilai alternatif ternormalisasi
 *     (normalisasi jumlah: benefit = x/sum(x) per kolom, cost = (1/x)/sum(1/x) per kolom)
 */

const AHP_RANDOM_INDEX = [
    1 => 0.00, 2 => 0.00, 3 => 0.58, 4 => 0.90, 5 => 1.12,
    6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49,
    11 => 1.51, 12 => 1.48, 13 => 1.56, 14 => 1.57, 15 => 1.59,
];

function ahpRandomIndex(int $n): float
{
    if ($n <= 2) {
        return 0.0;
    }
    return AHP_RANDOM_INDEX[$n] ?? 1.59;
}

/**
 * @param array $criteriaIds  daftar id kriteria, urutan menentukan indeks matriks
 * @param array $matrix       [row_id][col_id] = nilai (hasil PairwiseMatrix::getMatrix)
 * @return array langkah lengkap + hasil akhir
 */
function ahpCalculateWeights(array $criteriaIds, array $matrix): array
{
    $n = count($criteriaIds);

    // Matriks asli sebagai array numerik n x n untuk memudahkan perhitungan
    $A = [];
    foreach ($criteriaIds as $i => $rowId) {
        foreach ($criteriaIds as $j => $colId) {
            $A[$i][$j] = $matrix[$rowId][$colId] ?? ($i === $j ? 1.0 : 1.0);
        }
    }

    // 1. Jumlah tiap kolom
    $colSums = array_fill(0, $n, 0.0);
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $colSums[$j] += $A[$i][$j];
        }
    }

    // 2. Matriks ternormalisasi
    $N = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $N[$i][$j] = $colSums[$j] != 0 ? $A[$i][$j] / $colSums[$j] : 0.0;
        }
    }

    // 3. Bobot prioritas = rata-rata baris matriks ternormalisasi
    $weights = [];
    for ($i = 0; $i < $n; $i++) {
        $weights[$i] = array_sum($N[$i]) / $n;
    }

    // 4. Weighted sum vector = A * weights
    $weightedSum = [];
    for ($i = 0; $i < $n; $i++) {
        $sum = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $sum += $A[$i][$j] * $weights[$j];
        }
        $weightedSum[$i] = $sum;
    }

    // 5. Lambda per baris & lambda max
    $lambdaVector = [];
    for ($i = 0; $i < $n; $i++) {
        $lambdaVector[$i] = $weights[$i] != 0 ? $weightedSum[$i] / $weights[$i] : 0.0;
    }
    $lambdaMax = array_sum($lambdaVector) / $n;

    // 6. Consistency Index & 7. Consistency Ratio
    $ci = $n > 1 ? ($lambdaMax - $n) / ($n - 1) : 0.0;
    $ri = ahpRandomIndex($n);
    $cr = $ri != 0 ? $ci / $ri : 0.0;

    return [
        'criteria_ids' => $criteriaIds,
        'n'            => $n,
        'matrix'       => $A,
        'col_sums'     => $colSums,
        'normalized'   => $N,
        'weights'      => $weights,
        'weighted_sum' => $weightedSum,
        'lambda_vector'=> $lambdaVector,
        'lambda_max'   => $lambdaMax,
        'ci'           => $ci,
        'ri'           => $ri,
        'cr'           => $cr,
        'consistent'   => $cr <= CR_THRESHOLD,
    ];
}

/**
 * Peringkat alternatif metode AHP: sintesis bobot kriteria dengan nilai
 * alternatif yang dinormalisasi dengan normalisasi jumlah (khas AHP),
 * berbeda dari normalisasi vektor yang dipakai TOPSIS - agar kedua metode
 * benar-benar independen untuk keperluan perbandingan (Bagian 1 & FR-07 PRD).
 *
 * @param array $cranes    daftar crane (id => data)
 * @param array $criteria  daftar kriteria aktif (id => data, berisi 'tipe')
 * @param array $scoreMatrix [crane_id][criterion_id] = nilai mentah
 * @param array $weights   [criterion_id] = bobot AHP
 */
function ahpRankAlternatives(array $cranes, array $criteria, array $scoreMatrix, array $weights): array
{
    // Jumlah kolom per kriteria (untuk normalisasi benefit) dan jumlah 1/x (untuk cost)
    $colSum = [];
    $colSumInverse = [];
    foreach ($criteria as $crit) {
        $cid = $crit['id'];
        $sum = 0.0;
        $sumInv = 0.0;
        foreach ($cranes as $crane) {
            $x = $scoreMatrix[$crane['id']][$cid] ?? 0.0;
            $sum += $x;
            $sumInv += $x != 0 ? 1 / $x : 0.0;
        }
        $colSum[$cid] = $sum;
        $colSumInverse[$cid] = $sumInv;
    }

    $normalized = [];
    $scores = [];
    foreach ($cranes as $crane) {
        $total = 0.0;
        foreach ($criteria as $crit) {
            $cid = $crit['id'];
            $x = $scoreMatrix[$crane['id']][$cid] ?? 0.0;
            if ($crit['tipe'] === 'benefit') {
                $norm = $colSum[$cid] != 0 ? $x / $colSum[$cid] : 0.0;
            } else {
                $invX = $x != 0 ? 1 / $x : 0.0;
                $norm = $colSumInverse[$cid] != 0 ? $invX / $colSumInverse[$cid] : 0.0;
            }
            $normalized[$crane['id']][$cid] = $norm;
            $total += $norm * ($weights[$cid] ?? 0);
        }
        $scores[$crane['id']] = $total;
    }

    arsort($scores);
    $ranking = [];
    $rank = 1;
    foreach ($scores as $craneId => $skor) {
        $ranking[] = ['crane_id' => $craneId, 'skor' => $skor, 'peringkat' => $rank++];
    }

    return [
        'col_sum'          => $colSum,
        'col_sum_inverse'  => $colSumInverse,
        'normalized'       => $normalized,
        'scores'           => $scores,
        'ranking'          => $ranking,
    ];
}
