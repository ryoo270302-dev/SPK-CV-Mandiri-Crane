<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Crane.php';

requireAdmin();

$search = trim($_GET['q'] ?? '');
$sortBy = $_GET['sort'] ?? 'kode';
$sortDir = $_GET['dir'] ?? 'asc';
$cranes = Crane::all($search, $sortBy, $sortDir);

function sortLink(string $col, string $label, string $sortBy, string $sortDir, string $search): string
{
    $nextDir = ($sortBy === $col && $sortDir === 'asc') ? 'desc' : 'asc';
    $qs = http_build_query(['q' => $search, 'sort' => $col, 'dir' => $nextDir]);
    $arrow = $sortBy === $col ? ($sortDir === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="?' . $qs . '" class="text-decoration-none text-dark">' . htmlspecialchars($label) . $arrow . '</a>';
}

$pageTitle = 'Data Crane';
require __DIR__ . '/../views/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Data Crane (Alternatif)</h3>
  <a href="<?= url('crane/form.php') ?>" class="btn btn-primary">+ Tambah Crane</a>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-auto">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari kode/nama/jenis...">
  </div>
  <div class="col-auto"><button class="btn btn-outline-secondary">Cari</button></div>
</form>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
  <thead class="table-light">
    <tr>
      <th><?= sortLink('kode', 'Kode', $sortBy, $sortDir, $search) ?></th>
      <th><?= sortLink('nama', 'Nama', $sortBy, $sortDir, $search) ?></th>
      <th><?= sortLink('jenis', 'Jenis', $sortBy, $sortDir, $search) ?></th>
      <th class="text-end"><?= sortLink('kapasitas_ton', 'Kapasitas (ton)', $sortBy, $sortDir, $search) ?></th>
      <th><?= sortLink('tahun', 'Tahun', $sortBy, $sortDir, $search) ?></th>
      <th class="text-end"><?= sortLink('harga_sewa', 'Harga Sewa', $sortBy, $sortDir, $search) ?></th>
      <th class="text-end"><?= sortLink('jangkauan_m', 'Jangkauan (m)', $sortBy, $sortDir, $search) ?></th>
      <th><?= sortLink('status', 'Status', $sortBy, $sortDir, $search) ?></th>
      <th class="no-print">Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($cranes)): ?>
    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data crane.</td></tr>
  <?php endif; ?>
  <?php foreach ($cranes as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['kode']) ?></td>
      <td><?= htmlspecialchars($c['nama']) ?></td>
      <td><?= htmlspecialchars($c['jenis']) ?></td>
      <td class="text-end"><?= number_format((float)$c['kapasitas_ton'], 2) ?></td>
      <td><?= htmlspecialchars($c['tahun']) ?></td>
      <td class="text-end"><?= number_format((float)$c['harga_sewa'], 0) ?></td>
      <td class="text-end"><?= number_format((float)$c['jangkauan_m'], 2) ?></td>
      <td>
        <span class="badge <?= $c['status'] === 'tersedia' ? 'bg-success' : 'bg-secondary' ?>">
          <?= $c['status'] === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia' ?>
        </span>
      </td>
      <td class="no-print">
        <a href="<?= url('crane/form.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
        <a href="<?= url('crane/delete.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Hapus crane <?= htmlspecialchars(addslashes($c['nama'])) ?>? Data penilaian terkait juga akan terhapus.');">Hapus</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
