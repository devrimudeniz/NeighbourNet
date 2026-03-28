<?php
/**
 * Hashtag Helper Functions
 */

/**
 * Parse hashtags from content and save to database
 * @param PDO $pdo Database connection
 * @param int $post_id Post ID
 * @param string $content Post content
 * @return array List of extracted hashtags
 */
function extractAndSaveHashtags($pdo, $post_id, $content) {
    // Find all hashtags (supports Turkish characters)
    preg_match_all('/#([a-zA-Z0-9_şŞıİğĞüÜöÖçÇ]+)/u', $content, $matches);
    
    if (empty($matches[1])) {
        return [];
    }
    
    $hashtags = array_unique(array_map('mb_strtolower', $matches[1]));
    $saved = [];
    
    foreach ($hashtags as $tag) {
        if (mb_strlen($tag) < 2 || mb_strlen($tag) > 50) continue; // Skip too short or too long
        
        try {
            // Insert or update hashtag
            $stmt = $pdo->prepare("INSERT INTO hashtags (tag_name, usage_count) VALUES (?, 1) 
                                   ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
            $stmt->execute([$tag]);
            
            // Get hashtag ID
            $id_stmt = $pdo->prepare("SELECT id FROM hashtags WHERE tag_name = ?");
            $id_stmt->execute([$tag]);
            $hashtag_id = $id_stmt->fetchColumn();
            
            if ($hashtag_id) {
                // Link to post
                $link_stmt = $pdo->prepare("INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)");
                $link_stmt->execute([$post_id, $hashtag_id]);
                $saved[] = $tag;
            }
        } catch (PDOException $e) {
            // Log error but continue
            error_log("Hashtag save error: " . $e->getMessage());
        }
    }
    
    return $saved;
}

/**
 * Convert URLs and hashtags in content to clickable links
 * @param string $content Post content
 * @return string Content with linked URLs and hashtags
 */
function linkifyHashtags($content) {
    $placeholders = [];
    $counter = 0;
    
    // 1. Extract URLs first (http/https) and replace with placeholders
    $content = preg_replace_callback(
        '/\b(https?:\/\/[^\s<>"\'\)]+)/u',
        function($match) use (&$placeholders, &$counter) {
            $url = rtrim($match[1], '.,;:!?)');
            $placeholder = "%%URL_{$counter}%%";
            $placeholders[$placeholder] = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline break-all" onclick="event.stopPropagation()">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a>';
            $counter++;
            return $placeholder;
        },
        $content
    );
    
    // 2. Extract hashtags and replace with placeholders
    $content = preg_replace_callback(
        '/#([a-zA-Z0-9_şŞıİğĞüÜöÖçÇ]+)/u',
        function($match) use (&$placeholders, &$counter) {
            $tag = $match[1];
            $tag_lower = mb_strtolower($tag);
            $placeholder = "%%HASHTAG_{$counter}%%";
            $placeholders[$placeholder] = '<a href="hashtag?tag=' . urlencode($tag_lower) . '" class="text-pink-500 hover:text-pink-600 font-bold hover:underline" onclick="event.stopPropagation()">#' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</a>';
            $counter++;
            return $placeholder;
        },
        $content
    );
    
    // 3. Escape the rest
    $escaped = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    // 4. Restore links
    foreach ($placeholders as $placeholder => $link) {
        $escaped = str_replace($placeholder, $link, $escaped);
    }
    
    return $escaped;
}

/**
 * Get trending hashtags
 * @param PDO $pdo Database connection
 * @param int $limit Number of hashtags to return
 * @return array List of trending hashtags
 */
function getTrendingHashtags($pdo, $limit = 10) {
    $stmt = $pdo->prepare("SELECT tag_name, usage_count FROM hashtags ORDER BY usage_count DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get posts by hashtag
 * @param PDO $pdo Database connection
 * @param string $tag Hashtag name (without #)
 * @param int $limit Number of posts
 * @param int $offset Pagination offset
 * @return array Posts with that hashtag
 */
function getPostsByHashtag($pdo, $tag, $limit = 20, $offset = 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.full_name, u.avatar, u.badge,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN post_hashtags ph ON p.id = ph.post_id
        JOIN hashtags h ON ph.hashtag_id = h.id
        JOIN users u ON p.user_id = u.id
        WHERE h.tag_name = ? AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([mb_strtolower($tag), $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
