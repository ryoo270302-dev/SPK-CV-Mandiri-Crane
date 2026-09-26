<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Crane.php';
require_once __DIR__ . '/../models/Criterion.php';
require_once __DIR__ . '/../models/Score.php';
require_once __DIR__ . '/../models/PairwiseMatrix.php';
require_once __DIR__ . '/../models/CalcSession.php';
require_once __DIR__ . '/../services/ahp.php';
require_once __DIR__ . '/../services/topsis.php';
require_once __DIR__ . '/../services/comparison.php';

requireLogin();

$sessionId = (int) ($_GET['session'] ?? 0);
$session = CalcSession::find($sessionId);
if (!$session) {
    flash('error', 'Sesi perhitungan tidak ditemukan.');
    header('Location: ' . url('history/index.php'));
    exit;
}

$cranes = Crane::all();
$criteria = Criterion::all(true);
$criteriaIds = array_column($criteria, 'id');

$matrix = PairwiseMatrix::getMatrix($sessionId);
$ahpCalc = ahpCalculateWeights($criteriaIds, $matrix);
$ahpRanking = AhpRanking::forSession($sessionId);
$topsisRanking = TopsisResult::forSession($sessionId);
$cmp = ($ahpRanking && $topsisRanking) ? buildComparison($ahpRanking, $topsisRanking) : null;

$craneById = [];
foreach ($cranes as $c) { $craneById[$c['id']] = $c; }

$pageTitle = 'Laporan Hasil Rekomendasi';
require __DIR__ . '/../views/layout/header.php';
?>
<div class="no-print mb-3">
  <button onclick="window.print()" class="btn btn-primary">Cetak / Simpan sebagai PDF</button>
  <a href="<?= url('history/index.php') ?>" class="btn btn-secondary">Kembali</a>
</div>

<div class="text-center mb-4">
  <h4>LAPORAN HASIL REKOMENDASI CRANE TERBAIK</h4>
  <p class="mb-0">CV Mandiri Crane</p>
  <p class="small-muted">Sesi: <?= htmlspecialchars($session['nama_sesi']) ?> | Tanggal: <?= htmlspecialchars($session['tanggal']) ?> | Status: <?= htmlspecialchars($session['status']) ?></p>
</div>

<h5>1. Data Kriteria yang Digunakan</h5>
<table class="table table-bordered table-sm mb-4">
  <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Bobot</th></tr></thead>
  <tbody>
  <?php foreach ($criteria as $i => $c): ?>
    <tr><td><?= htmlspecialchars($c['kode']) ?></td><td><?= htmlspecialchars($c['nama']) ?></td><td><?= ucfirst($c['tipe']) ?></td>
        <td><?= number_format($ahpCalc['weights'][$i], DECIMAL_PLACES) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h5>2. Uji Konsistensi AHP</h5>
<table class="table table-bordered table-sm w-auto mb-4">
  <tbody>
    <tr><th>Lambda Maksimum</th><td><?= number_format($ahpCalc['lambda_max'], DECIMAL_PLACES) ?></td></tr>
    <tr><th>Consistency Index (CI)</th><td><?= number_format($ahpCalc['ci'], DECIMAL_PLACES) ?></td></tr>
    <tr><th>Consistency Ratio (CR)</th><td><?= number_format($ahpCalc['cr'], DECIMAL_PLACES) ?> - <?= $ahpCalc['consistent'] ? 'Konsisten' : 'Tidak Konsisten' ?></td></tr>
  </tbody>
</table>

<h5>3. Peringkat Alternatif - Metode AHP</h5>
<table class="table table-bordered table-sm mb-4">
  <thead class="table-light"><tr><th>Peringkat</th><th>Kode</th><th>Nama Crane</th><th>Skor</th></tr></thead>
  <tbody>
  <?php foreach ($ahpRanking as $r): ?>
    <tr><td><?= $r['peringkat'] ?></td><td><?= htmlspecialchars($r['kode']) ?></td><td><?= htmlspecialchars($r['nama']) ?></td><td><?= number_format($r['skor'], DECIMAL_PLACES) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h5>4. Peringkat Alternatif - Metode TOPSIS</h5>
<table class="table table-bordered table-sm mb-4">
  <thead class="table-light"><tr><th>Peringkat</th><th>Kode</th><th>Nama Crane</th><th>Nilai Preferensi (V)</th></tr></thead>
  <tbody>
  <?php foreach ($topsisRanking as $r): ?>
    <tr><td><?= $r['peringkat'] ?></td><td><?= htmlspecialchars($r['kode']) ?></td><td><?= htmlspecialchars($r['nama']) ?></td><td><?= number_format($r['preferensi'], DECIMAL_PLACES) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php if ($cmp): ?>
<h5>5. Perbandingan Hasil</h5>
<table class="table table-bordered table-sm mb-3">
  <thead class="table-light"><tr><th>Kode</th><th>Nama Crane</th><th>Peringkat AHP</th><th>Peringkat TOPSIS</th><th>Selisih</th></tr></thead>
  <tbody>
  <?php foreach ($cmp['rows'] as $row): $c = $craneById[$row['crane_id']]; ?>
    <tr><td><?= htmlspecialchars($c['kode']) ?></td><td><?= htmlspecialchars($c['nama']) ?></td><td><?= $row['peringkat_ahp'] ?></td><td><?= $row['peringkat_topsis'] ?></td><td><?= $row['selisih'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<p>Korelasi Spearman: <strong><?= number_format($cmp['spearman'], DECIMAL_PLACES) ?></strong> (tingkat kesesuaian <?= interpretSpearman($cmp['spearman']) ?>).
Rekomendasi terbaik AHP: <strong><?= htmlspecialchars($craneById[$cmp['best_ahp_crane_id']]['nama']) ?></strong>.
Rekomendasi terbaik TOPSIS: <strong><?= htmlspecialchars($craneById[$cmp['best_topsis_crane_id']]['nama']) ?></strong>.</p>
<?php endif; ?>

<p class="small-muted mt-4">Dicetak oleh: <?= htmlspecialchars(currentUser()['nama']) ?> pada <?= date('d-m-Y H:i') ?></p>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
