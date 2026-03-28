<?php
/**
 * API: Get reactions for a story
 * GET: story_id
 */

session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'auth_required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$story_id = intval($_GET['story_id'] ?? 0);

if (!$story_id) {
    echo json_encode(['success' => false, 'error' => 'story_id required']);
    exit();
}

try {
    // Check if story exists
    $story_check = $pdo->prepare("SELECT user_id FROM stories WHERE id = ?");
    $story_check->execute([$story_id]);
    $story = $story_check->fetch();
    
    if (!$story) {
        echo json_encode(['success' => false, 'error' => 'Story not found']);
        exit();
    }
    
    // Get all reactions with user info
    $stmt = $pdo->prepare("
        SELECT sr.reaction_type, sr.created_at, u.id as user_id, u.username, u.full_name, u.avatar
        FROM story_reactions sr
        JOIN users u ON sr.user_id = u.id
        WHERE sr.story_id = ?
        ORDER BY sr.created_at DESC
    ");
    $stmt->execute([$story_id]);
    $reactions = $stmt->fetchAll();
    
    // Get current user's reaction if any
    $user_reaction = null;
    foreach ($reactions as $r) {
        if ($r['user_id'] == $user_id) {
            $user_reaction = $r['reaction_type'];
            break;
        }
    }
    
    // Group reactions by type for count summary
    $reaction_counts = [];
    foreach ($reactions as $r) {
        $type = $r['reaction_type'];
        if (!isset($reaction_counts[$type])) {
            $reaction_counts[$type] = 0;
        }
        $reaction_counts[$type]++;
    }
    
    echo json_encode([
        'success' => true,
        'reactions' => $reactions,
        'reaction_counts' => $reaction_counts,
        'total_count' => count($reactions),
        'user_reaction' => $user_reaction
    ]);
    
} catch (PDOException $e) {
    error_log("Get story reactions error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
