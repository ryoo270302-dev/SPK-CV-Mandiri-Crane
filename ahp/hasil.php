<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Criterion.php';
require_once __DIR__ . '/../models/PairwiseMatrix.php';
require_once __DIR__ . '/../models/CalcSession.php';
require_once __DIR__ . '/../services/ahp.php';

requireLogin();

$sessionId = (int) ($_GET['session'] ?? 0);
$session = CalcSession::find($sessionId);
if (!$session) {
    flash('error', 'Sesi perhitungan tidak ditemukan.');
    header('Location: ' . url('history/index.php'));
    exit;
}

$criteria = Criterion::all(true);
$criteriaIds = array_column($criteria, 'id');
$criteriaById = [];
foreach ($criteria as $c) { $criteriaById[$c['id']] = $c; }

$matrix = PairwiseMatrix::getMatrix($sessionId);
$calc = ahpCalculateWeights($criteriaIds, $matrix);
$ahpResults = AhpResult::forSession($sessionId);
$ahpRanking = AhpRanking::forSession($sessionId);

$pageTitle = 'Hasil AHP';
require __DIR__ . '/../views/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h3>Hasil Perhitungan AHP - <?= htmlspecialchars($session['nama_sesi']) ?></h3>
  <div>
    <a href="<?= url('topsis/hasil.php?session=' . $sessionId) ?>" class="btn btn-outline-primary btn-sm">Lihat Hasil TOPSIS</a>
    <a href="<?= url('comparison/index.php?session=' . $sessionId) ?>" class="btn btn-outline-secondary btn-sm">Lihat Perbandingan</a>
  </div>
</div>

<h5>1. Matriks Perbandingan Berpasangan</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered table-matrix">
  <thead class="table-light"><tr><th>Kriteria</th><?php foreach ($criteria as $c): ?><th><?= htmlspecialchars($c['kode']) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($criteria as $i => $rowC): ?>
    <tr>
      <th class="text-start"><?= htmlspecialchars($rowC['kode']) ?> - <?= htmlspecialchars($rowC['nama']) ?></th>
      <?php foreach ($criteria as $j => $colC): ?>
        <td><?= number_format($calc['matrix'][$i][$j], DECIMAL_PLACES) ?></td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
  <tr class="table-secondary"><th class="text-start">Jumlah Kolom</th>
    <?php foreach ($calc['col_sums'] as $s): ?><td><?= number_format($s, DECIMAL_PLACES) ?></td><?php endforeach; ?>
  </tr>
  </tbody>
</table>
</div>

<h5>2. Matriks Ternormalisasi &amp; Bobot Prioritas</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered table-matrix">
  <thead class="table-light"><tr><th>Kriteria</th><?php foreach ($criteria as $c): ?><th><?= htmlspecialchars($c['kode']) ?></th><?php endforeach; ?><th>Bobot</th></tr></thead>
  <tbody>
  <?php foreach ($criteria as $i => $rowC): ?>
    <tr>
      <th class="text-start"><?= htmlspecialchars($rowC['kode']) ?></th>
      <?php foreach ($criteria as $j => $colC): ?>
        <td><?= number_format($calc['normalized'][$i][$j], DECIMAL_PLACES) ?></td>
      <?php endforeach; ?>
      <td class="fw-bold"><?= number_format($calc['weights'][$i], DECIMAL_PLACES) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<h5>3. Uji Konsistensi</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered w-auto">
  <tbody>
    <tr><th>&lambda; maksimum</th><td><?= number_format($calc['lambda_max'], DECIMAL_PLACES) ?></td></tr>
    <tr><th>Jumlah kriteria (n)</th><td><?= $calc['n'] ?></td></tr>
    <tr><th>Consistency Index (CI)</th><td><?= number_format($calc['ci'], DECIMAL_PLACES) ?></td></tr>
    <tr><th>Random Index (RI)</th><td><?= number_format($calc['ri'], DECIMAL_PLACES) ?></td></tr>
    <tr><th>Consistency Ratio (CR)</th>
      <td>
        <?= number_format($calc['cr'], DECIMAL_PLACES) ?>
        <?php if ($calc['consistent']): ?>
          <span class="badge badge-cr-ok">Konsisten (CR &le; 0.1)</span>
        <?php else: ?>
          <span class="badge badge-cr-bad">Tidak Konsisten (CR &gt; 0.1)</span>
        <?php endif; ?>
      </td>
    </tr>
  </tbody>
</table>
</div>
<?php if (!$calc['consistent']): ?>
  <div class="alert alert-danger">
    CR melebihi 0.1 (AC-03). Peringkat alternatif AHP/TOPSIS di bawah ini <strong>belum dihitung ulang</strong> untuk sesi ini.
    Silakan buat sesi baru dengan matriks perbandingan yang lebih konsisten melalui
    <a href="<?= url('ahp/pairwise.php') ?>">Hitung Rekomendasi</a>.
  </div>
<?php endif; ?>

<h5>4. Peringkat Alternatif Metode AHP</h5>
<?php if (empty($ahpRanking)): ?>
  <p class="text-muted">Belum tersedia (menunggu perbaikan konsistensi matriks).</p>
<?php else: ?>
<div class="table-responsive mb-4">
<table class="table table-bordered">
  <thead class="table-light"><tr><th>Peringkat</th><th>Kode</th><th>Nama Crane</th><th>Skor AHP</th></tr></thead>
  <tbody>
  <?php foreach ($ahpRanking as $r): ?>
    <tr class="<?= $r['peringkat'] == 1 ? 'rank-1' : '' ?>">
      <td><?= $r['peringkat'] ?></td>
      <td><?= htmlspecialchars($r['kode']) ?></td>
      <td><?= htmlspecialchars($r['nama']) ?></td>
      <td><?= number_format($r['skor'], DECIMAL_PLACES) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
