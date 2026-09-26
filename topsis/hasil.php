<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Crane.php';
require_once __DIR__ . '/../models/Criterion.php';
require_once __DIR__ . '/../models/Score.php';
require_once __DIR__ . '/../models/PairwiseMatrix.php';
require_once __DIR__ . '/../models/CalcSession.php';
require_once __DIR__ . '/../services/ahp.php';
require_once __DIR__ . '/../services/topsis.php';

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

$ahpResults = AhpResult::forSession($sessionId);
if (empty($ahpResults)) {
    flash('error', 'Bobot AHP untuk sesi ini belum tersedia.');
    header('Location: ' . url('ahp/hasil.php?session=' . $sessionId));
    exit;
}
if ($session['status'] !== 'selesai') {
    flash('error', 'Sesi ini belum "selesai" karena matriks AHP tidak konsisten (CR > 0.1). Hasil TOPSIS belum tersedia.');
    header('Location: ' . url('ahp/hasil.php?session=' . $sessionId));
    exit;
}
$weights = [];
foreach ($ahpResults as $r) {
    $weights[$r['criterion_id']] = (float) $r['bobot'];
}

$scoreMatrix = Score::matrix();
$topsis = topsisCalculate($cranes, $criteria, $scoreMatrix, $weights);

$pageTitle = 'Hasil TOPSIS';
require __DIR__ . '/../views/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h3>Hasil Perhitungan TOPSIS - <?= htmlspecialchars($session['nama_sesi']) ?></h3>
  <div>
    <a href="<?= url('ahp/hasil.php?session=' . $sessionId) ?>" class="btn btn-outline-primary btn-sm">Lihat Hasil AHP</a>
    <a href="<?= url('comparison/index.php?session=' . $sessionId) ?>" class="btn btn-outline-secondary btn-sm">Lihat Perbandingan</a>
  </div>
</div>

<h5>1. Bobot Kriteria (dari AHP)</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered w-auto">
  <thead class="table-light"><tr><th>Kriteria</th><th>Tipe</th><th>Bobot</th></tr></thead>
  <tbody>
  <?php foreach ($criteria as $c): ?>
    <tr><td><?= htmlspecialchars($c['kode'] . ' - ' . $c['nama']) ?></td><td><?= ucfirst($c['tipe']) ?></td><td><?= number_format($weights[$c['id']], DECIMAL_PLACES) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<h5>2. Matriks Keputusan (Nilai Mentah)</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered table-matrix">
  <thead class="table-light"><tr><th>Crane</th><?php foreach ($criteria as $c): ?><th><?= htmlspecialchars($c['kode']) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($cranes as $crane): ?>
    <tr>
      <th class="text-start"><?= htmlspecialchars($crane['kode']) ?></th>
      <?php foreach ($criteria as $c): ?><td><?= number_format($topsis['decision_matrix'][$crane['id']][$c['id']], DECIMAL_PLACES) ?></td><?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<h5>3. Normalisasi Vektor</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered table-matrix">
  <thead class="table-light"><tr><th>Crane</th><?php foreach ($criteria as $c): ?><th><?= htmlspecialchars($c['kode']) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($cranes as $crane): ?>
    <tr>
      <th class="text-start"><?= htmlspecialchars($crane['kode']) ?></th>
      <?php foreach ($criteria as $c): ?><td><?= number_format($topsis['normalized'][$crane['id']][$c['id']], DECIMAL_PLACES) ?></td><?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<h5>4. Matriks Ternormalisasi Terbobot</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered table-matrix">
  <thead class="table-light"><tr><th>Crane</th><?php foreach ($criteria as $c): ?><th><?= htmlspecialchars($c['kode']) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($cranes as $crane): ?>
    <tr>
      <th class="text-start"><?= htmlspecialchars($crane['kode']) ?></th>
      <?php foreach ($criteria as $c): ?><td><?= number_format($topsis['weighted'][$crane['id']][$c['id']], DECIMAL_PLACES) ?></td><?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
  <tr class="table-success"><th class="text-start">Solusi Ideal Positif (A+)</th><?php foreach ($criteria as $c): ?><td><?= number_format($topsis['ideal_positive'][$c['id']], DECIMAL_PLACES) ?></td><?php endforeach; ?></tr>
  <tr class="table-warning"><th class="text-start">Solusi Ideal Negatif (A-)</th><?php foreach ($criteria as $c): ?><td><?= number_format($topsis['ideal_negative'][$c['id']], DECIMAL_PLACES) ?></td><?php endforeach; ?></tr>
  </tbody>
</table>
</div>

<h5>5. Jarak ke Solusi Ideal &amp; Nilai Preferensi</h5>
<div class="table-responsive mb-4">
<table class="table table-bordered">
  <thead class="table-light"><tr><th>Peringkat</th><th>Kode</th><th>Nama Crane</th><th>D+</th><th>D-</th><th>Nilai Preferensi (V)</th></tr></thead>
  <tbody>
  <?php foreach ($topsis['ranking'] as $r): $c = array_values(array_filter($cranes, fn($x) => $x['id'] == $r['crane_id']))[0]; ?>
    <tr class="<?= $r['peringkat'] == 1 ? 'rank-1' : '' ?>">
      <td><?= $r['peringkat'] ?></td>
      <td><?= htmlspecialchars($c['kode']) ?></td>
      <td><?= htmlspecialchars($c['nama']) ?></td>
      <td><?= number_format($r['d_plus'], DECIMAL_PLACES) ?></td>
      <td><?= number_format($r['d_minus'], DECIMAL_PLACES) ?></td>
      <td class="fw-bold"><?= number_format($r['preferensi'], DECIMAL_PLACES) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="small-muted">Rumus nilai preferensi: V = D<sup>-</sup> / (D<sup>-</sup> + D<sup>+</sup>). Semakin besar nilai V, semakin baik peringkat alternatif.</p>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
