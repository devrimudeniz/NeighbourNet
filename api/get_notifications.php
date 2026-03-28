<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    try {
        // Get unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread_count = $stmt->fetchColumn();
        
        // Get latest notifications with actor info
        $sql = "SELECT n.*, u.username as actor_name, u.avatar as actor_avatar 
                FROM notifications n 
                LEFT JOIN users u ON n.actor_id = u.id 
                WHERE n.user_id = ? 
                ORDER BY n.created_at DESC LIMIT 10";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format for frontend
        foreach ($notifications as &$notif) {
            $notif['message'] = $notif['content']; // Map content to message
            $notif['date_formatted'] = date('d.m H:i', strtotime($notif['created_at']));
            $notif['time_ago'] = time_elapsed_string($notif['created_at']);
            
            // Fix avatar if null
            if (empty($notif['actor_avatar'])) {
                $notif['actor_avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($notif['actor_name'] ?? 'System');
            }
            if (empty($notif['actor_name'])) {
                $notif['actor_name'] = 'System';
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'unread_count' => $unread_count,
            'notifications' => $notifications
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'yıl',
        'm' => 'ay',
        'w' => 'hafta',
        'd' => 'gün',
        'h' => 'saat',
        'i' => 'dakika',
        's' => 'saniye',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' önce' : 'şimdi';
}
?>
