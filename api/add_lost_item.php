<?php
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once '../includes/push-helper.php';
require_once '../includes/email-helper.php';
require_once '../includes/optimize_upload.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => $lang == 'en' ? 'Please log in' : 'Lütfen giriş yapın']);
    exit();
}

require_csrf();

if (!RateLimiter::check($pdo, 'add_lost_item', 5, 300)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => $lang == 'en' ? 'Too many requests.' : 'Çok fazla istek.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$item_name = trim($_POST['item_name'] ?? '');
$category = $_POST['category'] ?? 'other';
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$contact_phone = trim($_POST['contact_phone'] ?? '');
$status = $_POST['status'] ?? 'lost';

$allowed_cats = ['keys','wallet','phone','bag','glasses','documents','jewelry','other'];
if (!in_array($category, $allowed_cats)) $category = 'other';
if (!in_array($status, ['lost','found'])) $status = 'lost';

if (empty($item_name) || empty($location)) {
    echo json_encode(['status' => 'error', 'message' => $lang == 'en' ? 'Item name and location are required.' : 'Eşya adı ve konum zorunludur.']);
    exit();
}

$photo_url = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $upload_dir = '../uploads/lost_items/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    $file_ary = [
        'name' => $_FILES['photo']['name'],
        'type' => $_FILES['photo']['type'],
        'tmp_name' => $_FILES['photo']['tmp_name'],
        'error' => $_FILES['photo']['error'],
        'size' => $_FILES['photo']['size']
    ];
    $result = gorselOptimizeEt($file_ary, $upload_dir);
    if (isset($result['success'])) {
        $photo_url = 'uploads/lost_items/' . $result['filename'];
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO lost_items (user_id, item_name, category, description, location, photo_url, contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $item_name, $category, $description, $location, $photo_url, $contact_phone, $status]);
    $item_id = $pdo->lastInsertId();

    $status_label = $status == 'lost' ? ($lang == 'en' ? 'Lost' : 'Kayıp') : ($lang == 'en' ? 'Found' : 'Bulundu');
    $title = $status == 'lost' ? "🔑 Kayıp: $item_name" : "✅ Bulundu: $item_name";
    $body = "$location - $item_name";
    $url = "/lost_found?id=$item_id";
    $notif_msg = $title . " - " . $body;

    // Ensure notify_email, notify_push columns exist
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_email'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER service");
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_push TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_email");
        }
    } catch (PDOException $e) { /* ignore */ }

    try {
        $notif_sql = "INSERT INTO notifications (user_id, type, message, link) 
                      SELECT user_id, 'lost_item', ?, ? 
                      FROM subscriptions 
                      WHERE service = 'lost_found' AND user_id != ?";
        $n_stmt = $pdo->prepare($notif_sql);
        $n_stmt->execute([$notif_msg, 'lost_found', $user_id]);
    } catch (PDOException $e) { /* ignore */ }

    // Email + push to subscribers (except author). If notify_* columns missing, treat all as opted-in.
    $subs = [];
    try {
        $sub_stmt = $pdo->prepare("SELECT user_id, COALESCE(notify_email, 1) AS ne, COALESCE(notify_push, 1) AS np FROM subscriptions WHERE service = 'lost_found' AND user_id != ?");
        $sub_stmt->execute([$user_id]);
        $subs = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $sub_stmt = $pdo->prepare("SELECT user_id FROM subscriptions WHERE service = 'lost_found' AND user_id != ?");
            $sub_stmt->execute([$user_id]);
            foreach ($sub_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $subs[] = ['user_id' => $r['user_id'], 'ne' => 1, 'np' => 1];
            }
        } catch (PDOException $e2) { /* ignore */ }
    }
    $site = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com');
    $html = "<p>$title</p><p>$body</p><p><a href=\"$site$url\">İlanı görüntüle</a></p>";
    foreach ($subs as $s) {
        $uid = (int)$s['user_id'];
        if (!empty($s['np'])) {
            sendPushNotification($uid, $title, $body, $url);
        }
        if (!empty($s['ne'])) {
            $em = $pdo->prepare("SELECT email FROM users WHERE id = ? AND email IS NOT NULL AND email != ''");
            $em->execute([$uid]);
            $row = $em->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $ok = sendEmail($row['email'], $title . ' - Kalkan Social', $html);
                if (!$ok) {
                    error_log("add_lost_item: email failed for user_id=$uid to " . $row['email']);
                }
            } else {
                error_log("add_lost_item: subscriber user_id=$uid has no email");
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => $lang == 'en' ? 'Item published.' : 'İlan yayınlandı.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
}
