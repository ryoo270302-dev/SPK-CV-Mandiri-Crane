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
$bodyClass = 'mc-login';
require __DIR__ . '/views/layout/header.php';
?>
<div class="row justify-content-center my-auto py-4">
  <div class="col-sm-9 col-md-6 col-lg-4">
    <div class="card mc-login-card">
      <div class="mc-login-top">
        <span class="mc-brand-icon">
          <svg width="30" height="30" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 28h11M11.5 28V6M11.5 6H28M11.5 6 4 11M23 6v10"/><path d="M23 16v3.5a2.5 2.5 0 1 1-2.5 2.5"/></svg>
        </span>
        <h4 class="mb-1">SPK Crane</h4>
        <small>CV Mandiri Crane &middot; AHP &amp; TOPSIS</small>
      </div>
      <div class="card-body p-4">
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
