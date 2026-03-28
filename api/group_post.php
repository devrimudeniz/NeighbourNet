<?php
/**
 * API: Group Post Actions (Create, Get)
 */
session_start();
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_csrf();

if (!RateLimiter::check($pdo, 'group_post', 5, 60)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests. Please try again later.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$group_id = (int)($_POST['group_id'] ?? 0);

if (!$group_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid group']);
    exit;
}

// Check if user is member
$membership = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$membership->execute([$group_id, $user_id]);

if (!$membership->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Not a member of this group']);
    exit;
}

try {
    if ($action === 'create') {
        $content = trim($_POST['content'] ?? '');
        $visibility = in_array($_POST['visibility'] ?? '', ['everyone', 'group_only']) ? $_POST['visibility'] : 'everyone';
        
        if (empty($content)) {
            echo json_encode(['status' => 'error', 'message' => 'Content is required']);
            exit;
        }
        
        // Insert post (check if visibility column exists)
        try {
            $stmt = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, content, visibility) VALUES (?, ?, ?, ?)");
            $stmt->execute([$group_id, $user_id, $content, $visibility]);
        } catch (PDOException $e) {
            // Fallback if visibility column doesn't exist yet
            if (strpos($e->getMessage(), 'visibility') !== false) {
                $stmt = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$group_id, $user_id, $content]);
            } else {
                throw $e;
            }
        }
        
        // Update post count
        $pdo->prepare("UPDATE groups SET post_count = post_count + 1 WHERE id = ?")
            ->execute([$group_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Post created']);
        
    } else if ($action === 'get') {
        // Get posts
        $stmt = $pdo->prepare("SELECT gp.*, u.username, u.full_name, u.avatar, u.badge 
                               FROM group_posts gp 
                               JOIN users u ON gp.user_id = u.id 
                               WHERE gp.group_id = ? 
                               ORDER BY gp.created_at DESC 
                               LIMIT 50");
        $stmt->execute([$group_id]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'posts' => $posts]);
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>
