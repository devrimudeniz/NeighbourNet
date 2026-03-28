<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_csrf();

if (!RateLimiter::check($pdo, 'vote_poll', 20, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$poll_id = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
$option_id = isset($_POST['option_id']) ? (int)$_POST['option_id'] : 0;

if (!$poll_id || !$option_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid poll or option']);
    exit();
}

try {
    // Check if poll exists and is still active
    $stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
    $stmt->execute([$poll_id]);
    $poll = $stmt->fetch();

    if (!$poll) {
        echo json_encode(['success' => false, 'message' => 'Poll not found']);
        exit();
    }

    // Check if poll has ended
    if ($poll['end_date'] && strtotime($poll['end_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'This poll has ended']);
        exit();
    }

    // Check if option belongs to this poll
    $stmt = $pdo->prepare("SELECT * FROM poll_options WHERE id = ? AND poll_id = ?");
    $stmt->execute([$option_id, $poll_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid option']);
        exit();
    }

    // Check if user already voted (if not allow_multiple)
    if (!$poll['allow_multiple']) {
        $stmt = $pdo->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $stmt->execute([$poll_id, $user_id]);
        $existing_vote = $stmt->fetch();

        if ($existing_vote) {
            // Remove existing vote and update count
            $stmt = $pdo->prepare("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
            $stmt->execute([$poll_id, $user_id]);
            $old_option = $stmt->fetch();

            if ($old_option) {
                // Decrease old option count
                $pdo->prepare("UPDATE poll_options SET vote_count = vote_count - 1 WHERE id = ?")->execute([$old_option['option_id']]);
                // Delete old vote
                $pdo->prepare("DELETE FROM poll_votes WHERE poll_id = ? AND user_id = ?")->execute([$poll_id, $user_id]);
            }
        }
    } else {
        // Check if user already voted for this specific option
        $stmt = $pdo->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND user_id = ? AND option_id = ?");
        $stmt->execute([$poll_id, $user_id, $option_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You already voted for this option']);
            exit();
        }
    }

    // Record the vote
    $stmt = $pdo->prepare("INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$poll_id, $option_id, $user_id]);

    // Update vote count
    $pdo->prepare("UPDATE poll_options SET vote_count = vote_count + 1 WHERE id = ?")->execute([$option_id]);

    // Get updated poll data
    $stmt = $pdo->prepare("SELECT id, option_text, vote_count FROM poll_options WHERE poll_id = ?");
    $stmt->execute([$poll_id]);
    $updated_options = $stmt->fetchAll();

    // Get total votes
    $total = array_sum(array_column($updated_options, 'vote_count'));

    echo json_encode([
        'success' => true,
        'message' => 'Vote recorded',
        'options' => $updated_options,
        'total_votes' => $total,
        'user_vote' => $option_id
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
