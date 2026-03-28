<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$target_id = $_POST['target_id'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$target_id || $target_id == $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid target']);
    exit();
}

try {
    switch ($action) {
        case 'mute':
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_filters (user_id, target_id, filter_type) VALUES (?, ?, 'mute')");
            $stmt->execute([$user_id, $target_id]);
            echo json_encode(['status' => 'success', 'message' => $t['mute_success']]);
            break;

        case 'unmute':
            $stmt = $pdo->prepare("DELETE FROM user_filters WHERE user_id = ? AND target_id = ? AND filter_type = 'mute'");
            $stmt->execute([$user_id, $target_id]);
            echo json_encode(['status' => 'success', 'message' => $t['unmute_success']]);
            break;

        case 'block':
            // When blocking, we should also remove friendship if exists
            $pdo->beginTransaction();
            
            // Add block filter
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_filters (user_id, target_id, filter_type) VALUES (?, ?, 'block')");
            $stmt->execute([$user_id, $target_id]);
            
            // Remove any friendship
            $stmt = $pdo->prepare("DELETE FROM friendships WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)");
            $stmt->execute([$user_id, $target_id, $target_id, $user_id]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => $t['block_success']]);
            break;

        case 'unblock':
            $stmt = $pdo->prepare("DELETE FROM user_filters WHERE user_id = ? AND target_id = ? AND filter_type = 'block'");
            $stmt->execute([$user_id, $target_id]);
            echo json_encode(['status' => 'success', 'message' => $t['unblock_success']]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
