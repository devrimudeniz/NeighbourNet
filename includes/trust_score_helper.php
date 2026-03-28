<?php
require_once 'db.php';

function calculateTrustScore($user_id) {
    global $pdo;
    
    $score = 0;

    // 1. Verification (Blue/Gold Tick)
    $stmt = $pdo->prepare("SELECT badge, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Badge Points
        if (in_array($user['badge'], ['verified', 'business', 'official', 'founder', 'moderator'])) {
            $score += 20;
        }

        // Account Age Points (+5 per year)
        $years_active = (time() - strtotime($user['created_at'])) / (365 * 24 * 60 * 60);
        $score += floor($years_active) * 5;
    }

    // 2. Friendships (Social Proof)
    // +1 point per accepted friend, max 20 points
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM friendships WHERE (requester_id = ? OR receiver_id = ?) AND status = 'accepted'");
    $stmt->execute([$user_id, $user_id]);
    $friend_count = $stmt->fetchColumn();
    $score += min($friend_count, 20);

    // 3. Reviews (Marketplace & Business)
    // Marketplace Reviews
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM marketplace_reviews WHERE seller_id = ?");
    $stmt->execute([$user_id]);
    $m_stats = $stmt->fetch();
    
    if ($m_stats['count'] > 0) {
        // Formula: (Avg Rating * 2) * (Log of Count + 1) limited to 30 points
        $points = ($m_stats['avg_rating'] * 2) * log($m_stats['count'] + 1);
        $score += min($points, 30); 
    }

    // Business Reviews (if user owns a business)
    // Find businesses owned by user
    $stmt = $pdo->prepare("SELECT id FROM business_listings WHERE owner_id = ?");
    $stmt->execute([$user_id]);
    $business_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($business_ids)) {
        $in_query = implode(',', array_fill(0, count($business_ids), '?'));
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM business_reviews WHERE business_id IN ($in_query)");
        $stmt->execute($business_ids);
        $b_stats = $stmt->fetch();
        
        if ($b_stats['count'] > 0) {
             // Formula: (Avg Rating * 2) * (Log of Count + 1) limited to 30 points
            $points = ($b_stats['avg_rating'] * 2) * log($b_stats['count'] + 1);
            $score += min($points, 30);
        }
    }

    // Cap at 100
    $score = min(round($score), 100);

    // Update User
    $pdo->prepare("UPDATE users SET trust_score = ? WHERE id = ?")->execute([$score, $user_id]);
    
    return $score;
}
?>
