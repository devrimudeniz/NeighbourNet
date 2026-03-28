<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'create_post') {
        $stmt = $pdo->prepare("INSERT INTO trail_posts (user_id, trail_segment, planned_date, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $data['trail_segment'],
            $data['planned_date'],
            $data['description']
        ]);
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'toggle_tracking') {
        $status = (int)$data['status'];
        
        // Find or create tracking session
        $stmt = $pdo->prepare("SELECT id FROM trail_tracking WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE trail_tracking SET is_active = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$status, $user_id]);
        } else {
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO trail_tracking (user_id, session_token, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $token, $status]);
        }
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'update_location') {
        $stmt = $pdo->prepare("UPDATE trail_tracking SET last_lat = ?, last_lng = ?, updated_at = NOW() WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$data['lat'], $data['lng'], $user_id]);
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'delete_post') {
        $post_id = (int)$data['post_id'];
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM trail_posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM trail_posts WHERE id = ?");
            $stmt->execute([$post_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unauthorized or not found']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
