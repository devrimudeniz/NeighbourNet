<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/email-helper.php';
require_once '../includes/optimize_upload.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

require_csrf();

if (!RateLimiter::check($pdo, 'add_lost_pet', 3, 300)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests. Please try again later.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$pet_name = $_POST['pet_name'] ?? '';
$pet_type = $_POST['pet_type'] ?? 'other';
$description = $_POST['description'] ?? '';
$location = $_POST['location'] ?? '';
$contact_phone = $_POST['contact_phone'] ?? '';

// New fields
$status_type = $_POST['status_type'] ?? 'lost';
$gender = $_POST['gender'] ?? 'unknown';
$color = $_POST['color'] ?? null;
$has_collar = isset($_POST['has_collar']) ? 1 : 0;
$has_chip = isset($_POST['has_chip']) ? 1 : 0;
$distinctive_features = $_POST['distinctive_features'] ?? null;
$lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;

if (empty($pet_name) || empty($location)) {
    echo json_encode(['status' => 'error', 'message' => 'Eksik bilgi: İsim ve Konum zorunludur.']);
    exit();
}

// Multi-Image Upload
$photo_urls = [];
if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $upload_dir = '../uploads/pets/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    foreach ($_FILES['photos']['name'] as $key => $filename) {
        if ($_FILES['photos']['error'][$key] == 0) {
            $file_ary = [
                'name' => $_FILES['photos']['name'][$key],
                'type' => $_FILES['photos']['type'][$key],
                'tmp_name' => $_FILES['photos']['tmp_name'][$key],
                'error' => $_FILES['photos']['error'][$key],
                'size' => $_FILES['photos']['size'][$key]
            ];
            
            $result = gorselOptimizeEt($file_ary, $upload_dir);
            if (isset($result['success'])) {
                $photo_urls[] = 'uploads/pets/' . $result['filename'];
                $full_path = realpath($upload_dir) . '/' . $result['filename'];
                $thumb_path = dirname($full_path) . '/' . pathinfo($result['filename'], PATHINFO_FILENAME) . '_thumb.webp';
                createThumbnail($full_path, $thumb_path, 120, 120);
            }
        }
    }
}

$primary_photo = $photo_urls[0] ?? null;

try {
    // Insert into DB with new fields
    $stmt = $pdo->prepare("INSERT INTO lost_pets (user_id, pet_name, pet_type, description, location, contact_phone, photo_url, status_type, gender, color, has_collar, has_chip, distinctive_features, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $pet_name, $pet_type, $description, $location, $contact_phone, $primary_photo, $status_type, $gender, $color, $has_collar, $has_chip, $distinctive_features, $lat, $lng]);
    $pet_id = $pdo->lastInsertId();

    // Insert additional photos
    if (!empty($photo_urls)) {
        $photo_stmt = $pdo->prepare("INSERT INTO lost_pet_photos (lost_pet_id, photo_url) VALUES (?, ?)");
        foreach ($photo_urls as $url) {
            $photo_stmt->execute([$pet_id, $url]);
        }
    }

    $status_label = $status_type == 'adoption' ? 'Sahiplendirme' : 'Kayıp';
    $title = $status_type == 'adoption' ? "💖 Sahiplendirme: $pet_name" : "🚨 Kayıp İlanı: $pet_name";
    $body = "$location konumunda bir $pet_type için $status_label ilanı oluşturuldu!";
    $url = "/pati_safe.php?id=$pet_id";
    $notif_msg = $title . " - " . $body;
    $notif_link = "pati_safe";

    // Ensure notify_email, notify_push exist on subscriptions
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_email'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER service");
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_push TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_email");
        }
    } catch (PDOException $e) { /* ignore */ }

    // In-app notification for all pati_safe subscribers (except author)
    try {
        $notif_sql = "INSERT INTO notifications (user_id, type, message, link) 
                      SELECT user_id, 'pet_alert', ?, ? 
                      FROM subscriptions 
                      WHERE service = 'pati_safe' AND user_id != ?";
        $n_stmt = $pdo->prepare($notif_sql);
        $n_stmt->execute([$notif_msg, $notif_link, $user_id]);
    } catch (PDOException $e) { /* Ignore */ }

    // Per-subscriber email and push (by preference)
    try {
        $sub_stmt = $pdo->prepare("SELECT user_id, COALESCE(notify_email, 1) AS ne, COALESCE(notify_push, 1) AS np FROM subscriptions WHERE service = 'pati_safe' AND user_id != ?");
        $sub_stmt->execute([$user_id]);
        $subs = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    $site = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com');
                    $html = "<p>$title</p><p>$body</p><p><a href=\"$site$url\">İlanı görüntüle</a></p>";
                    sendEmail($row['email'], $title . ' - Kalkan Social', $html);
                }
            }
        }
    } catch (PDOException $e) { /* ignore */ }

    echo json_encode(['status' => 'success', 'message' => 'İlan oluşturuldu ve bildirim gönderildi.']);

} catch (PDOException $e) {
    if ($e->getCode() == '42S22' && strpos($e->getMessage(), 'status_type') !== false) {
        // Self-Healing: Run migration if columns are missing
        try {
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN status_type ENUM('lost', 'found', 'adoption') DEFAULT 'lost'");
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN has_collar TINYINT(1) DEFAULT 0");
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN has_chip TINYINT(1) DEFAULT 0");
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN gender ENUM('male', 'female', 'unknown') DEFAULT 'unknown'");
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN color VARCHAR(100) DEFAULT NULL");
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN distinctive_features TEXT DEFAULT NULL");
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN lat DECIMAL(10, 8) DEFAULT NULL");
            $pdo->exec("ALTER TABLE lost_pets ADD COLUMN lng DECIMAL(11, 8) DEFAULT NULL");
            
            // Retry the original insert
            $stmt = $pdo->prepare("INSERT INTO lost_pets (user_id, pet_name, pet_type, description, location, contact_phone, photo_url, status_type, gender, color, has_collar, has_chip, distinctive_features, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $pet_name, $pet_type, $description, $location, $contact_phone, $primary_photo, $status_type, $gender, $color, $has_collar, $has_chip, $distinctive_features, $lat, $lng]);
            
            echo json_encode(['status' => 'success', 'message' => 'Schema otomatik güncellendi ve ilan oluşturuldu.']);
            exit();
        } catch (Exception $em) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
            exit();
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
}
?>
