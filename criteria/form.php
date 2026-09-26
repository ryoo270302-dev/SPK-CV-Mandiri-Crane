<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Criterion.php';

requireAdmin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$crit = $id ? Criterion::find($id) : null;
if ($id && !$crit) {
    flash('error', 'Kriteria tidak ditemukan.');
    header('Location: ' . url('criteria/index.php'));
    exit;
}

$errors = [];
$d = $crit ?? ['kode' => '', 'nama' => '', 'tipe' => 'benefit', 'satuan' => '', 'keterangan' => '', 'aktif' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $d = [
        'kode'       => trim($_POST['kode'] ?? ''),
        'nama'       => trim($_POST['nama'] ?? ''),
        'tipe'       => $_POST['tipe'] === 'cost' ? 'cost' : 'benefit',
        'satuan'     => trim($_POST['satuan'] ?? ''),
        'keterangan' => trim($_POST['keterangan'] ?? ''),
        'aktif'      => isset($_POST['aktif']) ? 1 : 0,
    ];

    if ($d['kode'] === '') $errors[] = 'Kode wajib diisi.';
    if ($d['nama'] === '') $errors[] = 'Nama wajib diisi.';
    if ($d['kode'] !== '' && Criterion::kodeExists($d['kode'], $id)) $errors[] = 'Kode sudah digunakan kriteria lain.';

    if (empty($errors)) {
        if ($id) {
            Criterion::update($id, $d);
            flash('success', 'Kriteria berhasil diperbarui.');
        } else {
            Criterion::create($d);
            flash('success', 'Kriteria berhasil ditambahkan.');
        }
        header('Location: ' . url('criteria/index.php'));
        exit;
    }
}

$pageTitle = $id ? 'Ubah Kriteria' : 'Tambah Kriteria';
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
    <label class="form-label">Tipe *</label>
    <select name="tipe" class="form-select">
      <option value="benefit" <?= $d['tipe'] === 'benefit' ? 'selected' : '' ?>>Benefit (semakin besar semakin baik)</option>
      <option value="cost" <?= $d['tipe'] === 'cost' ? 'selected' : '' ?>>Cost (semakin kecil semakin baik)</option>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Satuan</label>
    <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($d['satuan'] ?? '') ?>" placeholder="ton, Rp/hari, skor 1-5, dst.">
  </div>
  <div class="col-md-8">
    <label class="form-label">Keterangan</label>
    <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($d['keterangan'] ?? '') ?>">
  </div>
  <div class="col-12">
    <div class="form-check">
      <input type="checkbox" name="aktif" id="aktif" class="form-check-input" <?= !empty($d['aktif']) ? 'checked' : '' ?>>
      <label for="aktif" class="form-check-label">Aktif (diikutkan dalam perhitungan)</label>
    </div>
  </div>
  <div class="col-12">
    <button class="btn btn-primary" type="submit">Simpan</button>
    <a href="<?= url('criteria/index.php') ?>" class="btn btn-secondary">Batal</a>
  </div>
</form>
</div></div>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
