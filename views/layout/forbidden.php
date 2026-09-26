<?php $pageTitle = 'Akses Ditolak'; require __DIR__ . '/header.php'; ?>
<div class="alert alert-warning mt-4">
  <h4>Akses Ditolak</h4>
  <p>Anda tidak memiliki hak akses untuk membuka halaman ini. Halaman ini hanya untuk peran <strong>admin</strong>.</p>
  <a href="<?= url('index.php') ?>" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>
<?php require __DIR__ . '/footer.php'; ?>
