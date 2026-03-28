<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

require_csrf();

if (!RateLimiter::check($pdo, 'add_story', 5, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$caption = trim($_POST['caption'] ?? '');
$visibility = isset($_POST['visibility']) && $_POST['visibility'] === 'friends_only' ? 'friends_only' : 'everyone';
$media_url = null;
$media_type = 'image';

require_once __DIR__ . '/../includes/image_helper.php';
require_once __DIR__ . '/../includes/optimize_upload.php';

// Handle file upload
if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
    $upload_dir = "../uploads/stories/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_videos = ['mp4', 'mov', 'avi', 'webm'];
    
    if (in_array($file_ext, $allowed_images)) {
        $result = gorselOptimizeEt($_FILES['media'], $upload_dir);
        if (isset($result['success'])) {
            $media_type = 'image';
            $media_url = 'uploads/stories/' . $result['filename'];
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Failed to optimize image']);
            exit();
        }
    } elseif (in_array($file_ext, $allowed_videos)) {
        $media_type = 'video';
        $new_filename = 'story_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            $media_url = 'uploads/stories/' . $new_filename;
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to upload video']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No media file provided']);
    exit();
}

// Calculate expiration time (24 hours from now)
$expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Insert story into database
try {
    // Check if visibility column exists (migration may not have run)
    $has_visibility = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM stories LIKE 'visibility'");
        $has_visibility = $col->fetch() !== false;
    } catch (PDOException $e) {}
    
    if ($has_visibility) {
        $stmt = $pdo->prepare("INSERT INTO stories (user_id, media_url, media_type, caption, expires_at, visibility) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $media_url, $media_type, $caption, $expires_at, $visibility]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO stories (user_id, media_url, media_type, caption, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $media_url, $media_type, $caption, $expires_at]);
    }
    
    $story_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'story_id' => $story_id,
        'message' => 'Story added successfully',
        'expires_at' => $expires_at
    ]);
} catch (PDOException $e) {
    // Clean up uploaded file if database insert fails
    if ($media_url && file_exists($media_url)) {
        unlink($media_url);
    }
    echo json_encode(['success' => false, 'error' => 'An error occurred']);
}
?>
