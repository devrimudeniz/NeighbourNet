<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/security_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed', 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized', 'message' => 'Unauthorized']);
    exit;
}

require_csrf();

$post_id = (int)($_POST['post_id'] ?? 0);
$post_type = $_POST['post_type'] ?? 'regular';
$user_id = $_SESSION['user_id'];

// Founder ve moderator her gönderiyi silebilir
$user_badge = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$user_badge->execute([$user_id]);
$badge = $user_badge->fetchColumn();
$is_mod = in_array($badge, ['founder', 'moderator']);

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Post ID required', 'message' => 'Post ID required']);
    exit;
}

try {
    // GROUP POST
    if ($post_type === 'group') {
        $stmt = $pdo->prepare("SELECT id, user_id, group_id FROM group_posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $group_post = $stmt->fetch();

        if (!$group_post) {
            echo json_encode(['success' => false, 'error' => 'Post not found', 'message' => 'Post not found']);
            exit;
        }

        if ($group_post['user_id'] != $user_id && !$is_mod) {
            echo json_encode(['success' => false, 'error' => 'Permission denied', 'message' => 'Permission denied']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM group_posts WHERE id = ?")->execute([$post_id]);
            $pdo->prepare("UPDATE groups SET post_count = GREATEST(0, post_count - 1) WHERE id = ?")->execute([$group_post['group_id']]);
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        exit;
    }

    // REGULAR POST
    $wall_exists = false;
    try {
        $wc = $pdo->query("SHOW COLUMNS FROM posts LIKE 'wall_user_id'");
        $wall_exists = $wc->rowCount() > 0;
    } catch(Exception $e){}

    if ($wall_exists) {
        $stmt = $pdo->prepare("SELECT user_id, wall_user_id FROM posts WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT user_id, NULL as wall_user_id FROM posts WHERE id = ?");
    }
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found', 'message' => 'Post not found']);
        exit;
    }

    if (!$is_mod && $post['user_id'] != $user_id && (empty($post['wall_user_id']) || $post['wall_user_id'] != $user_id)) {
        echo json_encode(['success' => false, 'error' => 'Permission denied', 'message' => 'Permission denied']);
        exit;
    }

    $delete_stmt = $pdo->prepare("UPDATE posts SET deleted_at = NOW() WHERE id = ?");
    if ($delete_stmt->execute([$post_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error', 'message' => 'Database error']);
    }

} catch (PDOException $e) {
    error_log("Delete post error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred', 'message' => 'An error occurred']);
}
?>
