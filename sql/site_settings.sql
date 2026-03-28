CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Kalkan Social'),
('site_short_name', 'KalkanSocial'),
('site_tagline_tr', 'Topluluk ve etkinlik platformu'),
('site_tagline_en', 'Community and events platform'),
('support_email', 'hello@example.com'),
('contact_phone', ''),
('app_url', 'http://localhost/kalkansocial')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
