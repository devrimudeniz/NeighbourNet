<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$venue_name = $_POST['venue_name'] ?? '';
$description = $_POST['description'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$event_type = $_POST['event_type'] ?? 'happy_hour';
$performer_name = $_POST['performer_name'] ?? null;
$music_genre = $_POST['music_genre'] ?? null;

if (empty($venue_name) || empty($start_time) || empty($end_time)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill required fields']);
    exit;
}

if ($event_type == 'live_music' && empty($performer_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Performer name is required for live music']);
    exit;
}

// Handle Photo Upload (Optimized)
require_once '../includes/optimize_upload.php';
$photo_url = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $upload_dir = '../uploads/happy_hour/';
    
    $result = gorselOptimizeEt($_FILES['photo'], $upload_dir, 85);
    
    if (isset($result['success'])) {
        // Return relative path for DB
        $photo_url = 'uploads/happy_hour/' . $result['filename'];
    } else {
        // If optimization fails, log error but maybe continue without photo or return error?
        // Ideally return error to user
        echo json_encode(['status' => 'error', 'message' => $result['error']]);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO happy_hours (user_id, venue_name, description, start_time, end_time, photo_url, event_type, performer_name, music_genre) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $venue_name, $description, $start_time, $end_time, $photo_url, $event_type, $performer_name, $music_genre])) {
        
        // Notification Logic
        if ($event_type == 'live_music') {
             $notif_msg = "Live Music! 🎸 $venue_name: $performer_name ($start_time)";
        } else {
             $notif_msg = "Happy Hour Alert! 🍹 $venue_name: $description ($start_time-$end_time)";
        }
        
        $notif_link = "happy_hour.php";
        
        // Site Notification
        try {
            $notif_sql = "INSERT INTO notifications (user_id, type, message, link) 
                          SELECT user_id, 'happy_hour', ?, ? 
                          FROM subscriptions 
                          WHERE service = 'happy_hour' AND user_id != ?";
            $n_stmt = $pdo->prepare($notif_sql);
            $n_stmt->execute([$notif_msg, $notif_link, $user_id]);
        } catch (PDOException $e) { /* Ignore */ }

        // Optional: Mass Push could go here if implemented for this type

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
