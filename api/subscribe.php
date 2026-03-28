<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['service'])) {
    echo json_encode(['status' => 'error', 'message' => 'Service required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$service = $input['service'];

try {
    // Check if subscribed
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND service = ?");
    $stmt->execute([$user_id, $service]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Unsubscribe
        $del = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        $del->execute([$exists['id']]);
        echo json_encode(['status' => 'success', 'subscribed' => false, 'message' => 'Unsubscribed']);
    } else {
        // Ensure notify_email, notify_push columns exist
        try {
            $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_email'");
            if ($chk->rowCount() === 0) {
                $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER service");
            }
            $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_push'");
            if ($chk->rowCount() === 0) {
                $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_push TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_email");
            }
        } catch (PDOException $e) { /* ignore */ }
        // Subscribe (with prefs when columns exist)
        $ins = $pdo->prepare("INSERT INTO subscriptions (user_id, service, notify_email, notify_push) VALUES (?, ?, 1, 1)");
        $ins->execute([$user_id, $service]);
        echo json_encode(['status' => 'success', 'subscribed' => true, 'message' => 'Subscribed']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
}
