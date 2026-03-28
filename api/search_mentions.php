<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode(['status' => 'success', 'users' => []]);
    exit();
}

try {
    // Search users by username or full_name
    // Prioritize friends could be added here, but for now simple search
    $stmt = $pdo->prepare("SELECT id, username, full_name, avatar, badge FROM users WHERE username LIKE ? OR full_name LIKE ? LIMIT 5");
    $param = '%' . $query . '%';
    $stmt->execute([$param, $param]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for frontend
    $formatted_users = [];
    foreach ($users as $u) {
        $formatted_users[] = [
            'id' => $u['id'],
            'username' => $u['username'],
            'full_name' => $u['full_name'],
            'avatar' => $u['avatar'],
            'badge' => $u['badge']
        ];
    }

    echo json_encode(['status' => 'success', 'users' => $formatted_users]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
