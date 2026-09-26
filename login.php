<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/User.php';

if (isLoggedIn()) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = User::findByUsername($username);
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'nama'     => $user['nama'],
            'role'     => $user['role'],
        ];
        header('Location: ' . url('index.php'));
        exit;
    }
    $error = 'Username atau password salah.';
}

$pageTitle = 'Masuk';
require __DIR__ . '/views/layout/header.php';
?>
<div class="row justify-content-center mt-5">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="mb-3 text-center">SPK Crane</h4>
        <p class="text-center small-muted mb-4">CV Mandiri Crane - AHP &amp; TOPSIS</p>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <?= csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>
        <p class="small-muted mt-3 mb-0 text-center">Akun default: admin/admin123 atau viewer/viewer123</p>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/views/layout/footer.php'; ?>
