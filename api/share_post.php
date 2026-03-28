<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/security_helper.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

require_csrf();

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($post_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz post']);
    exit();
}

try {
    // 1. Verify original post exists
    $stmt = $pdo->prepare("SELECT user_id, content FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $original_post = $stmt->fetch();

    if (!$original_post) {
        // Check group posts?
        // Current logic assumes sharing generic posts. 
        // If sharing a group post, we might want to store it as a regular post but referencing it?
        // Implementation Plan said 'shared_from_id' in 'posts'.
        // If original is in group_posts, we might need 'shared_from_type' too?
        // Let's assume sharing regular posts for now from feed.
        echo json_encode(['status' => 'error', 'message' => 'Post bulunamadı']);
        exit();
    }

    // 2. Create New Post (The Share)
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, shared_from_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $content, $post_id]);
    $new_post_id = $pdo->lastInsertId();

    // 3. Log Share Stats
    $pdo->prepare("INSERT INTO post_shares (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);

    // 4. Notification to Original Owner
    if ($original_post['user_id'] != $user_id) {
        $sharer_name = $_SESSION['full_name'] ?? $_SESSION['username'];
        $notif_content = "$sharer_name gönderini paylaştı";
        $notif_url = 'feed.php#post-' . $new_post_id;
        
        // In-App
        $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'share', ?, ?, ?)")
            ->execute([$original_post['user_id'], $user_id, $post_id, $notif_content, $notif_url]);

        // Push
        sendPushNotification(
            $original_post['user_id'],
            'Yeni Paylaşım 🔄',
            $notif_content,
            '/' . $notif_url
        );
    }

    echo json_encode(['status' => 'success', 'message' => 'Paylaşıldı!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>
