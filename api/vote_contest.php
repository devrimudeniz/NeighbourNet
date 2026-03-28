<?php
require_once '../includes/db.php';
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

$submission_id = (int)($_POST['submission_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$submission_id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    // Constraint: Prevent self-voting
    $sub_check = $pdo->prepare("SELECT user_id FROM contest_submissions WHERE id = ?");
    $sub_check->execute([$submission_id]);
    $owner_id = $sub_check->fetchColumn();

    if ($owner_id == $user_id) {
        echo json_encode(['error' => 'Kendi fotoğrafına oy veremezsin!']);
        exit;
    }

    // Check if already voted
    $check = $pdo->prepare("SELECT id FROM contest_votes WHERE user_id = ? AND submission_id = ?");
    $check->execute([$user_id, $submission_id]);
    $exists = $check->fetchColumn();

    if ($exists) {
        // Remove vote
        $del = $pdo->prepare("DELETE FROM contest_votes WHERE user_id = ? AND submission_id = ?");
        $del->execute([$user_id, $submission_id]);
        $action = 'removed';
    } else {
        // Add vote
        $ins = $pdo->prepare("INSERT INTO contest_votes (user_id, submission_id) VALUES (?, ?)");
        $ins->execute([$user_id, $submission_id]);
        $action = 'added';
    }

    // Get new count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM contest_votes WHERE submission_id = ?");
    $countStmt->execute([$submission_id]);
    $new_count = $countStmt->fetchColumn();

    echo json_encode(['success' => true, 'action' => $action, 'new_count' => $new_count]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
