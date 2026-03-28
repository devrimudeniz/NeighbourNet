<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['badge']) || 
    !in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$is_available = isset($data['is_available']) ? (bool)$data['is_available'] : false;

try {
    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT bmi.id
        FROM business_menu_items bmi
        JOIN business_listings bl ON bmi.business_id = bl.id
        WHERE bmi.id = ? AND bl.owner_id = ?
    ");
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Item not found or unauthorized']);
        exit();
    }
    
    // Update availability
    $stmt = $pdo->prepare("UPDATE business_menu_items SET is_available = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$is_available ? 1 : 0, $item_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
