<?php
/**
 * HTMX Like Button Endpoint
 * Returns HTML partial for the like button after a reaction
 * 
 * Usage: hx-post="api/htmx/like_button.php"
 *        hx-vals='{"post_id": 123, "reaction_type": "like"}'
 *        hx-swap="outerHTML"
 */

require_once '../../includes/db.php';
require_once '../../includes/push-helper.php';
require_once '../../includes/lang.php';

session_start();

// Only accept HTMX requests
if (!isset($_SERVER['HTTP_HX_REQUEST'])) {
    http_response_code(400);
    echo 'Direct access not allowed';
    exit();
}

if (!isset($_SESSION['user_id'])) {
    // Return login prompt button
    echo '<button onclick="showLoginPopup()" class="flex items-center gap-2 py-2 px-2 rounded-lg transition-colors bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400">
            <i class="far fa-thumbs-up text-xl like-icon"></i>
            <span class="font-bold text-sm">' . ($lang == 'en' ? 'Like' : 'Beğen') . '</span>
          </button>';
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$reaction = isset($_POST['reaction_type']) ? $_POST['reaction_type'] : 'like';
$valid_reactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

if (!in_array($reaction, $valid_reactions)) {
    $reaction = 'like';
}

if ($post_id <= 0) {
    http_response_code(400);
    echo 'Invalid post';
    exit();
}

try {
    // Check if already reacted
    $stmt = $pdo->prepare("SELECT id, reaction_type FROM post_likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing = $stmt->fetch();

    $is_reacted = false;
    $current_reaction = null;

    if ($existing) {
        if ($existing['reaction_type'] == $reaction) {
            // SAME REACTION -> REMOVE (Toggle Off)
            $pdo->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
            $pdo->prepare("UPDATE posts SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?")->execute([$post_id]);
            $is_reacted = false;
            $current_reaction = null;
        } else {
            // DIFFERENT REACTION -> UPDATE
            $pdo->prepare("UPDATE post_likes SET reaction_type = ? WHERE user_id = ? AND post_id = ?")->execute([$reaction, $user_id, $post_id]);
            $is_reacted = true;
            $current_reaction = $reaction;
        }
    } else {
        // NEW REACTION -> INSERT
        $pdo->prepare("INSERT INTO post_likes (user_id, post_id, reaction_type) VALUES (?, ?, ?)")->execute([$user_id, $post_id, $reaction]);
        $pdo->prepare("UPDATE posts SET like_count = like_count + 1 WHERE id = ?")->execute([$post_id]);
        $is_reacted = true;
        $current_reaction = $reaction;

        // Send Notification (if not own post)
        $post_stmt = $pdo->prepare("SELECT user_id, content FROM posts WHERE id = ?");
        $post_stmt->execute([$post_id]);
        $post = $post_stmt->fetch();

        if ($post && $post['user_id'] != $user_id) {
            $liker_name = $_SESSION['full_name'] ?? $_SESSION['username'];
            
            $reaction_emojis = [
                'like' => '👍', 'love' => '❤️', 'haha' => '😂', 
                'wow' => '😮', 'sad' => '😢', 'angry' => '😡'
            ];
            $emoji = $reaction_emojis[$reaction] ?? '❤️';
            
            $notif_content = $liker_name . " gönderine tepki verdi $emoji";
            $notif_url = 'feed.php#post-' . $post_id;
            
            // In-App Notification
            $ins = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) VALUES (?, ?, 'reaction', ?, ?, ?)");
            $ins->execute([$post['user_id'], $user_id, $post_id, $notif_content, $notif_url]);

            // Push Notification
            sendPushNotification(
                $post['user_id'],
                'Yeni Tepki ' . $emoji,
                $notif_content,
                '/' . $notif_url
            );
        }
    }

    // Get new like count
    $count_stmt = $pdo->prepare("SELECT like_count FROM posts WHERE id = ?");
    $count_stmt->execute([$post_id]);
    $like_count = $count_stmt->fetchColumn();

    // Render the updated like button HTML
    $icons = [
        'like' => 'fas fa-thumbs-up',
        'love' => 'fas fa-heart',
        'haha' => 'fas fa-laugh-squint',
        'wow' => 'fas fa-surprise',
        'sad' => 'fas fa-sad-tear',
        'angry' => 'fas fa-angry'
    ];
    
    $colors = [
        'like' => 'text-blue-500', 
        'love' => 'text-red-500',
        'haha' => 'text-yellow-500',
        'wow' => 'text-yellow-500',
        'sad' => 'text-yellow-500', 
        'angry' => 'text-orange-500'
    ];

    $icon_class = $current_reaction ? $icons[$current_reaction] : 'far fa-thumbs-up';
    $color_class = $current_reaction ? $colors[$current_reaction] : 'text-slate-500 dark:text-slate-400';
    $label = $lang == 'en' ? 'Like' : 'Beğen';
    
?>
<!-- HTMX Like Button Component -->
<div class="relative z-10" id="like-wrapper-<?php echo $post_id; ?>" onclick="event.stopPropagation()">
    <button type="button" 
            class="flex items-center gap-2 py-2 px-2 rounded-lg transition-colors bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 <?php echo $color_class; ?>" 
            hx-post="api/htmx/like_button.php"
            hx-vals='{"post_id": <?php echo $post_id; ?>, "reaction_type": "like"}'
            hx-target="#like-wrapper-<?php echo $post_id; ?>"
            hx-swap="outerHTML"
            onclick="event.stopPropagation()"
            id="like-btn-<?php echo $post_id; ?>">
        <i class="<?php echo $icon_class; ?> text-xl like-icon"></i>
        <span class="font-bold text-sm"><?php echo $label; ?></span>
        <?php if ($like_count > 0): ?>
        <span class="text-sm font-medium opacity-80">(<?php echo $like_count; ?>)</span>
        <?php endif; ?>
    </button>

    <!-- Reaction Bar (long-press popup) -->
    <div class="reaction-bar absolute bottom-10 left-0 bg-white dark:bg-slate-800 p-2 rounded-full shadow-2xl border border-slate-100 dark:border-slate-700 gap-2 z-50" onclick="event.stopPropagation()">
        <button hx-post="api/htmx/like_button.php" 
                hx-vals='{"post_id": <?php echo $post_id; ?>, "reaction_type": "like"}'
                hx-target="#like-wrapper-<?php echo $post_id; ?>"
                hx-swap="outerHTML"
                onclick="event.stopPropagation()"
                class="hover:scale-125 transition-transform text-2xl" title="<?php echo $lang == 'en' ? 'Like' : 'Beğen'; ?>">👍</button>
        <button hx-post="api/htmx/like_button.php" 
                hx-vals='{"post_id": <?php echo $post_id; ?>, "reaction_type": "love"}'
                hx-target="#like-wrapper-<?php echo $post_id; ?>"
                hx-swap="outerHTML"
                onclick="event.stopPropagation()"
                class="hover:scale-125 transition-transform text-2xl" title="<?php echo $lang == 'en' ? 'Love' : 'Bayıldım'; ?>">❤️</button>
        <button hx-post="api/htmx/like_button.php" 
                hx-vals='{"post_id": <?php echo $post_id; ?>, "reaction_type": "haha"}'
                hx-target="#like-wrapper-<?php echo $post_id; ?>"
                hx-swap="outerHTML"
                onclick="event.stopPropagation()"
                class="hover:scale-125 transition-transform text-2xl" title="<?php echo $lang == 'en' ? 'Funny' : 'Komik'; ?>">😂</button>
        <button hx-post="api/htmx/like_button.php" 
                hx-vals='{"post_id": <?php echo $post_id; ?>, "reaction_type": "wow"}'
                hx-target="#like-wrapper-<?php echo $post_id; ?>"
                hx-swap="outerHTML"
                onclick="event.stopPropagation()"
                class="hover:scale-125 transition-transform text-2xl" title="<?php echo $lang == 'en' ? 'Wow' : 'Şaşırdım'; ?>">😮</button>
        <button hx-post="api/htmx/like_button.php" 
                hx-vals='{"post_id": <?php echo $post_id; ?>, "reaction_type": "sad"}'
                hx-target="#like-wrapper-<?php echo $post_id; ?>"
                hx-swap="outerHTML"
                onclick="event.stopPropagation()"
                class="hover:scale-125 transition-transform text-2xl" title="<?php echo $lang == 'en' ? 'Sad' : 'Üzüldüm'; ?>">😢</button>
        <button hx-post="api/htmx/like_button.php" 
                hx-vals='{"post_id": <?php echo $post_id; ?>, "reaction_type": "angry"}'
                hx-target="#like-wrapper-<?php echo $post_id; ?>"
                hx-swap="outerHTML"
                onclick="event.stopPropagation()"
                class="hover:scale-125 transition-transform text-2xl" title="<?php echo $lang == 'en' ? 'Angry' : 'Kızgın'; ?>">😡</button>
    </div>
</div>
<?php

} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="text-red-500 text-sm">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
