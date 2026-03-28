<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$story_id = $_POST['story_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$story_id) {
    echo json_encode(['success' => false, 'error' => 'Story ID required']);
    exit();
}

try {
    // 1. Verify ownership
    $stmt = $pdo->prepare("SELECT id, user_id, media_url FROM stories WHERE id = ?");
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

    // 2. Delete file
    // media_url might be absolute or relative. Assuming relative to root like 'uploads/stories/...'
    // If it starts with http, need to parse. But usually it is stored as 'uploads/stories/file.jpg'
    // Let's safe delete.
    $file_path = '../' . $story['media_url']; 
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // 3. Delete from DB
    $delName = $pdo->prepare("DELETE FROM stories WHERE id = ?");
    $delName->execute([$story_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
