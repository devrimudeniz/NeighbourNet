<?php
require_once '../includes/db.php';
require_once '../includes/cache_helper.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$id = $_POST['id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    // Get all photo URLs before deletion
    $stmt = $pdo->prepare("SELECT photo_url FROM lost_pet_photos WHERE lost_pet_id = ?");
    $stmt->execute([$id]);
    $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check ownership of the main report
    $check = $pdo->prepare("SELECT id FROM lost_pets WHERE id = ? AND user_id = ?");
    $check->execute([$id, $user_id]);
    
    if ($check->fetch()) {
        // Delete photos from server
        foreach ($photos as $url) {
            $photo_path = '../' . $url;
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        
        // Delete from database (Cascade will handle lost_pet_photos if defined, 
        // but let's be safe if it's not)
        $pdo->prepare("DELETE FROM lost_pet_photos WHERE lost_pet_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM lost_pets WHERE id = ?")->execute([$id]);
        
        // Invalidate index cache so removed pet disappears immediately
        $cache = new CacheHelper();
        $cache->delete('latest_lost_pets_index_v2');
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Not found or authorized']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
