<?php
/**
 * API: Join/Leave Group
 */
require_once '../includes/db.php';
require_once '../includes/lang.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$group_id = (int)($_POST['group_id'] ?? 0);

if (!$group_id || !in_array($action, ['join', 'leave'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

// Check if group exists
$group = $pdo->prepare("SELECT id, privacy FROM groups WHERE id = ?");
$group->execute([$group_id]);
$group_data = $group->fetch();

if (!$group_data) {
    echo json_encode(['status' => 'error', 'message' => 'Group not found']);
    exit;
}

try {
    if ($action === 'join') {
        // Check if already a member
        $check = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
        $check->execute([$group_id, $user_id]);
        
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Already a member']);
            exit;
        }
        
        // Add member
        $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')")
            ->execute([$group_id, $user_id]);
        
        // Update member count
        $pdo->prepare("UPDATE groups SET member_count = member_count + 1 WHERE id = ?")
            ->execute([$group_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Joined group', 'action' => 'joined']);
        
    } else if ($action === 'leave') {
        // Check if member
        $check = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
        $check->execute([$group_id, $user_id]);
        $membership = $check->fetch();
        
        if (!$membership) {
            echo json_encode(['status' => 'error', 'message' => 'Not a member']);
            exit;
        }
        
        // Prevent admin from leaving (must transfer ownership first)
        if ($membership['role'] === 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Admins cannot leave. Transfer ownership first.']);
            exit;
        }
        
        // Remove member
        $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?")
            ->execute([$group_id, $user_id]);
        
        // Update member count
        $pdo->prepare("UPDATE groups SET member_count = GREATEST(member_count - 1, 0) WHERE id = ?")
            ->execute([$group_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Left group', 'action' => 'left']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
