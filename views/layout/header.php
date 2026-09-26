<?php
/** @var string $pageTitle set by caller before including this file */
$pageTitle = $pageTitle ?? APP_NAME;
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<?php if ($user): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 no-print">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= url('index.php') ?>">SPK Crane</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Dashboard</a></li>
        <?php if (isAdmin()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('crane/index.php') ?>">Data Crane</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('criteria/index.php') ?>">Kriteria</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('scores/index.php') ?>">Penilaian</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('ahp/pairwise.php') ?>">Hitung Rekomendasi</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('comparison/index.php') ?>">Perbandingan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('history/index.php') ?>">Riwayat</a></li>
        <?php if (isAdmin()): ?>
        <li class="nav-item"><a class="nav-link" href="<?= url('users/index.php') ?>">Pengguna</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="<?= url('profile/password.php') ?>">Halo, <?= htmlspecialchars($user['nama']) ?> (<?= htmlspecialchars($user['role']) ?>)</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('logout.php') ?>">Keluar</a></li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
<div class="container-fluid px-4 pb-5">
<?php if ($f = flash('success')): ?>
  <div class="alert alert-success no-print"><?= htmlspecialchars($f) ?></div>
<?php endif; ?>
<?php if ($f = flash('error')): ?>
  <div class="alert alert-danger no-print"><?= htmlspecialchars($f) ?></div>
<?php endif; ?>
