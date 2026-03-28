<?php
/**
 * OneSignal Push Notification Helper
 * 
 * Sends push notifications via OneSignal REST API
 */
require_once __DIR__ . '/env.php';

// OneSignal Configuration
define('ONESIGNAL_APP_ID', env_value('ONESIGNAL_APP_ID', ''));
define('ONESIGNAL_REST_API_KEY', env_value('ONESIGNAL_REST_API_KEY', ''));

/**
 * Send OneSignal notification to a specific user
 * 
 * @param int $user_id Target user ID
 * @param string $title Notification title
 * @param string $body Notification body
 * @param string $url Target URL when clicked
 * @param array $data Additional data payload
 * @return array Result with success status
 */
function sendOneSignalNotification($user_id, $title, $body, $url = '/', $data = []) {
    global $pdo;
    
    try {
        // Get user's OneSignal Player ID
        $stmt = $pdo->prepare("SELECT onesignal_player_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $player_id = $stmt->fetchColumn();
        
        if (empty($player_id)) {
            return ['success' => false, 'error' => 'User has no OneSignal player ID'];
        }
        
        return sendOneSignalToPlayerIds([$player_id], $title, $body, $url, $data);
        
    } catch (Exception $e) {
        error_log("OneSignal Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send OneSignal notification to specific Player IDs
 * 
 * @param array $player_ids Array of OneSignal Player IDs
 * @param string $title Notification title
 * @param string $body Notification body
 * @param string $url Target URL when clicked
 * @param array $data Additional data payload
 * @return array Result with success status
 */
function sendOneSignalToPlayerIds($player_ids, $title, $body, $url = '/', $data = []) {
    if (empty($player_ids)) {
        return ['success' => false, 'error' => 'No player IDs provided'];
    }
    
    // Filter out empty values
    $player_ids = array_filter($player_ids);
    if (empty($player_ids)) {
        return ['success' => false, 'error' => 'All player IDs were empty'];
    }
    
    $payload = [
        'app_id' => ONESIGNAL_APP_ID,
        'include_player_ids' => array_values($player_ids),
        'headings' => ['en' => $title],
        'contents' => ['en' => $body],
        'url' => app_url() . $url,
        'data' => array_merge(['url' => $url], $data),
        'large_icon' => app_url() . '/icon-192.png'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://onesignal.com/api/v1/notifications',
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . ONESIGNAL_REST_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log("OneSignal cURL Error: " . $curl_error);
        return ['success' => false, 'error' => $curl_error];
    }
    
    $result = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300) {
        return [
            'success' => true, 
            'recipients' => $result['recipients'] ?? 0,
            'id' => $result['id'] ?? null
        ];
    } else {
        error_log("OneSignal API Error: HTTP $http_code - " . $response);
        return [
            'success' => false, 
            'error' => $result['errors'][0] ?? "HTTP $http_code",
            'response' => $result
        ];
    }
}

/**
 * Send mass notification to all OneSignal subscribers
 * 
 * @param string $title Notification title
 * @param string $body Notification body  
 * @param string $url Target URL
 * @param array $filters Optional segments/filters
 * @return array Result
 */
function sendOneSignalMass($title, $body, $url = '/', $filters = []) {
    $payload = [
        'app_id' => ONESIGNAL_APP_ID,
        'included_segments' => ['Subscribed Users'],
        'headings' => ['en' => $title],
        'contents' => ['en' => $body],
        'url' => app_url() . $url,
        'data' => ['url' => $url],
        'large_icon' => app_url() . '/icon-192.png'
    ];
    
    if (!empty($filters)) {
        unset($payload['included_segments']);
        $payload['filters'] = $filters;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://onesignal.com/api/v1/notifications',
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . ONESIGNAL_REST_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $http_code >= 200 && $http_code < 300,
        'recipients' => $result['recipients'] ?? 0,
        'response' => $result
    ];
}
?>
