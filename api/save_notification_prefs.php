<?php
/**
 * Save notification preferences (subscribe + notify_email, notify_push) per service.
 * POST: service (pati_safe | lost_found | events), subscribed (0|1), notify_email (0|1), notify_push (0|1)
 */
require_once __DIR__ . '/../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$service = isset($_POST['service']) ? trim($_POST['service']) : '';
$subscribed = isset($_POST['subscribed']) ? (int)$_POST['subscribed'] : 0;
$notify_email = isset($_POST['notify_email']) ? (int)$_POST['notify_email'] : 1;
$notify_push = isset($_POST['notify_push']) ? (int)$_POST['notify_push'] : 1;

$allowed = ['pati_safe', 'lost_found', 'events'];
if (!in_array($service, $allowed, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid service']);
    exit;
}

// Ensure columns exist (same as notification_preferences.php)
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

try {
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND service = ?");
    $stmt->execute([$user_id, $service]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subscribed) {
        $notify_email = $notify_email ? 1 : 0;
        $notify_push = $notify_push ? 1 : 0;
        if ($row) {
            $up = $pdo->prepare("UPDATE subscriptions SET notify_email = ?, notify_push = ? WHERE id = ?");
            $up->execute([$notify_email, $notify_push, $row['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO subscriptions (user_id, service, notify_email, notify_push) VALUES (?, ?, ?, ?)");
            $ins->execute([$user_id, $service, $notify_email, $notify_push]);
        }
        echo json_encode(['status' => 'success', 'subscribed' => true]);
    } else {
        if ($row) {
            $del = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
            $del->execute([$row['id']]);
        }
        echo json_encode(['status' => 'success', 'subscribed' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
}
