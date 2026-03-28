<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/security_helper.php';

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

$user_id = $_SESSION['user_id'];
$requester_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : ''; // 'accept' or 'decline'

// Validation
if ($requester_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit;
}

if (!in_array($action, ['accept', 'decline'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

try {
    // Verify the request exists and is pending
    $stmt = $pdo->prepare("
        SELECT id FROM friendships 
        WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$requester_id, $user_id]);
    $friendship = $stmt->fetch();
    
    if (!$friendship) {
        echo json_encode(['status' => 'error', 'message' => 'Friend request not found']);
        exit;
    }
    
    if ($action == 'accept') {
        // Accept the request
        $stmt = $pdo->prepare("
            UPDATE friendships 
            SET status = 'accepted', updated_at = NOW()
            WHERE requester_id = ? AND receiver_id = ?
        ");
        $stmt->execute([$requester_id, $user_id]);
        
        // Get user info for notification
        $stmt = $pdo->prepare("SELECT full_name, avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();
        
        // Send notification to requester
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) 
            VALUES (?, ?, 'friend_accept', ?, ?, ?)
        ");
        $notification_text = $current_user['full_name'] . " arkadaşlık isteğinizi kabul etti";
        $stmt->execute([
            $requester_id,
            $user_id, // actor_id
            $user_id,
            $notification_text,
            'profile.php?uid=' . $user_id
        ]);
        
        // Send push notification (temporarily disabled)
        /*
        sendPushToUser(
            $requester_id,
            "Arkadaşlık İsteği Kabul Edildi",
            $notification_text,
            $current_user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($current_user['full_name'])
        );
        */
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Friend request accepted'
        ]);
        
    } else { // decline
        // Decline the request
        $stmt = $pdo->prepare("
            UPDATE friendships 
            SET status = 'declined', updated_at = NOW()
            WHERE requester_id = ? AND receiver_id = ?
        ");
        $stmt->execute([$requester_id, $user_id]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Friend request declined'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Friend response error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
