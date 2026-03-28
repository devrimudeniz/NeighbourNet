<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/optimize_upload.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$question = trim($_POST['question'] ?? '');
$options = $_POST['options'] ?? [];
$end_date = !empty($_POST['end_date']) ? trim($_POST['end_date']) : null;
$allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;
$content = trim($_POST['content'] ?? ''); // Optional text content with poll

// Validate input
if (empty($question)) {
    echo json_encode(['success' => false, 'message' => 'Poll question is required']);
    exit();
}

if (!is_array($options) || count($options) < 2) {
    echo json_encode(['success' => false, 'message' => 'At least 2 options are required']);
    exit();
}

// Filter empty options
$options = array_filter($options, function($opt) {
    return !empty(trim($opt));
});

if (count($options) < 2) {
    echo json_encode(['success' => false, 'message' => 'At least 2 valid options are required']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Create the post with media_type = 'poll'
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, media_type) VALUES (?, ?, 'poll')");
    $stmt->execute([$user_id, $content]);
    $post_id = $pdo->lastInsertId();

    // Create the poll
    $stmt = $pdo->prepare("INSERT INTO polls (post_id, question, end_date, allow_multiple) VALUES (?, ?, ?, ?)");
    $stmt->execute([$post_id, $question, $end_date, $allow_multiple]);
    $poll_id = $pdo->lastInsertId();

    // Create poll options
    $stmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
    foreach ($options as $option_text) {
        $stmt->execute([$poll_id, trim($option_text)]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'post_id' => $post_id,
        'poll_id' => $poll_id,
        'message' => 'Poll created successfully'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
