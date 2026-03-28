<?php
/**
 * API: Answer Listing Question
 * Allows listing owners to answer questions
 */
require_once __DIR__ . '/../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Giriş yapmalısınız']);
    exit();
}

$user_id = $_SESSION['user_id'];
$question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$answer = isset($_POST['answer']) ? trim($_POST['answer']) : '';

if (!$question_id || empty($answer)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz veri']);
    exit();
}

try {
    // Check if this is the listing owner
    $stmt = $pdo->prepare("
        SELECT mq.id, ml.user_id as owner_id 
        FROM marketplace_questions mq 
        JOIN marketplace_listings ml ON mq.listing_id = ml.id 
        WHERE mq.id = ?
    ");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    if (!$question) {
        echo json_encode(['success' => false, 'error' => 'Soru bulunamadı']);
        exit();
    }

    if ($question['owner_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Sadece ilan sahibi cevap verebilir']);
        exit();
    }

    // Update question with answer
    $stmt = $pdo->prepare("UPDATE marketplace_questions SET answer = ?, answered_at = NOW() WHERE id = ?");
    $stmt->execute([$answer, $question_id]);

    echo json_encode([
        'success' => true, 
        'message' => 'Cevabınız kaydedildi',
        'answer' => $answer,
        'answered_at' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
