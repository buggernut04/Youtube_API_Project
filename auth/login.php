<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/google_client.php';

startAppSession();

// Already logged in → go to dashboard
if (isLoggedIn()) {
    header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/pages/dashboard.php');
    exit;
}

// Generate CSRF state token
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$authUrl = googleAuthUrl($state);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — YouTube Channel Manager</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-logo">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
        <rect width="48" height="48" rx="12" fill="#FF0033"/>
        <path d="M34 24L20 32V16L34 24Z" fill="white"/>
      </svg>
    </div>
    <h1 class="auth-title">YouTube Manager</h1>
    <p class="auth-subtitle">Sync and explore YouTube channels in one place</p>

    <a href="<?= htmlspecialchars($authUrl) ?>" class="btn-google">
      <svg width="20" height="20" viewBox="0 0 48 48">
        <path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.2l6.8-6.8C35.8 2.4 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.3l7.9 6.1C12.5 13.1 17.8 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v8.5h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4.1 6.9-10.1 7.1-17z"/>
        <path fill="#FBBC05" d="M10.5 28.6A14.6 14.6 0 0 1 9.5 24c0-1.6.3-3.2.8-4.6L2.4 13.3A23.8 23.8 0 0 0 0 24c0 3.8.9 7.4 2.6 10.6l7.9-6z"/>
        <path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7.5-5.8c-2 1.4-4.6 2.2-7.7 2.2-6.2 0-11.5-4.2-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/>
      </svg>
      Continue with Google
    </a>

    <?php if (!empty($_GET['error'])): ?>
    <p class="auth-error"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>