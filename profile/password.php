<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/User.php';

requireLogin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $user = User::findById(currentUser()['id']);
    if (!password_verify($current, $user['password_hash'])) {
        $errors[] = 'Password saat ini salah.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    }
    if ($new !== $confirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($errors)) {
        User::updatePassword($user['id'], $new);
        flash('success', 'Password berhasil diubah.');
        header('Location: ' . url('index.php'));
        exit;
    }
}

$pageTitle = 'Ubah Password';
require __DIR__ . '/../views/layout/header.php';
?>
<h3 class="mb-3">Ubah Password</h3>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card shadow-sm col-md-5"><div class="card-body">
<form method="post">
  <?= csrfField() ?>
  <div class="mb-3">
    <label class="form-label">Password Saat Ini</label>
    <input type="password" name="current_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password Baru</label>
    <input type="password" name="new_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Konfirmasi Password Baru</label>
    <input type="password" name="confirm_password" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Simpan</button>
</form>
</div></div>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
