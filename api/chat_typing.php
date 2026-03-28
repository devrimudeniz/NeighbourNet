<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? ''; // 'set' or 'get'
$partner_id = $_REQUEST['partner_id'] ?? 0;

if ($action == 'set') {
    // I am typing to partner
    $stmt = $pdo->prepare("INSERT INTO chat_activity (user_id, typing_to) VALUES (?, ?) ON DUPLICATE KEY UPDATE typing_to = VALUES(typing_to), last_activity = NOW()");
    $stmt->execute([$user_id, $partner_id]);
    echo json_encode(['status' => 'ok']);
} elseif ($action == 'get') {
    // Is partner typing to me?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_activity WHERE user_id = ? AND typing_to = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 5 SECOND)");
    $stmt->execute([$partner_id, $user_id]);
    $is_typing = $stmt->fetchColumn() > 0;
    
    echo json_encode(['is_typing' => $is_typing]);
}
?>
