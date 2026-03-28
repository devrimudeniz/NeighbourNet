<?php
/**
 * API: Event RSVP Actions (RSVP, Get Attendees)
 */
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

require_csrf();

if (!RateLimiter::check($pdo, 'event_rsvp', 10, 60)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests. Please try again later.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$event_id = (int)($_POST['event_id'] ?? 0);

if (!$event_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid event']);
    exit;
}

// Get event details for validation
$stmt = $pdo->prepare("SELECT attendee_limit, attendee_count FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    echo json_encode(['status' => 'error', 'message' => 'Event not found']);
    exit;
}

try {
    if ($action === 'rsvp') {
        $status = $_POST['status'] ?? 'going'; // going, interested, not_going
        
        if (!in_array($status, ['going', 'interested', 'not_going'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            exit;
        }

        // Check if limit reached (only if status is 'going')
        if ($status === 'going' && $event['attendee_limit'] > 0) {
            // Check if user is already going (updating status)
            $current = $pdo->prepare("SELECT status FROM event_attendees WHERE event_id = ? AND user_id = ?");
            $current->execute([$event_id, $user_id]);
            $existing = $current->fetch();
            
            // If new 'going' and limit reached
            if ((!$existing || $existing['status'] !== 'going') && $event['attendee_count'] >= $event['attendee_limit']) {
                echo json_encode(['status' => 'error', 'message' => $lang == 'en' ? 'Event is full!' : 'Etkinlik dolu!']);
                exit;
            }
        }
        
        // Upsert RSVP
        $sql = "INSERT INTO event_attendees (event_id, user_id, status) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status)";
        $pdo->prepare($sql)->execute([$event_id, $user_id, $status]);
        
        // Update total count
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_attendees WHERE event_id = ? AND status = 'going'");
        $count_stmt->execute([$event_id]);
        $count = $count_stmt->fetchColumn();
        $pdo->prepare("UPDATE events SET attendee_count = ? WHERE id = ?")->execute([$count, $event_id]);
        
        echo json_encode(['status' => 'success', 'message' => $t['rsvp_success'], 'new_status' => $status]);
        
    } else if ($action === 'get_attendees') {
        // Get list of attendees
        $stmt = $pdo->prepare("SELECT ea.*, u.username, u.full_name, u.avatar, u.badge 
                               FROM event_attendees ea 
                               JOIN users u ON ea.user_id = u.id 
                               WHERE ea.event_id = ? AND ea.status IN ('going', 'interested')
                               ORDER BY ea.status ASC, ea.created_at DESC 
                               LIMIT 50");
        $stmt->execute([$event_id]);
        $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'attendees' => $attendees]);
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
