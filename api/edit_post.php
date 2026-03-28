<?php
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_csrf();

if (!RateLimiter::check($pdo, 'edit_post', 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
    exit;
}

$post_id = $_POST['post_id'] ?? 0;
$content = trim($_POST['content'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$post_id || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Valid content required']);
    exit;
}

try {
    // Verify ownership
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }

    if ($post['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }

    // Update post
    // Note: We might want to mark it as 'edited' in DB if we had a column, but user request didn't specify.
    // We just update content.
    $update_stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ?");
    if ($update_stmt->execute([$content, $post_id])) {
        echo json_encode(['success' => true, 'new_content' => $content]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
}
?>
