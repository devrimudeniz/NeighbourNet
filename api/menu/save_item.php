<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/cdn_helper.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['badge']) || 
    !in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$business_id = isset($_POST['business_id']) ? (int)$_POST['business_id'] : 0;
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$name_en = isset($_POST['name_en']) ? trim($_POST['name_en']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$description_en = isset($_POST['description_en']) ? trim($_POST['description_en']) : '';
$allergens = isset($_POST['allergens']) && is_array($_POST['allergens']) ? $_POST['allergens'] : [];
$price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
$is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
$is_vegan = isset($_POST['is_vegan']) ? 1 : 0;
$is_spicy = isset($_POST['is_spicy']) ? 1 : 0;
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM business_listings WHERE id = ? AND owner_id = ?");
$stmt->execute([$business_id, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Business not found or unauthorized']);
    exit();
}

// Validate
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Item name is required']);
    exit();
}

try {
    $image_url = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/menu/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid() . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image_url = 'uploads/menu/' . $filename;
            }
        }
    }
    
    if ($item_id > 0) {
        // Update existing item
        if ($image_url) {
            $stmt = $pdo->prepare("
                UPDATE business_menu_items 
                SET category_id = ?, name = ?, name_en = ?, description = ?, description_en = ?, price = ?, image_url = ?,
                    is_vegetarian = ?, is_vegan = ?, is_spicy = ?, is_available = ?, updated_at = NOW()
                WHERE id = ? AND business_id = ?
            ");
            $stmt->execute([$category_id, $name, $name_en, $description, $description_en, $price, $image_url,
                           $is_vegetarian, $is_vegan, $is_spicy, $is_available, $item_id, $business_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE business_menu_items 
                SET category_id = ?, name = ?, name_en = ?, description = ?, description_en = ?, price = ?,
                    is_vegetarian = ?, is_vegan = ?, is_spicy = ?, is_available = ?, updated_at = NOW()
                WHERE id = ? AND business_id = ?
            ");
            $stmt->execute([$category_id, $name, $name_en, $description, $description_en, $price,
                           $is_vegetarian, $is_vegan, $is_spicy, $is_available, $item_id, $business_id]);
        }
        
        // Update allergens
        $pdo->prepare("DELETE FROM menu_item_allergens WHERE item_id = ?")->execute([$item_id]);
        foreach ($allergens as $allergen_id) {
            $pdo->prepare("INSERT INTO menu_item_allergens (item_id, allergen_id) VALUES (?, ?)")
                ->execute([$item_id, (int)$allergen_id]);
        }
    } else {
        // Get max sort order
        $max_order = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM business_menu_items WHERE business_id = ?");
        $max_order->execute([$business_id]);
        $sort_order = $max_order->fetchColumn();
        
        // Insert new item
        $stmt = $pdo->prepare("
            INSERT INTO business_menu_items 
            (business_id, category_id, name, name_en, description, description_en, price, image_url, is_vegetarian, is_vegan, is_spicy, is_available, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$business_id, $category_id, $name, $name_en, $description, $description_en, $price, $image_url,
                       $is_vegetarian, $is_vegan, $is_spicy, $is_available, $sort_order]);
        
        $item_id = $pdo->lastInsertId();
        
        // Insert allergens
        foreach ($allergens as $allergen_id) {
            $pdo->prepare("INSERT INTO menu_item_allergens (item_id, allergen_id) VALUES (?, ?)")
                ->execute([$item_id, (int)$allergen_id]);
        }
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
