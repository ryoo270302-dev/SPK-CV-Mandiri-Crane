<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Crane.php';
require_once __DIR__ . '/models/Criterion.php';
require_once __DIR__ . '/models/CalcSession.php';

requireLogin();

$jumlahCrane = Crane::count();
$jumlahKriteria = Criterion::countActive();
$jumlahSesi = CalcSession::count();
$sesiTerakhir = CalcSession::latestSelesai();

$topAhp = [];
$topTopsis = [];
if ($sesiTerakhir) {
    $topAhp = AhpRanking::forSession($sesiTerakhir['id']);
    $topTopsis = TopsisResult::forSession($sesiTerakhir['id']);
}

$pageTitle = 'Dashboard';
require __DIR__ . '/views/layout/header.php';
?>
<h3 class="mb-4">Dashboard</h3>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card card-stat shadow-sm h-100"><div class="card-body">
      <div class="small-muted">Jumlah Crane</div>
      <div class="fs-3 fw-bold"><?= $jumlahCrane ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card card-stat shadow-sm h-100"><div class="card-body">
      <div class="small-muted">Kriteria Aktif</div>
      <div class="fs-3 fw-bold"><?= $jumlahKriteria ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card card-stat shadow-sm h-100"><div class="card-body">
      <div class="small-muted">Sesi Perhitungan</div>
      <div class="fs-3 fw-bold"><?= $jumlahSesi ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card card-stat shadow-sm h-100"><div class="card-body">
      <div class="small-muted">Sesi Terakhir Selesai</div>
      <div class="fs-6 fw-bold"><?= $sesiTerakhir ? htmlspecialchars($sesiTerakhir['nama_sesi']) : '-' ?></div>
      <div class="small-muted"><?= $sesiTerakhir ? htmlspecialchars($sesiTerakhir['tanggal']) : '' ?></div>
    </div></div>
  </div>
</div>

<?php if (!$sesiTerakhir): ?>
  <div class="alert alert-info">
    Belum ada sesi perhitungan yang selesai.
    <?php if (isAdmin()): ?>
      <a href="<?= url('ahp/pairwise.php') ?>">Mulai hitung rekomendasi</a> setelah data crane, kriteria, dan penilaian lengkap.
    <?php endif; ?>
  </div>
<?php else: ?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">Rekomendasi Teratas - Metode AHP</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>#</th><th>Kode</th><th>Nama Crane</th><th>Skor</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($topAhp, 0, 5) as $r): ?>
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
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">Rekomendasi Teratas - Metode TOPSIS</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>#</th><th>Kode</th><th>Nama Crane</th><th>Nilai V</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($topTopsis, 0, 5) as $r): ?>
            <tr class="<?= $r['peringkat'] == 1 ? 'rank-1' : '' ?>">
              <td><?= $r['peringkat'] ?></td>
              <td><?= htmlspecialchars($r['kode']) ?></td>
              <td><?= htmlspecialchars($r['nama']) ?></td>
              <td><?= number_format($r['preferensi'], DECIMAL_PLACES) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="mt-3">
  <a href="<?= url('comparison/index.php?session=' . $sesiTerakhir['id']) ?>" class="btn btn-outline-primary btn-sm">Lihat Perbandingan Lengkap</a>
  <a href="<?= url('report/cetak.php?session=' . $sesiTerakhir['id']) ?>" class="btn btn-outline-secondary btn-sm">Cetak Laporan</a>
</div>
<?php endif; ?>

<?php require __DIR__ . '/views/layout/footer.php'; ?>
