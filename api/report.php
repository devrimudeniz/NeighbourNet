<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_csrf();

if (!RateLimiter::check($pdo, 'report', 5, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

$reporter_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : null;
$reason = trim($_POST['reason'] ?? '');
$description = trim($_POST['description'] ?? '');

$valid_reasons = ['spam', 'harassment', 'hate_speech', 'nudity', 'violence', 'misinformation', 'other'];

if (!in_array($reason, $valid_reasons)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reason']);
    exit;
}

if (!$post_id && !$user_id && !$comment_id) {
    echo json_encode(['success' => false, 'message' => 'Nothing to report']);
    exit;
}

// Prevent self-reporting
if ($user_id && $user_id === $reporter_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot report yourself']);
    exit;
}

// Check for duplicate reports
try {
    $check_sql = "SELECT id FROM reports WHERE reporter_id = ? AND status = 'pending' AND ";
    $check_params = [$reporter_id];
    
    if ($post_id) {
        $check_sql .= "post_id = ?";
        $check_params[] = $post_id;
    } elseif ($user_id) {
        $check_sql .= "reported_user_id = ?";
        $check_params[] = $user_id;
    } else {
        $check_sql .= "comment_id = ?";
        $check_params[] = $comment_id;
    }
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute($check_params);
    
    // Sanitize IDs - ensure 0 becomes null
    $post_id = $post_id ?: null;
    $comment_id = $comment_id ?: null;
    $user_id = $user_id ?: null;

    // Automatic User Resolution: If reporting content, find the author
    if (!$user_id) {
        if ($post_id) {
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $user_id = $stmt->fetchColumn();
        } elseif ($comment_id) {
            $stmt = $pdo->prepare("SELECT user_id FROM post_comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $user_id = $stmt->fetchColumn();
        }
    }
    
    // If still invalid (e.g. content deleted), ensure it acts safely (though FK will fail if we send a bad ID, fetching column returns false if not found. false -> null for DB?)
    // fetchColumn returns false if no row.
    if ($user_id === false) $user_id = null;

    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this content']);
        exit;
    }
    
    // Insert report
    $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, reported_user_id, post_id, comment_id, reason, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $reporter_id,
        $user_id,
        $post_id,
        $comment_id,
        $reason,
        $description ?: null
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Report submitted. Thank you for helping keep our community safe.']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
