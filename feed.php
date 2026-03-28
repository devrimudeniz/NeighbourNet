<?php
define('ENABLE_PULL_TO_REFRESH', true); // Pull-to-refresh sadece feed sayfasında
require_once 'includes/bootstrap.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/ui_components.php';
require_once 'includes/image_helper.php';
require_once 'includes/optimize_upload.php';
require_once 'includes/icon_helper.php';
require_once 'includes/hashtag_helper.php';

// Handle New Post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $content = trim($_POST['content']);
    $media_type = 'text';
    $media_url = null;

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_name = uniqid() . '.webp';
        $target_file = $target_dir . $new_name;
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $result = gorselOptimizeEt($_FILES['image'], $target_dir);
            if (isset($result['success'])) {
                $media_type = 'image';
                $media_url = 'uploads/' . $result['filename'];
            }
        }
    } 
    // Handle Link Preview Data
    $link_url = !empty($_POST['link_url']) ? trim($_POST['link_url']) : null;
    $link_title = !empty($_POST['link_title']) ? trim($_POST['link_title']) : null;
    $link_description = !empty($_POST['link_description']) ? trim($_POST['link_description']) : null;
    $link_video = !empty($_POST['video_url']) ? trim($_POST['video_url']) : null; // Legacy support or specific field
    $link_image = !empty($_POST['link_image']) ? trim($_POST['link_image']) : null;

    // Handle Feelings
    $feeling_action = !empty($_POST['feeling_action']) ? trim($_POST['feeling_action']) : null;
    $feeling_value = !empty($_POST['feeling_value']) ? trim($_POST['feeling_value']) : null;

    // Handle Video Upload
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["video"]["name"], PATHINFO_EXTENSION));
        $new_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_name;
        
        $allowed = ['mp4', 'mov', 'avi', 'mpeg', 'webm'];
        if (in_array($file_ext, $allowed)) {
            if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
                $media_type = 'video';
                $media_url = 'uploads/' . $new_name;
            }
        }
    }

    if (!empty($content) || $media_url || $link_url || $feeling_value) {
        $location = trim($_POST['location'] ?? '');
        
        $sql = "INSERT INTO posts (user_id, content, media_type, media_url, image_url, location, link_url, link_title, link_description, link_image, feeling_action, feeling_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'], $content, $media_type, $media_url, 
            ($media_type == 'image' ? $media_url : null), 
            $location,
            $link_url, $link_title, $link_description, $link_image,
            $feeling_action, $feeling_value
        ]);
        
        header("Location: feed");
        exit();
    }
}

// Get All Posts with User Info and Comment Counts
// Get All Posts (Regular + Joined Groups)
$user_id = $_SESSION['user_id'] ?? 0;
// $lang is already set by bootstrap.php -> lang.php
$sort = $_GET['sort'] ?? 'latest';
$name_field = $lang == 'tr' ? 'name_tr' : 'name_en';

// Guest feed cache (90 sec) - reduces DB load for anonymous visitors
$posts = null;
if ($user_id == 0 && $sort === 'latest' && !isset($_GET['post_id'])) {
    require_once 'includes/cache_helper.php';
    $cache = new CacheHelper();
    $cached = $cache->get('feed_guest_latest_v1', 90);
    if ($cached !== false) {
        $posts = $cached; // Use cached data (could be an array, even empty)
    }
    // If cache miss ($cached === false), $posts stays null → query will run below
}

$order_by = "ORDER BY created_at DESC";
$where_clause = "";
$params = [$user_id, $user_id]; // Default params for UNION query

if ($sort === 'trending') {
    $order_by = "ORDER BY (like_count + comment_count) DESC, created_at DESC";
} elseif ($sort === 'friends') {
    // Filter for friends only (and self)
    $where_clause = "AND (p.user_id = ? OR p.user_id IN (SELECT receiver_id FROM friendships WHERE requester_id = ? AND status = 'accepted' UNION SELECT requester_id FROM friendships WHERE receiver_id = ? AND status = 'accepted'))";
    // We need to inject this into the SQL and update params
    // Current SQL structure is complex with UNION. 
    // Simplified strategy: We will inject this WHERE clause into the first SELECT (Regular Posts)
    // Params need to be adjusted: [user_id (for my_reaction), user_id (for where), user_id (for friend check 1), user_id (for friend check 2), user_id (for group)]
    // Actually, let's just modify the query variable directly below efficiently.
}

// Check if wall_user_id column exists (cached 24h - schema rarely changes)
$wall_filter_sql = "";
$cache_dir = __DIR__ . '/cache';
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
$wall_filter_cache = $cache_dir . '/wall_filter_has_column.cache';
if (file_exists($wall_filter_cache) && (time() - filemtime($wall_filter_cache)) < 86400) {
    $wall_filter_sql = file_get_contents($wall_filter_cache) ?: "";
} else {
    try {
        $wc = $pdo->query("SHOW COLUMNS FROM posts LIKE 'wall_user_id'");
        $wf = ($wc->rowCount() > 0) ? " AND (p.wall_user_id IS NULL OR p.wall_user_id = p.user_id) " : "";
        file_put_contents($wall_filter_cache, $wf);
        $wall_filter_sql = $wf;
    } catch(Exception $e) {}
}

$filter_where = "";
try {
    if ($user_id > 0) {
        // Cache table existence check (24h) - schema rarely changes
        $uf_cache_file = $cache_dir . '/has_user_filters.cache';
        $has_uf_table = null;
        if (file_exists($uf_cache_file) && (time() - filemtime($uf_cache_file)) < 86400) {
            $has_uf_table = (bool)file_get_contents($uf_cache_file);
        } else {
            $check = $pdo->query("SHOW TABLES LIKE 'user_filters'");
            $has_uf_table = ($check->rowCount() > 0);
            file_put_contents($uf_cache_file, $has_uf_table ? '1' : '0');
        }
        if ($has_uf_table) {
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
        
        -- Shared Post Data
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
    WHERE 1=1 $where_clause $wall_filter_sql $filter_where
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
    AND (gp.visibility = 'everyone' OR gp.visibility IS NULL)
    " . (empty($filter_where) ? "" : "AND gp.user_id NOT IN (
        SELECT target_id FROM user_filters WHERE user_id = ? AND filter_type IN ('mute', 'block')
        UNION
        SELECT user_id FROM user_filters WHERE target_id = ? AND filter_type = 'block'
    )") . "

    $order_by LIMIT 50";

if ($posts === null) {
try {
    $exec_params = [$user_id, $user_id]; // for my_reaction AND is_saved
    
    // Add params for regular post filtering
    if (!empty($filter_where) && $user_id > 0) {
        $exec_params[] = $user_id; // user_filters.user_id
        $exec_params[] = $user_id; // user_filters.target_id (blocked by me)
    }

    if ($sort === 'friends') {
        $exec_params[] = $user_id; // p.user_id = ?
        $exec_params[] = $user_id; // user1_id = ?
        $exec_params[] = $user_id; // user2_id = ?
    }
    
    // Group query params
    $exec_params[] = $user_id; // for group query (group_members)
    if (!empty($filter_where) && $user_id > 0) {
        $exec_params[] = $user_id; // user_filters.user_id
        $exec_params[] = $user_id; // user_filters.target_id (blocked by me)
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($exec_params);
    $posts = $stmt->fetchAll();

    // Cache guest feed for next request
    if ($user_id == 0 && $sort === 'latest' && !isset($_GET['post_id']) && isset($cache)) {
        $cache->set('feed_guest_latest_v1', $posts);
    }

    // Son feed ziyaretini kaydet (index'te "yeni gönderi var" için)
    if ($user_id > 0 && count($posts) > 0 && isset($posts[0]['created_at'])) {
        $_SESSION['last_feed_post_time'] = strtotime($posts[0]['created_at']);
    }

    // Check for specific post_id request
    if (isset($_GET['post_id'])) {
        $target_id = (int)$_GET['post_id'];
        $found = false;
        foreach ($posts as $p) {
            if ($p['id'] == $target_id && $p['post_type'] == 'regular') {
                $found = true;
                break;
            }
        }

        if (!$found) {
            // Fetch the specific post and prepend
             $sql_single = "
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
                
                -- Shared Post Data
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
            WHERE p.id = ? AND p.deleted_at IS NULL";

            $stmt_single = $pdo->prepare($sql_single);
            $stmt_single->execute([$user_id, $user_id, $target_id]);
            $single_post = $stmt_single->fetch();

            if ($single_post) {
                array_unshift($posts, $single_post);
            }
        }
    }
} catch (PDOException $e) {
    // Fallback if saved_posts doesn't exist yet
    if (strpos($e->getMessage(), "saved_posts") !== false) {
        // Use original SQL without is_saved column
        $sql_fallback = "
            SELECT 
                'regular' as post_type,
                p.id, p.user_id, p.content, p.created_at, p.like_count, p.location,
                NULL as image, NULL as group_id, NULL as group_name,
                u.username, u.full_name, u.avatar, u.badge, u.venue_name, u.last_seen,
                (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
                (SELECT reaction_type FROM post_likes WHERE post_id = p.id AND user_id = ?) as my_reaction,
                0 as is_saved,
                (SELECT GROUP_CONCAT(reaction_type) FROM post_likes WHERE post_id = p.id) as top_reactions,
                (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
                p.view_count,
                p.shared_from_id, orig.content as shared_content, orig.media_url as shared_media,
                orig.media_type as shared_media_type, orig_u.username as shared_username,
                orig_u.full_name as shared_fullname, orig_u.avatar as shared_avatar
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN posts orig ON p.shared_from_id = orig.id
            LEFT JOIN users orig_u ON orig.user_id = orig_u.id 
            WHERE 1=1 $where_clause $wall_filter_sql
            AND p.deleted_at IS NULL

            UNION ALL

            SELECT 
                'group' as post_type,
                gp.id, gp.user_id, gp.content, gp.created_at, 0 as like_count, NULL as location,
                gp.image, gp.group_id, g.$name_field as group_name,
                NULL as link_url, NULL as link_title, NULL as link_description, NULL as link_image, NULL as feeling_action, NULL as feeling_value,
                u.username, u.full_name, u.avatar, u.badge, u.venue_name, u.last_seen,
                gp.comment_count,
                NULL as my_reaction,
                0 as is_saved,
                NULL as top_reactions,
                (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
                0 as view_count,
                NULL, NULL, NULL, NULL, NULL, NULL, NULL
            FROM group_posts gp 
            JOIN users u ON gp.user_id = u.id 
            JOIN groups g ON gp.group_id = g.id
            WHERE gp.group_id IN (SELECT group_id FROM group_members WHERE user_id = ?)
            AND (gp.visibility = 'everyone' OR gp.visibility IS NULL)

            $order_by LIMIT 50";
        
        $stmt = $pdo->prepare($sql_fallback);
        $exec_params_fb = [$user_id]; // my_reaction only
        if ($sort === 'friends') { $exec_params_fb[] = $user_id; $exec_params_fb[] = $user_id; $exec_params_fb[] = $user_id; }
        $exec_params_fb[] = $user_id; // group query
        
        $stmt->execute($exec_params_fb);
        $posts = $stmt->fetchAll();
    } else {
        throw $e;
    }
}
} // end if ($posts === null)

// Ensure post_comments table exists (cached check)
$pc_cache_file = (isset($cache_dir) ? $cache_dir : __DIR__ . '/cache') . '/has_post_comments.cache';
if (!file_exists($pc_cache_file) || (time() - filemtime($pc_cache_file)) > 86400) {
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
        file_put_contents($pc_cache_file, '1');
    } catch (PDOException $e) {}
}

// ── Batch fetch related data (N+1 → batch queries) ──
// Instead of querying per-post in the loop, fetch all at once
$batch_images = [];
$batch_comments = [];
$batch_polls = [];
$batch_poll_options = [];
$batch_poll_votes = [];

if (!empty($posts)) {
    // Collect all post IDs (regular posts only for images/polls/comments)
    $all_post_ids = [];
    $regular_post_ids = [];
    foreach ($posts as $p) {
        $all_post_ids[] = $p['id'];
        if ($p['post_type'] === 'regular') {
            $regular_post_ids[] = $p['id'];
        }
    }

    try {
        // 1) Batch fetch post_images
        if (!empty($regular_post_ids)) {
            $ph = implode(',', array_fill(0, count($regular_post_ids), '?'));
            $img_stmt = $pdo->prepare("SELECT post_id, image_url FROM post_images WHERE post_id IN ($ph) ORDER BY post_id, sort_order");
            $img_stmt->execute($regular_post_ids);
            foreach ($img_stmt->fetchAll() as $row) {
                $batch_images[$row['post_id']][] = $row['image_url'];
            }
        }

        // 2) Batch fetch preview comments (first 2 per post)
        if (!empty($all_post_ids)) {
            $ph = implode(',', array_fill(0, count($all_post_ids), '?'));
            $cmt_stmt = $pdo->prepare("SELECT c.post_id, c.id, c.content, c.created_at, c.user_id, u.username, u.full_name, u.avatar FROM post_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id IN ($ph) ORDER BY c.post_id, c.created_at ASC");
            $cmt_stmt->execute($all_post_ids);
            foreach ($cmt_stmt->fetchAll() as $row) {
                $pid = $row['post_id'];
                if (!isset($batch_comments[$pid])) $batch_comments[$pid] = [];
                if (count($batch_comments[$pid]) < 2) {
                    $batch_comments[$pid][] = $row;
                }
            }
        }

        // 3) Batch fetch polls
        if (!empty($regular_post_ids)) {
            $ph = implode(',', array_fill(0, count($regular_post_ids), '?'));
            $poll_stmt = $pdo->prepare("SELECT * FROM polls WHERE post_id IN ($ph)");
            $poll_stmt->execute($regular_post_ids);
            foreach ($poll_stmt->fetchAll() as $row) {
                $batch_polls[$row['post_id']] = $row;
            }

            if (!empty($batch_polls)) {
                $poll_ids = array_column(array_values($batch_polls), 'id');
                $ph2 = implode(',', array_fill(0, count($poll_ids), '?'));

                // Poll options
                $opts_stmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id IN ($ph2) ORDER BY id");
                $opts_stmt->execute($poll_ids);
                foreach ($opts_stmt->fetchAll() as $row) {
                    $batch_poll_options[$row['poll_id']][] = $row;
                }

                // Current user's votes
                if ($user_id > 0) {
                    $vote_stmt = $pdo->prepare("SELECT poll_id, option_id FROM poll_votes WHERE poll_id IN ($ph2) AND user_id = ?");
                    $vote_params = array_merge($poll_ids, [$user_id]);
                    $vote_stmt->execute($vote_params);
                    foreach ($vote_stmt->fetchAll() as $row) {
                        $batch_poll_votes[$row['poll_id']] = $row['option_id'];
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Fallback: if batch fails, loop queries still work via original code
    }
}

// For WhatsApp/Messenger link preview: set $post when sharing specific post URL (feed.php?post_id=X)
if (isset($_GET['post_id']) && !empty($posts) && isset($posts[0])) {
    $post = $posts[0];
    if (empty($post['image_url']) && !empty($post['image'])) $post['image_url'] = $post['image'];
    if (empty($post['media_url']) && !empty($post['image'])) $post['media_url'] = $post['image'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/seo_tags.php'; ?>
    <!-- Core CSS & Config -->
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300 overflow-x-hidden">

    <!-- Premium Background -->
    <!-- Light Mode -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <!-- Dark Mode -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <?php include 'includes/header.php'; ?>



    <!-- Main Feed -->
    <main id="swup-main" class="transition-main container mx-auto px-4 pt-16 md:pt-24 max-w-2xl">



        <!-- Advertising Warning -->
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl p-4 mb-4 flex items-center gap-4 border border-slate-200 dark:border-slate-700">
            <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-200">
                <i class="fas fa-store"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-slate-600 dark:text-slate-300 font-medium leading-relaxed">
                    <?php if($lang == 'en'): ?>
                        <span class="font-bold">Business ads belong in Directory!</span>
                        <a href="directory" class="ml-1 inline-flex items-center gap-1 text-slate-900 dark:text-white font-bold hover:text-[#0055FF] transition-colors">
                            Go to Directory <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="font-bold">İşletme reklamları Rehber'de!</span>
                        <a href="directory" class="ml-1 inline-flex items-center gap-1 text-slate-900 dark:text-white font-bold hover:text-[#0055FF] transition-colors">
                            Rehber'e Git <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <?php if(isset($_SESSION['user_id'])): ?>
        

        <!-- Advanced Facebook-Style Composer -->
        
        <!-- Create Post removed as per request -->



        <?php else: ?>
            <div class="bg-slate-900 dark:bg-white rounded-3xl p-6 text-white dark:text-slate-900 flex items-center justify-between gap-4 mb-6 shadow-xl shadow-slate-900/10"> 
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 dark:bg-slate-900/10 rounded-2xl flex items-center justify-center shrink-0 backdrop-blur-sm">
                         <i class="fas fa-comment-dots text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm uppercase tracking-wider"><?php echo $t['join_chat_title']; ?></h3>
                        <p class="text-xs opacity-80 leading-tight max-w-[200px] sm:max-w-none mt-1"><?php echo $t['join_chat_desc']; ?></p>
                    </div>
                </div>
                <a href="register" class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-6 py-3 rounded-xl text-xs font-bold shrink-0 hover:scale-105 transition-all shadow-lg active:scale-95"><?php echo $t['register']; ?></a>
            </div>
        <?php endif; ?>

        <!-- Feed Sort Tabs -->
        <!-- Feed Sort Tabs -->
        <div class="flex gap-1 bg-white/50 dark:bg-slate-800/50 p-1 rounded-2xl mb-6 backdrop-blur-sm border border-slate-200/50 dark:border-slate-700/50 max-w-full mx-auto shadow-sm overflow-x-auto">
            <a href="feed?sort=latest" class="px-3 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all whitespace-nowrap <?php echo $sort === 'latest' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-lg' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'; ?>">
                <i class="fas fa-clock mr-1 sm:mr-2 opacity-70"></i><?php echo $t['sort_latest']; ?>
            </a>
            <a href="feed?sort=trending" class="px-3 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all whitespace-nowrap <?php echo $sort === 'trending' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-lg' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'; ?>">
                <i class="fas fa-fire mr-1 sm:mr-2 opacity-70"></i><?php echo $t['sort_trending']; ?>
            </a>
            <a href="feed?sort=friends" class="px-3 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all whitespace-nowrap <?php echo $sort === 'friends' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-lg' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'; ?>">
                <i class="fas fa-user-friends mr-1 sm:mr-2 opacity-70"></i><?php echo $t['friends']; ?>
            </a>
        </div>

        <!-- Yukarı Çık (Feed only) -->
        <button id="scroll-to-top" type="button" class="fixed bottom-24 right-4 md:bottom-28 md:right-6 z-40 w-12 h-12 rounded-full bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xl flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 hover:scale-110 hover:shadow-2xl" aria-label="<?php echo $lang == 'en' ? 'Scroll to top' : 'Yukarı çık'; ?>">
            <?php echo heroicon('chevron_up', 'w-6 h-6'); ?>
        </button>

        <!-- Feed -->
        <div id="feed-container" class="space-y-6 pb-20">
            <?php if (empty($posts)): ?>
            <div class="text-center py-16 bg-white dark:bg-slate-800/50 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    <i class="fas fa-newspaper text-4xl text-slate-400"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-700 dark:text-slate-200 mb-2"><?php echo $lang == 'en' ? 'No posts yet' : 'Henüz gönderi yok'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mb-6 max-w-sm mx-auto"><?php echo $lang == 'en' ? 'Be the first to share something with the community!' : 'Toplulukla paylaşacak ilk sen ol!'; ?></p>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="create_post_page" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0055FF] hover:bg-[#0044CC] text-white font-bold rounded-xl transition-colors">
                    <i class="fas fa-plus"></i> <?php echo $lang == 'en' ? 'Create Post' : 'Gönderi Oluştur'; ?>
                </a>
                <?php else: ?>
                <a href="register" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0055FF] hover:bg-[#0044CC] text-white font-bold rounded-xl transition-colors">
                    <?php echo $t['register']; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <?php $post_counter = 0; foreach($posts as $post): ?>
            <div class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer hover:shadow-xl transition-all mb-4" data-post-id="<?php echo $post['id']; ?>" onclick="location.href='post_detail?id=<?php echo $post['id']; ?>'">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4">
                    <div class="flex gap-3">
                        <a href="profile?uid=<?php echo $post['user_id']; ?>" aria-label="<?php echo htmlspecialchars($post['full_name']); ?> profile" onclick="event.stopPropagation()">
                             <?php 
                                // Determine effective badge (check legacy and new expert badges)
                                $badge_raw = strtolower($post['badge'] ?? '');
                                $expert_badges = !empty($post['expert_badges']) ? explode(',', strtolower($post['expert_badges'])) : [];
                                
                                // Priority Override: If main badge is empty/user, check expert badges
                                if (($badge_raw == '' || $badge_raw == 'user') && !empty($expert_badges)) {
                                    if (count(array_intersect(['local_guide', 'local guide', 'guide'], $expert_badges)) > 0) {
                                        $badge_raw = 'local_guide';
                                    } elseif (in_array('founder', $expert_badges)) {
                                        $badge_raw = 'founder';
                                    } elseif (in_array('moderator', $expert_badges)) {
                                        $badge_raw = 'moderator';
                                    }
                                }
                                
                                $avatar_color = $badge_raw ?: 'user';
                                
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
                                <?php if($badge_raw == 'founder') echo heroicon('shield', 'text-red-500 w-3 h-3'); ?>
                                <?php if($badge_raw == 'moderator') echo heroicon('gavel', 'text-indigo-500 w-3 h-3'); ?>
                                <?php if(in_array($badge_raw, ['local_guide', 'local guide', 'guide'])) echo heroicon('map', 'text-amber-500 w-3 h-3'); ?>
                                <?php if(in_array($badge_raw, ['business', 'verified_business'])) echo heroicon('check', 'text-emerald-500 w-3 h-3'); ?>
                                <?php if($badge_raw == 'captain') echo heroicon('anchor', 'text-blue-500 w-3 h-3'); ?>
                                <?php if($badge_raw == 'taxi') echo heroicon('taxi', 'text-yellow-500 w-3 h-3'); ?>
                                
                                <?php if(!empty($post['feeling_action']) && !empty($post['feeling_value'])): ?>
                                    <span class="text-slate-500 text-sm font-normal">
                                        <?php 
                                        $f_action = $post['feeling_action'];
                                        $f_value = $post['feeling_value'];
                                        
                                        // Translate keys
                                        $trans_action = $t['action_' . $f_action] ?? $f_action;
                                        $trans_value = $t['feeling_' . $f_value] ?? $f_value;
                                        
                                        echo " " . $trans_action . " <strong>" . $trans_value . "</strong>";
                                        ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if(!empty($post['location'])): ?>
                                    <span class="text-slate-400 font-normal text-xs flex items-center gap-1 ml-1" title="Konum">
                                        <?php echo heroicon('location', 'text-pink-500 w-3 h-3'); ?> <span class="text-slate-600 dark:text-slate-300 font-medium"><?php echo htmlspecialchars($post['location']); ?></span>
                                    </span>
                                <?php endif; ?>
                                
                                <?php 
                                // Display Expert Badges in Feed
                                if (!empty($post['expert_badges'])) {
                                    $e_badges = explode(',', $post['expert_badges']);
                                    $b_icons = [
                                        'foodie' => 'foodie',
                                        'place_scout' => 'place_scout',
                                        'explorer' => 'location',
                                        'photographer' => 'camera',
                                        'historian' => 'library',
                                        'local' => 'certificate'
                                    ];
                                    $b_colors = [
                                        'foodie' => 'text-orange-500',
                                        'place_scout' => 'text-teal-500',
                                        'explorer' => 'text-green-500',
                                        'photographer' => 'text-blue-500',
                                        'historian' => 'text-yellow-600',
                                        'local' => 'text-violet-600'
                                    ];
                                    foreach($e_badges as $eb) {
                                        if (isset($b_icons[$eb])) {
                                            echo heroicon($b_icons[$eb], $b_colors[$eb] . ' w-3 h-3');
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['username']); ?> • <span class="timeago" datetime="<?php echo $post['created_at']; ?>"><?php echo date('d.m H:i', strtotime($post['created_at'])); ?></span></span>
                            <?php if ($post['post_type'] == 'group'): ?>
                                <a href="group_detail?id=<?php echo $post['group_id']; ?>" class="block text-xs font-bold text-pink-500 hover:underline mt-1 flex items-center gap-1">
                                    <?php echo heroicon('users', 'w-3 h-3'); ?><?php echo htmlspecialchars($post['group_name']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                        <div class="relative" id="post-menu-container-<?php echo $post['id']; ?>">
                            <button onclick="event.stopPropagation(); togglePostMenu(<?php echo $post['id']; ?>)" aria-label="Post options" class="text-slate-300 hover:text-red-500 transition-colors p-2">
                                <?php echo heroicon('ellipsis', 'w-5 h-5'); ?>
                            </button>
                            <div id="post-menu-<?php echo $post['id']; ?>" class="hidden absolute right-0 top-8 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-20">
                                <button onclick="event.stopPropagation(); editPost(<?php echo $post['id']; ?>)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                    <?php echo heroicon('edit', 'w-4 h-4 text-blue-500'); ?> Edit Post
                                </button>
                                <button onclick="event.stopPropagation(); toggleSave(<?php echo $post['id']; ?>, this)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                    <i class="<?php echo ($post['is_saved'] ?? 0) ? 'fas' : 'far'; ?> fa-bookmark w-4 h-4 text-yellow-500"></i> 
                                    <span class="save-text"><?php echo ($post['is_saved'] ?? 0) ? ($lang == 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar') : ($lang == 'en' ? 'Save' : 'Kaydet'); ?></span>
                                </button>
                                <button onclick="event.stopPropagation(); confirmDeletePost(<?php echo $post['id']; ?>, '<?php echo $post['post_type'] ?? 'regular'; ?>')" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors border-t border-slate-50 dark:border-slate-700">
                                    <?php echo heroicon('trash', 'w-4 h-4'); ?> <?php echo $t['delete_post']; ?>
                                </button>
                            </div>
                        </div>
                    <?php elseif(isset($_SESSION['user_id'])): ?>
                        <?php $can_mod_delete = in_array($_SESSION['badge'] ?? '', ['founder', 'moderator']); ?>
                        <!-- Report menu for non-owners (+ moderator delete) -->
                        <div class="relative" id="post-menu-container-<?php echo $post['id']; ?>">
                            <button onclick="event.stopPropagation(); togglePostMenu(<?php echo $post['id']; ?>)" aria-label="Post options" class="text-slate-300 hover:text-slate-500 transition-colors p-2">
                                <?php echo heroicon('ellipsis', 'w-5 h-5'); ?>
                            </button>
                            <div id="post-menu-<?php echo $post['id']; ?>" class="hidden absolute right-0 top-8 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-20">
                                <button onclick="event.stopPropagation(); toggleSave(<?php echo $post['id']; ?>, this)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                    <i class="<?php echo ($post['is_saved'] ?? 0) ? 'fas' : 'far'; ?> fa-bookmark w-4 h-4 text-yellow-500"></i> 
                                    <span class="save-text"><?php echo ($post['is_saved'] ?? 0) ? ($lang == 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar') : ($lang == 'en' ? 'Save' : 'Kaydet'); ?></span>
                                </button>
                                <?php if ($can_mod_delete): ?>
                                <button onclick="event.stopPropagation(); confirmDeletePost(<?php echo $post['id']; ?>, '<?php echo $post['post_type'] ?? 'regular'; ?>')" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors border-t border-slate-50 dark:border-slate-700">
                                    <?php echo heroicon('trash', 'w-4 h-4'); ?> <?php echo $t['delete_post']; ?>
                                </button>
                                <?php endif; ?>
                                <button onclick="event.stopPropagation(); openReportModal(<?php echo $post['id']; ?>, 'post')" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors">
                                    <?php echo heroicon('flag', 'w-4 h-4'); ?> <?php echo $lang == 'en' ? 'Report Post' : 'Gönderiyi Şikayet Et'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div id="post-content-wrapper-<?php echo $post['id']; ?>" class="mb-4 group/trans">
                    <p id="post-original-<?php echo $post['id']; ?>" class="text-slate-800 dark:text-slate-200 leading-relaxed text-lg transition-all duration-300">
                        <?php 
                        // Decode any HTML entities that may be stored in DB
                        $content = $post['content'];
                        $prev = '';
                        while ($prev !== $content) {
                            $prev = $content;
                            $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
                        }
                        // linkifyHashtags handles escaping internally
                        echo nl2br(linkifyHashtags($content)); 
                        ?>
                    </p>
                    <p id="post-translated-<?php echo $post['id']; ?>" class="hidden text-slate-800 dark:text-slate-200 leading-relaxed text-lg italic border-l-4 border-pink-500 pl-4 transition-all duration-300">
                    </p>

                    <!-- Link Preview Card -->
                    <?php if(!empty($post['link_url']) && !empty($post['link_title'])): ?>
                    <a href="<?php echo htmlspecialchars($post['link_url']); ?>" target="_blank" class="block mt-3 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group/link">
                        <?php if(!empty($post['link_image'])): ?>
                            <div class="aspect-video w-full bg-slate-200 dark:bg-slate-800 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($post['link_image']); ?>')"></div>
                        <?php endif; ?>
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/50">
                            <p class="text-xs text-slate-500 uppercase font-bold mb-1"><?php echo parse_url($post['link_url'], PHP_URL_HOST); ?></p>
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 mb-1 group-hover/link:text-blue-600 transition-colors"><?php echo htmlspecialchars($post['link_title']); ?></h4>
                            <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2"><?php echo htmlspecialchars($post['link_description']); ?></p>
                        </div>
                    </a>
                    <?php endif; ?>

                    <!-- Shared Content Card -->
                    <?php if(!empty($post['shared_from_id'])): ?>
                    <div class="mt-3 border border-slate-200 dark:border-slate-700 rounded-xl p-4 bg-slate-50 dark:bg-slate-800/50">
                        <div class="flex items-center gap-2 mb-2">
                             <img src="<?php echo media_url($post['shared_avatar']); ?>" class="w-6 h-6 rounded-full object-cover" width="24" height="24" alt="<?php echo htmlspecialchars($post['shared_fullname']); ?>">
                             <div class="flex flex-col">
                                 <span class="text-sm font-bold dark:text-slate-200"><?php echo htmlspecialchars($post['shared_fullname']); ?></span>
                                 <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['shared_username']); ?></span>
                             </div>
                        </div>
                        <p class="text-sm text-slate-700 dark:text-slate-300 mb-2"><?php echo nl2br(linkifyHashtags($post['shared_content'] ?? '')); ?></p>
                        <?php if(!empty($post['shared_media'])): ?>
                            <?php if($post['shared_media_type'] == 'image'): ?>
                                <img src="<?php echo htmlspecialchars(media_url($post['shared_media'])); ?>" class="rounded-lg w-full object-cover aspect-video max-h-60" width="400" height="225" loading="lazy">
                            <?php elseif($post['shared_media_type'] == 'video'): ?>
                                <div class="relative pt-[56.25%] bg-black rounded-lg overflow-hidden">
                                    <iframe src="<?php echo htmlspecialchars(media_url($post['shared_media'])); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Poll Display (for poll posts) -->
                <?php if($post['media_type'] == 'poll'): ?>
                    <?php
                    // Use batch-fetched poll data (no per-post query)
                    $poll_data = $batch_polls[$post['id']] ?? null;
                    
                    if ($poll_data):
                        $poll_options = $batch_poll_options[$poll_data['id']] ?? [];
                        $total_votes = array_sum(array_column($poll_options, 'vote_count'));
                        
                        // Check if user already voted (from batch)
                        $user_vote = $batch_poll_votes[$poll_data['id']] ?? null;
                        
                        $poll_ended = $poll_data['end_date'] && strtotime($poll_data['end_date']) < time();
                    ?>
                    <div class="poll-card bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 mb-4 border border-slate-200 dark:border-slate-700" onclick="event.stopPropagation()">
                        <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                            <i class="fas fa-poll text-violet-500"></i>
                            <?php echo htmlspecialchars($poll_data['question']); ?>
                        </h4>
                        <div class="space-y-2">
                            <?php foreach($poll_options as $option): ?>
                                <?php 
                                $percentage = $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100) : 0;
                                $is_user_vote = $user_vote == $option['id'];
                                ?>
                                <button type="button" 
                                        onclick="votePoll(<?php echo $poll_data['id']; ?>, <?php echo $option['id']; ?>, this)"
                                        data-option-id="<?php echo $option['id']; ?>"
                                        class="poll-option-btn w-full relative overflow-hidden rounded-xl p-3 text-left transition-all <?php echo $is_user_vote ? 'voted ring-2 ring-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?> <?php echo $poll_ended ? 'pointer-events-none opacity-70' : ''; ?>"
                                        <?php echo $poll_ended ? 'disabled' : ''; ?>>
                                    <div class="poll-bar absolute inset-0 bg-violet-100 dark:bg-violet-900/30 transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                                    <div class="relative z-10 flex items-center justify-between">
                                        <span class="font-medium text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                            <?php if($is_user_vote): ?><i class="fas fa-check-circle text-violet-500"></i><?php endif; ?>
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                        </span>
                                        <span class="flex items-center gap-2 text-sm">
                                            <span class="poll-percent font-bold text-slate-600 dark:text-slate-300"><?php echo $percentage; ?>%</span>
                                            <span class="poll-votes text-slate-400 text-xs">(<?php echo $option['vote_count']; ?>)</span>
                                        </span>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                            <span><span class="poll-total"><?php echo $total_votes; ?></span> <?php echo $lang == 'en' ? 'votes' : 'oy'; ?></span>
                            <?php if($poll_ended): ?>
                                <span class="text-red-500 font-bold"><?php echo $lang == 'en' ? 'Poll ended' : 'Anket sona erdi'; ?></span>
                            <?php elseif($poll_data['end_date']): ?>
                                <span><?php echo $lang == 'en' ? 'Ends' : 'Bitiş'; ?>: <?php echo date('d M H:i', strtotime($poll_data['end_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($post['image'])): ?>
                    <?php 
                    // Use batch-fetched images (no per-post query)
                    $post_images = $batch_images[$post['id']] ?? [];
                    
                    // If no multi-images, use the single image
                    if (empty($post_images)) {
                        $post_images = [$post['image']];
                    }
                    
                    // Convert all image URLs to CDN URLs
                    $post_images = array_map('media_url', $post_images);
                    $image_count = count($post_images);
                    ?>
                    
                    <?php if ($post['media_type'] === 'video'): ?>
                        <?php 
                        $video_src = $post['image'];
                        $is_local_video = strpos($video_src, 'uploads/') !== false || (strpos($video_src, 'http') === false);
                        // Convert local videos to CDN URL
                        if ($is_local_video) {
                            $video_src = media_url($video_src);
                        }
                        $video_src = htmlspecialchars($video_src);
                        ?>
                        
                        <?php if($is_local_video): ?>
                        <!-- Instagram-Style Video Player (Local) -->
                        <div class="relative rounded-xl mb-4 bg-black overflow-hidden aspect-video group" onclick="event.stopPropagation()">
                            <video 
                                id="video-<?php echo $post['id']; ?>"
                                src="<?php echo $video_src; ?>" 
                                class="w-full h-full object-contain insta-video" 
                                muted
                                loop
                                playsinline
                                preload="metadata"
                                data-post-id="<?php echo $post['id']; ?>">
                            </video>
                            
                            <!-- Play/Pause Overlay (tap to toggle) -->
                            <div class="absolute inset-0 flex items-center justify-center cursor-pointer" 
                                 onclick="toggleVideoPlay(<?php echo $post['id']; ?>)">
                                <div id="play-icon-<?php echo $post['id']; ?>" 
                                     class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center opacity-0 transition-opacity pointer-events-none">
                                    <i class="fas fa-play text-white text-2xl ml-1"></i>
                                </div>
                            </div>
                            
                            <!-- Mute/Unmute Button -->
                            <button onclick="event.stopPropagation(); toggleVideoMute(<?php echo $post['id']; ?>)" 
                                    class="absolute bottom-3 right-3 w-9 h-9 bg-black/60 rounded-full flex items-center justify-center text-white text-sm transition-all hover:bg-black/80 z-10">
                                <i id="mute-icon-<?php echo $post['id']; ?>" class="fas fa-volume-mute"></i>
                            </button>
                            
                            <!-- Progress Bar (clickable for seeking) -->
                            <div class="absolute bottom-0 left-0 right-0 h-6 flex items-end cursor-pointer z-10" 
                                 onclick="event.stopPropagation(); seekVideo(<?php echo $post['id']; ?>, event)"
                                 ontouchmove="event.stopPropagation(); seekVideo(<?php echo $post['id']; ?>, event)">
                                <div class="w-full h-1 bg-white/30 group-hover:h-2 transition-all">
                                    <div id="progress-<?php echo $post['id']; ?>" class="h-full bg-white transition-all" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- IFrame Embed Video (Youtube/Vimeo) -->
                        <div class="relative rounded-xl mb-4 bg-black overflow-hidden aspect-video">
                            <iframe src="<?php echo $video_src; ?>" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                        <?php endif; ?>
                    <?php elseif ($image_count === 1): ?>
                        <!-- Single Image Display -->
                        <div class="rounded-xl mb-4 bg-slate-100 dark:bg-slate-700 overflow-hidden aspect-square">
                            <img src="<?php echo htmlspecialchars($post_images[0]); ?>" 
                                 class="w-full h-full object-cover" 
                                 width="1080" height="1080"
                                 <?php if ($post_counter === 0): ?>
                                     fetchpriority="high"
                                 <?php else: ?>
                                     loading="lazy"
                                 <?php endif; ?>>
                        </div>
                    <?php else: ?>
                        <!-- Multi-Image Carousel -->
                        <div class="relative rounded-xl mb-4 overflow-hidden" onclick="event.stopPropagation()">
                            <div class="carousel-container flex overflow-x-auto snap-x snap-mandatory scrollbar-hide" 
                                 id="carousel-<?php echo $post['id']; ?>"
                                 style="scroll-behavior: smooth;">
                                <?php foreach ($post_images as $idx => $img_url): ?>
                                <div class="flex-shrink-0 w-full snap-center aspect-square bg-slate-100 dark:bg-slate-700">
                                    <img src="<?php echo htmlspecialchars($img_url); ?>" 
                                         class="w-full h-full object-cover" 
                                         loading="lazy">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Navigation Arrows -->
                            <?php if ($image_count > 1): ?>
                            <button onclick="carouselPrev(<?php echo $post['id']; ?>)" 
                                    class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white text-sm transition-colors">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button onclick="carouselNext(<?php echo $post['id']; ?>)" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/50 hover:bg-black/70 rounded-full flex items-center justify-center text-white text-sm transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            
                            <!-- Dots Indicator -->
                            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                                <?php for ($i = 0; $i < $image_count; $i++): ?>
                                <div class="carousel-dot w-2 h-2 rounded-full bg-white/50 transition-colors <?php echo $i === 0 ? 'bg-white' : ''; ?>" 
                                     data-index="<?php echo $i; ?>"></div>
                                <?php endfor; ?>
                            </div>
                            
                            <!-- Counter Badge -->
                            <div class="absolute top-3 right-3 bg-black/60 text-white text-xs font-bold px-2 py-1 rounded-full">
                                <span class="carousel-current">1</span>/<?php echo $image_count; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php $post_counter++; ?>

                <!-- Actions -->
                <div class="flex gap-3 border-t border-slate-100 dark:border-slate-700 pt-4 text-slate-400 min-h-[44px] items-center pr-1">
                    <!-- Reaction System -->
                        <!-- Reactions & Comments Count Row -->
                        <?php if($post['like_count'] > 0 || $post['comment_count'] > 0): ?>
                        <div class="flex items-center justify-between py-2.5 px-1">
                            <div class="flex items-center gap-1 cursor-pointer" onclick="event.stopPropagation(); showReactionDetails(<?php echo $post['id']; ?>)">
                                <?php if($post['like_count'] > 0 && !empty($post['top_reactions'])): 
                                    $reactions_raw = explode(',', $post['top_reactions']);
                                    $counts = array_count_values($reactions_raw);
                                    arsort($counts);
                                    
                                    $r_icons = [
                                        'like' => 'fas fa-thumbs-up', 'love' => 'fas fa-heart', 
                                        'haha' => 'fas fa-laugh-squint', 'wow' => 'fas fa-surprise', 
                                        'sad' => 'fas fa-sad-tear', 'angry' => 'fas fa-angry'
                                    ];
                                    $r_bg = [
                                        'like' => 'bg-blue-500', 'love' => 'bg-red-500', 
                                        'haha' => 'bg-yellow-500', 'wow' => 'bg-yellow-500', 
                                        'sad' => 'bg-yellow-500', 'angry' => 'bg-orange-500'
                                    ];
                                ?>
                                <div class="flex -space-x-1.5 mr-1">
                                    <?php $shown = 0; foreach($counts as $type => $count): if($shown >= 3) break; ?>
                                    <div class="w-5 h-5 rounded-full border-2 border-white dark:border-slate-800 <?php echo $r_bg[$type]; ?> flex items-center justify-center">
                                        <i class="<?php echo $r_icons[$type]; ?> text-[10px] text-white"></i>
                                    </div>
                                    <?php $shown++; endforeach; ?>
                                </div>
                                <span class="text-sm text-slate-500 dark:text-slate-400 hover:underline" id="like-count-<?php echo $post['id']; ?>">
                                    <?php echo $post['like_count']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                    </div>
                    <?php endif; ?>

                        <!-- HTMX Like Button Wrapper -->
                        <div class="relative z-10 group" id="like-wrapper-<?php echo $post['id']; ?>" onclick="event.stopPropagation()">
                             <!-- Reaction Bar (Click toggle) -->
                            <div id="reaction-bar-<?php echo $post['id']; ?>" class="hidden absolute bottom-10 left-0 bg-white dark:bg-slate-800 p-2 rounded-full shadow-2xl border border-slate-100 dark:border-slate-700 gap-2 z-50 animate-in fade-in slide-in-from-bottom-2 duration-200" onclick="event.stopPropagation()">
                                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "like"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" aria-label="Like" class="hover:scale-125 transition-transform text-2xl" title="Beğen">👍</button>
                                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "love"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" aria-label="Love" class="hover:scale-125 transition-transform text-2xl" title="Bayıldım">❤️</button>
                                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "haha"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" aria-label="Haha" class="hover:scale-125 transition-transform text-2xl" title="Komik">😂</button>
                                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "wow"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" aria-label="Wow" class="hover:scale-125 transition-transform text-2xl" title="Şaşırdım">😮</button>
                                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "sad"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" aria-label="Sad" class="hover:scale-125 transition-transform text-2xl" title="Üzüldüm">😢</button>
                                <button hx-post="api/htmx/like_button.php" hx-vals='{"post_id": <?php echo $post['id']; ?>, "reaction_type": "angry"}' hx-target="#like-wrapper-<?php echo $post['id']; ?>" hx-swap="outerHTML" aria-label="Angry" class="hover:scale-125 transition-transform text-2xl" title="Kızgın">😡</button>
                            </div>

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
                                onclick="toggleReactionMenu(<?php echo $post['id']; ?>, event)"
                                id="like-btn-<?php echo $post['id']; ?>"
                                aria-label="Toggle reactions">
                                <i class="<?php echo $icon_class; ?> text-xl like-icon"></i>

                                <?php if ($post['like_count'] > 0): ?>
                                <span class="text-sm font-medium opacity-80">(<?php echo $post['like_count']; ?>)</span>
                                <?php endif; ?>
                            </button>
                        </div>
                    <button onclick="event.stopPropagation(); <?php echo $post['post_type'] == 'group' ? "location.href='group_detail?id={$post['group_id']}'" : "toggleComments({$post['id']})"; ?>" 
                            class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg py-2 px-3 transition-colors text-slate-500 dark:text-slate-400" 
                            id="comment-btn-<?php echo $post['id']; ?>"
                            aria-label="Comments">
                        <i class="far fa-comment text-xl"></i>

                    </button>

                    <!-- Save Button (Added) -->
                    <button onclick="event.stopPropagation(); toggleSave(<?php echo $post['id']; ?>, this)" 
                            class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg py-2 px-3 transition-colors text-slate-500 dark:text-slate-400 group/save"
                            aria-label="<?php echo ($post['is_saved'] ?? 0) ? ($lang == 'en' ? 'Unsave' : 'Kaydetme') : ($lang == 'en' ? 'Save' : 'Kaydet'); ?>">
                        <i class="<?php echo ($post['is_saved'] ?? 0) ? 'fas text-yellow-500' : 'far group-hover/save:text-yellow-500'; ?> fa-bookmark text-xl transition-colors"></i>

                    </button>

                    <button class="flex items-center gap-2 hover:text-green-500 transition-colors" onclick="event.stopPropagation(); openShareModal(<?php echo $post['id']; ?>)" aria-label="Share post">
                        <i class="fas fa-share"></i>
                    </button>
                    
                    <!-- Translation Button (Moved to Right) -->
                    <button onclick="event.stopPropagation(); toggleTranslation(<?php echo $post['id']; ?>)" 
                            class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-pink-500 transition-all opacity-70 hover:opacity-100 ml-auto mr-1"
                            id="trans-btn-<?php echo $post['id']; ?>" title="<?php echo $lang == 'en' ? 'See Translation' : 'Çeviriyi Gör'; ?>">
                        <i class="fas fa-language text-lg"></i>
                    </button>
                </div>

                <!-- Comments Section (Lazy Loaded) -->
    <div id="comments-section-<?php echo $post['id']; ?>" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
        
        <?php if($post['comment_count'] > 2): ?>
        <button onclick="event.stopPropagation(); toggleComments(<?php echo $post['id']; ?>)" class="text-slate-500 dark:text-slate-400 text-sm font-semibold hover:underline mb-3 ml-2">
             <?php echo $lang == 'en' ? 'View all ' . $post['comment_count'] . ' comments' : 'Tüm ' . $post['comment_count'] . ' yorumu gör'; ?>
        </button>
        <?php endif; ?>

        <!-- Preview Comments (Last 2) -->
        <?php 
        // Use batch-fetched comments (no per-post query)
        $preview_comments = $batch_comments[$post['id']] ?? [];
        

        // Wrap preview in container
        echo '<div id="preview-comments-' . $post['id'] . '">';
        foreach($preview_comments as $comment): 
        ?>
        <div class="flex gap-2 mb-2 last:mb-0">
            <a href="profile?uid=<?php echo $comment['user_id']; ?>" class="shrink-0">
                <img src="<?php echo !empty($comment['avatar']) ? $comment['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($comment['full_name']).'&background=random'; ?>" class="w-8 h-8 rounded-full object-cover">
            </a>
            <div class="flex flex-col items-start max-w-[90%]">
                 <div class="bg-slate-100 dark:bg-slate-700/50 rounded-2xl px-3 py-2 relative min-w-[120px]">
                     <div class="flex flex-col gap-0">
                         <a href="profile?uid=<?php echo $comment['user_id']; ?>" class="font-bold text-xs text-slate-900 dark:text-white hover:underline truncate leading-snug">
                             <?php echo htmlspecialchars($comment['full_name']); ?>
                         </a>
                         <div class="text-sm text-slate-800 dark:text-slate-200 break-words leading-tight">
                             <?php echo nl2br(htmlspecialchars(trim($comment['content']))); ?>
                         </div>
                     </div>
                 </div>
                 <div class="flex items-center gap-3 ml-2 mt-1">
                     <span class="text-[10px] text-slate-400 font-medium"><?php echo date('d.m H:i', strtotime($comment['created_at'])); ?></span>
                     <button class="text-[10px] font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition-colors">
                         <?php echo $lang == 'en' ? 'Like' : 'Beğen'; ?>
                     </button>
                     <button class="text-[10px] font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition-colors">
                         <?php echo $lang == 'en' ? 'Reply' : 'Yanıtla'; ?>
                     </button>
                 </div>
            </div>
        </div>
        <?php endforeach; 
        echo '</div>'; // End preview container
        ?>

        <!-- Hidden full loader for "View all" -->
        <div id="comments-loader-<?php echo $post['id']; ?>" class="hidden text-center text-slate-400 text-sm py-2">
            <i class="fas fa-spinner fa-spin mr-2"></i> Yükleniyor...
        </div>
    </div>
                <!-- Comments Section -->
                <div id="comments-list-<?php echo $post['id']; ?>" class="space-y-3 mb-4 max-h-64 overflow-y-auto hidden" onclick="event.stopPropagation()">
                    <!-- Comments will be loaded here -->
                </div>
                    
                    <!-- Comment Input -->
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <div id="comment-input-wrapper-<?php echo $post['id']; ?>" class="hidden flex gap-2" onclick="event.stopPropagation()">
                        <img src="<?php echo media_url($_SESSION['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'])); ?>" class="w-8 h-8 rounded-full flex-shrink-0" width="32" height="32">
                        <div class="flex-1 flex gap-2">
                            <input type="text" id="comment-input-<?php echo $post['id']; ?>" 
                                   class="flex-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-blue-500 transition-colors" 
                                   placeholder="Yorum yaz... (@kullaniciadi ile etiketle)" 
                                   onkeypress="if(event.key === 'Enter') postComment(<?php echo $post['id']; ?>)">
                            <button onclick="postComment(<?php echo $post['id']; ?>)" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full text-sm font-bold transition-colors">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-xs text-slate-400 text-center py-2">
                        <a href="login" class="text-blue-500 hover:underline">Yorum yapmak için giriş yapın</a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            
            <?php $post_counter++; ?>
            <?php endforeach; ?>
            <!-- HTMX Load More - Triggered when scrolled into view -->
            <div id="load-more-trigger"
                 hx-get="api/htmx/get_more_posts.php?offset=50"
                 hx-trigger="revealed"
                 hx-swap="beforebegin"
                 hx-indicator="#feed-loading"
                 class="py-4">
            </div>
            <div id="feed-loading" class="htmx-indicator py-10 text-center text-slate-400">
                <i class="fas fa-spinner fa-spin text-2xl"></i>
                <p class="text-sm mt-2"><?php echo $lang == 'en' ? 'Loading more posts...' : 'Daha fazla yükleniyor...'; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function previewFeedImage(input) {
            if (input.files && input.files[0]) {
                removeFeedVideo(); // Clear video if image selected
                var reader = new FileReader();
                reader.onload = function(e) {
                    const imgPreview = document.getElementById('image-preview');
                    const imgContainer = document.getElementById('image-preview-container');
                    if (imgPreview) imgPreview.src = e.target.result;
                    if (imgContainer) imgContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeFeedImage() {
            document.getElementById('feed-image-input').value = '';
            const imgContainer = document.getElementById('image-preview-container');
            const imgPreview = document.getElementById('image-preview');
            if (imgContainer) imgContainer.classList.add('hidden');
            if (imgPreview) imgPreview.src = '#';
        }

        function previewFeedVideo(input) {
            if (input.files && input.files[0]) {
                removeFeedImage(); // Clear image if video selected
                var reader = new FileReader();
                reader.onload = function(e) {
                    const vidPreview = document.getElementById('video-preview');
                    const vidContainer = document.getElementById('video-preview-container');
                    if (vidPreview) vidPreview.src = e.target.result;
                    if (vidContainer) vidContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeFeedVideo() {
            document.getElementById('feed-video-input').value = '';
            const vidContainer = document.getElementById('video-preview-container');
            const vidPreview = document.getElementById('video-preview');
            if (vidContainer) vidContainer.classList.add('hidden');
            if (vidPreview) vidPreview.src = '';
        }

        function toggleComments(postId) {
            const section = document.getElementById('comments-section-' + postId);
            const list = document.getElementById('comments-list-' + postId);
            const inputWrapper = document.getElementById('comment-input-wrapper-' + postId);
            const preview = document.getElementById('preview-comments-' + postId);
            
            if (section) section.classList.remove('hidden');

            if (list) {
                if (list.classList.contains('hidden')) {
                    list.classList.remove('hidden');
                    if (preview) preview.classList.add('hidden'); // Hide Preview
                    loadComments(postId);
                } else {
                    list.classList.add('hidden');
                    if (preview) preview.classList.remove('hidden'); // Show Preview
                }
            }
            
            if (inputWrapper) {
                inputWrapper.classList.toggle('hidden');
                if (!inputWrapper.classList.contains('hidden')) {
                    setTimeout(() => {
                        const input = document.getElementById('comment-input-' + postId);
                        if(input) input.focus();
                    }, 50);
                }
            }
        }

        function loadComments(postId) {
            const list = document.getElementById('comments-list-' + postId);
            
            fetch('api/post_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get&post_id=' + postId
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    list.innerHTML = '';
                    if (data.comments.length === 0) {
                        list.innerHTML = '<p class="text-xs text-slate-400 text-center py-2">Henüz yorum yok. İlk yorumu sen yap!</p>';
                    } else {
                        data.comments.forEach(comment => {
                            const html = `
                                <div class="flex gap-2">
                                    <img src="${comment.avatar}" class="w-8 h-8 rounded-full flex-shrink-0 object-cover" alt="${comment.full_name}" width="32" height="32" loading="lazy">
                                    <div class="flex-1">
                                        <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl rounded-tl-none px-3 py-2 group/comment relative">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">${comment.full_name}</span>
                                            
                                            <div id="comment-content-wrapper-${comment.id}">
                                                <p id="comment-original-${comment.id}" class="text-sm text-slate-600 dark:text-slate-400 mt-1 transition-all duration-300">${comment.content}</p>
                                                <p id="comment-translated-${comment.id}" class="hidden text-sm text-slate-600 dark:text-slate-400 mt-1 italic border-l-2 border-pink-500 pl-2 transition-all duration-300"></p>
                                            </div>

                                            <button onclick="toggleTranslationComment(${comment.id})" 
                                                    class="absolute bottom-2 right-2 text-[10px] text-slate-400 hover:text-pink-500 opacity-0 group-hover/comment:opacity-100 transition-opacity"
                                                    title="Tercüme et"
                                                    aria-label="Translate comment">
                                                <i class="fas fa-language"></i> <span id="comment-trans-text-${comment.id}"></span>
                                            </button>
                                        </div>
                                        <span class="text-[10px] text-slate-400 ml-2">${comment.date}</span>
                                    </div>
                                </div>
                            `;
                            list.insertAdjacentHTML('beforeend', html);
                        });
                    }
                }
            })
            .catch(err => {
                console.error('Yorumlar yüklenemedi:', err);
            });
        }

        async function sendReaction(postId, type) {
            event.stopPropagation(); // Prevent parent clicks
            
            const btn = document.getElementById('like-btn-' + postId);
            
            // Double-click protection
            if (btn.disabled) return;
            btn.disabled = true;
            
            const icon = btn.querySelector('i');
            const countSpan = document.getElementById('like-count-' + postId);
            
            // Map for Optimistic UI
            const icons = {
                'like': 'fas fa-thumbs-up',
                'love': 'fas fa-heart',
                'haha': 'fas fa-laugh-squint',
                'wow': 'fas fa-surprise',
                'sad': 'fas fa-sad-tear',
                'angry': 'fas fa-angry'
            };
            const colors = {
                'like': 'text-blue-500', 
                'love': 'text-red-500',
                'haha': 'text-yellow-500',
                'wow': 'text-yellow-500',
                'sad': 'text-yellow-500', 
                'angry': 'text-orange-500'
            };

            // Apply UI immediately
            btn.className = `flex items-center gap-2 transition-colors ${colors[type] || 'text-pink-500'}`;
            icon.className = `${icons[type] || 'fas fa-heart'} transform transition-transform duration-300 text-xl scale-125`;
            setTimeout(() => icon.classList.remove('scale-125'), 200);

            // Play Lottie Animation
            if (type === 'like' || type === 'love') {

            }

            // Fetch
            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('reaction_type', type);
                
                const res = await fetch('api/like_post.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    countSpan.innerText = data.count;
                }
            } catch (err) {
                console.error(err);
                alert('Ağ hatası veya sunucu yanıtı bozuk.');
            } finally {
                // Re-enable after 500ms
                setTimeout(() => { btn.disabled = false; }, 500);
            }
        }

        function postComment(postId) {
            const input = document.getElementById('comment-input-' + postId);
            const btn = input.nextElementSibling; // The submit button next to input
            const content = input.value.trim();
            
            if (!content) return;
            
            // Double-click protection
            if (btn.disabled) return;
            btn.disabled = true;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.classList.add('opacity-50', 'cursor-not-allowed');

            fetch('api/post_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add&post_id=' + postId + '&content=' + encodeURIComponent(content)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Add comment to list
                    const list = document.getElementById('comments-list-' + postId);
                    if (list.innerHTML.includes('Henüz yorum yok')) {
                        list.innerHTML = '';
                    }
                    
                    const html = `
                        <div class="flex gap-2 animate-pulse">
                            <img src="${data.comment.avatar}" class="w-8 h-8 rounded-full flex-shrink-0 object-cover" alt="${data.comment.full_name}" width="32" height="32">
                            <div class="flex-1">
                                <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl rounded-tl-none px-3 py-2 group/comment relative">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">${data.comment.full_name}</span>
                                    
                                    <div id="comment-content-wrapper-${data.comment.id}">
                                        <p id="comment-original-${data.comment.id}" class="text-sm text-slate-600 dark:text-slate-400 mt-1 transition-all duration-300">${data.comment.content}</p>
                                        <p id="comment-translated-${data.comment.id}" class="hidden text-sm text-slate-600 dark:text-slate-400 mt-1 italic border-l-2 border-pink-500 pl-2 transition-all duration-300"></p>
                                    </div>

                                    <button onclick="toggleTranslationComment(${data.comment.id})" 
                                            class="absolute bottom-2 right-2 text-[10px] text-slate-400 hover:text-pink-500 opacity-0 group-hover/comment:opacity-100 transition-opacity"
                                            title="Tercüme et"
                                            aria-label="Translate comment">
                                        <i class="fas fa-language"></i> <span id="comment-trans-text-${data.comment.id}"></span>
                                    </button>
                                </div>
                                <span class="text-[10px] text-slate-400 ml-2">${data.comment.date}</span>
                            </div>
                        </div>
                    `;
                    list.insertAdjacentHTML('beforeend', html);
                    
                    // Update comment count
                    const countEl = document.getElementById('comment-count-' + postId);
                    const currentCount = parseInt(countEl.textContent) || 0;
                    countEl.textContent = currentCount + 1;
                    
                    // Clear input
                    input.value = '';
                    
                    // Scroll to bottom
                    list.scrollTop = list.scrollHeight;
                    
                    // Brief success feedback
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    btn.classList.remove('bg-blue-500');
                    btn.classList.add('bg-green-500');
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('bg-green-500');
                        btn.classList.add('bg-blue-500');
                    }, 1000);
                } else {
                    alert(data.message || 'Yorum eklenemedi');
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(err => {
                console.error('Yorum eklenemedi:', err);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                btn.innerHTML = originalHTML;
            })
            .finally(() => {
                // Re-enable button
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }

        async function toggleTranslation(postId) {
            const originalP = document.getElementById('post-original-' + postId);
            const translatedP = document.getElementById('post-translated-' + postId);
            const btn = document.getElementById('trans-btn-' + postId);
            const btnText = document.getElementById('trans-text-' + postId); // May be null if icon-only
            
            const currentLang = '<?php echo $lang; ?>';
            
            const isShowingOriginal = !originalP.classList.contains('hidden');
            
            if (isShowingOriginal) {
                if (!translatedP.innerHTML.trim()) {
                    const originalText = originalP.innerText.trim();
                    const oldBtnText = btnText ? btnText.innerText : null;
                    const oldIcon = btn.querySelector('i');
                    if (oldIcon) oldIcon.className = 'fas fa-circle-notch fa-spin text-lg';
                    
                    try {
                        const formData = new FormData();
                        formData.append('text', originalText);
                        formData.append('target_lang', currentLang);
                        
                        const response = await fetch('api/translate.php', { method: 'POST', body: formData });
                        const data = await response.json();
                        
                        if (data.success) {
                            translatedP.innerText = data.translated_text;
                            if (oldIcon) oldIcon.className = 'fas fa-language text-lg';
                        } else {
                            alert(data.error || 'Translation failed');
                            if (btnText) btnText.innerText = oldBtnText;
                            if (oldIcon) oldIcon.className = 'fas fa-language text-lg';
                            return;
                        }
                    } catch (error) {
                        console.error(error);
                        alert('Translation error. Make sure API is running.');
                        if (btnText) btnText.innerText = oldBtnText;
                        if (oldIcon) oldIcon.className = 'fas fa-language text-lg';
                        return;
                    }
                }
                
                originalP.style.opacity = '0';
                setTimeout(() => {
                    originalP.classList.add('hidden');
                    translatedP.classList.remove('hidden');
                    void translatedP.offsetWidth; 
                    translatedP.style.opacity = '1';
                }, 300);
                
                if (btnText) btnText.innerText = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
                if (btn) btn.title = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
            } else {
                translatedP.style.opacity = '0';
                setTimeout(() => {
                    translatedP.classList.add('hidden');
                    originalP.classList.remove('hidden');
                    void originalP.offsetWidth;
                    originalP.style.opacity = '1';
                }, 300);
                
                if (btnText) btnText.innerText = currentLang === 'en' ? 'See Translation' : 'Çeviriyi Gör';
                if (btn) btn.title = currentLang === 'en' ? 'See Translation' : 'Çeviriyi Gör';
            }
        }

        async function toggleTranslationComment(commentId) {
            const originalP = document.getElementById('comment-original-' + commentId);
            const translatedP = document.getElementById('comment-translated-' + commentId);
            const btnText = document.getElementById('comment-trans-text-' + commentId);
            
            const currentLang = '<?php echo $lang; ?>';
            
            const isShowingOriginal = !originalP.classList.contains('hidden');
            
            if (isShowingOriginal) {
                if (!translatedP.innerHTML.trim()) {
                    const originalText = originalP.innerText.trim();
                    btnText.innerHTML = '...'; 
                    try {
                        const formData = new FormData();
                        formData.append('text', originalText);
                        formData.append('target_lang', currentLang);
                        const response = await fetch('api/translate.php', { method: 'POST', body: formData });
                        const data = await response.json();
                        if (data.success) {
                            translatedP.innerText = data.translated_text;
                        } else {
                            alert(data.error || 'Translation failed');
                            btnText.innerText = '';
                            return;
                        }
                    } catch (e) { 
                        console.error(e); 
                        alert('Translation error');
                        btnText.innerText = '';
                        return;
                    }
                }
                originalP.style.opacity = '0';
                setTimeout(() => {
                    originalP.classList.add('hidden');
                    translatedP.classList.remove('hidden');
                    void translatedP.offsetWidth; translatedP.style.opacity = '1';
                }, 300);
                btnText.innerText = currentLang === 'en' ? 'Original' : 'Orijinal';
            } else {
                translatedP.style.opacity = '0';
                setTimeout(() => {
                    translatedP.classList.add('hidden');
                    originalP.classList.remove('hidden');
                    void originalP.offsetWidth; originalP.style.opacity = '1';
                }, 300);
                btnText.innerText = '';
            }
        }

        /* Post Management Logic */
        function togglePostMenu(postId) {
            const menu = document.getElementById('post-menu-' + postId);
            if(menu) menu.classList.toggle('hidden');
        }

        window.onclick = function(event) {
            if (!event.target.matches('.fa-ellipsis-h') && !event.target.closest('button')) {
                const menus = document.querySelectorAll('[id^=post-menu-]:not([id*="container"])');
                menus.forEach(menu => {
                    if (!menu.classList.contains('hidden')) {
                        menu.classList.add('hidden');
                    }
                });
            }
        }

        async function confirmDeletePost(postId) {
            if(!confirm('<?php echo $lang == 'en' ? 'Are you sure you want to delete this post?' : 'Bu gönderiyi silmek istediğinizden emin misiniz?'; ?>')) return;
            
            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                const response = await fetch('api/delete_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if(data.success) {
                    const postEl = document.getElementById('post-content-wrapper-' + postId).closest('.border'); // feed.php structure uses border class on wrapper div line 171
                    // Actually line 171: bg-white ... border ...
                    // Let's use id or generic removal
                    // closest('.border') might be risky if other elements have border.
                    // But line 171 has border-slate-200.
                    // Or simpler: remove the parent div.
                    // Let's reload page as clean fallback? No, animation is requested.
                    // Let's try locating the card.
                    // In feed.php, wrapper is div id="...". 
                    // Wait, post div doesn't have ID. Line 171.
                    // I will assign ID to wrapper div first?
                    // Or use closest('.rounded-3xl') which is unique to post card here.
                    const card = document.getElementById('post-content-wrapper-' + postId).closest('.rounded-3xl');
                    if(card) {
                        card.style.transition = 'all 0.5s ease';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 500);
                    } else {
                        location.reload(); 
                    }
                } else {
                    alert(data.error || 'Delete failed');
                }
            } catch(e) { console.error(e); }
        }

        function editPost(postId) {
            togglePostMenu(postId);
            const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
            const originalP = document.getElementById('post-original-' + postId);
            const text = originalP.innerText; 
            contentWrapper.dataset.originalHtml = contentWrapper.innerHTML;
            contentWrapper.innerHTML = `
                <textarea id="edit-input-${postId}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-lg focus:outline-none focus:ring-2 focus:ring-pink-500 mb-2 resize-none transition-all dark:text-white" rows="4">${text}</textarea>
                <div class="flex gap-2 justify-end">
                    <button onclick="cancelEdit(${postId})" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>
                    <button onclick="savePost(${postId})" class="px-3 py-1.5 text-xs font-bold text-white bg-pink-500 hover:bg-pink-600 rounded-lg shadow-lg shadow-pink-500/30 transition-all">Save</button>
                </div>
            `;
            document.getElementById('edit-input-' + postId).focus();
        }

        function cancelEdit(postId) {
            const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
            if(contentWrapper.dataset.originalHtml) {
                contentWrapper.innerHTML = contentWrapper.dataset.originalHtml;
            }
        }

        async function savePost(postId) {
            const newVal = document.getElementById('edit-input-' + postId).value;
            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('content', newVal);
                const response = await fetch('api/edit_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                if(data.success) {
                    const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
                    contentWrapper.innerHTML = `
                        <p id="post-original-${postId}" class="text-slate-800 dark:text-slate-200 leading-relaxed text-lg transition-all duration-300">
                            ${newVal.replace(/\n/g, '<br>')}
                        </p>
                        <p id="post-translated-${postId}" class="hidden text-slate-800 dark:text-slate-200 leading-relaxed text-lg italic border-l-4 border-pink-500 pl-4 transition-all duration-300">
                        </p>
                    `;
                } else {
                    alert(data.error || 'Update failed');
                }
            } catch(e) { console.error(e); }
        }

        /* Save Post Logic */
        async function toggleSave(postId, btn) {
            btn.disabled = true;
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('.save-text');
            const isSaved = icon.classList.contains('fas');
            const lang = document.documentElement.lang || 'tr';

            // Optimistic Update
            if(isSaved) {
                icon.classList.replace('fas', 'far'); // Unsaving
                icon.classList.remove('text-yellow-500');
                if(textSpan) textSpan.innerText = lang === 'en' ? 'Save' : 'Kaydet';
            } else {
                icon.classList.replace('far', 'fas'); // Saving
                icon.classList.add('text-yellow-500');
                icon.classList.add('scale-125');
                setTimeout(()=>icon.classList.remove('scale-125'), 300);
                if(textSpan) textSpan.innerText = lang === 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar';
            }

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_save');
                formData.append('post_id', postId);
                
                const response = await fetch('api/save_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if(data.status === 'success') {
                    if(data.is_saved) {
                        showSavedToast(postId);
                    }
                } else {
                    // Revert on error
                    if(isSaved) {
                       icon.classList.replace('far', 'fas');
                       icon.classList.add('text-yellow-500');
                       if(textSpan) textSpan.innerText = lang === 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar';
                    } else {
                       icon.classList.replace('fas', 'far');
                       icon.classList.remove('text-yellow-500');
                       if(textSpan) textSpan.innerText = lang === 'en' ? 'Save' : 'Kaydet';
                    }
                }
            } catch(e) { console.error(e); }
            btn.disabled = false;
        }

        function showSavedToast(postId) {
            // Remove existing toast
            const existing = document.getElementById('saved-toast');
            if(existing) existing.remove();
            
            const toast = document.createElement('div');
            toast.id = 'saved-toast';
            toast.className = 'fixed bottom-24 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-4 z-[100] animate-in slide-in-from-bottom-5 fade-in duration-300 min-w-[300px] border border-slate-700/50';
            toast.innerHTML = `
                <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center shrink-0">
                    <i class="fas fa-check text-white text-lg"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-sm mb-0.5"><?php echo $lang == 'en' ? 'Saved to Private Items' : 'Kaydedilenlere Eklendi'; ?></p>
                    <button onclick="promptAddToCollection(${postId})" class="text-xs text-yellow-400 font-bold hover:underline">
                        <?php echo $lang == 'en' ? 'Add to Collection' : 'Koleksiyona Ekle'; ?>
                    </button>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-500 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
            `;
            document.body.appendChild(toast);
            
            // Auto remove after 5s
            setTimeout(() => {
                if(toast && document.body.contains(toast)) {
                    toast.classList.add('opacity-0', 'translate-y-4');
                    setTimeout(()=>toast.remove(), 300);
                }
            }, 5000);
        }
        
        async function promptAddToCollection(postId) {
             const toast = document.getElementById('saved-toast');
             if(toast) toast.remove();
             
             // Fetch collections first
             try {
                 const formData = new FormData();
                 formData.append('action', 'get_collections');
                 const res = await fetch('api/save_post.php', { method: 'POST', body: formData });
                 const data = await res.json();
                 
                 if(data.status === 'success') {
                     showCollectionModal(postId, data.collections);
                 }
             } catch(e) { console.error(e); }
        }
        
        function showCollectionModal(postId, collections) {
            let listHtml = '';
            if(collections.length > 0) {
                collections.forEach(col => {
                     listHtml += `
                        <button onclick="addToCollection(${postId}, ${col.id})" class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl flex items-center gap-3 transition-colors mb-1">
                            <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-500">
                                <i class="fas fa-folder"></i>
                            </div>
                            <span class="font-bold text-slate-700 dark:text-slate-200">${col.name}</span>
                        </button>
                     `;
                });
            } else {
                listHtml = `<p class="text-center text-slate-500 py-4 italic"><?php echo $lang == 'en' ? 'No collections found' : 'Koleksiyon bulunamadı'; ?></p>`;
            }

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200';
            modal.onclick = (e) => { if(e.target === modal) modal.remove(); };
            modal.innerHTML = `
                <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2rem] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200 relative">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="font-bold text-lg dark:text-white"><?php echo $lang == 'en' ? 'Add to Collection' : 'Koleksiyona Ekle'; ?></h3>
                         <button onclick="this.closest('.fixed').remove()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-red-500 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="p-4 max-h-[50vh] overflow-y-auto custom-scrollbar">
                        ${listHtml}
                    </div>
                    
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                        <button onclick="location.href='saved'" class="w-full py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-xl shadow-lg shadow-yellow-500/20 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-plus"></i> <?php echo $lang == 'en' ? 'New Collection' : 'Yeni Koleksiyon Oluştur'; ?>
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        async function addToCollection(postId, collectionId) {
             try {
                 const formData = new FormData();
                 formData.append('action', 'add_to_collection');
                 formData.append('post_id', postId);
                 formData.append('collection_id', collectionId);
                 
                 const res = await fetch('api/save_post.php', { method: 'POST', body: formData });
                 const data = await res.json();
                 
                 if(data.status === 'success') {
                     // Close all modals
                     document.querySelectorAll('.fixed.z-\\[110\\]').forEach(m => m.remove());
                     alert('<?php echo $lang == "en" ? "Added to collection!" : "Koleksiyona eklendi!"; ?>');
                 }
             } catch(e) { console.error(e); }
        }

        /* REACTION SYSTEM JS (Ported from index.php) */
        async function sendReaction(postId, type) {
            event.stopPropagation();
            
            // Check if user is logged in
            <?php if (!isset($_SESSION['user_id'])): ?>
            showLoginPopup();
            return;
            <?php endif; ?>
            
            const btn = document.getElementById('like-btn-' + postId);
            const icon = btn.querySelector('i');
            const countSpan = document.getElementById('like-count-' + postId);
            
            const icons = {
                'like': 'fas fa-thumbs-up',
                'love': 'fas fa-heart',
                'haha': 'fas fa-laugh-squint',
                'wow': 'fas fa-surprise',
                'sad': 'fas fa-sad-tear',
                'angry': 'fas fa-angry'
            };
            const colors = {
                'like': 'text-blue-500', 
                'love': 'text-red-500',
                'haha': 'text-yellow-500',
                'wow': 'text-yellow-500',
                'sad': 'text-yellow-500', 
                'angry': 'text-orange-500'
            };

            btn.className = `flex items-center gap-2 transition-colors text-sm ${colors[type] || 'text-pink-500'}`;
            icon.className = `${icons[type] || 'fas fa-heart'} transform transition-transform duration-300 text-xl scale-125`;
            setTimeout(() => icon.classList.remove('scale-125'), 200);

            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('reaction_type', type);
                
                const res = await fetch('api/like_post.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    if(countSpan) countSpan.innerText = data.count;
                    if (!data.reacted && !data.reaction) {
                         resetLikeUI(postId);
                    }
                }
            } catch(e) {
                console.error(e);
            }
        }

        function resetLikeUI(postId) {
            const btn = document.getElementById('like-btn-' + postId);
            const icon = btn.querySelector('i');
            btn.className = 'flex items-center gap-2 transition-colors text-sm hover:text-pink-500 text-slate-400';
            icon.className = 'far fa-heart transform transition-transform duration-300 text-xl';
        }

        /* Post Management Logic */
        function togglePostMenu(postId) {
            const menu = document.getElementById('post-menu-' + postId);
            if(menu) menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.fa-ellipsis-h') && !event.target.closest('button')) {
                const menus = document.querySelectorAll('[id^=post-menu-]:not([id*="container"])');
                menus.forEach(menu => {
                    if (!menu.classList.contains('hidden')) {
                        menu.classList.add('hidden');
                    }
                });
            }
        }

        async function confirmDeletePost(postId) {
            if(!confirm('<?php echo $lang == 'en' ? 'Are you sure you want to delete this post?' : 'Bu gönderiyi silmek istediğinizden emin misiniz?'; ?>')) return;
            
            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                const response = await fetch('api/delete_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if(data.success) {
                    // Use data-post-id attribute for reliable selection
                    const postEl = document.querySelector(`[data-post-id="${postId}"]`);
                    if (postEl) {
                        postEl.style.transition = 'all 0.4s ease';
                        postEl.style.opacity = '0';
                        postEl.style.transform = 'scale(0.95)';
                        setTimeout(() => postEl.remove(), 400);
                    }
                } else {
                    alert(data.error || 'Delete failed');
                }
            } catch(e) { console.error(e); }
        }

        function editPost(postId) {
            togglePostMenu(postId);
            const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
            const originalP = document.getElementById('post-original-' + postId);
            const text = originalP.innerText; 
            
            contentWrapper.dataset.originalHtml = contentWrapper.innerHTML;
            
            contentWrapper.innerHTML = `
                <textarea id="edit-input-${postId}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 mb-2 resize-none transition-all dark:text-white" rows="4">${text}</textarea>
                <div class="flex gap-2 justify-end">
                    <button onclick="event.stopPropagation(); cancelEdit(${postId})" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </button>
                    <button onclick="event.stopPropagation(); savePost(${postId})" class="px-3 py-1.5 text-xs font-bold text-white bg-pink-500 hover:bg-pink-600 rounded-lg shadow-lg shadow-pink-500/30 transition-all">
                        <?php echo $lang == 'en' ? 'Save Changes' : 'Kaydet'; ?>
                    </button>
                </div>
            `;
            const textarea = document.getElementById('edit-input-' + postId);
            textarea.focus();
            textarea.onclick = (e) => e.stopPropagation();
        }

        function cancelEdit(postId) {
            const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
            if(contentWrapper.dataset.originalHtml) {
                contentWrapper.innerHTML = contentWrapper.dataset.originalHtml;
            }
        }

        async function savePost(postId) {
            const newVal = document.getElementById('edit-input-' + postId).value;
            
            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('content', newVal);
                
                const response = await fetch('api/edit_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if(data.success) {
                    const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
                    contentWrapper.innerHTML = `
                        <p id="post-original-${postId}" class="text-slate-800 dark:text-slate-200 leading-relaxed text-lg transition-all duration-300">
                            ${newVal.replace(/\n/g, '<br>')}
                        </p>
                        <p id="post-translated-${postId}" class="hidden text-slate-800 dark:text-slate-200 leading-relaxed text-lg italic border-l-4 border-pink-500 pl-4 transition-all duration-300">
                        </p>
                    `;
                } else {
                    alert(data.error || 'Update failed');
                }
            } catch(e) { console.error(e); }
        }

        function toggleLike(postId, btn) {
            sendReaction(postId, 'like');
        }


         /* Infinite Scroll Logic */
        let offset = 10;
        let isLoading = false;
        const sentinel = document.getElementById('load-more-sentinel');
        const feedContainer = document.getElementById('feed-container');

        /* Mobile Reaction Menu Logic */
        function toggleReactionMenu(postId, event) {
            if(event) {
                event.stopPropagation();
                event.preventDefault(); // Prevent focus/other default actions
            }
            
            const bar = document.getElementById('reaction-bar-' + postId);
            if (bar) {
                // If currently hidden, show it and hide others
                if (bar.classList.contains('hidden')) {
                     // Hide all others first
                     document.querySelectorAll('[id^="reaction-bar-"]').forEach(el => {
                         el.classList.add('hidden');
                         el.classList.remove('flex');
                     });
                     
                     bar.classList.remove('hidden');
                     bar.classList.add('flex');
                } else {
                     // If visible, hide it
                     bar.classList.add('hidden');
                     bar.classList.remove('flex');
                }
            }
        }

        // Close reactions when clicking elsewhere
        window.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="like-wrapper-"]')) {
                 document.querySelectorAll('[id^="reaction-bar-"]').forEach(el => {
                     el.classList.add('hidden');
                     el.classList.remove('flex');
                 });
            }
        });

        /* Delete Post with Custom Modal */
        async function confirmDeletePost(postId, postType) {
            postType = postType || 'regular';
            KalkanModal.showConfirm(
                '<?php echo $lang == "en" ? "Delete Post" : "Gönderiyi Sil"; ?>',
                '<?php echo $lang == "en" ? "Are you sure you want to delete this post?" : "Bu gönderiyi silmek istediğinizden emin misiniz?"; ?>',
                async () => {
                    try {
                        const formData = new FormData();
                        formData.append('post_id', postId);
                        formData.append('post_type', postType);
                        
                        const response = await fetch('api/delete_post.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            // Find the post element and remove it with animation
                            const postElement = document.querySelector(`[data-post-id="${postId}"]`) || document.getElementById('post-' + postId);
                            if (postElement) {
                                postElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                postElement.style.opacity = '0';
                                postElement.style.transform = 'scale(0.95)';
                                setTimeout(() => postElement.remove(), 300);
                            }
                        } else {
                            KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', data.message || data.error || '<?php echo $lang == "en" ? "Error deleting post" : "Gönderi silinirken hata oluştu"; ?>');
                        }
                    } catch (error) {
                        console.error('Delete error:', error);
                        KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', '<?php echo $lang == "en" ? "Connection error" : "Bağlantı hatası"; ?>');
                    }
                }
            );
        }


        const observer = new IntersectionObserver(async (entries) => {
            if(entries[0].isIntersecting && !isLoading) {
                isLoading = true;
                
                try {
                    const response = await fetch(`api/get_feed_posts.php?offset=${offset}`);
                    const html = await response.text();
                    
                    if(html.trim()) {
                        sentinel.insertAdjacentHTML('beforebegin', html);
                        offset += 10;
                        isLoading = false;
                    } else {
                        sentinel.innerHTML = '<span class="text-sm"><?php echo $t['all_posts_loaded']; ?></span>';
                        observer.disconnect();
                    }
                } catch (error) {
                    console.error('Load more failed', error);
                    isLoading = false;
                }
            }
        }, { rootMargin: '200px' });

        if(sentinel) observer.observe(sentinel);

    </script>

    <!-- Share Modal -->
    <div id="share-modal" class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl p-6 shadow-2xl transform scale-95 transition-transform duration-300 border border-slate-100 dark:border-slate-800">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                 <h3 class="text-xl font-bold dark:text-white">Paylaş</h3>
                 <button onclick="closeShareModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                     <i class="fas fa-times"></i>
                 </button>
            </div>
            
            <!-- Options -->
            <div class="space-y-3" id="share-options">
                 <button onclick="showShareInput()" class="w-full flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-pink-50 dark:hover:bg-pink-900/20 transition-all group border border-slate-200 dark:border-slate-700 hover:border-pink-200 dark:hover:border-pink-500/30">
                     <div class="w-12 h-12 rounded-full bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-500 group-hover:scale-110 transition-transform">
                         <i class="fas fa-pen text-lg"></i>
                     </div>
                     <div class="text-left">
                        <span class="block font-bold text-slate-700 dark:text-slate-200 text-lg">Kendi Profilimde Paylaş</span>
                        <span class="text-xs text-slate-400">Takipçilerinle paylaş</span>
                     </div>
                 </button>
                 
                <button onclick="copyLink()" class="w-full flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group border border-slate-200 dark:border-slate-700 hover:border-blue-200 dark:hover:border-blue-500/30">
                     <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                         <i class="fas fa-link text-lg"></i>
                     </div>
                     <div class="text-left">
                        <span class="block font-bold text-slate-700 dark:text-slate-200 text-lg">Bağlantıyı Kopyala</span>
                        <span class="text-xs text-slate-400">Paylaşmak için linki al</span>
                     </div>
                 </button>

                 <!-- DM Share Button -->
                 <button onclick="sendViaDM()" class="w-full flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all group border border-slate-200 dark:border-slate-700 hover:border-violet-200 dark:hover:border-violet-500/30">
                     <div class="w-12 h-12 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-500 group-hover:scale-110 transition-transform">
                         <i class="fas fa-paper-plane text-lg"></i>
                     </div>
                     <div class="text-left">
                        <span class="block font-bold text-slate-700 dark:text-slate-200 text-lg">Mesaj Olarak Gönder</span>
                        <span class="text-xs text-slate-400">Özel mesaj ile arkadaşına gönder</span>
                     </div>
                 </button>
            </div>
            
            <!-- Input Layer (Hidden initially) -->
            <div id="share-input-layer" class="hidden">
                 <div class="flex items-center gap-3 mb-4 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                    <img src="<?php echo $_SESSION['avatar'] ?? 'https://ui-avatars.com/api/?name=User'; ?>" class="w-10 h-10 rounded-full">
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $_SESSION['full_name'] ?? 'User'; ?></span>
                 </div>
                 
                 <textarea id="share-caption" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 min-h-[100px] focus:outline-none focus:ring-2 focus:ring-pink-500 dark:text-white resize-none" placeholder="Bu gönderi hakkında bir şeyler yaz..."></textarea>
                 
                 <div class="flex gap-3 mt-4">
                     <button onclick="closeShareModal()" class="flex-1 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">İptal</button>
                     <button onclick="submitShare()" class="flex-1 bg-gradient-to-r from-pink-500 to-violet-600 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-pink-500/30 transition-all transform active:scale-95">
                        Paylaş
                     </button>
                 </div>
            </div>
        </div>
    </div>

    <!-- DM Modal -->
    <div id="dm-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl transform scale-95 transition-transform duration-300 border border-slate-100 dark:border-slate-800 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center shrink-0">
                 <h3 class="text-xl font-bold dark:text-white">Mesaj Olarak Gönder</h3>
                 <button onclick="closeDMModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                     <i class="fas fa-times"></i>
                 </button>
            </div>
            
            <!-- Search -->
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input type="text" oninput="searchDMUsers(this.value)" placeholder="Kişi ara..." class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl py-3 pl-10 pr-4 text-sm focus:ring-2 focus:ring-pink-500 dark:text-white">
                </div>
            </div>

            <!-- User List -->
            <div id="dm-user-list" class="flex-1 overflow-y-auto p-4 space-y-2 min-h-[200px]">
                <!-- Populated by JS -->
            </div>
            
            <!-- Footer (Message) -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 shrink-0">
                 <textarea id="dm-message-input" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 dark:text-white resize-none mb-3" rows="2"></textarea>
                 
                 <button id="dm-send-btn" onclick="submitDM()" disabled class="w-full py-3 bg-gradient-to-r from-pink-500 to-violet-600 text-white font-bold rounded-xl shadow-lg opacity-50 cursor-not-allowed transition-all">
                    Gönder
                 </button>
            </div>
        </div>
    </div>

    <div id="mention-dropdown" class="hidden fixed bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-100 dark:border-slate-700 z-[9999] max-h-60 overflow-y-auto w-64"></div>
    <script src="js/mentions.js?v=<?php echo ASSET_VERSION; ?>" defer></script>

    <script>
        let currentSharePostId = 0;

        function openShareModal(postId) {
            currentSharePostId = postId;
            const modal = document.getElementById('share-modal');
            const layer = document.getElementById('share-input-layer');
            const options = document.getElementById('share-options');
            
            // Reset
            layer.classList.add('hidden');
            options.classList.remove('hidden');
            document.getElementById('share-caption').value = '';
            
            modal.classList.remove('hidden');
            // Animation
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
                modal.querySelector('div').classList.add('scale-100');
            }, 10);
        }

        function closeShareModal() {
            const modal = document.getElementById('share-modal');
            if(modal.classList.contains('hidden')) return;
            
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.remove('scale-100');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function showShareInput() {
            document.getElementById('share-options').classList.add('hidden');
            document.getElementById('share-input-layer').classList.remove('hidden');
            document.getElementById('share-caption').focus();
        }

        async function submitShare() {
             const caption = document.getElementById('share-caption').value;
             try {
                 const formData = new FormData();
                 formData.append('post_id', currentSharePostId);
                 formData.append('content', caption);
                 
                 const res = await fetch('api/share_post.php', { method: 'POST', body: formData });
                 const data = await res.json();
                 
                 if (data.status === 'success') {
                     closeShareModal();
                     alert('Başarıyla paylaşıldı! ✨');
                     location.reload(); 
                 } else {
                     alert(data.message || 'Hata oluştu');
                 }
             } catch(e) {
                 console.error(e);
                 alert('Bir hata oluştu.');
             }
        }

        function copyLink() {
             const link = window.location.origin + '/feed.php?post_id=' + currentSharePostId;
             navigator.clipboard.writeText(link).then(() => {
                 closeShareModal();
                 alert('Bağlantı kopyalandı! 📋');
             });
        }

        async function toggleSave(postId, btn) {
            btn.disabled = true;
            const icon = btn.querySelector('i');
            const textSpan = btn.querySelector('.save-text');
            const isSaved = icon.classList.contains('fas');
            const lang = document.documentElement.lang || 'tr';
            
            // Optimistic Update
            if (isSaved) {
                // Switch to outline (unsaved)
                icon.classList.remove('fas', 'text-yellow-500');
                icon.classList.add('far');
                if(textSpan) textSpan.innerText = lang === 'en' ? 'Save' : 'Kaydet';
            } else {
                // Switch to solid (saved)
                icon.classList.remove('far');
                icon.classList.add('fas', 'text-yellow-500');
                
                // Jump Animation
                icon.style.transition = 'transform 0.2s';
                icon.style.transform = 'scale(1.4)';
                setTimeout(() => icon.style.transform = 'scale(1)', 200);
                if(textSpan) textSpan.innerText = lang === 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar';
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_save');
                formData.append('post_id', postId);
                
                const response = await fetch('api/save_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.status !== 'success') {
                    // Revert UI on error
                    if (isSaved) { 
                        icon.classList.add('fas', 'text-yellow-500');
                        icon.classList.remove('far');
                        if(textSpan) textSpan.innerText = lang === 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar';
                    } else { 
                        icon.classList.add('far');
                        icon.classList.remove('fas', 'text-yellow-500');
                        if(textSpan) textSpan.innerText = lang === 'en' ? 'Save' : 'Kaydet';
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message, 'success');
                    }
                }
            } catch (error) {
                console.error('Error toggling save:', error);
            }
            btn.disabled = false;
        }
        

        let selectedDMUser = null;

        function sendViaDM() {
             closeShareModal();
             const modal = document.getElementById('dm-modal');
             modal.classList.remove('hidden');
             // Animation
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
                modal.querySelector('div').classList.add('scale-100');
            }, 10);

             loadRecentConversations();
             
             // Prefill message
             const link = window.location.origin + '/feed.php?post_id=' + currentSharePostId;
             document.getElementById('dm-message-input').value = link;
        }

        function closeDMModal() {
            const modal = document.getElementById('dm-modal');
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.remove('scale-100');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                selectedDMUser = null;
                updateDMSendButton();
            }, 300);
        }

        async function loadRecentConversations() {
            const list = document.getElementById('dm-user-list');
            list.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin text-pink-500 text-2xl"></i></div>';
            
            try {
                const res = await fetch('api/get_conversations.php');
                const data = await res.json();
                if(data.status === 'success') {
                    renderDMUsers(data.conversations);
                }
            } catch(e) { console.error(e); }
        }

        // Auto-scroll to specific post if present in URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const postId = urlParams.get('post_id');
            if(postId) {
                // Wait slightly for layout to settle
                setTimeout(() => {
                    const post = document.querySelector(`[data-post-id="${postId}"]`);
                    if(post) {
                        post.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Highlight effect
                        post.classList.add('ring-4', 'ring-pink-500/50', 'ring-offset-4', 'transition-all', 'duration-500');
                        setTimeout(() => {
                            post.classList.remove('ring-4', 'ring-pink-500/50', 'ring-offset-4');
                        }, 2000);
                    }
                }, 500);
            }
        });

        async function searchDMUsers(query) {
            const list = document.getElementById('dm-user-list');
            if(!query) { loadRecentConversations(); return; }
            
            // list.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i></div>'; // Optional: show loading
            
            try {
                const res = await fetch('api/search_mentions.php?q=' + encodeURIComponent(query));
                const data = await res.json();
                if(data.status === 'success') {
                    renderDMUsers(data.users);
                }
            } catch(e) { console.error(e); }
        }

        function renderDMUsers(users) {
            const list = document.getElementById('dm-user-list');
            if(!users || users.length === 0) {
                list.innerHTML = '<p class="text-center text-slate-400 p-4 text-sm">Kullanıcı bulunamadı.</p>';
                return;
            }
            
            let html = '';
            users.forEach(u => {
                const isSelected = selectedDMUser === u.id;
                // Use absolute path for avatar if needed, but usually stored fully or relative
                const avatar = u.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.full_name);
                
                html += `
                    <div onclick="selectDMUser(${u.id})" class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 ${isSelected ? 'border-pink-500 bg-pink-50 dark:bg-pink-900/20 ring-1 ring-pink-500' : ''}" data-id="${u.id}">
                        <img src="${avatar}" class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm text-slate-800 dark:text-white truncate">${u.full_name}</p>
                            <p class="text-xs text-slate-400 truncate">@${u.username}</p>
                        </div>
                        ${isSelected ? '<i class="fas fa-check-circle text-pink-500 ml-auto text-xl animate-in zoom-in spin-in-90 duration-200"></i>' : '<div class="w-5 h-5 rounded-full border-2 border-slate-200 dark:border-slate-600"></div>'}
                    </div>
                `;
            });
            list.innerHTML = html;
        }

        function selectDMUser(id) {
            selectedDMUser = id;
            updateDMSendButton();
            
            // Visual Update without full re-render
            document.querySelectorAll('#dm-user-list > div').forEach(el => {
                 if(el.dataset.id == id) {
                     el.className = 'flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all border border-pink-500 bg-pink-50 dark:bg-pink-900/20 ring-1 ring-pink-500';
                     // Update icon area
                     const iconArea = el.lastElementChild;
                     iconArea.outerHTML = '<i class="fas fa-check-circle text-pink-500 ml-auto text-xl animate-in zoom-in spin-in-90 duration-200"></i>';
                 } else {
                     el.className = 'flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50';
                     const iconArea = el.lastElementChild;
                     if(iconArea.tagName === 'I') {
                        iconArea.outerHTML = '<div class="w-5 h-5 rounded-full border-2 border-slate-200 dark:border-slate-600"></div>';
                     }
                 }
            });
        }

        function updateDMSendButton() {
            const btn = document.getElementById('dm-send-btn');
            if(selectedDMUser) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        async function submitDM() {
            if(!selectedDMUser) return;
            
            const btn = document.getElementById('dm-send-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
            
            const msg = document.getElementById('dm-message-input').value;
            
            const formData = new FormData();
            formData.append('receiver_id', selectedDMUser);
            formData.append('message', msg);
            
            try {
                const res = await fetch('api/chat_send.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.status === 'success') {
                    closeDMModal();
                    alert('Mesaj başarıyla gönderildi! ✈️');
                } else {
                    alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
                }
            } catch(e) { 
                console.error(e); 
                alert('Bağlantı hatası.');
            }
            
            btn.innerHTML = 'Gönder';
            updateDMSendButton(); // Reset state check
        }
    </script>

<!-- Stylish Login Popup Modal -->
<div id="login-popup-modal" class="fixed inset-0 z-[200] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeLoginPopup()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm px-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-100 dark:border-slate-700 text-center animate-[popup_0.3s_ease-out]">
            <div class="w-20 h-20 bg-gradient-to-br from-pink-500 to-violet-500 rounded-full mx-auto mb-6 flex items-center justify-center shadow-lg shadow-pink-500/30">
                <i class="fas fa-heart text-white text-3xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2"><?php echo $lang == 'en' ? 'Join the Community!' : 'Topluluğa Katılın!'; ?></h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6 text-sm leading-relaxed">
                <?php echo $lang == 'en' ? 'Log in to like posts, leave comments, and connect with the Kalkan community.' : 'Gönderileri beğenmek, yorum yapmak ve Kalkan topluluğu ile bağlantı kurmak için giriş yapın.'; ?>
            </p>
            <div class="flex flex-col gap-3">
                <a href="login" class="w-full py-4 bg-gradient-to-r from-pink-500 to-violet-500 text-white font-bold rounded-2xl hover:shadow-lg hover:shadow-pink-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $lang == 'en' ? 'Log In' : 'Giriş Yap'; ?>
                </a>
                <a href="register" class="w-full py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-user-plus"></i>
                    <?php echo $lang == 'en' ? 'Create Account' : 'Hesap Oluştur'; ?>
                </a>
            </div>
            <button onclick="closeLoginPopup()" class="mt-6 text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                <?php echo $lang == 'en' ? 'Maybe later' : 'Sonra belki'; ?>
            </button>
        </div>
    </div>
</div>

<style>
@keyframes popup {
    from { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
    to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}
</style>

<script>
function showLoginPopup() {
    document.getElementById('login-popup-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLoginPopup() {
    document.getElementById('login-popup-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLoginPopup();
});
</script>

<!-- Poll Modal -->
<div id="poll-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-md" onclick="closePollModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-2xl border border-slate-100 dark:border-slate-700 w-full max-w-md animate-[popup_0.3s_ease-out] max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-poll text-violet-500"></i>
                    <?php echo $lang == 'en' ? 'Create Poll' : 'Anket Oluştur'; ?>
                </h3>
                <button onclick="closePollModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-times text-slate-500"></i>
                </button>
            </div>
            <form id="poll-form" onsubmit="submitPoll(event)">
                <div class="space-y-4">
                    <!-- Question -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"><?php echo $lang == 'en' ? 'Question' : 'Soru'; ?></label>
                        <input type="text" name="poll_question" id="poll-question" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-violet-500 dark:text-white" placeholder="<?php echo $lang == 'en' ? 'Ask something...' : 'Bir şey sorun...'; ?>" required>
                    </div>
                    
                    <!-- Options -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2"><?php echo $lang == 'en' ? 'Options' : 'Seçenekler'; ?></label>
                        <div id="poll-options" class="space-y-2">
                            <input type="text" name="poll_options[]" class="poll-option w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-violet-500 dark:text-white" placeholder="<?php echo $lang == 'en' ? 'Option 1' : 'Seçenek 1'; ?>" required>
                            <input type="text" name="poll_options[]" class="poll-option w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-violet-500 dark:text-white" placeholder="<?php echo $lang == 'en' ? 'Option 2' : 'Seçenek 2'; ?>" required>
                        </div>
                        <button type="button" onclick="addPollOption()" class="mt-2 text-sm font-bold text-violet-500 hover:text-violet-600 flex items-center gap-1">
                            <i class="fas fa-plus"></i> <?php echo $lang == 'en' ? 'Add option' : 'Seçenek ekle'; ?>
                        </button>
                    </div>
                </div>
                
                <button type="submit" id="poll-submit-btn" class="mt-6 w-full py-4 bg-gradient-to-r from-violet-500 to-pink-500 text-white font-bold rounded-2xl hover:shadow-lg hover:shadow-violet-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-check"></i>
                    <?php echo $lang == 'en' ? 'Create Poll' : 'Anketi Oluştur'; ?>
                </button>
            </form>
        </div>
</div>

<script>
// Poll Functions
function openPollModal() {
    <?php if(!isset($_SESSION['user_id'])): ?>
    showLoginPopup();
    return;
    <?php endif; ?>
    document.getElementById('poll-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePollModal() {
    document.getElementById('poll-modal').classList.add('hidden');
    document.body.style.overflow = '';
    // Reset form
    document.getElementById('poll-form').reset();
    const optionsContainer = document.getElementById('poll-options');
    while (optionsContainer.children.length > 2) {
        optionsContainer.removeChild(optionsContainer.lastChild);
    }
}

function addPollOption() {
    const container = document.getElementById('poll-options');
    if (container.children.length >= 4) {
        alert('<?php echo $lang == 'en' ? 'Maximum 4 options allowed' : 'Maksimum 4 seçenek ekleyebilirsiniz'; ?>');
        return;
    }
    const optionNum = container.children.length + 1;
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'poll_options[]';
    input.className = 'poll-option w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-violet-500 dark:text-white';
    input.placeholder = '<?php echo $lang == 'en' ? 'Option' : 'Seçenek'; ?> ' + optionNum;
    container.appendChild(input);
}

async function submitPoll(e) {
    e.preventDefault();
    const btn = document.getElementById('poll-submit-btn');
    const question = document.getElementById('poll-question').value;
    const options = Array.from(document.querySelectorAll('.poll-option')).map(el => el.value).filter(v => v.trim());
    
    if (!question || options.length < 2) {
        alert('<?php echo $lang == 'en' ? 'Question and at least 2 options are required' : 'Soru ve en az 2 seçenek gereklidir'; ?>');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $lang == 'en' ? 'Creating...' : 'Oluşturuluyor...'; ?>';
    
    const formData = new FormData();
    formData.append('question', question);
    options.forEach(opt => formData.append('options[]', opt));
    
    try {
        const res = await fetch('api/create_poll.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            closePollModal();
            location.reload();
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> <?php echo $lang == 'en' ? 'Create Poll' : 'Anketi Oluştur'; ?>';
        }
    } catch (err) {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> <?php echo $lang == 'en' ? 'Create Poll' : 'Anketi Oluştur'; ?>';
    }
}

async function votePoll(pollId, optionId, btn) {
    <?php if(!isset($_SESSION['user_id'])): ?>
    showLoginPopup();
    return;
    <?php endif; ?>
    
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('poll_id', pollId);
    formData.append('option_id', optionId);
    
    try {
        const res = await fetch('api/vote_poll.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            // Update UI
            const pollCard = btn.closest('.poll-card');
            const options = pollCard.querySelectorAll('.poll-option-btn');
            
            options.forEach(optBtn => {
                const optId = optBtn.dataset.optionId;
                const optData = data.options.find(o => o.id == optId);
                if (optData) {
                    const percentage = data.total_votes > 0 ? Math.round((optData.vote_count / data.total_votes) * 100) : 0;
                    optBtn.querySelector('.poll-bar').style.width = percentage + '%';
                    optBtn.querySelector('.poll-percent').textContent = percentage + '%';
                    optBtn.querySelector('.poll-votes').textContent = optData.vote_count;
                }
                if (optId == data.user_vote) {
                    optBtn.classList.add('voted');
                } else {
                    optBtn.classList.remove('voted');
                }
            });
            
            pollCard.querySelector('.poll-total').textContent = data.total_votes;
        } else {
            alert(data.message);
        }
    } catch (err) {
        console.error(err);
    }
    
    btn.disabled = false;
}

// Close poll modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePollModal();
    }
});

// Carousel Functions
function carouselNext(postId) {
    const carousel = document.getElementById('carousel-' + postId);
    if (!carousel) return;
    const slideWidth = carousel.offsetWidth;
    carousel.scrollLeft += slideWidth;
    updateCarouselIndicators(postId);
}

function carouselPrev(postId) {
    const carousel = document.getElementById('carousel-' + postId);
    if (!carousel) return;
    const slideWidth = carousel.offsetWidth;
    carousel.scrollLeft -= slideWidth;
    updateCarouselIndicators(postId);
}

function updateCarouselIndicators(postId) {
    const carousel = document.getElementById('carousel-' + postId);
    if (!carousel) return;
    
    setTimeout(() => {
        const container = carousel.closest('.relative');
        const slideWidth = carousel.offsetWidth;
        const currentIndex = Math.round(carousel.scrollLeft / slideWidth);
        
        // Update dots
        const dots = container.querySelectorAll('.carousel-dot');
        dots.forEach((dot, i) => {
            if (i === currentIndex) {
                dot.classList.add('bg-white');
                dot.classList.remove('bg-white/50');
            } else {
                dot.classList.remove('bg-white');
                dot.classList.add('bg-white/50');
            }
        });
        
        // Update counter
        const counter = container.querySelector('.carousel-current');
        if (counter) counter.textContent = currentIndex + 1;
    }, 100);
}

// Add scroll event listeners to carousels
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.carousel-container').forEach(carousel => {
        carousel.addEventListener('scroll', function() {
            const postId = this.id.replace('carousel-', '');
            updateCarouselIndicators(postId);
        });
    });
});
</script>


<!-- Reaction Details Modal -->
<div id="reactionModalOverlay" class="fixed inset-0 bg-black/60 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeReactionModal()">
    <div onclick="event.stopPropagation()" class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="reactionModalContent">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50 backdrop-blur">
            <h3 class="font-bold text-lg dark:text-white">Beğeniler</h3>
            <button onclick="closeReactionModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 hover:text-pink-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-0 max-h-[60vh] overflow-y-auto custom-scrollbar" id="reactionList">
            <!-- Content -->
        </div>
    </div>
</div>

<script>
function showReactionDetails(postId) {
    const overlay = document.getElementById('reactionModalOverlay');
    const content = document.getElementById('reactionModalContent');
    const list = document.getElementById('reactionList');
    
    // Show Modal
    overlay.classList.remove('hidden');
    setTimeout(() => {
        overlay.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);

    list.innerHTML = '<div class="flex justify-center p-8"><i class="fas fa-spinner fa-spin text-pink-500 text-2xl"></i></div>';

    fetch(`api/get_post_reactions.php?post_id=${postId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.reactions.length > 0) {
                let html = '';
                const icons = {
                    'like': '👍', 'love': '❤️', 'haha': '😂', 
                    'wow': '😮', 'sad': '😢', 'angry': '😡'
                };
                
                data.reactions.forEach(user => {
                    html += `
                        <a href="profile?uid=${user.user_id}" class="flex items-center gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-b border-slate-50 dark:border-slate-800/50 last:border-0">
                            <div class="relative">
                                <img src="${user.avatar}" class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                                <span class="absolute -bottom-1 -right-1 text-xs bg-white dark:bg-slate-800 rounded-full w-5 h-5 flex items-center justify-center shadow-sm border border-slate-100 dark:border-slate-700">
                                    ${icons[user.reaction_type] || '👍'}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white truncate flex items-center gap-1">
                                    ${user.full_name || user.username}
                                    ${user.badge === 'verified' ? '<i class="fas fa-check-circle text-blue-500 text-xs"></i>' : ''}
                                </h4>
                                <p class="text-xs text-slate-500 truncate">@${user.username}</p>
                            </div>
                        </a>
                    `;
                });
                list.innerHTML = html;
            } else {
                list.innerHTML = '<div class="text-center p-8 text-slate-500 dark:text-slate-400">Henüz beğeni yok.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            list.innerHTML = '<div class="text-center p-8 text-red-500">Bir hata oluştu.</div>';
        });
}

function closeReactionModal() {
    const overlay = document.getElementById('reactionModalOverlay');
    const content = document.getElementById('reactionModalContent');
    
    overlay.classList.add('opacity-0');
    content.classList.add('scale-95', 'opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
}
</script>

<!-- Report Modal -->
<div id="reportModalOverlay" class="fixed inset-0 bg-black/60 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeReportModal()">
    <div onclick="event.stopPropagation()" class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="reportModalContent">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-red-50 dark:bg-red-900/20">
            <h3 class="font-bold text-lg text-red-600 dark:text-red-400 flex items-center gap-2">
                <i class="fas fa-flag"></i> <?php echo $lang == 'en' ? 'Report Content' : 'İçerik Şikayet Et'; ?>
            </h3>
            <button onclick="closeReportModal()" class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-red-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="reportForm" class="p-5">
            <input type="hidden" name="post_id" id="report_post_id">
            <input type="hidden" name="user_id" id="report_user_id">
            <input type="hidden" name="type" id="report_type">
            
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                <?php echo $lang == 'en' ? 'Why are you reporting this content?' : 'Bu içeriği neden şikayet ediyorsunuz?'; ?>
            </p>
            
            <div class="space-y-2 mb-4">
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-red-300 dark:hover:border-red-500/50 cursor-pointer transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                    <input type="radio" name="reason" value="spam" class="accent-red-500" required>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Spam' : 'Spam / İstenmeyen İçerik'; ?></span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-red-300 dark:hover:border-red-500/50 cursor-pointer transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                    <input type="radio" name="reason" value="harassment" class="accent-red-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Harassment or Bullying' : 'Taciz veya Zorbalık'; ?></span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-red-300 dark:hover:border-red-500/50 cursor-pointer transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                    <input type="radio" name="reason" value="hate_speech" class="accent-red-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Hate Speech' : 'Nefret Söylemi'; ?></span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-red-300 dark:hover:border-red-500/50 cursor-pointer transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                    <input type="radio" name="reason" value="nudity" class="accent-red-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Nudity or Sexual Content' : 'Müstehcen İçerik'; ?></span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-red-300 dark:hover:border-red-500/50 cursor-pointer transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                    <input type="radio" name="reason" value="misinformation" class="accent-red-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Misinformation' : 'Yanlış Bilgi'; ?></span>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-red-300 dark:hover:border-red-500/50 cursor-pointer transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20">
                    <input type="radio" name="reason" value="other" class="accent-red-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?></span>
                </label>
            </div>
            
            <textarea name="description" placeholder="<?php echo $lang == 'en' ? 'Additional details (optional)' : 'Ek açıklama (opsiyonel)'; ?>" 
                      class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm resize-none h-20 focus:outline-none focus:border-red-300 dark:focus:border-red-500"></textarea>
            
            <button type="submit" class="w-full mt-4 py-3 rounded-xl bg-red-500 hover:bg-red-600 text-white font-bold transition-colors flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> <?php echo $lang == 'en' ? 'Submit Report' : 'Şikayeti Gönder'; ?>
            </button>
        </form>
    </div>
</div>

<script>
let currentReportId = null;
let currentReportType = null;

function openReportModal(id, type) {
    currentReportId = id;
    currentReportType = type;
    
    const overlay = document.getElementById('reportModalOverlay');
    const content = document.getElementById('reportModalContent');
    
    // Reset form
    document.getElementById('reportForm').reset();
    document.getElementById('report_post_id').value = type === 'post' ? id : '';
    document.getElementById('report_user_id').value = type === 'user' ? id : '';
    document.getElementById('report_type').value = type;
    
    // Show modal
    overlay.classList.remove('hidden');
    setTimeout(() => {
        overlay.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Close post menu if open
    document.querySelectorAll('[id^="post-menu-"]').forEach(m => m.classList.add('hidden'));
}

function closeReportModal() {
    const overlay = document.getElementById('reportModalOverlay');
    const content = document.getElementById('reportModalContent');
    
    overlay.classList.add('opacity-0');
    content.classList.add('scale-95', 'opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
}

document.getElementById('reportForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    try {
        const res = await fetch('api/report.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            closeReportModal();
            alert(data.message);
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (err) {
        console.error(err);
        alert('Bir hata oluştu');
    }
    
    btn.disabled = false;
    btn.innerHTML = originalText;
});
</script>

<!-- Image Editor Initialization -->
<script>
let imageEditor = null;
let editingFileIndex = null;
let editedFiles = {}; // Store edited blobs by index

// ImageEditor init (lazy loaded with Cropper.js)
function ensureImageEditor(callback) {
    if (imageEditor) { if (callback) callback(); return; }
    function doInit() {
        imageEditor = new ImageEditor({
            lang: '<?php echo $lang; ?>',
            onSave: function(blob) {
                if (editingFileIndex !== null) {
                    editedFiles[editingFileIndex] = blob;
                    var previewContainer = document.getElementById('multi-image-previews');
                    if (previewContainer) {
                        var imgElements = previewContainer.querySelectorAll('img');
                        if (imgElements[editingFileIndex]) {
                            imgElements[editingFileIndex].src = URL.createObjectURL(blob);
                        }
                    }
                    editingFileIndex = null;
                }
            }
        });
        if (callback) callback();
    }
    if (typeof ImageEditor !== 'undefined') { doInit(); }
    else if (typeof loadCropperLazy === 'function') { loadCropperLazy(doInit); }
}

function openImageEditor(index) {
    // Use the selectedFiles array from the existing preview system
    if (typeof selectedFiles !== 'undefined' && selectedFiles[index]) {
        editingFileIndex = index;
        ensureImageEditor(function() { imageEditor.open(selectedFiles[index]); });
        
        // Override onSave to update selectedFiles and grid
        imageEditor.onSave = function(blob) {
            if (editingFileIndex !== null) {
                // Replace file in selectedFiles with edited blob
                const newFile = new File([blob], `edited_${editingFileIndex}.jpg`, { type: 'image/jpeg' });
                selectedFiles[editingFileIndex] = newFile;
                editedFiles[editingFileIndex] = blob;
                
                // Update preview in grid
                const grid = document.getElementById('image-preview-grid');
                if (grid) {
                    const wrappers = grid.querySelectorAll('[data-file-index]');
                    wrappers.forEach(wrapper => {
                        if (parseInt(wrapper.dataset.fileIndex) === editingFileIndex) {
                            const img = wrapper.querySelector('img');
                            if (img) img.src = URL.createObjectURL(blob);
                        }
                    });
                }
                editingFileIndex = null;
            }
        };
    }
}
</script>

<!-- Instagram-Style Video Player Script -->
<script>
// Video autoplay when visible using Intersection Observer
document.addEventListener('DOMContentLoaded', function() {
    const videos = document.querySelectorAll('.insta-video');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const video = entry.target;
            if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                video.play().catch(() => {});
            } else {
                video.pause();
            }
        });
    }, {
        threshold: [0.6]
    });
    
    videos.forEach(video => {
        observer.observe(video);
        
        // Update progress bar
        video.addEventListener('timeupdate', function() {
            const postId = video.dataset.postId;
            const progress = document.getElementById('progress-' + postId);
            if (progress && video.duration) {
                const percent = (video.currentTime / video.duration) * 100;
                progress.style.width = percent + '%';
            }
        });
    });
});

function toggleVideoPlay(postId) {
    const video = document.getElementById('video-' + postId);
    const playIcon = document.getElementById('play-icon-' + postId);
    
    if (video.paused) {
        video.play();
        playIcon.style.opacity = '0';
    } else {
        video.pause();
        playIcon.style.opacity = '1';
        playIcon.querySelector('i').className = 'fas fa-play text-white text-2xl ml-1';
    }
}

function toggleVideoMute(postId) {
    const video = document.getElementById('video-' + postId);
    const muteIcon = document.getElementById('mute-icon-' + postId);
    
    video.muted = !video.muted;
    
    if (video.muted) {
        muteIcon.className = 'fas fa-volume-mute';
    } else {
        muteIcon.className = 'fas fa-volume-up';
    }
}

function seekVideo(postId, event) {
    const video = document.getElementById('video-' + postId);
    const progressBar = event.currentTarget;
    const rect = progressBar.getBoundingClientRect();
    
    // Get click/touch position
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clickX = clientX - rect.left;
    const percent = Math.max(0, Math.min(1, clickX / rect.width));
    
    // Set video time
    if (video.duration) {
        video.currentTime = percent * video.duration;
    }
}

// Feed Composer Toggles
function toggleFeedVideoUrl() {
    const section = document.getElementById('feed-video-url-section');
    section.classList.toggle('hidden');
    if(!section.classList.contains('hidden')) document.getElementById('feed-video-url-input').focus();
    else document.getElementById('feed-video-url-input').value = '';
}

function toggleFeedLink() {
    const section = document.getElementById('feed-link-section');
    section.classList.toggle('hidden');
    if(!section.classList.contains('hidden')) document.getElementById('feed-link-input').focus();
    else document.getElementById('feed-link-input').value = '';
}

// Bind buttons to functions
document.addEventListener('DOMContentLoaded', function() {
    const videoUrlBtn = document.getElementById('video-url-btn');
    const linkBtn = document.getElementById('link-btn');
    const locationBtn = document.getElementById('location-btn');
    
    if(videoUrlBtn) videoUrlBtn.addEventListener('click', toggleFeedVideoUrl);
    if(linkBtn) linkBtn.addEventListener('click', toggleFeedLink);
    if(locationBtn) locationBtn.addEventListener('click', function() {
        document.getElementById('feed-location-section').classList.toggle('hidden');
    });
});

// Feed Feeling Functions
function toggleFeedFeelingMenu() {
    const menu = document.getElementById('feed-feeling-menu');
    menu.classList.toggle('hidden');
}

function setFeedFeeling(action, value, icon, text, actionText) {
    // Hide menu
    document.getElementById('feed-feeling-menu').classList.add('hidden');
    
    // Set hidden inputs
    document.getElementById('meta-feeling-action').value = action;
    document.getElementById('meta-feeling-value').value = value;
    
    // Show display
    const display = document.getElementById('feed-feeling-display');
    display.classList.remove('hidden');
    
    document.getElementById('feed-feeling-icon').textContent = icon;
    document.getElementById('feed-feeling-text').textContent = text;
}

function clearFeedFeeling() {
    document.getElementById('meta-feeling-action').value = '';
    document.getElementById('meta-feeling-value').value = '';
    
    document.getElementById('feed-feeling-display').classList.add('hidden');
    document.getElementById('feed-feeling-icon').textContent = '';
    document.getElementById('feed-feeling-text').textContent = '';
}

// Yukarı çık butonu
(function() {
    const btn = document.getElementById('scroll-to-top');
    if (!btn) return;
    window.addEventListener('scroll', function() {
        if (window.scrollY > 400) {
            btn.classList.remove('opacity-0', 'pointer-events-none');
        } else {
            btn.classList.add('opacity-0', 'pointer-events-none');
        }
    });
    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
</script>

</body>
</html>
