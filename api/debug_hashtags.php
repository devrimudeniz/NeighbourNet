<?php
// API Debug Hashtags
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/hashtag_helper.php';

$debug = [];

// 1. Check Tables
$tables = ['hashtags', 'post_hashtags'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        $debug['tables'][$table] = "Exists, Rows: $count";
    } catch (PDOException $e) {
        $debug['tables'][$table] = "Error: " . $e->getMessage();
        // Create table if missing
        if ($table == 'post_hashtags') {
             $pdo->exec("CREATE TABLE IF NOT EXISTS post_hashtags (
                post_id INT NOT NULL,
                hashtag_id INT NOT NULL,
                PRIMARY KEY (post_id, hashtag_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            $debug['tables'][$table] .= " -> RECREATED";
        }
    }
}

// 2. Check Specific Tag 'test'
$tag = 'test';
$stmt = $pdo->prepare("SELECT * FROM hashtags WHERE tag_name = ?");
$stmt->execute([$tag]);
$tagData = $stmt->fetch(PDO::FETCH_ASSOC);
$debug['test_tag'] = $tagData;

if ($tagData) {
    // Check Links
    $stmt = $pdo->prepare("SELECT count(*) FROM post_hashtags WHERE hashtag_id = ?");
    $stmt->execute([$tagData['id']]);
    $links = $stmt->fetchColumn();
    $debug['test_tag_links'] = $links;
    
    // Check Posts
    if ($links > 0) {
        $stmt = $pdo->prepare("SELECT post_id FROM post_hashtags WHERE hashtag_id = ? LIMIT 5");
        $stmt->execute([$tagData['id']]);
        $postIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $debug['linked_posts'] = $postIds;
    }
}

// 3. Retroactive Fix: Scan last 50 posts and index hashtags
$stmt = $pdo->query("SELECT id, content FROM posts WHERE content LIKE '%#%' ORDER BY id DESC LIMIT 50");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$indexed_count = 0;
$found_tags = [];

foreach ($posts as $post) {
    if (!empty($post['content'])) {
        $tags = extractAndSaveHashtags($pdo, $post['id'], $post['content']);
        if (!empty($tags)) {
            $indexed_count++;
            $found_tags = array_merge($found_tags, $tags);
        }
    }
}
$debug['retroactive_fix'] = "Scanned " . count($posts) . " posts, indexed hashtags for $indexed_count posts.";
$debug['found_tags_sample'] = array_unique($found_tags);

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
