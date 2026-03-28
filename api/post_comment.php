<?php
require_once '../includes/db.php';
require_once '../includes/push-helper.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';
session_start();

header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

require_csrf();

if (!RateLimiter::check($pdo, 'post_comment', 10, 60)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests. Please try again later.']);
    exit();
}

$action = $_POST['action'] ?? 'add';

if ($action == 'add') {
    // Add Comment
    $user_id = $_SESSION['user_id'];
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] > 0 ? (int)$_POST['parent_id'] : NULL;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($post_id > 0 && !empty($content)) {
        try {
            // Ensure post_comments table exists (without foreign keys first, then add them if needed)
            try {
                // Check if table exists
                $table_check = $pdo->query("SHOW TABLES LIKE 'post_comments'");
                if (!$table_check->fetch()) {
                    // Create table without foreign keys first
                    $pdo->exec("CREATE TABLE `post_comments` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `post_id` int(11) NOT NULL,
                        `user_id` int(11) NOT NULL,
                        `content` text NOT NULL,
                        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `post_id` (`post_id`),
                        KEY `user_id` (`user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                }
            } catch (PDOException $e) {
                // Table might already exist, continue
            }

            // Insert comment
            $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$post_id, $user_id, $content, $parent_id])) {
                // Get comment with user info
                $comment_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT c.*, u.username, u.full_name, u.avatar, u.badge 
                                       FROM post_comments c 
                                       JOIN users u ON c.user_id = u.id 
                                       WHERE c.id = ?");
                $stmt->execute([$comment_id]);
                $comment = $stmt->fetch();
                
                // Create in-app notification and send push
                try {
                    $post_stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
                    $post_stmt->execute([$post_id]);
                    $post = $post_stmt->fetch();
                    $commenter_name = $_SESSION['full_name'] ?? $_SESSION['username'];
                    $notif_url = 'feed.php#post-' . $post_id;

                    // 1. Notify Post Owner (if not self)
                    if ($post && $post['user_id'] != $user_id) {
                        $notif_content = $commenter_name . ' gönderine yorum yaptı';
                        
                        // Insert into database
                        $ins_notif = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'comment', ?, ?, ?)");
                        $ins_notif->execute([$post['user_id'], $user_id, $post_id, $notif_content, $notif_url]);
                        
                        // Send Push
                        sendPushNotification(
                            $post['user_id'],
                            'Yeni Yorum 💬',
                            $notif_content,
                            '/' . $notif_url
                        );
                    }

                    // 2. Notify Parent Comment Owner (if reply)
                    if ($parent_id) {
                        $p_stmt = $pdo->prepare("SELECT user_id FROM post_comments WHERE id = ?");
                        $p_stmt->execute([$parent_id]);
                        $parent_owner_id = $p_stmt->fetchColumn();

                        if ($parent_owner_id && $parent_owner_id != $user_id) {
                            $reply_content = $commenter_name . ' yorumunuza yanıt verdi';
                            
                            $ins_reply = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'comment_reply', ?, ?, ?)");
                            $ins_reply->execute([$parent_owner_id, $user_id, $post_id, $reply_content, $notif_url]);

                            sendPushNotification(
                                $parent_owner_id,
                                'Yeni Yanıt ↩️',
                                $reply_content,
                                '/' . $notif_url
                            );
                        }
                    }

                    // 2. Process Mentions (@username)
                    preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
                    if (!empty($matches[1])) {
                        $mentioned_usernames = array_unique($matches[1]);
                        foreach ($mentioned_usernames as $username) {
                            // Find user data
                            $u_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                            $u_stmt->execute([$username]);
                            $target_user = $u_stmt->fetch();
                            
                            if ($target_user && $target_user['id'] != $user_id && $target_user['id'] != $post['user_id']) { 
                                // Notify mentioned user (exclude self and post owner to avoid double notif if they are the owner)
                                // Actually, if I tag the owner, they get "commented" notif. Let's send "mentioned" notif specifically if tagged.
                                
                                $notif_content_mention = $commenter_name . ' bir yorumda senden bahsetti';
                                
                                // Insert into database
                                $ins_notif_m = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'mention', ?, ?, ?)");
                                $ins_notif_m->execute([$target_user['id'], $user_id, $post_id, $notif_content_mention, $notif_url]);

                                // Send Push
                                sendPushNotification(
                                    $target_user['id'],
                                    'Etiketlendin 🔔',
                                    $notif_content_mention,
                                    '/' . $notif_url
                                );
                            }
                        }
                    }

                } catch (Exception $e) {
                    // Notification failed but comment was saved, continue
                    error_log("Notification failed: " . $e->getMessage());
                }
                
                if ($comment) {
                    echo json_encode([
                        'status' => 'success', 
                        'comment' => [
                            'id' => $comment['id'],
                            'username' => $comment['username'] ?? 'Bilinmeyen',
                            'full_name' => $comment['full_name'] ?? $comment['username'] ?? 'Kullanıcı',
                            'avatar' => $comment['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($comment['full_name'] ?? 'User'),
                            'content' => htmlspecialchars($comment['content']),
                            'created_at' => $comment['created_at'],
                            'date' => 'Şimdi'
                        ]
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Yorum oluşturuldu ancak bilgiler alınamadı']);
                }
            } else {
                $error_info = $stmt->errorInfo();
                echo json_encode(['status' => 'error', 'message' => 'Yorum eklenemedi: ' . ($error_info[2] ?? 'Bilinmeyen hata')]);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Boş yorum gönderemezsiniz veya geçersiz post ID']);
    }
} elseif ($action == 'get') {
    // Get Comments for a Post
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    
    if ($post_id > 0) {
        try {
            // Ensure table exists
            try {
                $table_check = $pdo->query("SHOW TABLES LIKE 'post_comments'");
                if (!$table_check->fetch()) {
                    $pdo->exec("CREATE TABLE `post_comments` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `post_id` int(11) NOT NULL,
                        `user_id` int(11) NOT NULL,
                        `content` text NOT NULL,
                        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `post_id` (`post_id`),
                        KEY `user_id` (`user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                }
            } catch (PDOException $e) {
                // Table might already exist
            }
            
            $stmt = $pdo->prepare("SELECT c.*, u.username, u.full_name, u.avatar, u.badge 
                                   FROM post_comments c 
                                   JOIN users u ON c.user_id = u.id 
                                   WHERE c.post_id = ? 
                                   ORDER BY c.created_at ASC");
            $stmt->execute([$post_id]);
            $comments = $stmt->fetchAll();
            
            $formatted_comments = [];
            foreach ($comments as $comment) {
                $formatted_comments[] = [
                    'id' => $comment['id'],
                    'username' => $comment['username'] ?? 'Bilinmeyen',
                    'full_name' => $comment['full_name'] ?? $comment['username'] ?? 'Kullanıcı',
                    'avatar' => $comment['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($comment['full_name'] ?? 'User'),
                    'content' => htmlspecialchars($comment['content']),
                    'created_at' => $comment['created_at'],
                    'date' => date('d.m H:i', strtotime($comment['created_at']))
                ];
            }
            
            echo json_encode(['status' => 'success', 'comments' => $formatted_comments]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz post ID']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
}
?>

