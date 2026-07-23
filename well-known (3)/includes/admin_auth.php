<?php
declare(strict_types=1);

const AQUA_ADMIN_USERNAME = 'admin';
const AQUA_ADMIN_PASSWORD = 'admin123!';

function isAdminAuthenticated(): bool
{
    return !empty($_SESSION['is_admin']) && ($_SESSION['admin_username'] ?? '') === AQUA_ADMIN_USERNAME;
}

function requireAdminAuthentication(): void
{
    if (!isAdminAuthenticated()) {
        header('Location: admin-login.php');
        exit;
    }
}

function loginAdmin(string $username, string $password): bool
{
    if ($username !== AQUA_ADMIN_USERNAME || $password !== AQUA_ADMIN_PASSWORD) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_username'] = AQUA_ADMIN_USERNAME;

    return true;
}

function logoutAdmin(): void
{
    unset($_SESSION['is_admin'], $_SESSION['admin_username'], $_SESSION['admin_csrf_token']);
}

function getAdminCsrfToken(): string
{
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['admin_csrf_token'];
}

function verifyAdminCsrfToken(string $token): bool
{
    return hash_equals((string)getAdminCsrfToken(), $token);
}
