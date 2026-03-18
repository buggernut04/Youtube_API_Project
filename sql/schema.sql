-- YouTube Channel Manager Schema

CREATE DATABASE IF NOT EXISTS youtube_manager_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE youtube_manager_db;

-- Users table (Google OAuth)
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_id   VARCHAR(100)  NOT NULL UNIQUE,
    name        VARCHAR(150)  NOT NULL,
    email       VARCHAR(255)  NOT NULL UNIQUE,
    avatar_url  VARCHAR(500)  DEFAULT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_google_id (google_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- YouTube Channels table
CREATE TABLE IF NOT EXISTS channels (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel_id       VARCHAR(100)  NOT NULL UNIQUE,
    title            VARCHAR(255)  NOT NULL,
    description      TEXT          DEFAULT NULL,
    thumbnail_url    VARCHAR(500)  DEFAULT NULL,
    subscriber_count BIGINT UNSIGNED DEFAULT 0,
    video_count      INT UNSIGNED    DEFAULT 0,
    view_count       BIGINT UNSIGNED DEFAULT 0,
    published_at     DATETIME       DEFAULT NULL,
    synced_by        INT UNSIGNED   NOT NULL,
    synced_at        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (synced_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_synced_by (synced_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Videos table
CREATE TABLE IF NOT EXISTS videos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id      VARCHAR(20)   NOT NULL UNIQUE,
    channel_id    VARCHAR(100)  NOT NULL,
    title         VARCHAR(500)  NOT NULL,
    description   TEXT          DEFAULT NULL,
    thumbnail_url VARCHAR(500)  DEFAULT NULL,
    published_at  DATETIME      DEFAULT NULL,
    duration      VARCHAR(20)   DEFAULT NULL,
    view_count    BIGINT UNSIGNED DEFAULT 0,
    like_count    BIGINT UNSIGNED DEFAULT 0,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(channel_id) ON DELETE CASCADE,
    INDEX idx_video_id (video_id),
    INDEX idx_channel_id (channel_id),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;