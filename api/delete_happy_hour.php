<?php
/**
 * Delete Happy Hour Event API
 * Only allows event owners to delete their own events
 */
header('Content-Type: application/json');
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$eventId = $input['id'] ?? null;

if (!$eventId) {
    echo json_encode(['status' => 'error', 'message' => 'Event ID required']);
    exit;
}

try {
    // Check if user owns this event
    $stmt = $pdo->prepare("SELECT user_id FROM happy_hours WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        echo json_encode(['status' => 'error', 'message' => 'Event not found']);
        exit;
    }

    // Only owner or admin can delete
    if ($event['user_id'] != $_SESSION['user_id'] && ($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'You can only delete your own events']);
        exit;
    }

    // Delete the event
    $stmt = $pdo->prepare("DELETE FROM happy_hours WHERE id = ?");
    $stmt->execute([$eventId]);

    echo json_encode(['status' => 'success', 'message' => 'Event deleted successfully']);

} catch (Exception $e) {
    error_log('Delete happy hour error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>
