<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/site_settings.php';

// Check for subdomain routing FIRST
$host = $_SERVER['HTTP_HOST'] ?? '';
$parts = explode('.', $host);

// If it's a subdomain (not www or main domain), route to subdomain handler
$mainHostParts = explode('.', preg_replace('/^www\./', '', site_host()));
$mainRoot = count($mainHostParts) >= 2 ? $mainHostParts[count($mainHostParts) - 2] : ($mainHostParts[0] ?? '');
if (count($parts) >= 3 && $parts[0] !== 'www' && ($parts[1] ?? '') === $mainRoot) {
    require_once 'subdomain_handler.php';
    exit();
}

require_once 'includes/lang.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/image_helper.php';
require_once 'includes/icon_helper.php';
require_once 'includes/contest_helper.php';

// Trigger winner check on page load (lazy execution)
checkAndPickWinner($pdo);

// No category filter needed anymore


// Cache Helper
require_once 'includes/cache_helper.php';
$cache = new CacheHelper();

// Fetch upcoming events for Index (Cached for 10 minutes)
$cacheKey = 'upcoming_events_index_' . date('Y-m-d');
$upcoming_events = $cache->get($cacheKey, 600);

if ($upcoming_events === false) {
    try {
        $upcoming_events_stmt = $pdo->prepare("SELECT e.*, u.venue_name, u.avatar as venue_avatar 
                                             FROM events e 
                                             JOIN users u ON e.user_id = u.id 
                                             WHERE e.event_date >= DATE(NOW()) AND e.status = 'approved' 
                                             ORDER BY e.event_date ASC, e.start_time ASC 
                                             LIMIT 10");
        $upcoming_events_stmt->execute();
        $upcoming_events = $upcoming_events_stmt->fetchAll();
        $cache->set($cacheKey, $upcoming_events);
    } catch (PDOException $e) {
        $upcoming_events = []; // Fallback
    }
}

// Get venue user's events if they're logged in as venue/admin
// Check role from database, not session (more secure and up-to-date)
$venue_events = [];
$venue_event_count = 0;
$is_venue_user = false;

$show_email_verify_banner = false;
if (isset($_SESSION['user_id'])) {
    try {
        $role_stmt = $pdo->prepare("SELECT role, badge, COALESCE(email_verified, 1) as email_verified FROM users WHERE id = ?");
        $role_stmt->execute([$_SESSION['user_id']]);
        $user_data = $role_stmt->fetch(PDO::FETCH_ASSOC);
        $user_role = $user_data['role'] ?? '';
        $user_badge = $user_data['badge'] ?? '';
        $show_email_verify_banner = (int)($user_data['email_verified'] ?? 1) === 0;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'email_verified') !== false) {
            try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 1"); } catch (Exception $x) {}
        }
        $role_stmt = $pdo->prepare("SELECT role, badge FROM users WHERE id = ?");
        $role_stmt->execute([$_SESSION['user_id']]);
        $user_data = $role_stmt->fetch(PDO::FETCH_ASSOC);
        $user_role = $user_data['role'] ?? '';
        $user_badge = $user_data['badge'] ?? '';
    }
    
    // Update session role if different
    if ($user_role && $user_role != ($_SESSION['role'] ?? '')) {
        $_SESSION['role'] = $user_role;
    }
    
    // Check if user is venue, admin, or has business badge
    $allowed_badges = ['vip_business', 'verified_business', 'business'];
    if ($user_role == 'venue' || $user_role == 'admin' || in_array($user_badge, $allowed_badges)) {
        $is_venue_user = true;
        $venue_stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY event_date DESC, start_time ASC LIMIT 5");
        $venue_stmt->execute([$_SESSION['user_id']]);
        $venue_events = $venue_stmt->fetchAll();
        
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM events WHERE user_id = ?");
        $count_stmt->execute([$_SESSION['user_id']]);
        $venue_event_count = $count_stmt->fetch()['total'];
    }
}

// Fetch latest lost pets for Important section (Cached for 5 minutes)
$cacheKeyLostPets = 'latest_lost_pets_index_v2';
$latest_lost_pets = $cache->get($cacheKeyLostPets, 300);

if ($latest_lost_pets === false) {
    try {
        $lost_pets_stmt = $pdo->query("SELECT * FROM lost_pets WHERE status_type IN ('lost', 'found') ORDER BY created_at DESC LIMIT 5");
        $latest_lost_pets = $lost_pets_stmt->fetchAll();
        $cache->set($cacheKeyLostPets, $latest_lost_pets);
    } catch (PDOException $e) {
        $latest_lost_pets = [];
    }
}

// Topluluk istatistikleri (cache 5 dk)
$stats_cache_key = 'index_community_stats_v1';
$community_stats = $cache->get($stats_cache_key, 300);
if ($community_stats === false) {
    $community_stats = ['members' => 0, 'posts_week' => 0, 'businesses' => 0];
    try {
        $community_stats['members'] = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (Exception $e) {}
    try {
        $community_stats['posts_week'] = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND deleted_at IS NULL")->fetchColumn();
    } catch (Exception $e) {}
    try {
        $community_stats['businesses'] = (int) $pdo->query("SELECT COUNT(*) FROM business_listings")->fetchColumn();
    } catch (Exception $e) {}
    $cache->set($stats_cache_key, $community_stats);
}

// Yeni post var mı? (Önemli Duyurular altında göstermek için)
$has_new_feed_post = false;
$current_user_id = $_SESSION['user_id'] ?? 0;
if ($current_user_id > 0) {
    try {
        $row = $pdo->query("SELECT MAX(created_at) as newest FROM posts WHERE deleted_at IS NULL")->fetch();
        if ($row && $row['newest']) {
            $newest = strtotime($row['newest']);
            if (isset($_SESSION['last_feed_post_time']) && $newest > $_SESSION['last_feed_post_time']) {
                $has_new_feed_post = true;
            }
            $_SESSION['last_feed_post_time'] = $newest;
        }
    } catch (Exception $e) {}
}

// Pre-fetch contest leader for LCP preload (moved up before <head>)
$week_start = getCurrentContestWeek() . " 00:00:00";
$current_leader = false;
$lcp_image_url = '';
try {
    $leader_stmt = $pdo->prepare("
        SELECT s.*, u.username, u.full_name, u.avatar,
               (SELECT COUNT(*) FROM contest_votes WHERE submission_id = s.id) as vote_count
        FROM contest_submissions s
        JOIN users u ON s.user_id = u.id
        WHERE s.created_at >= ?
        AND s.deleted_at IS NULL
        ORDER BY vote_count DESC, s.created_at DESC
        LIMIT 1
    ");
    $leader_stmt->execute([$week_start]);
    $current_leader = $leader_stmt->fetch();
    if ($current_leader) {
        $lcp_image_url = getIndexThumbUrl($current_leader['image_path']);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/seo_tags.php'; ?>
    <?php if (!empty($lcp_image_url)): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($lcp_image_url); ?>" fetchpriority="high">
    <?php endif; ?>
    <link rel="icon" href="logo.jpg?v=<?php echo ASSET_VERSION; ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars(site_short_name()); ?>">

    <!-- Favicons -->
    <link rel="icon" href="/logo.jpg">
    <link rel="apple-touch-icon" href="/logo.jpg">
    <link rel="manifest" href="/manifest.php">
    
    <!-- Core CSS & Config (Centralized) -->
    <?php include 'includes/header_css.php'; ?>
    
    <!-- Explore buttons dark mode fix (Tailwind dark: may not apply) -->
    <style>
    .dark .explore-btn-dir { background: rgba(37, 99, 235, 0.35) !important; border-color: rgba(96, 165, 250, 0.5) !important; }
    .dark .explore-btn-dir:hover { background: rgba(59, 130, 246, 0.45) !important; border-color: rgba(96, 165, 250, 0.7) !important; }
    .dark .explore-btn-mkt { background: rgba(5, 150, 105, 0.35) !important; border-color: rgba(52, 211, 153, 0.5) !important; }
    .dark .explore-btn-mkt:hover { background: rgba(16, 185, 129, 0.45) !important; border-color: rgba(52, 211, 153, 0.7) !important; }
    .dark .explore-btn-prop { background: rgba(124, 58, 237, 0.35) !important; border-color: rgba(167, 139, 250, 0.5) !important; }
    .dark .explore-btn-prop:hover { background: rgba(139, 92, 246, 0.45) !important; border-color: rgba(167, 139, 250, 0.7) !important; }
    .dark .explore-btn-pati { background: rgba(234, 88, 12, 0.35) !important; border-color: rgba(251, 146, 60, 0.5) !important; }
    .dark .explore-btn-pati:hover { background: rgba(249, 115, 22, 0.45) !important; border-color: rgba(251, 146, 60, 0.7) !important; }
    .dark .explore-btn-comm { background: rgba(22, 163, 74, 0.35) !important; border-color: rgba(74, 222, 128, 0.5) !important; }
    .dark .explore-btn-comm:hover { background: rgba(34, 197, 94, 0.45) !important; border-color: rgba(74, 222, 128, 0.7) !important; }
    .dark .explore-btn-svc { background: rgba(79, 70, 229, 0.35) !important; border-color: rgba(129, 140, 248, 0.5) !important; }
    .dark .explore-btn-svc:hover { background: rgba(99, 102, 241, 0.45) !important; border-color: rgba(129, 140, 248, 0.7) !important; }
    </style>
    
    <!-- LCP Optimization: Preload Hero Image -->
    <?php if (!empty($upcoming_events)): 
        $first_evt = $upcoming_events[0];
        $lcp_url = 'assets/img/event-placeholder.jpg';
        
        if (!empty($first_evt['image_url'])) {
            $lcp_url = $first_evt['image_url'];
            // Check for optimized _thumb version
            if (strpos($lcp_url, 'uploads/') !== false) {
                $thumb_path = str_replace('.webp', '_thumb.webp', $lcp_url);
                $pure_thumb_path = ltrim($thumb_path, '/');
                
                // Robust file check
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $thumb_path) || file_exists(__DIR__ . '/' . $pure_thumb_path)) {
                    $lcp_url = $thumb_path;
                } elseif (strpos($lcp_url, 'medium_') === false) {
                     // Legacy fallback
                     $medium_path = str_replace('uploads/', 'uploads/medium_', $lcp_url);
                     $pure_medium_path = ltrim($medium_path, '/');
                     if (file_exists($_SERVER['DOCUMENT_ROOT'] . $medium_path) || file_exists(__DIR__ . '/' . $pure_medium_path)) {
                         $lcp_url = $medium_path;
                     }
                }
            }
        }
    ?>
    <link rel="preload" as="image" href="<?php echo $lcp_url; ?>" fetchpriority="high">
    <?php endif; ?>

    <!-- Management Panel Visibility Logic -->
    <script>
        if (localStorage.getItem('hide_management_panel') === 'true') {
            document.write('<style>#management-panel { display: none !important; }</style>');
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-500 selection:bg-[#0055FF] selection:text-white overflow-x-hidden">

    <!-- Premium Background -->
    <!-- Light Mode Background -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <!-- Dark Mode Background -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>

    <main id="swup-main" class="transition-main container mx-auto px-4 md:px-6 pt-24">
        
        <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Welcome / Support Banner -->
        <div id="welcome-banner" class="mb-4 relative overflow-hidden rounded-2xl border border-blue-100 dark:border-blue-900/40" style="background:linear-gradient(135deg,#eff6ff 0%,#f0f9ff 50%,#f5f3ff 100%);">
            <div class="relative p-5 md:p-6 flex flex-col md:flex-row items-start md:items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center" style="background:#0055FF;">
                    <i class="fas fa-hand-holding-heart text-white text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-black text-slate-800 dark:text-slate-800 text-base md:text-lg mb-1">
                        <?php echo $lang == 'en' ? site_name() . ' just launched!' : site_name() . ' yeni acildi!'; ?>
                        <span style="display:inline-block;background:#0055FF;color:#fff;font-size:10px;padding:2px 8px;border-radius:6px;vertical-align:middle;margin-left:6px;"><?php echo $lang == 'en' ? 'NEW' : 'YENİ'; ?></span>
                    </h3>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        <?php echo $lang == 'en' 
                            ? 'We are building the digital heart of Kalkan together! Support us by creating a free account — share your experiences, discover events, and become part of the community.' 
                            : 'Kalkan\'ın dijital kalbini birlikte inşa ediyoruz! Ücretsiz hesap oluşturarak bize destek olun — deneyimlerinizi paylaşın, etkinlikleri keşfedin ve topluluğun bir parçası olun.'; ?>
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <a href="register" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white font-bold text-sm shadow-lg hover:shadow-xl transition-all hover:scale-105" style="background:#0055FF;">
                        <i class="fas fa-user-plus"></i>
                        <?php echo $lang == 'en' ? 'Join Free' : 'Ücretsiz Katıl'; ?>
                    </a>
                    <button onclick="document.getElementById('welcome-banner').style.display='none'" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-white/60 transition-colors" title="Kapat">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($show_email_verify_banner): ?>
        <!-- E-posta doğrulama hatırlatması -->
        <div id="email-verify-banner" class="mb-4 flex items-center justify-between gap-3 p-4 rounded-2xl bg-amber-500/20 dark:bg-amber-600/20 border border-amber-500/40 dark:border-amber-600/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/30 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-envelope-open-text text-amber-600 dark:text-amber-400"></i>
                </div>
                <div>
                    <p class="font-bold text-amber-900 dark:text-amber-100"><?php echo $lang == 'en' ? 'Please verify your email' : 'Lütfen e-postanızı doğrulayın'; ?></p>
                    <p class="text-sm text-amber-800/80 dark:text-amber-200/80"><?php echo $lang == 'en' ? 'Verify your email to secure your account.' : 'Hesabınızı güvende tutmak için e-postanızı doğrulayın.'; ?></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('email-verify-banner').remove()" class="text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 p-1" aria-label="Kapat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Stories Rail (Left Aligned) -->
        <div class="mb-6 w-full overflow-hidden">
            <div class="flex gap-4 overflow-x-auto hide-scrollbar pb-2" id="stories-container">
                <?php if(!isset($_SESSION['user_id'])): ?>
                <div class="flex items-center justify-between w-full py-2 px-4 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md rounded-2xl border border-white/20 dark:border-slate-700/30 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-pink-500 shadow-sm">
                            <?php echo heroicon('lock', 'w-5 h-5'); ?>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 font-bold">
                            <?php echo $lang == 'en' ? 'Login to see stories' : 'Hikayeleri görmek için'; ?> 
                            <a href="login" class="text-pink-500 hover:underline"><?php echo $t['login']; ?></a>
                        </p>
                    </div>
                </div>
                <?php else: ?>
                <!-- Skeleton Loader -->
                <div class="flex gap-4 animate-pulse">
                    <?php for($i=0; $i<6; $i++): ?>
                    <div class="flex flex-col items-center gap-1 shrink-0">
                        <div class="w-16 h-16 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                        <div class="w-12 h-2 rounded bg-slate-200 dark:bg-slate-800"></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Story Upload Modal -->
        <?php if(isset($_SESSION['user_id'])): ?>
        <div id="story-upload-modal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeStoryUploadModal()"></div>
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-black text-slate-800 dark:text-white"><?php echo $t['add_story']; ?></h2>
                        <button onclick="closeStoryUploadModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="story-upload-preview" class="mb-4 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-900 aspect-[9/16] max-h-64 flex items-center justify-center hidden">
                        <img id="story-preview-img" class="max-w-full max-h-full object-contain hidden">
                        <video id="story-preview-video" class="max-w-full max-h-full object-contain hidden" muted playsinline></video>
                        <p id="story-preview-placeholder" class="text-slate-400 text-sm"><?php echo $lang == 'en' ? 'Choose a photo or video' : 'Fotoğraf veya video seçin'; ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'Who can see' : 'Kim görebilir'; ?></label>
                        <div class="flex gap-2">
                            <button type="button" id="story-vis-everyone" onclick="setStoryVisibility('everyone')" class="flex-1 py-2 px-4 rounded-xl text-sm font-bold border-2 border-violet-500 bg-violet-500 text-white transition-all">
                                <i class="fas fa-globe mr-1"></i> <?php echo $t['story_everyone']; ?>
                            </button>
                            <button type="button" id="story-vis-friends" onclick="setStoryVisibility('friends_only')" class="flex-1 py-2 px-4 rounded-xl text-sm font-bold border-2 border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:border-violet-400 transition-all">
                                <i class="fas fa-user-friends mr-1"></i> <?php echo $t['story_friends_only']; ?>
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input type="file" id="story-file-input" accept="image/*,video/*" class="hidden">
                        <button type="button" onclick="document.getElementById('story-file-input').click()" class="flex-1 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                            <i class="fas fa-folder-open mr-2"></i> <?php echo $lang == 'en' ? 'Choose file' : 'Dosya seç'; ?>
                        </button>
                        <button type="button" id="story-share-btn" onclick="submitStory()" disabled class="flex-1 py-3 rounded-xl bg-violet-500 text-white font-bold text-sm hover:bg-violet-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Share' : 'Paylaş'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Hoş Geldin Kartı -->
        <div class="mb-6 bg-gradient-to-r from-[#0055FF]/10 to-pink-500/10 dark:bg-slate-800/60 dark:from-[#0055FF]/25 dark:to-pink-500/20 rounded-2xl p-4 md:p-5 border border-[#0055FF]/20 dark:border-slate-600/50">
            <h3 class="text-lg font-black text-slate-800 dark:text-white mb-1">
                <?php echo $lang == 'en' ? 'Welcome back' : 'Hoş geldin'; ?>, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Friend')[0]); ?>! 👋
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                <?php echo $lang == 'en' ? 'Discover what\'s happening in Kalkan today.' : 'Kalkan\'da bugün neler oluyor keşfet.'; ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Keşfet – Elegant service shortcuts -->
        <div class="mb-8 overflow-hidden">
            <div class="relative rounded-2xl bg-white/80 dark:bg-slate-900/40 dark:border-slate-700/40 backdrop-blur-sm border border-slate-200/60 shadow-sm dark:shadow-none">
                <div class="absolute inset-0 bg-gradient-to-br from-[#0055FF]/[0.03] via-transparent to-pink-500/[0.03] dark:from-[#0055FF]/[0.08] dark:to-pink-500/[0.06] rounded-2xl pointer-events-none"></div>
                <div class="relative px-4 py-5">
                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em] mb-4">
                        <?php echo $lang == 'en' ? 'Explore' : 'Keşfet'; ?>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <a href="directory" class="explore-btn-dir group flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50/80 border border-slate-100 hover:border-blue-300 hover:bg-blue-50/50 transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/15 dark:bg-blue-500/20 flex items-center justify-center group-hover:scale-105 transition-transform"><i class="fas fa-store text-blue-600 dark:text-blue-400"></i></div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Businesses' : 'İşletmeler'; ?></span>
                        </a>
                        <a href="marketplace" class="explore-btn-mkt group flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50/80 border border-slate-100 hover:border-emerald-300 hover:bg-emerald-50/50 transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/15 dark:bg-emerald-500/20 flex items-center justify-center group-hover:scale-105 transition-transform"><i class="fas fa-shopping-bag text-emerald-600 dark:text-emerald-400"></i></div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Market' : 'Pazar'; ?></span>
                        </a>
                        <a href="properties" class="explore-btn-prop group flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50/80 border border-slate-100 hover:border-violet-300 hover:bg-violet-50/50 transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-violet-500/15 dark:bg-violet-500/20 flex items-center justify-center group-hover:scale-105 transition-transform"><i class="fas fa-home text-violet-600 dark:text-violet-400"></i></div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Properties' : 'Emlak'; ?></span>
                        </a>
                        <a href="pati_safe" class="explore-btn-pati group flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50/80 border border-slate-100 hover:border-orange-300 hover:bg-orange-50/50 transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-orange-500/15 dark:bg-orange-500/20 flex items-center justify-center group-hover:scale-105 transition-transform"><i class="fas fa-paw text-orange-600 dark:text-orange-400"></i></div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?php echo $t['pati_safe']; ?></span>
                        </a>
                        <a href="community_support" class="explore-btn-comm group flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50/80 border border-slate-100 hover:border-green-300 hover:bg-green-50/50 transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-green-500/15 dark:bg-green-500/20 flex items-center justify-center group-hover:scale-105 transition-transform"><i class="fas fa-hand-holding-heart text-green-600 dark:text-green-400"></i></div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Community' : 'Askıda İyilik'; ?></span>
                        </a>
                        <a href="services" class="explore-btn-svc group flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50/80 border border-slate-100 hover:border-slate-400 hover:bg-slate-100/50 transition-all duration-200">
                            <div class="w-10 h-10 rounded-xl bg-slate-500/15 dark:bg-indigo-500/20 flex items-center justify-center group-hover:scale-105 transition-transform"><i class="fas fa-th-large text-slate-600 dark:text-indigo-400"></i></div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?php echo $t['services']; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- IMPORTANT ALERTS SECTION (Lost Pets, etc) -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center text-white">
                        <i class="fas fa-exclamation-triangle text-sm"></i>
                    </div>
                    <h2 class="text-xl font-black tracking-tight dark:text-white"><?php echo $t['important_alerts'] ?? ($lang == 'en' ? 'Important Alerts' : 'Önemli Duyurular'); ?></h2>
                </div>
                <?php if (!empty($latest_lost_pets)): ?>
                <a href="pati_safe.php" class="text-xs font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors uppercase tracking-wider">
                    <?php echo $t['view_all'] ?? ($lang == 'en' ? 'View All' : 'Hepsini Gör'); ?>
                </a>
                <?php endif; ?>
            </div>
            
            <div class="flex gap-4 overflow-x-auto hide-scrollbar pb-2">
                <!-- Moderator recruitment -->
                <a href="contact" class="shrink-0 w-64 bg-gradient-to-r from-violet-500/10 to-blue-500/10 dark:from-violet-500/20 dark:to-blue-500/20 rounded-2xl p-3 border-2 border-violet-200 dark:border-violet-700/50 shadow-sm hover:shadow-md hover:border-violet-400 transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl bg-violet-500/20 dark:bg-violet-500/30 flex items-center justify-center shrink-0">
                            <i class="fas fa-users-cog text-violet-600 dark:text-violet-400 text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-sm text-slate-800 dark:text-white">
                                <?php echo $lang == 'en' ? 'Volunteer moderators wanted!' : 'Gönüllü moderatör arıyoruz!'; ?>
                            </h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 line-clamp-2">
                                <?php echo $lang == 'en' ? 'Kalkan lovers who want to work with us – join our team!' : 'Bizimle çalışacak Kalkan sevdalısı gönüllü moderatörler arıyoruz.'; ?>
                            </p>
                        </div>
                    </div>
                </a>
                <?php foreach($latest_lost_pets ?? [] as $pet): ?>
                <a href="pati_safe.php?id=<?php echo $pet['id']; ?>" class="shrink-0 w-64 bg-white dark:bg-slate-800 rounded-2xl p-3 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                    <!-- Status Badge -->
                    <div class="absolute top-2 right-2 z-10">
                        <?php if($pet['status_type'] == 'lost'): ?>
                            <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] font-black rounded-lg uppercase">🚨 LOST</span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[10px] font-black rounded-lg uppercase">✅ FOUND</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 shrink-0">
                            <?php if(!empty($pet['photo_url'])): ?>
                                <img src="<?php echo htmlspecialchars(getIndexThumbUrl($pet['photo_url'])); ?>" class="w-full h-full object-cover" loading="lazy" width="56" height="56">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fas fa-paw"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-sm text-slate-800 dark:text-white truncate"><?php echo htmlspecialchars($pet['pet_name']); ?></h4>
                            <p class="text-xs text-slate-500 truncate mt-0.5">
                                <i class="fas fa-map-marker-alt text-[10px] mr-1"></i> <?php echo htmlspecialchars($pet['location']); ?>
                            </p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[10px] font-bold py-0.5 px-2 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                    <?php echo ucfirst($pet['pet_type']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($has_new_feed_post): ?>
        <a href="feed" class="mb-6 flex items-center justify-center gap-2 py-3 px-5 bg-[#0055FF] hover:bg-[#0044CC] text-white rounded-2xl font-bold text-sm shadow-lg shadow-blue-500/30 transition-all max-w-4xl mx-auto">
            <i class="fas fa-sparkles"></i>
            <?php echo $lang == 'en' ? 'New post!' : 'Yeni post var!'; ?>
            <i class="fas fa-arrow-right text-xs"></i>
        </a>
        <?php endif; ?>

        <!-- CURRENT CONTEST LEADER SECTION -->
        <?php // $current_leader already fetched at top for LCP preload ?>
        
        <?php if($current_leader): ?>
        <div class="mb-8 max-w-lg mx-auto px-4 sm:px-0">
             <div class="relative rounded-2xl overflow-hidden shadow-xl group cursor-pointer border border-pink-100 dark:border-slate-700" onclick="location.href='photo_contest.php'">
                <div class="h-40 w-full relative">
                    <img src="<?php echo htmlspecialchars(getIndexThumbUrl($current_leader['image_path'])); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" fetchpriority="high" width="400" height="160">
                </div>
                <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-transparent flex flex-col justify-center px-6">
                    <div class="inline-flex items-center gap-2 bg-pink-500 text-white px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider mb-2 w-max shadow-lg animate-pulse">
                        <i class="fas fa-fire"></i> <?php echo $lang == 'en' ? 'Weekly Leader' : 'Haftanın Lideri'; ?>
                    </div>
                    <h3 class="text-xl font-black text-white mb-1 truncate max-w-xs shadow-black drop-shadow-md">
                        #Kalkan<span class="text-pink-400">Snaps</span>
                    </h3>
                    <div class="flex items-center gap-2 mt-2">
                         <img src="<?php echo htmlspecialchars($current_leader['avatar']); ?>" class="w-8 h-8 rounded-full border-2 border-white shadow-md">
                         <div>
                             <p class="text-white text-sm font-bold leading-tight"><?php echo htmlspecialchars($current_leader['full_name']); ?></p>
                             <div class="flex items-center gap-1 text-pink-300 text-[10px] font-black bg-black/30 px-2 py-0.5 rounded-full mt-0.5 w-max backdrop-blur-sm">
                                <i class="fas fa-heart"></i> <?php echo $current_leader['vote_count']; ?> Love
                             </div>
                         </div>
                    </div>
                </div>
                <!-- Action -->
                <div class="absolute bottom-6 right-6 hidden md:block">
                    <span class="bg-white/20 backdrop-blur border border-white/30 text-white px-5 py-2.5 rounded-full font-bold text-sm hover:bg-white/30 transition-all flex items-center gap-2">
                        <?php echo $lang == 'en' ? 'Vote Now' : 'Oy Ver'; ?> <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
             </div>
        </div>
        <?php else: ?>
        <!-- Promo State if no entries yet -->
        <div class="mb-10 max-w-4xl mx-auto px-4 sm:px-0">
             <div class="relative rounded-3xl overflow-hidden shadow-2xl cursor-pointer" onclick="location.href='photo_contest.php'">
                 <div class="w-full bg-blue-600 p-8 md:p-10 flex items-center justify-between" style="background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-white/20 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-3 border border-white/20">
                            📸 <?php echo $lang == 'en' ? 'New Challenge' : 'Yeni Yarışma'; ?>
                        </div>
                        <h3 class="text-2xl md:text-4xl font-black text-white mb-2">Kalkan <span class="text-white/80">Snaps</span></h3>
                        <p class="text-white/90 max-w-md text-sm md:text-base font-medium leading-relaxed">
                            <?php echo $lang == 'en' ? 'The weekly photo contest is live! Snap a pic, get votes, become a legend.' : 'Haftalık fotoğraf yarışması başladı! Bir kare yakala, oy topla, efsane ol.'; ?>
                        </p>
                    </div>
                    <div class="hidden md:block">
                        <span class="bg-white text-pink-600 px-6 py-3 rounded-full font-black hover:scale-105 transition-transform shadow-xl flex items-center gap-2">
                            <i class="fas fa-camera"></i> <?php echo $lang == 'en' ? 'Join Contest' : 'Yarışmaya Katıl'; ?>
                        </span>
                    </div>
                 </div>
             </div>
        </div>
        <?php endif; ?>
        
        <!-- PWA INSTALL SECTION -->
        <div id="pwa-install-section" class="mb-8 max-w-4xl mx-auto px-4 sm:px-0">
             <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-xl rounded-2xl p-4 md:p-5 border border-white/20 dark:border-slate-700/50 shadow-xl relative overflow-hidden group">
                 <!-- Decorative Gradient -->
                 <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all"></div>
                 <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-pink-500/10 rounded-full blur-3xl group-hover:bg-pink-500/20 transition-all"></div>

                 <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left relative z-10">
                     <div class="flex-1">
                         <div class="inline-flex items-center gap-2 bg-[#0055FF]/10 text-[#0055FF] dark:text-blue-400 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider mb-2 border border-[#0055FF]/20">
                             <i class="fas fa-mobile-alt"></i> App
                         </div>
                         <h3 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white mb-1 tracking-tight">
                             <?php echo $lang == 'en' ? 'Install App' : 'Uygulamayı Yükle'; ?>
                         </h3>
                         <p class="text-slate-500 dark:text-slate-400 text-xs max-w-md font-bold leading-tight">
                             <?php echo $lang == 'en' ? 'Faster access and instant push notifications.' : 'Daha hızlı erişim ve anlık bildirimler.'; ?>
                         </p>
                     </div>
                     <div class="flex flex-col sm:flex-row gap-2 shrink-0 w-full md:w-auto">
                         <!-- Android / Chrome Install Button -->
                         <button id="pwa-install-btn-index" onclick="installPWA()" class="px-6 py-2.5 text-white font-black rounded-xl hover:scale-105 active:scale-95 transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2" style="background-color: #0055FF !important;">
                             <i class="fab fa-android text-base"></i> <?php echo $lang == 'en' ? 'Android Install' : 'Android Yükle'; ?>
                         </button>
                         
                         <!-- iOS Button -->
                         <button onclick="document.getElementById('ios-install-prompt-overlay').classList.remove('hidden'); document.getElementById('ios-install-prompt').classList.remove('hidden')" class="px-6 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black rounded-xl hover:scale-105 active:scale-95 transition-all shadow-lg flex items-center justify-center gap-2">
                             <i class="fab fa-apple text-base"></i> <?php echo $lang == 'en' ? 'iOS' : 'iOS'; ?>
                         </button>
                     </div>
                 </div>
             </div>
        </div>



        <!-- Upcoming Events Bar -->
        <?php if(!empty($upcoming_events)): ?>
        <div class="mb-10 max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-4 px-2">
                <h2 class="text-xl font-black text-[#0055FF] dark:text-white flex items-center gap-2">
                    <span class="w-2 h-8 bg-[#0055FF] rounded-full"></span>
                    <?php echo $lang == 'en' ? 'Upcoming Events' : 'Yaklaşan Etkinlikler'; ?>
                </h2>
                <a href="events" class="text-xs font-bold text-[#0055FF] hover:text-blue-800 dark:hover:text-white hover:underline transition-colors"><?php echo $t['view_all']; ?></a>
                </a>
            </div>
            
            <!-- Add Event Button -->
            <a href="add_event" class="block w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl text-center shadow-lg shadow-blue-500/20 mb-6 transition-all transform hover:scale-[1.02] active:scale-[0.98]" style="background: linear-gradient(to right, #0055FF, #7c3aed); color: white;">
                <i class="fas fa-plus-circle mr-2"></i><?php echo $lang == 'en' ? 'Add Your Event' : 'Etkinlik Ekle'; ?>
            </a>

            <div class="flex gap-4 overflow-x-auto hide-scrollbar pb-6 px-1">
                <?php foreach($upcoming_events as $index => $event): 
                    $event_date = new DateTime($event['event_date']);
                    $date_formatted = $event_date->format('d');
                    $month_formatted = $event_date->format('M');
                ?>
                <a href="event_detail?id=<?php echo $event['id']; ?>" class="flex-shrink-0 w-52 bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-lg hover:-translate-y-1 transition-all group">
                    <!-- LCP Optimization: Inline styles for critical layout -->
                    <div class="h-24 relative" style="height: 6rem; width: 13rem;">
                        <?php 
                            $img_src = !empty($event['cover_image']) ? $event['cover_image'] : (!empty($event['image_url']) ? $event['image_url'] : '');
                            
                            // Check for optimized _thumb thumbnail
                            if (!empty($img_src) && strpos($img_src, 'uploads/') !== false) {
                                $thumb_path = str_replace('.webp', '_thumb.webp', $img_src);
                                $pure_thumb_path = ltrim($thumb_path, '/');
                                
                                // Robust file check
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $thumb_path) || file_exists(__DIR__ . '/' . $pure_thumb_path)) {
                                    $img_src = $thumb_path;
                                } elseif (strpos($img_src, 'medium_') === false) { 
                                     // Try legacy medium_ if _thumb doesn't exist
                                     $medium_path = str_replace('uploads/', 'uploads/medium_', $img_src);
                                     $pure_medium_path = ltrim($medium_path, '/');
                                     if (file_exists($_SERVER['DOCUMENT_ROOT'] . $medium_path) || file_exists(__DIR__ . '/' . $pure_medium_path)) {
                                         $img_src = $medium_path;
                                     }
                                }
                            }
                            
                            if(!empty($img_src)): 
                                $loading_attr = ($index === 0) ? 'eager' : 'lazy';
                                $priority_attr = ($index === 0) ? 'fetchpriority="high"' : '';
                        ?>
                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" width="300" height="150" loading="<?php echo $loading_attr; ?>" <?php echo $priority_attr; ?> style="width: 100%; height: 100%; object-fit: cover; border-radius: 1rem 1rem 0 0;">
                        <?php else: ?>
                            <div class="w-full h-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:scale-110 transition-transform duration-700" style="width: 100%; height: 100%; border-radius: 1rem 1rem 0 0;">
                                <?php echo heroicon('photo', 'text-slate-300 dark:text-slate-600 w-8 h-8'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="absolute top-3 left-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur px-1.5 py-1 rounded-lg text-center shadow-sm border border-white/20">
                            <p class="text-[10px] font-black text-[#0055FF] leading-none"><?php echo $date_formatted; ?></p>
                            <p class="text-[7px] font-bold text-slate-500 uppercase mt-0.5"><?php echo $month_formatted; ?></p>
                        </div>
                    </div>
                    <div class="p-3">
                        <h3 class="font-bold text-slate-800 dark:text-white mb-1 line-clamp-1 group-hover:text-[#0055FF] transition-colors text-xs"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <div class="flex items-center gap-1.5 text-[9px] text-slate-400 font-bold mb-1.5">
                            <?php echo heroicon('location', 'text-[#0055FF] w-2.5 h-2.5'); ?>
                            <span class="truncate"><?php echo htmlspecialchars($event['venue_name']); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[8px] font-black uppercase text-[#0055FF] bg-blue-50 dark:bg-blue-900/20 px-1.5 py-0.5 rounded-md border border-blue-100 dark:border-blue-500/10">
                                <?php echo htmlspecialchars($event['category']); ?>
                            </span>
                            <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                <?php echo heroicon('clock', 'w-2.5 h-2.5'); ?><?php echo date('H:i', strtotime($event['start_time'])); ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>




        <script>
            // Load and display stories
            async function loadStories() {
                <?php if(!isset($_SESSION['user_id'])): ?>
                return; // Don't load if not logged in
                <?php endif; ?>

                try {
                    const response = await fetch('api/get_stories.php');
                    const data = await response.json();
                    
                    const container = document.getElementById('stories-container');
                    
                    if (!data.success || data.stories.length === 0) {
                        container.innerHTML = `
                            <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="flex flex-col items-center gap-1 cursor-pointer hover:opacity-80 transition-opacity shrink-0" onclick="openStoryUpload()">
                                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-pink-500 to-violet-500 flex items-center justify-center relative p-[2px]">
                                    <img src="<?php echo $_SESSION['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']); ?>" alt="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> Avatar" class="w-full h-full rounded-full object-cover border-2 border-white dark:border-slate-900" loading="lazy">
                                    <div class="absolute bottom-0 right-0 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900">
                                        <?php echo heroicon('plus', 'text-white w-3 h-3'); ?>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300"><?php echo $t['add_story']; ?></span>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-slate-400 text-sm py-4 w-full">
                                <?php echo $t['no_stories']; ?>
                            </div>
                            <?php endif; ?>
                        `;
                        return;
                    }
                    
                    let html = '';
                    
                    // PREPEND ADD STORY BUTTON IS LOGGED IN
                    <?php if(isset($_SESSION['user_id'])): ?>
                    html += `
                        <div class="flex flex-col items-center gap-1 flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity group" onclick="openStoryUpload()">
                            <div class="relative">
                                <div class="w-16 h-16 rounded-full bg-slate-200 dark:bg-slate-700 p-[2px]">
                                    <img src="<?php echo $_SESSION['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']); ?>" class="w-full h-full rounded-full object-cover border-2 border-white dark:border-slate-900" loading="lazy">
                                </div>
                                <div class="absolute bottom-0 right-0 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white dark:border-slate-900">
                                    <?php echo heroicon('plus', 'text-white w-3 h-3'); ?>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300 text-center max-w-[70px] truncate group-hover:text-pink-500 transition-colors"><?php echo $t['add_story']; ?></span>
                        </div>
                    `;
                    <?php endif; ?>
                    
                    data.stories.forEach(user => {
                        const isOwn = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?> == user.user_id;
                        const gradientClass = user.has_unviewed ? 'bg-gradient-to-br from-pink-500 via-purple-500 to-violet-500' : 'bg-slate-300 dark:bg-slate-600';
                        const label = isOwn ? '<?php echo $t['your_story']; ?>' : user.full_name || user.username;
                        
                        html += `
                            <div class="flex flex-col items-center gap-1 flex-shrink-0 cursor-pointer hover:opacity-80 transition-opacity group" onclick="viewStory(${user.user_id})">
                                <div class="relative">
                                    <div class="w-16 h-16 rounded-full ${gradientClass} p-[2px] animate-pulse-slow">
                                        <img src="${user.avatar}" class="w-full h-full rounded-full object-cover border-2 border-white dark:border-slate-900" loading="lazy">
                                    </div>
                                    <!-- Plus icon removed as we have dedicated Add button -->
                                </div>
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300 text-center max-w-[70px] truncate group-hover:text-pink-500 transition-colors">${label}</span>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                } catch (error) {
                    console.error('Error loading stories:', error);
                }
            }

            // Load stories on page load
            document.addEventListener('DOMContentLoaded', loadStories);
            
            // Reload stories every 30 seconds
            setInterval(loadStories, 30000);

            function viewStory(userId) {
                window.location.href = `view_story?user_id=${userId}`;
            }

            let storyUploadFile = null;
            let storyUploadVisibility = 'everyone';

            function closeStoryUploadModal() {
                document.getElementById('story-upload-modal').classList.add('hidden');
                storyUploadFile = null;
            }

            function setStoryVisibility(v) {
                storyUploadVisibility = v;
                const ev = document.getElementById('story-vis-everyone');
                const fv = document.getElementById('story-vis-friends');
                if (!ev || !fv) return;
                ev.classList.remove('border-violet-500', 'bg-violet-500', 'text-white');
                ev.classList.add('border-slate-200', 'dark:border-slate-600', 'text-slate-600', 'dark:text-slate-400');
                fv.classList.remove('border-violet-500', 'bg-violet-500', 'text-white');
                fv.classList.add('border-slate-200', 'dark:border-slate-600', 'text-slate-600', 'dark:text-slate-400');
                if (v === 'everyone') {
                    ev.classList.remove('border-slate-200', 'dark:border-slate-600', 'text-slate-600', 'dark:text-slate-400');
                    ev.classList.add('border-violet-500', 'bg-violet-500', 'text-white');
                } else {
                    fv.classList.remove('border-slate-200', 'dark:border-slate-600', 'text-slate-600', 'dark:text-slate-400');
                    fv.classList.add('border-violet-500', 'bg-violet-500', 'text-white');
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const fileInput = document.getElementById('story-file-input');
                if (fileInput) {
                    fileInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        storyUploadFile = file;
                        const preview = document.getElementById('story-upload-preview');
                        const img = document.getElementById('story-preview-img');
                        const vid = document.getElementById('story-preview-video');
                        const ph = document.getElementById('story-preview-placeholder');
                        preview.classList.remove('hidden');
                        ph.classList.add('hidden');
                        if (file.type.startsWith('image/')) {
                            img.src = URL.createObjectURL(file);
                            img.classList.remove('hidden');
                            if (vid) { vid.classList.add('hidden'); vid.src = ''; }
                        } else if (file.type.startsWith('video/')) {
                            vid.src = URL.createObjectURL(file);
                            vid.classList.remove('hidden');
                            img.classList.add('hidden');
                        }
                        document.getElementById('story-share-btn').disabled = false;
                    });
                }
            });

            async function submitStory() {
                if (!storyUploadFile) return;
                const btn = document.getElementById('story-share-btn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?php echo $lang == "en" ? "Sharing..." : "Paylaşılıyor..."; ?>';
                const formData = new FormData();
                formData.append('media', storyUploadFile);
                formData.append('visibility', storyUploadVisibility);
                try {
                    const response = await fetch('api/add_story.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.success) {
                        closeStoryUploadModal();
                        loadStories();
                        alert('<?php echo $lang == "en" ? "Story added!" : "Hikaye eklendi!"; ?> ✨');
                    } else {
                        alert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>: ' + (data.error || ''));
                    }
                } catch (error) {
                    alert('<?php echo $lang == "en" ? "Upload error!" : "Yükleme hatası!"; ?>');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == "en" ? "Share" : "Paylaş"; ?>';
            }

            function openStoryUpload() {
                storyUploadFile = null;
                storyUploadVisibility = 'everyone';
                const ev = document.getElementById('story-vis-everyone');
                const fv = document.getElementById('story-vis-friends');
                if (ev && fv) {
                    setStoryVisibility('everyone');
                }
                const fi = document.getElementById('story-file-input');
                if (fi) fi.value = '';
                const img = document.getElementById('story-preview-img');
                if (img) { img.classList.add('hidden'); img.src = ''; }
                const vid = document.getElementById('story-preview-video');
                if (vid) { vid.classList.add('hidden'); vid.src = ''; }
                const ph = document.getElementById('story-preview-placeholder');
                if (ph) ph.classList.remove('hidden');
                const sb = document.getElementById('story-share-btn');
                if (sb) sb.disabled = true;
                const mod = document.getElementById('story-upload-modal');
                if (mod) mod.classList.remove('hidden');
            }
        </script>
        

        
        <!-- Post Redirect Message -->
        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 mb-6 flex items-center justify-between gap-3 border border-blue-100 dark:border-blue-900/30 backdrop-blur-sm">
            <p class="text-slate-600 dark:text-slate-300 text-sm font-medium pl-2">
                <i class="fas fa-pen-fancy text-[#0055FF] mr-2"></i>
                <?php echo $lang == 'en' ? 'To create a new post, please visit the feed.' : 'Gönderi paylaşmak için akışa geçiniz.'; ?>
            </p>
            <a href="feed" class="flex-shrink-0 bg-white dark:bg-slate-800 text-[#0055FF] hover:text-blue-700 px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-colors border border-blue-100 dark:border-blue-900/50">
                <?php echo $lang == 'en' ? 'Go to Feed' : 'Akışa Git'; ?> <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <?php endif; ?>

        <!-- Upcoming Events Section -->
        <?php
        $events_sql = "SELECT * FROM events WHERE event_date >= CURDATE() AND status = 'approved' ORDER BY event_date ASC LIMIT 3";
        $events_stmt = $pdo->query($events_sql);
        $upcoming_events = $events_stmt->fetchAll();
        ?>
        
        <div class="mb-10 mt-4">
            <div class="flex items-center justify-between mb-6 px-2">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-pink-500"></i> <?php echo $t['upcoming_events'] ?? ($lang == 'en' ? 'Upcoming Events' : 'Yaklaşan Etkinlikler'); ?>
                </h2>
                <a href="add_event" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/30 hover:scale-105 transition-transform flex items-center gap-2" style="background: linear-gradient(to right, #0055FF, #7c3aed);">
                    <i class="fas fa-plus"></i> <?php echo $lang == 'en' ? 'Add Event' : 'Etkinlik Ekle'; ?>
                </a>
            </div>

            <?php if (count($upcoming_events) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach($upcoming_events as $evt): 
                        $evt_date = date('d.m', strtotime($evt['event_date']));
                        $evt_time = date('H:i', strtotime($evt['start_time']));
                    ?>
                    <a href="events?cat=all" class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-all group flex items-start gap-4">
                        <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-700 overflow-hidden flex-shrink-0">
                            <?php if($evt['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($evt['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fas fa-calendar text-2xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-pink-500 uppercase tracking-wider mb-1 block"><?php echo $evt_date; ?> • <?php echo $evt_time; ?></span>
                            <h3 class="font-bold text-slate-800 dark:text-slate-200 leading-tight mb-1 line-clamp-1"><?php echo htmlspecialchars($evt['title']); ?></h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                                <i class="fas fa-map-marker-alt text-[#0055FF] mr-1"></i> <?php echo htmlspecialchars($evt['event_location']); ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 text-center border border-blue-100 dark:border-blue-900/30 dashed-border">
                    <p class="text-slate-600 dark:text-slate-400 font-medium text-sm">
                        <?php echo $lang == 'en' ? 'No upcoming events.' : 'Yaklaşan etkinlik yok.'; ?>
                    </p>
                    <a href="add_event" class="text-[#0055FF] font-bold text-sm mt-2 inline-block hover:underline">
                        <?php echo $lang == 'en' ? '+ Add the first one!' : '+ İlk etkinliği sen ekle!'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Latest Posts Feed -->
        <?php
        // Ensure post_comments table exists
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

        // Fetch latest posts with comment counts and like status
        $current_user_id = $_SESSION['user_id'] ?? 0;
        
        // Prepare Wall Filter
        $wall_filter_idx = "";
        try {
            $wc = $pdo->query("SHOW COLUMNS FROM posts LIKE 'wall_user_id'");
            if ($wc->rowCount() > 0) {
                $wall_filter_idx = " AND (p.wall_user_id IS NULL OR p.wall_user_id = p.user_id) ";
            }
        } catch(Exception $e){}

        $feed_posts = [];
        try {
            $feed_sql = "SELECT p.*, u.username, u.full_name, u.avatar, u.badge, u.venue_name,
                         (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
                         (SELECT reaction_type FROM post_likes WHERE post_id = p.id AND user_id = ?) as my_reaction,
                         (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as is_saved,
                         (SELECT GROUP_CONCAT(DISTINCT reaction_type) FROM post_likes WHERE post_id = p.id) as top_reactions,
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
                         WHERE 1=1 $wall_filter_idx
                         AND p.deleted_at IS NULL
                         ORDER BY p.created_at DESC LIMIT 3";
            $feed_stmt = $pdo->prepare($feed_sql);
            $feed_stmt->execute([$current_user_id, $current_user_id]);
            $feed_posts = $feed_stmt->fetchAll();
        } catch (PDOException $e) {
            $feed_posts = [];
        }
        ?>
        
        <?php if(count($feed_posts) > 0): ?>
            <div class="mb-10 max-w-4xl mx-auto px-2">
                <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-500"></i> <?php echo $t['latest_posts']; ?>
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <?php foreach($feed_posts as $post): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-all cursor-pointer group" onclick="location.href='post_detail?id=<?php echo $post['id']; ?>'">
                        <div class="flex items-center gap-2 mb-2">
                            <img src="<?php echo !empty($post['avatar']) ? $post['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['full_name']); ?>" class="w-6 h-6 rounded-full object-cover">
                            <div class="overflow-hidden">
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate"><?php echo htmlspecialchars($post['full_name']); ?></p>
                                <p class="text-[10px] text-slate-400 leading-none"><span class="timeago" datetime="<?php echo $post['created_at']; ?>"><?php echo date('d.m H:i', strtotime($post['created_at'])); ?></span></p>
                            </div>
                        </div>
                        
                        <?php if(!empty($post['media_url']) && $post['media_type'] == 'image'): ?>
                            <div class="h-24 w-full rounded-lg overflow-hidden mb-2">
                                <img src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            </div>
                        <?php endif; ?>

                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed mb-2">
                            <?php echo htmlspecialchars(mb_substr($post['content'], 0, 100)); ?>
                        </p>
                        
                        <div class="flex items-center justify-between text-[10px] text-slate-400 border-t border-slate-50 dark:border-slate-700/50 pt-2">
                            <span class="flex items-center gap-1"><i class="fas fa-heart text-pink-400"></i> <?php echo $post['like_count']; ?></span>
                            <span class="flex items-center gap-1"><i class="fas fa-comment text-blue-400"></i> <?php echo $post['comment_count']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                     <a href="feed" class="text-xs font-bold text-blue-500 hover:text-blue-600"><?php echo $lang == 'en' ? 'View all posts in Feed' : 'Tüm gönderileri Akışta gör'; ?> <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Events CTA -->
        <div class="text-center py-12 mt-6 min-h-[250px]">
            <div style="background: linear-gradient(to right, #0055FF, #0033CC);" class="rounded-3xl p-8 text-white shadow-xl max-w-xl mx-auto border border-white/20">
                <i class="fas fa-calendar-alt text-3xl mb-3"></i>
                <h2 class="text-xl font-bold mb-3 tracking-tight"><?php echo $lang == 'en' ? 'Discover Nearby Events' : 'Yakındaki Etkinlikleri Keşfet'; ?></h2>
                <p class="text-sm opacity-90 mb-6 max-w-sm mx-auto leading-relaxed"><?php echo $lang == 'en' ? 'Stay updated with the latest live music, parties, and social gatherings in Kalkan.' : 'Kalkan\'daki en güncel canlı müzik, parti ve sosyal etkinliklerden haberdar olun.'; ?></p>
                <div class="flex justify-center gap-3">
                    <a href="events" style="color: #0055FF !important;" class="bg-white px-6 py-3 rounded-full font-black text-base hover:bg-slate-100 transition-all shadow-lg hover:scale-105 active:scale-95">
                        <i class="fas fa-calendar-alt mr-2"></i><?php echo $t['events']; ?>
                    </a>
                    <a href="map.php" class="bg-white/20 backdrop-blur border border-white/30 text-white px-6 py-3 rounded-full font-black text-base hover:bg-white/30 transition-all shadow-lg hover:scale-105 active:scale-95">
                        <i class="fas fa-hiking mr-2"></i><?php echo $lang == 'en' ? 'Trail Map' : 'Likya Yolu'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- About Us / Contact Teaser -->
        <div class="max-w-xl mx-auto mt-8 mb-12 text-center px-4">
            <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-md rounded-2xl p-6 border border-slate-100 dark:border-slate-700/50 hover:bg-white/80 dark:hover:bg-slate-800/80 transition-all duration-300 group cursor-pointer" onclick="location.href='contact.php'">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 text-left">
                        <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0 text-[#0055FF]">
                            <i class="fas fa-info-circle text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-200"><?php echo $lang == 'en' ? 'About Us' : 'Hakkımızda'; ?></h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?php echo $lang == 'en' ? 'Get to know the team behind ' . site_name() : site_name() . ' ekibini yakindan taniyin'; ?></p>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 group-hover:bg-[#0055FF] group-hover:text-white transition-all">
                        <i class="fas fa-arrow-right text-sm"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topluluk İstatistikleri (8) -->
        <div class="max-w-2xl mx-auto px-4 mb-12">
            <div class="bg-slate-50 dark:bg-slate-900/40 dark:border-slate-700/40 rounded-xl p-4 border border-slate-100">
                <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-500"></i> <?php echo $lang == 'en' ? 'Community Stats' : 'Topluluk Özeti'; ?>
                </h3>
                <div class="flex flex-wrap justify-center gap-6 sm:gap-8">
                    <a href="members.php" class="flex flex-col items-center gap-0.5 hover:opacity-80 transition-opacity">
                        <span class="text-2xl font-black text-slate-800 dark:text-slate-200"><?php echo number_format($community_stats['members']); ?></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Members' : 'Üye'; ?></span>
                    </a>
                    <div class="flex flex-col items-center gap-0.5">
                        <span class="text-2xl font-black text-slate-800 dark:text-slate-200"><?php echo number_format($community_stats['posts_week']); ?></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Posts this week' : 'Bu hafta paylaşım'; ?></span>
                    </div>
                    <a href="directory" class="flex flex-col items-center gap-0.5 hover:opacity-80 transition-opacity">
                        <span class="text-2xl font-black text-slate-800 dark:text-slate-200"><?php echo number_format($community_stats['businesses']); ?></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Businesses' : 'İşletme'; ?></span>
                    </a>
                </div>
            </div>
        </div>

    </main>

    <script>
        function toggleLike(postId, btn) {
            // Double-click protection
            if (btn.disabled) return;
            btn.disabled = true;
            
            const icon = btn.querySelector('i');
            const countSpan = document.getElementById('like-count-' + postId);
            if (!countSpan) {
                btn.disabled = false;
                return;
            }
            
            let count = parseInt(countSpan.innerText) || 0;
            const isLiked = icon.classList.contains('fas');
            
            // Optimistic UI Update
            if (isLiked) {
                icon.classList.replace('fas', 'far');
                btn.classList.remove('text-pink-500');
                countSpan.innerText = Math.max(0, count - 1) || '';
            } else {
                icon.classList.replace('far', 'fas');
                btn.classList.add('text-pink-500');
                icon.classList.add('scale-125');
                setTimeout(() => icon.classList.remove('scale-125'), 200);
                countSpan.innerText = count + 1;
            }

            fetch('api/like_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'post_id=' + postId
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    countSpan.innerText = data.count > 0 ? data.count : '';
                } else {
                    // Revert on error
                    if (isLiked) {
                        icon.classList.replace('far', 'fas');
                        btn.classList.add('text-pink-500');
                    } else {
                        icon.classList.replace('fas', 'far');
                        btn.classList.remove('text-pink-500');
                    }
                    countSpan.innerText = count > 0 ? count : '';
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                // Re-enable after 500ms to prevent rapid clicking
                setTimeout(() => { btn.disabled = false; }, 500);
            });
        }

        function toggleComments(postId) {
            const section = document.getElementById('comments-section-' + postId);
            const isHidden = section.classList.contains('hidden');
            
            if (isHidden) {
                section.classList.remove('hidden');
                loadComments(postId);
            } else {
                section.classList.add('hidden');
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
                        list.innerHTML = `<p class="text-xs text-slate-400 text-center py-2"><?php echo $lang == 'en' ? 'No comments yet. Be the first!' : 'Henüz yorum yok. İlk yorumu sen yap!'; ?></p>`;
                    } else {
                        data.comments.forEach(comment => {
                            const html = `
                                <div class="flex gap-2">
                                    <img src="${comment.avatar}" class="w-8 h-8 rounded-full flex-shrink-0 object-cover" alt="${comment.full_name}">
                                    <div class="flex-1">
                                        <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl rounded-tl-none px-3 py-2 group/comment relative">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">${comment.full_name}</span>
                                            
                                            <div id="comment-content-wrapper-${comment.id}">
                                                <p id="comment-original-${comment.id}" class="text-sm text-slate-600 dark:text-slate-400 mt-1 transition-all duration-300">${comment.content}</p>
                                                <p id="comment-translated-${comment.id}" class="hidden text-sm text-slate-600 dark:text-slate-400 mt-1 italic border-l-2 border-pink-500 pl-2 transition-all duration-300"></p>
                                            </div>

                                            <button onclick="toggleTranslationComment(${comment.id})" 
                                                    class="absolute bottom-2 right-2 text-[10px] text-slate-400 hover:text-pink-500 opacity-0 group-hover/comment:opacity-100 transition-opacity"
                                                    title="<?php echo ($lang == 'en') ? 'Translate' : 'Tercüme et'; ?>"
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
                            <img src="${data.comment.avatar}" class="w-8 h-8 rounded-full flex-shrink-0 object-cover" alt="${data.comment.full_name}">
                            <div class="flex-1">
                                <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl rounded-tl-none px-3 py-2 group/comment relative">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">${data.comment.full_name}</span>
                                    
                                    <div id="comment-content-wrapper-${data.comment.id}">
                                        <p id="comment-original-${data.comment.id}" class="text-sm text-slate-600 dark:text-slate-400 mt-1 transition-all duration-300">${data.comment.content}</p>
                                        <p id="comment-translated-${data.comment.id}" class="hidden text-sm text-slate-600 dark:text-slate-400 mt-1 italic border-l-2 border-pink-500 pl-2 transition-all duration-300"></p>
                                    </div>

                                    <button onclick="toggleTranslationComment(${data.comment.id})" 
                                            class="absolute bottom-2 right-2 text-[10px] text-slate-400 hover:text-pink-500 opacity-0 group-hover/comment:opacity-100 transition-opacity"
                                            title="<?php echo ($lang == 'en') ? 'Translate' : 'Tercüme et'; ?>">
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
                    alert(data.message || '<?php echo $lang == 'en' ? 'Comment could not be added' : 'Yorum eklenemedi'; ?>');
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(err => {
                console.error('Yorum eklenemedi:', err);
                alert('<?php echo $lang == 'en' ? 'An error occurred. Please try again.' : 'Bir hata oluştu. Lütfen tekrar deneyin.'; ?>');
                btn.innerHTML = originalHTML;
            })
            .finally(() => {
                // Re-enable button
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>

    <!-- PWA Install Prompt for Android -->
    <div id="pwa-install-prompt" class="fixed bottom-24 md:bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-[#0055FF] text-white p-5 rounded-2xl shadow-2xl z-50 hidden">
        <button onclick="closePWAPrompt()" class="absolute top-3 right-3 w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors" aria-label="<?php echo $lang == 'en' ? 'Close install prompt' : 'Yükleme penceresini kapat'; ?>">
            <i class="fas fa-times text-xs" aria-hidden="true"></i>
        </button>
        <div class="flex items-start gap-4 mb-4">
            <img src="/logo.jpg" alt="<?php echo htmlspecialchars(site_name()); ?>" class="w-14 h-14 flex-shrink-0 bg-white/10 rounded-2xl p-2">
            <div>
                <h3 class="font-bold text-lg mb-1"><?php echo $lang == 'en' ? 'Install Our App!' : 'Uygulamamızı Yükle!'; ?></h3>
                <p class="text-sm text-white/90"><?php echo $lang == 'en' ? 'Add to your home screen for quick access!' : 'Ana ekranınıza ekleyin, hızlı erişim!'; ?></p>
            </div>
        </div>
        <button onclick="installPWA()" class="w-full bg-white text-[#0055FF] py-3 rounded-xl font-bold hover:bg-white/90 transition-colors flex items-center justify-center gap-2 shadow-lg">
            <i class="fas fa-download"></i>
            <?php echo $lang == 'en' ? 'Install Now' : 'Şimdi Yükle'; ?>
        </button>
    </div>

    <!-- PWA Install Prompt for iOS (Safari) - Modal style for visibility -->
    <div id="ios-install-prompt-overlay" class="fixed inset-0 z-[100] hidden" style="background: rgba(0,0,0,0.5);" onclick="closeIOSPrompt()"></div>
    <div id="ios-install-prompt" class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[calc(100%-2rem)] max-w-md p-6 rounded-3xl shadow-2xl z-[101] hidden animate-[popIn_0.3s_ease-out]" style="background: #fff; color: #1e293b; border: 4px solid #0055FF;" onclick="event.stopPropagation()">
        <div class="absolute top-4 right-4">
            <button onclick="closeIOSPrompt()" class="w-10 h-10 rounded-full flex items-center justify-center transition-colors" style="background: #e2e8f0; color: #475569;" aria-label="<?php echo $lang == 'en' ? 'Close install prompt' : 'Yükleme penceresini kapat'; ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex items-start gap-4 mb-5">
            <div class="w-16 h-16 flex-shrink-0 rounded-2xl p-2 flex items-center justify-center" style="background: #0055FF;">
                <img src="/logo.jpg" alt="<?php echo htmlspecialchars(site_name()); ?>" class="w-full h-full object-contain rounded-xl">
            </div>
            <div class="flex-1 pr-12">
                <h3 class="font-black text-xl mb-2" style="color: #0055FF;"><?php echo $lang == 'en' ? 'Install on iPhone' : 'iPhone\'a Yükle'; ?></h3>
                <p class="text-sm leading-relaxed" style="color: #64748b;">
                    <?php echo $lang == 'en' 
                        ? '1. Tap <strong style="color:#1e293b">Share</strong> <i class="fas fa-share-alt" style="color:#0055FF"></i><br>2. Select <strong style="color:#1e293b">Add to Home Screen</strong>' 
                        : '1. <strong style="color:#1e293b">Paylaş</strong> butonuna bas <i class="fas fa-share-alt" style="color:#0055FF"></i><br>2. <strong style="color:#1e293b">Ana Ekrana Ekle</strong>\'yi seç'; ?>
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="closeIOSPrompt()" class="flex-1 py-3 px-4 rounded-xl font-bold" style="border: 2px solid #cbd5e1; color: #475569; background: #f8fafc;">
                <?php echo $lang == 'en' ? 'Later' : 'Sonra'; ?>
            </button>
            <button type="button" onclick="closeIOSPrompt()" class="flex-1 py-3 px-4 rounded-xl font-bold text-center" style="background: #0055FF; color: white; border: none;">
                <?php echo $lang == 'en' ? 'Got it!' : 'Anladım!'; ?>
            </button>
        </div>
    </div>
    <style>
        @keyframes popIn {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
    </style>
    <script>
        // Service Worker Registration (Force Update V9)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js?v=15', { updateViaCache: 'none' })
                    .then(registration => {
                        console.log('✅ Service Worker Registered (V14)');
                        
                        // Force check for updates
                        registration.update();
                        
                        // Initialize push notifications for logged-in users
                        <?php if(isset($_SESSION['user_id'])): ?>
                        initPushNotifications(registration);
                        <?php endif; ?>
                    })
                    .catch(err => console.error('❌ Service Worker hatası:', err));
            });
        }
        
        // Push Notification Subscription
        const VAPID_PUBLIC_KEY = '<?php echo addslashes(env_value("VAPID_PUBLIC_KEY", "")); ?>';
        const PUSH_VERSION = 'v3-vapid-update'; // Change this to force re-subscription for all users

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
        
        async function initPushNotifications(registration) {
            try {
                // Version Check for Force Update
                const savedVersion = localStorage.getItem('push_version');
                const existingSubscription = await registration.pushManager.getSubscription();

                if (existingSubscription && savedVersion !== PUSH_VERSION) {
                    console.log('🔄 Eski abonelik temizleniyor (Versiyon Güncellemesi)...');
                    await existingSubscription.unsubscribe();
                    localStorage.setItem('push_version', PUSH_VERSION);
                    subscribeToPush(registration);
                    return;
                }

                if (existingSubscription) {
                     // Sync existing
                     await sendSubscriptionToServer(existingSubscription);
                     return;
                }
                
                // Normal flow...
                if (localStorage.getItem('push-dismissed') && Date.now() - parseInt(localStorage.getItem('push-dismissed')) < 7 * 24 * 60 * 60 * 1000) {
                    return; 
                }
                
                // Request permission
                if (Notification.permission === 'default') {
                    setTimeout(async () => {
                        const permission = await Notification.requestPermission();
                        if (permission === 'granted') {
                            subscribeToPush(registration);
                        } else {
                            localStorage.setItem('push-dismissed', Date.now());
                        }
                    }, 5000); 
                } else if (Notification.permission === 'granted') {
                    subscribeToPush(registration);
                }
            } catch (err) {
                console.error('Push init error:', err);
            }
        }
        
        async function subscribeToPush(registration) {
            try {
                // First unsubscribe any existing broken subscription
                const oldSub = await registration.pushManager.getSubscription();
                if (oldSub) {
                    await oldSub.unsubscribe();
                }

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
                });
                
                await sendSubscriptionToServer(subscription);
                
            } catch (error) {
                console.error('❌ Push subscription hatası:', error);
                // alert('Push bildirim hatası: ' + error.message); // Debug
            }
        }

        async function sendSubscriptionToServer(subscription) {
            try {
                const response = await fetch('/api/subscribe_push.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ subscription: subscription.toJSON() })
                });
                
                const data = await response.json();
                if (data.success) {
                    console.log('✅ Push notification sunucuya kaydedildi');
                } else {
                    console.error('❌ Sunucu kayıt hatası:', data.error);
                    
                    // If error implies mismatch, try to re-subscribe
                    if (data.error && (data.error.includes('dummy') || true)) { // Always retry for now if server fails
                         // Maybe force re-subscribe next time?
                    }
                }
            } catch (e) {
                console.error('Fetch error:', e);
            }
        }

        // Detect Mobile & OS & In-App Browsers
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
        const isAndroid = /Android/i.test(navigator.userAgent);
        const isInStandaloneMode = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        
        // Detect Instagram and Facebook In-App Browsers
        const ua = navigator.userAgent || navigator.vendor || window.opera;
        const isInAppBrowser = (ua.indexOf("Instagram") > -1) || (ua.indexOf("FBAN") > -1) || (ua.indexOf("FBAV") > -1);

        let deferredPrompt;
        const androidPrompt = document.getElementById('pwa-install-prompt');
        const iosPrompt = document.getElementById('ios-install-prompt');

        // Android: beforeinstallprompt event
        // Android: beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent prompt in In-App Browsers or if already dismissed
            if (isInAppBrowser) {
                console.log('🚫 PWA prompt suppressed (In-App Browser detected)');
                e.preventDefault();
                return;
            }

            console.log('📱 PWA install prompt hazır');
            e.preventDefault();
            deferredPrompt = e;
            
            /* DISABLED AS PER REQUEST (iOS ONLY)
            if (!localStorage.getItem('pwa-dismissed') && !isInStandaloneMode) {
                setTimeout(() => {
                    androidPrompt.classList.remove('hidden');
                    console.log('✅ Android popup gösteriliyor');
                }, 2000);
            }
            */
        });

        // iOS: Show manual instructions (Skip if In-App Browser)
        if (isIOS && isMobile && !isInStandaloneMode && !localStorage.getItem('ios-pwa-dismissed') && !isInAppBrowser) {
            setTimeout(() => {
                const overlay = document.getElementById('ios-install-prompt-overlay');
                if (overlay) overlay.classList.remove('hidden');
                iosPrompt.classList.remove('hidden');
                console.log('✅ iOS popup gösteriliyor');
            }, 2000);
        }

        // Android: Show prompt for devices that don't trigger beforeinstallprompt (Skip if In-App Browser)
        /* DISABLED AS PER REQUEST (iOS ONLY)
        if (isAndroid && isMobile && !isInStandaloneMode && !localStorage.getItem('pwa-dismissed') && !isInAppBrowser) {
            setTimeout(() => {
                // Fallback for Android if beforeinstallprompt doesn't fire
                if (!deferredPrompt && androidPrompt.classList.contains('hidden')) {
                    androidPrompt.classList.remove('hidden');
                    console.log('✅ Android fallback popup gösteriliyor');
                }
            }, 3000);
        }
        */

        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    console.log('Kullanıcı seçimi:', choiceResult.outcome);
                    deferredPrompt = null;
                    androidPrompt.classList.add('hidden');
                });
            } else {
                // Fallback: Show generic install instructions
                alert('<?php echo $lang == 'en' ? 'Use the "Add to Home Screen" option from your browser menu.' : 'Tarayıcınızın menüsünden "Ana Ekrana Ekle" seçeneğini kullanın.'; ?>');
            }
        }

        function closePWAPrompt() {
            androidPrompt.classList.add('hidden');
            localStorage.setItem('pwa-dismissed', Date.now());
        }

        function closeIOSPrompt() {
            const overlay = document.getElementById('ios-install-prompt-overlay');
            if (overlay) overlay.classList.add('hidden');
            iosPrompt.classList.add('hidden');
            localStorage.setItem('ios-pwa-dismissed', Date.now());
        }

        // Auto-show again after 7 days
        const dismissedTime = parseInt(localStorage.getItem('pwa-dismissed') || '0');
        const iosDismissedTime = parseInt(localStorage.getItem('ios-pwa-dismissed') || '0');
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        const now = Date.now();
        
        if (now - dismissedTime > sevenDays) {
            localStorage.removeItem('pwa-dismissed');
        }
        if (now - iosDismissedTime > sevenDays) {
            localStorage.removeItem('ios-pwa-dismissed');
        }



        // Request push notification permission
        <?php if(isset($_SESSION['user_id'])): ?>
        async function requestPushPermission() {
            console.log('🔔 Checking push notification support...');
            
            if (!('Notification' in window)) {
                console.log('❌ Push notifications not supported');
                return;
            }
            
            console.log('Current permission:', Notification.permission);
            
            if (Notification.permission === 'granted') {
                console.log('✅ Permission already granted');
                return;
            }
            
            if (Notification.permission === 'denied') {
                console.log('❌ Permission denied by user');
                return;
            }

            // If default (not asked yet), show prompt after 3 seconds
            if (Notification.permission === 'default') {
                console.log('📱 Will request permission in 3 seconds...');
                setTimeout(async () => {
                    try {
                        const permission = await Notification.requestPermission();
                        console.log('User choice:', permission);
                        
                        if (permission === 'granted') {
                            console.log('✅ Permission granted! Showing test notification...');
                            if ('serviceWorker' in navigator) {
                                navigator.serviceWorker.ready.then(function(registration) {
                                    registration.showNotification('<?php echo addslashes(site_name()); ?> Notification', {
                                        body: '<?php echo $lang == "en" ? "Notifications are enabled!" : "Bildirimler aktif!"; ?>',
                                        icon: '/icon-192.png',
                                        vibrate: [200, 100, 200]
                                    });
                                });
                            }
                        }
                    } catch (error) {
                        console.error('Permission request failed:', error);
                    }
                }, 3000);
            }
        }

        // Subscribe to push notifications (for mobile PWA)
        async function subscribeToPush() {
            try {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    console.log('Push subscription not supported');
                    return;
                }
                
                const registration = await navigator.serviceWorker.ready;
                console.log('Service Worker ready, attempting subscription...');
                
                // For now, skip VAPID key requirement (will add later)
                // const subscription = await registration.pushManager.subscribe({
                //     userVisibleOnly: true,
                //     applicationServerKey: urlBase64ToUint8Array('<?php echo getenv("VAPID_PUBLIC_KEY") ?: ""; ?>')
                // });

                // Send subscription to server
                // await fetch('api/subscribe_push.php', {
                //     method: 'POST',
                //     headers: { 'Content-Type': 'application/json' },
                //     body: JSON.stringify({ subscription })
                // });

                console.log('✅ Ready for push notifications');
            } catch (error) {
                console.error('Push subscription failed:', error);
            }
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        // Request push permission immediately on page load (if logged in)
        requestPushPermission();

        <?php endif; ?>

        // Smart Translate System
        async function toggleTranslation(postId) {
            const originalP = document.getElementById('post-original-' + postId);
            const translatedP = document.getElementById('post-translated-' + postId);
            const btn = document.getElementById('trans-btn-' + postId);
            const btnText = document.getElementById('trans-text-' + postId); // May be null if icon-only
            
            // Current user language (Synced with server-side preference)
            const currentLang = '<?php echo $lang; ?>';
            
            // Check state
            const isShowingOriginal = !originalP.classList.contains('hidden');
            
            if (isShowingOriginal) {
                // -> Switch to Translation
                
                // If not loaded yet, fetch it
                if (!translatedP.innerHTML.trim()) {
                    const originalText = originalP.innerText.trim();
                    const oldBtnText = btnText ? btnText.innerText : null;
                    const oldIcon = btn ? btn.querySelector('i') : null;
                    
                    if (oldIcon) oldIcon.className = 'fas fa-circle-notch fa-spin text-lg';
                    
                    try {
                        const formData = new FormData();
                        formData.append('text', originalText);
                        formData.append('target_lang', currentLang);
                        
                        const response = await fetch('api/translate.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            translatedP.innerText = data.translated_text;
                            if (oldIcon) oldIcon.className = 'fas fa-language text-lg';
                        } else {
                            alert(data.error || 'Translation error');
                            if (btnText) btnText.innerText = oldBtnText;
                            if (oldIcon) oldIcon.className = 'fas fa-language text-lg';
                            return;
                        }
                    } catch (error) {
                        console.error('Translation failed:', error);
                        if (btnText) btnText.innerText = oldBtnText;
                        if (oldIcon) oldIcon.className = 'fas fa-language text-lg';
                        return;
                    }
                }
                
                // Animate Swap
                originalP.style.opacity = '0';
                setTimeout(() => {
                    originalP.classList.add('hidden');
                    translatedP.classList.remove('hidden');
                    // Trigger reflow
                    void translatedP.offsetWidth; 
                    translatedP.style.opacity = '1';
                }, 300);
                
                if (btnText) btnText.innerText = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
                if (btn) btn.title = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
                
            } else {
                // -> Switch back to Original
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
            
            if (isShowingOriginal) { // Show Translation
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
                    } catch (error) {
                        console.error(error);
                        alert('Translation error');
                        btnText.innerText = '';
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
                
                btnText.innerText = currentLang === 'en' ? 'Original' : 'Orijinal';
                
            } else { // Show Original
                translatedP.style.opacity = '0';
                setTimeout(() => {
                    translatedP.classList.add('hidden');
                    originalP.classList.remove('hidden');
                    void originalP.offsetWidth;
                    originalP.style.opacity = '1';
                }, 300);
                
                btnText.innerText = ''; // Return to icon only to save space
            }
        }

        /* REACTION SYSTEM JS */
        async function sendReaction(postId, type) {
            event.stopPropagation(); // Prevent parent clicks
            
            // Check if user is logged in
            <?php if (!isset($_SESSION['user_id'])): ?>
            showLoginPopup();
            return;
            <?php endif; ?>
            const btn = document.getElementById('like-btn-' + postId);
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
            btn.className = `flex items-center gap-2 transition-colors text-sm ${colors[type] || 'text-pink-500'}`;
            icon.className = `${icons[type] || 'fas fa-heart'} transform transition-transform duration-300 text-xl scale-125`;
            setTimeout(() => icon.classList.remove('scale-125'), 200);

            // Play Lottie Animation for Like/Love
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
                    if (!data.reacted) {
                        // Reset if toggled off
                        if (!data.reaction) {
                             resetLikeUI(postId);
                        }
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

        function toggleLike(postId, btn) {
            // Main click defaults to 'like'
            sendReaction(postId, 'like');
        }

        /* Post Management Logic */
        function togglePostMenu(postId) {
            const menu = document.getElementById('post-menu-' + postId);
            if(menu) menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.fa-ellipsis-h') && !event.target.closest('button')) {
                // Fix: Select only dropdowns, ignore containers
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
                    <button onclick="cancelEdit(${postId})" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </button>
                    <button onclick="savePost(${postId})" class="px-3 py-1.5 text-xs font-bold text-white bg-pink-500 hover:bg-pink-600 rounded-lg shadow-lg shadow-pink-500/30 transition-all">
                        <?php echo $lang == 'en' ? 'Save Changes' : 'Kaydet'; ?>
                    </button>
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
                        <p id="post-original-${postId}" class="text-slate-800 dark:text-slate-200 leading-relaxed text-base transition-all duration-300">
                            ${newVal.replace(/\n/g, '<br>')}
                        </p>
                        <p id="post-translated-${postId}" class="hidden text-slate-800 dark:text-slate-200 leading-relaxed text-base italic border-l-4 border-pink-500 pl-4 transition-all duration-300">
                        </p>
                    `;
                } else {
                    alert(data.error || 'Update failed');
                }
            } catch(e) { console.error(e); }
        }
    </script>

    <!-- Footer -->
    <!-- Footer - Removed as requested
    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 mt-20">
        <div class="container mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                
                <div class="col-span-1 md:col-span-2">
                    <a href="index" class="text-2xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500 tracking-tight mb-4 inline-block">
                        Kalkan<span class="text-slate-900 dark:text-white">Social</span>
                    </a>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                        <?php echo $lang == 'en' 
                            ? 'Discover live music, parties and events in Kalkan. Join our community and create unforgettable memories.' 
                            : 'Kalkan\'daki canlı müzik, parti ve etkinlikleri keşfedin. Topluluğumuza katılın ve unutulmaz anılar biriktirin.'; ?>
                    </p>
                    <div class="flex gap-4">
                        <a href="https://instagram.com/devrimdnz07" target="_blank" aria-label="Instagram" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-gradient-to-r hover:from-pink-500 hover:to-violet-500 hover:text-white transition-all">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" aria-label="Facebook" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-gradient-to-r hover:from-pink-500 hover:to-violet-500 hover:text-white transition-all">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="Twitter" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-gradient-to-r hover:from-pink-500 hover:to-violet-500 hover:text-white transition-all">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>

                
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-slate-900 dark:text-white mb-4">
                        <?php echo $lang == 'en' ? 'Quick Links' : 'Hızlı Linkler'; ?>
                    </h3>
                    <ul class="space-y-2">
                        <li><a href="index" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors"><?php echo $t['home']; ?></a></li>
                        <li><a href="events" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors"><?php echo $t['events']; ?></a></li>
                        <li><a href="feed" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors"><?php echo $t['feed']; ?></a></li>
                        <li><a href="members" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors"><?php echo $lang == 'en' ? 'Members' : 'Üyeler'; ?></a></li>
                    </ul>
                </div>

                
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-slate-900 dark:text-white mb-4">
                        <?php echo $lang == 'en' ? 'Legal & Support' : 'Yasal & Destek'; ?>
                    </h3>
                    <ul class="space-y-2">
                        <li><a href="privacy" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors"><?php echo $lang == 'en' ? 'Privacy Policy' : 'Gizlilik Politikası'; ?></a></li>
                        <li><a href="terms" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors"><?php echo $lang == 'en' ? 'Terms of Service' : 'Kullanım Şartları'; ?></a></li>
                        <li><a href="kvkk" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors">KVKK</a></li>
                        <li><a href="contact" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors"><?php echo $lang == 'en' ? 'Contact Us' : 'İletişim'; ?></a></li>
                    </ul>
                </div>
            </div>

            
            <div class="border-t border-slate-200 dark:border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-slate-500 dark:text-slate-500">
                    © <?php echo date('Y'); ?> <?php echo $t['site_name']; ?>. <?php echo $lang == 'en' ? 'All rights reserved.' : 'Tüm hakları saklıdır.'; ?>
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-500">
                    Made with <span class="text-red-500">❤</span> by 
                    <a href="https://kasdigitalsolutions.com" target="_blank" class="font-bold text-pink-500 hover:text-pink-400 transition-colors">KAS Digital Solutions</a>
                </p>
            </div>
        </div>
    </footer>
    -->

    <!-- Share Modal -->
    <div id="share-modal" class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-black/50 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl p-6 shadow-2xl transform scale-95 transition-transform duration-300 border border-slate-100 dark:border-slate-800">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                 <h3 class="text-xl font-bold dark:text-white"><?php echo $lang == 'en' ? 'Share' : 'Paylaş'; ?></h3>
                 <button onclick="closeShareModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                     <i class="fas fa-times"></i>
                 </button>
            </div>
            
            <!-- Options -->
            <div class="space-y-3" id="share-options">
                 <button onclick="showShareInput()" class="w-full flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group border border-slate-200 dark:border-slate-700 hover:border-blue-200 dark:hover:border-blue-500/30">
                     <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[#0055FF] group-hover:scale-110 transition-transform">
                         <i class="fas fa-pen text-lg"></i>
                     </div>
                     <div class="text-left">
                        <span class="block font-bold text-slate-700 dark:text-slate-200 text-lg"><?php echo $lang == 'en' ? 'Share on My Profile' : 'Kendi Profilimde Paylaş'; ?></span>
                        <span class="text-xs text-slate-400"><?php echo $lang == 'en' ? 'Share with your followers' : 'Takipçilerinle paylaş'; ?></span>
                     </div>
                 </button>
                 
                 <button onclick="copyLink()" class="w-full flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group border border-slate-200 dark:border-slate-700 hover:border-blue-200 dark:hover:border-blue-500/30">
                     <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                         <i class="fas fa-link text-lg"></i>
                     </div>
                     <div class="text-left">
                        <span class="block font-bold text-slate-700 dark:text-slate-200 text-lg"><?php echo $lang == 'en' ? 'Copy Link' : 'Bağlantıyı Kopyala'; ?></span>
                        <span class="text-xs text-slate-400"><?php echo $lang == 'en' ? 'Get the link to share' : 'Paylaşmak için linki al'; ?></span>
                     </div>
                 </button>
            </div>
            
            <!-- Input Layer (Hidden initially) -->
            <div id="share-input-layer" class="hidden">
                 <div class="flex items-center gap-3 mb-4 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                    <img src="<?php echo $_SESSION['avatar'] ?? 'https://ui-avatars.com/api/?name=User'; ?>" class="w-10 h-10 rounded-full">
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $_SESSION['full_name'] ?? 'User'; ?></span>
                 </div>
                 
                 <textarea id="share-caption" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 min-h-[100px] focus:outline-none focus:ring-2 focus:ring-pink-500 dark:text-white resize-none" placeholder="<?php echo $lang == 'en' ? 'Write something about this post...' : 'Bu gönderi hakkında bir şeyler yaz...'; ?>"></textarea>
                 
                 <div class="flex gap-3 mt-4">
                     <button onclick="closeShareModal()" class="flex-1 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"><?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?></button>
                     <button onclick="submitShare()" class="flex-1 bg-[#0055FF] hover:bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-[#0055FF]/30 transition-all transform active:scale-95">
                        <?php echo $lang == 'en' ? 'Share' : 'Paylaş'; ?>
                     </button>
                 </div>
            </div>
        </div>
    </div>

    <!-- Mentions -->
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
             // Validations?
             
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
             const link = window.location.origin + '/feed#post-' + currentSharePostId;
             navigator.clipboard.writeText(link).then(() => {
                 closeShareModal();
                 alert('Bağlantı kopyalandı! 📋');
             });
        }
        
        // Close on backdrop
        document.getElementById('share-modal').addEventListener('click', function(e) {
            if (e.target === this) closeShareModal();
        });
        /* Save Post Logic */
        async function toggleSave(postId, btn) {
            btn.disabled = true;
            const icon = btn.querySelector('i');
            const isSaved = icon.classList.contains('fas');
            
            // Optimistic Update
            if(isSaved) {
                icon.classList.replace('fas', 'far');
                btn.classList.remove('text-yellow-500');
                btn.classList.add('text-slate-400');
            } else {
                icon.classList.replace('far', 'fas');
                btn.classList.remove('text-slate-400');
                btn.classList.add('text-yellow-500');
                icon.classList.add('scale-125');
                setTimeout(()=>icon.classList.remove('scale-125'), 300);
            }

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_save');
                formData.append('post_id', postId);
                const response = await fetch('api/save_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                if(data.status === 'success') {
                    if(data.is_saved) showSavedToast(postId);
                } else {
                    if(isSaved) {
                       icon.classList.replace('far', 'fas'); btn.classList.add('text-yellow-500');
                    } else {
                       icon.classList.replace('fas', 'far'); btn.classList.remove('text-yellow-500');
                    }
                }
            } catch(e) { console.error(e); }
            btn.disabled = false;
        }

        function showSavedToast(postId) {
            const existing = document.getElementById('saved-toast');
            if(existing) existing.remove();
            const toast = document.createElement('div');
            toast.id = 'saved-toast';
            toast.className = 'fixed bottom-24 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-4 z-[100] animate-in slide-in-from-bottom-5 fade-in duration-300 min-w-[300px] border border-slate-700/50';
            toast.innerHTML = `
                <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-check text-white text-lg"></i></div>
                <div class="flex-1">
                    <p class="font-bold text-sm mb-0.5"><?php echo $lang == 'en' ? 'Saved to Private Items' : 'Kaydedilenlere Eklendi'; ?></p>
                    <button onclick="promptAddToCollection(\${postId})" class="text-xs text-yellow-400 font-bold hover:underline"><?php echo $lang == 'en' ? 'Add to Collection' : 'Koleksiyona Ekle'; ?></button>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-500 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
            `;
            document.body.appendChild(toast);
            setTimeout(() => { if(toast && document.body.contains(toast)) { toast.classList.add('opacity-0', 'translate-y-4'); setTimeout(()=>toast.remove(), 300); } }, 5000);
        }

        async function promptAddToCollection(postId) {
             const toast = document.getElementById('saved-toast'); if(toast) toast.remove();
             try {
                 const formData = new FormData(); formData.append('action', 'get_collections');
                 const res = await fetch('api/save_post.php', { method: 'POST', body: formData });
                 const data = await res.json();
                 if(data.status === 'success') showCollectionModal(postId, data.collections);
             } catch(e) { console.error(e); }
        }

        function showCollectionModal(postId, collections) {
            let listHtml = '';
            if(collections.length > 0) {
                collections.forEach(col => {
                     listHtml += `
                        <button onclick="addToCollection(\${postId}, \${col.id})" class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl flex items-center gap-3 transition-colors mb-1">
                            <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-500"><i class="fas fa-folder"></i></div>
                            <span class="font-bold text-slate-700 dark:text-slate-200">\${col.name}</span>
                        </button>`;
                });
            } else { listHtml = `<p class="text-center text-slate-500 py-4 italic"><?php echo $lang == 'en' ? 'No collections found' : 'Koleksiyon bulunamadı'; ?></p>`; }
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200';
            modal.onclick = (e) => { if(e.target === modal) modal.remove(); };
            modal.innerHTML = `
                <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2rem] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200 relative">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="font-bold text-lg dark:text-white"><?php echo $lang == 'en' ? 'Add to Collection' : 'Koleksiyona Ekle'; ?></h3>
                         <button onclick="this.closest('.fixed').remove()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="p-4 max-h-[50vh] overflow-y-auto custom-scrollbar">\${listHtml}</div>
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                        <button onclick="location.href='saved'" class="w-full py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-xl shadow-lg shadow-yellow-500/20 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-plus"></i> <?php echo $lang == 'en' ? 'New Collection' : 'Yeni Koleksiyon Oluştur'; ?>
                        </button>
                    </div>
                </div>`;
            document.body.appendChild(modal);
        }

        async function addToCollection(postId, collectionId) {
             try {
                 const formData = new FormData(); formData.append('action', 'add_to_collection'); formData.append('post_id', postId); formData.append('collection_id', collectionId);
                 const res = await fetch('api/save_post.php', { method: 'POST', body: formData });
                 const data = await res.json();
                 if(data.status === 'success') { document.querySelectorAll('.fixed.z-\\[110\\]').forEach(m => m.remove()); alert('<?php echo $lang == "en" ? "Added to collection!" : "Koleksiyona eklendi!"; ?>'); }
             } catch(e) { console.error(e); }
        }
    </script>

<!-- Stylish Login Popup Modal -->
<div id="login-popup-modal" class="fixed inset-0 z-[200] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeLoginPopup()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm px-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-100 dark:border-slate-700 text-center animate-[popup_0.3s_ease-out]">
            <div class="w-20 h-20 bg-[#0055FF] rounded-full mx-auto mb-6 flex items-center justify-center shadow-lg shadow-[#0055FF]/30">
                <i class="fas fa-heart text-white text-3xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2"><?php echo $lang == 'en' ? 'Join the Community!' : 'Topluluğa Katılın!'; ?></h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6 text-sm leading-relaxed">
                <?php echo $lang == 'en' ? 'Log in to like posts, leave comments, and connect with the Kalkan community.' : 'Gönderileri beğenmek, yorum yapmak ve Kalkan topluluğu ile bağlantı kurmak için giriş yapın.'; ?>
            </p>
            <div class="flex flex-col gap-3">
                <a href="login" class="w-full py-4 bg-[#0055FF] text-white font-bold rounded-2xl hover:shadow-lg hover:shadow-[#0055FF]/30 transition-all flex items-center justify-center gap-2">
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

// Hide PWA Install Section if already in standalone mode
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    const pwaSection = document.getElementById('pwa-install-section');
    if (pwaSection) pwaSection.style.display = 'none';
}
</script>

<?php 
// Welcome Tour for new users
if (isset($_SESSION['show_welcome_tour']) && $_SESSION['show_welcome_tour'] === true) {
    include 'includes/welcome_tour.php';
}
?>

</body>
</html>



