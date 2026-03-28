<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$story_id = $_POST['story_id'] ?? 0;
$viewer_id = $_SESSION['user_id'];

if (!$story_id) {
    echo json_encode(['success' => false, 'error' => 'No story ID provided']);
    exit();
}

try {
    // Check if story exists and is not expired
    $check_stmt = $pdo->prepare("SELECT user_id FROM stories WHERE id = ? AND expires_at > NOW()");
    $check_stmt->execute([$story_id]);
    $story = $check_stmt->fetch();

    if (!$story) {
        echo json_encode(['success' => false, 'error' => 'Story not found or expired']);
        exit();
    }

    // Don't record view if viewing own story
    if ($story['user_id'] == $viewer_id) {
        echo json_encode(['success' => true, 'message' => 'Own story view (not recorded)']);
        exit();
    }

    // Insert or ignore view record
    $stmt = $pdo->prepare("INSERT IGNORE INTO story_views (story_id, viewer_id) VALUES (?, ?)");
    $stmt->execute([$story_id, $viewer_id]);

    echo json_encode(['success' => true, 'message' => 'View recorded']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
