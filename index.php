<?php
require_once __DIR__ . '/includes/auth_check.php';

startAppSession();

$appUrl = $_ENV['APP_URL'] ?? '';

if (isLoggedIn()) {
    header("Location: {$appUrl}/pages/dashboard.php");
} else {
    header("Location: {$appUrl}/auth/login.php");
}
exit;