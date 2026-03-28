-- Business Analytics Database Migration
-- Run this in phpMyAdmin or MySQL client

-- Profile Views Table - tracks who viewed which profile
CREATE TABLE IF NOT EXISTS profile_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_user_id INT NOT NULL COMMENT 'The profile being viewed',
    viewer_user_id INT NULL COMMENT 'Logged in viewer (NULL for guests)',
    viewer_ip VARCHAR(45) COMMENT 'IP address for unique visitor tracking',
    user_agent VARCHAR(500) NULL COMMENT 'Browser/device info',
    referrer VARCHAR(500) NULL COMMENT 'Where they came from',
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_profile_user (profile_user_id),
    INDEX idx_viewed_at (viewed_at),
    INDEX idx_viewer_ip (viewer_ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Stats Summary Table - for fast dashboard queries
CREATE TABLE IF NOT EXISTS profile_stats_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Profile owner',
    stat_date DATE NOT NULL,
    profile_views INT DEFAULT 0 COMMENT 'Total profile views',
    unique_visitors INT DEFAULT 0 COMMENT 'Unique visitors (by IP)',
    member_views INT DEFAULT 0 COMMENT 'Views from logged-in users',
    guest_views INT DEFAULT 0 COMMENT 'Views from guests',
    post_impressions INT DEFAULT 0 COMMENT 'Total post views',
    post_likes INT DEFAULT 0 COMMENT 'Total likes received',
    post_comments INT DEFAULT 0 COMMENT 'Total comments received',
    new_followers INT DEFAULT 0 COMMENT 'New friends/followers',
    UNIQUE KEY unique_user_date (user_id, stat_date),
    INDEX idx_user_id (user_id),
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add view_count column to users table if not exists
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS total_profile_views INT DEFAULT 0;
