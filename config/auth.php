<?php
/**
 * Helper autentikasi & otorisasi (FR-01).
 * require di setiap halaman yang butuh login.
 */
require_once __DIR__ . '/config.php';

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function isAdmin(): bool
{
    return isLoggedIn() && $_SESSION['user']['role'] === 'admin';
}

/** Panggil di awal halaman yang wajib login (admin & viewer). */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

/** Panggil di awal halaman yang hanya boleh diakses admin. */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        require __DIR__ . '/../views/layout/forbidden.php';
        exit;
    }
}

/** Token CSRF sederhana untuk form POST. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Permintaan tidak valid (CSRF token tidak cocok). Silakan muat ulang halaman dan coba lagi.');
    }
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}
