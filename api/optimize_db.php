<?php
// Database Optimization Script
header('Content-Type: application/json');
require_once '../includes/db.php';

$output = [];

function checkAndExec($pdo, $sql, $desc, &$output) {
    try {
        $pdo->exec($sql);
        $output[] = "SUCCESS: $desc";
    } catch (PDOException $e) {
        // Ignorable errors (Duplicate column/index)
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
             $output[] = "SKIPPED (Exists): $desc";
        } else {
             $output[] = "ERROR: $desc -> " . $e->getMessage();
        }
    }
}

// 1. ADD COLUMNS
// Posts: Soft Delete & Geo & Rich Link
checkAndExec($pdo, "ALTER TABLE posts ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL", "Add deleted_at to posts", $output);
checkAndExec($pdo, "ALTER TABLE posts ADD COLUMN lat DECIMAL(10, 8) NULL", "Add lat to posts", $output);
checkAndExec($pdo, "ALTER TABLE posts ADD COLUMN lng DECIMAL(11, 8) NULL", "Add lng to posts", $output);

// Events: Geo
checkAndExec($pdo, "ALTER TABLE events ADD COLUMN lat DECIMAL(10, 8) NULL", "Add lat to events", $output);
checkAndExec($pdo, "ALTER TABLE events ADD COLUMN lng DECIMAL(11, 8) NULL", "Add lng to events", $output);

// Users: Activity
// checkAndExec($pdo, "ALTER TABLE users ADD COLUMN last_activity DATETIME NULL", "Add last_activity to users", $output); // Using last_seen instead

// 2. ADD INDEXES (Crucial for Speed)
checkAndExec($pdo, "CREATE INDEX idx_users_username ON users(username)", "Index users(username)", $output);
checkAndExec($pdo, "CREATE INDEX idx_users_last_seen ON users(last_seen)", "Index users(last_seen)", $output);

checkAndExec($pdo, "CREATE INDEX idx_posts_user_id ON posts(user_id)", "Index posts(user_id)", $output);
checkAndExec($pdo, "CREATE INDEX idx_posts_created_at ON posts(created_at)", "Index posts(created_at)", $output);
checkAndExec($pdo, "CREATE INDEX idx_posts_deleted_at ON posts(deleted_at)", "Index posts(deleted_at)", $output);

checkAndExec($pdo, "CREATE INDEX idx_friendships_status ON friendships(requester_id, receiver_id, status)", "Index friendships composite", $output);

checkAndExec($pdo, "CREATE INDEX idx_notifications_user ON notifications(user_id, is_read)", "Index notifications", $output);

checkAndExec($pdo, "CREATE INDEX idx_hashtags_tag ON hashtags(tag_name)", "Index hashtags(tag_name)", $output);


// 3. CLEANUP (Optional)
// Clean up orphaned hashtags?
// Not for now.

echo json_encode($output, JSON_PRETTY_PRINT);
?>
