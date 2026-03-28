<?php
require_once 'includes/bootstrap.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/ui_components.php';
require_once 'includes/friendship-helper.php';
require_once 'includes/hashtag_helper.php';
require_once 'includes/icon_helper.php';

// Guest access allowed for profile viewing (if viewing others)
$user_id = $_SESSION['user_id'] ?? null;

$view_user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['uid']) ? (int)$_GET['uid'] : null);

// If username is provided, lookup ID
if (!$view_user_id && isset($_GET['username'])) {
    $username = trim($_GET['username']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $view_user_id = $stmt->fetchColumn();
}

// Fallback to current user if nothing specified
if (!$view_user_id) {
    if (isset($_SESSION['user_id'])) {
        // Redirect to shareable URL
        header("Location: profile?uid=" . $_SESSION['user_id']);
        exit();
    }
}

// If no user to view (Guest trying to view own profile), redirect to login
if (!$view_user_id) {
    header("Location: login");
    exit();
}

// Get User
// Fetch expert_badge explicitly to be sure
$stmt = $pdo->prepare("SELECT u.*, 
    (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as all_badges,
    u.expert_badge
    FROM users u WHERE u.id = ?");
$stmt->execute([$view_user_id]);
$profile_user = $stmt->fetch();

// Parse badges logic to array
$badges = [];
if (!empty($profile_user['badge'])) $badges[] = $profile_user['badge']; // main role
if (!empty($profile_user['all_badges'])) {
    $badges = array_merge($badges, explode(',', $profile_user['all_badges']));
}
$badges = array_unique($badges);

// Headers to prevent browser caching of private profiles
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Actions
$is_own_profile = ($user_id !== null && $user_id == $view_user_id);
$is_business = ($profile_user['role'] == 'venue' || in_array($profile_user['badge'] ?? '', ['business', 'verified_business', 'vip_business']));

// Check for blocks/mutes
$is_muted = false;
$is_blocked = false;
$blocked_me = false;

if ($user_id && !$is_own_profile) {
    try {
        $f_stmt = $pdo->prepare("SELECT filter_type FROM user_filters WHERE user_id = ? AND target_id = ?");
        $f_stmt->execute([$user_id, $view_user_id]);
        $filters = $f_stmt->fetchAll(PDO::FETCH_COLUMN);
        $is_muted = in_array('mute', $filters);
        $is_blocked = in_array('block', $filters);
        
        $b_stmt = $pdo->prepare("SELECT 1 FROM user_filters WHERE user_id = ? AND target_id = ? AND filter_type = 'block'");
        $b_stmt->execute([$view_user_id, $user_id]);
        $blocked_me = (bool)$b_stmt->fetchColumn();
    } catch (PDOException $e) {
        // Table might not exist yet, allow viewing but log if needed
    }
}

// Redirect if blocked
if ($is_blocked || $blocked_me) {
    // We can either redirect to home with error or show a localized "Blocked" state
    // For now, let's keep the user on the page but hide contents if blocked
}

// Get Posts (Timeline) - includes own posts AND wall posts from others
// Check if wall_user_id column exists
$wall_column_exists = false;
try {
    $check = $pdo->query("SHOW COLUMNS FROM posts LIKE 'wall_user_id'");
    $wall_column_exists = $check->rowCount() > 0;
} catch (Exception $e) {}

if ($wall_column_exists) {
    $p_stmt = $pdo->prepare("
        SELECT DISTINCT p.*, 
               u.username, u.full_name, u.avatar, u.badge, u.last_seen,
               p.wall_user_id,
               wu.full_name as wall_owner_name,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
               (SELECT reaction_type FROM post_likes WHERE post_id = p.id AND user_id = ?) as my_reaction
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN users wu ON p.wall_user_id = wu.id
        WHERE (p.user_id = ? OR p.wall_user_id = ?)
        AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
    ");
    $p_stmt->execute([$user_id, $view_user_id, $view_user_id]);
} else {
    $p_stmt = $pdo->prepare("
        SELECT DISTINCT p.*, u.username, u.full_name, u.avatar, u.badge, u.last_seen,
        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
        (SELECT reaction_type FROM post_likes WHERE post_id = p.id AND user_id = ?) as my_reaction
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.user_id = ?
        AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
    ");
    $p_stmt->execute([$user_id, $view_user_id]);
}
$posts = $p_stmt->fetchAll();


// Get Events (If Business)
$user_events = [];
if ($is_business) {
    $e_stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? AND event_date >= CURDATE() ORDER BY event_date ASC");
    $e_stmt->execute([$view_user_id]);
    $user_events = $e_stmt->fetchAll();
}

// Get Liked Events
$l_stmt = $pdo->prepare("SELECT e.*, u.venue_name FROM likes l 
        JOIN events e ON l.event_id = e.id 
        JOIN users u ON e.user_id = u.id 
        WHERE l.user_id = ? ORDER BY l.created_at DESC");
$l_stmt->execute([$view_user_id]);
$likes = $l_stmt->fetchAll();

// Get User Badges
$badges = [];
try {
    $b_stmt = $pdo->prepare("SELECT badge_type FROM user_badges WHERE user_id = ?");
    $b_stmt->execute([$view_user_id]);
    $badges = $b_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get Friendship Status
$friendship_status = 'none';
$mutual_count = 0;
$mutual_friends = [];
if (!$is_own_profile) {
    try {
        $friendship_status = getFriendshipStatus($user_id, $view_user_id);
        $mutual_count = getMutualFriendsCount($user_id, $view_user_id);
        if ($mutual_count > 0) {
            $mutual_friends = getMutualFriends($user_id, $view_user_id, 3);
        }
    } catch (PDOException $e) {
        // Friendship table might not exist yet
    }
}

// Get Friends List
$friends_list = [];
try {
    $friends_list = getFriendsList($view_user_id, 50);
} catch (PDOException $e) {
    // Friendship table might not exist yet
}

// Count friends and posts
$friends_count = count($friends_list);
$posts_count = count($posts);

// Badge Definitions (Visuals)
$badge_defs = [
    'foodie' => ['icon' => 'foodie', 'color' => 'text-orange-500', 'bg' => 'bg-orange-500/10'],
    'place_scout' => ['icon' => 'place_scout', 'color' => 'text-teal-500', 'bg' => 'bg-teal-500/10'],
    'explorer' => ['icon' => 'location', 'color' => 'text-green-500', 'bg' => 'bg-green-500/10'],
    'photographer' => ['icon' => 'camera', 'color' => 'text-blue-500', 'bg' => 'bg-blue-500/10'],
    'historian' => ['icon' => 'library', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-600/10'],
    'local' => ['icon' => 'certificate', 'color' => 'text-violet-600', 'bg' => 'bg-violet-600/10'],
];

// Contact visibility: show if own profile, or public, or (friends and viewer is friend)
$phone_vis = $profile_user['phone_visibility'] ?? 'private';
$email_vis = $profile_user['email_visibility'] ?? 'private';
$is_friend = ($user_id && $friendship_status === 'friends');
$can_see_phone = !empty($profile_user['phone']) && ($is_own_profile || $phone_vis === 'public' || ($phone_vis === 'friends' && $is_friend));
$can_see_email = !empty($profile_user['email']) && ($is_own_profile || $email_vis === 'public' || ($email_vis === 'friends' && $is_friend));
?>
<!DOCTYPE html>
<html lang="tr" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/seo_tags.php'; ?>
    <?php include 'includes/header_css.php'; ?>
    
    <!-- Custom Modal System -->
    <script src="js/modal.js"></script>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-500 selection:bg-[#0055FF] selection:text-white overflow-x-hidden">

    <!-- Premium Background -->
    <!-- Light Mode -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <!-- Dark Mode -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>


    <!-- Swup Main Content Wrapper -->
    <div id="swup-main" class="transition-main">
    
    <!-- Modern Profile Header -->
    <?php 
    $cover_position = $profile_user['cover_position'] ?? '50 50';
    $pos_parts = explode(' ', $cover_position);
    $pos_x = $pos_parts[0] ?? 50;
    $pos_y = $pos_parts[1] ?? 50;
    $has_cover = !empty($profile_user['cover_photo']);
    ?>
    
    <!-- Profile Cover -->
    <div class="relative w-full" id="cover-container">
        <!-- Cover Image - compact height with rounded bottom -->
        <div class="h-40 relative overflow-hidden rounded-b-[2rem]">
            <?php if($has_cover): ?>
                <?php $default_cover = "https://picsum.photos/seed/user_" . ($profile_user['id'] ?? '') . "/1200/400"; ?>
                <img src="<?php echo htmlspecialchars(media_url($profile_user['cover_photo'])); ?>" 
                     class="w-full h-full object-cover" 
                     id="cover-image-display"
                     style="object-position: <?php echo $pos_x; ?>% <?php echo $pos_y; ?>%"
                     loading="eager"
                     alt="<?php echo htmlspecialchars($profile_user['full_name']); ?> - <?php echo $lang == 'en' ? 'Cover' : 'Kapak'; ?>"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <img src="<?php echo $default_cover; ?>" class="w-full h-full object-cover" style="display:none;" alt="Default Cover">
            <?php else: ?>
                <?php 
                // Random WordPress-style cover photo based on user ID seeded
                $seed = 'user_' . $profile_user['id'];
                $random_cover = "https://picsum.photos/seed/{$seed}/1200/400";
                ?>
                <img src="<?php echo $random_cover; ?>" 
                     class="w-full h-full object-cover" 
                     id="cover-image-display"
                     alt="Default Cover">
            <?php endif; ?>
            
            <!-- Top Actions -->
            <div class="absolute top-4 left-4 z-30">
                <a href="feed" class="w-11 h-11 rounded-full flex items-center justify-center transition-all shadow-lg hover:scale-110 <?php echo $has_cover ? 'bg-black/50 backdrop-blur-md text-white hover:bg-black/70' : 'bg-white dark:bg-slate-800 text-[#0055FF] border-2 border-slate-200 dark:border-slate-700'; ?>">
                    <i class="fas fa-arrow-left text-base"></i>
                </a>
            </div>

            <div class="absolute top-4 right-4 flex gap-3 z-30">
                <button onclick="shareProfile()" class="w-11 h-11 rounded-full bg-black/50 backdrop-blur-md text-white flex items-center justify-center shadow-lg hover:bg-black/70 hover:scale-110 transition-all">
                    <i class="fas fa-share-alt text-base"></i>
                </button>
                <?php if ($is_own_profile && $has_cover): ?>
                <button onclick="openPositionModal('cover')" class="w-11 h-11 rounded-full bg-black/50 backdrop-blur-md text-white flex items-center justify-center shadow-lg hover:bg-black/70 hover:scale-110 transition-all">
                    <i class="fas fa-crop-alt text-base"></i>
                </button>
                <?php endif; ?>
                <?php if ($is_own_profile): ?>
                <button onclick="openSettingsModal()" class="w-11 h-11 rounded-full bg-black/50 backdrop-blur-md text-white flex items-center justify-center shadow-lg hover:bg-black/70 hover:scale-110 transition-all">
                    <i class="fas fa-cog text-base"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        $avatar_position = $profile_user['avatar_position'] ?? '50 50';
        $avatar_pos_parts = explode(' ', $avatar_position);
        $avatar_pos_x = $avatar_pos_parts[0] ?? 50;
        $avatar_pos_y = $avatar_pos_parts[1] ?? 50;
        
        $user_status = getUserStatus($profile_user['last_seen'] ?? null);
        $is_online = ($user_status === 'online');
        
        // Badge icons removed in favor of getBadgeHTML()
        ?>
        
        <!-- Avatar centered at bottom of cover -->
        <div class="absolute -bottom-10 left-1/2 -translate-x-1/2 z-20" id="avatar-container">
            <div class="relative">
                <div class="w-24 h-24 rounded-full border-4 border-white dark:border-slate-900 overflow-hidden bg-white shadow-xl">
                    <img src="<?php echo htmlspecialchars(media_url($profile_user['avatar'])); ?>" 
                         alt="<?php echo htmlspecialchars($profile_user['full_name']); ?> - <?php echo $lang == 'en' ? 'Profile photo' : 'Profil fotoğrafı'; ?>"
                         id="avatar-image"
                         class="w-full h-full object-cover"
                         style="object-position: <?php echo $avatar_pos_x; ?>% <?php echo $avatar_pos_y; ?>%">
                </div>
                <span class="absolute bottom-0 right-0 w-5 h-5 rounded-full border-2 border-white dark:border-slate-900 shadow <?php echo $is_online ? 'bg-emerald-500' : 'bg-slate-400'; ?>" title="<?php echo $is_online ? ($lang == 'en' ? 'Online' : 'Çevrimiçi') : ($lang == 'en' ? 'Offline' : 'Çevrimdışı'); ?>">
                    <?php if ($is_online): ?><span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-75"></span><?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Profile Info Section -->
    <div class="bg-white dark:bg-slate-900 pt-20 px-6 text-center">
        
        <!-- Name -->
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center justify-center flex-wrap gap-2 <?php echo ($profile_user['status'] ?? '') === 'banned' ? 'line-through decoration-red-500 decoration-4 text-slate-400' : ''; ?>">
            <?php echo htmlspecialchars($profile_user['full_name']); ?>
            <?php if (($profile_user['status'] ?? '') === 'banned'): ?>
                <span class="inline-block px-3 py-1 text-xs font-black rounded-md bg-black text-white no-underline shadow-lg">
                    🚫 BANNED
                </span>
            <?php else: ?>
                <?php echo getBadgeHTML($profile_user['badge']); ?>
            <?php endif; ?>
            <?php 
            if (!empty($badges)) {
                foreach ($badges as $expert_badge) {
                    echo getBadgeHTML($expert_badge);
                }
            }
            ?>
        </h1>
        <p class="text-slate-500 dark:text-slate-400 font-medium mt-1">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
        <?php
        $last_active_text = getLastActiveText($profile_user['last_seen'] ?? null, $t);
        if ($last_active_text !== ''):
        ?>
        <p class="text-xs text-slate-400 mt-0.5"><?php echo $t['last_active']; ?>: <?php echo htmlspecialchars($last_active_text); ?></p>
        <?php endif; ?>
        
        <!-- Stats Row -->
        <div class="flex justify-center items-center gap-4 sm:gap-6 mt-4 mb-4">
            <div class="text-center px-3 sm:px-4">
                <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo number_format($friends_count); ?></p>
                <p class="text-xs text-slate-400"><?php echo $t['friends_tab']; ?></p>
            </div>
            <div class="w-px h-10 bg-slate-200 dark:bg-slate-700"></div>
            <div class="text-center px-3 sm:px-4">
                <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo number_format($posts_count); ?></p>
                <p class="text-xs text-slate-400"><?php echo $lang == 'en' ? 'Posts' : 'Gönderi'; ?></p>
            </div>
        </div>


            <!-- Action Buttons -->
            <div class="flex justify-center gap-3">
                <?php if ($is_own_profile): ?>
                    <!-- Own Profile Actions -->
                    <a href="edit_profile" style="background-color: #0055FF;" class="flex-1 max-w-[200px] flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-white font-bold text-sm shadow-lg shadow-[#0055FF]/30 hover:shadow-[#0055FF]/50 transition-all active:scale-95">
                        <?php echo heroicon('pencil_square', 'w-5 h-5'); ?>
                        <?php echo $t['edit_profile']; ?>
                    </a>
                    
                    <?php if ($is_business): ?>
                    <a href="business_analytics" class="flex-1 max-w-[200px] flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-slate-700 text-slate-700 dark:text-white font-bold text-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all active:scale-95">
                        <i class="fas fa-chart-line"></i>
                        <?php echo $lang == 'en' ? 'Insights' : 'İstatistikler'; ?>
                    </a>
                    <?php else: ?>
                    <a href="saved" class="flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-slate-700 text-slate-700 dark:text-white font-bold text-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all active:scale-95">
                        <i class="fas fa-bookmark"></i>
                        <?php echo $lang == 'en' ? 'Saved' : 'Kaydedilenler'; ?>
                    </a>
                    <?php endif; ?>
                    
                <?php elseif ($user_id): ?>
                    <!-- Other User Actions -->
                    <div class="flex gap-3 w-full justify-center" id="friendship-actions">
                        <?php if ($friendship_status == 'friends'): ?>
                            <div class="relative">
                                <button onclick="toggleFriendDropdown()" id="friend-dropdown-btn" class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-white font-bold text-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all">
                                    <?php echo heroicon('user_check', 'w-5 h-5 text-emerald-500'); ?>
                                    <?php echo $t['friendship_friend']; ?>
                                    <?php echo heroicon('chevron_down', 'w-4 h-4 ml-1 opacity-50'); ?>
                                </button>
                                <div id="friend-dropdown" class="hidden absolute left-0 top-full mt-2 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-50">
                                    <button onclick="blockUser(<?php echo $view_user_id; ?>)" class="w-full text-left px-4 py-3 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                        <i class="fas fa-ban text-red-500 w-4"></i>
                                        <span><?php echo $lang == 'en' ? 'Block' : 'Engelle'; ?></span>
                                    </button>
                                    <button onclick="muteUser(<?php echo $view_user_id; ?>)" class="w-full text-left px-4 py-3 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors border-t border-slate-100 dark:border-slate-700">
                                        <i class="fas fa-volume-mute text-orange-500 w-4"></i>
                                        <span><?php echo $lang == 'en' ? 'Mute' : 'Sessize Al'; ?></span>
                                    </button>
                                    <button onclick="unfriend(<?php echo $view_user_id; ?>)" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 flex items-center gap-3 transition-colors border-t border-slate-100 dark:border-slate-700">
                                        <?php echo heroicon('user_minus', 'w-4 h-4'); ?>
                                        <span><?php echo $t['friendship_unfriend']; ?></span>
                                    </button>
                                </div>
                            </div>
                            
                            <a href="messages?uid=<?php echo $view_user_id; ?>" style="background-color: #0055FF;" class="flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-white font-bold text-sm shadow-lg shadow-[#0055FF]/30 hover:shadow-[#0055FF]/50 transition-all active:scale-95">
                                <?php echo heroicon('chat_bubble_left_right', 'w-5 h-5'); ?>
                                <?php echo $t['messages']; ?>
                            </a>
                            
                        <?php elseif ($friendship_status == 'pending_sent'): ?>
                            <button disabled class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-400 font-bold text-sm flex items-center gap-2 cursor-not-allowed">
                                <?php echo heroicon('clock', 'w-5 h-5'); ?> <?php echo $t['friendship_request_sent']; ?>
                            </button>
                            
                        <?php elseif ($friendship_status == 'pending_received'): ?>
                            <button onclick="respondToRequest(<?php echo $view_user_id; ?>, 'accept')" class="flex-1 max-w-[150px] px-4 py-3 rounded-2xl bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-500/30 active:scale-95">
                                <?php echo $t['friendship_accept']; ?>
                            </button>
                            <button onclick="respondToRequest(<?php echo $view_user_id; ?>, 'decline')" class="flex-1 max-w-[150px] px-4 py-3 rounded-2xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-sm active:scale-95">
                                <?php echo $t['friendship_decline']; ?>
                            </button>
                            
                        <?php else: ?>
                            <button onclick="sendFriendRequest(<?php echo $view_user_id; ?>)" style="background-color: #0055FF;" class="flex-1 max-w-[250px] px-6 py-3 rounded-2xl text-white font-bold text-sm shadow-lg shadow-[#0055FF]/30 hover:shadow-[#0055FF]/50 active:scale-95 transition-all flex items-center justify-center gap-2">
                                <?php echo heroicon('user_plus', 'w-5 h-5'); ?>
                                <?php echo $t['friendship_add_friend']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <a href="login" style="background-color: #0055FF;" class="flex-1 max-w-[250px] px-6 py-3 rounded-2xl text-white font-bold text-sm shadow-lg shadow-[#0055FF]/30 hover:shadow-[#0055FF]/50 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <?php echo heroicon('user_plus', 'w-5 h-5'); ?>
                        <?php echo $t['friendship_add_friend']; ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mutual Friends -->
            <?php if ($user_id && !$is_own_profile && $mutual_count > 0): ?>
            <div class="flex items-center justify-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="flex -space-x-2">
                    <?php foreach ($mutual_friends as $mf): ?>
                        <img src="<?php echo $mf['avatar']; ?>" class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800 object-cover" title="<?php echo htmlspecialchars($mf['full_name']); ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
                <span><?php echo $mutual_count; ?> <?php echo $t['mutual_friends']; ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Icon-based Tab Navigation -->
    <div class="px-4 sm:px-6 mt-6" role="tablist" aria-label="<?php echo $lang == 'en' ? 'Profile sections' : 'Profil bölümleri'; ?>">
        <div class="flex gap-2 overflow-x-auto no-scrollbar">
            <!-- About Tab (Gradient Active) -->
            <button type="button" onclick="switchTab('about')" id="tab-about" role="tab" aria-selected="true" aria-controls="content-about" tabindex="0" style="background-color: #0055FF;" class="flex-1 min-w-[80px] flex flex-col items-center gap-1 px-4 py-3 rounded-2xl transition-all tab-btn text-white shadow-lg">
                <i class="fas fa-user text-lg" aria-hidden="true"></i>
                <span class="text-xs font-bold"><?php echo $lang == 'en' ? 'About' : 'Hakkında'; ?></span>
            </button>
            
            <!-- Posts Tab -->
            <button type="button" onclick="switchTab('posts')" id="tab-posts" role="tab" aria-selected="false" aria-controls="content-posts" tabindex="-1" class="flex-1 min-w-[80px] flex flex-col items-center gap-1 px-4 py-3 rounded-2xl transition-all tab-btn bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:border-blue-300">
                <i class="fas fa-th-large text-lg" aria-hidden="true"></i>
                <span class="text-xs font-bold"><?php echo $t['posts_tab']; ?></span>
            </button>
            
            <!-- Events Tab (Business Only) -->
            <?php if($is_business): ?>
            <button type="button" onclick="switchTab('events')" id="tab-events" role="tab" aria-selected="false" aria-controls="content-events" tabindex="-1" class="flex-1 min-w-[80px] flex flex-col items-center gap-1 px-4 py-3 rounded-2xl transition-all tab-btn bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:border-blue-300">
                <i class="fas fa-calendar-alt text-lg" aria-hidden="true"></i>
                <span class="text-xs font-bold"><?php echo $t['events_tab']; ?></span>
            </button>
            <?php endif; ?>
            
            <!-- Friends Tab -->
            <button type="button" onclick="switchTab('friends')" id="tab-friends" role="tab" aria-selected="false" aria-controls="content-friends" tabindex="-1" class="flex-1 min-w-[80px] flex flex-col items-center gap-1 px-4 py-3 rounded-2xl transition-all tab-btn bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:border-blue-300">
                <i class="fas fa-users text-lg" aria-hidden="true"></i>
                <span class="text-xs font-bold"><?php echo $t['friends_tab']; ?></span>
            </button>
            
            <!-- Expert Tab (Expert Only) -->
            <?php if(!empty($profile_user['expert_badge']) || in_array('local_guide', $badges) || in_array('expert', $badges)): ?>
            <button type="button" onclick="switchTab('expert')" id="tab-expert" role="tab" aria-selected="false" aria-controls="content-expert" tabindex="-1" class="flex-1 min-w-[80px] flex flex-col items-center gap-1 px-4 py-3 rounded-2xl transition-all tab-btn bg-violet-600 text-white shadow-lg border border-violet-500 hover:bg-violet-700">
                <i class="fas fa-certificate text-lg text-white" aria-hidden="true"></i>
                <span class="text-xs font-bold text-white">Expert</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .tab-btn.active {
            background-color: #0055FF !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 10px 25px -5px rgba(0, 85, 255, 0.4);
        }
        .tab-btn.active i, .tab-btn.active span {
            color: white !important;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <!-- Tab Contents Container -->
    <div class="px-4 sm:px-6 mt-6">
    
        <!-- About Tab Content -->
        <div id="content-about" role="tabpanel" aria-labelledby="tab-about" aria-hidden="false">

        <?php if ($is_blocked || $blocked_me): ?>
            <div class="mt-12 bg-white/50 dark:bg-slate-800/50 backdrop-blur rounded-[2rem] p-12 text-center border border-slate-200 dark:border-slate-700 shadow-xl">
                <div class="w-20 h-20 bg-red-50 dark:bg-red-900/20 rounded-full flex items-center justify-center mx-auto mb-6 text-red-500">
                    <?php echo heroicon('no_symbol', 'w-10 h-10'); ?>
                </div>
                <h2 class="text-xl font-bold mb-2"><?php echo $is_blocked ? $t['user_blocked'] : $t['blocked_by_user']; ?></h2>
                <p class="text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Profile details and posts are not visible due to privacy settings.' : 'Gizlilik ayarları nedeniyle profil detayları ve gönderiler görüntülenemiyor.'; ?></p>
            </div>
        <?php else: ?>
        <!-- Personal Info Card -->
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="mt-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2rem] p-8 shadow-xl border border-white/20 dark:border-slate-700/50 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 relative overflow-hidden group">
                 <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-blue-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                 <h3 class="font-bold text-lg mb-6 flex items-center gap-2 relative z-10 w-max bg-white/50 dark:bg-black/20 pr-4 py-1 rounded-r-full -ml-8 pl-8 border-y border-r border-white/20">
                    <i class="far fa-id-card text-[#0055FF]"></i> <?php echo $t['personal_info']; ?>
                </h3>
                
                <div class="space-y-6 relative z-10">
                    <?php 
                    $has_personal_info = !empty($profile_user['bio']) || 
                                         (!empty($profile_user['birth_date']) && $profile_user['birth_date'] != '0000-00-00') ||
                                         (!empty($profile_user['gender']) && $profile_user['gender'] != 'unspecified') ||
                                         (!empty($profile_user['relationship_status']) && $profile_user['relationship_status'] != 'unspecified') ||
                                         !empty($profile_user['location']) ||
                                         !empty($profile_user['facebook_link']) ||
                                         !empty($profile_user['instagram_link']);
                    
                    if(!$has_personal_info): 
                    ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                                <?php echo heroicon('user', 'w-8 h-8 opacity-50'); ?>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 font-medium">
                                <?php echo $is_own_profile 
                                    ? ($lang == 'en' ? "You haven't added any personal information yet." : 'Henüz kişisel bilgilerini eklemedin.') 
                                    : ($lang == 'en' ? 'User has not added any personal information yet.' : 'Kullanıcı henüz kişisel bilgilerini eklememiş.'); ?>
                            </p>
                            <?php if ($is_own_profile): ?>
                            <a href="edit_profile" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-2xl text-white font-bold text-sm shadow-lg transition-all active:scale-95" style="background-color: #0055FF;">
                                <?php echo heroicon('pencil_square', 'w-5 h-5'); ?>
                                <?php echo $t['edit_profile']; ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($profile_user['bio'])): ?>
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase mb-2 tracking-wider"><?php echo $t['bio']; ?></h4>
                            <p class="text-slate-700 dark:text-slate-300 leading-relaxed bg-white/50 dark:bg-black/20 p-4 rounded-xl border border-white/10"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if(!empty($profile_user['birth_date']) && $profile_user['birth_date'] != '0000-00-00'): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-500 flex-shrink-0">
                                <?php echo heroicon('cake', 'w-5 h-5'); ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-1"><?php echo $t['birthday']; ?></h4>
                                <p class="font-medium text-slate-700 dark:text-slate-200"><?php echo date('d.m.Y', strtotime($profile_user['birth_date'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($profile_user['gender']) && $profile_user['gender'] != 'unspecified'): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-500 flex-shrink-0">
                                <?php echo heroicon('user', 'w-5 h-5'); ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-1"><?php echo $t['gender']; ?></h4>
                                <p class="font-medium text-slate-700 dark:text-slate-200">
                                    <?php 
                                        $trans_key = 'gender_' . $profile_user['gender'];
                                        echo $t[$trans_key] ?? ucfirst($profile_user['gender']); 
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($profile_user['relationship_status']) && $profile_user['relationship_status'] != 'unspecified'): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-[#0055FF] flex-shrink-0">
                                <?php echo heroicon('heart', 'w-5 h-5'); ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-1"><?php echo $t['relationship']; ?></h4>
                                <p class="font-medium text-slate-700 dark:text-slate-200">
                                    <?php 
                                        $trans_key = 'rel_' . $profile_user['relationship_status'];
                                        echo $t[$trans_key] ?? ucfirst(str_replace('_', ' ', $profile_user['relationship_status'])); 
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($profile_user['location'])): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-500 flex-shrink-0">
                                <?php echo heroicon('location', 'w-5 h-5'); ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-1"><?php echo $t['location']; ?></h4>
                                <p class="font-medium text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($profile_user['location']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($can_see_phone && !$is_business): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 flex-shrink-0">
                                <?php echo heroicon('phone', 'w-5 h-5'); ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-1"><?php echo $t['phone']; ?></h4>
                                <a href="tel:<?php echo htmlspecialchars($profile_user['phone']); ?>" class="font-medium text-slate-700 dark:text-slate-200 hover:text-[#0055FF]"><?php echo htmlspecialchars($profile_user['phone']); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($can_see_email && !$is_business): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 flex-shrink-0">
                                <?php echo heroicon('envelope', 'w-5 h-5'); ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-1"><?php echo $t['email']; ?></h4>
                                <a href="mailto:<?php echo htmlspecialchars($profile_user['email']); ?>" class="font-medium text-slate-700 dark:text-slate-200 hover:text-[#0055FF]"><?php echo htmlspecialchars($profile_user['email']); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if(!empty($profile_user['facebook_link']) || !empty($profile_user['instagram_link'])): ?>
                    <div class="pt-6 border-t border-slate-100 dark:border-slate-700/50">
                        <h4 class="text-xs font-bold text-slate-400 uppercase mb-4 tracking-wider"><?php echo $t['social_accounts'] ?? 'Sosyal Medya'; ?></h4>
                        <div class="flex flex-wrap gap-4">
                            <?php if(!empty($profile_user['facebook_link'])): ?>
                                <a href="<?php echo htmlspecialchars($profile_user['facebook_link']); ?>" target="_blank" class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-5 py-3 rounded-2xl border border-blue-100 dark:border-blue-800/30 hover:scale-105 transition-all duration-300 font-bold">
                                    <i class="fab fa-facebook-f text-xl"></i>
                                    <span>Facebook</span>
                                </a>
                            <?php endif; ?>
                            
                            <?php if(!empty($profile_user['instagram_link'])): ?>
                                <a href="<?php echo htmlspecialchars($profile_user['instagram_link']); ?>" target="_blank" class="flex items-center gap-3 bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 px-5 py-3 rounded-2xl border border-pink-100 dark:border-pink-800/30 hover:scale-105 transition-all duration-300 font-bold">
                                    <i class="fab fa-instagram text-xl"></i>
                                    <span>Instagram</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif(!$is_own_profile): ?>
             <div class="mt-8 bg-slate-100 dark:bg-slate-800/50 rounded-2xl p-6 text-center border border-dashed border-slate-300 dark:border-slate-700">
                <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                    <?php echo heroicon('lock', 'w-6 h-6'); ?>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium"><?php echo $t['login_to_view_info']; ?></p>
                <a href="login" class="inline-block mt-3 text-[#0055FF] font-bold hover:underline text-sm flex items-center gap-1"><?php echo $t['login']; ?> <?php echo heroicon('arrow_right', 'w-3 h-3'); ?></a>
            </div>
        <?php endif; ?>

        <!-- Business Info Card -->
        <?php if($is_business): ?>
        <div class="mt-8 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2rem] p-8 shadow-xl border border-white/20 dark:border-slate-700/50 relative overflow-hidden group hover:shadow-2xl hover:shadow-pink-500/10 transition-all duration-500">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-blue-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            <h3 class="font-bold text-lg mb-6 flex items-center gap-2 relative z-10 w-max bg-white/50 dark:bg-black/20 pr-4 py-1 rounded-r-full -ml-8 pl-8 border-y border-r border-white/20">
                <?php echo heroicon('store', 'text-[#0055FF] w-5 h-5'); ?> <?php echo $t['business_info']; ?>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contact Info -->
                <div class="space-y-4">
                     <?php if(!empty($profile_user['location'])): ?>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 flex-shrink-0">
                            <?php echo heroicon('location', 'w-4 h-4'); ?>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase"><?php echo $t['location']; ?></p>
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($profile_user['location']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($can_see_phone): ?>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 flex-shrink-0">
                            <?php echo heroicon('phone', 'w-4 h-4'); ?>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase"><?php echo $t['phone']; ?></p>
                            <a href="tel:<?php echo htmlspecialchars($profile_user['phone']); ?>" class="text-sm font-medium hover:text-pink-500"><?php echo htmlspecialchars($profile_user['phone']); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($can_see_email): ?>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 flex-shrink-0">
                            <?php echo heroicon('envelope', 'w-4 h-4'); ?>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase"><?php echo $t['email']; ?></p>
                            <a href="mailto:<?php echo htmlspecialchars($profile_user['email']); ?>" class="text-sm font-medium hover:text-pink-500"><?php echo htmlspecialchars($profile_user['email']); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Map -->
                <div class="h-48 rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 relative">
                    <?php if(!empty($profile_user['location'])): ?>
                        <iframe width="100%" height="100%" frameborder="0" style="border:0"
                            src="https://maps.google.com/maps?q=<?php echo urlencode($profile_user['location']); ?>&output=embed">
                        </iframe>
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                            <div class="text-center">
                                <?php echo heroicon('location', 'w-8 h-8 mb-2 mx-auto'); ?>
                                <p class="text-xs"><?php echo $t['location']; ?> <?php echo $lang == 'tr' ? 'bilgisi girilmemiş' : 'info not provided'; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
        <?php endif; // if session user ?>
        <?php endif; // if blocked else ?>
        </div>
        <!-- End About Tab Content -->
        
        <!-- Posts Tab Content -->
        <div id="content-posts" class="hidden space-y-6" role="tabpanel" aria-labelledby="tab-posts" aria-hidden="true">
            <?php 
            // Show composer for own profile OR if user is a friend
            $can_post_on_wall = $is_own_profile || ($user_id && $friendship_status === 'friends');
            
            if($can_post_on_wall): 
            ?>
                <!-- Wall Composer -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 mb-8">
                    <?php if(!$is_own_profile): ?>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-2">
                            <i class="fas fa-pen-fancy text-[#0055FF]"></i>
                            <?php echo $lang == 'en' ? 'Write on ' . htmlspecialchars($profile_user['full_name']) . "'s wall" : htmlspecialchars($profile_user['full_name']) . "'nun duvarına yaz"; ?>
                        </p>
                    <?php else: ?>
                        <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 px-4 py-2 rounded-xl mb-4 text-xs font-bold flex items-center gap-2 border border-blue-100 dark:border-blue-800/30">
                            <i class="fas fa-info-circle"></i>
                            <?php echo $lang == 'en' ? 'Posts shared here will only appear on your profile wall.' : 'Burada paylaşılan gönderiler sadece profil duvarınızda görünür.'; ?>
                        </div>
                    <?php endif; ?>

                    <form id="wall-post-form" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="wall_user_id" value="<?php echo $view_user_id; ?>">
                        <div class="flex gap-3">
                            <?php 
                            $poster_avatar = $is_own_profile ? $profile_user['avatar'] : ($_SESSION['avatar'] ?? 'assets/default-avatar.jpg');
                            echo renderAvatar($poster_avatar, ['size' => 'md', 'isBordered' => true, 'color' => 'primary']); 
                            ?>
                            <textarea name="content" id="wall-post-input" class="w-full bg-slate-50 dark:bg-slate-700/50 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[#0055FF]/50 text-sm p-3 h-20" placeholder="<?php echo $is_own_profile ? ($t['composer_placeholder'] ?? 'Ne düşünüyorsun?') : ($lang == 'en' ? 'Write something...' : 'Bir şeyler yaz...'); ?>"></textarea>
                        </div>
                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                            <div class="flex gap-2">
                                <label class="cursor-pointer text-slate-400 hover:text-[#0055FF] transition-colors p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                    <input type="file" name="image" accept="image/*" class="hidden" onchange="previewWallImage(this)">
                                    <?php echo heroicon('image', 'w-5 h-5'); ?>
                                </label>
                            </div>
                            <button type="submit" style="background-color: #0055FF;" class="text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg shadow-blue-500/30 hover:opacity-90 transition-all disabled:opacity-50 flex items-center gap-2" id="wall-post-btn">
                                <i class="fas fa-paper-plane"></i> <?php echo $t['share'] ?? 'Paylaş'; ?>
                            </button>
                        </div>
                        <!-- Image Preview -->
                        <div id="wall-image-preview" class="hidden mt-3">
                            <div class="relative inline-block">
                                <img id="wall-preview-img" src="" class="max-h-32 rounded-lg">
                                <button type="button" onclick="clearWallImage()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>


            <?php if(count($posts) > 0): ?>
                <?php foreach($posts as $post): ?>
                    <!-- Feed-Style Post Card -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl transition-all" data-post-id="<?php echo $post['id']; ?>" id="post-<?php echo $post['id']; ?>">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex gap-3">
                                <a href="profile?uid=<?php echo $post['user_id']; ?>" onclick="event.stopPropagation()" aria-label="<?php echo htmlspecialchars($post['full_name']); ?>">
                                     <?php echo renderAvatar($post['avatar'], [
                                         'size' => 'lg', 
                                         'isBordered' => true, 
                                         'color' => $post['badge'] == 'founder' ? 'primary' : ($post['badge'] == 'moderator' ? 'primary' : 'default'),
                                         'last_seen' => $post['last_seen'],
                                         'alt' => $post['full_name'] . "'s Avatar"
                                     ]); ?>
                                </a>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <a href="profile?uid=<?php echo $post['user_id']; ?>" class="font-bold text-slate-700 dark:text-slate-200 hover:text-[#0055FF] transition-colors">
                                            <?php echo htmlspecialchars($post['full_name']); ?>
                                        </a>
                                        <?php if($post['badge'] == 'founder') echo heroicon('shield', 'text-[#0055FF] w-3 h-3'); ?>
                                        <?php if($post['badge'] == 'business') echo heroicon('check', 'text-green-500 w-3 h-3'); ?>
                                        <?php if(!empty($post['wall_user_id']) && $post['wall_user_id'] != $post['user_id']): ?>
                                        <i class="fas fa-caret-right text-slate-400 text-xs"></i>
                                        <a href="profile?uid=<?php echo $post['wall_user_id']; ?>" class="font-medium text-slate-500 dark:text-slate-400 hover:text-[#0055FF] transition-colors text-sm">
                                            <?php echo htmlspecialchars($post['wall_owner_name']); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('d.m H:i', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>

                            <!-- Post Menu (Delete) -->
                            <?php 
                            // Can delete if: Own post OR It's my wall
                            $can_delete = ($user_id && $user_id == $post['user_id']) || $is_own_profile;
                            
                            if($can_delete): 
                            ?>
                            <div class="relative ml-auto">
                                <button onclick="togglePostMenu(<?php echo $post['id']; ?>)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div id="post-menu-<?php echo $post['id']; ?>" class="hidden absolute right-0 top-8 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-20">
                                    <button onclick="deletePost(<?php echo $post['id']; ?>)" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2 transition-colors">
                                        <i class="fas fa-trash-alt text-xs"></i> 
                                        <?php echo $lang == 'en' ? 'Delete' : 'Sil'; ?>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div id="post-text-container-<?php echo $post['id']; ?>">
    <p class="text-slate-800 dark:text-slate-200 leading-relaxed mb-2" id="post-original-<?php echo $post['id']; ?>">
        <?php echo nl2br(linkifyHashtags($post['content'] ?? '')); ?>
    </p>
    <p class="text-slate-800 dark:text-slate-200 leading-relaxed mb-2 hidden italic border-l-4 border-[#0055FF] pl-4 transition-all" id="post-translated-<?php echo $post['id']; ?>">
    </p>
</div>                        

                        <!-- Post Media -->
                        <?php if(!empty($post['media_url'])): ?>
                            <?php if($post['media_type'] == 'image'): ?>
                                <img src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full h-auto max-h-[500px] object-cover rounded-xl mt-3 mb-1">
                            <?php elseif($post['media_type'] == 'video'): ?>
                                <div class="rounded-xl overflow-hidden mt-3 mb-1 border border-slate-100 dark:border-slate-800 bg-black">
                                    <?php if(strpos($post['media_url'], 'youtube.com') !== false || strpos($post['media_url'], 'youtu.be') !== false || strpos($post['media_url'], 'vimeo.com') !== false): ?>
                                        <div class="relative pt-[56.25%]">
                                            <iframe src="<?php echo htmlspecialchars($post['media_url']); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                                        </div>
                                    <?php else: ?>
                                        <video src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full max-h-[500px]" controls playsinline></video>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif(!empty($post['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="w-full h-auto max-h-[500px] object-cover rounded-xl mt-3 mb-1">
                        <?php elseif(!empty($post['image'])): ?>
                             <img src="<?php echo htmlspecialchars($post['image']); ?>" class="w-full h-auto max-h-[500px] object-cover rounded-xl mt-3 mb-1">
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="flex gap-6 border-t border-slate-100 dark:border-slate-700 pt-4 text-slate-400">
                            <button onclick="likePost(<?php echo $post['id']; ?>, this)" 
                                    class="flex items-center gap-2 hover:text-[#0055FF] transition-colors like-btn"
                                    id="like-btn-<?php echo $post['id']; ?>">
                                <?php echo heroicon('heart', 'w-5 h-5'); ?>
                                <span class="text-sm font-bold" id="like-count-<?php echo $post['id']; ?>"><?php echo $post['like_count'] ?? 0; ?></span>
                            </button>
                            <a href="post_detail?id=<?php echo $post['id']; ?>" class="flex items-center gap-2 hover:text-blue-500 transition-colors">
                                <?php echo heroicon('comment_dots', 'w-5 h-5'); ?>
                                <span class="text-sm font-bold"><?php echo $post['comment_count'] ?? 0; ?></span>
                            </a>
                            <button onclick="translatePost(<?php echo $post['id']; ?>)" class="flex items-center gap-2 hover:text-violet-500 transition-colors">
                                <i class="fas fa-language"></i>
                            </button>
                            <button class="flex items-center gap-2 hover:text-green-500 transition-colors ml-auto">
                                <?php echo heroicon('share', 'w-5 h-5'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-10 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 px-6">
                    <div class="flex justify-center mb-3 text-slate-400"><?php echo heroicon('document_text', 'w-10 h-10'); ?></div>
                    <p class="text-slate-500 dark:text-slate-400 font-medium"><?php echo $t['no_posts_yet']; ?></p>
                    <?php if ($is_own_profile && $can_post_on_wall): ?>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-1"><?php echo $lang == 'en' ? 'Share your first post above.' : 'İlk gönderini yukarıdaki alandan paylaş.'; ?></p>
                    <button type="button" onclick="document.getElementById('wall-post-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); document.getElementById('wall-post-input')?.focus();" class="mt-4 px-5 py-2.5 rounded-2xl text-white font-bold text-sm transition-all active:scale-95" style="background-color: #0055FF;">
                        <?php echo heroicon('pencil_square', 'w-5 h-5 inline-block mr-2 align-middle'); ?>
                        <?php echo $lang == 'en' ? 'Write first post' : 'İlk gönderiyi yaz'; ?>
                    </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Events Feed (Business Only) -->
        <?php if($is_business): ?>
        <div id="content-events" class="hidden space-y-4" role="tabpanel" aria-labelledby="tab-events" aria-hidden="true">
             <?php foreach ($user_events as $event): ?>
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl flex gap-4 border border-slate-200 dark:border-slate-700 shadow-sm relative group">
                    <div class="w-24 h-24 bg-slate-200 dark:bg-slate-700 rounded-lg overflow-hidden flex-shrink-0 relative">
                        <?php if($event['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($event['image_url']); ?>" class="w-full h-full object-cover" loading="lazy" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-400"><?php echo heroicon('image', 'w-6 h-6'); ?></div>
                        <?php endif; ?>
                        <div class="absolute top-1 right-1 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded backdrop-blur">
                            <?php echo $event['category']; ?>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                             <div>
                                <h4 class="font-bold text-lg leading-tight mb-1">
                                    <a href="event_detail?id=<?php echo $event['id']; ?>" class="hover:underline"><?php echo htmlspecialchars($event['title']); ?></a>
                                </h4>
                                 <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-2">
                                    <?php echo heroicon('calendar', 'w-3 h-3'); ?> <?php echo date('d.m.Y', strtotime($event['event_date'])); ?> 
                                    <span class="mx-1">•</span> 
                                    <?php echo heroicon('clock', 'w-3 h-3'); ?> <?php echo date('H:i', strtotime($event['start_time'])); ?>
                                </p>
                             </div>
                             <?php if($is_own_profile): ?>
                             <div class="flex gap-2">
                                 <?php if(isset($_SESSION['badge']) && $_SESSION['badge'] === 'founder'): ?>
                                 <a href="admin/index" class="text-slate-400 hover:text-blue-500"><?php echo heroicon('edit', 'w-4 h-4'); ?></a>
                                 <?php endif; ?>
                             </div>
                             <?php endif; ?>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 line-clamp-2"><?php echo htmlspecialchars($event['description']); ?></p>
                        
                        <?php if(!empty($event['event_location'])): ?>
                            <p class="text-xs text-slate-400 mt-2 flex items-center gap-1"><?php echo heroicon('location', 'w-3 h-3'); ?> <?php echo htmlspecialchars($event['event_location']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(count($user_events) == 0): ?>
                    <div class="text-center py-10 opacity-50">
                        <div class="flex justify-center mb-2"><?php echo heroicon('calendar', 'w-8 h-8'); ?></div>
                        <p><?php echo $t['no_upcoming_events']; ?></p>
                        <?php if($is_own_profile): ?>
                            <a href="admin/add_event" class="text-[#0055FF] font-bold hover:underline mt-2 inline-block"><?php echo $t['add_event']; ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Likes Feed -->
        <div id="content-likes" class="hidden space-y-4">
             <?php foreach ($likes as $event): ?>
                <div class="bg-white dark:bg-slate-800 p-4 rounded-xl flex gap-4 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <div class="w-20 h-20 bg-slate-200 dark:bg-slate-700 rounded-lg overflow-hidden flex-shrink-0">
                        <?php if($event['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($event['image_url']); ?>" class="w-full h-full object-cover" loading="lazy" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4 class="text-xs uppercase text-[#0055FF] font-bold mb-1"><?php echo htmlspecialchars($event['venue_name']); ?></h4>
                        <a href="event_detail?id=<?php echo $event['id']; ?>" class="font-bold leading-tight hover:underline block"><?php echo htmlspecialchars($event['title']); ?></a>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1">
                            <?php echo heroicon('calendar', 'w-3 h-3'); ?> <?php echo date('d.m.Y', strtotime($event['event_date'])); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(count($likes) == 0) echo "<p class='text-center text-slate-400 py-4'>Hiç beğeni yok.</p>"; ?>
        </div>

        <!-- Friends List -->
        <div id="content-friends" class="hidden" role="tabpanel" aria-labelledby="tab-friends" aria-hidden="true">
            <?php if (count($friends_list) > 0): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($friends_list as $friend): ?>
                        <a href="profile?uid=<?php echo $friend['id']; ?>" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 hover:shadow-lg hover:-translate-y-1 transition-all group">
                            <div class="flex flex-col items-center text-center">
                                <img src="<?php echo $friend['avatar']; ?>" class="w-16 h-16 rounded-full object-cover mb-3 border-2 border-blue-200 dark:border-blue-800 group-hover:border-blue-400 transition-colors" loading="lazy" alt="<?php echo htmlspecialchars($friend['full_name']); ?>">
                                <h4 class="font-bold text-sm group-hover:text-[#0055FF] transition-colors"><?php echo htmlspecialchars($friend['full_name']); ?></h4>
                                <p class="text-xs text-slate-400">@<?php echo htmlspecialchars($friend['username']); ?></p>
                                <?php if($friend['badge'] == 'founder') echo '<span class="mt-2 bg-blue-100 dark:bg-blue-900/30 text-[#0055FF] dark:text-blue-400 text-[9px] px-2 py-0.5 rounded uppercase tracking-wider font-bold">Kurucu</span>'; ?>
                                <?php if($friend['badge'] == 'business') echo '<span class="mt-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[9px] px-2 py-0.5 rounded uppercase tracking-wider font-bold">İşletme</span>'; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-10 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 px-6">
                    <div class="flex justify-center mb-3 text-slate-400"><?php echo heroicon('users', 'w-10 h-10'); ?></div>
                    <p class="text-slate-500 dark:text-slate-400 font-medium"><?php echo $is_own_profile ? ($lang == 'en' ? "You don't have any friends yet." : 'Henüz arkadaşınız yok.') : ($lang == 'en' ? "This user doesn't have any friends yet." : 'Bu kullanıcının henüz arkadaşı yok.'); ?></p>
                    <?php if ($is_own_profile): ?>
                    <a href="members" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-2xl text-white font-bold text-sm shadow-lg transition-all active:scale-95" style="background-color: #0055FF;">
                        <?php echo heroicon('user_plus', 'w-5 h-5'); ?>
                        <?php echo $lang == 'en' ? 'Find friends' : 'Arkadaş bul'; ?>
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- End Friends Tab Content -->
        
        <!-- Expert Tab Content -->
        <div id="content-expert" class="hidden" role="tabpanel" aria-labelledby="tab-expert" aria-hidden="true">
            <div class="mt-8">
                <!-- Ask Me Anything Section -->
                <div class="bg-violet-600 rounded-[2rem] p-8 shadow-xl text-white relative overflow-hidden mb-8">
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-16 h-16 bg-violet-700 rounded-2xl flex items-center justify-center text-3xl">
                                <i class="fas fa-comment-dots"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-white">Ask Me Anything</h3>
                                <p class="text-white font-semibold">Get verified advice from a local expert.</p>
                            </div>
                        </div>

                        <?php if(!$is_own_profile): ?>
                        <form id="askExpertForm" class="bg-violet-700 rounded-xl p-4 border border-violet-500">
                            <input type="hidden" name="expert_id" value="<?php echo $view_user_id; ?>">
                            <textarea name="question" required rows="3" class="w-full bg-violet-800 border border-violet-600 rounded-lg px-4 py-3 outline-none text-white placeholder-violet-300 resize-none font-medium text-base leading-relaxed focus:ring-2 focus:ring-violet-400" placeholder="What's your favorite hidden gem in Kalkan?"></textarea>
                            <div class="flex justify-between items-center mt-4 border-t border-violet-500 pt-4">
                                <span class="text-sm text-white font-semibold"><i class="fas fa-shield-alt mr-1"></i> Questions are moderated</span>
                                <button type="submit" class="px-6 py-2 bg-slate-700 hover:bg-slate-800 text-white font-black rounded-lg hover:scale-105 transition-all shadow-lg">
                                    Ask Now <i class="fas fa-paper-plane ml-1"></i>
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="bg-violet-700 rounded-xl p-6 text-center border border-violet-500">
                            <p class="font-black text-lg text-white">Your Expert Dashboard</p>
                            <p class="text-base text-white font-semibold mt-1">Answer questions to climb the expert leaderboard!</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Q&A History -->
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-history text-slate-400"></i> Recent Q&A
                </h3>
                
                <div id="qa-loader" class="text-center py-10 hidden">
                    <i class="fas fa-spinner fa-spin text-3xl text-violet-500"></i>
                </div>

                <div id="qa-container" class="space-y-6">
                    <!-- Loaded via JS -->
                </div>
            </div>
            
            <script>
            // Q&A Logic
            document.addEventListener('DOMContentLoaded', () => {
                const askForm = document.getElementById('askExpertForm');
                const qaContainer = document.getElementById('qa-container');
                const expertId = <?php echo $view_user_id; ?>;

                if (askForm) {
                    askForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const btn = this.querySelector('button');
                        const originalBtn = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                        const formData = new FormData(this);
                        formData.append('action', 'ask'); // Important if using same endpoint logic

                        fetch('api/expert_qa.php?action=ask', { // Corrected URL structure
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                alert('Question sent! The expert will be notified.');
                                this.reset();
                            } else {
                                alert(data.error || 'Error sending question');
                            }
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalBtn;
                        });
                    });
                }

                // Load Q&A
                function loadQA() {
                    document.getElementById('qa-loader').classList.remove('hidden');
                    fetch(`api/expert_qa.php?action=fetch&expert_id=${expertId}`)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('qa-loader').classList.add('hidden');
                        if (data.success && data.data.length > 0) {
                            const isOwner = data.is_owner;
                            qaContainer.innerHTML = data.data.map(q => {
                                const hasAnswer = q.answer !== null;
                                return `
                                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700">
                                    <div class="flex items-start gap-3 mb-4">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 shrink-0">
                                            <i class="fas fa-question"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-slate-900 dark:text-white leading-snug">${q.question}</h4>
                                            <div class="text-xs text-slate-400 mt-1">Asked by ${q.asker_name} • ${new Date(q.created_at).toLocaleDateString()}</div>
                                        </div>
                                        ${isOwner ? `
                                        <button onclick="deleteQuestion(${q.id})" class="text-red-500 hover:text-red-700 text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        ` : ''}
                                    </div>
                                    
                                    ${hasAnswer ? `
                                    <div class="ml-11 bg-violet-50 dark:bg-violet-900/10 rounded-xl p-4 border border-violet-100 dark:border-violet-800/30 relative">
                                        <div class="absolute top-0 left-4 -mt-2 w-4 h-4 bg-violet-50 dark:bg-gray-800 border-t border-l border-violet-100 dark:border-slate-700 transform rotate-45"></div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-black text-violet-600 bg-violet-100 px-2 py-0.5 rounded-md uppercase">Expert Answer</span>
                                            ${isOwner && q.is_public == 0 ? '<span class="text-xs text-slate-500">(Private)</span>' : ''}
                                        </div>
                                        <p class="text-slate-700 dark:text-slate-300">${q.answer}</p>
                                    </div>
                                    ` : (isOwner ? `
                                    <div class="ml-11">
                                        <form onsubmit="answerQuestion(event, ${q.id})" class="space-y-3">
                                            <textarea name="answer" required rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none resize-none" placeholder="Write your answer..."></textarea>
                                            <div class="flex items-center justify-between">
                                                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                                    <input type="checkbox" name="is_public" checked class="rounded">
                                                    <span>Make public</span>
                                                </label>
                                                <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-lg transition-colors">
                                                    <i class="fas fa-reply mr-1"></i> Answer
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    ` : '')}
                                </div>
                            `}).join('');
                        } else {
                            qaContainer.innerHTML = '<div class="text-center py-8 text-slate-400">No questions yet.</div>';
                        }
                    });
                }

                // Answer Question
                window.answerQuestion = function(e, questionId) {
                    e.preventDefault();
                    const form = e.target;
                    const btn = form.querySelector('button[type="submit"]');
                    const originalBtn = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    const formData = new FormData(form);
                    formData.append('question_id', questionId);

                    fetch('api/expert_qa.php?action=answer', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            loadQA(); // Reload Q&A
                        } else {
                            alert(data.error || 'Error posting answer');
                            btn.disabled = false;
                            btn.innerHTML = originalBtn;
                        }
                    })
                    .catch(() => {
                        alert('Connection error');
                        btn.disabled = false;
                        btn.innerHTML = originalBtn;
                    });
                }

                // Delete Question
                window.deleteQuestion = function(questionId) {
                    if (!confirm('Are you sure you want to delete this question?')) return;

                    const formData = new FormData();
                    formData.append('question_id', questionId);

                    fetch('api/expert_qa.php?action=delete', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            loadQA(); // Reload Q&A
                        } else {
                            alert(data.error || 'Error deleting question');
                        }
                    })
                    .catch(() => alert('Connection error'));
                }

                // Load on tab switch if expert tab is active initially? 
                // Or just load now if tab is visible. 
                // Better: expose loadQA to global or auto-load
                loadQA();
            });
            </script>
        </div>
        
    </div>
    <!-- End Tab Contents Container -->

    <script src="https://cdn.kalkansocial.com/js/theme.js?v=<?php echo defined('ASSET_VERSION') ? ASSET_VERSION : '1'; ?>" defer></script>
    <script>


        function switchTab(tab) {
            const tabIds = ['about', 'posts', 'events', 'friends', 'expert'];
            // Hide all tab contents and set aria-hidden
            tabIds.forEach(t => {
                const content = document.getElementById('content-' + t);
                if (content) {
                    content.classList.add('hidden');
                    content.setAttribute('aria-hidden', 'true');
                }
                const tb = document.getElementById('tab-' + t);
                if (tb) {
                    tb.setAttribute('aria-selected', 'false');
                    tb.setAttribute('tabindex', '-1');
                    tb.style.backgroundColor = '';
                    tb.classList.remove('active', 'text-white', 'shadow-lg');
                    tb.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-500', 'dark:text-slate-400', 'border', 'border-slate-200', 'dark:border-slate-700');
                }
            });
            const content = document.getElementById('content-' + tab);
            if (content) {
                content.classList.remove('hidden');
                content.setAttribute('aria-hidden', 'false');
            }
            const btn = document.getElementById('tab-' + tab);
            if (btn) {
                btn.setAttribute('aria-selected', 'true');
                btn.setAttribute('tabindex', '0');
                btn.style.backgroundColor = '#0055FF';
                btn.classList.add('active', 'text-white', 'shadow-lg');
                btn.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-500', 'dark:text-slate-400', 'border', 'border-slate-200', 'dark:border-slate-700');
                btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }

        // Keyboard: Arrow Left/Right + Enter/Space for tabs
        document.querySelector('[role="tablist"]')?.addEventListener('keydown', function(e) {
            const tabs = this.querySelectorAll('[role="tab"]');
            const current = Array.from(tabs).indexOf(document.activeElement);
            if (current === -1) return;
            let next = current;
            if (e.key === 'ArrowLeft') { e.preventDefault(); next = current > 0 ? current - 1 : tabs.length - 1; }
            else if (e.key === 'ArrowRight') { e.preventDefault(); next = current < tabs.length - 1 ? current + 1 : 0; }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (document.activeElement?.getAttribute('role') === 'tab') document.activeElement.click(); return; }
            else return;
            const id = tabs[next].id.replace('tab-', '');
            switchTab(id);
            tabs[next].focus();
        });
        
        function shareProfile() {
            const url = window.location.href;
            const title = '<?php echo htmlspecialchars($profile_user['full_name']); ?> - Kalkan Social';
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(console.error);
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    KalkanModal.showAlert('<?php echo $lang == "en" ? "Success" : "Başarılı"; ?>', '<?php echo $lang == "en" ? "Profile link copied!" : "Profil linki kopyalandı!"; ?>');
                }).catch(() => {
                    prompt('<?php echo $lang == "en" ? "Copy this link:" : "Bu linki kopyalayın:"; ?>', url);
                });
            }
        }
        
        
        // Friendship Functions
        async function sendFriendRequest(userId) {
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                const response = await fetch('api/friend_request.php', { method: 'POST', body: formData });
                const data = await response.json();
                console.log('Friend request response:', data); // DEBUG
                if (data.status === 'success') {
                    KalkanModal.showAlert('Başarılı', '✨ Arkadaşlık isteği gönderildi!');
                    location.reload();
                } else {
                    KalkanModal.showAlert('Hata', data.message || 'Bir hata oluştu');
                    if (data.debug) {
                        console.error('Debug info:', data.debug);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                KalkanModal.showAlert('Hata', 'Bir hata oluştu');
            }
        }
        
        async function respondToRequest(userId, action) {
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('action', action);
                const response = await fetch('api/friend_response.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    if (action === 'accept') KalkanModal.showAlert('Başarılı', '✨ Artık arkadaşsınız!');
                    location.reload();
                } else {
                    alert(data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Bir hata oluştu');
            }
        }
        
        // Toggle Friend Dropdown
        function toggleFriendDropdown() {
            const dropdown = document.getElementById('friend-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('friend-dropdown');
            const btn = document.getElementById('friend-dropdown-btn');
            if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
        // Close dropdown on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const dropdown = document.getElementById('friend-dropdown');
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    document.getElementById('friend-dropdown-btn')?.focus();
                }
            }
        });

        // Block User
        async function blockUser(userId) {
            KalkanModal.showConfirm(
                '<?php echo $lang == "en" ? "Block User" : "Kullanıcıyı Engelle"; ?>',
                '<?php echo $lang == "en" ? "Are you sure you want to block this user?" : "Bu kullanıcıyı engellemek istediğinizden emin misiniz?"; ?>',
                async () => {
                    try {
                        const formData = new FormData();
                        formData.append('target_id', userId);
                        formData.append('filter_type', 'block');
                        const response = await fetch('api/user_filter.php', { method: 'POST', body: formData });
                        const data = await response.json();
                        if (data.status === 'success') {
                            KalkanModal.showAlert('<?php echo $lang == "en" ? "Success" : "Başarılı"; ?>', '<?php echo $lang == "en" ? "User blocked" : "Kullanıcı engellendi"; ?>');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', data.message || '<?php echo $lang == "en" ? "An error occurred" : "Bir hata oluştu"; ?>');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', '<?php echo $lang == "en" ? "An error occurred" : "Bir hata oluştu"; ?>');
                    }
                }
            );
        }

        // Mute User
        async function muteUser(userId) {
            KalkanModal.showConfirm(
                '<?php echo $lang == "en" ? "Mute User" : "Kullanıcıyı Sessize Al"; ?>',
                '<?php echo $lang == "en" ? "Are you sure you want to mute this user?" : "Bu kullanıcıyı sessize almak istediğinizden emin misiniz?"; ?>',
                async () => {
                    try {
                        const formData = new FormData();
                        formData.append('target_id', userId);
                        formData.append('filter_type', 'mute');
                        const response = await fetch('api/user_filter.php', { method: 'POST', body: formData });
                        const data = await response.json();
                        if (data.status === 'success') {
                            KalkanModal.showAlert('<?php echo $lang == "en" ? "Success" : "Başarılı"; ?>', '<?php echo $lang == "en" ? "User muted" : "Kullanıcı sessize alındı"; ?>');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', data.message || '<?php echo $lang == "en" ? "An error occurred" : "Bir hata oluştu"; ?>');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', '<?php echo $lang == "en" ? "An error occurred" : "Bir hata oluştu"; ?>');
                    }
                }
            );
        }

        async function unfriend(userId) {
            KalkanModal.showConfirm(
                'Arkadaşlıktan Çıkar',
                'Arkadaşlıktan çıkarmak istediğinizden emin misiniz?',
                async () => {
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                const response = await fetch('api/unfriend.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    location.reload();
                } else {
                    KalkanModal.showAlert('Hata', data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                console.error('Error:', error);
                KalkanModal.showAlert('Hata', 'Bir hata oluştu');
            }
                }
            );
        }
        
        function toggleFriendMenu() {
            const menu = document.getElementById('friend-menu');
            menu.classList.toggle('hidden');
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('friend-menu');
            const btn = event.target.closest('button[onclick="toggleFriendMenu()"]');
            if (menu && !menu.contains(event.target) && !btn) {
                menu.classList.add('hidden');
            }
        });

// =============================================
// TRANSLATE POST FUNCTION
// =============================================
async function translatePost(postId) {
    const originalP = document.getElementById('post-original-' + postId);
    const translatedP = document.getElementById('post-translated-' + postId);
    // Find the button that triggered this - simplified selector
    const btn = document.querySelector(`button[onclick="translatePost(${postId})"]`);
    const icon = btn.querySelector('i');
    
    // Determine target lang based on page lang
    // As passed from PHP
    const currentLang = '<?php echo $lang ?? "tr"; ?>';
    
    if (originalP.classList.contains('hidden')) {
        // BACK TO ORIGINAL
        originalP.classList.remove('hidden');
        translatedP.classList.add('hidden');
        btn.classList.remove('text-pink-500');
    } else {
        // SHOW TRANSLATION
        if (!translatedP.innerText.trim()) {
            const originalIcon = icon.className;
            icon.className = 'fas fa-spinner fa-spin';
            
            try {
                const formData = new FormData();
                formData.append('text', originalP.innerText.trim());
                formData.append('target_lang', currentLang);
                
                const response = await fetch('api/translate.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    translatedP.innerText = data.translated_text;
                } else {
                    KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', 'Translation failed');
                    icon.className = originalIcon;
                    return;
                }
            } catch(e) {
                console.error(e);
                KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', 'Connection error');
                icon.className = originalIcon;
                return;
            }
            icon.className = originalIcon;
        }
        
        originalP.classList.add('hidden');
        translatedP.classList.remove('hidden');
        btn.classList.add('text-pink-500');
    }
}

// =============================================
// LIKE POST FUNCTION
// =============================================
async function likePost(postId, btn) {
    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        
        const response = await fetch('api/like_post.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.status === 'success') {
            const countEl = document.getElementById('like-count-' + postId);
            if (countEl) countEl.textContent = data.count;
            
            if (data.reacted) {
                btn.classList.add('text-pink-500');
                btn.classList.remove('text-slate-400');
            } else {
                btn.classList.remove('text-pink-500');
                btn.classList.add('text-slate-400');
            }
        } else {
            console.error('Like failed:', data.message);
        }
    } catch (error) {
        console.error('Like error:', error);
    }
}

// =============================================
// DELETE POST FUNCTIONS
// =============================================
function togglePostMenu(postId) {
    const menu = document.getElementById('post-menu-' + postId);
    // Close all other menus first
    document.querySelectorAll('[id^="post-menu-"]').forEach(el => {
        if(el.id !== 'post-menu-' + postId) el.classList.add('hidden');
    });
    menu.classList.toggle('hidden');
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('button[onclick^="togglePostMenu"]')) {
        document.querySelectorAll('[id^="post-menu-"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

async function deletePost(postId) {
    // Close the menu first
    const menu = document.getElementById('post-menu-' + postId);
    if (menu) menu.classList.add('hidden');
    
    KalkanModal.showConfirm(
        '<?php echo $lang == "en" ? "Delete Post" : "Gönderiyi Sil"; ?>',
        '<?php echo $lang == "en" ? "Are you sure you want to delete this post?" : "Bu gönderiyi silmek istediğinizden emin misiniz?"; ?>',
        async () => {
            try {
                const formData = new FormData();
                formData.append('post_id', postId);
                
                const response = await fetch('api/delete_post.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'fixed top-20 left-1/2 -translate-x-1/2 bg-emerald-500 text-white px-6 py-3 rounded-full font-bold shadow-xl z-[9999] animate-bounce';
                    successMsg.innerHTML = '<i class="fas fa-check-circle mr-2"></i><?php echo $lang == "en" ? "Post deleted!" : "Gönderi silindi!"; ?>';
                    document.body.appendChild(successMsg);
                    setTimeout(() => successMsg.remove(), 2000);
                    
                    // Find the post element and remove it with animation
                    const postElement = document.querySelector(`[data-post-id="${postId}"]`) || document.getElementById('post-' + postId);
                    if (postElement) {
                        postElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        postElement.style.opacity = '0';
                        postElement.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            postElement.remove();
                            
                            // Check if no posts left
                            const postsContainer = document.querySelector('#content-posts');
                            if (postsContainer && postsContainer.querySelectorAll('[data-post-id]').length === 0) {
                                postsContainer.innerHTML = `
                                    <div class="text-center py-20">
                                        <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl text-slate-300">
                                            <i class="far fa-image"></i>
                                        </div>
                                        <h3 class="text-slate-500 font-bold"><?php echo $lang == "en" ? "No posts yet" : "Henüz gönderi yok"; ?></h3>
                                    </div>
                                `;
                            }
                        }, 300);
                    }
                } else {
                    KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', data.message || '<?php echo $lang == "en" ? "Error deleting post" : "Gönderi silinirken hata oluştu"; ?>');
                }
            } catch (error) {
                console.error('Delete error:', error);
                KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', '<?php echo $lang == "en" ? "Connection error" : "Bağlantı hatası"; ?>');
            }
        }
    );
}


// =============================================
// WALL POST FUNCTIONS
// =============================================
function previewWallImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('wall-preview-img').src = e.target.result;
            document.getElementById('wall-image-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function clearWallImage() {
    document.querySelector('#wall-post-form input[type="file"]').value = '';
    document.getElementById('wall-image-preview').classList.add('hidden');
}

const wallPostForm = document.getElementById('wall-post-form');
if (wallPostForm) {
    wallPostForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('wall-post-btn');
        const content = document.getElementById('wall-post-input').value.trim();
        const imageInput = this.querySelector('input[type="file"]');
        
        if (!content && (!imageInput.files || !imageInput.files[0])) {
            KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', '<?php echo $lang == "en" ? "Please write something or add an image" : "Lütfen bir şeyler yazın veya resim ekleyin"; ?>');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> <?php echo $lang == "en" ? "Posting..." : "Paylaşılıyor..."; ?>';
        
        const formData = new FormData(this);
        
        // Debug: Check CSRF token
        console.log('CSRF Token:', formData.get('csrf_token'));
        
        try {
            const response = await fetch('api/create_post.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            // Debug: Log response
            console.log('Response:', data);
            
            if (data.success) {
                location.reload();
            } else {
                KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', data.message || '<?php echo $lang == "en" ? "Error posting" : "Paylaşım hatası"; ?>');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> <?php echo $t["share"] ?? "Paylaş"; ?>';
            }
        } catch (error) {
            console.error('Error:', error);
            KalkanModal.showAlert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>', '<?php echo $lang == "en" ? "Connection error" : "Bağlantı hatası"; ?>');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> <?php echo $t["share"] ?? "Paylaş"; ?>';
        }
    });
}

<?php if($is_own_profile): ?>
// =============================================
// POSITION EDITOR MODAL
// =============================================
let currentEditType = 'cover';
let modalPosX = 50;
let modalPosY = 50;
let modalOriginalPosX = 50;
let modalOriginalPosY = 50;
let isModalDragging = false;
let modalStartX, modalStartY;

function openPositionModal(type) {
    currentEditType = type;
    const modal = document.getElementById('position-modal');
    const preview = document.getElementById('modal-preview-image');
    
    if (type === 'cover') {
        const coverImg = document.getElementById('cover-image-display');
        if (!coverImg) return;
        preview.src = coverImg.src;
        const style = coverImg.style.objectPosition || '50% 50%';
        const parts = style.replace(/%/g, '').split(' ');
        modalPosX = parseFloat(parts[0]) || 50;
        modalPosY = parseFloat(parts[1]) || 50;
        preview.className = 'w-full h-64 object-cover rounded-xl';
    } else {
        const avatarImg = document.getElementById('avatar-image');
        if (!avatarImg) return;
        preview.src = avatarImg.src;
        const style = avatarImg.style.objectPosition || '50% 50%';
        const parts = style.replace(/%/g, '').split(' ');
        modalPosX = parseFloat(parts[0]) || 50;
        modalPosY = parseFloat(parts[1]) || 50;
        preview.className = 'w-48 h-48 object-cover rounded-full mx-auto';
    }
    
    modalOriginalPosX = modalPosX;
    modalOriginalPosY = modalPosY;
    preview.style.objectPosition = `${modalPosX}% ${modalPosY}%`;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePositionModal() {
    document.getElementById('position-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

function setPresetPosition(preset) {
    switch(preset) {
        case 'top': modalPosY = 0; break;
        case 'center': modalPosX = 50; modalPosY = 50; break;
        case 'bottom': modalPosY = 100; break;
        case 'left': modalPosX = 0; break;
        case 'right': modalPosX = 100; break;
    }
    updateModalPreview();
}

function updateModalPreview() {
    document.getElementById('modal-preview-image').style.objectPosition = `${modalPosX}% ${modalPosY}%`;
}

function saveModalPosition() {
    const formData = new FormData();
    formData.append('position_x', modalPosX);
    formData.append('position_y', modalPosY);
    formData.append('type', currentEditType);
    
    const saveBtn = document.getElementById('modal-save-btn');
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Kaydediliyor...';
    saveBtn.disabled = true;
    
    fetch('api/save_cover_position.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Update the original image
            if (currentEditType === 'cover') {
                document.getElementById('cover-image-display').style.objectPosition = `${modalPosX}% ${modalPosY}%`;
            } else {
                document.getElementById('avatar-image').style.objectPosition = `${modalPosX}% ${modalPosY}%`;
            }
            closePositionModal();
            showToast('<?php echo $lang == "en" ? "Position saved!" : "Pozisyon kaydedildi!"; ?>');
        } else {
            alert(data.message || 'Hata oluştu');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Bağlantı hatası');
    })
    .finally(() => {
        saveBtn.innerHTML = '<i class="fas fa-check mr-2"></i> <?php echo $lang == "en" ? "Save" : "Kaydet"; ?>';
        saveBtn.disabled = false;
    });
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-full font-bold shadow-2xl z-[100] animate-pulse';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
}

// Modal Touch/Mouse Events
document.addEventListener('DOMContentLoaded', function() {
    const previewContainer = document.getElementById('modal-preview-container');
    if (!previewContainer) return;
    
    previewContainer.addEventListener('mousedown', startDrag);
    previewContainer.addEventListener('touchstart', startDrag, { passive: false });
    document.addEventListener('mousemove', doDrag);
    document.addEventListener('touchmove', doDrag, { passive: false });
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
    
    function startDrag(e) {
        isModalDragging = true;
        if (e.touches) {
            modalStartX = e.touches[0].clientX;
            modalStartY = e.touches[0].clientY;
        } else {
            modalStartX = e.clientX;
            modalStartY = e.clientY;
        }
        e.preventDefault();
    }
    
    function doDrag(e) {
        if (!isModalDragging) return;
        
        let clientX, clientY;
        if (e.touches) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }
        
        const deltaX = (modalStartX - clientX) * 0.3;
        const deltaY = (modalStartY - clientY) * 0.3;
        
        modalPosX = Math.max(0, Math.min(100, modalPosX + deltaX));
        modalPosY = Math.max(0, Math.min(100, modalPosY + deltaY));
        
        updateModalPreview();
        
        modalStartX = clientX;
        modalStartY = clientY;
        e.preventDefault();
    }
    
    function endDrag() {
        isModalDragging = false;
    }
});
<?php endif; ?>
    </script>
<script src="https://cdn.kalkansocial.com/js/notifications.js?v=<?php echo defined('ASSET_VERSION') ? ASSET_VERSION : '1'; ?>" defer></script>

<?php if($is_own_profile): ?>
<!-- Position Editor Modal -->
<div id="position-modal" class="hidden fixed inset-0 z-[60] bg-slate-900/95 backdrop-blur-xl flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-slate-700">
        <button onclick="closePositionModal()" class="text-white p-2">
            <i class="fas fa-times text-xl"></i>
        </button>
        <h2 class="text-white font-bold text-lg"><?php echo $lang == 'en' ? 'Adjust Position' : 'Pozisyonu Ayarla'; ?></h2>
        <div class="w-10"></div>
    </div>
    
    <!-- Preview Area -->
    <div class="flex-1 flex items-center justify-center p-6 overflow-hidden">
        <div id="modal-preview-container" class="relative w-full max-w-md cursor-grab active:cursor-grabbing touch-none select-none">
            <img id="modal-preview-image" src="" alt="Preview" class="w-full h-64 object-cover rounded-xl pointer-events-none">
            <div class="absolute inset-0 border-2 border-dashed border-white/30 rounded-xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-white/50 pointer-events-none">
                <i class="fas fa-arrows-alt text-4xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Quick Presets -->
    <div class="px-4 pb-4">
        <p class="text-slate-400 text-xs text-center mb-3"><?php echo $lang == 'en' ? 'Quick Positions' : 'Hızlı Pozisyonlar'; ?></p>
        <div class="flex justify-center gap-2 flex-wrap">
            <button onclick="setPresetPosition('top')" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-full text-sm font-medium transition-colors">
                <i class="fas fa-arrow-up mr-1"></i> <?php echo $lang == 'en' ? 'Top' : 'Üst'; ?>
            </button>
            <button onclick="setPresetPosition('center')" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-full text-sm font-medium transition-colors">
                <i class="fas fa-compress-arrows-alt mr-1"></i> <?php echo $lang == 'en' ? 'Center' : 'Orta'; ?>
            </button>
            <button onclick="setPresetPosition('bottom')" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-full text-sm font-medium transition-colors">
                <i class="fas fa-arrow-down mr-1"></i> <?php echo $lang == 'en' ? 'Bottom' : 'Alt'; ?>
            </button>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="p-4 border-t border-slate-700 flex gap-3">
        <button onclick="closePositionModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white py-4 rounded-2xl font-bold text-base transition-colors">
            <i class="fas fa-times mr-2"></i> <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
        </button>
        <button id="modal-save-btn" onclick="saveModalPosition()" style="background-color: #0055FF;" class="flex-1 text-white py-4 rounded-2xl font-bold text-base transition-colors shadow-lg shadow-[#0055FF]/30 hover:shadow-[#0055FF]/50">
            <i class="fas fa-check mr-2"></i> <?php echo $lang == 'en' ? 'Save' : 'Kaydet'; ?>
        </button>
    </div>
</div>
<?php endif; ?>

<script>
async function toggleInTownProfile() {
    const btn = document.getElementById('profile-in-town-btn');
    if (!btn) return;
    
    // Optimistic UI update
    const wasInTown = btn.innerHTML.includes('map-marker');
    
    // Disable button briefly
    btn.disabled = true;
    btn.classList.add('opacity-70');

    try {
        const response = await fetch('api/in_town.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=toggle'
        });
        const data = await response.json();
        
        if (data.status === 'success') {
            if (data.is_in_town) {
                // Now in town
                btn.className = 'px-3 py-1.5 rounded-full text-xs font-bold shadow-lg flex items-center gap-2 transition-all bg-emerald-500 text-white animate-pulse';
                btn.innerHTML = '<i class="fas fa-map-marker-alt"></i> <?php echo $lang == "en" ? "I\'m Here!" : "Buradayım!"; ?>';
            } else {
                // Now away
                btn.className = 'px-3 py-1.5 rounded-full text-xs font-bold shadow-lg flex items-center gap-2 transition-all bg-white dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700';
                btn.innerHTML = '<i class="fas fa-plane"></i> <?php echo $lang == "en" ? "Away" : "Uzakta"; ?>';
            }
            
            // Show toast if available, otherwise native alert
            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            } else {
                // Silent success
            }
        }
    } catch (error) {
        console.error('Error toggling in-town status:', error);
        alert('<?php echo $lang == "en" ? "Connection error" : "Bağlantı hatası"; ?>');
    } finally {
        btn.disabled = false;
        btn.classList.remove('opacity-70');
    }
}
    
function toggleFriendMenu() {
    // Try to find the friend-menu (original) or friend-menu-alt (for non-friends ellipsis)
    const menu = document.getElementById('friend-menu') || document.getElementById('friend-menu-alt');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

    async function handleUserAction(targetId, action) {
        const confirmMsg = action === 'block' ? '<?php echo $t['confirm_block']; ?>' : (action === 'mute' ? '<?php echo $t['confirm_mute']; ?>' : null);
        if (confirmMsg && !confirm(confirmMsg)) return;

        try {
            const formData = new FormData();
            formData.append('target_id', targetId);
            formData.append('action', action);

            const res = await fetch('api/user_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                if (typeof showToast === 'function') {
                    showToast(data.message);
                } else {
                    alert(data.message);
                }
                setTimeout(() => location.reload(), 1000);
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message, 'error');
                } else {
                    alert(data.message);
                }
            }
        } catch (e) {
            alert('<?php echo $lang == "en" ? "An error occurred" : "Bir hata oluştu"; ?>');
        }
    }

</script>

<?php if (isset($_SESSION['user_id'])): ?>
<?php include __DIR__ . '/includes/settings_modal.php'; ?>
<?php endif; ?>

<!-- PWA Bottom Navigation Bar (Mobile Only) -->
<nav class="lg:hidden fixed bottom-0 left-0 w-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border-t border-slate-100 dark:border-slate-800 px-6 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))] flex justify-between items-center z-50 shadow-2xl safe-area-pb">
    <!-- Home -->
    <a href="index" class="flex items-center justify-center w-12 h-12 rounded-2xl transition-all text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50">
        <?php echo heroicon('home', 'text-2xl'); ?>
    </a>

    <!-- Feed -->
    <a href="feed" class="flex items-center justify-center w-12 h-12 rounded-2xl transition-all text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50">
        <?php echo heroicon('feed', 'text-2xl'); ?>
    </a>

    <!-- Create (FAB) -->
    <div class="relative -mt-10">
        <a href="create_post_page.php" style="background: linear-gradient(135deg, #2563eb 0%, #1e3a5f 100%);" class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-900/50 border-4 border-white dark:border-slate-900 transform active:scale-95 transition-all hover:shadow-blue-800/60 hover:scale-110 animate-pulse-glow">
            <?php echo heroicon('plus', 'text-2xl'); ?>
        </a>
    </div>

    </div><!-- End swup-main wrapper -->

<!-- Services -->
    <a href="services" class="flex items-center justify-center w-12 h-12 rounded-2xl transition-all text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50">
        <?php echo heroicon('briefcase', 'text-2xl'); ?>
    </a>

    <!-- Profile (Active) -->
    <a href="profile?uid=<?php echo $_SESSION['user_id'] ?? ''; ?>" class="flex items-center justify-center w-12 h-12 rounded-2xl transition-all bg-blue-50 dark:bg-slate-800 text-[#0055FF]">
        <?php echo heroicon('profile', 'text-2xl'); ?>
    </a>
</nav>


</body>
</html>
