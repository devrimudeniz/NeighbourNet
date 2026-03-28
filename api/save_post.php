<?php
// Silent error handling for clean JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();
ob_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security_helper.php';

// Clean buffer
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

require_csrf();

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'toggle_save') {
        $post_id = $_POST['post_id'] ?? 0;
        
        // Check if already saved
        $stmt = $pdo->prepare("SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Unsave
            $del = $pdo->prepare("DELETE FROM saved_posts WHERE id = ?");
            $del->execute([$existing['id']]);
            $is_saved = false;
            $msg = 'Gönderi kaydedilenlerden kaldırıldı';
        } else {
            // Save (Default to no collection)
            $ins = $pdo->prepare("INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)");
            $ins->execute([$user_id, $post_id]);
            $is_saved = true;
            $msg = 'Gönderi kaydedildi';
        }
        
        echo json_encode(['status' => 'success', 'is_saved' => $is_saved, 'message' => $msg]);
        
    } elseif ($action === 'create_collection') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) throw new Exception('Koleksiyon adı boş olamaz');
        
        $stmt = $pdo->prepare("INSERT INTO collections (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
        
        echo json_encode(['status' => 'success', 'collection_id' => $pdo->lastInsertId(), 'name' => $name]);
        
    } elseif ($action === 'add_to_collection') {
        $post_id = $_POST['post_id'];
        $collection_id = $_POST['collection_id']; // 0 for remove from collection (keep saved)
        
        // Ensure post is saved first
        $stmt = $pdo->prepare("INSERT IGNORE INTO saved_posts (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
        
        // Update collection
        $upd = $pdo->prepare("UPDATE saved_posts SET collection_id = ? WHERE user_id = ? AND post_id = ?");
        $upd->execute([$collection_id == 0 ? null : $collection_id, $user_id, $post_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Koleksiyona eklendi']);
        
    } elseif ($action === 'get_collections') {
        $stmt = $pdo->prepare("SELECT * FROM collections WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'collections' => $collections]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>
