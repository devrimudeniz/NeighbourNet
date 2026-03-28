<?php
/**
 * Central site settings helper.
 */

require_once __DIR__ . '/env.php';

if (!function_exists('default_site_settings')) {
    function default_site_settings() {
        $appName = (string) env_value('SITE_NAME', 'Kalkan Social');
        $shortName = (string) env_value('SITE_SHORT_NAME', preg_replace('/\s+/', '', $appName) ?: 'KalkanSocial');

        return [
            'site_name' => $appName,
            'site_short_name' => $shortName,
            'site_tagline_tr' => (string) env_value('SITE_TAGLINE_TR', 'Topluluk ve etkinlik platformu'),
            'site_tagline_en' => (string) env_value('SITE_TAGLINE_EN', 'Community and events platform'),
            'support_email' => (string) env_value('SUPPORT_EMAIL', 'hello@example.com'),
            'contact_phone' => (string) env_value('CONTACT_PHONE', ''),
            'app_url' => app_url(),
        ];
    }
}

if (!function_exists('ensure_site_settings_table')) {
    function ensure_site_settings_table($pdo) {
        static $ensured = false;

        if ($ensured || !$pdo) {
            return;
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $defaults = default_site_settings();
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = COALESCE(site_settings.setting_value, VALUES(setting_value))
        ");

        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $ensured = true;
    }
}

if (!function_exists('load_site_settings')) {
    function load_site_settings($pdo = null) {
        static $loaded = false;
        static $settings = [];

        if ($loaded) {
            return $settings;
        }

        $settings = default_site_settings();

        $pdo = $pdo ?: ($GLOBALS['pdo'] ?? null);
        if ($pdo) {
            try {
                ensure_site_settings_table($pdo);

                $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
                if (is_array($rows)) {
                    foreach ($rows as $key => $value) {
                        $settings[$key] = (string) $value;
                    }
                }
            } catch (Exception $e) {
                // Fall back to defaults when database is not ready.
            }
        }

        $GLOBALS['site_settings'] = $settings;
        $loaded = true;

        return $settings;
    }
}

if (!function_exists('site_setting')) {
    function site_setting($key, $default = '') {
        if (isset($GLOBALS['site_settings']) && array_key_exists($key, $GLOBALS['site_settings'])) {
            return $GLOBALS['site_settings'][$key];
        }

        $settings = load_site_settings($GLOBALS['pdo'] ?? null);
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }
}

if (!function_exists('site_name')) {
    function site_name() {
        return site_setting('site_name', 'Kalkan Social');
    }
}

if (!function_exists('site_short_name')) {
    function site_short_name() {
        return site_setting('site_short_name', preg_replace('/\s+/', '', site_name()));
    }
}

if (!function_exists('site_tagline')) {
    function site_tagline($lang = 'en') {
        $key = $lang === 'tr' ? 'site_tagline_tr' : 'site_tagline_en';
        return site_setting($key, '');
    }
}

if (!function_exists('site_title')) {
    function site_title($lang = 'en') {
        $tagline = trim(site_tagline($lang));
        return $tagline !== '' ? site_name() . ' | ' . $tagline : site_name();
    }
}

if (!function_exists('site_support_email')) {
    function site_support_email() {
        return site_setting('support_email', 'hello@example.com');
    }
}
