<?php
require_once '../includes/db.php';
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Istanbul'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$partner_id = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;
$after_id = isset($_GET['after_id']) ? (int)$_GET['after_id'] : 0;

if (!$partner_id) {
    echo json_encode([]);
    exit();
}

// Mark messages as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")->execute([$partner_id, $user_id]);

// Fetch new messages
$sql = "
    SELECT m.*, ml.title as listing_title, ml.image_url as listing_image,
    (SELECT reaction_type FROM message_reactions WHERE message_id = m.id AND user_id = ?) as my_reaction,
    (SELECT GROUP_CONCAT(reaction_type) FROM message_reactions WHERE message_id = m.id) as all_reactions
    FROM messages m 
    LEFT JOIN marketplace_listings ml ON m.listing_id = ml.id
    WHERE (
        (sender_id = ? AND receiver_id = ?) OR 
        (sender_id = ? AND receiver_id = ?)
    ) 
    AND m.id > ?
    ORDER BY created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id, $partner_id, $partner_id, $user_id, $after_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for JSON
foreach ($messages as &$msg) {
    $msg['is_me'] = $msg['sender_id'] == $user_id;
    $msg['time'] = date('H:i', strtotime($msg['created_at']));
    // If deleted, hide content for privacy/security (or handle in frontend)
    if ($msg['is_deleted']) {
        $msg['message'] = null;
        $msg['attachment_url'] = null;
    }

    // Check for Shared Post
    if (!empty($msg['message']) && preg_match('/feed\.php#post-(\d+)/', $msg['message'], $matches)) {
        $post_id = (int)$matches[1];
        $post_stmt = $pdo->prepare("SELECT p.id, p.content, p.media_url, p.media_type, u.username, u.full_name, u.avatar FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $post_stmt->execute([$post_id]);
        $post_data = $post_stmt->fetch(PDO::FETCH_ASSOC);
        if ($post_data) {
            $msg['shared_post'] = $post_data;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($messages);
?>
