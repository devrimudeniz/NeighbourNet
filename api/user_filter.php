<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_csrf();

$user_id = $_SESSION['user_id'];
$target_id = (int)$_POST['target_id'];
$filter_type = $_POST['filter_type'] ?? ''; // 'block' or 'mute'

// Validate filter type
if (!in_array($filter_type, ['block', 'mute'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid filter type']);
    exit();
}

// Validate target user
if ($target_id <= 0 || $target_id == $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid target user']);
    exit();
}

try {
    // Check if filter already exists
    $check = $pdo->prepare("SELECT id FROM user_filters WHERE user_id = ? AND target_id = ? AND filter_type = ?");
    $check->execute([$user_id, $target_id, $filter_type]);
    
    if ($check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Filter already exists']);
        exit();
    }
    
    // Insert filter
    $stmt = $pdo->prepare("INSERT INTO user_filters (user_id, target_id, filter_type, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $target_id, $filter_type]);
    
    // If blocking, also unfriend
    if ($filter_type === 'block') {
        // Remove friendship in both directions
        $unfriend = $pdo->prepare("DELETE FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $unfriend->execute([$user_id, $target_id, $target_id, $user_id]);
        
        // Remove pending friend requests
        $remove_requests = $pdo->prepare("DELETE FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        $remove_requests->execute([$user_id, $target_id, $target_id, $user_id]);
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Filter applied successfully']);
    
} catch (PDOException $e) {
    error_log("User filter error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
