<?php
/**
 * Reply to Review API
 * Allows business owners to reply to reviews
 */
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Require login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$review_id = (int)($_POST['review_id'] ?? 0);
$reply = trim($_POST['reply'] ?? '');
$user_id = $_SESSION['user_id'];

if ($review_id < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid review ID']);
    exit();
}

if (empty($reply)) {
    echo json_encode(['success' => false, 'error' => 'Reply cannot be empty']);
    exit();
}

try {
    // Get the review and check if user is the business owner
    $stmt = $pdo->prepare("
        SELECT r.*, b.owner_id as business_owner_id 
        FROM business_reviews r
        JOIN business_listings b ON r.business_id = b.id
        WHERE r.id = ?
    ");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch();

    if (!$review) {
        echo json_encode(['success' => false, 'error' => 'Review not found']);
        exit();
    }

    // Check if user is the business owner
    if ($review['business_owner_id'] != $user_id) {
        // Also check if user is admin/moderator
        $admin_check = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
        $admin_check->execute([$user_id]);
        $user_data = $admin_check->fetch();
        
        if (!in_array($user_data['badge'] ?? '', ['founder', 'moderator'])) {
            echo json_encode(['success' => false, 'error' => 'Not authorized to reply']);
            exit();
        }
    }

    // Check if already replied
    if (!empty($review['business_reply'])) {
        echo json_encode(['success' => false, 'error' => 'Already replied to this review']);
        exit();
    }

    // Add reply
    $update = $pdo->prepare("UPDATE business_reviews SET business_reply = ?, reply_at = NOW() WHERE id = ?");
    $update->execute([$reply, $review_id]);

    echo json_encode([
        'success' => true,
        'reply' => $reply,
        'reply_at' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
