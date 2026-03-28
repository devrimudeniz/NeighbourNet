<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($event_id > 0 && !empty($content)) {
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, event_id, parent_id, content) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $event_id, $parent_id, $content])) {
        
        // Return new comment data for AJAX append
        $new_comment = [
            'username' => $_SESSION['username'],
            'avatar' => isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']),
            'content' => htmlspecialchars($content),
            'date' => 'Şimdi'
        ];

        echo json_encode(['status' => 'success', 'comment' => $new_comment]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Yorum eklenemedi']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Boş yorum gönderemezsiniz']);
}
?>
