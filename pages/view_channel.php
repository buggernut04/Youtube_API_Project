<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$channelId = trim($_GET['channel_id'] ?? '');

if ($channelId === '' || !preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $channelId)) {
    header('Location: dashboard.php?error=invalid_channel');
    exit;
}

$db   = getDB();
$user = currentUser();

$stmt = $db->prepare("SELECT * FROM channels WHERE channel_id = :channel_id");
$stmt->execute([':channel_id' => $channelId]);
$channel = $stmt->fetch();

if (!$channel) {
    header('Location: dashboard.php?error=channel_not_found');
    exit;
}

$appUrl = $_ENV['APP_URL'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($channel['title']) ?> — YouTube Channel Manager</title>
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

  <!-- Back -->
  <a href="dashboard.php" class="back-link">← All Channels</a>

  <!-- Channel Header -->
  <div class="channel-header">
    <img src="<?= htmlspecialchars($channel['thumbnail_url']) ?>" alt="<?= htmlspecialchars($channel['title']) ?>" class="channel-header-thumb">
    <div class="channel-header-info">
      <h1 class="channel-header-title"><?= htmlspecialchars($channel['title']) ?></h1>
      <div class="channel-stats">
        <div class="stat"><span class="stat-val"><?= number_format($channel['subscriber_count']) ?></span><span class="stat-lbl">Subscribers</span></div>
        <div class="stat"><span class="stat-val"><?= number_format($channel['video_count']) ?></span><span class="stat-lbl">Total Videos</span></div>
        <div class="stat"><span class="stat-val"><?= number_format($channel['view_count']) ?></span><span class="stat-lbl">Total Views</span></div>
      </div>
      <?php if ($channel['description']): ?>
      <p class="channel-desc"><?= nl2br(htmlspecialchars(mb_strimwidth($channel['description'], 0, 300, '…'))) ?></p>
      <?php endif; ?>
      <a href="https://youtube.com/channel/<?= urlencode($channel['channel_id']) ?>" target="_blank" rel="noopener" class="btn-yt">View on YouTube ↗</a>
    </div>
  </div>

  <!-- Videos Section -->
  <div class="section-header">
    <h2>Videos</h2>
    <span id="videoCount" class="badge"></span>
  </div>

  <div id="videoError" class="alert alert-error" hidden></div>

  <div id="videoGrid" class="video-grid">
    <div class="loading-row">Loading videos…</div>
  </div>

  <!-- Pagination -->
  <div id="pagination" class="pagination"></div>
  <div id="channelInfo" data-channel-id="<?= htmlspecialchars($channelId, ENT_QUOTES) ?>"></div>

</main>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/view_channel.js">
//   let currentPage  = 1;

//   async function loadVideos(page) {
//     currentPage = page;
//     const grid   = document.getElementById('videoGrid');
//     const errEl  = document.getElementById('videoError');
//     const pagEl  = document.getElementById('pagination');
//     const cntEl  = document.getElementById('videoCount');

//     hideEl(errEl);
//     grid.innerHTML = '<div class="loading-row">Loading…</div>';
//     pagEl.innerHTML = '';

//     try {
//       const res  = await fetch(`../api/fetch_videos.php?channel_id=${encodeURIComponent(CHANNEL_ID)}&page=${page}`);
//       const data = await res.json();

//       if (!data.success) { showError(errEl, data.error || 'Failed to load videos.'); grid.innerHTML = ''; return; }

//       cntEl.textContent = data.total + ' video' + (data.total !== 1 ? 's' : '') + ' saved';

//       if (data.videos.length === 0) {
//         grid.innerHTML = '<p class="empty-videos">No videos found for this channel.</p>';
//         return;
//       }

//       grid.innerHTML = data.videos.map(v => `
//         <a href="https://youtube.com/watch?v=${escHtml(v.video_id)}" target="_blank" rel="noopener" class="video-card">
//           <div class="video-thumb-wrap">
//             <img src="${escHtml(v.thumbnail_url)}" alt="${escHtml(v.title)}" class="video-thumb" loading="lazy">
//             <span class="video-duration">${escHtml(v.duration_formatted)}</span>
//           </div>
//           <div class="video-meta">
//             <h3 class="video-title">${escHtml(v.title)}</h3>
//             <div class="video-stats">
//               <span>${escHtml(v.view_count_formatted)} views</span>
//               <span class="dot">·</span>
//               <span>${formatDate(v.published_at)}</span>
//             </div>
//           </div>
//         </a>
//       `).join('');

//       renderPagination(pagEl, data.page, data.total_pages);

//     } catch (e) {
//       showError(errEl, 'Network error. Please try again.');
//       grid.innerHTML = '';
//     }
//   }

//   function renderPagination(el, page, total) {
//     if (total <= 1) return;
//     let html = '';
//     if (page > 1) html += `<button class="page-btn" onclick="loadVideos(${page - 1})">← Prev</button>`;

//     // Show up to 7 page buttons
//     const start = Math.max(1, page - 3);
//     const end   = Math.min(total, page + 3);
//     if (start > 1) html += `<button class="page-btn" onclick="loadVideos(1)">1</button><span class="page-ellipsis">…</span>`;
//     for (let p = start; p <= end; p++) {
//       html += `<button class="page-btn ${p === page ? 'active' : ''}" onclick="loadVideos(${p})">${p}</button>`;
//     }
//     if (end < total) html += `<span class="page-ellipsis">…</span><button class="page-btn" onclick="loadVideos(${total})">${total}</button>`;
//     if (page < total) html += `<button class="page-btn" onclick="loadVideos(${page + 1})">Next →</button>`;

//     el.innerHTML = html;
//   }

//   loadVideos(1);
</script>

</body>
</html>