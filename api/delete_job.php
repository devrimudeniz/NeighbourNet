<?php
/**
 * Delete Job API
 * Only the job owner can delete their own job listings
 */

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$job_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid job ID']);
    exit();
}

try {
    // First, verify the user owns this job
    $check = $pdo->prepare("SELECT employer_id FROM job_listings WHERE id = ?");
    $check->execute([$job_id]);
    $job = $check->fetch();

    if (!$job) {
        echo json_encode(['success' => false, 'error' => 'Job not found']);
        exit();
    }

    if ($job['employer_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'You are not authorized to delete this job']);
        exit();
    }

    // Delete the job (or set inactive - safer approach)
    $stmt = $pdo->prepare("UPDATE job_listings SET is_active = 0 WHERE id = ?");
    $stmt->execute([$job_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
