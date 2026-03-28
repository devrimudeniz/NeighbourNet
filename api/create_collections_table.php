<?php
require_once '../includes/db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `collections` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `name` varchar(255) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table collections created or already exists.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
