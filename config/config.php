<?php
/**
 * Konfigurasi aplikasi SPK Crane.
 * Ubah kredensial basis data di bawah sesuai instalasi XAMPP Anda.
 */

// --- Basis data ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'spk_crane');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Aplikasi ---
define('APP_NAME', 'SPK Crane - CV Mandiri Crane');
define('CR_THRESHOLD', 0.1); // batas maksimal Consistency Ratio (AC-03)
define('DECIMAL_PLACES', 4); // pembulatan hasil perhitungan (NFR-01)

// Base URL relatif project, dipakai oleh helper url() untuk membangun link.
// Jika folder project (di dalam htdocs) BUKAN "spk-crane", ubah nilai ini.
// Contoh: jalan di http://localhost/spk-crane/ -> '/spk-crane'
//         jalan di document root http://localhost/          -> ''
define('BASE_URL', '/spk-crane');

/** Membangun URL relatif terhadap root project, contoh: url('crane/index.php') */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
