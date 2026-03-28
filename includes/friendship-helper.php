<?php
// Friendship System Helper Functions

/**
 * Get friendship status between two users
 * @return string 'friends', 'pending_sent', 'pending_received', 'none'
 */
function getFriendshipStatus($user_id, $other_user_id) {
    global $pdo;
    
    if ($user_id == $other_user_id) return 'self';
    
    // Check if they are friends (accepted in either direction)
    $stmt = $pdo->prepare("
        SELECT status, requester_id, receiver_id 
        FROM friendships 
        WHERE (requester_id = ? AND receiver_id = ?)
           OR (requester_id = ? AND receiver_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $friendship = $stmt->fetch();
    
    if (!$friendship) return 'none';
    
    if ($friendship['status'] == 'accepted') return 'friends';
    
    // Check direction of pending request
    if ($friendship['requester_id'] == $user_id) {
        return 'pending_sent';
    } else {
        return 'pending_received';
    }
}

/**
 * Check if two users are friends
 */
function areFriends($user_id, $other_user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 1 FROM friendships 
        WHERE ((requester_id = ? AND receiver_id = ?) 
           OR (requester_id = ? AND receiver_id = ?))
           AND status = 'accepted'
        LIMIT 1
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    return $stmt->fetch() !== false;
}

/**
 * Get count of mutual friends
 */
function getMutualFriendsCount($user_id, $other_user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT mutual_friend) as count FROM (
            -- User 1's friends
            SELECT 
                CASE 
                    WHEN requester_id = ? THEN receiver_id 
                    ELSE requester_id 
                END as mutual_friend
            FROM friendships 
            WHERE (requester_id = ? OR receiver_id = ?) 
              AND status = 'accepted'
        ) as user1_friends
        WHERE mutual_friend IN (
            -- User 2's friends
            SELECT 
                CASE 
                    WHEN requester_id = ? THEN receiver_id 
                    ELSE requester_id 
                END
            FROM friendships 
            WHERE (requester_id = ? OR receiver_id = ?) 
              AND status = 'accepted'
        )
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $other_user_id, $other_user_id, $other_user_id]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

/**
 * Get list of mutual friends with profile data
 */
function getMutualFriends($user_id, $other_user_id, $limit = 3) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username, u.full_name, u.avatar 
        FROM users u
        WHERE u.id IN (
            SELECT 
                CASE 
                    WHEN requester_id = ? THEN receiver_id 
                    ELSE requester_id 
                END as mutual_friend
            FROM friendships 
            WHERE (requester_id = ? OR receiver_id = ?) 
              AND status = 'accepted'
        )
        AND u.id IN (
            SELECT 
                CASE 
                    WHEN requester_id = ? THEN receiver_id 
                    ELSE requester_id 
                END
            FROM friendships 
            WHERE (requester_id = ? OR receiver_id = ?) 
              AND status = 'accepted'
        )
        LIMIT ?
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $other_user_id, $other_user_id, $other_user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get user's friends list
 */
function getFriendsList($user_id, $limit = null) {
    global $pdo;
    
    $sql = "
        SELECT u.id, u.username, u.full_name, u.avatar, u.badge
        FROM users u
        JOIN friendships f ON (
            (f.requester_id = ? AND f.receiver_id = u.id)
            OR (f.receiver_id = ? AND f.requester_id = u.id)
        )
        WHERE f.status = 'accepted'
        ORDER BY u.full_name ASC
    ";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll();
}

/**
 * Get pending friend requests (incoming)
 */
function getPendingRequests($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT f.id as friendship_id, f.created_at, 
               u.id, u.username, u.full_name, u.avatar, u.badge
        FROM friendships f
        JOIN users u ON f.requester_id = u.id
        WHERE f.receiver_id = ? AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
?>
