<?php
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_csrf();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Helper to check expert status
// Helper to check expert status
function isExpert($pdo, $uid) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_type IN ('expert', 'local_guide')");
    $stmt->execute([$uid]);
    // Also check explicit users column if added
    // Or check role
    return $stmt->fetchColumn() > 0 || in_array($_SESSION['badge'] ?? '', ['founder', 'moderator', 'expert', 'local_guide']);
}

// Calculate reading time
function calculateReadingTime($content) {
    $word_count = str_word_count(strip_tags($content));
    $minutes = ceil($word_count / 200); // 200 words per minute
    return max(1, $minutes);
}

if ($action === 'create') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') die(json_encode(['error' => 'Invalid Method']));
    if (!isExpert($pdo, $user_id)) die(json_encode(['error' => 'Access Denied: Experts Only']));

    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $cover = trim($_POST['cover_image']);
    $tags_raw = trim($_POST['tags'] ?? '');
    
    // Process Tags
    $tags_array = array_filter(array_map('trim', explode(',', $tags_raw)));
    $tags_json = json_encode(array_slice($tags_array, 0, 5)); // Limit to 5 tags

    // Reading Time
    $reading_time = calculateReadingTime($content);
    
    // Slug generation
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $slug .= '-' . time(); 

    if (empty($title) || empty($content)) {
        echo json_encode(['error' => 'Title and Content required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO guidebooks (user_id, title, slug, content, category, cover_image, tags, reading_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->execute([$user_id, $title, $slug, $content, $category, $cover, $tags_json, $reading_time]);
        
        // Update Expert Stats
        $pdo->prepare("INSERT INTO expert_stats (user_id, total_guidebooks) VALUES (?, 1) ON DUPLICATE KEY UPDATE total_guidebooks = total_guidebooks + 1")->execute([$user_id]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
} 
elseif ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') die(json_encode(['error' => 'Invalid Method']));
    
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $cover = trim($_POST['cover_image']);
    $tags_raw = trim($_POST['tags'] ?? '');

    if (empty($title) || empty($content)) {
        echo json_encode(['error' => 'Title and Content required']);
        exit;
    }

    // Fetch Guide for permission check
    $stmt = $pdo->prepare("SELECT user_id FROM guidebooks WHERE id = ?");
    $stmt->execute([$id]);
    $guide = $stmt->fetch();

    if (!$guide) die(json_encode(['error' => 'Guide not found']));

    // Permission Check
    $is_admin = in_array($_SESSION['badge'] ?? '', ['founder', 'moderator']);
    if ($guide['user_id'] != $user_id && !$is_admin) {
        die(json_encode(['error' => 'Access Denied']));
    }

    // Process Tags
    $tags_array = array_filter(array_map('trim', explode(',', $tags_raw)));
    $tags_json = json_encode(array_slice($tags_array, 0, 5));

    // Reading Time
    $reading_time = calculateReadingTime($content);

    try {
        $stmt = $pdo->prepare("UPDATE guidebooks SET title = ?, content = ?, category = ?, cover_image = ?, tags = ?, reading_time = ? WHERE id = ?");
        $stmt->execute([$title, $content, $category, $cover, $tags_json, $reading_time, $id]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
elseif ($action === 'vote') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') die(json_encode(['error' => 'Invalid Method']));
    
    $guide_id = (int)$_POST['guide_id'];
    $type = $_POST['vote_type'] === 'helpful' ? 'helpful' : 'not_helpful';

    try {
        // Toggle/Insert Vote
        // Simple logic: Insert ignore, if duplicate maybe verify?
        // Let's keep it simple: Insert
        $stmt = $pdo->prepare("INSERT INTO guidebook_votes (guidebook_id, user_id, vote_type) VALUES (?, ?, ?)");
        $stmt->execute([$guide_id, $user_id, $type]);
        
        if ($type === 'helpful') {
            $pdo->prepare("UPDATE guidebooks SET helpful_count = helpful_count + 1 WHERE id = ?")->execute([$guide_id]);
             // Update Expert Stats (helpful votes)
            $author_stmt = $pdo->prepare("SELECT user_id FROM guidebooks WHERE id = ?");
            $author_stmt->execute([$guide_id]);
            $author_id = $author_stmt->fetchColumn();
            if ($author_id) {
                $pdo->prepare("INSERT INTO expert_stats (user_id, total_helpful_votes) VALUES (?, 1) ON DUPLICATE KEY UPDATE total_helpful_votes = total_helpful_votes + 1")->execute([$author_id]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Duplicate vote
        echo json_encode(['error' => 'Already voted']);
    }
}
else {
    echo json_encode(['error' => 'Unknown Action']);
}
?>
