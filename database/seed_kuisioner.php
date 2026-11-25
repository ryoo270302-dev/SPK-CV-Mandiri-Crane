<?php
/**
 * Seed data hasil kuisioner penelitian (responden: Basuki, Kepala Operasional
 * Pool CV Mandiri Crane, diisi 7 November 2026).
 *
 * Sistem memakai 5 kriteria: C1 Kapasitas angkat, C2 Biaya sewa, C3 Jangkauan
 * kerja, C4 Mobilitas, C5 Ketersediaan unit. Kriteria Usia alat dan Kondisi alat
 * pada kuisioner asli (7 kriteria) sudah dikeluarkan dari sistem.
 *
 * Yang dimasukkan ke basis data:
 *   1. Data 5 unit crane (Bagian B)            -> tabel cranes
 *   2. Skor 5 crane x 5 kriteria (Bagian C, E1) -> tabel scores
 *   3. Matriks perbandingan berpasangan (Bagian D) -> sesi perhitungan AHP+TOPSIS
 *
 * Pemetaan dari kuisioner 7 kriteria: C1->C1, C2->C2, C3->C3, C6->C4, C7->C5.
 * Jawaban perbandingan yang melibatkan C4 (usia) dan C5 (kondisi) tidak dipakai.
 *
 * Cara pakai (setelah database/spk_crane.sql di-import):
 *   php database/seed_kuisioner.php
 * Di XAMPP Windows:
 *   C:\xampp\php\php.exe database\seed_kuisioner.php
 *
 * Skrip ini idempoten: boleh dijalankan berulang kali. Sesi bernama sama
 * akan diganti, bukan digandakan.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Skrip ini hanya boleh dijalankan lewat command line.');
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../models/Crane.php';
require_once __DIR__ . '/../models/Criterion.php';
require_once __DIR__ . '/../models/Score.php';
require_once __DIR__ . '/../models/PairwiseMatrix.php';
require_once __DIR__ . '/../models/CalcSession.php';
require_once __DIR__ . '/../services/ahp.php';
require_once __DIR__ . '/../services/topsis.php';
require_once __DIR__ . '/../services/comparison.php';

require_once __DIR__ . '/seed_helpers.php';

const NAMA_SESI = 'Kuisioner Basuki - 7 November 2026';

// ---------------------------------------------------------------------
// Bagian B + keterangan Bagian C
// ---------------------------------------------------------------------
// Catatan: Bagian B kuisioner menulis CR-02 "SANY 25T" dan CR-04 "SANY 35T",
// sedangkan Bagian C menulis "Tadano 25T" dan "Zoomlion 35T" (sama dengan
// data awal aplikasi). Nama yang sudah ada di basis data TIDAK diubah; nama di
// bawah hanya dipakai bila unit belum ada. Konfirmasikan nama final ke
// responden, lalu ubah lewat menu Data Crane.
$cranes = [
    'CR-01' => ['nama' => 'Crane Kobelco 50T',  'jenis' => 'Crawler Crane',       'ton' => 50, 'tahun' => 2018, 'harga' => 3500000, 'jangkauan' => 32,
                'ket' => 'Mesin dan hidrolik sehat. Crawler, wajib diangkut trailer lowbed. Selesai proyek dan kembali ke pool dalam 3 hari.'],
    'CR-02' => ['nama' => 'Crane Tadano 25T',   'jenis' => 'Truck Crane',         'ton' => 25, 'tahun' => 2020, 'harga' => 2500000, 'jangkauan' => 28,
                'ket' => 'Kondisi sangat prima, baru selesai servis rutin. Mudah berpindah sendiri via jalan raya. Standby di pool.'],
    'CR-03' => ['nama' => 'Crane XCMG 80T',     'jenis' => 'Crawler Crane',       'ton' => 80, 'tahun' => 2016, 'harga' => 5000000, 'jangkauan' => 40,
                'ket' => 'Ada perbaikan ringan seal hidrolik di bengkel. Mobilitas sangat sulit, boom dibongkar saat pindah. Tersedia sekitar 10 hari lagi.'],
    'CR-04' => ['nama' => 'Crane Zoomlion 35T', 'jenis' => 'Truck Crane',         'ton' => 35, 'tahun' => 2019, 'harga' => 2800000, 'jangkauan' => 30,
                'ket' => 'Mesin dan winch baik. Mudah dipindahkan lewat jalan raya, hanya butuh persiapan outrigger. Standby di pool.'],
    'CR-05' => ['nama' => 'Crane Kato 20T',     'jenis' => 'Rough Terrain Crane', 'ton' => 20, 'tahun' => 2021, 'harga' => 2200000, 'jangkauan' => 24,
                'ket' => 'Unit paling muda, sangat terawat. Lincah di medan belum diaspal; jarak jauh antar-kota perlu self-loader. Tersedia dalam 2 hari.'],
];

// ---------------------------------------------------------------------
// Bagian C: skor subjektif C4 Mobilitas dan C5 Ketersediaan
// (di kuisioner asli bernomor C6 dan C7)
// ---------------------------------------------------------------------
$skorSubjektif = [
    'CR-01' => ['C4' => 2, 'C5' => 4],
    'CR-02' => ['C4' => 5, 'C5' => 5],
    'CR-03' => ['C4' => 1, 'C5' => 3],
    'CR-04' => ['C4' => 4, 'C5' => 5],
    'CR-05' => ['C4' => 4, 'C5' => 4],
];

// ---------------------------------------------------------------------
// Bagian D: [kriteria A, kriteria B, lebih penting (A/B/S), nilai]
// 10 pasang dari 21 jawaban asli; hanya pasangan antar C1, C2, C3, C6, C7
// yang dipertahankan, dengan C6 -> C4 dan C7 -> C5.
// ---------------------------------------------------------------------
$perbandingan = [
    ['C1', 'C2', 'A', 3], ['C1', 'C3', 'A', 2], ['C1', 'C4', 'A', 5], ['C1', 'C5', 'A', 4],
    ['C2', 'C3', 'B', 2], ['C2', 'C4', 'A', 2], ['C2', 'C5', 'A', 2],
    ['C3', 'C4', 'A', 3], ['C3', 'C5', 'A', 3],
    ['C4', 'C5', 'B', 2],
];

$db = getDb();

// ---------------------------------------------------------------------
// 1. Kriteria (harus sudah ada dari spk_crane.sql)
// ---------------------------------------------------------------------
$criteria = Criterion::all(true);
$critByKode = [];
foreach ($criteria as $c) {
    $critByKode[$c['kode']] = $c;
}
foreach (['C1', 'C2', 'C3', 'C4', 'C5'] as $k) {
    if (!isset($critByKode[$k])) {
        fwrite(STDERR, "Kriteria $k tidak ditemukan/aktif. Import database/spk_crane.sql dulu.\n");
        exit(1);
    }
}
// Pengaman: basis data lama (7 kriteria) memakai C4 = Usia alat dan C5 = Kondisi alat.
if (stripos($critByKode['C4']['nama'], 'mobilitas') === false || stripos($critByKode['C5']['nama'], 'ketersediaan') === false) {
    fwrite(STDERR, "Kriteria di basis data masih skema lama (C4 = {$critByKode['C4']['nama']}, C5 = {$critByKode['C5']['nama']}).\n"
        . "Import ulang database/spk_crane.sql atau hapus kriteria Usia alat dan Kondisi alat, lalu ubah kode Mobilitas menjadi C4 dan Ketersediaan unit menjadi C5.\n");
    exit(1);
}
if (count($criteria) !== 5) {
    fwrite(STDERR, "Jumlah kriteria aktif harus 5, ditemukan " . count($criteria) . ".\n");
    exit(1);
}

$db->beginTransaction();
try {
    // -----------------------------------------------------------------
    // 2. Crane
    // -----------------------------------------------------------------
    $existing = [];
    foreach (Crane::all() as $c) {
        $existing[$c['kode']] = $c;
    }
    $craneId = [];
    foreach ($cranes as $kode => $d) {
        $row = [
            'kode' => $kode,
            'nama' => $existing[$kode]['nama'] ?? $d['nama'], // nama yang sudah ada dipertahankan
            'jenis' => $d['jenis'],
            'kapasitas_ton' => $d['ton'],
            'tahun' => $d['tahun'],
            'harga_sewa' => $d['harga'],
            'jangkauan_m' => $d['jangkauan'],
            'keterangan' => $d['ket'],
            'status' => $existing[$kode]['status'] ?? 'tersedia',
        ];
        if (isset($existing[$kode])) {
            $craneId[$kode] = (int) $existing[$kode]['id'];
            Crane::update($craneId[$kode], $row);
        } else {
            $craneId[$kode] = Crane::create($row);
        }
    }

    // -----------------------------------------------------------------
    // 3. Skor 5 crane x 5 kriteria
    // -----------------------------------------------------------------
    foreach ($cranes as $kode => $d) {
        $skor = skorKuantitatif($d['ton'], $d['harga'], $d['jangkauan']) + $skorSubjektif[$kode];

        foreach ($skor as $kk => $nilai) {
            Score::save($craneId[$kode], (int) $critByKode[$kk]['id'], (float) $nilai);
        }
        echo sprintf("%s  %s\n", $kode, implode('  ', array_map(fn($k, $v) => "$k=$v", array_keys($skor), $skor)));
    }

    $db->commit(); // crane + skor tersimpan; PairwiseMatrix::saveMatrix memakai transaksinya sendiri

    // -----------------------------------------------------------------
    // 4. Sesi perhitungan dari matriks berpasangan Bagian D
    // -----------------------------------------------------------------
    $sessionId = 0;
    $old = $db->prepare('SELECT id FROM calc_sessions WHERE nama_sesi = ?');
    $old->execute([NAMA_SESI]);
    foreach ($old->fetchAll() as $r) {
        CalcSession::delete((int) $r['id']);
    }

    $admin = $db->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetch();
    $sessionId = CalcSession::create(NAMA_SESI, $admin ? (int) $admin['id'] : null);

    $criteriaIds = array_map(fn($c) => (int) $c['id'], $criteria);
    $values = [];
    foreach ($perbandingan as [$a, $b, $menang, $nilai]) {
        $idA = (int) $critByKode[$a]['id'];
        $idB = (int) $critByKode[$b]['id'];
        // Sel segitiga atas harus (indeks A < indeks B) agar sesuai PairwiseMatrix::saveMatrix
        $val = $menang === 'A' ? (float) $nilai : ($menang === 'B' ? 1.0 / $nilai : 1.0);
        $values[$idA . '_' . $idB] = $val;
    }
    PairwiseMatrix::saveMatrix($sessionId, $criteriaIds, $values);

    $matrix = PairwiseMatrix::getMatrix($sessionId);
    $ahpCalc = ahpCalculateWeights($criteriaIds, $matrix);

    $rows = [];
    foreach ($criteriaIds as $idx => $cid) {
        $rows[] = [
            'criterion_id' => $cid, 'bobot' => $ahpCalc['weights'][$idx],
            'lambda_max' => $ahpCalc['lambda_max'], 'ci' => $ahpCalc['ci'], 'cr' => $ahpCalc['cr'],
        ];
    }
    AhpResult::saveAll($sessionId, $rows);

    if (!$ahpCalc['consistent']) {
        fwrite(STDERR, sprintf("PERINGATAN: CR = %.4f > %.1f. Sesi disimpan sebagai draft tanpa peringkat.\n", $ahpCalc['cr'], CR_THRESHOLD));
        exit(2);
    }

    $weights = [];
    foreach ($criteriaIds as $idx => $cid) {
        $weights[$cid] = $ahpCalc['weights'][$idx];
    }
    // Sesi kuisioner hanya memuat 5 unit hasil kuisioner, walau ada unit lain di basis data
    $craneList = array_values(array_filter(Crane::all(), fn($c) => isset($cranes[$c['kode']])));
    $scoreMatrix = Score::matrix();

    $ahpRank = ahpRankAlternatives($craneList, $criteria, $scoreMatrix, $weights);
    AhpRanking::saveAll($sessionId, $ahpRank['ranking']);

    $topsis = topsisCalculate($craneList, $criteria, $scoreMatrix, $weights);
    TopsisResult::saveAll($sessionId, $topsis['ranking']);

    $cmp = buildComparison($ahpRank['ranking'], $topsis['ranking']);
    Comparison::saveAll($sessionId, $cmp['rows']);

    CalcSession::markSelesai($sessionId);

    echo sprintf("\nSesi #%d \"%s\" selesai. CR = %.4f, Spearman = %.4f\n", $sessionId, NAMA_SESI, $ahpCalc['cr'], $cmp['spearman']);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if (!empty($sessionId)) {
        CalcSession::delete($sessionId); // buang sesi setengah jadi
    }
    fwrite(STDERR, 'Gagal: ' . $e->getMessage() . "\n");
    exit(1);
}
