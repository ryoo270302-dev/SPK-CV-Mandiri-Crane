<?php
/**
 * Koneksi basis data menggunakan PDO + prepared statement (NFR-02).
 */
require_once __DIR__ . '/config.php';

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi basis data gagal. Pastikan MySQL (XAMPP) berjalan dan '
                . 'database "spk_crane" sudah diimport. Detail: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
