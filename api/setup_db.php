<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';

echo "<h2>Database Setup Result</h2>";

try {
    $sql = "CREATE TABLE IF NOT EXISTS `message_reactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `message_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `reaction_type` varchar(20) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_reaction` (`message_id`,`user_id`),
        KEY `message_id` (`message_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `fk_mr_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_mr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "<div style='color: green; font-weight: bold;'>✅ Table 'message_reactions' created or checks out successfully.</div>";
    
    // Verify it exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'message_reactions'");
    if ($stmt->rowCount() > 0) {
        echo "<p>Verification: Table 'message_reactions' found in database.</p>";
    } else {
        echo "<p style='color: red;'>❌ Verification FAILED: Table not found after create attempt.</p>";
    }

} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
