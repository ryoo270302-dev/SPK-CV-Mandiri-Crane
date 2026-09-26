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

requireAdmin();

$cranes = Crane::all();
$criteria = Criterion::all(true);
$criteriaIds = array_column($criteria, 'id');
$n = count($criteria);

$missing = Score::findMissing($cranes, $criteria);
$canRun = $n >= 2 && count($cranes) >= 2 && empty($missing);

/** Saaty scale options: label => decimal value (as string, parsed below) */
function saatyOptions(): array
{
    return [
        '9' => '9 - Mutlak lebih penting',
        '7' => '7 - Sangat lebih penting',
        '5' => '5 - Lebih penting',
        '3' => '3 - Sedikit lebih penting',
        '1' => '1 - Sama penting',
        '1/3' => '1/3 - Sedikit kurang penting',
        '1/5' => '1/5 - Kurang penting',
        '1/7' => '1/7 - Sangat kurang penting',
        '1/9' => '1/9 - Mutlak kurang penting',
    ];
}

function parseSaaty(string $val): float
{
    if (strpos($val, '/') !== false) {
        [$a, $b] = explode('/', $val);
        return $b != 0 ? ((float) $a) / ((float) $b) : 1.0;
    }
    return (float) $val;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canRun) {
    verifyCsrf();

    $namaSesi = trim($_POST['nama_sesi'] ?? '') ?: ('Sesi ' . date('d-m-Y H:i'));

    // Kumpulkan nilai upper-triangle
    $values = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $key = $criteriaIds[$i] . '_' . $criteriaIds[$j];
            $raw = $_POST['pair_' . $key] ?? '1';
            $values[$key] = parseSaaty($raw);
        }
    }

    // 1. Buat sesi & simpan matriks berpasangan
    $sessionId = CalcSession::create($namaSesi, currentUser()['id']);
    PairwiseMatrix::saveMatrix($sessionId, $criteriaIds, $values);

    // 2. Hitung bobot AHP
    $matrix = PairwiseMatrix::getMatrix($sessionId);
    $ahpCalc = ahpCalculateWeights($criteriaIds, $matrix);

    if (!$ahpCalc['consistent']) {
        $errors[] = sprintf(
            'Consistency Ratio (CR) = %.4f melebihi batas maksimal %.1f (AC-03). '
            . 'Matriks perbandingan tidak konsisten. Silakan tinjau ulang nilai perbandingan sebelum melanjutkan.',
            $ahpCalc['cr'], CR_THRESHOLD
        );
        // Tetap simpan bobot untuk ditinjau, namun sesi ditandai draft (tidak "selesai")
        $rows = [];
        foreach ($criteriaIds as $idx => $cid) {
            $rows[] = [
                'criterion_id' => $cid, 'bobot' => $ahpCalc['weights'][$idx],
                'lambda_max' => $ahpCalc['lambda_max'], 'ci' => $ahpCalc['ci'], 'cr' => $ahpCalc['cr'],
            ];
        }
        AhpResult::saveAll($sessionId, $rows);
    } else {
        $rows = [];
        foreach ($criteriaIds as $idx => $cid) {
            $rows[] = [
                'criterion_id' => $cid, 'bobot' => $ahpCalc['weights'][$idx],
                'lambda_max' => $ahpCalc['lambda_max'], 'ci' => $ahpCalc['ci'], 'cr' => $ahpCalc['cr'],
            ];
        }
        AhpResult::saveAll($sessionId, $rows);

        $weights = [];
        foreach ($criteriaIds as $idx => $cid) {
            $weights[$cid] = $ahpCalc['weights'][$idx];
        }

        // 3. Peringkat alternatif metode AHP
        $scoreMatrix = Score::matrix();
        $ahpRank = ahpRankAlternatives($cranes, $criteria, $scoreMatrix, $weights);
        AhpRanking::saveAll($sessionId, $ahpRank['ranking']);

        // 4. TOPSIS (memakai bobot AHP)
        $topsis = topsisCalculate($cranes, $criteria, $scoreMatrix, $weights);
        TopsisResult::saveAll($sessionId, $topsis['ranking']);

        // 5. Perbandingan
        $cmp = buildComparison($ahpRank['ranking'], $topsis['ranking']);
        Comparison::saveAll($sessionId, $cmp['rows']);

        CalcSession::markSelesai($sessionId);
        flash('success', 'Perhitungan AHP dan TOPSIS berhasil dijalankan.');
        header('Location: ' . url('ahp/hasil.php?session=' . $sessionId));
        exit;
    }
}

$pageTitle = 'Hitung Rekomendasi';
require __DIR__ . '/../views/layout/header.php';
?>
<h3 class="mb-3">Perbandingan Berpasangan Antar Kriteria (AHP)</h3>

<?php if (!$canRun): ?>
  <div class="alert alert-warning">
    Perhitungan belum dapat dijalankan. Pastikan:
    <ul class="mb-0">
      <li>Minimal 2 kriteria aktif (saat ini: <?= $n ?>)</li>
      <li>Minimal 2 data crane (saat ini: <?= count($cranes) ?>)</li>
      <li>Semua penilaian alternatif sudah terisi (kekurangan: <?= count($missing) ?> sel)</li>
    </ul>
    <div class="mt-2">
      <a href="<?= url('criteria/index.php') ?>" class="btn btn-sm btn-outline-secondary">Kelola Kriteria</a>
      <a href="<?= url('crane/index.php') ?>" class="btn btn-sm btn-outline-secondary">Kelola Crane</a>
      <a href="<?= url('scores/index.php') ?>" class="btn btn-sm btn-outline-secondary">Isi Penilaian</a>
    </div>
  </div>
<?php else: ?>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><p class="mb-0"><?= htmlspecialchars($e) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<form method="post">
  <?= csrfField() ?>
  <div class="mb-3 col-md-5">
    <label class="form-label">Nama Sesi Perhitungan</label>
    <input type="text" name="nama_sesi" class="form-control" placeholder="contoh: Rekomendasi Crane Proyek A">
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-header">Isi nilai perbandingan tingkat kepentingan antar kriteria (skala Saaty 1-9)</div>
    <div class="table-responsive">
      <table class="table table-bordered mb-0 align-middle">
        <thead class="table-light"><tr><th>Kriteria A</th><th>Kriteria B</th><th style="width:280px;">Perbandingan (A terhadap B)</th></tr></thead>
        <tbody>
        <?php for ($i = 0; $i < $n; $i++): for ($j = $i + 1; $j < $n; $j++): $key = $criteriaIds[$i] . '_' . $criteriaIds[$j]; ?>
          <tr>
            <td><?= htmlspecialchars($criteria[$i]['nama']) ?></td>
            <td><?= htmlspecialchars($criteria[$j]['nama']) ?></td>
            <td>
              <?php $prev = $_POST['pair_' . $key] ?? '1'; ?>
              <select name="pair_<?= $key ?>" class="form-select form-select-sm">
                <?php foreach (saatyOptions() as $val => $label): ?>
                  <option value="<?= $val ?>" <?= $val === $prev ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        <?php endfor; endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Hitung AHP &amp; TOPSIS</button>
</form>
<?php endif; ?>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
