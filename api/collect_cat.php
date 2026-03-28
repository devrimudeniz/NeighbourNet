<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/optimize_upload.php'; // Reuse optimization logic
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cat_id = $_POST['cat_id'] ?? null;
// Also support JSON input for simple check-ins without photo
if (!$cat_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cat_id = $input['cat_id'] ?? null;
}

if (!$cat_id) {
    echo json_encode(['status' => 'error', 'message' => 'Cat ID required']);
    exit;
}

// Check if already collected
$check = $pdo->prepare("SELECT id FROM user_cat_collection WHERE user_id = ? AND cat_id = ?");
$check->execute([$user_id, $cat_id]);
if ($check->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You already collected this cat!']);
    exit;
}

// Handle Proof Photo
$user_photo_url = null;

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    // Detailed upload error reporting
    $error_code = $_FILES['photo']['error'] ?? 'No file sent';
    $error_msg = match($error_code) {
        UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
        UPLOAD_ERR_partial => 'File validation failed',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        default => 'Upload failed with code ' . $error_code
    };
    echo json_encode(['status' => 'error', 'message' => $error_msg]);
    exit;
}

$upload_dir = '../uploads/catdex/';
$result = gorselOptimizeEt($_FILES['photo'], $upload_dir, 80); 

if (!isset($result['success'])) {
    echo json_encode(['status' => 'error', 'message' => 'Optimization failed: ' . ($result['error'] ?? 'Unknown error')]);
    exit;
}

$user_photo_url = 'uploads/catdex/' . $result['filename'];
$status = 'pending'; // Always pending admin approval

try {
    $stmt = $pdo->prepare("INSERT INTO user_cat_collection (user_id, cat_id, user_photo, status) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $cat_id, $user_photo_url, $status])) {
        echo json_encode(['status' => 'success', 'message' => 'Proof uploaded! Waiting for admin approval.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database insert failed']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
