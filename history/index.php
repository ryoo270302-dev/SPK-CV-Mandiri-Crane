<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/CalcSession.php';

requireLogin();

$sessions = CalcSession::all();

$pageTitle = 'Riwayat Perhitungan';
require __DIR__ . '/../views/layout/header.php';
?>
<h3 class="mb-3">Riwayat Sesi Perhitungan</h3>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
  <thead class="table-light"><tr><th>Nama Sesi</th><th>Tanggal</th><th>Dibuat oleh</th><th>Status</th><th class="no-print">Aksi</th></tr></thead>
  <tbody>
  <?php if (empty($sessions)): ?>
    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat perhitungan.</td></tr>
  <?php endif; ?>
  <?php foreach ($sessions as $s): ?>
    <tr>
      <td><?= htmlspecialchars($s['nama_sesi']) ?></td>
      <td><?= htmlspecialchars($s['tanggal']) ?></td>
      <td><?= htmlspecialchars($s['pembuat'] ?? '-') ?></td>
      <td>
        <span class="badge <?= $s['status'] === 'selesai' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($s['status']) ?></span>
      </td>
      <td class="no-print">
        <a href="<?= url('ahp/hasil.php?session=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">AHP</a>
        <?php if ($s['status'] === 'selesai'): ?>
          <a href="<?= url('topsis/hasil.php?session=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">TOPSIS</a>
          <a href="<?= url('comparison/index.php?session=' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary">Perbandingan</a>
          <a href="<?= url('report/cetak.php?session=' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary">Cetak</a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
          <a href="<?= url('history/delete.php?id=' . $s['id']) ?>" class="btn btn-sm btn-outline-danger"
             onclick="return confirm('Hapus riwayat sesi ini beserta seluruh hasilnya?');">Hapus</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
