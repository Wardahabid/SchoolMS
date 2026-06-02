<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /dbProject/auth/login.php');
    exit;
}
function hasRole(string ...$roles): bool {
    return in_array($_SESSION['role_name'], $roles);
}
function requireRole(string ...$roles): void {
    if (!hasRole(...$roles)) {
        http_response_code(403);
        die('<h1>403 — Access Denied</h1>');
    }
}
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void {
    if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token mismatch.');
    }
}
