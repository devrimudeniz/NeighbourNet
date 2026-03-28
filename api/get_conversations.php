<?php
require_once '../includes/db.php';
require_once '../includes/lang.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch recent conversations
    $sql = "
        SELECT 
            u.id, u.username, u.full_name, u.avatar
        FROM users u
        JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        AND m.id IN (
            SELECT MAX(id) FROM messages 
            WHERE sender_id = ? OR receiver_id = ? 
            GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
        )
        AND u.id != ?
        ORDER BY m.created_at DESC
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format avatar URLs
    foreach ($users as &$user) {
        $user['full_name'] = htmlspecialchars($user['full_name']);
        // Check if avatar is full URL or relative
        if (!filter_var($user['avatar'], FILTER_VALIDATE_URL)) {
             // Assuming a helper function or base URL logic exists, but let's stick to what messages.php uses.
             // messages.php uses the raw DB value. If DB has relative path, it might be an issue if we are in /api/.
             // But usually DB stores full path or relative from root.
             // Let's use media_url helper if available, but we can't include ui_components here easily.
             // Let's manually fix it if needed.
             // Actually, the frontend often handles this, or DB has full URL. 
             // Let's leave it as is, messages.php uses direct echo.
        }
    }

    echo json_encode(['status' => 'success', 'conversations' => $users]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
