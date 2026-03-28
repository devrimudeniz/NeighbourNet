<?php
// Visitor Tracking Logic for Kalkan Social

function trackVisitor($pdo) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $current_page = $_SERVER['REQUEST_URI'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Basic Bot Detection
    $bot_list = [
        'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'Sogou', 'Exabot', 'facebot', 'facebookexternalhit', 'ia_archiver', 'Sitemap', 'Crawler'
    ];
    
    $is_bot = 0;
    $bot_name = null;
    foreach ($bot_list as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            $is_bot = 1;
            $bot_name = $bot;
            break;
        }
    }

    // Basic Device Detection
    $device_type = 'desktop';
    if ($is_bot) {
        $device_type = 'bot';
    } elseif (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        $device_type = 'tablet';
    } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
        $device_type = 'mobile';
    }

    // Country Detection
    $country_code = null;
    
    // 1. Try Cloudflare header first
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY']) && $_SERVER['HTTP_CF_IPCOUNTRY'] !== 'XX') {
        $country_code = $_SERVER['HTTP_CF_IPCOUNTRY'];
    }
    
    // 2. Try ip-api.com for free geo lookup (with caching)
    if (empty($country_code) || $country_code === '??') {
        // Check cache first (store in session to avoid repeated API calls)
        if (isset($_SESSION['visitor_country']) && !empty($_SESSION['visitor_country'])) {
            $country_code = $_SESSION['visitor_country'];
        } else {
            // Only do API lookup for new sessions, not local IPs
            $is_local_ip = in_array($ip_address, ['127.0.0.1', '::1', 'localhost']) || 
                           strpos($ip_address, '192.168.') === 0 || 
                           strpos($ip_address, '10.') === 0;
            
            if (!$is_local_ip) {
                try {
                    $geo_url = "http://ip-api.com/json/{$ip_address}?fields=countryCode";
                    $context = stream_context_create(['http' => ['timeout' => 2]]);
                    $geo_response = @file_get_contents($geo_url, false, $context);
                    if ($geo_response) {
                        $geo_data = json_decode($geo_response, true);
                        if (!empty($geo_data['countryCode'])) {
                            $country_code = $geo_data['countryCode'];
                            $_SESSION['visitor_country'] = $country_code;
                        }
                    }
                } catch (Exception $e) {
                    // Fail silently
                }
            } else {
                $country_code = 'TR'; // Default for local development
            }
        }
    }
    
    // Fallback
    if (empty($country_code)) {
        $country_code = '??';
    }

    try {
        // Upsert logic (Update if exists for this session, otherwise insert)
        // We use session_id to track unique visitors over time
        $stmt = $pdo->prepare("SELECT id FROM visitor_activity WHERE session_id = ? LIMIT 1");
        $stmt->execute([$session_id]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE visitor_activity SET 
                user_id = ?, 
                current_page = ?, 
                last_activity = CURRENT_TIMESTAMP 
                WHERE id = ?");
            $stmt->execute([$user_id, $current_page, $existing]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO visitor_activity 
                (session_id, user_id, ip_address, user_agent, current_page, referer, country_code, is_bot, bot_name, device_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$session_id, $user_id, $ip_address, $user_agent, $current_page, $referer, $country_code, $is_bot, $bot_name, $device_type]);
        }

        // Cleanup: Remove records older than 1 hour (keep it lean)
        // Only run cleanup periodically or on every hit (it's small enough now)
        if (mt_rand(1, 10) === 1) { // 10% chance to run cleanup to save resources
            $pdo->exec("DELETE FROM visitor_activity WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        }

    } catch (PDOException $e) {
        // Fail silently in production to avoid breaking pages
        error_log("Visitor Tracker Error: " . $e->getMessage());
    }
}

// Execute tracking
if (isset($pdo)) {
    trackVisitor($pdo);
}
?>
