<?php
require_once '../includes/db.php';
require_once '../includes/lang.php';
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

$user_id = $_SESSION['user_id'];
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

$user_badge = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$user_badge->execute([$user_id]);
$badge = $user_badge->fetchColumn();
$is_mod = in_array($badge, ['founder', 'moderator']);

if ($comment_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid comment ID']);
    exit;
}

try {
    // Check permission:
    // 1. Comment owner
    // 2. Post owner (moderation)
    
    // Fetch comment and post info
    $stmt = $pdo->prepare("
        SELECT c.user_id as comment_owner_id, p.user_id as post_owner_id 
        FROM post_comments c 
        JOIN posts p ON c.post_id = p.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        echo json_encode(['status' => 'error', 'message' => 'Comment not found']);
        exit;
    }

    if (!$is_mod && $user_id != $info['comment_owner_id'] && $user_id != $info['post_owner_id']) {
        echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
        exit;
    }

    // Delete comment (and replies? usually cascading delete in DB, but let's assume simple delete)
    // If replies exist, they might be orphaned or deleted. 
    // Ideally we should delete replies too.
    $del = $pdo->prepare("DELETE FROM post_comments WHERE id = ? OR parent_id = ?");
    $del->execute([$comment_id, $comment_id]);

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    error_log("Delete comment error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
