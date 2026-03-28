<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$subscription = $data['subscription'] ?? null;

if (!$subscription || !isset($subscription['endpoint'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid subscription data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$endpoint = $subscription['endpoint'];
$p256dh = $subscription['keys']['p256dh'] ?? '';
$auth = $subscription['keys']['auth'] ?? '';

try {
    // Check if subscription already exists
    $check = $pdo->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
    $check->execute([$user_id, $endpoint]);
    
    if ($check->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Subscription already exists']);
        exit();
    }

    // Save new subscription
    $stmt = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $endpoint, $p256dh, $auth])) {
        error_log("Subscribe API: Success - Inserted ID: " . $pdo->lastInsertId());
        echo json_encode(['success' => true, 'message' => 'Subscription saved']);
    } else {
        error_log("Subscribe API: Insert Failed - " . print_r($stmt->errorInfo(), true));
        echo json_encode(['success' => false, 'error' => 'Database insert failed']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
