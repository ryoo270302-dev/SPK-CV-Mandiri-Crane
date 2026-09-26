<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/User.php';

requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id === currentUser()['id']) {
    flash('error', 'Anda tidak dapat menghapus akun sendiri.');
} elseif ($id && User::findById($id)) {
    User::delete($id);
    flash('success', 'Pengguna berhasil dihapus.');
} else {
    flash('error', 'Pengguna tidak ditemukan.');
}
header('Location: ' . url('users/index.php'));
exit;
