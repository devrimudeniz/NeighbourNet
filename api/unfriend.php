<?php
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

require_csrf();

$user_id = $_SESSION['user_id'];
$friend_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Validation
if ($friend_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Delete friendship (works in either direction)
    $stmt = $pdo->prepare("
        DELETE FROM friendships 
        WHERE ((requester_id = ? AND receiver_id = ?)
           OR (requester_id = ? AND receiver_id = ?))
           AND status = 'accepted'
    ");
    $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Unfriended successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Friendship not found'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Unfriend error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
