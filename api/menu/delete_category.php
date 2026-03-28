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
$category_id = isset($data['category_id']) ? (int)$data['category_id'] : 0;

try {
    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT bmc.id 
        FROM business_menu_categories bmc
        JOIN business_listings bl ON bmc.business_id = bl.id
        WHERE bmc.id = ? AND bl.owner_id = ?
    ");
    $stmt->execute([$category_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Category not found or unauthorized']);
        exit();
    }
    
    // Delete category (items will be set to NULL category due to ON DELETE SET NULL)
    $stmt = $pdo->prepare("DELETE FROM business_menu_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
