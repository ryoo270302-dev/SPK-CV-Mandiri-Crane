<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/CalcSession.php';

requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id && CalcSession::find($id)) {
    CalcSession::delete($id);
    flash('success', 'Riwayat sesi berhasil dihapus.');
} else {
    flash('error', 'Sesi tidak ditemukan.');
}
header('Location: ' . url('history/index.php'));
exit;
