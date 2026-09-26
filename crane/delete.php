<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Crane.php';

requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id && Crane::find($id)) {
    Crane::delete($id);
    flash('success', 'Data crane berhasil dihapus.');
} else {
    flash('error', 'Data crane tidak ditemukan.');
}
header('Location: ' . url('crane/index.php'));
exit;
