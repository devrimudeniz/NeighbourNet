<?php
/**
 * Add performance indexes - run once
 * 
 * cPanel Yöntem 1 - Terminal: php migrations/run_performance_indexes.php
 * cPanel Yöntem 2 - Tarayıcı: https://siteniz.com/migrations/run_performance_indexes.php?run=1
 *   (Çalıştırdıktan sonra bu dosyayı silin veya run=1 parametresini kaldırın)
 */
if (php_sapi_name() !== 'cli' && (!isset($_GET['run']) || $_GET['run'] !== '1')) {
    die('CLI: php migrations/run_performance_indexes.php | Web: ?run=1 ekleyin');
}
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}
require_once __DIR__ . '/../includes/db.php';

$indexes = [
    ['posts', 'idx_posts_deleted_created', 'CREATE INDEX idx_posts_deleted_created ON posts(deleted_at, created_at)'],
    ['posts', 'idx_posts_user_created', 'CREATE INDEX idx_posts_user_created ON posts(user_id, created_at)'],
    ['posts', 'idx_posts_created_at', 'CREATE INDEX idx_posts_created_at ON posts(created_at)'],
    ['post_likes', 'idx_post_likes_post_user', 'CREATE INDEX idx_post_likes_post_user ON post_likes(post_id, user_id)'],
    ['post_likes', 'idx_post_likes_post', 'CREATE INDEX idx_post_likes_post ON post_likes(post_id)'],
    ['post_comments', 'idx_post_comments_post', 'CREATE INDEX idx_post_comments_post ON post_comments(post_id)'],
    ['notifications', 'idx_notifications_user_read', 'CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read)'],
    ['messages', 'idx_messages_receiver_read', 'CREATE INDEX idx_messages_receiver_read ON messages(receiver_id, is_read)'],
    ['events', 'idx_events_date_status', 'CREATE INDEX idx_events_date_status ON events(event_date, status)'],
    ['events', 'idx_events_user', 'CREATE INDEX idx_events_user ON events(user_id, event_date)'],
];

foreach ($indexes as $def) {
    list($table, $name, $sql) = $def;
    try {
        $pdo->exec($sql);
        echo "OK: $name on $table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "SKIP: $name (already exists)\n";
        } else {
            echo "FAIL: $name - " . $e->getMessage() . "\n";
        }
    }
}
echo "Done.\n";
