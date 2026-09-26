<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/CalcSession.php';
require_once __DIR__ . '/../services/comparison.php';

requireLogin();

$sessions = CalcSession::all();
$sessionId = (int) ($_GET['session'] ?? 0);
if (!$sessionId) {
    $latest = CalcSession::latestSelesai();
    $sessionId = $latest ? (int) $latest['id'] : 0;
}
$session = $sessionId ? CalcSession::find($sessionId) : null;

$ahpRanking = $sessionId ? AhpRanking::forSession($sessionId) : [];
$topsisRanking = $sessionId ? TopsisResult::forSession($sessionId) : [];
$cmp = null;
if ($ahpRanking && $topsisRanking) {
    $cmp = buildComparison($ahpRanking, $topsisRanking);
}

$craneById = [];
foreach ($topsisRanking as $r) { $craneById[$r['crane_id']] = $r; }
$ahpByCrane = [];
foreach ($ahpRanking as $r) { $ahpByCrane[$r['crane_id']] = $r; }

$pageTitle = 'Perbandingan Hasil';
require __DIR__ . '/../views/layout/header.php';
?>
<h3 class="mb-3">Perbandingan Hasil AHP vs TOPSIS</h3>

<form method="get" class="row g-2 mb-3 no-print">
  <div class="col-auto">
    <select name="session" class="form-select" onchange="this.form.submit()">
      <option value="">-- Pilih Sesi --</option>
      <?php foreach ($sessions as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $s['id'] == $sessionId ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['nama_sesi']) ?> (<?= htmlspecialchars($s['tanggal']) ?>) - <?= $s['status'] ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<?php if (!$session || !$cmp): ?>
  <div class="alert alert-info">Belum ada sesi perhitungan yang lengkap untuk dibandingkan.</div>
<?php else: ?>

<p class="small-muted">Sesi: <strong><?= htmlspecialchars($session['nama_sesi']) ?></strong> - <?= htmlspecialchars($session['tanggal']) ?></p>

<div class="table-responsive mb-4">
<table class="table table-bordered">
  <thead class="table-light">
    <tr><th>Kode</th><th>Nama Crane</th><th>Peringkat AHP</th><th>Peringkat TOPSIS</th><th>Selisih Peringkat</th></tr>
  </thead>
  <tbody>
  <?php foreach ($cmp['rows'] as $row):
      $t = $craneById[$row['crane_id']]; ?>
    <tr class="<?= $row['peringkat_topsis'] == 1 ? 'rank-1' : '' ?>">
      <td><?= htmlspecialchars($t['kode']) ?></td>
      <td><?= htmlspecialchars($t['nama']) ?></td>
      <td><?= $row['peringkat_ahp'] ?></td>
      <td><?= $row['peringkat_topsis'] ?></td>
      <td>
        <?php if ($row['selisih'] == 0): ?>
          <span class="badge bg-success">Sama (0)</span>
        <?php else: ?>
          <span class="badge bg-warning text-dark"><?= ($row['selisih'] > 0 ? '+' : '') . $row['selisih'] ?></span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card shadow-sm h-100"><div class="card-body">
      <div class="small-muted">Alternatif Terbaik - AHP</div>
      <div class="fs-5 fw-bold"><?= htmlspecialchars($ahpByCrane[$cmp['best_ahp_crane_id']]['kode'] . ' - ' . $ahpByCrane[$cmp['best_ahp_crane_id']]['nama']) ?></div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm h-100"><div class="card-body">
      <div class="small-muted">Alternatif Terbaik - TOPSIS</div>
      <div class="fs-5 fw-bold"><?= htmlspecialchars($craneById[$cmp['best_topsis_crane_id']]['kode'] . ' - ' . $craneById[$cmp['best_topsis_crane_id']]['nama']) ?></div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm h-100"><div class="card-body">
      <div class="small-muted">Korelasi Spearman (Tingkat Kesesuaian)</div>
      <div class="fs-5 fw-bold"><?= number_format($cmp['spearman'], DECIMAL_PLACES) ?> <span class="fs-6 fw-normal">(<?= interpretSpearman($cmp['spearman']) ?>)</span></div>
    </div></div>
  </div>
</div>

<div class="alert <?= $cmp['same_top_pick'] ? 'alert-success' : 'alert-warning' ?>">
  <?php if ($cmp['same_top_pick']): ?>
    Kedua metode (AHP dan TOPSIS) merekomendasikan alternatif terbaik yang <strong>sama</strong>, yaitu
    <strong><?= htmlspecialchars($craneById[$cmp['best_topsis_crane_id']]['nama']) ?></strong>.
  <?php else: ?>
    Kedua metode menghasilkan rekomendasi alternatif terbaik yang <strong>berbeda</strong>:
    AHP memilih <strong><?= htmlspecialchars($ahpByCrane[$cmp['best_ahp_crane_id']]['nama']) ?></strong>,
    sedangkan TOPSIS memilih <strong><?= htmlspecialchars($craneById[$cmp['best_topsis_crane_id']]['nama']) ?></strong>.
  <?php endif; ?>
  Nilai korelasi Spearman sebesar <?= number_format($cmp['spearman'], DECIMAL_PLACES) ?> menunjukkan tingkat kesesuaian peringkat yang
  <strong><?= interpretSpearman($cmp['spearman']) ?></strong> antara kedua metode.
</div>

<a href="<?= url('report/cetak.php?session=' . $sessionId) ?>" class="btn btn-outline-secondary btn-sm no-print">Cetak Laporan Lengkap</a>

<?php endif; ?>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
