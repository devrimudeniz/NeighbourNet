<?php
/**
 * Kalkan Pass Card Helper Functions
 */

/**
 * Generate a unique card number
 */
function generateCardNumber($pdo) {
    do {
        $card_number = sprintf("%04d %04d %04d %04d", 
            rand(1000, 9999), rand(1000, 9999), 
            rand(1000, 9999), rand(1000, 9999)
        );
        $check = $pdo->prepare("SELECT 1 FROM user_cards WHERE card_number = ?");
        $check->execute([$card_number]);
    } while ($check->fetch());
    
    return $card_number;
}

/**
 * Get or create card data for a user
 */
function getCardData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM user_cards WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$card) {
        // Create new card
        $card_number = generateCardNumber($pdo);
        
        // Get user's join date
        $user_stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();
        
        $member_since = date('Y-m-d', strtotime($user['created_at']));
        
        $ins = $pdo->prepare("INSERT INTO user_cards (user_id, card_number, member_since) VALUES (?, ?, ?)");
        $ins->execute([$user_id, $card_number, $member_since]);
        
        // Fetch the newly created card
        $stmt->execute([$user_id]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $card;
}

/**
 * Calculate membership tier based on user activity
 */
function getMembershipTier($pdo, $user_id) {
    // Get user stats
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as post_count,
            (SELECT COUNT(*) FROM post_comments WHERE user_id = ?) as comment_count,
            (SELECT COUNT(*) FROM friendships WHERE (requester_id = ? OR receiver_id = ?) AND status = 'accepted') as friend_count,
            (SELECT badge FROM users WHERE id = ?) as badge
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // VIP tier for special badges
    if (in_array($stats['badge'], ['founder', 'moderator', 'verified_business', 'captain'])) {
        return 'vip';
    }
    
    // Premium tier for active users
    $total_activity = $stats['post_count'] + $stats['comment_count'] + ($stats['friend_count'] * 2);
    if ($total_activity >= 50) {
        return 'premium';
    }
    
    return 'standard';
}

/**
 * Generate QR code data URL (simple implementation)
 */
function generateQRCodeDataURL($user_id, $card_number) {
    // For now, return a placeholder
    // In production, use a QR code library like phpqrcode or an API
    $data = "kalkan-pass://$user_id/$card_number";
    
    // Using a free QR code API
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($data);
    
    return $qr_url;
}

/**
 * Get tier display info
 */
function getTierInfo($tier) {
    $tiers = [
        'standard' => [
            'name' => 'Standard',
            'name_tr' => 'Standart',
            'color' => 'from-slate-400 to-slate-600',
            'icon' => '⭐'
        ],
        'premium' => [
            'name' => 'Premium',
            'name_tr' => 'Premium',
            'color' => 'from-purple-400 to-purple-600',
            'icon' => '💎'
        ],
        'vip' => [
            'name' => 'VIP',
            'name_tr' => 'VIP',
            'color' => 'from-amber-400 to-amber-600',
            'icon' => '👑'
        ]
    ];
    
    return $tiers[$tier] ?? $tiers['standard'];
}
?>
