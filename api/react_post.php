<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/security_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_csrf();

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;
$reaction = $_POST['reaction'] ?? 'like'; // 'love', 'like', etc.

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit;
}

// Map 'love' to existing reaction logic if needed, or stick to simple logic
// Check if reaction exists
$stmt = $pdo->prepare("SELECT id, type FROM reactions WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user_id, $post_id]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['type'] == $reaction) {
        // Remove reaction (toggle off)
        $pdo->prepare("DELETE FROM reactions WHERE id = ?")->execute([$existing['id']]);
        $action = 'removed';
    } else {
        // Change reaction
        $pdo->prepare("UPDATE reactions SET type = ? WHERE id = ?")->execute([$reaction, $existing['id']]);
        $action = 'updated';
    }
} else {
    // Add new reaction
    $pdo->prepare("INSERT INTO reactions (user_id, post_id, type) VALUES (?, ?, ?)")->execute([$user_id, $post_id, $reaction]);
    $action = 'added';
    
    // Notification logic could go here
    // ...
}

// Get updated count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reactions WHERE post_id = ? AND type = ?");
$countStmt->execute([$post_id, $reaction]);
$new_count = $countStmt->fetchColumn();

echo json_encode(['success' => true, 'action' => $action, 'count' => $new_count]);
