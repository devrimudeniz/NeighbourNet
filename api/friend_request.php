<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/security_helper.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

require_csrf();

// Rate Limiting: Max 10 friend requests per 60 seconds
require_once '../includes/RateLimiter.php';
if (!RateLimiter::check($pdo, 'friend_req', 10, 60)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Çok fazla istek gönderdiniz. Lütfen 1 dakika bekleyin. (Rate Limit)'
    ]);
    exit;
}

$requester_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Validation
if ($receiver_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit;
}

if ($requester_id == $receiver_id) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot send request to yourself']);
    exit;
}

try {
    // Check if receiver exists
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    $receiver = $stmt->fetch();
    
    if (!$receiver) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    
    // Check for existing friendship (in either direction)
    $stmt = $pdo->prepare("
        SELECT status FROM friendships 
        WHERE (requester_id = ? AND receiver_id = ?)
           OR (requester_id = ? AND receiver_id = ?)
    ");
    $stmt->execute([$requester_id, $receiver_id, $receiver_id, $requester_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['status'] == 'accepted') {
            echo json_encode(['status' => 'error', 'message' => 'Already friends']);
            exit;
        } elseif ($existing['status'] == 'pending') {
            echo json_encode(['status' => 'error', 'message' => 'Request already pending']);
            exit;
        }
        // If declined, allow re-requesting (we'll update the record)
    }
    
    // Get requester info for notification
    $stmt = $pdo->prepare("SELECT full_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$requester_id]);
    $requester = $stmt->fetch();
    
    // Insert or update friendship request
    if ($existing && $existing['status'] == 'declined') {
        // Update declined request to pending
        $stmt = $pdo->prepare("
            UPDATE friendships 
            SET status = 'pending', requester_id = ?, receiver_id = ?, updated_at = NOW()
            WHERE (requester_id = ? AND receiver_id = ?)
               OR (requester_id = ? AND receiver_id = ?)
        ");
        $stmt->execute([$requester_id, $receiver_id, $requester_id, $receiver_id, $receiver_id, $requester_id]);
    } else {
        // Create new request
        $stmt = $pdo->prepare("
            INSERT INTO friendships (requester_id, receiver_id, status) 
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$requester_id, $receiver_id]);
    }
    
    // Send in-app notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) 
        VALUES (?, ?, 'friend_request', ?, ?, ?)
    ");
    $notification_text = $requester['full_name'] . " size arkadaşlık isteği gönderdi";
    $stmt->execute([
        $receiver_id,
        $requester_id, // actor_id
        $requester_id,
        $notification_text,
        'friend_requests.php'
    ]);
    
    // Send push notification
    sendPushNotification(
        $receiver_id,
        'Arkadaşlık İsteği 👋',
        $notification_text,
        '/notifications'
    );
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Friend request sent'
    ]);
    
} catch (PDOException $e) {
    error_log("Friend request error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'status' => 'error', 
        'message' => 'An error occurred'
    ]);
}
?>
