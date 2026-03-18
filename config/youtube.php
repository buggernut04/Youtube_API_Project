<?php
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

define('YOUTUBE_API_KEY',      $_ENV['YOUTUBE_API_KEY'] ?? '');
define('YOUTUBE_API_BASE_URL', 'https://www.googleapis.com/youtube/v3');
define('VIDEOS_PER_PAGE',      20);
define('MAX_VIDEOS_TO_FETCH',  100);