<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$story_id = $_GET['story_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$story_id) {
    echo json_encode(['success' => false, 'error' => 'Story ID required']);
    exit();
}

try {
    // 1. Verify ownership of the story
    $stmt = $pdo->prepare("SELECT user_id FROM stories WHERE id = ?");
    $stmt->execute([$story_id]);
    $story = $stmt->fetch();

    if (!$story) {
        echo json_encode(['success' => false, 'error' => 'Story not found']);
        exit();
    }

    if ($story['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit();
    }

    // 2. Fetch Viewers
    // Join story_views with users table
    $query = "SELECT u.id, u.username, u.full_name, u.avatar, v.viewed_at 
              FROM story_views v 
              JOIN users u ON v.viewer_id = u.id 
              WHERE v.story_id = ? 
              ORDER BY v.viewed_at DESC";
    
    $v_stmt = $pdo->prepare($query);
    $v_stmt->execute([$story_id]);
    $viewers = $v_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'viewers' => $viewers]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
