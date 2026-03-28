<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit;
}

$post_id = (int)$_GET['post_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id as user_id, 
            u.username, 
            u.full_name, 
            u.avatar, 
            pl.reaction_type,
            u.badge
        FROM post_likes pl
        JOIN users u ON pl.user_id = u.id
        WHERE pl.post_id = ?
        ORDER BY pl.id DESC
    ");
    $stmt->execute([$post_id]);
    $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data
    $formatted = [];
    foreach ($reactions as $r) {
        $avatar = !empty($r['avatar']) ? $r['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($r['full_name'] ?? $r['username']) . '&background=random';
        // Handle relative paths for avatars
        if (!filter_var($avatar, FILTER_VALIDATE_URL)) {
            // Assume it's a relative path, prepend site url if needed, but for now client might handle or we send what we have. 
            // Better to keep it as is, frontend usually handles relative paths if they are correct.
            // But helper usually does cleaning.
        }

        $formatted[] = [
            'user_id' => $r['user_id'],
            'username' => $r['username'],
            'full_name' => $r['full_name'],
            'avatar' => $avatar,
            'reaction_type' => $r['reaction_type'],
            'badge' => $r['badge']
        ];
    }

    echo json_encode(['success' => true, 'reactions' => $formatted]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
