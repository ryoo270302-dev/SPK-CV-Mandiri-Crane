<?php
/**
 * Fungsi bersama untuk skrip seed: konversi spesifikasi crane ke skor 1-5
 * memakai rentang Bagian E1 kuisioner (dikonfirmasi responden Basuki).
 */

const TAHUN_ACUAN = 2026; // tahun pengisian kuisioner; usia alat = TAHUN_ACUAN - tahun

/** Batas atas skor 1..4; skor 5 = di atas batas terakhir. */
function rentangSkor(): array
{
    return [
        'C1' => [20, 30, 45, 60],
        'C2' => [2000000, 2500000, 3000000, 4000000],
        'C3' => [20, 25, 30, 35],
        'C4' => [2, 4, 6, 8],
    ];
}

function konversiSkor(float $nilai, array $batas): int
{
    foreach ($batas as $i => $b) {
        if ($nilai <= $b) {
            return $i + 1;
        }
    }
    return 5;
}

/** Skor C1-C4 dari spesifikasi: ton, harga (Rp/hari), jangkauan (m), tahun unit. */
function skorKuantitatif(float $ton, float $harga, float $jangkauan, int $tahun): array
{
    $r = rentangSkor();
    return [
        'C1' => konversiSkor($ton, $r['C1']),
        'C2' => konversiSkor($harga, $r['C2']),
        'C3' => konversiSkor($jangkauan, $r['C3']),
        'C4' => konversiSkor(TAHUN_ACUAN - $tahun, $r['C4']),
    ];
}
