<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $ride_date = $_POST['ride_date'] ?? '';
    $ride_time = $_POST['ride_time'] ?? '';
    $seats = (int)($_POST['seats'] ?? 1);
    $price = trim($_POST['price'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (empty($origin) || empty($destination) || empty($ride_date) || empty($ride_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen tüm zorunlu alanları doldurun']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO rides (user_id, origin, destination, ride_date, ride_time, seats, price, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $origin, $destination, $ride_date, $ride_time, $seats, $price, $note])) {
        // Trigger Notifications
        try {
            $notif_msg = "New Ride Alert: $origin to $destination";
            $notif_link = "rides.php";
            
            $notif_sql = "INSERT INTO notifications (user_id, type, message, link) 
                        SELECT user_id, 'ride_alert', ?, ? 
                        FROM subscriptions 
                        WHERE service = 'rides' AND user_id != ?";
            $n_stmt = $pdo->prepare($notif_sql);
            $n_stmt->execute([$notif_msg, $notif_link, $user_id]);
        } catch (PDOException $e) {
            // Ignore if notifications/subscriptions table issue
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Bir hata oluştu']);
    }
} 

elseif ($action === 'delete') {
    $ride_id = (int)($_POST['ride_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM rides WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$ride_id, $user_id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Yetkiniz yok veya ilan bulunamadı']);
    }
}
?>
