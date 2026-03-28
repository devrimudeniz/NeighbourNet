<?php
/**
 * API: React to a story with an emoji
 * POST: story_id, reaction_type
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/RateLimiter.php';

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'auth_required']);
    exit();
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$story_id = intval($_POST['story_id'] ?? 0);
$reaction_type = $_POST['reaction_type'] ?? '';

// Validate inputs
if (!$story_id) {
    echo json_encode(['success' => false, 'error' => 'story_id required']);
    exit();
}

// Allowed reactions
$allowed_reactions = ['❤️', '😂', '😮', '😢', '😡', '🔥'];
if (!in_array($reaction_type, $allowed_reactions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid reaction type']);
    exit();
}

// Rate limiting: max 30 reactions per minute
if (!RateLimiter::check($pdo, 'story_react', 30, 60)) {
    echo json_encode(['success' => false, 'error' => 'Çok hızlı tepki veriyorsunuz']);
    exit();
}

try {
    // Check if story exists and not expired
    $check = $pdo->prepare("SELECT id, user_id FROM stories WHERE id = ? AND expires_at > NOW()");
    $check->execute([$story_id]);
    $story = $check->fetch();
    
    if (!$story) {
        echo json_encode(['success' => false, 'error' => 'Story not found or expired']);
        exit();
    }
    
    // Can't react to own story
    if ($story['user_id'] == $user_id) {
        echo json_encode(['success' => false, 'error' => 'Kendi hikayenize tepki veremezsiniz']);
        exit();
    }
    
    // Check existing reaction
    $existing = $pdo->prepare("SELECT id, reaction_type FROM story_reactions WHERE story_id = ? AND user_id = ?");
    $existing->execute([$story_id, $user_id]);
    $existingReaction = $existing->fetch();
    
    if ($existingReaction) {
        if ($existingReaction['reaction_type'] === $reaction_type) {
            // Same reaction - remove it (toggle off)
            $delete = $pdo->prepare("DELETE FROM story_reactions WHERE id = ?");
            $delete->execute([$existingReaction['id']]);
            
            echo json_encode(['success' => true, 'action' => 'removed', 'reaction' => null]);
            exit();
        } else {
            // Different reaction - update it
            $update = $pdo->prepare("UPDATE story_reactions SET reaction_type = ?, created_at = NOW() WHERE id = ?");
            $update->execute([$reaction_type, $existingReaction['id']]);
            
            echo json_encode(['success' => true, 'action' => 'updated', 'reaction' => $reaction_type]);
            exit();
        }
    } else {
        // New reaction - insert
        $insert = $pdo->prepare("INSERT INTO story_reactions (story_id, user_id, reaction_type) VALUES (?, ?, ?)");
        $insert->execute([$story_id, $user_id, $reaction_type]);
        
        // Get story media for message preview
        $story_media = $pdo->prepare("SELECT media_url FROM stories WHERE id = ?");
        $story_media->execute([$story_id]);
        $story_data = $story_media->fetch();
        $media_url = $story_data['media_url'] ?? null;
        
        // Send notification to story owner
        $story_owner_id = $story['user_id'];
        $sender_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Birisi';
        
        // Insert DM message for reaction (so it shows in messages)
        $dm_message = $reaction_type . " Hikayene tepki verdi";
        $dm_insert = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, attachment_url, attachment_type) VALUES (?, ?, ?, ?, 'story_reaction')");
        $dm_insert->execute([$user_id, $story_owner_id, $dm_message, $media_url]);
        
        // Insert notification
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'story_reaction', ?, ?, ?)");
        $notif_content = $sender_name . ' hikayenize ' . $reaction_type . ' tepki verdi';
        $notif_url = 'view_story.php?user_id=' . $story_owner_id;
        $notif->execute([$story_owner_id, $user_id, $story_id, $notif_content, $notif_url]);
        
        // Send push notification
        try {
            sendPushNotification(
                $story_owner_id,
                'Hikaye Tepkisi ' . $reaction_type,
                $notif_content,
                '/' . $notif_url
            );
        } catch (Exception $e) {
            error_log("Push notification failed for story reaction: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'action' => 'added', 'reaction' => $reaction_type]);
    }
    
} catch (PDOException $e) {
    error_log("Story reaction error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
