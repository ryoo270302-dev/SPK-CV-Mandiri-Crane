<?php
/**
 * @var string $pageTitle set by caller before including this file
 * @var string $bodyClass kelas tambahan untuk <body> (opsional, mis. 'mc-login')
 */
$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
$user = currentUser();

// Halaman aktif ditentukan dari nama folder skrip yang sedang berjalan.
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$sections = ['crane', 'criteria', 'scores', 'ahp', 'comparison', 'history', 'users', 'profile'];
$curDir = basename(dirname($script));
$current = in_array($curDir, $sections, true) ? $curDir : 'dashboard';
$active = fn(string $key): string => $key === $current ? ' active' : '';

$craneIcon = '<svg width="24" height="24" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.4" '
    . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M6 28h11M11.5 28V6M11.5 6H28M11.5 6 4 11M23 6v10"/><path d="M23 16v3.5a2.5 2.5 0 1 1-2.5 2.5"/></svg>';
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
<body class="<?= htmlspecialchars($bodyClass) ?>">
<?php if ($user): ?>
<nav class="navbar navbar-expand-lg navbar-dark mc-navbar mb-4 no-print">
  <div class="container-fluid px-4">
    <a class="mc-brand" href="<?= url('index.php') ?>">
      <span class="mc-brand-icon"><?= $craneIcon ?></span>
      <span class="mc-brand-text"><strong>Mandiri Crane</strong><small>SPK AHP &amp; TOPSIS</small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-lg-4 me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link<?= $active('dashboard') ?>" href="<?= url('index.php') ?>">Dashboard</a></li>
        <?php if (isAdmin()): ?>
        <li class="nav-item"><a class="nav-link<?= $active('crane') ?>" href="<?= url('crane/index.php') ?>">Data Crane</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('criteria') ?>" href="<?= url('criteria/index.php') ?>">Kriteria</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('scores') ?>" href="<?= url('scores/index.php') ?>">Penilaian</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('ahp') ?>" href="<?= url('ahp/pairwise.php') ?>">Hitung Rekomendasi</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link<?= $active('comparison') ?>" href="<?= url('comparison/index.php') ?>">Perbandingan</a></li>
        <li class="nav-item"><a class="nav-link<?= $active('history') ?>" href="<?= url('history/index.php') ?>">Riwayat</a></li>
        <?php if (isAdmin()): ?>
        <li class="nav-item"><a class="nav-link<?= $active('users') ?>" href="<?= url('users/index.php') ?>">Pengguna</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a class="mc-user" href="<?= url('profile/password.php') ?>" title="Ubah password">
          <?= htmlspecialchars($user['nama']) ?>
          <span class="mc-user-role"><?= htmlspecialchars($user['role']) ?></span>
        </a>
        <a class="nav-link mc-logout" href="<?= url('logout.php') ?>">Keluar</a>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
<div class="container-fluid px-4 pb-4">
<?php if ($f = flash('success')): ?>
  <div class="alert alert-success no-print"><?= htmlspecialchars($f) ?></div>
<?php endif; ?>
<?php if ($f = flash('error')): ?>
  <div class="alert alert-danger no-print"><?= htmlspecialchars($f) ?></div>
<?php endif; ?>
