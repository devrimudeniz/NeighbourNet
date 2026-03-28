<?php
/**
 * HTMX Get More Posts Endpoint
 * Returns HTML partial with additional post cards for infinite scroll
 * 
 * Usage: hx-get="api/htmx/get_more_posts.php?offset=10"
 *        hx-trigger="revealed"
 *        hx-swap="beforebegin"
 */

require_once '../../includes/db.php';
require_once '../../includes/cdn_helper.php';
require_once '../../includes/ui_components.php';
require_once '../../includes/lang.php';
require_once '../../includes/image_helper.php';
require_once '../../includes/icon_helper.php';
require_once '../../includes/hashtag_helper.php';

session_start();

// Only accept HTMX requests
if (!isset($_SERVER['HTTP_HX_REQUEST'])) {
    http_response_code(400);
    echo 'Direct access not allowed';
    exit();
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 10;
$current_user_id = $_SESSION['user_id'] ?? 0;
// $lang is already set by lang.php
$name_field = $lang == 'tr' ? 'name_tr' : 'name_en';

// Check if wall_user_id column exists
$wall_column_exists = false;
try {
    $check = $pdo->query("SHOW COLUMNS FROM posts LIKE 'wall_user_id'");
    $wall_column_exists = $check->rowCount() > 0;
} catch (Exception $e) {}

$wall_condition = $wall_column_exists ? "AND (p.wall_user_id IS NULL OR p.wall_user_id = p.user_id)" : "";

// Check for user filters
$filter_where = "";
try {
    if ($current_user_id > 0) {
        $check = $pdo->query("SHOW TABLES LIKE 'user_filters'");
        if ($check->rowCount() > 0) {
            $filter_where = " AND p.user_id NOT IN (
                SELECT target_id FROM user_filters WHERE user_id = ? AND filter_type IN ('mute', 'block')
                UNION
                SELECT user_id FROM user_filters WHERE target_id = ? AND filter_type = 'block'
            ) ";
        }
    }
} catch (Exception $e) {}

$sql = "
    SELECT 
        'regular' as post_type,
        p.id, p.user_id, p.content, p.created_at, p.like_count, p.location,
        COALESCE(p.image_url, p.media_url) as image, p.media_type, NULL as group_id, NULL as group_name,
        p.link_url, p.link_title, p.link_description, p.link_image, p.feeling_action, p.feeling_value,
        u.username, u.full_name, u.avatar, u.badge, u.venue_name, u.last_seen,
        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
        (SELECT reaction_type FROM post_likes WHERE post_id = p.id AND user_id = ?) as my_reaction,
        (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as is_saved,
        (SELECT GROUP_CONCAT(reaction_type) FROM post_likes WHERE post_id = p.id) as top_reactions,
        (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
        p.view_count,
        
        p.shared_from_id,
        orig.content as shared_content,
        orig.media_url as shared_media,
        orig.media_type as shared_media_type,
        orig_u.username as shared_username,
        orig_u.full_name as shared_fullname,
        orig_u.avatar as shared_avatar
        
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN posts orig ON p.shared_from_id = orig.id
    LEFT JOIN users orig_u ON orig.user_id = orig_u.id 
    WHERE 1=1 $wall_condition $filter_where
    AND p.deleted_at IS NULL

    UNION ALL

    SELECT 
        'group' as post_type,
        gp.id, gp.user_id, gp.content, gp.created_at, 0 as like_count, NULL as location,
        gp.image, NULL as media_type, gp.group_id, g.$name_field as group_name,
        NULL as link_url, NULL as link_title, NULL as link_description, NULL as link_image, NULL as feeling_action, NULL as feeling_value,
        u.username, u.full_name, u.avatar, u.badge, u.venue_name, u.last_seen,
        gp.comment_count,
        NULL as my_reaction,
        0 as is_saved,
        NULL as top_reactions,
        (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
        0 as view_count,
        NULL as shared_from_id, NULL as shared_content, NULL as shared_media, NULL as shared_media_type, NULL as shared_username, NULL as shared_fullname, NULL as shared_avatar
    FROM group_posts gp 
    JOIN users u ON gp.user_id = u.id 
    JOIN groups g ON gp.group_id = g.id
    WHERE gp.group_id IN (SELECT group_id FROM group_members WHERE user_id = ?)

    ORDER BY created_at DESC 
    LIMIT $limit OFFSET $offset";

$exec_params = [$current_user_id, $current_user_id];
if (!empty($filter_where) && $current_user_id > 0) {
    $exec_params[] = $current_user_id;
    $exec_params[] = $current_user_id;
}
$exec_params[] = $current_user_id;

$stmt = $pdo->prepare($sql);
$stmt->execute($exec_params);
$posts = $stmt->fetchAll();

// If no more posts, return empty with stop trigger
if (empty($posts)) {
    echo '<!-- No more posts -->';
    exit;
}

// Translations
$t = [
    'delete_post' => $lang == 'en' ? 'Delete Post' : 'Sil',
    'friends' => $lang == 'en' ? 'Friends' : 'Arkadaşlar',
];

// Render posts
foreach($posts as $post): 
    // Fetch all images for this post
    $post_images = [];
    try {
        $img_stmt = $pdo->prepare("SELECT image_url FROM post_images WHERE post_id = ? ORDER BY sort_order");
        $img_stmt->execute([$post['id']]);
        $post_images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
    
    if (empty($post_images) && !empty($post['image'])) {
        $post_images = [$post['image']];
    }
    $post_images = array_map('media_url', $post_images);
    $image_count = count($post_images);
?>
<div class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer hover:shadow-xl transition-all" data-post-id="<?php echo $post['id']; ?>" onclick="location.href='post_detail?id=<?php echo $post['id']; ?>'">
    <!-- Header -->
    <div class="flex justify-between items-start mb-4">
        <div class="flex gap-3">
            <a href="profile?uid=<?php echo $post['user_id']; ?>" onclick="event.stopPropagation()" class="shrink-0">
                <?php 
                    $avatar_color = 'default';
                    if (in_array($post['badge'], ['founder', 'moderator'])) $avatar_color = 'primary';
                    elseif ($post['badge'] == 'verified_business') $avatar_color = 'success';
                    elseif ($post['badge'] == 'taxi') $avatar_color = 'warning';
                    
                    echo renderAvatar($post['avatar'], [
                     'size' => 'lg', 
                     'isBordered' => true, 
                     'color' => $avatar_color,
                     'last_seen' => $post['last_seen'],
                     'alt' => $post['full_name'] . "'s Avatar"
                 ]); ?>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <a href="profile?uid=<?php echo $post['user_id']; ?>" class="font-bold text-slate-700 dark:text-slate-200 hover:text-pink-500 transition-colors" onclick="event.stopPropagation()">
                        <?php echo htmlspecialchars($post['full_name']); ?>
                    </a>
                    <?php if($post['badge'] == 'founder') echo heroicon('shield', 'text-pink-500 w-3 h-3'); ?>
                    <?php if($post['badge'] == 'moderator') echo heroicon('gavel', 'text-blue-500 w-3 h-3'); ?>
                    <?php if($post['badge'] == 'business') echo heroicon('check', 'text-green-500 w-3 h-3'); ?>
                    
                    <?php if(!empty($post['feeling_action']) && !empty($post['feeling_value'])): ?>
                        <span class="text-slate-500 text-sm font-normal">
                            <?php echo " " . $post['feeling_action'] . " <strong>" . $post['feeling_value'] . "</strong>"; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if(!empty($post['location'])): ?>
                        <span class="text-slate-400 font-normal text-xs flex items-center gap-1 ml-1">
                            <?php echo heroicon('location', 'text-pink-500 w-3 h-3'); ?> <span class="text-slate-600 dark:text-slate-300 font-medium"><?php echo htmlspecialchars($post['location']); ?></span>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('d.m H:i', strtotime($post['created_at'])); ?></span>
                <?php if ($post['post_type'] == 'group'): ?>
                    <a href="group_detail?id=<?php echo $post['group_id']; ?>" class="block text-xs font-bold text-pink-500 hover:underline mt-1 flex items-center gap-1" onclick="event.stopPropagation()">
                        <?php echo heroicon('users', 'w-3 h-3'); ?><?php echo htmlspecialchars($post['group_name']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="mb-4 group/trans">
        <p class="text-slate-800 dark:text-slate-200 leading-relaxed text-lg transition-all duration-300">
            <?php 
            $content = $post['content'];
            $prev = '';
            while ($prev !== $content) {
                $prev = $content;
                $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
            }
            echo nl2br(linkifyHashtags($content)); 
            ?>
        </p>
    </div>

    <?php if (!empty($post['image']) && $image_count === 1 && $post['media_type'] !== 'video'): ?>
        <div class="rounded-xl mb-4 bg-slate-100 dark:bg-slate-700 overflow-hidden aspect-square">
            <img src="<?php echo htmlspecialchars($post_images[0]); ?>" 
                 class="w-full h-full object-cover" 
                 loading="lazy">
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex gap-3 border-t border-slate-100 dark:border-slate-700 pt-4 text-slate-400 min-h-[44px] items-center pr-1">
        <!-- HTMX Like Button -->
        <div class="relative z-10" id="like-wrapper-<?php echo $post['id']; ?>" onclick="event.stopPropagation()">
            <?php
            $r_type = $post['my_reaction'];
            $r_colors = [
                'like' => 'text-blue-500', 
                'love' => 'text-red-500',
                'haha' => 'text-yellow-500',
                'wow' => 'text-yellow-500',
                'sad' => 'text-yellow-500', 
                'angry' => 'text-orange-500'
            ];
            $r_icons = [
                'like' => 'fas fa-thumbs-up',
                'love' => 'fas fa-heart',
                'haha' => 'fas fa-laugh-squint',
                'wow' => 'fas fa-surprise',
                'sad' => 'fas fa-sad-tear',
                'angry' => 'fas fa-angry'
            ];
            $color_class = $r_type ? ($r_colors[$r_type] ?? 'text-pink-500') : 'text-slate-500 dark:text-slate-400';
            $icon_class = $r_type ? ($r_icons[$r_type] ?? 'fas fa-heart') : 'far fa-thumbs-up';
            ?>
            <button type="button" 
                    class="flex items-center gap-2 py-2 px-2 rounded-lg transition-colors bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 <?php echo $color_class; ?>" 
                    hx-post="api/htmx/like_button.php"
                    hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "like"}'
                    hx-target="#like-wrapper-<?php echo $post['id']; ?>"
                    hx-swap="outerHTML"
                    onclick="event.stopPropagation()"
                    id="like-btn-<?php echo $post['id']; ?>">
                <i class="<?php echo $icon_class; ?> text-xl like-icon"></i>
                <span class="font-bold text-sm"><?php echo $lang == 'en' ? 'Like' : 'Beğen'; ?></span>
                <?php if ($post['like_count'] > 0): ?>
                <span class="text-sm font-medium opacity-80">(<?php echo $post['like_count']; ?>)</span>
                <?php endif; ?>
            </button>

            <!-- Reaction Bar (long-press popup) -->
            <div class="reaction-bar absolute bottom-10 left-0 bg-white dark:bg-slate-800 p-2 rounded-full shadow-2xl border border-slate-100 dark:border-slate-700 gap-2 z-50" onclick="event.stopPropagation()">
                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "like"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" class="hover:scale-125 transition-transform text-2xl">👍</button>
                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "love"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" class="hover:scale-125 transition-transform text-2xl">❤️</button>
                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "haha"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" class="hover:scale-125 transition-transform text-2xl">😂</button>
                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "wow"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" class="hover:scale-125 transition-transform text-2xl">😮</button>
                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "sad"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" class="hover:scale-125 transition-transform text-2xl">😢</button>
                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "angry"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" class="hover:scale-125 transition-transform text-2xl">😡</button>
            </div>
        </div>

        <button onclick="event.stopPropagation(); location.href='post_detail?id=<?php echo $post['id']; ?>#comments'" class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg py-2 px-3 transition-colors text-slate-500 dark:text-slate-400">
            <i class="far fa-comment text-xl"></i>
            <span class="font-bold text-sm"><?php echo $post['comment_count']; ?></span>
        </button>
    </div>
</div>
<?php endforeach; ?>

<!-- Next page trigger -->
<?php $next_offset = $offset + $limit; ?>
<div id="load-more-trigger"
     hx-get="api/htmx/get_more_posts.php?offset=<?php echo $next_offset; ?>"
     hx-trigger="revealed"
     hx-swap="outerHTML"
     hx-indicator="#feed-loading">
</div>
