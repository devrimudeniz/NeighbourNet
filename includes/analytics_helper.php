<?php
/**
 * Business Analytics Helper
 * Functions for tracking and retrieving profile/business statistics
 */

/**
 * Record a profile view
 * 
 * @param PDO $pdo Database connection
 * @param int $profile_user_id The profile being viewed
 * @param int|null $viewer_user_id Logged in user ID (null for guests)
 * @param string|null $viewer_ip Viewer's IP address
 * @return bool Success status
 */
function recordProfileView($pdo, $profile_user_id, $viewer_user_id = null, $viewer_ip = null) {
    // Don't record self-views
    if ($viewer_user_id && $viewer_user_id == $profile_user_id) {
        return false;
    }
    
    // Rate limit: Don't record same IP viewing same profile within 1 hour
    if ($viewer_ip) {
        try {
            $check = $pdo->prepare("
                SELECT id FROM profile_views 
                WHERE profile_user_id = ? AND viewer_ip = ? 
                AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                LIMIT 1
            ");
            $check->execute([$profile_user_id, $viewer_ip]);
            if ($check->fetch()) {
                return false; // Already recorded recently
            }
        } catch (PDOException $e) {
            // Table might not exist yet
            return false;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO profile_views (profile_user_id, viewer_user_id, viewer_ip, user_agent, referrer)
            VALUES (?, ?, ?, ?, ?)
        ");
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $referrer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);
        
        return $stmt->execute([$profile_user_id, $viewer_user_id, $viewer_ip, $user_agent, $referrer]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get profile statistics for a given period
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id Profile owner
 * @param int $days Number of days to look back
 * @return array Statistics data
 */
function getProfileStats($pdo, $user_id, $days = 30) {
    $stats = [
        'total_views' => 0,
        'unique_visitors' => 0,
        'member_views' => 0,
        'guest_views' => 0,
        'daily' => [],
        'top_referrers' => [],
        'devices' => ['mobile' => 0, 'desktop' => 0, 'tablet' => 0]
    ];
    
    try {
        // Total views in period
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT viewer_ip) as unique_visitors,
                SUM(CASE WHEN viewer_user_id IS NOT NULL THEN 1 ELSE 0 END) as member_views,
                SUM(CASE WHEN viewer_user_id IS NULL THEN 1 ELSE 0 END) as guest_views
            FROM profile_views 
            WHERE profile_user_id = ? 
            AND viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$user_id, $days]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['total_views'] = (int)$result['total_views'];
        $stats['unique_visitors'] = (int)$result['unique_visitors'];
        $stats['member_views'] = (int)$result['member_views'];
        $stats['guest_views'] = (int)$result['guest_views'];
        
        // Daily breakdown
        $daily = $pdo->prepare("
            SELECT DATE(viewed_at) as view_date, COUNT(*) as views
            FROM profile_views 
            WHERE profile_user_id = ? 
            AND viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(viewed_at)
            ORDER BY view_date ASC
        ");
        $daily->execute([$user_id, $days]);
        
        while ($row = $daily->fetch(PDO::FETCH_ASSOC)) {
            $stats['daily'][$row['view_date']] = (int)$row['views'];
        }
        
        // Top referrers
        $refs = $pdo->prepare("
            SELECT referrer, COUNT(*) as count
            FROM profile_views 
            WHERE profile_user_id = ? 
            AND referrer IS NOT NULL AND referrer != ''
            AND viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY referrer
            ORDER BY count DESC
            LIMIT 5
        ");
        $refs->execute([$user_id, $days]);
        $stats['top_referrers'] = $refs->fetchAll(PDO::FETCH_ASSOC);
        
        // Device analysis
        $devices = $pdo->prepare("
            SELECT user_agent
            FROM profile_views 
            WHERE profile_user_id = ? 
            AND viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $devices->execute([$user_id, $days]);
        
        while ($row = $devices->fetch(PDO::FETCH_ASSOC)) {
            $ua = strtolower($row['user_agent'] ?? '');
            if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
                $stats['devices']['mobile']++;
            } elseif (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
                $stats['devices']['tablet']++;
            } else {
                $stats['devices']['desktop']++;
            }
        }
        
    } catch (PDOException $e) {
        // Tables might not exist
    }
    
    return $stats;
}

/**
 * Get top performing posts for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id Post owner
 * @param int $limit Number of posts to return
 * @return array Top posts with engagement metrics
 */
function getTopPosts($pdo, $user_id, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.content, p.image, p.created_at,
                   (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments
            FROM posts p
            WHERE p.user_id = ? AND p.deleted_at IS NULL
            ORDER BY (
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) + 
                (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) * 2
            ) DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get total engagement stats (likes, comments) over a period
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id Profile owner
 * @param int $days Number of days
 * @return array Engagement stats
 */
function getEngagementStats($pdo, $user_id, $days = 30) {
    $stats = [
        'total_likes' => 0,
        'total_comments' => 0,
        'total_posts' => 0,
        'avg_likes_per_post' => 0,
        'avg_comments_per_post' => 0
    ];
    
    try {
        // Posts in period
        $posts = $pdo->prepare("
            SELECT COUNT(*) as total FROM posts 
            WHERE user_id = ? AND deleted_at IS NULL 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $posts->execute([$user_id, $days]);
        $stats['total_posts'] = (int)$posts->fetchColumn();
        
        // Likes received in period
        $likes = $pdo->prepare("
            SELECT COUNT(*) as total FROM post_likes pl
            JOIN posts p ON pl.post_id = p.id
            WHERE p.user_id = ? AND pl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $likes->execute([$user_id, $days]);
        $stats['total_likes'] = (int)$likes->fetchColumn();
        
        // Comments received in period
        $comments = $pdo->prepare("
            SELECT COUNT(*) as total FROM post_comments pc
            JOIN posts p ON pc.post_id = p.id
            WHERE p.user_id = ? AND pc.user_id != ? 
            AND pc.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $comments->execute([$user_id, $user_id, $days]);
        $stats['total_comments'] = (int)$comments->fetchColumn();
        
        // Calculate averages
        if ($stats['total_posts'] > 0) {
            $stats['avg_likes_per_post'] = round($stats['total_likes'] / $stats['total_posts'], 1);
            $stats['avg_comments_per_post'] = round($stats['total_comments'] / $stats['total_posts'], 1);
        }
        
    } catch (PDOException $e) {
        // Handle errors gracefully
    }
    
    return $stats;
}

/**
 * Get viewer's IP address
 * 
 * @return string IP address
 */
function getViewerIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}
?>
