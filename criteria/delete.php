<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Criterion.php';

requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id && Criterion::find($id)) {
    Criterion::delete($id);
    flash('success', 'Kriteria berhasil dihapus.');
} else {
    flash('error', 'Kriteria tidak ditemukan.');
}
header('Location: ' . url('criteria/index.php'));
exit;
