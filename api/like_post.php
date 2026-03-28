<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/security_helper.php';

session_start();
header('Content-Type: application/json');

// Disable error reporting for cleaner API response
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

require_csrf();

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$reaction = isset($_POST['reaction_type']) ? $_POST['reaction_type'] : 'like';
$valid_reactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

if (!in_array($reaction, $valid_reactions)) {
    $reaction = 'like';
}

if ($post_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz post']);
    exit();
}

try {
    // Check if already reacted
    $stmt = $pdo->prepare("SELECT id, reaction_type FROM post_likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing = $stmt->fetch();

    $is_reacted = false;
    $current_reaction = null;

    if ($existing) {
        if ($existing['reaction_type'] == $reaction) {
            // SAME REACTION -> REMOVE (Toggle Off)
            $pdo->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
            $pdo->prepare("UPDATE posts SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?")->execute([$post_id]);
            $is_reacted = false;
            $current_reaction = null;
        } else {
            // DIFFERENT REACTION -> UPDATE
            $pdo->prepare("UPDATE post_likes SET reaction_type = ? WHERE user_id = ? AND post_id = ?")->execute([$reaction, $user_id, $post_id]);
            // Count doesn't change
            $is_reacted = true;
            $current_reaction = $reaction;
        }
    } else {
        // NEW REACTION -> INSERT
        $pdo->prepare("INSERT INTO post_likes (user_id, post_id, reaction_type) VALUES (?, ?, ?)")->execute([$user_id, $post_id, $reaction]);
        $pdo->prepare("UPDATE posts SET like_count = like_count + 1 WHERE id = ?")->execute([$post_id]);
        $is_reacted = true;
        $current_reaction = $reaction;

        // Send Notification (if not own post)
        $post_stmt = $pdo->prepare("SELECT user_id, content FROM posts WHERE id = ?");
        $post_stmt->execute([$post_id]);
        $post = $post_stmt->fetch();

        if ($post && $post['user_id'] != $user_id) {
            $liker_name = $_SESSION['full_name'] ?? $_SESSION['username'];
            
            $reaction_emojis = [
                'like' => '👍', 'love' => '❤️', 'haha' => '😂', 
                'wow' => '😮', 'sad' => '😢', 'angry' => '😡'
            ];
            $emoji = $reaction_emojis[$reaction] ?? '❤️';
            
            $notif_content = $liker_name . " gönderine tepki verdi $emoji";
            $notif_url = 'feed.php#post-' . $post_id;
            
            // In-App Notification
            $ins = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'reaction', ?, ?, ?)");
            $ins->execute([$post['user_id'], $user_id, $post_id, $notif_content, $notif_url]);

            // Push Notification
            sendPushNotification(
                $post['user_id'],
                'Yeni Tepki ' . $emoji,
                $notif_content,
                '/' . $notif_url
            );
        }
    }

    // Get new count
    $count_stmt = $pdo->prepare("SELECT like_count FROM posts WHERE id = ?");
    $count_stmt->execute([$post_id]);
    $new_count = $count_stmt->fetchColumn();

    echo json_encode([
        'status' => 'success', 
        'reacted' => $is_reacted, 
        'reaction' => $current_reaction,
        'count' => $new_count
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>
