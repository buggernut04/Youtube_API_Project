<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$user = currentUser();
$db   = getDB();

// Fetch all channels from DB
$stmt = $db->query("
    SELECT c.*, u.name AS synced_by_name
    FROM   channels c
    JOIN   users u ON u.id = c.synced_by
    ORDER  BY c.synced_at DESC
");
$channels = $stmt->fetchAll();

$appUrl = $_ENV['APP_URL'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — YouTube Channel Manager</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <a href="dashboard.php" class="nav-brand">
    <svg width="28" height="28" viewBox="0 0 48 48" fill="none">
      <rect width="48" height="48" rx="10" fill="#FF0033"/>
      <path d="M34 24L20 32V16L34 24Z" fill="white"/>
    </svg>
    <span>YT Manager</span>
  </a>
  <div class="nav-user">
    <?php if ($user['avatar_url']): ?>
    <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="avatar" class="nav-avatar">
    <?php endif; ?>
    <span class="nav-name"><?= htmlspecialchars($user['name']) ?></span>
    <a href="../auth/logout.php" class="btn-link">Sign out</a>
  </div>
</nav>

<main class="container">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Channels</h1>
      <p class="page-sub"><?= count($channels) ?> channel<?= count($channels) !== 1 ? 's' : '' ?> saved</p>
    </div>
    <button class="btn-primary" id="openSyncModal">+ Sync Channel</button>
  </div>

  <!-- Channel Cards -->
  <?php if (empty($channels)): ?>
  <div class="empty-state">
    <svg width="64" height="64" viewBox="0 0 48 48" fill="none" opacity=".25">
      <rect width="48" height="48" rx="10" fill="#FF0033"/>
      <path d="M34 24L20 32V16L34 24Z" fill="white"/>
    </svg>
    <p>No channels yet. Sync your first channel to get started.</p>
    <button class="btn-primary" id="openSyncModal2">Sync a Channel</button>
  </div>
  <?php else: ?>
  <div class="channel-grid">
    <?php foreach ($channels as $ch): ?>
    <a href="view_channel.php?channel_id=<?= urlencode($ch['channel_id']) ?>" class="channel-card">
      <img src="<?= htmlspecialchars($ch['thumbnail_url']) ?>" alt="<?= htmlspecialchars($ch['title']) ?>" class="channel-thumb">
      <div class="channel-info">
        <h3 class="channel-name"><?= htmlspecialchars($ch['title']) ?></h3>
        <div class="channel-meta">
          <span><?= number_format($ch['subscriber_count']) ?> subscribers</span>
          <span class="dot">·</span>
          <span><?= number_format($ch['video_count']) ?> videos</span>
        </div>
        <p class="channel-synced">Synced <?= date('M j, Y', strtotime($ch['synced_at'])) ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>

<!-- Sync Modal -->
<div class="modal-overlay" id="syncModal" hidden>
  <div class="modal">
    <div class="modal-header">
      <h2>Sync a YouTube Channel</h2>
      <button class="modal-close" id="closeSyncModal">&times;</button>
    </div>
    <p class="modal-hint">Enter the YouTube Channel ID (e.g. <code>UCVHFbw7woebKtfvUT9Xj0jg</code>).</p>
    <div class="form-group">
      <input type="text" id="channelIdInput" placeholder="Channel ID" class="form-input" maxlength="64">
    </div>
    <div id="syncError" class="alert alert-error" hidden></div>
    <div id="syncSuccess" class="alert alert-success" hidden></div>
    <div class="modal-actions">
      <button class="btn-secondary" id="cancelSync">Cancel</button>
      <button class="btn-primary" id="doSync">
        <span id="syncLabel">Sync</span>
      </button>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/dashboard.js"></script>

</body>
</html>