<?php
require_once __DIR__ . '/../config/env.php';
loadEnv(__DIR__ . '/../.env');

function startAppSession(): void
{
    $name = $_ENV['SESSION_NAME'] ?? 'yt_manager_session';
    if (session_status() === PHP_SESSION_NONE) {
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function requireLogin(): void
{
    startAppSession();
    if (empty($_SESSION['user'])) {
        header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/auth/login.php');
        exit;
    }
}

function currentUser(): ?array
{
    startAppSession();
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    startAppSession();
    return !empty($_SESSION['user']);
}