<?php
class RateLimiter {
    /**
     * Check if the current IP has exceeded the rate limit for a specific action.
     * 
     * @param PDO $pdo Database connection
     * @param string $action Action identifier (e.g., 'friend_req', 'chat_msg')
     * @param int $max_hits Maximum allowed hits in the time window
     * @param int $window_sec Time window in seconds
     * @return bool True if allowed, False if limit exceeded
     */
    public static function check($pdo, $action, $max_hits, $window_sec) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $now = time();

        // 1. Clean up old entries (Garbage Collection - occasional)
        if (rand(1, 100) <= 5) {
            $pdo->prepare("DELETE FROM rate_limits WHERE window_start < ?")->execute([$now - 3600]);
        }

        // 2. Get current status
        $stmt = $pdo->prepare("SELECT * FROM rate_limits WHERE ip_address = ? AND action_type = ?");
        $stmt->execute([$ip, $action]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            // Check if window has expired
            if (($now - $record['window_start']) > $window_sec) {
                // Reset window
                $upd = $pdo->prepare("UPDATE rate_limits SET hits = 1, window_start = ? WHERE id = ?");
                $upd->execute([$now, $record['id']]);
                return true;
            } else {
                // Check limits
                if ($record['hits'] >= $max_hits) {
                    return false; // Limit exceeded
                } else {
                    // Increment hits
                    $upd = $pdo->prepare("UPDATE rate_limits SET hits = hits + 1 WHERE id = ?");
                    $upd->execute([$record['id']]);
                    return true;
                }
            }
        } else {
            // New record
            $ins = $pdo->prepare("INSERT INTO rate_limits (ip_address, action_type, window_start, hits) VALUES (?, ?, ?, 1)");
            $ins->execute([$ip, $action, $now]);
            return true;
        }
    }
}
?>
