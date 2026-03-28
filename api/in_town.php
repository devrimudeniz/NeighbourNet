<?php
// Silently handle errors to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

/**
 * API: Toggle In-Town Status
 * Allows users to set their "in town" status
 */
session_start();
ob_start(); // Start buffer to catch any unwanted output from includes

try {
    require_once __DIR__ . '/../includes/db.php';
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection error']);
    exit();
}

// Clean the buffer before sending JSON headers
ob_clean();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giriş yapmalısınız']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'toggle') {
    // Get current status
    $stmt = $pdo->prepare("SELECT is_in_town FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetchColumn();
    
    $new_status = $current ? 0 : 1;
    $message = trim($_POST['message'] ?? '');
    
    // Update status
    if ($new_status) {
        // Arriving in town
        $stmt = $pdo->prepare("UPDATE users SET is_in_town = 1, in_town_since = NOW(), in_town_message = ? WHERE id = ?");
        $stmt->execute([$message ?: null, $user_id]);
        
        // Notify friends that user arrived
        // notifyFriendsArrival($pdo, $user_id);
        
    } else {
        // Leaving town
        $stmt = $pdo->prepare("UPDATE users SET is_in_town = 0, in_town_since = NULL, in_town_message = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    
    echo json_encode([
        'status' => 'success',
        'is_in_town' => $new_status,
        'message' => $new_status ? 'Kalkan\'a hoş geldiniz! 🌊' : 'İyi yolculuklar! 👋'
    ]);
    
} elseif ($action === 'get_in_town') {
    // Get list of friends who are currently in town
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar, u.in_town_since, u.in_town_message,
               u.badge, u.venue_name
        FROM users u
        WHERE u.is_in_town = 1 
        AND u.id != ?
        AND (
            u.id IN (
                SELECT receiver_id FROM friendships WHERE requester_id = ? AND status = 'accepted'
                UNION
                SELECT requester_id FROM friendships WHERE receiver_id = ? AND status = 'accepted'
            )
            OR u.badge IN ('founder', 'moderator', 'verified_business')
        )
        ORDER BY u.in_town_since DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $in_town = $stmt->fetchAll();
    
    // Format dates
    foreach ($in_town as &$user) {
        if ($user['in_town_since']) {
            $since = new DateTime($user['in_town_since']);
            $now = new DateTime();
            $diff = $now->diff($since);
            
            if ($diff->days == 0) {
                $user['since_text'] = 'Bugün geldi';
            } elseif ($diff->days == 1) {
                $user['since_text'] = 'Dün geldi';
            } elseif ($diff->days < 7) {
                $user['since_text'] = $diff->days . ' gün önce geldi';
            } else {
                $user['since_text'] = $since->format('d M') . '\'den beri';
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'count' => count($in_town),
        'users' => $in_town
    ]);
    
} elseif ($action === 'set_message') {
    // Update just the message
    $message = trim($_POST['message'] ?? '');
    $stmt = $pdo->prepare("UPDATE users SET in_town_message = ? WHERE id = ?");
    $stmt->execute([$message ?: null, $user_id]);
    
    echo json_encode(['status' => 'success']);
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
}

/**
 * Notify friends when user arrives in town
 */
function notifyFriendsArrival($pdo, $user_id) {
    // Get user info
    $stmt = $pdo->prepare("SELECT full_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) return;
    
    // Get friends
    $stmt = $pdo->prepare("
        SELECT receiver_id as friend_id FROM friendships WHERE requester_id = ? AND status = 'accepted'
        UNION
        SELECT requester_id as friend_id FROM friendships WHERE receiver_id = ? AND status = 'accepted'
    ");
    $stmt->execute([$user_id, $user_id]);
    $friends = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($friends)) return;
    
    // Create notifications
    $notif_stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, actor_id, message, link)
        VALUES (?, 'in_town', ?, ?, ?)
    ");
    
    $message = $user['full_name'] . ' Kalkan\'a geldi! 🌊';
    $link = 'profile?uid=' . $user_id;
    
    foreach ($friends as $friend_id) {
        try {
            $notif_stmt->execute([$friend_id, $user_id, $message, $link]);
        } catch (Exception $e) {
            // Notification might fail, continue
        }
    }
}
?>
