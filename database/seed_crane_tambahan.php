<?php
/**
 * Seed 27 unit crane tambahan dari file data_manajemen_crane.html.
 *
 * Kode di file sumber (CR-01..CR-27) bentrok dengan 5 unit hasil kuisioner
 * (CR-01..CR-05) yang spesifikasinya berbeda. Unit kuisioner dipertahankan,
 * sehingga unit dari file ini diberi kode baru: nomor sumber + 5
 * (CR-01 -> CR-06, ..., CR-27 -> CR-32).
 *
 * Skor yang dibuat per unit:
 *   C1-C4 : dikonversi dari spesifikasi memakai rentang E1 (seed_helpers.php)
 *   C5-C7 : ESTIMASI, karena tidak ada datanya di sumber. Aturan:
 *           C5 Kondisi      : tahun >= 2020 -> 5, selain itu -> 4 (pola unit kuisioner)
 *           C6 Mobilitas    : berdasarkan jenis dan kapasitas (lihat skorMobilitas)
 *           C7 Ketersediaan : Tersedia -> 5, Terbatas -> 3, Sangat Terbatas -> 2
 *   Ganti estimasi dengan penilaian responden lewat menu Penilaian bila ada.
 *   Menjalankan ulang skrip ini akan menimpa skor C1-C7 unit tambahan.
 *
 * Status: kolom status di basis data hanya 'tersedia'/'tidak_tersedia'. Semua
 * unit dipetakan ke 'tersedia'; status asli (Terbatas/Sangat Terbatas) dicatat
 * di keterangan dan tercermin pada skor C7.
 *
 * Cara pakai (setelah spk_crane.sql di-import):
 *   php database/seed_crane_tambahan.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Skrip ini hanya boleh dijalankan lewat command line.');
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../models/Crane.php';
require_once __DIR__ . '/../models/Criterion.php';
require_once __DIR__ . '/../models/Score.php';
require_once __DIR__ . '/seed_helpers.php';

const OFFSET_KODE = 5;

// [nomor di file sumber, nama, jenis, kapasitas ton, tahun, harga sewa, jangkauan m, status asli]
$data = [
    [1,  'Truck Crane 12 Ton',          'Truck Crane',         12.00, 2021, 5500000,   28.10, 'Tersedia'],
    [2,  'Truck Crane 25 Ton',          'Truck Crane',         25.00, 2020, 7500000,   41.00, 'Tersedia'],
    [3,  'Truck Crane 50 Ton',          'Truck Crane',         50.00, 2020, 11500000,  44.50, 'Tersedia'],
    [4,  'Truck Crane 80 Ton',          'Truck Crane',         80.00, 2019, 18000000,  50.50, 'Terbatas'],
    [5,  'Truck Crane 120 Ton',         'Truck Crane',        120.00, 2019, 28000000,  68.20, 'Terbatas'],
    [6,  'Truck Crane 160 Ton',         'Truck Crane',        160.00, 2018, 38000000,  75.50, 'Sangat Terbatas'],
    [7,  'Crawler Crane 30 Ton',        'Crawler Crane',       30.00, 2021, 6500000,   42.50, 'Tersedia'],
    [8,  'Crawler Crane 50 Ton',        'Crawler Crane',       50.00, 2020, 10000000,  44.00, 'Tersedia'],
    [9,  'Crawler Crane 100 Ton',       'Crawler Crane',      100.00, 2019, 20000000,  57.00, 'Terbatas'],
    [10, 'Crawler Crane 150 Ton',       'Crawler Crane',      150.00, 2019, 30000000,  59.90, 'Terbatas'],
    [11, 'Crawler Crane 200 Ton',       'Crawler Crane',      200.00, 2018, 42000000,  85.00, 'Sangat Terbatas'],
    [12, 'Crawler Crane 300 Ton',       'Crawler Crane',      300.00, 2018, 65000000,  87.00, 'Sangat Terbatas'],
    [13, 'All Terrain Crane 60 Ton',    'All Terrain Crane',   60.00, 2021, 16000000,  50.00, 'Tersedia'],
    [14, 'All Terrain Crane 100 Ton',   'All Terrain Crane',  100.00, 2020, 26000000,  63.00, 'Terbatas'],
    [15, 'All Terrain Crane 150 Ton',   'All Terrain Crane',  150.00, 2020, 38000000,  74.80, 'Terbatas'],
    [16, 'All Terrain Crane 200 Ton',   'All Terrain Crane',  200.00, 2019, 50000000,  68.00, 'Sangat Terbatas'],
    [17, 'All Terrain Crane 250 Ton',   'All Terrain Crane',  250.00, 2019, 62000000,  70.00, 'Sangat Terbatas'],
    [18, 'All Terrain Crane 300 Ton',   'All Terrain Crane',  300.00, 2018, 78000000,  80.00, 'Sangat Terbatas'],
    [19, 'Loader Crane 3 Ton',          'Loader Crane',         3.30, 2021, 2500000,    9.60, 'Tersedia'],
    [20, 'Loader Crane 5 Ton',          'Loader Crane',         5.60, 2021, 3800000,   11.90, 'Tersedia'],
    [21, 'Loader Crane 6.5 Ton',        'Loader Crane',         6.79, 2020, 4500000,   12.40, 'Tersedia'],
    [22, 'Rough Terrain Crane 13 Ton',  'Rough Terrain Crane', 13.00, 2021, 6000000,   16.50, 'Tersedia'],
    [23, 'Rough Terrain Crane 30 Ton',  'Rough Terrain Crane', 30.00, 2020, 7500000,   35.00, 'Tersedia'],
    [24, 'Rough Terrain Crane 50 Ton',  'Rough Terrain Crane', 50.00, 2020, 10500000,  45.00, 'Tersedia'],
    [25, 'Rough Terrain Crane 65 Ton',  'Rough Terrain Crane', 65.00, 2019, 13000000,  45.50, 'Terbatas'],
    [26, 'Rough Terrain Crane 80 Ton',  'Rough Terrain Crane', 80.00, 2019, 16000000,  47.50, 'Terbatas'],
    [27, 'Rough Terrain Crane 120 Ton', 'Rough Terrain Crane',120.00, 2018, 24000000,  80.00, 'Sangat Terbatas'],
];

function skorKondisi(int $tahun): int
{
    return $tahun >= 2020 ? 5 : 4;
}

function skorMobilitas(string $jenis, float $ton): int
{
    switch ($jenis) {
        case 'Loader Crane':        return 5;                                  // terpasang di truk, berpindah sendiri
        case 'Crawler Crane':       return $ton <= 100 ? 2 : 1;                // wajib lowbed / dibongkar
        case 'Truck Crane':         return $ton <= 30 ? 5 : ($ton < 120 ? 4 : 3);
        case 'All Terrain Crane':   return $ton < 200 ? 4 : 3;
        case 'Rough Terrain Crane': return $ton < 100 ? 4 : 3;
        default:                    return 3;
    }
}

function skorKetersediaan(string $status): int
{
    return ['Tersedia' => 5, 'Terbatas' => 3, 'Sangat Terbatas' => 2][$status] ?? 3;
}

$critByKode = [];
foreach (Criterion::all(true) as $c) {
    $critByKode[$c['kode']] = (int) $c['id'];
}
foreach (['C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7'] as $k) {
    if (!isset($critByKode[$k])) {
        fwrite(STDERR, "Kriteria $k tidak ditemukan/aktif. Import database/spk_crane.sql dulu.\n");
        exit(1);
    }
}

$existing = [];
foreach (Crane::all() as $c) {
    $existing[$c['kode']] = $c;
}

$db = getDb();
$db->beginTransaction();
try {
    echo "Kode   Nama                          C1 C2 C3 C4 C5 C6 C7\n";
    foreach ($data as [$no, $nama, $jenis, $ton, $tahun, $harga, $jangkauan, $statusAsli]) {
        $kode = sprintf('CR-%02d', $no + OFFSET_KODE);
        $ket = "Unit tambahan (sumber: data_manajemen_crane.html, kode asli CR-" . sprintf('%02d', $no) . "). "
             . "Status asli: $statusAsli. Skor C5-C7 adalah estimasi, bukan penilaian responden.";
        $row = [
            'kode' => $kode, 'nama' => $nama, 'jenis' => $jenis, 'kapasitas_ton' => $ton, 'tahun' => $tahun,
            'harga_sewa' => $harga, 'jangkauan_m' => $jangkauan, 'keterangan' => $ket, 'status' => 'tersedia',
        ];
        if (isset($existing[$kode])) {
            $id = (int) $existing[$kode]['id'];
            Crane::update($id, $row);
        } else {
            $id = Crane::create($row);
        }

        $skor = skorKuantitatif($ton, $harga, $jangkauan, $tahun) + [
            'C5' => skorKondisi($tahun),
            'C6' => skorMobilitas($jenis, $ton),
            'C7' => skorKetersediaan($statusAsli),
        ];
        foreach ($skor as $kk => $nilai) {
            Score::save($id, $critByKode[$kk], (float) $nilai);
        }
        echo sprintf("%-6s %-29s %s\n", $kode, $nama, implode('  ', $skor));
    }
    $db->commit();
    echo "\nSelesai: " . count($data) . " unit tambahan. Total crane sekarang: " . Crane::count() . "\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Gagal: ' . $e->getMessage() . "\n");
    exit(1);
}
