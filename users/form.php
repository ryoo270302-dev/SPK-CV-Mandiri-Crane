<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/User.php';

requireAdmin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$u = $id ? User::findById($id) : null;
if ($id && !$u) {
    flash('error', 'Pengguna tidak ditemukan.');
    header('Location: ' . url('users/index.php'));
    exit;
}

$errors = [];
$d = $u ?? ['username' => '', 'nama' => '', 'role' => 'viewer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $role = $_POST['role'] === 'admin' ? 'admin' : 'viewer';
    $password = $_POST['password'] ?? '';

    if ($username === '') $errors[] = 'Username wajib diisi.';
    if ($nama === '') $errors[] = 'Nama wajib diisi.';
    if ($username !== '' && User::usernameExists($username, $id)) $errors[] = 'Username sudah digunakan.';
    if (!$id && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    $d = ['username' => $username, 'nama' => $nama, 'role' => $role];

    if (empty($errors)) {
        if ($id) {
            User::updateProfile($id, $nama, $role);
            if ($password !== '') {
                if (strlen($password) < 6) {
                    $errors[] = 'Password minimal 6 karakter.';
                } else {
                    User::updatePassword($id, $password);
                }
            }
        } else {
            User::create($username, $password, $nama, $role);
        }
        if (empty($errors)) {
            flash('success', 'Data pengguna berhasil disimpan.');
            header('Location: ' . url('users/index.php'));
            exit;
        }
    }
}

$pageTitle = $id ? 'Ubah Pengguna' : 'Tambah Pengguna';
require __DIR__ . '/../views/layout/header.php';
?>
<h3 class="mb-3"><?= $pageTitle ?></h3>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card shadow-sm"><div class="card-body">
<form method="post" class="row g-3">
  <?= csrfField() ?>
  <div class="col-md-4">
    <label class="form-label">Username *</label>
    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($d['username']) ?>" <?= $id ? 'readonly' : '' ?> required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Nama *</label>
    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($d['nama']) ?>" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Role *</label>
    <select name="role" class="form-select">
      <option value="admin" <?= $d['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="viewer" <?= $d['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label"><?= $id ? 'Password Baru (kosongkan jika tidak diganti)' : 'Password *' ?></label>
    <input type="password" name="password" class="form-control">
  </div>
  <div class="col-12">
    <button class="btn btn-primary" type="submit">Simpan</button>
    <a href="<?= url('users/index.php') ?>" class="btn btn-secondary">Batal</a>
  </div>
</form>
</div></div>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
