<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Criterion.php';

requireAdmin();

$criteria = Criterion::all(false);

$pageTitle = 'Kriteria';
require __DIR__ . '/../views/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Manajemen Kriteria</h3>
  <a href="<?= url('criteria/form.php') ?>" class="btn btn-primary">+ Tambah Kriteria</a>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
  <thead class="table-light">
    <tr><th>Urutan</th><th>Kode</th><th>Nama</th><th>Tipe</th><th>Satuan</th><th>Keterangan</th><th>Status</th><th class="no-print">Aksi</th></tr>
  </thead>
  <tbody>
  <?php if (empty($criteria)): ?>
    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada kriteria.</td></tr>
  <?php endif; ?>
  <?php foreach ($criteria as $c): ?>
    <tr>
      <td><?= $c['urutan'] ?></td>
      <td><?= htmlspecialchars($c['kode']) ?></td>
      <td><?= htmlspecialchars($c['nama']) ?></td>
      <td><span class="badge <?= $c['tipe'] === 'benefit' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= ucfirst($c['tipe']) ?></span></td>
      <td><?= htmlspecialchars($c['satuan']) ?></td>
      <td><?= htmlspecialchars($c['keterangan']) ?></td>
      <td>
        <?php if ($c['aktif']): ?><span class="badge bg-primary">Aktif</span>
        <?php else: ?><span class="badge bg-secondary">Nonaktif</span><?php endif; ?>
      </td>
      <td class="no-print">
        <a href="<?= url('criteria/form.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
        <a href="<?= url('criteria/delete.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Hapus kriteria <?= htmlspecialchars(addslashes($c['nama'])) ?>? Data penilaian terkait juga akan terhapus.');">Hapus</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<p class="small-muted mt-2">Hanya kriteria berstatus <strong>Aktif</strong> yang diikutkan dalam perhitungan AHP dan TOPSIS.</p>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
