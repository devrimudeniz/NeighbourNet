<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/optimize_upload.php';
require_once '../includes/security_helper.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_csrf();

$user_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? 0;
$message = trim($_POST['message'] ?? '');
$listing_id = !empty($_POST['listing_id']) ? $_POST['listing_id'] : null;

// Rate Limiting: Max 20 messages per 60 seconds
require_once '../includes/RateLimiter.php';
if (!RateLimiter::check($pdo, 'chat_msg', 20, 60)) {
    echo json_encode(['error' => 'Çok hızlı mesaj gönderiyorsunuz. Biraz yavaşlayın.']);
    exit;
}

if (!$receiver_id) {
    echo json_encode(['error' => 'No receiver']);
    exit();
}

$attachment_url = null;
$attachment_type = null;

// Handle File Upload
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
    if (in_array($ext, $allowed)) {
        $upload_dir = '../uploads/chat/';
        $result = gorselOptimizeEt($_FILES['file'], $upload_dir);
        
        if (isset($result['success'])) {
            $attachment_url = '/uploads/chat/' . $result['filename'];
            $attachment_type = 'image';
        }
    }
    }
}

if (!empty($message) || $attachment_url) {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, listing_id, message, attachment_url, attachment_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $receiver_id, $listing_id, $message, $attachment_url, $attachment_type]);
    
    $message_id = $pdo->lastInsertId();
    
    // Create in-app notification and send push
    try {
        $sender_name = $_SESSION['full_name'] ?? $_SESSION['username'];
        $notification_body = $attachment_url ? $sender_name . ' size bir fotoğraf gönderdi' : $sender_name . ': ' . mb_substr($message, 0, 50) . (mb_strlen($message) > 50 ? '...' : '');
        $notif_url = 'messages.php?user=' . $user_id;
        
        // Insert into database
        $ins_notif = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'message', ?, ?, ?)");
        $ins_notif->execute([$receiver_id, $user_id, $message_id, $notification_body, $notif_url]);
        
        // Send Push
        sendPushNotification(
            $receiver_id,
            'Yeni Mesaj 💬',
            $notification_body,
            '/' . $notif_url
        );
    } catch (Exception $e) {
        // Notification failed but message was saved, continue
        error_log("Push/Notification Failed for Receiver ID: $receiver_id. Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
    
    echo json_encode(['status' => 'success', 'id' => $message_id, 'attachment_url' => $attachment_url]);
} else {
    echo json_encode(['error' => 'Empty message']);
}
?>
