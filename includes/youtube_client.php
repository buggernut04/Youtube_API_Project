<?php
require_once __DIR__ . '/../config/youtube.php';

/**
 * Make a GET request to the YouTube Data API v3.
 * Returns decoded JSON array or throws on error.
 */
function youtubeGet(string $endpoint, array $params = []): array
{
    $params['key'] = YOUTUBE_API_KEY;
    $url = YOUTUBE_API_BASE_URL . '/' . $endpoint . '?' . http_build_query($params);

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'header'        => "Accept: application/json\r\n",
            'timeout'       => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);

    if ($response === false) {
        throw new RuntimeException('YouTube API request failed. Check your network or API key.');
    }

    $data = json_decode($response, true);

    if (isset($data['error'])) {
        $msg    = $data['error']['message'] ?? 'Unknown YouTube API error';
        $reason = $data['error']['errors'][0]['reason'] ?? '';

        if ($reason === 'quotaExceeded' || $reason === 'dailyLimitExceeded') {
            throw new RuntimeException('YouTube API quota exceeded. Please try again later.');
        }
        throw new RuntimeException("YouTube API error: $msg");
    }

    return $data;
}

/**
 * Fetch basic channel information by channel ID.
 * Returns a single channel array or null if not found.
 */
function fetchChannelInfo(string $channelId): ?array
{
    $data = youtubeGet('channels', [
        'part' => 'snippet,statistics',
        'id'   => $channelId,
    ]);

    if (empty($data['items'])) {
        return null;
    }

    $item  = $data['items'][0];
    $snip  = $item['snippet'];
    $stats = $item['statistics'] ?? [];

    return [
        'channel_id'       => $item['id'],
        'title'            => $snip['title']              ?? '',
        'description'      => $snip['description']        ?? '',
        'thumbnail_url'    => $snip['thumbnails']['high']['url'] ?? ($snip['thumbnails']['default']['url'] ?? ''),
        'subscriber_count' => (int)($stats['subscriberCount'] ?? 0),
        'video_count'      => (int)($stats['videoCount']      ?? 0),
        'view_count'       => (int)($stats['viewCount']       ?? 0),
        'published_at'     => date('Y-m-d H:i:s', strtotime($snip['publishedAt'] ?? 'now')),
    ];
}

/**
 * Fetch up to MAX_VIDEOS_TO_FETCH videos for a channel.
 * Uses the search endpoint (by channelId) then retrieves full video details.
 *
 * @return array  List of video data arrays.
 */
function fetchChannelVideos(string $channelId): array
{
    $videoIds  = [];
    $pageToken = null;
    $remaining = MAX_VIDEOS_TO_FETCH;

    // Step 1 — collect video IDs via search
    while ($remaining > 0) {
        $params = [
            'part'       => 'id',
            'channelId'  => $channelId,
            'type'       => 'video',
            'order'      => 'date',
            'maxResults' => min(50, $remaining),
        ];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $data = youtubeGet('search', $params);

        foreach ($data['items'] ?? [] as $item) {
            if (isset($item['id']['videoId'])) {
                $videoIds[] = $item['id']['videoId'];
            }
        }

        $remaining -= count($data['items'] ?? []);
        $pageToken  = $data['nextPageToken'] ?? null;

        if (!$pageToken || $remaining <= 0) {
            break;
        }
    }

    if (empty($videoIds)) {
        return [];
    }

    // Step 2 — fetch full details in batches of 50
    $videos = [];
    foreach (array_chunk($videoIds, 50) as $chunk) {
        $data = youtubeGet('videos', [
            'part' => 'snippet,contentDetails,statistics',
            'id'   => implode(',', $chunk),
        ]);

        foreach ($data['items'] ?? [] as $item) {
            $snip  = $item['snippet'];
            $stats = $item['statistics'] ?? [];

            $videos[] = [
                'video_id'      => $item['id'],
                'channel_id'    => $channelId,
                'title'         => $snip['title']       ?? '',
                'description'   => $snip['description'] ?? '',
                'thumbnail_url' => $snip['thumbnails']['medium']['url'] ?? ($snip['thumbnails']['default']['url'] ?? ''),
                'published_at'  => date('Y-m-d H:i:s', strtotime($snip['publishedAt'] ?? 'now')),
                'duration'      => $item['contentDetails']['duration'] ?? '',
                'view_count'    => (int)($stats['viewCount'] ?? 0),
                'like_count'    => (int)($stats['likeCount'] ?? 0),
            ];
        }
    }

    return $videos;
}

/**
 * Convert ISO 8601 duration (PT4M13S) to human-readable (4:13).
 */
function formatDuration(string $iso): string
{
    if (!$iso) return '0:00';
    preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $iso, $m);
    $h = (int)($m[1] ?? 0);
    $i = (int)($m[2] ?? 0);
    $s = (int)($m[3] ?? 0);
    if ($h > 0) {
        return sprintf('%d:%02d:%02d', $h, $i, $s);
    }
    return sprintf('%d:%02d', $i, $s);
}