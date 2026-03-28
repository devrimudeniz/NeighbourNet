<?php
require_once '../includes/db.php';
require_once '../includes/trust_score_helper.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    require_csrf();
    
    if (!RateLimiter::check($pdo, 'submit_review', 3, 60)) {
        http_response_code(429);
        die("Too many requests. Please try again later.");
    }
    
    $reviewer_id = $_SESSION['user_id'];
    $seller_id = (int)$_POST['seller_id'];
    $listing_id = (int)$_POST['listing_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) die("Invalid rating");

    try {
        // Insert Review
        $stmt = $pdo->prepare("INSERT INTO marketplace_reviews (listing_id, reviewer_id, seller_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$listing_id, $reviewer_id, $seller_id, $rating, $comment]);

        // Recalculate Trust Score for Seller
        calculateTrustScore($seller_id);

        // Redirect back
        header("Location: ../listing_detail.php?id=$listing_id&status=review_submitted");
    } catch (PDOException $e) {
        die("Error: An error occurred");
    }
}
?>
