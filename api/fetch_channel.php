<?php
/**
 * POST /api/fetch_channel.php
 * Body: { "channel_id": "UCxxxxxx" }
 *
 * Fetches channel info + videos from YouTube and stores them in the DB.
 * Returns JSON.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/youtube_client.php';
require_once __DIR__ . '/../config/database.php';

startAppSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true);
$channelId = trim($body['channel_id'] ?? '');

// Basic validation — YouTube channel IDs start with UC and are 24 chars
if ($channelId === '') {
    echo json_encode(['success' => false, 'error' => 'Channel ID is required.']);
    exit;
}
if (!preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $channelId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid Channel ID format.']);
    exit;
}

try {
    $db     = getDB();
    $userId = currentUser()['id'];

    // 1. Check if channel already exists in the database
    $stmt = $db->prepare("SELECT channel_id, title, synced_at FROM channels WHERE channel_id = :channel_id");
    $stmt->execute([':channel_id' => $channelId]);
    $existing = $stmt->fetch();
 
    if ($existing) {
        echo json_encode([
            'success'      => false,
            'already_exists' => true,
            'error'        => "Channel \"{$existing['title']}\" is already saved. It was last synced on " . date('M j, Y \a\t g:i A', strtotime($existing['synced_at'])) . ".",
        ]);
        exit;
    }

    // 2. Fetch channel info
    $channelInfo = fetchChannelInfo($channelId);
    if (!$channelInfo) {
        echo json_encode(['success' => false, 'error' => 'Channel not found. Please check the Channel ID.']);
        exit;
    }


    // 3. Insert channel
    $stmt = $db->prepare("
        INSERT INTO channels
            (channel_id, title, description, thumbnail_url, subscriber_count, video_count, view_count, published_at, synced_by)
        VALUES
            (:channel_id, :title, :description, :thumbnail_url, :subscriber_count, :video_count, :view_count, :published_at, :synced_by)
        ON DUPLICATE KEY UPDATE
            title            = VALUES(title),
            description      = VALUES(description),
            thumbnail_url    = VALUES(thumbnail_url),
            subscriber_count = VALUES(subscriber_count),
            video_count      = VALUES(video_count),
            view_count       = VALUES(view_count),
            published_at     = VALUES(published_at),
            synced_by        = VALUES(synced_by),
            synced_at        = CURRENT_TIMESTAMP
    ");
    $stmt->execute(array_merge($channelInfo, [':synced_by' => $userId]));

    // 4. Fetch videos
    $videos = fetchChannelVideos($channelId);

    $videoCount = 0;
    if (!empty($videos)) {
        $stmt = $db->prepare("
            INSERT IGNORE INTO videos
                (video_id, channel_id, title, description, thumbnail_url, published_at, duration, view_count, like_count)
            VALUES
                (:video_id, :channel_id, :title, :description, :thumbnail_url, :published_at, :duration, :view_count, :like_count)
        ");
        foreach ($videos as $video) {
            $stmt->execute([
                ':video_id'      => $video['video_id'],
                ':channel_id'    => $video['channel_id'],
                ':title'         => $video['title'],
                ':description'   => $video['description'],
                ':thumbnail_url' => $video['thumbnail_url'],
                ':published_at'  => $video['published_at'],
                ':duration'      => $video['duration'],
                ':view_count'    => $video['view_count'],
                ':like_count'    => $video['like_count'],
            ]);
        }
        $videoCount = count($videos);
    }

    echo json_encode([
        'success'      => true,
        'channel'      => $channelInfo,
        'video_count'  => $videoCount,
        'message'      => "Synced \"{$channelInfo['title']}\" with $videoCount videos.",
    ]);

} catch (Throwable $e) {
    error_log('Channel sync error: ' . $e->getMessage());
    $safe = preg_match('/quota|not found|invalid|API/i', $e->getMessage())
        ? $e->getMessage()
        : 'Sync failed. Please try again later.';
    echo json_encode(['success' => false, 'error' => $safe]);
}