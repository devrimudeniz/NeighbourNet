<?php
/**
 * VAPID Keys for Web Push Notifications
 * 
 * These keys are used to authenticate push notifications.
 * Generate new keys using: https://web-push-codelab.glitch.me/
 * or using openssl commands.
 * 
 * IMPORTANT: Keep the private key secret!
 */
require_once __DIR__ . '/env.php';

// VAPID Keys - Updated based on user input
define('VAPID_PUBLIC_KEY', env_value('VAPID_PUBLIC_KEY', ''));
define('VAPID_PRIVATE_KEY', env_value('VAPID_PRIVATE_KEY', ''));
define('VAPID_SUBJECT', env_value('VAPID_SUBJECT', 'mailto:hello@example.com'));

// Base64 URL Safe encoding/decoding functions
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}
?>
