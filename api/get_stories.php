<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/friendship-helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'auth_required']);
    exit();
}

try {
    // Check if visibility column exists
    $has_visibility = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM stories LIKE 'visibility'");
        $has_visibility = $col->fetch() !== false;
    } catch (PDOException $e) {}
    
    $sql = "SELECT s.*, u.username, u.full_name, u.avatar, u.badge,
            (SELECT COUNT(*) FROM story_views WHERE story_id = s.id) as view_count,
            (SELECT COUNT(*) FROM story_views WHERE story_id = s.id AND viewer_id = ?) as has_viewed
            FROM stories s
            JOIN users u ON s.user_id = u.id
            WHERE s.expires_at > NOW()
            ORDER BY s.created_at DESC";
    
    $viewer_id = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$viewer_id]);
    $all_stories = $stmt->fetchAll();

    // Group stories by user (filter by visibility)
    $grouped_stories = [];
    foreach ($all_stories as $story) {
        $story_visibility = ($has_visibility && isset($story['visibility'])) ? $story['visibility'] : 'everyone';
        
        // friends_only: only show to owner or friends
        if ($story_visibility === 'friends_only') {
            if ($story['user_id'] != $viewer_id && !areFriends($viewer_id, $story['user_id'])) {
                continue; // Skip this story
            }
        }
        
        $user_id = $story['user_id'];
        if (!isset($grouped_stories[$user_id])) {
            $grouped_stories[$user_id] = [
                'user_id' => $user_id,
                'username' => $story['username'],
                'full_name' => $story['full_name'],
                'avatar' => $story['avatar'],
                'badge' => $story['badge'],
                'stories' => [],
                'has_unviewed' => false
            ];
        }
        
        $grouped_stories[$user_id]['stories'][] = [
            'id' => $story['id'],
            'media_url' => $story['media_url'],
            'media_type' => $story['media_type'],
            'caption' => $story['caption'],
            'created_at' => $story['created_at'],
            'expires_at' => $story['expires_at'],
            'view_count' => $story['view_count'],
            'has_viewed' => $story['has_viewed'] > 0
        ];

        if ($story['has_viewed'] == 0) {
            $grouped_stories[$user_id]['has_unviewed'] = true;
        }
    }

    // Convert to indexed array and sort (own story first if logged in)
    $result = array_values($grouped_stories);
    if (isset($_SESSION['user_id'])) {
        usort($result, function($a, $b) {
            if ($a['user_id'] == $_SESSION['user_id']) return -1;
            if ($b['user_id'] == $_SESSION['user_id']) return 1;
            return 0;
        });
    }

    echo json_encode([
        'success' => true,
        'stories' => $result
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
