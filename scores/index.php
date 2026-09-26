<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Crane.php';
require_once __DIR__ . '/../models/Criterion.php';
require_once __DIR__ . '/../models/Score.php';

requireAdmin();

$cranes = Crane::all();
$criteria = Criterion::all(true); // hanya kriteria aktif

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($cranes as $crane) {
        foreach ($criteria as $crit) {
            $field = 'nilai_' . $crane['id'] . '_' . $crit['id'];
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $nilai = (float) $_POST[$field];
                $nilai = max(1, min(5, $nilai)); // jaga rentang skala 1-5
                Score::save($crane['id'], $crit['id'], $nilai);
            }
        }
    }
    flash('success', 'Penilaian berhasil disimpan.');
    header('Location: ' . url('scores/index.php'));
    exit;
}

$matrix = Score::matrix();
$missing = Score::findMissing($cranes, $criteria);

$pageTitle = 'Input Penilaian';
require __DIR__ . '/../views/layout/header.php';
?>
<h3 class="mb-3">Input Penilaian Alternatif</h3>
<p class="small-muted">Isi skor setiap crane terhadap setiap kriteria aktif dengan skala <strong>1 (sangat kurang)</strong> sampai <strong>5 (sangat baik)</strong>, sesuai pedoman penilaian internal CV.</p>

<?php if (empty($cranes) || empty($criteria)): ?>
  <div class="alert alert-warning">
    Data crane atau kriteria aktif belum tersedia. Lengkapi <a href="<?= url('crane/index.php') ?>">Data Crane</a> dan
    <a href="<?= url('criteria/index.php') ?>">Kriteria</a> terlebih dahulu.
  </div>
<?php else: ?>

<?php if (!empty($missing)): ?>
  <div class="alert alert-info">
    Penilaian belum lengkap untuk <?= count($missing) ?> pasangan crane-kriteria. Perhitungan tidak dapat dijalankan sampai semua sel terisi (FR-04).
  </div>
<?php else: ?>
  <div class="alert alert-success">Semua penilaian sudah lengkap. Anda dapat melanjutkan ke <a href="<?= url('ahp/pairwise.php') ?>">Hitung Rekomendasi</a>.</div>
<?php endif; ?>

<form method="post">
  <?= csrfField() ?>
  <div class="card shadow-sm">
  <div class="table-responsive">
  <table class="table table-bordered table-matrix mb-0">
    <thead class="table-light">
      <tr>
        <th>Crane \ Kriteria</th>
        <?php foreach ($criteria as $crit): ?>
          <th><?= htmlspecialchars($crit['kode']) ?><br><span class="small-muted"><?= htmlspecialchars($crit['nama']) ?> (<?= $crit['tipe'] ?>)</span></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($cranes as $crane): ?>
      <tr>
        <td class="text-start"><strong><?= htmlspecialchars($crane['kode']) ?></strong> - <?= htmlspecialchars($crane['nama']) ?></td>
        <?php foreach ($criteria as $crit): ?>
          <?php $val = $matrix[$crane['id']][$crit['id']] ?? ''; ?>
          <td>
            <input type="number" min="1" max="5" step="1"
                   name="nilai_<?= $crane['id'] ?>_<?= $crit['id'] ?>"
                   value="<?= htmlspecialchars($val) ?>"
                   class="form-control form-control-sm text-center" style="width:70px;margin:auto;">
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  </div>
  <button type="submit" class="btn btn-primary mt-3">Simpan Penilaian</button>
</form>
<?php endif; ?>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
