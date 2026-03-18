<?php
/**
 * GET /api/fetch_videos.php?channel_id=UCxxxxx&page=1
 *
 * Returns paginated videos for the given channel as JSON.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/youtube_client.php'; // for formatDuration

startAppSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$channelId = trim($_GET['channel_id'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));

if ($channelId === '' || !preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $channelId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid channel ID.']);
    exit;
}

try {
    $db     = getDB();
    $limit  = VIDEOS_PER_PAGE;
    $offset = ($page - 1) * $limit;

    // Verify channel exists
    $stmt = $db->prepare("SELECT title FROM channels WHERE channel_id = :channel_id");
    $stmt->execute([':channel_id' => $channelId]);
    $channel = $stmt->fetch();

    if (!$channel) {
        echo json_encode(['success' => false, 'error' => 'Channel not found in database.']);
        exit;
    }

    // Total video count for this channel
    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE channel_id = :channel_id");
    $stmt->execute([':channel_id' => $channelId]);
    $total = (int)$stmt->fetchColumn();

    // Paginated videos
    $stmt = $db->prepare("
        SELECT video_id, title, thumbnail_url, published_at, duration, view_count, like_count
        FROM   videos
        WHERE  channel_id = :channel_id
        ORDER  BY published_at DESC
        LIMIT  :limit OFFSET :offset
    ");
    $stmt->bindValue(':channel_id', $channelId);
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $videos = $stmt->fetchAll();

    // Format duration for display
    foreach ($videos as &$v) {
        $v['duration_formatted'] = formatDuration($v['duration']);
        $v['view_count_formatted'] = number_format($v['view_count']);
    }

    echo json_encode([
        'success'      => true,
        'videos'       => $videos,
        'total'        => $total,
        'page'         => $page,
        'per_page'     => $limit,
        'total_pages'  => (int)ceil($total / $limit),
    ]);

} catch (Throwable $e) {
    error_log('Fetch videos error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load videos.']);
}