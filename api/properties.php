<?php
require_once '../includes/db.php';
require_once '../includes/optimize_upload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Check user verification
$u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$u_stmt->execute([$user_id]);
$user = $u_stmt->fetch();

$allowed_badges = ['founder', 'moderator', 'business', 'verified'];
$is_verified = in_array($user['badge'], $allowed_badges);

if ($action === 'create') {
    if (!$is_verified) {
        echo json_encode(['status' => 'error', 'message' => 'İlan verebilmek için hesabınızın onaylanmış (Verified) olması gerekmektedir.']);
        exit();
    }
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'short_term';
    $price = (float)($_POST['price'] ?? 0);
    $currency = $_POST['currency'] ?? 'GBP';
    $location = trim($_POST['location'] ?? '');
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (int)($_POST['bathrooms'] ?? 0);
    $area_sqm = (int)($_POST['area_sqm'] ?? 0);
    $has_sea_view = isset($_POST['has_sea_view']) ? 1 : 0;
    $has_pool = isset($_POST['has_pool']) ? 1 : 0;
    $is_dog_friendly = isset($_POST['is_dog_friendly']) ? 1 : 0;
    $wifi_speed = (int)($_POST['wifi_speed'] ?? 0);
    $video_url = trim($_POST['video_url'] ?? '');
    
    // New Specs
    $furnished = isset($_POST['furnished']) ? 1 : 0;
    $parking = isset($_POST['parking']) ? 1 : 0;
    $air_conditioning = isset($_POST['air_conditioning']) ? 1 : 0;
    $heating = isset($_POST['heating']) ? 1 : 0;
    $balcony = isset($_POST['balcony']) ? 1 : 0;
    $garden = isset($_POST['garden']) ? 1 : 0;
    $accessibility = isset($_POST['accessibility']) ? 1 : 0;
    $year_built = !empty($_POST['year_built']) ? (int)$_POST['year_built'] : NULL;

    if (empty($title) || empty($description) || empty($location) || $price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen başlık, açıklama, konum ve fiyat alanlarını doldurun.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO properties 
            (user_id, title, description, type, price, currency, location, bedrooms, bathrooms, area_sqm, 
             has_sea_view, has_pool, is_dog_friendly, wifi_speed, video_url,
             furnished, parking, air_conditioning, heating, balcony, garden, accessibility, year_built) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $user_id, $title, $description, $type, $price, $currency, $location, $bedrooms, $bathrooms, $area_sqm, 
            $has_sea_view, $has_pool, $is_dog_friendly, $wifi_speed, $video_url,
            $furnished, $parking, $air_conditioning, $heating, $balcony, $garden, $accessibility, $year_built
        ]);
        $property_id = $pdo->lastInsertId();

        // Handle Images
        $upload_results = [];
        
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $base_dir = dirname(__DIR__);
            $upload_dir = $base_dir . "/uploads/properties/";
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $main_set = false;

            foreach ($_FILES['images']['name'] as $key => $filename) {
                $error_code = $_FILES['images']['error'][$key] ?? 4;
                
                if ($error_code == 0 && !empty($filename)) {
                    $file_ary = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $_FILES['images']['tmp_name'][$key],
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key]
                    ];
                    
                    $result = gorselOptimizeEt($file_ary, $upload_dir);

                    if (isset($result['success'])) {
                        $is_main = (!$main_set) ? 1 : 0;
                        if ($is_main) $main_set = true;
                        
                        $db_path = "uploads/properties/" . $result['filename'];
                        try {
                            $i_stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_main) VALUES (?, ?, ?)");
                            $i_stmt->execute([$property_id, $db_path, $is_main]);
                            $upload_results[] = ["file" => $filename, "status" => "success"];
                        } catch (Exception $e) {
                            error_log("KalkanSocial DB Error: " . $e->getMessage());
                            $upload_results[] = ["file" => $filename, "status" => "db_fail"];
                        }
                    } else {
                        $upload_results[] = ["file" => $filename, "status" => "optimize_fail", "error" => $result['error'] ?? 'Unknown'];
                    }
                }
            }
        }

        $pdo->commit();
        
        $response = [
            'status' => 'success', 
            'property_id' => $property_id,
            'image_results' => $upload_results
        ];
        if (!empty($upload_errors)) {
            $response['upload_warnings'] = $upload_errors;
        }
        echo json_encode($response);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}

if ($action === 'approve') {
    if (!in_array($user['badge'], ['founder', 'moderator'])) {
        echo json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz yok.']);
        exit();
    }
    
    $property_id = $_POST['property_id'] ?? 0;
    $stmt = $pdo->prepare("UPDATE properties SET status = 'active' WHERE id = ?");
    if ($stmt->execute([$property_id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Onaylama başarısız.']);
    }
}

if ($action === 'delete') {
    $property_id = $_POST['property_id'] ?? 0;
    
    // Check if owner or moderator
    $stmt = $pdo->prepare("SELECT user_id FROM properties WHERE id = ?");
    $stmt->execute([$property_id]);
    $p = $stmt->fetch();
    
    if (!$p) {
        echo json_encode(['status' => 'error', 'message' => 'İlan bulunamadı.']);
        exit();
    }
    
    if ($p['user_id'] != $user_id && !in_array($user['badge'], ['founder', 'moderator'])) {
        echo json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz yok.']);
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
    if ($stmt->execute([$property_id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Silme başarısız.']);
    }
}
?>
