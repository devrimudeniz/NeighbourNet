<?php
/**
 * Save Cover/Avatar Photo Position API
 * Saves the background position (x%, y%) for the user's cover or avatar photo
 */
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$position_x = isset($_POST['position_x']) ? (float)$_POST['position_x'] : 50;
$position_y = isset($_POST['position_y']) ? (float)$_POST['position_y'] : 50;
$type = isset($_POST['type']) ? $_POST['type'] : 'cover'; // 'cover' or 'avatar'

// Whitelist column names - SQL injection koruması
$allowed_columns = ['cover_position', 'avatar_position'];
$column = ($type === 'avatar') ? 'avatar_position' : 'cover_position';
if (!in_array($column, $allowed_columns, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
    exit;
}

// Clamp values between 0 and 100
$position_x = max(0, min(100, $position_x));
$position_y = max(0, min(100, $position_y));

$position_value = round($position_x, 1) . ' ' . round($position_y, 1);

try {
    // Check if column exists, add if not (safe - whitelist validated)
    $check = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
    $check->execute([$column]);
    if ($check->rowCount() == 0) {
        // Column name is from whitelist, safe to interpolate
        $pdo->exec("ALTER TABLE users ADD COLUMN `$column` VARCHAR(20) DEFAULT '50 50'");
    }
    
    $stmt = $pdo->prepare("UPDATE users SET `$column` = ? WHERE id = ?");
    $stmt->execute([$position_value, $user_id]);
    
    echo json_encode([
        'status' => 'success',
        'message' => ucfirst($type) . ' position saved',
        'position' => $position_value,
        'type' => $type
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error'
    ]);
}
