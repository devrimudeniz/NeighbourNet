<?php
require_once '../includes/db.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit();
    }

    try {
        if ($action == 'approve') {
            $stmt = $pdo->prepare("UPDATE marketplace_listings SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } elseif ($action == 'reject') {
            $stmt = $pdo->prepare("UPDATE marketplace_listings SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
