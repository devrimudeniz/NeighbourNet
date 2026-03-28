<?php
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_csrf();

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
$new_content = isset($_POST['new_content']) ? trim($_POST['new_content']) : '';

if (!$message_id) {
    echo json_encode(['error' => 'Invalid message ID']);
    exit();
}

// Check ownership and valid message
$stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE id = ?");
$stmt->execute([$message_id]);
$msg = $stmt->fetch();

if (!$msg || $msg['sender_id'] != $user_id) {
    echo json_encode(['error' => 'Not allowed']); // Can only edit own messages
    exit();
}

try {
    if ($action == 'delete') {
        $upd = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
        $upd->execute([$message_id]);
        echo json_encode(['status' => 'success', 'action' => 'deleted']);
    } elseif ($action == 'edit') {
        if (empty($new_content)) {
            echo json_encode(['error' => 'Content cannot be empty']);
            exit();
        }
        $upd = $pdo->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ?");
        $upd->execute([$new_content, $message_id]);
        echo json_encode(['status' => 'success', 'action' => 'edited']);
    } elseif ($action == 'react') {
        $reaction_type = $_POST['reaction_type'] ?? 'like';
        
        // check if already reacted
        $check = $pdo->prepare("SELECT id, reaction_type FROM message_reactions WHERE message_id = ? AND user_id = ?");
        $check->execute([$message_id, $user_id]);
        $existing = $check->fetch();

        if ($existing) {
            if ($existing['reaction_type'] == $reaction_type) {
                // Toggle off
                $del = $pdo->prepare("DELETE FROM message_reactions WHERE id = ?");
                $del->execute([$existing['id']]);
                $final_action = 'removed';
            } else {
                // Change reaction
                $upd = $pdo->prepare("UPDATE message_reactions SET reaction_type = ? WHERE id = ?");
                $upd->execute([$reaction_type, $existing['id']]);
                $final_action = 'updated';
            }
        } else {
            // New reaction
            $ins = $pdo->prepare("INSERT INTO message_reactions (message_id, user_id, reaction_type) VALUES (?, ?, ?)");
            $ins->execute([$message_id, $user_id, $reaction_type]);
            $final_action = 'added';
        }
        echo json_encode(['status' => 'success', 'action' => $final_action]);
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
