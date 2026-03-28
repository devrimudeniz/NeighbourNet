<?php
require_once __DIR__ . '/db.php';

function getCurrentContestWeek() {
    // Week starts on Monday
    return date('Y-m-d', strtotime('monday this week'));
}

function getLastContestWeek() {
    return date('Y-m-d', strtotime('monday last week'));
}

// Check and Pick Winner - throttled to run max once per hour (not every page load)
function checkAndPickWinner($pdo) {
    static $cache_file;
    if ($cache_file === null) {
        $cache_file = __DIR__ . '/../cache/contest_winner_check.lock';
    }
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 3600) {
        return; // Run at most once per hour
    }
    @touch($cache_file);

    $last_week = getLastContestWeek();
    
    // Check if we already have a winner for last week
    $stmt = $pdo->prepare("SELECT id FROM contest_winners WHERE week_of = ?");
    $stmt->execute([$last_week]);
    if ($stmt->fetch()) {
        return; // Already picked
    }
    
    // Find top voted submission from last week
    $start_date = $last_week . " 00:00:00";
    $end_date = date('Y-m-d', strtotime('sunday last week')) . " 23:59:59";
    
    // Select submission with most votes
    $sql = "
        SELECT s.id, s.user_id, COUNT(v.id) as vote_count
        FROM contest_submissions s
        LEFT JOIN contest_votes v ON s.id = v.submission_id
        WHERE s.created_at BETWEEN ? AND ?
        AND s.deleted_at IS NULL
        GROUP BY s.id
        ORDER BY vote_count DESC, s.created_at ASC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $winner = $stmt->fetch();
    
    if ($winner && $winner['vote_count'] > 0) {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert into contest_winners
            $ins = $pdo->prepare("INSERT INTO contest_winners (submission_id, user_id, week_of) VALUES (?, ?, ?)");
            $ins->execute([$winner['id'], $winner['user_id'], $last_week]);
            
            // 2. Award Badge
            $check_badge = $pdo->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_type = 'photographer'");
            $check_badge->execute([$winner['user_id']]);
            if (!$check_badge->fetch()) {
                $add_badge = $pdo->prepare("INSERT INTO user_badges (user_id, badge_type, assigned_at) VALUES (?, 'photographer', NOW())");
                $add_badge->execute([$winner['user_id']]);
                
                // Notification (Validation safe)
                try {
                     $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, message, created_at) VALUES (?, 'system', ?, ?, NOW())");
                     $msg = "You won the Kalkan Snaps contest! You've been awarded the Photographer badge.";
                     $notif->execute([$winner['user_id'], $winner['id'], $msg]);
                } catch (Exception $e) {}
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}
?>
