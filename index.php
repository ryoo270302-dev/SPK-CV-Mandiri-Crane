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
<?php
$icon = fn(string $paths) => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
    . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
$icoCrane = $icon('<path d="M3 21h8M7 21V3M7 3h14M7 3 2 7M18 3v9"/><path d="M18 12v3a2 2 0 1 1-2 2"/>');
$icoList = $icon('<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>');
$icoCalc = $icon('<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h8"/>');
$icoCheck = $icon('<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>');
?>
<section class="mc-hero mb-4">
  <span class="mc-eyebrow">CV Mandiri Crane &middot; Pusat Rental Crane</span>
  <h1>Pilih crane terbaik dengan perhitungan yang objektif</h1>
  <p>Sistem pendukung keputusan yang membandingkan unit crane memakai metode AHP dan TOPSIS,
     berdasarkan kapasitas, biaya, jangkauan, mobilitas, dan ketersediaan unit.</p>
  <div class="d-flex flex-wrap gap-2">
    <?php if (isAdmin()): ?>
      <a href="<?= url('ahp/pairwise.php') ?>" class="btn btn-primary btn-lg">Hitung Rekomendasi</a>
    <?php endif; ?>
    <a href="<?= url('history/index.php') ?>" class="btn btn-light-hero btn-lg">Lihat Riwayat</a>
    <a href="<?= url('comparison/index.php') ?>" class="btn btn-light-hero btn-lg">Perbandingan Metode</a>
  </div>
</section>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card card-stat h-100"><div class="card-body d-flex align-items-center gap-3">
      <span class="stat-icon"><?= $icoCrane ?></span>
      <div><div class="small-muted">Jumlah Crane</div><div class="stat-value"><?= $jumlahCrane ?></div></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card card-stat h-100"><div class="card-body d-flex align-items-center gap-3">
      <span class="stat-icon"><?= $icoList ?></span>
      <div><div class="small-muted">Kriteria Aktif</div><div class="stat-value"><?= $jumlahKriteria ?></div></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card card-stat h-100"><div class="card-body d-flex align-items-center gap-3">
      <span class="stat-icon"><?= $icoCalc ?></span>
      <div><div class="small-muted">Sesi Perhitungan</div><div class="stat-value"><?= $jumlahSesi ?></div></div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card card-stat h-100"><div class="card-body d-flex align-items-center gap-3">
      <span class="stat-icon"><?= $icoCheck ?></span>
      <div>
        <div class="small-muted">Sesi Terakhir Selesai</div>
        <div class="fw-bold lh-sm"><?= $sesiTerakhir ? htmlspecialchars($sesiTerakhir['nama_sesi']) : '-' ?></div>
        <div class="small-muted"><?= $sesiTerakhir ? htmlspecialchars($sesiTerakhir['tanggal']) : '' ?></div>
      </div>
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
<?php if ($topTopsis && $topAhp): ?>
<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card best-card h-100"><div class="card-body">
      <div class="best-label">Crane terbaik &middot; metode AHP</div>
      <div class="fs-4 fw-bold"><?= htmlspecialchars($topAhp[0]['nama']) ?></div>
      <div class="small-muted"><?= htmlspecialchars($topAhp[0]['kode']) ?> &middot; skor <?= number_format($topAhp[0]['skor'], DECIMAL_PLACES) ?></div>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card best-card h-100"><div class="card-body">
      <div class="best-label">Crane terbaik &middot; metode TOPSIS</div>
      <div class="fs-4 fw-bold"><?= htmlspecialchars($topTopsis[0]['nama']) ?></div>
      <div class="small-muted"><?= htmlspecialchars($topTopsis[0]['kode']) ?> &middot; nilai V <?= number_format($topTopsis[0]['preferensi'], DECIMAL_PLACES) ?></div>
    </div></div>
  </div>
</div>
<?php endif; ?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">Rekomendasi Teratas - Metode AHP</div>
      <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0 rank-table">
          <thead><tr><th>#</th><th>Kode</th><th>Nama Crane</th><th>Skor</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($topAhp, 0, 5) as $r): ?>
            <tr class="<?= $r['peringkat'] == 1 ? 'rank-1' : '' ?>">
              <td><span class="rank-pill"><?= $r['peringkat'] ?></span></td>
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
        <table class="table table-hover align-middle mb-0 rank-table">
          <thead><tr><th>#</th><th>Kode</th><th>Nama Crane</th><th>Nilai V</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($topTopsis, 0, 5) as $r): ?>
            <tr class="<?= $r['peringkat'] == 1 ? 'rank-1' : '' ?>">
              <td><span class="rank-pill"><?= $r['peringkat'] ?></span></td>
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
