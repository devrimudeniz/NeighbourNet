<?php
require_once '../includes/db.php';
require_once '../includes/friendship-helper.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if (empty($query)) {
        // Get all friends if no query
        $friends = getFriendsList($user_id);
    } else {
        // Search friends
        $sql = "
            SELECT u.id, u.username, u.full_name, u.avatar, u.badge
            FROM users u
            JOIN friendships f ON (
                (f.requester_id = ? AND f.receiver_id = u.id)
                OR (f.receiver_id = ? AND f.requester_id = u.id)
            )
            WHERE f.status = 'accepted'
            AND (u.username LIKE ? OR u.full_name LIKE ?)
            ORDER BY u.full_name ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $searchTerm = "%$query%";
        $stmt->execute([$user_id, $user_id, $searchTerm, $searchTerm]);
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['status' => 'success', 'friends' => $friends]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    error_log($e->getMessage());
}
?>
