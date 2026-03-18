<?php
require_once __DIR__ . '/../includes/auth_check.php';

startAppSession();
$_SESSION = [];
session_destroy();

$appUrl = $_ENV['APP_URL'] ?? '';
header("Location: {$appUrl}/auth/login.php");
exit;