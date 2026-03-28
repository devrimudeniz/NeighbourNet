<?php
require_once '../includes/db.php';
require_once '../includes/lang.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$expertise = trim($_POST['area_of_expertise'] ?? '');
$motivation = trim($_POST['motivation'] ?? '');
$socials = trim($_POST['social_links'] ?? '');

// Validation
if (empty($expertise) || strlen($expertise) < 3) {
    echo json_encode(['error' => 'Please specify a valid area of expertise.']);
    exit;
}

if (empty($motivation) || strlen($motivation) < 20) {
    echo json_encode(['error' => 'Please provide a more detailed motivation (min 20 chars).']);
    exit;
}

// Check for existing pending application
$stmt = $pdo->prepare("SELECT id FROM expert_applications WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'You already have a pending application.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO expert_applications (user_id, area_of_expertise, motivation, social_links) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $expertise, $motivation, $socials]);

    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
