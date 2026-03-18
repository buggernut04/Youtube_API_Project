<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/google_client.php';
require_once __DIR__ . '/../config/database.php';

startAppSession();

$appUrl = $_ENV['APP_URL'] ?? '';

// Validate state (CSRF protection)
if (
    empty($_GET['state']) ||
    empty($_SESSION['oauth_state']) ||
    !hash_equals($_SESSION['oauth_state'], $_GET['state'])
) {
    unset($_SESSION['oauth_state']);
    header("Location: {$appUrl}/auth/login.php?error=" . urlencode('Invalid OAuth state. Please try again.'));
    exit;
}

unset($_SESSION['oauth_state']);

// Handle user cancellation or access denial
if (!empty($_GET['error'])) {
    $err = htmlspecialchars($_GET['error']);
    header("Location: {$appUrl}/auth/login.php?error=" . urlencode("Google login failed: $err"));
    exit;
}

if (empty($_GET['code'])) {
    header("Location: {$appUrl}/auth/login.php?error=" . urlencode('No authorization code received.'));
    exit;
}

try {
    $tokenData = exchangeCodeForToken($_GET['code']);
    $googleUser = fetchGoogleUserInfo($tokenData['access_token']);

    $db = getDB();

    // Upsert user record
    $stmt = $db->prepare("
        INSERT INTO users (google_id, name, email, avatar_url)
        VALUES (:google_id, :name, :email, :avatar_url)
        ON DUPLICATE KEY UPDATE
            name       = VALUES(name),
            email      = VALUES(email),
            avatar_url = VALUES(avatar_url),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':google_id'  => $googleUser['sub'],
        ':name'       => $googleUser['name']    ?? 'Unknown',
        ':email'      => $googleUser['email']   ?? '',
        ':avatar_url' => $googleUser['picture'] ?? null,
    ]);

    // Fetch the persisted user row
    $stmt = $db->prepare("SELECT * FROM users WHERE google_id = :google_id");
    $stmt->execute([':google_id' => $googleUser['sub']]);
    $user = $stmt->fetch();

    // Store in session
    $_SESSION['user'] = [
        'id'         => $user['id'],
        'google_id'  => $user['google_id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'avatar_url' => $user['avatar_url'],
    ];

    session_regenerate_id(true);

    header("Location: {$appUrl}/pages/dashboard.php");
    exit;

} catch (Throwable $e) {
    error_log('OAuth callback error: ' . $e->getMessage());
    header("Location: {$appUrl}/auth/login.php?error=" . urlencode('Authentication failed. Please try again.'));
    exit;
}