<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$category = $_POST['category'] ?? 'other';
$quantity = (int)($_POST['quantity'] ?? 1);
$location_name = trim($_POST['location_name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (empty($title) || empty($location_name)) {
    echo json_encode(['success' => false, 'error' => 'Title and Location are required']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO community_items (user_id, title, category, quantity, location_name, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $category, $quantity, $location_name, $description]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error']);
}
?>
