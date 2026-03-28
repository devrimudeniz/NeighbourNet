<?php
/**
 * API: Send a DM reply to a story
 * POST: story_id, message
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/RateLimiter.php';

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'auth_required']);
    exit();
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$story_id = intval($_POST['story_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

// Validate inputs
if (!$story_id) {
    echo json_encode(['success' => false, 'error' => 'story_id required']);
    exit();
}

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit();
}

// Limit message length
if (mb_strlen($message) > 500) {
    echo json_encode(['success' => false, 'error' => 'Message too long (max 500 characters)']);
    exit();
}

// Rate limiting: max 10 story replies per minute
if (!RateLimiter::check($pdo, 'story_reply', 10, 60)) {
    echo json_encode(['success' => false, 'error' => 'Çok hızlı mesaj gönderiyorsunuz']);
    exit();
}

try {
    // Check if story exists and get owner
    $check = $pdo->prepare("SELECT id, user_id, media_url FROM stories WHERE id = ? AND expires_at > NOW()");
    $check->execute([$story_id]);
    $story = $check->fetch();
    
    if (!$story) {
        echo json_encode(['success' => false, 'error' => 'Story not found or expired']);
        exit();
    }
    
    $story_owner_id = $story['user_id'];
    
    // Can't reply to own story
    if ($story_owner_id == $user_id) {
        echo json_encode(['success' => false, 'error' => 'Kendi hikayenize yanıt veremezsiniz']);
        exit();
    }
    
    // Format message with story context
    $formatted_message = "📖 Hikayene yanıt:\n" . $message;
    
    // Insert message into messages table
    $insert = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, attachment_url, attachment_type) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // Include story thumbnail as context
    $attachment_url = $story['media_url'] ?? null;
    $attachment_type = $attachment_url ? 'story_reply' : null;
    
    $insert->execute([$user_id, $story_owner_id, $formatted_message, $attachment_url, $attachment_type]);
    $message_id = $pdo->lastInsertId();
    
    // Send notification to story owner
    $sender_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Birisi';
    $notif_content = $sender_name . ' hikayenize yanıt verdi';
    $notif_url = 'messages.php?to=' . $user_id;
    
    // Insert notification
    $notif = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'story_reply', ?, ?, ?)");
    $notif->execute([$story_owner_id, $user_id, $message_id, $notif_content, $notif_url]);
    
    // Send push notification
    try {
        sendPushNotification(
            $story_owner_id,
            'Hikaye Yanıtı 💬',
            $sender_name . ': ' . mb_substr($message, 0, 50) . (mb_strlen($message) > 50 ? '...' : ''),
            '/' . $notif_url
        );
    } catch (Exception $e) {
        error_log("Push notification failed for story reply: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message_id' => $message_id,
        'message' => 'Yanıtınız gönderildi'
    ]);
    
} catch (PDOException $e) {
    error_log("Story reply error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
