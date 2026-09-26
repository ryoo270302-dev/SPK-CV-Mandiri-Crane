<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Crane.php';

requireAdmin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$crane = $id ? Crane::find($id) : null;
if ($id && !$crane) {
    flash('error', 'Data crane tidak ditemukan.');
    header('Location: ' . url('crane/index.php'));
    exit;
}

$errors = [];
$d = $crane ?? [
    'kode' => '', 'nama' => '', 'jenis' => '', 'kapasitas_ton' => '', 'tahun' => '',
    'harga_sewa' => '', 'jangkauan_m' => '', 'keterangan' => '', 'status' => 'tersedia',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $d = [
        'kode'          => trim($_POST['kode'] ?? ''),
        'nama'          => trim($_POST['nama'] ?? ''),
        'jenis'         => trim($_POST['jenis'] ?? ''),
        'kapasitas_ton' => $_POST['kapasitas_ton'] !== '' ? (float) $_POST['kapasitas_ton'] : null,
        'tahun'         => $_POST['tahun'] !== '' ? (int) $_POST['tahun'] : null,
        'harga_sewa'    => $_POST['harga_sewa'] !== '' ? (float) $_POST['harga_sewa'] : null,
        'jangkauan_m'   => $_POST['jangkauan_m'] !== '' ? (float) $_POST['jangkauan_m'] : null,
        'keterangan'    => trim($_POST['keterangan'] ?? ''),
        'status'        => $_POST['status'] === 'tidak_tersedia' ? 'tidak_tersedia' : 'tersedia',
    ];

    if ($d['kode'] === '') $errors[] = 'Kode wajib diisi.';
    if ($d['nama'] === '') $errors[] = 'Nama wajib diisi.';
    if ($d['kode'] !== '' && Crane::kodeExists($d['kode'], $id)) $errors[] = 'Kode sudah digunakan crane lain.';

    if (empty($errors)) {
        if ($id) {
            Crane::update($id, $d);
            flash('success', 'Data crane berhasil diperbarui.');
        } else {
            Crane::create($d);
            flash('success', 'Data crane berhasil ditambahkan.');
        }
        header('Location: ' . url('crane/index.php'));
        exit;
    }
}

$pageTitle = $id ? 'Ubah Crane' : 'Tambah Crane';
require __DIR__ . '/../views/layout/header.php';
?>
<h3 class="mb-3"><?= $pageTitle ?></h3>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card shadow-sm"><div class="card-body">
<form method="post" class="row g-3">
  <?= csrfField() ?>
  <div class="col-md-3">
    <label class="form-label">Kode *</label>
    <input type="text" name="kode" class="form-control" value="<?= htmlspecialchars($d['kode']) ?>" required>
  </div>
  <div class="col-md-5">
    <label class="form-label">Nama *</label>
    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($d['nama']) ?>" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Jenis</label>
    <input type="text" name="jenis" class="form-control" value="<?= htmlspecialchars($d['jenis'] ?? '') ?>" placeholder="Crawler Crane, Truck Crane, dst.">
  </div>
  <div class="col-md-3">
    <label class="form-label">Kapasitas Angkat (ton)</label>
    <input type="number" step="0.01" name="kapasitas_ton" class="form-control" value="<?= htmlspecialchars($d['kapasitas_ton'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Tahun</label>
    <input type="number" name="tahun" class="form-control" value="<?= htmlspecialchars($d['tahun'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Harga Sewa (Rp/hari)</label>
    <input type="number" step="0.01" name="harga_sewa" class="form-control" value="<?= htmlspecialchars($d['harga_sewa'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Jangkauan Kerja (m)</label>
    <input type="number" step="0.01" name="jangkauan_m" class="form-control" value="<?= htmlspecialchars($d['jangkauan_m'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Keterangan</label>
    <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($d['keterangan'] ?? '') ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label">Status Ketersediaan</label>
    <select name="status" class="form-select">
      <option value="tersedia" <?= ($d['status'] ?? '') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
      <option value="tidak_tersedia" <?= ($d['status'] ?? '') === 'tidak_tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
    </select>
  </div>
  <div class="col-12">
    <button class="btn btn-primary" type="submit">Simpan</button>
    <a href="<?= url('crane/index.php') ?>" class="btn btn-secondary">Batal</a>
  </div>
</form>
</div></div>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
