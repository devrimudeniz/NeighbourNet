<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($id < 1) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM lost_items WHERE id = ? AND user_id = ? AND status = 'lost'");
    $stmt->execute([$id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Not found or not authorized']);
        exit();
    }
    $pdo->prepare("UPDATE lost_items SET status = 'found' WHERE id = ?")->execute([$id]);
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
}
