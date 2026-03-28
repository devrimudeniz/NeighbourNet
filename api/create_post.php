<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');
require_once '../includes/image_helper.php';
require_once '../includes/friendship-helper.php';
require_once '../includes/optimize_upload.php';
require_once '../includes/RateLimiter.php';
require_once '../includes/profanity_filter.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        // Debug info
        $debug = [
            'success' => false, 
            'message' => 'Invalid Security Token (CSRF)',
            'debug' => [
                'received_token' => $token ? substr($token, 0, 10) . '...' : 'empty',
                'session_token' => isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 10) . '...' : 'empty',
                'session_id' => session_id()
            ]
        ];
        echo json_encode($debug);
        exit();
    }
}

$user_id = $_SESSION['user_id'];

// Rate limit: max 5 posts per minute per IP (bot/spam protection)
if (!RateLimiter::check($pdo, 'create_post', 5, 60)) {
    echo json_encode([
        'success' => false,
        'message' => (($_SESSION['language'] ?? $_COOKIE['language'] ?? '') === 'en') 
            ? 'Too many posts. Please wait a minute.' 
            : 'Çok fazla paylaşım. Lütfen 1 dakika bekleyin.'
    ]);
    exit();
}

$content = trim($_POST['content'] ?? '');
$location = trim($_POST['location'] ?? '');
$feeling_action = trim($_POST['feeling_action'] ?? '');
$feeling_value = trim($_POST['feeling_value'] ?? '');
$wall_user_id = isset($_POST['wall_user_id']) ? (int)$_POST['wall_user_id'] : null;

// If posting to someone else's wall, verify friendship
if ($wall_user_id && $wall_user_id != $user_id) {
    $friendship_status = getFriendshipStatus($user_id, $wall_user_id);
    if ($friendship_status !== 'friends') {
        echo json_encode(['success' => false, 'message' => 'You can only post on friends\' walls']);
        exit();
    }
}

$media_type = 'text';
$media_url = null;
$uploaded_images = [];

// Handle Multiple Image Upload (up to 10)
if (isset($_FILES['images'])) {
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $files = $_FILES['images'];
    $file_count = is_array($files['name']) ? count($files['name']) : 0;
    
    // Limit to 10 images
    $file_count = min($file_count, 10);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] == 0) {
            // Create a single-file array for the optimizer
            $single_file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = gorselOptimizeEt($single_file, $target_dir);
            
            if (isset($result['success'])) {
                $uploaded_images[] = 'uploads/' . $result['filename'];
            }
        }
    }
    
    if (count($uploaded_images) > 0) {
        $media_type = 'image';
        $media_url = $uploaded_images[0]; // First image as main
    }
}

// Handle Single Image Upload (backwards compatibility)
if (empty($uploaded_images) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "../uploads/";
    $result = gorselOptimizeEt($_FILES['image'], $target_dir);
    
    if (isset($result['success'])) {
        $media_type = 'image';
        $media_url = 'uploads/' . $result['filename'];
        $uploaded_images[] = $media_url;
    }
}

// Handle Multiple Video Upload (up to 10 total media limit logic needed but let's just process them)
if (isset($_FILES['videos'])) {
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $files = $_FILES['videos'];
    $file_count = is_array($files['name']) ? count($files['name']) : 0;
    
    // Limit total media? Let's just process for now
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] == 0) {
            $file_ext = strtolower(pathinfo($files["name"][$i], PATHINFO_EXTENSION));
            $new_name = uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $new_name;
            
            $allowed_video_exts = ['mp4', 'mov', 'avi', 'mpeg', 'webm'];
            if (in_array($file_ext, $allowed_video_exts)) {
                if (move_uploaded_file($files["tmp_name"][$i], $target_file)) {
                    $uploaded_images[] = 'uploads/' . $new_name; // Treat as generic media url in the same array
                    
                    // If this is the FIRST media found (and no images yet), set type to video
                    if (empty($media_type) || $media_type == 'text') {
                        $media_type = 'video';
                        $media_url = 'uploads/' . $new_name;
                    }
                }
            }
        }
    }
}

// Check mixed content
// If we have both images and videos, usually platforms default to 'gallery' or 'mixed', 
// but if your system uses 'image' type for post_images carousel, we can keep 'image' or 'video' based on first item,
// or better: if we have multiple items, let's ensure feed handles them.
// For now, if we have uploaded_images > 0, we set media_type.
if (count($uploaded_images) > 0) {
    if (empty($media_type) || $media_type == 'text') {
         // Default to image if not set yet, or detect based on first file extension?
         $first_file = $uploaded_images[0];
         $ext = strtolower(pathinfo($first_file, PATHINFO_EXTENSION));
         if(in_array($ext, ['mp4', 'mov', 'avi', 'wmv', 'flv', '3gp', 'mkv'])) {
             $media_type = 'video';
         } else {
             $media_type = 'image';
         }
         $media_url = $first_file;
    }
}

// Handle Link Preview Data
$link_url = !empty($_POST['link_url']) ? trim($_POST['link_url']) : null;
$link_title = !empty($_POST['link_title']) ? trim($_POST['link_title']) : null;
$link_description = !empty($_POST['link_description']) ? trim($_POST['link_description']) : null;
$link_image = !empty($_POST['link_image']) ? trim($_POST['link_image']) : null;
$video_url = !empty($_POST['video_url']) ? trim($_POST['video_url']) : null;

// Only use video_url if a video file wasn't uploaded
if ($video_url && $media_type !== 'video') {
    $media_type = 'video';
    
    // Auto-convert YouTube/Vimeo to Embed URL
    if (strpos($video_url, 'youtube.com/watch?v=') !== false) {
        $video_url = str_replace('youtube.com/watch?v=', 'youtube.com/embed/', $video_url);
        $video_url = explode('&', $video_url)[0]; // Remove extra params
    } elseif (strpos($video_url, 'youtu.be/') !== false) {
        $video_url = str_replace('youtu.be/', 'youtube.com/embed/', $video_url);
    } elseif (strpos($video_url, 'vimeo.com/') !== false) {
        $video_id = (int) substr(parse_url($video_url, PHP_URL_PATH), 1);
        $video_url = "https://player.vimeo.com/video/$video_id";
    }
    
    $media_url = $video_url;
}

if (empty($content) && !$media_url && !$link_url && !$feeling_value) {
    echo json_encode(['success' => false, 'message' => (($_SESSION['language'] ?? $_COOKIE['language'] ?? '') === 'en') ? 'Post content cannot be empty.' : 'Gönderi metni boş olamaz.']);
    exit();
}

// En az 1 sözcük (boş gönderi engelle)
$plain = trim(strip_tags($content));
$words = $plain === '' ? [] : preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
if (count($words) < 1) {
    $msg = (($_SESSION['language'] ?? $_COOKIE['language'] ?? '') === 'en')
        ? 'Please write at least one word.'
        : 'En az bir sözcük yazın.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit();
}

// Profanity filter - küfürlü sözcük koruması
$textToCheck = $content . ' ' . ($link_title ?? '') . ' ' . ($link_description ?? '') . ' ' . ($feeling_value ?? '');
if (ProfanityFilter::containsProfanity($textToCheck)) {
    echo json_encode([
        'success' => false,
        'message' => (($_SESSION['language'] ?? $_COOKIE['language'] ?? '') === 'en')
            ? 'Your post contains inappropriate language.'
            : 'Paylaşımınız uygun olmayan dil içeriyor.'
    ]);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check if wall_user_id column exists
    $check = $pdo->query("SHOW COLUMNS FROM posts LIKE 'wall_user_id'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN wall_user_id INT DEFAULT NULL AFTER user_id");
    }
    
    $sql = "INSERT INTO posts (user_id, wall_user_id, content, media_type, media_url, image_url, location, link_url, link_title, link_description, link_image, feeling_action, feeling_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id, 
        $wall_user_id,
        $content, 
        $media_type, 
        $media_url, 
        ($media_type == 'image' ? $media_url : null), 
        $location,
        $link_url, 
        $link_title, 
        $link_description, 
        $link_image,
        $feeling_action, 
        $feeling_value
    ]);
    
    $post_id = $pdo->lastInsertId();
    
    // Insert multiple images into post_images table
    if (count($uploaded_images) > 0) {
        $img_stmt = $pdo->prepare("INSERT INTO post_images (post_id, image_url, sort_order) VALUES (?, ?, ?)");
        foreach ($uploaded_images as $index => $img_url) {
            $img_stmt->execute([$post_id, $img_url, $index]);
        }
    }
    
    // Extract and save hashtags
    if (!empty($content)) {
        require_once '../includes/hashtag_helper.php';
        extractAndSaveHashtags($pdo, $post_id, $content);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'post_id' => $post_id, 'image_count' => count($uploaded_images)]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
