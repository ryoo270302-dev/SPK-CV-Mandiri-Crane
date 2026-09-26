<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/User.php';

requireAdmin();

$users = User::all();

$pageTitle = 'Manajemen Pengguna';
require __DIR__ . '/../views/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Manajemen Pengguna</h3>
  <a href="<?= url('users/form.php') ?>" class="btn btn-primary">+ Tambah Pengguna</a>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
  <thead class="table-light"><tr><th>Username</th><th>Nama</th><th>Role</th><th>Dibuat</th><th class="no-print">Aksi</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= htmlspecialchars($u['username']) ?></td>
      <td><?= htmlspecialchars($u['nama']) ?></td>
      <td><span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-info text-dark' ?>"><?= ucfirst($u['role']) ?></span></td>
      <td><?= htmlspecialchars($u['created_at']) ?></td>
      <td class="no-print">
        <a href="<?= url('users/form.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
        <?php if ($u['id'] != currentUser()['id']): ?>
          <a href="<?= url('users/delete.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-danger"
             onclick="return confirm('Hapus pengguna <?= htmlspecialchars(addslashes($u['username'])) ?>?');">Hapus</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/../views/layout/footer.php'; ?>
