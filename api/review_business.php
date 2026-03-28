<?php
/**
 * Review Business API
 * Submit a review with optional photo
 */
session_start();
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';

// Disable JSON header until we process file upload
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

require_csrf();

if (!RateLimiter::check($pdo, 'review_biz', 3, 60)) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
    exit();
}

$business_id = (int)($_POST['business_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$user_id = $_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid rating']);
    exit();
}

if ($business_id < 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid business ID']);
    exit();
}

try {
    // Check if already reviewed
    $check = $pdo->prepare("SELECT id FROM business_reviews WHERE business_id = ? AND user_id = ?");
    $check->execute([$business_id, $user_id]);
    
    if ($check->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Already reviewed']);
        exit();
    }

    // Handle image upload
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/reviews/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'review_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.webp';
            $upload_path = $upload_dir . $filename;
            
            // Convert to WebP if possible
            if (function_exists('imagecreatefromstring')) {
                $source_image = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name']));
                if ($source_image) {
                    // Resize if too large (max 1200px)
                    $width = imagesx($source_image);
                    $height = imagesy($source_image);
                    $max_size = 1200;
                    
                    if ($width > $max_size || $height > $max_size) {
                        if ($width > $height) {
                            $new_width = $max_size;
                            $new_height = (int)($height * ($max_size / $width));
                        } else {
                            $new_height = $max_size;
                            $new_width = (int)($width * ($max_size / $height));
                        }
                        
                        $resized = imagecreatetruecolor($new_width, $new_height);
                        imagecopyresampled($resized, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                        imagedestroy($source_image);
                        $source_image = $resized;
                    }
                    
                    // Save as WebP
                    imagewebp($source_image, $upload_path, 85);
                    imagedestroy($source_image);
                    $image_url = 'uploads/reviews/' . $filename;
                }
            } else {
                // Fallback: just move the file
                $filename = 'review_' . $user_id . '_' . time() . '.' . $extension;
                $upload_path = $upload_dir . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
                $image_url = 'uploads/reviews/' . $filename;
            }
        }
    }

    // Insert review with image
    $stmt = $pdo->prepare("INSERT INTO business_reviews (business_id, user_id, rating, comment, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$business_id, $user_id, $rating, $comment, $image_url]);

    $review_id = $pdo->lastInsertId();

    // Update business avg rating and count
    $update = $pdo->prepare("
        UPDATE business_listings SET 
        avg_rating = (SELECT AVG(rating) FROM business_reviews WHERE business_id = ?),
        total_reviews = (SELECT COUNT(*) FROM business_reviews WHERE business_id = ?)
        WHERE id = ?
    ");
    $update->execute([$business_id, $business_id, $business_id]);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'review_id' => $review_id,
        'image' => $image_url
    ]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
}
?>
