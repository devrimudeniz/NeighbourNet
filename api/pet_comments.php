<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// GET: List comments for a pet
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pet_id = (int)($_GET['pet_id'] ?? 0);
    
    if (!$pet_id) {
        echo json_encode(['status' => 'error', 'message' => 'Pet ID required']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT c.*, u.username, u.full_name, u.avatar 
                               FROM lost_pet_comments c 
                               JOIN users u ON c.user_id = u.id 
                               WHERE c.lost_pet_id = ? 
                               ORDER BY c.created_at DESC");
        $stmt->execute([$pet_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'comments' => $comments]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// POST: Add a comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if (!$pet_id || empty($comment)) {
        echo json_encode(['status' => 'error', 'message' => 'Pet ID ve yorum gerekli']);
        exit();
    }
    
    if (strlen($comment) > 1000) {
        echo json_encode(['status' => 'error', 'message' => 'Yorum 1000 karakterden uzun olamaz']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO lost_pet_comments (lost_pet_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$pet_id, $user_id, $comment]);
        
        // Get the inserted comment with user info
        $comment_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT c.*, u.username, u.full_name, u.avatar 
                               FROM lost_pet_comments c 
                               JOIN users u ON c.user_id = u.id 
                               WHERE c.id = ?");
        $stmt->execute([$comment_id]);
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'message' => 'Yorum eklendi', 'comment' => $newComment]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
?>
