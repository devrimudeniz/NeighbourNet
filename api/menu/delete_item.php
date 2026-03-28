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

try {
    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT bmi.id, bmi.image_url
        FROM business_menu_items bmi
        JOIN business_listings bl ON bmi.business_id = bl.id
        WHERE bmi.id = ? AND bl.owner_id = ?
    ");
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found or unauthorized']);
        exit();
    }
    
    // Delete image file if exists
    if ($item['image_url'] && file_exists('../../' . $item['image_url'])) {
        unlink('../../' . $item['image_url']);
    }
    
    // Delete item
    $stmt = $pdo->prepare("DELETE FROM business_menu_items WHERE id = ?");
    $stmt->execute([$item_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
