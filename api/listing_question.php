<?php
/**
 * API: Submit Listing Question
 * Allows users to ask questions about a listing
 */
require_once __DIR__ . '/../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Giriş yapmalısınız']);
    exit();
}

$user_id = $_SESSION['user_id'];
$listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;
$question = isset($_POST['question']) ? trim($_POST['question']) : '';

if (!$listing_id || empty($question)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz veri']);
    exit();
}

// Check if listing exists
$stmt = $pdo->prepare("SELECT id, user_id FROM marketplace_listings WHERE id = ?");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch();

if (!$listing) {
    echo json_encode(['success' => false, 'error' => 'İlan bulunamadı']);
    exit();
}

// Can't ask question on your own listing
if ($listing['user_id'] == $user_id) {
    echo json_encode(['success' => false, 'error' => 'Kendi ilanınıza soru soramazsınız']);
    exit();
}

try {
    // Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS marketplace_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            listing_id INT NOT NULL,
            user_id INT NOT NULL,
            question TEXT NOT NULL,
            answer TEXT DEFAULT NULL,
            answered_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_listing (listing_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $pdo->prepare("INSERT INTO marketplace_questions (listing_id, user_id, question) VALUES (?, ?, ?)");
    $stmt->execute([$listing_id, $user_id, $question]);

    // Get the newly created question with user info
    $q_id = $pdo->lastInsertId();
    $q_stmt = $pdo->prepare("
        SELECT mq.*, u.full_name, u.avatar 
        FROM marketplace_questions mq 
        JOIN users u ON mq.user_id = u.id 
        WHERE mq.id = ?
    ");
    $q_stmt->execute([$q_id]);
    $new_question = $q_stmt->fetch();

    echo json_encode([
        'success' => true, 
        'message' => 'Sorunuz gönderildi',
        'question' => $new_question
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
