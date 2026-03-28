<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Single delete
if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    echo json_encode(['status' => 'success']);
    exit;
}

// Bulk delete by type
if (isset($_POST['type'])) {
    $type = $_POST['type'];
    $allowed = ['like', 'comment', 'reaction', 'follow', 'friend_request', 'in_town', 'mention'];
    if (in_array($type, $allowed)) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND type = ?");
        $stmt->execute([$user_id, $type]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

// Mark all read
if (isset($_POST['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Missing params']);
