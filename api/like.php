<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// api/like.php doesn't include lang.php, set lang manually
$lang = $_SESSION['language'] ?? 'en';

if (!isset($_SESSION['user_id'])) {
    $msg = ($lang == 'en') ? 'Please log in to interact' : 'Etkileşimde bulunmak için lütfen giriş yapın';
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

if ($event_id > 0) {
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    
    if ($stmt->fetch()) {
        // Unlike
        $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND event_id = ?")->execute([$user_id, $event_id]);
        $action = 'unliked';
    } else {
        // Like
        $pdo->prepare("INSERT INTO likes (user_id, event_id) VALUES (?, ?)")->execute([$user_id, $event_id]);
        
        // Create notification for event owner
        try {
            $event_stmt = $pdo->prepare("SELECT user_id FROM events WHERE id = ?");
            $event_stmt->execute([$event_id]);
            $event_owner = $event_stmt->fetchColumn();
            
            // Don't notify yourself
            if ($event_owner && $event_owner != $user_id) {
                $liker_name = $_SESSION['full_name'] ?? $_SESSION['username'];
                $event_stmt = $pdo->prepare("SELECT title FROM events WHERE id = ?");
                $event_stmt->execute([$event_id]);
                $event_title = $event_stmt->fetchColumn();

                $notif_content = $liker_name . " " . ($lang == 'en' ? "liked your event:" : "etkinliğini beğendi:") . " " . $event_title;
                $notif_url = 'event_detail.php?id=' . $event_id;

                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'event_like', ?, ?, ?)");
                $notif_stmt->execute([$event_owner, $user_id, $event_id, $notif_content, $notif_url]);
            }
        } catch (Exception $e) {
            // Ignore if notifications table doesn't exist yet
        }
        
        $action = 'liked';
    }

    // Get new count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'action' => $action, 'count' => $count]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz ID']);
}
?>
