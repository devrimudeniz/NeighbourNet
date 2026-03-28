<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$business_id = (int)$_POST['business_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if already favorited
    $check = $pdo->prepare("SELECT id FROM business_favorites WHERE business_id = ? AND user_id = ?");
    $check->execute([$business_id, $user_id]);
    
    if ($check->fetch()) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM business_favorites WHERE business_id = ? AND user_id = ?");
        $stmt->execute([$business_id, $user_id]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO business_favorites (business_id, user_id) VALUES (?, ?)");
        $stmt->execute([$business_id, $user_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
