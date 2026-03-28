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

$business_id = isset($_POST['business_id']) ? (int)$_POST['business_id'] : 0;
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$name_en = isset($_POST['name_en']) ? trim($_POST['name_en']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$description_en = isset($_POST['description_en']) ? trim($_POST['description_en']) : '';

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM business_listings WHERE id = ? AND owner_id = ?");
$stmt->execute([$business_id, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Business not found or unauthorized']);
    exit();
}

// Validate
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
}

try {
    if ($category_id > 0) {
        // Update existing category
        $stmt = $pdo->prepare("
            UPDATE business_menu_categories 
            SET name = ?, name_en = ?, description = ?, description_en = ?, updated_at = NOW()
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$name, $name_en, $description, $description_en, $category_id, $business_id]);
    } else {
        // Get max sort order
        $max_order = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM business_menu_categories WHERE business_id = ?");
        $max_order->execute([$business_id]);
        $sort_order = $max_order->fetchColumn();
        
        // Insert new category
        $stmt = $pdo->prepare("
            INSERT INTO business_menu_categories (business_id, name, name_en, description, description_en, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$business_id, $name, $name_en, $description, $description_en, $sort_order]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
