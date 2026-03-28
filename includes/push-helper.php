<?php
/**
 * Web Push Notification Helper (PWA)
 * 
 * Uses VAPID + push_subscriptions table. OneSignal removed - PWA only.
 */
require_once __DIR__ . '/vapid_keys.php';

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    function sendPushNotification($user_id, $title, $body, $url = '/') {
        error_log("Web Push: composer not installed, skipping push to user $user_id");
        return ['success' => false, 'error' => 'Web Push library not available'];
    }
    function sendMassPush($title, $body, $url = '/') {
        error_log("Web Push: composer not installed, skipping mass push");
        return ['success' => false, 'count' => 0, 'error' => 'Web Push library not available'];
    }
    return;
}
require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Send Push Notification (Web Push - PWA)
 * 
 * @param int $user_id Recipient User ID
 * @param string $title Notification Title
 * @param string $body Notification Body
 * @param string $url Target URL (e.g. /messages, /feed.php#post-123)
 * @return array
 */
function sendPushNotification($user_id, $title, $body, $url = '/') {
    global $pdo;
    
    $results = ['web_push' => null];
    
    // Web Push (PWA - browsers & installed PWA)
    try {
        $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subscriptions)) {
            $results['web_push'] = ['success' => false, 'error' => 'No push subscription for user'];
        } else {
            $auth = [
                'VAPID' => [
                    'subject' => VAPID_SUBJECT,
                    'publicKey' => VAPID_PUBLIC_KEY,
                    'privateKey' => VAPID_PRIVATE_KEY
                ],
            ];

            $webPush = new WebPush($auth, ['reuse_vapid_headers' => true]);
            $icon = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com') . '/logo.jpg';

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'icon' => $icon
            ]);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'publicKey' => $sub['p256dh'],
                        'authToken' => $sub['auth'],
                    ]),
                    $payload
                );
            }

            $web_results = [];
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                if ($report->isSuccess()) {
                    $web_results[] = "Success: $endpoint";
                } else {
                    $web_results[] = "Failed: $endpoint - " . $report->getReason();
                    if ($report->isSubscriptionExpired()) {
                        $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                        $del->execute([$endpoint]);
                    }
                }
            }
            
            $results['web_push'] = ['success' => true, 'results' => $web_results];
        }
    } catch (Exception $e) {
        error_log("Web Push Error: " . $e->getMessage());
        $results['web_push'] = ['success' => false, 'error' => $e->getMessage()];
    }
    
    return [
        'success' => $results['web_push']['success'] ?? false,
        'details' => $results
    ];
}

/**
 * Direct Send (Testing)
 */
function sendWebPush($endpoint, $p256dh, $auth_token, $payload) {
    // This is a wrapper for the manual test script to work
    // We construct a one-off WebPush instance
    
    $auth = [
        'VAPID' => [
            'subject' => VAPID_SUBJECT,
            'publicKey' => VAPID_PUBLIC_KEY,
            'privateKey' => VAPID_PRIVATE_KEY
        ],
    ];

    $webPush = new WebPush($auth);
    
    $webPush->queueNotification(
        Subscription::create([
            'endpoint' => $endpoint,
            'publicKey' => $p256dh,
            'authToken' => $auth_token,
        ]),
        $payload
    );

    $results = [];
    foreach ($webPush->flush() as $report) {
         if ($report->isSuccess()) {
                $results['success'] = 1;
                $results['status'] = 201;
         } else {
                $results['success'] = 0;
                $results['error'] = $report->getReason();
         }
    }
    return $results;
}
/**
 * Send Mass Push Notification (To ALL Subscribers)
 */
function sendMassPush($title, $body, $url = '/') {
    global $pdo;

    try {
        // Fetch all unique subscriptions
        $stmt = $pdo->query("SELECT * FROM push_subscriptions");
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) return ['success' => true, 'count' => 0];

        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY
            ],
        ];

        $webPush = new WebPush($auth, ['reuse_vapid_headers' => true]);
        $icon = 'https://' . $_SERVER['HTTP_HOST'] . '/logo.jpg';
        
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $icon
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['p256dh'],
                    'authToken' => $sub['auth'],
                ]),
                $payload
            );
        }

        $webPush->flush();
        
        // Log mass push (optional but good for tracking)
        // file_put_contents('push_log.txt', date('Y-m-d H:i:s') . " Mass push sent: $title\n", FILE_APPEND);

        return ['success' => true, 'count' => count($subscriptions)];

    } catch (Exception $e) {
        error_log("Mass Push Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
