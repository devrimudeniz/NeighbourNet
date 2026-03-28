<?php
require_once 'includes/db.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/visitor_tracker.php';
require_once 'includes/lang.php';
require_once 'includes/ui_components.php';
require_once 'includes/icon_helper.php';
require_once 'includes/site_settings.php';
$user_role = $_SESSION['badge'] ?? '';

// Update Last Seen
if (isset($_SESSION['user_id'])) {
    // Optimization: Only update last_seen every 5 minutes
    if (!isset($_SESSION['last_seen_update']) || (time() - $_SESSION['last_seen_update'] > 300)) {
        $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$_SESSION['user_id']]);
        $_SESSION['last_seen_update'] = time();
    }
    
    // Combined unread messages + notifications (single DB round-trip)
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0) AS unread,
        (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0) AS unread_notifs
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $unread = $row['unread'] ?? 0;
    $unread_notifs = $row['unread_notifs'] ?? 0;
} else {
    $unread = 0;
    $unread_notifs = 0;
}
?>
<!-- Header / Navigation -->
<header class="fixed top-0 left-0 w-full z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-white/20 dark:border-slate-800/50 shadow-sm transition-all duration-300">
    <div class="container mx-auto px-4 sm:px-6 py-1 flex justify-between items-center h-20">
        <a href="index" class="flex items-center gap-1 sm:gap-2 shrink-0 no-underline">
            <span class="text-2xl sm:text-3xl font-black tracking-tighter text-slate-900 dark:text-white">
                <?php echo htmlspecialchars(site_name()); ?>
            </span>
        </a>

        <!-- Smart Search Bar (Desktop) -->
        <div class="hidden md:block relative w-80 lg:w-96 mx-4 z-40">
            <div class="relative group">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-pink-500 transition-colors pointer-events-none"></i>
                <input type="text" id="global-search" 
                    placeholder="<?php echo $lang == 'en' ? 'Search members...' : 'Kullanıcı ara...'; ?>" 
                    class="w-full bg-slate-100 dark:bg-slate-800/50 border-none rounded-2xl py-2.5 pl-16 pr-4 text-sm font-bold text-slate-700 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-pink-500/50 transition-all outline-none" autocomplete="off">
            </div>
            
            <!-- Smart Dropdown -->
            <div id="search-dropdown" class="absolute top-full left-0 w-full mt-2 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden hidden transform transition-all origin-top">
                <div id="search-results" class="max-h-[400px] overflow-y-auto custom-scrollbar">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
        
        <!-- Nav (Desktop/Tablet) -->
        <nav class="hidden md:flex items-center gap-1 lg:gap-2 font-bold text-sm">
            <a href="index" class="nav-haptic px-3 lg:px-4 py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-[#0055FF] hover:bg-slate-50 dark:hover:bg-slate-800 transition-all font-bold"><?php echo $lang == 'en' ? 'Home' : 'Ana Sayfa'; ?></a>
            <a href="feed" class="nav-haptic px-3 lg:px-4 py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-[#0055FF] hover:bg-slate-50 dark:hover:bg-slate-800 transition-all font-bold"><?php echo $t['feed']; ?></a>
            
            <!-- Discovery Dropdown -->
            <div class="relative group px-1">
                <button class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-[#0055FF] hover:bg-slate-50 dark:hover:bg-slate-800 transition-all font-bold">
                    <span><?php echo $lang == 'en' ? 'Explore' : 'Keşfet'; ?></span>
                    <?php echo heroicon('chevron_down', 'text-xs w-2.5 h-2.5 group-hover:rotate-180 transition-transform'); ?>
                </button>
                <div class="absolute top-full left-0 w-56 pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all z-50">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 p-2 overflow-hidden">
                        <a href="events" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-300 transition-colors">
                            <?php echo heroicon('calendar', 'w-5 w-5 text-violet-500'); ?>
                            <span class="font-bold text-sm"><?php echo $t['events']; ?></span>
                        </a>
                        <a href="news" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-300 transition-colors">
                            <i class="fas fa-newspaper w-5 text-blue-500"></i>
                            <span class="font-bold text-sm"><?php echo $lang == 'en' ? 'News' : 'Haberler'; ?></span>
                        </a>
                        <a href="members" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-300 transition-colors">
                            <?php echo heroicon('users', 'w-5 w-5 text-emerald-500'); ?>
                            <span class="font-bold text-sm"><?php echo $lang == 'en' ? 'Members' : 'Üyeler'; ?></span>
                        </a>
                        <a href="groups" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-300 transition-colors">
                            <?php echo heroicon('group', 'w-5 w-5 text-orange-500'); ?>
                            <span class="font-bold text-sm"><?php echo $t['groups']; ?></span>
                        </a>
                        <a href="photo_contest" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-300 transition-colors">
                            <i class="fas fa-camera w-5 text-pink-500"></i>
                            <span class="font-bold text-sm">Kalkan Hafızası</span>
                        </a>
                        <a href="community_support" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-300 transition-colors">
                            <i class="fas fa-hand-holding-heart w-5 text-green-500"></i>
                            <span class="font-bold text-sm"><?php echo $lang == 'en' ? 'Community Support' : 'Askıda İyilik'; ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Services Dropdown -->
            <a href="services" class="nav-haptic px-3 lg:px-4 py-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-[#0055FF] hover:bg-slate-50 dark:hover:bg-slate-800 transition-all font-bold"><?php echo $lang == 'en' ? 'Services' : 'Hizmetler'; ?></a>


            <?php 
            // Business Panel Link
            if (isset($_SESSION['badge']) && in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])): 
            ?>
                <a href="business_panel.php" class="flex items-center gap-2 px-3 lg:px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-black text-xs hover:scale-105 transition-all relative shadow-lg">
                    <i class="fas fa-store w-3 h-3"></i>
                    <span class="hidden lg:inline"><?php echo $lang == 'en' ? 'Business Panel' : 'İşletme Paneli'; ?></span>
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['badge']) && $_SESSION['badge'] === 'founder'): 
                // Admin Notifications for Header
                $header_pending = $pdo->query("SELECT (SELECT COUNT(*) FROM verification_requests WHERE status = 'pending') + (SELECT COUNT(*) FROM properties WHERE status = 'pending')")->fetchColumn();
            ?>
                <a href="admin/index.php" class="flex items-center gap-2 px-3 lg:px-4 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-xs hover:scale-105 transition-all relative">
                    <?php echo heroicon('shield', 'w-3 h-3'); ?>
                    <span class="hidden lg:inline"><?php echo $t['admin_panel']; ?></span>
                    <?php if($header_pending > 0): ?>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-[#0055FF] text-[8px] flex items-center justify-center rounded-full border-2 border-white dark:border-slate-900"><?php echo $header_pending; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3 shrink-0">

            
            <!-- Create Post Button (Desktop/Tablet) -> Direct Link for better experience -->
            <?php if(isset($_SESSION['user_id'])): ?>
            <a href="create_post_page.php" class="hidden md:flex items-center gap-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-4 py-2 rounded-xl font-bold text-xs hover:scale-105 transition-all shadow-lg shadow-slate-900/10 active:scale-95">
                <?php echo heroicon('plus', 'w-4 h-4'); ?>
                <span class="hidden lg:inline"><?php echo $lang == 'en' ? 'Create' : 'Paylaş'; ?></span>
            </a>
            <?php endif; ?>

            <!-- Duty Pharmacy Button - Desktop only, moved to hamburger menu on mobile -->
            <a href="duty_pharmacy" class="hidden sm:flex items-center gap-2 bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-full border border-red-200 dark:border-red-800 hover:scale-105 transition-transform" title="<?php echo $lang == 'en' ? 'Duty Pharmacy' : 'Nöbetçi Eczane'; ?>">
                <?php echo heroicon('pills', 'text-red-600 dark:text-red-400 w-4 h-4'); ?>
                <span class="hidden lg:inline text-[10px] sm:text-xs font-bold text-red-700 dark:text-red-300">
                    <?php echo $lang == 'en' ? 'Pharmacy' : 'Eczane'; ?>
                </span>
            </a>
            
        

            <?php if(isset($_SESSION['user_id'])): ?>
            <?php if(basename($_SERVER['PHP_SELF']) == 'feed.php'): ?>
            <!-- Mobile Search Toggle -->
            <button onclick="document.getElementById('mobileSearchOverlay').classList.remove('hidden'); document.getElementById('mobile-search').focus();" class="lg:hidden w-10 h-10 rounded-full flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i class="fas fa-search text-xl"></i>
            </button>
            <?php endif; ?>

            <!-- Notifications Icon -->
            <div class="relative">
                <a href="notifications" aria-label="Notifications" class="w-10 h-10 rounded-full flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors relative">
                    <?php echo heroicon('bell', 'w-5 h-5'); ?>
                    <span id="notif-badge" class="absolute top-0.5 right-0.5 bg-red-500 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-black border-2 border-white dark:border-slate-900 animate-pulse <?php echo $unread_notifs > 0 ? '' : 'hidden'; ?>">
                        <?php echo $unread_notifs > 99 ? '99+' : $unread_notifs; ?>
                    </span>
                </a>
                
                <!-- Notifications Dropdown -->

            </div>

            <!-- Messages Icon - Visible on all screens -->
            <a href="messages" aria-label="<?php echo $t['messages']; ?>" class="flex w-10 h-10 rounded-full items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors relative">
                <?php echo heroicon('comment_dots', 'w-5 h-5'); ?>
                <?php if ($unread > 0): ?>
                <span class="absolute top-0.5 right-0.5 bg-pink-500 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-black border-2 border-white dark:border-slate-900">
                    <?php echo $unread > 9 ? '9+' : $unread; ?>
                </span>
                <?php endif; ?>
            </a>

            <!-- User Avatar -->
            <a href="profile?uid=<?php echo $_SESSION['user_id'] ?? ''; ?>" aria-label="<?php echo $lang == 'en' ? 'User Profile' : 'Kullanıcı Profili'; ?>" class="nav-haptic flex-shrink-0 ml-1">
                <span class="block w-9 h-9 rounded-full ring-2 ring-transparent group-hover:ring-[#0055FF] transition-all overflow-hidden">
                    <?php 
                    $avatarUrl = $_SESSION['avatar'] ?? '';
                    if (empty($avatarUrl) || strpos($avatarUrl, 'default-avatar.png') !== false) {
                        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User');
                    } elseif (function_exists('media_url')) {
                        $avatarUrl = media_url($avatarUrl);
                    }
                    
                    // Optimization: Resize Google Avatars
                    if (strpos($avatarUrl, 'googleusercontent.com') !== false) {
                        $avatarUrl = preg_replace('/=s\d+(-c)?/', '=s80-c', $avatarUrl);
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="avatar" class="w-full h-full rounded-full object-cover" loading="lazy">
                </span>
            </a>
            <?php else: ?>
            <a href="login" class="px-5 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-xs hover:scale-105 transition-all shrink-0 shadow-lg shadow-slate-900/20">
                <?php echo $t['login']; ?>
            </a>
            <?php endif; ?>

            <!-- Hamburger Button -->
            <button onclick="toggleMobileMenu()" aria-label="Toggle mobile menu" class="lg:hidden w-10 h-10 rounded-full flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors ml-1">
                <?php echo heroicon('bars', 'w-6 h-6'); ?>
            </button>
        </div>
    </div>
</header>
<script src="<?php echo js_url('notifications.js'); ?>" defer></script>
<script src="<?php echo js_url('theme.js'); ?>" defer></script>
<script src="<?php echo js_url('search.js'); ?>" defer></script>
<?php if (defined('ENABLE_PULL_TO_REFRESH')): ?>
<!-- Pull To Refresh (sadece feed sayfasında) -->
<script src="https://unpkg.com/pulltorefreshjs@0.1.22/dist/index.umd.js"></script>
<script>
(function() {
    let ptrInstance = null;

    function initPTR() {
        if (typeof PullToRefresh === 'undefined') {
            console.warn('PullToRefresh library not loaded');
            return;
        }

        if (ptrInstance) ptrInstance.destroy();
        
        // Remove manual overscroll suppression - handled by CSS
        // document.body.style.overscrollBehaviorY = 'none';

        ptrInstance = PullToRefresh.init({
            mainElement: 'body',
            triggerElement: 'body', 
            onRefresh: function() {
                window.location.reload();
            },
            distThreshold: 60,
            distMax: 120, // Prevent pulling too far
            // Instagram Style: Same icon for both states, just spins repeatedly or rotates on pull
            iconArrow: '<div class="ptr-spinner-box"><svg class="ptr-spinner-icon w-8 h-8 text-[#06b6d4]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg></div>',
            iconRefreshing: '<div class="ptr-spinner-box animate-spin"><svg class="ptr-spinner-icon w-8 h-8 text-[#06b6d4]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg></div>',
            instructionsPullToRefresh: ' ',
            instructionsReleaseToRefresh: ' ',
            instructionsRefreshing: ' ',
            classPrefix: 'ptr--',
            cssProp: 'padding-top', 
            shouldPullToRefresh: function() {
                if (!document.getElementById('mobileSearchOverlay') || !document.getElementById('mobileSearchOverlay').classList.contains('hidden')) return false;
                return window.scrollY <= 0;
            },
            onPull: function(percent, pixel) {
                // Rotate the spinner as user pulls (Instagram effect)
                const spinner = document.querySelector('.ptr--ptr:not(.ptr--refreshing) .ptr-spinner-icon');
                if (spinner) {
                    spinner.style.transform = `rotate(${pixel * 2}deg)`;
                }
            }
        });

    }

    // Init on load
    // Init on load
    document.addEventListener('DOMContentLoaded', initPTR);
    
    // Re-init on Swup page change (if you use Swup)
    document.addEventListener('swup:contentReplace', initPTR);
})();
</script>
<style>
    /* Custom Pull to Refresh Styling */
    .ptr--ptr { box-shadow: none !important; background: transparent !important; pointer-events: none; }
    .ptr--box { padding: 0; background: transparent !important; border-radius: 50%; box-shadow: none !important; width: 36px !important; height: 36px !important; display: flex; align-items: center; justify-content: center; margin: 0 auto; border: none; }
    .dark .ptr--box { background: transparent !important; box-shadow: none !important; }
    .ptr--text { display: none !important; }
    .ptr-spinner-box { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
    .ptr-spinner-icon { color: #06b6d4; transition: transform 0.1s linear; }
    .dark .ptr-spinner-icon { color: #22d3ee; }
</style>
<?php endif; ?>
<!-- Cropper.js & Image Editor -->
<style>
/*!
 * Cropper.js v1.6.1
 * https://fengyuanchen.github.io/cropperjs
 *
 * Copyright 2015-present Chen Fengyuan
 * Released under the MIT license
 *
 * Date: 2023-09-17T03:44:17.565Z
 */.cropper-container{direction:ltr;font-size:0;line-height:0;position:relative;-ms-touch-action:none;touch-action:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}.cropper-container img{backface-visibility:hidden;display:block;height:100%;image-orientation:0deg;max-height:none!important;max-width:none!important;min-height:0!important;min-width:0!important;width:100%}.cropper-canvas,.cropper-crop-box,.cropper-drag-box,.cropper-modal,.cropper-wrap-box{bottom:0;left:0;position:absolute;right:0;top:0}.cropper-canvas,.cropper-wrap-box{overflow:hidden}.cropper-drag-box{background-color:#fff;opacity:0}.cropper-modal{background-color:#000;opacity:.5}.cropper-view-box{display:block;height:100%;outline:1px solid #39f;outline-color:rgba(51,153,255,.75);overflow:hidden;width:100%}.cropper-dashed{border:0 dashed #eee;display:block;opacity:.5;position:absolute}.cropper-dashed.dashed-h{border-bottom-width:1px;border-top-width:1px;height:33.33333%;left:0;top:33.33333%;width:100%}.cropper-dashed.dashed-v{border-left-width:1px;border-right-width:1px;height:100%;left:33.33333%;top:0;width:33.33333%}.cropper-center{display:block;height:0;left:50%;opacity:.75;position:absolute;top:50%;width:0}.cropper-center:after,.cropper-center:before{background-color:#eee;content:" ";display:block;position:absolute}.cropper-center:before{height:1px;left:-3px;top:0;width:7px}.cropper-center:after{height:7px;left:0;top:-3px;width:1px}.cropper-face,.cropper-line,.cropper-point{display:block;height:100%;opacity:.1;position:absolute;width:100%}.cropper-face{background-color:#fff;left:0;top:0}.cropper-line{background-color:#39f}.cropper-line.line-e{cursor:ew-resize;right:-3px;top:0;width:5px}.cropper-line.line-n{cursor:ns-resize;height:5px;left:0;top:-3px}.cropper-line.line-w{cursor:ew-resize;left:-3px;top:0;width:5px}.cropper-line.line-s{bottom:-3px;cursor:ns-resize;height:5px;left:0}.cropper-point{background-color:#39f;height:5px;opacity:.75;width:5px}.cropper-point.point-e{cursor:ew-resize;margin-top:-3px;right:-3px;top:50%}.cropper-point.point-n{cursor:ns-resize;left:50%;margin-left:-3px;top:-3px}.cropper-point.point-w{cursor:ew-resize;left:-3px;margin-top:-3px;top:50%}.cropper-point.point-s{bottom:-3px;cursor:s-resize;left:50%;margin-left:-3px}.cropper-point.point-ne{cursor:nesw-resize;right:-3px;top:-3px}.cropper-point.point-nw{cursor:nwse-resize;left:-3px;top:-3px}.cropper-point.point-sw{bottom:-3px;cursor:nesw-resize;left:-3px}.cropper-point.point-se{bottom:-3px;cursor:nwse-resize;height:20px;opacity:1;right:-3px;width:20px}@media (min-width:768px){.cropper-point.point-se{height:15px;width:15px}}@media (min-width:992px){.cropper-point.point-se{height:10px;width:10px}}@media (min-width:1200px){.cropper-point.point-se{height:5px;opacity:.75;width:5px}}.cropper-point.point-se:before{background-color:#39f;bottom:-50%;content:" ";display:block;height:200%;opacity:0;position:absolute;right:-50%;width:200%}.cropper-invisible{opacity:0}.cropper-bg{background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAA3NCSVQICAjb4U/gAAAABlBMVEXMzMz////TjRV2AAAACXBIWXMAAArrAAAK6wGCiw1aAAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M26LyyjAAAABFJREFUCJlj+M/AgBVhF/0PAH6/D/HkDxOGAAAAAElFTkSuQmCC")}.cropper-hide{display:block;height:0;position:absolute;width:0}.cropper-hidden{display:none!important}.cropper-move{cursor:move}.cropper-crop{cursor:crosshair}.cropper-disabled .cropper-drag-box,.cropper-disabled .cropper-face,.cropper-disabled .cropper-line,.cropper-disabled .cropper-point{cursor:not-allowed}
</style>
<script>
// Lazy load Cropper.js + Image Editor (only when needed)
window._cropperLoaded = false;
window.loadCropperLazy = function(callback) {
    if (window._cropperLoaded) { if (callback) callback(); return; }
    var s1 = document.createElement('script');
    s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
    s1.onload = function() {
        var s2 = document.createElement('script');
        s2.src = 'https://cdn.kalkansocial.com/js/image_editor.js?v=<?php echo defined('ASSET_VERSION') ? ASSET_VERSION : '1'; ?>';
        s2.onload = function() { window._cropperLoaded = true; if (callback) callback(); };
        document.head.appendChild(s2);
    };
    document.head.appendChild(s1);
};
</script>




<!-- Mobile Search Overlay -->
<div id="mobileSearchOverlay" class="fixed top-20 left-0 w-full bg-white dark:bg-slate-900 z-40 p-4 border-b border-slate-100 dark:border-slate-800 hidden lg:hidden shadow-2xl animate-in slide-in-from-top-5 duration-200">
    <div class="relative">
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="text" id="mobile-search" placeholder="<?php echo $lang == 'en' ? 'Search...' : 'Ara...'; ?>" class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl py-3 pl-16 pr-10 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-pink-500 transition-all outline-none">
        <button onclick="document.getElementById('mobileSearchOverlay').classList.add('hidden')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 p-1">
             <i class="fas fa-times"></i>
        </button>
    </div>
    <div id="mobile-search-dropdown" class="mt-4 hidden">
         <div id="mobile-search-results" class="max-h-[60vh] overflow-y-auto custom-scrollbar"></div>
    </div>
</div>

<!-- PWA Bottom Navigation Bar (Mobile Only) -->
<nav class="lg:hidden fixed bottom-0 left-0 w-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border-t border-slate-100 dark:border-slate-800 px-6 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))] flex justify-between items-center z-50 shadow-2xl safe-area-pb">
    <!-- Home -->
    <a href="index" aria-label="Home" class="nav-haptic flex items-center justify-center w-12 h-12 rounded-2xl transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-50 dark:bg-slate-800 text-[#0055FF]' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50'; ?>">
        <?php echo heroicon('home', 'text-2xl'); ?>
    </a>

    <!-- Explore -->
    <a href="feed" aria-label="Feed" class="nav-haptic flex items-center justify-center w-12 h-12 rounded-2xl transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'feed.php' ? 'bg-blue-50 dark:bg-slate-800 text-[#0055FF]' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50'; ?>">
        <?php echo heroicon('fire', 'text-2xl'); ?>
    </a>

    <!-- Create (FAB) -->
    <div class="relative -mt-10">
        <a href="create_post_page.php" aria-label="Create new post" style="background: linear-gradient(135deg, #2563eb 0%, #1e3a5f 100%);" class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-900/50 border-4 border-white dark:border-slate-900 transform active:scale-95 transition-all hover:shadow-blue-800/60 hover:scale-110 animate-pulse-glow">
            <?php echo heroicon('plus', 'text-2xl'); ?>
        </a>
    </div>

    <!-- Services -->
    <a href="services" aria-label="Services" class="nav-haptic flex items-center justify-center w-12 h-12 rounded-2xl transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'bg-blue-50 dark:bg-slate-800 text-[#0055FF]' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50'; ?>">
        <?php echo heroicon('briefcase', 'text-2xl'); ?>
    </a>

    <!-- Profile -->
    <a href="profile?uid=<?php echo $_SESSION['user_id'] ?? ''; ?>" aria-label="Profile" class="nav-haptic flex items-center justify-center w-12 h-12 rounded-2xl transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' || (isset($_GET['uid']) && $_GET['uid'] == ($_SESSION['user_id'] ?? 0)) ? 'bg-blue-50 dark:bg-slate-800 text-[#0055FF]' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50'; ?>">
        <?php echo heroicon('profile', 'text-2xl'); ?>
    </a>
</nav>

<!-- Post Composer Modal -->
<!-- Post Composer Modal -->
<?php if (!defined('HIDE_COMPOSER_MODAL')): ?>
<div id="composerOverlay" class="fixed inset-0 bg-black/60 z-[80] hidden opacity-0 transition-opacity duration-300 backdrop-blur-sm" onclick="closeComposerModal()"></div>
<div id="composerModal" class="fixed inset-x-0 bottom-0 lg:bottom-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 w-full lg:w-[600px] bg-white dark:bg-slate-900 z-[90] shadow-2xl rounded-t-3xl lg:rounded-3xl transform translate-y-full lg:translate-y-0 lg:scale-95 lg:opacity-0 transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] max-h-[90vh] lg:max-h-[85vh] overflow-hidden border-t-4 lg:border-4 border-[#0055FF] hidden">
    <form action="api/create_post.php" method="POST" enctype="multipart/form-data" id="composerForm">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-xl font-black text-slate-900 dark:text-white">
                <?php echo $lang == 'en' ? 'Create Post' : 'Yeni Paylaşım'; ?>
            </h3>
            <button type="button" onclick="closeComposerModal()" aria-label="Close composer modal" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <?php echo heroicon('times', 'text-slate-600 dark:text-slate-400'); ?>
            </button>
        </div>

        <!-- Content -->
        <div class="p-4 overflow-y-auto max-h-[calc(90vh-180px)] lg:max-h-[calc(85vh-180px)]">
            <!-- Feeling/Activity Display -->
            <div id="feelingDisplay" class="hidden mb-3 flex items-center gap-2 p-3 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                <span id="feelingText" class="text-sm font-bold"></span>
                <button type="button" onclick="removeFeel()" class="ml-auto text-red-500 hover:text-red-600">
                    <?php echo heroicon('times', 'w-4 h-4'); ?>
                </button>
            </div>
            
            <!-- Editor.js Container -->
            <div id="editorjs" class="w-full min-h-[150px] p-4 rounded-xl bg-transparent border-none text-lg text-slate-900 dark:text-white leading-relaxed"></div>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" id="composerWordCount">0 <?php echo ($lang ?? 'tr') == 'en' ? 'word' : 'sözcük'; ?></p>
            <input type="hidden" name="content" id="composerContent">
            <!-- Legacy Textarea (Hidden/Removed) -->
            <!-- <textarea id="composerTextarea" class="hidden"></textarea> -->

            <!-- Link Input -->
            <div id="linkSection" class="hidden mt-3">
                <div class="flex gap-2">
                    <input type="url" name="link_url" id="linkInput" placeholder="<?php echo $lang == 'en' ? 'Paste link here...' : 'Linki buraya yapıştır...'; ?>" class="flex-1 px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-500 outline-none text-sm">
                    <button type="button" onclick="removeLink()" class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-colors">
                        <?php echo heroicon('times', 'w-5 h-5'); ?>
                    </button>
                </div>
            </div>

            <!-- Preview Areas -->
            <div id="imagePreview" class="hidden mt-3 relative inline-block">
                <img id="previewImg" src="#" class="max-h-60 rounded-xl border border-slate-200 object-cover shadow-sm">
                <button type="button" onclick="removeModalImage()" class="absolute top-2 right-2 bg-gray-900/50 hover:bg-gray-900 text-white rounded-full p-1 shadow-md transition-colors">
                    <?php echo heroicon('times', 'w-4 h-4'); ?>
                </button>
            </div>
            
            <div id="videoPreview" class="hidden mt-3 relative inline-block w-full">
                <video id="previewVid" class="w-full rounded-xl border border-slate-200 shadow-sm" controls></video>
                <button type="button" onclick="removeModalVideoFile()" class="absolute top-2 right-2 bg-gray-900/50 hover:bg-gray-900 text-white rounded-full p-1 shadow-md transition-colors">
                    <?php echo heroicon('times', 'w-4 h-4'); ?>
                </button>
            </div>

            <!-- Video URL Input -->
            <div id="videoUrlSection" class="hidden mt-3">
                <div class="flex gap-2">
                    <input type="url" name="video_url" id="videoUrlInput" placeholder="<?php echo $lang == 'en' ? 'YouTube or video link...' : 'YouTube veya video linki...'; ?>" class="flex-1 px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 focus:border-red-500 outline-none text-sm">
                    <button type="button" onclick="removeModalVideoUrl()" class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-colors">
                        <?php echo heroicon('times', 'w-5 h-5'); ?>
                    </button>
                </div>
            </div>

            <!-- Hidden inputs for feeling/activity -->
            <input type="hidden" name="feeling_action" id="feelingAction">
            <input type="hidden" name="feeling_value" id="feelingValue">

            <!-- Action Buttons (Optimized) -->
            <div class="flex flex-wrap gap-2 mt-4">
                <label class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-green-50 dark:hover:bg-green-900/20 text-slate-600 dark:text-slate-300 hover:text-green-600 transition-all cursor-pointer group">
                    <?php echo heroicon('image', 'w-5 h-5 group-hover:scale-110 transition-transform'); ?>
                    <span class="text-xs font-bold"><?php echo $t['photo']; ?></span>
                    <input type="file" name="image" id="imageInput" accept="image/*" class="hidden" onchange="previewModalImage(event)">
                </label>
                
                <label class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-red-900/20 text-slate-600 dark:text-slate-300 hover:text-red-500 transition-all cursor-pointer group">
                    <?php echo heroicon('video', 'w-5 h-5 group-hover:scale-110 transition-transform'); ?>
                    <span class="text-xs font-bold"><?php echo $lang == 'en' ? 'Video' : 'Video'; ?></span>
                    <input type="file" name="video" id="videoFileInput" accept="video/*" class="hidden" onchange="previewModalVideo(event)">
                </label>
                
                <button type="button" onclick="toggleLink()" class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-slate-600 dark:text-slate-300 hover:text-blue-500 transition-all group">
                    <?php echo heroicon('link', 'w-5 h-5 group-hover:scale-110 transition-transform'); ?>
                    <span class="text-xs font-bold"><?php echo $lang == 'en' ? 'Link' : 'Link'; ?></span>
                </button>
                
                <button type="button" onclick="toggleVideoUrl()" class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-purple-900/20 text-slate-600 dark:text-slate-300 hover:text-purple-500 transition-all group">
                    <?php echo heroicon('play_circle', 'w-5 h-5 group-hover:scale-110 transition-transform'); ?>
                    <span class="text-xs font-bold"><?php echo $lang == 'en' ? 'Video URL' : 'Video URL'; ?></span>
                </button>

                <button type="button" onclick="document.getElementById('locationSection').classList.toggle('hidden')" class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-slate-600 dark:text-slate-300 hover:text-emerald-500 transition-all group">
                    <?php echo heroicon('location', 'w-5 h-5 group-hover:scale-110 transition-transform'); ?>
                    <span class="text-xs font-bold"><?php echo $lang == 'en' ? 'Location' : 'Konum'; ?></span>
                </button>

                <button type="button" onclick="openFeelingPicker()" class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 text-slate-600 dark:text-slate-300 hover:text-yellow-500 transition-all group">
                    <?php echo heroicon('smile', 'w-5 h-5 group-hover:scale-110 transition-transform'); ?>
                    <span class="text-xs font-bold"><?php echo $lang == 'en' ? 'Feeling' : 'Duygu'; ?></span>
                </button>
            </div>

            <!-- Location Input -->
            <div id="locationSection" class="hidden mt-3">
                <input type="text" name="location" id="locationInput" placeholder="<?php echo $lang == 'en' ? 'Where are you?' : 'Neredesin?'; ?>" class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 focus:border-emerald-500 outline-none text-sm">
            </div>

            <!-- Link Preview Card (Synced with Feed) -->
            <div id="link-preview-container-modal" class="hidden mt-3 border-2 border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden bg-slate-50 dark:bg-slate-900 group relative">
                <button type="button" onclick="clearLinkPreviewModal()" class="absolute top-2 right-2 bg-white/80 p-1 rounded-full shadow-sm hover:bg-red-50 text-red-500 z-10"><?php echo heroicon('times', 'w-4 h-4'); ?></button>
                <div class="h-32 w-full bg-slate-200 dark:bg-slate-800 bg-cover bg-center" id="preview-image-modal"></div>
                <div class="p-3">
                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-100 truncate" id="preview-title-modal"><?php echo $t['loading']; ?></h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1" id="preview-desc-modal"></p>
                    <p class="text-[10px] text-slate-400 uppercase mt-1" id="preview-domain-modal"></p>
                </div>
            </div>

            <!-- Hidden Inputs for Feed Compatibility -->
            <input type="hidden" name="link_title" id="meta-link-title-modal">
            <input type="hidden" name="link_description" id="meta-link-desc-modal">
            <input type="hidden" name="link_image" id="meta-link-image-modal">
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-pink-500 to-violet-600 text-white font-black hover:shadow-lg hover:shadow-pink-500/30 transition-all flex items-center justify-center gap-2">
                <?php echo heroicon('paper_plane', 'w-5 h-5'); ?>
                <?php echo $lang == 'en' ? 'Post' : 'Paylaş'; ?>
            </button>
        </div>
    </form>
    </form>
</div>
<?php endif; ?>

<!-- Feeling/Activity Picker Modal -->
<div id="feelingPicker" class="fixed inset-0 bg-black/60 z-[100] hidden opacity-0 transition-opacity duration-300" onclick="closeFeelingPicker()"></div>
<div id="feelingPickerModal" class="fixed bottom-0 left-0 right-0 lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-[500px] bg-white dark:bg-slate-900 z-[110] rounded-t-3xl lg:rounded-3xl transform translate-y-full lg:translate-y-0 lg:scale-95 lg:opacity-0 transition-all duration-500 max-h-[70vh] overflow-hidden border-t-4 lg:border-4 border-yellow-500 hidden">
    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20">
        <h3 class="text-lg font-black"><?php echo $lang == 'en' ? 'How are you feeling?' : 'Nasıl hissediyorsun?'; ?></h3>
    </div>
    <div class="p-4 overflow-y-auto max-h-[calc(70vh-80px)] grid grid-cols-2 gap-2">
        <?php
        $feelings = [
            'happy' => ['emoji' => '😊', 'en' => 'Happy', 'tr' => 'Mutlu', 'action_en' => 'feeling', 'action_tr' => 'hissediyor'],
            'loved' => ['emoji' => '❤️', 'en' => 'Loved', 'tr' => 'Sevgi Dolu', 'action_en' => 'feeling', 'action_tr' => 'hissediyor'],
            'excited' => ['emoji' => '🤩', 'en' => 'Excited', 'tr' => 'Heyecanlı', 'action_en' => 'feeling', 'action_tr' => 'hissediyor'],
            'blessed' => ['emoji' => '🙏', 'en' => 'Blessed', 'tr' => 'Mübarek', 'action_en' => 'feeling', 'action_tr' => 'hissediyor'],
            'relaxed' => ['emoji' => '😌', 'en' => 'Relaxed', 'tr' => 'Rahat', 'action_en' => 'feeling', 'action_tr' => 'hissediyor'],
            'hungry' => ['emoji' => '🍕', 'en' => 'Hungry', 'tr' => 'Aç', 'action_en' => 'eating', 'action_tr' => 'yiyor'],
            'drinking' => ['emoji' => '☕', 'en' => 'Drinking Coffee', 'tr' => 'Kahve İçiyor', 'action_en' => 'drinking', 'action_tr' => 'içiyor'],
            'traveling' => ['emoji' => '✈️', 'en' => 'Traveling', 'tr' => 'Seyahatte', 'action_en' => 'traveling', 'action_tr' => 'geziyor'],
            'celebrating' => ['emoji' => '🎉', 'en' => 'Celebrating', 'tr' => 'Kutluyor', 'action_en' => 'celebrating', 'action_tr' => 'kutluyor'],
            'sad' => ['emoji' => '😔', 'en' => 'Sad', 'tr' => 'Üzgün', 'action_en' => 'feeling', 'action_tr' => 'hissediyor'],
        ];
        foreach ($feelings as $key => $feel):
            $f_action = ($lang == 'en' ? $feel['action_en'] : $feel['action_tr']);
            $f_label = ($lang == 'en' ? $feel['en'] : $feel['tr']);
        ?>
        <button type="button" onclick="selectFeeling('<?php echo $feel['action_en']; ?>', '<?php echo $key; ?>', '<?php echo $feel['emoji']; ?> <?php echo $f_action; ?> <?php echo $f_label; ?>')" class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 border border-slate-200 dark:border-slate-700 hover:border-yellow-500 transition-all text-left">
            <span class="text-2xl"><?php echo $feel['emoji']; ?></span>
            <span class="text-sm font-bold"><?php echo $f_label; ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</div>


<!-- Mobile Overlay & Menu -->
<div id="mobileMenuOverlay" class="fixed inset-0 bg-black/60 z-[60] hidden opacity-0 transition-opacity duration-300 backdrop-blur-sm touch-none" onclick="toggleMobileMenu()"></div>
<div id="mobileMenuPanel" class="fixed top-0 right-0 h-full w-80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl z-[70] shadow-2xl transform translate-x-full transition-transform duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] p-8 flex flex-col invisible pointer-events-none">
    <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-800 pb-6">
        <span class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500"><?php echo $t['navigation']; ?></span>
        <button onclick="toggleMobileMenu()" aria-label="Close mobile menu" class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500">
            <?php echo heroicon('times', 'text-lg w-5 h-5'); ?>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto overscroll-contain space-y-8 pr-2 custom-scrollbar">
        <!-- Main Section -->
        <div>
            <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4 px-2"><?php echo $t['general']; ?></h4>
            <div class="space-y-1">
                <a href="index" class="nav-haptic flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <?php echo heroicon('home', 'w-5 w-5 text-pink-500'); ?>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $t['home']; ?></span>
                </a>
                <a href="feed" class="nav-haptic flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <?php echo heroicon('fire', 'w-5 w-5 text-orange-500'); ?>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $t['explore']; ?> (<?php echo $t['feed']; ?>)</span>
                </a>

                <a href="duty_pharmacy" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                    <?php echo heroicon('pills', 'w-5 w-5 text-red-500'); ?>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Duty Pharmacy' : 'Nöbetçi Eczane'; ?></span>
                </a>
                <?php if (isset($_SESSION['badge']) && in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])): ?>
                <a href="business_panel.php" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all">
                    <i class="fas fa-store w-5 h-5 text-violet-500"></i>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Business Panel' : 'İşletme Paneli'; ?></span>
                </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['badge']) && $_SESSION['badge'] === 'founder'): ?>
                <a href="admin/index.php" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-900 hover:text-white dark:hover:bg-white dark:hover:text-slate-900 transition-all">
                    <?php echo heroicon('shield', 'w-5 w-5 text-pink-500 group-hover:text-white'); ?>
                    <span class="font-bold text-slate-700 dark:text-slate-200 group-hover:text-white"><?php echo $t['admin_panel']; ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Social Section -->
        <div>
            <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4 px-2"><?php echo $t['social']; ?></h4>
            <div class="space-y-1">
                <a href="events" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <?php echo heroicon('calendar', 'w-5 w-5 text-violet-500'); ?>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $t['events']; ?></span>
                </a>
                <a href="news" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <i class="fas fa-newspaper w-5 text-blue-500"></i>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'News' : 'Haberler'; ?></span>
                </a>
                <a href="groups" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <i class="fas fa-users w-5 text-emerald-500"></i>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $t['groups']; ?></span>
                </a>
                <a href="members" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <?php echo heroicon('group', 'w-5 w-5 text-blue-500'); ?>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $t['members']; ?></span>
                </a>
                <a href="photo_contest" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <i class="fas fa-camera w-5 text-pink-500"></i>
                    <span class="font-bold text-slate-700 dark:text-slate-200">Kalkan Hafızası</span>
                </a>
                <a href="community_support" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <i class="fas fa-hand-holding-heart w-5 text-green-500"></i>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Community Support' : 'Askıda İyilik'; ?></span>
                </a>
            </div>
        </div>

        <!-- Services Section -->
        <div>
            <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4 px-2"><?php echo $t['services']; ?></h4>
            <div class="space-y-1">
                <a href="services" class="nav-haptic flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    <?php echo heroicon('briefcase', 'w-5 w-5 text-indigo-500'); ?>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Services' : 'Hizmetler'; ?></span>
                </a>
            </div>
        </div>

    </div>

            <!-- Settings (logged in) - Opens modal -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="toggleMobileMenu(); openSettingsModal();" class="w-full flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all text-left">
                    <i class="fas fa-cog w-5 h-5 text-slate-500"></i>
                    <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Settings' : 'Ayarlar'; ?></span>
                    <span class="text-xs text-slate-400 ml-auto"><?php echo $lang == 'en' ? 'Theme, language' : 'Tema, dil'; ?></span>
                </button>
            </div>
            <?php endif; ?>

            <!-- User Footer -->
            <div class="mt-auto pt-6 border-t border-slate-100 dark:border-slate-800 hidden">
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <?php include __DIR__ . '/settings_modal.php'; ?>
        <?php endif; ?>

<script>
let mobileMenuOpen = false;
function toggleMobileMenu() {
    const overlay = document.getElementById('mobileMenuOverlay');
    const panel = document.getElementById('mobileMenuPanel');
    mobileMenuOpen = !mobileMenuOpen;
    if (mobileMenuOpen) {
        overlay.classList.remove('hidden');
        panel.classList.remove('invisible', 'pointer-events-none');
        setTimeout(() => overlay.classList.add('opacity-100'), 10);
        panel.classList.remove('translate-x-full');
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden'; // Lock html too
    } else {
        overlay.classList.remove('opacity-100');
        panel.classList.add('translate-x-full');
        document.body.style.overflow = '';
        document.documentElement.style.overflow = ''; // Unlock html
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => {
            overlay.classList.add('hidden');
            panel.classList.add('invisible', 'pointer-events-none');
        }, 500);
    }
}

// Scroll Recovery System - Emergency fix for stuck scrolling
setInterval(() => {
    const modals = [
        document.getElementById('mobileMenuOverlay'),
        document.getElementById('composerOverlay'),
        document.getElementById('feelingPicker'),
        document.getElementById('mobileSearchOverlay')
    ];
    
    const isAnyModalOpen = modals.some(m => m && !m.classList.contains('hidden'));
    
    if (!isAnyModalOpen) {
        const bodyStyles = document.body.style;
        const htmlStyles = document.documentElement.style;
        
        if (bodyStyles.overflow === 'hidden' || htmlStyles.overflow === 'hidden' || bodyStyles.overscrollBehaviorY === 'none') {
            console.warn('Scroll lock or overscroll suppression detected without active modal. Recovering...');
            bodyStyles.overflow = '';
            bodyStyles.overscrollBehaviorY = '';
            htmlStyles.overflow = '';
            htmlStyles.overscrollBehaviorY = '';
        }
    }
}, 1000);

// Editor.js Instance
let editor;

function initEditor() {
    if (editor) return;
    
    // Check if tools are loaded to avoid ReferenceErrors
    const HeaderTool = typeof Header !== 'undefined' ? Header : null;
    const ListTool = typeof List !== 'undefined' ? List : null;
    const ParagraphTool = typeof Paragraph !== 'undefined' ? Paragraph : null;

    if (typeof EditorJS === 'undefined') {
        console.error('EditorJS not loaded');
        return;
    }
    
    const editorConfig = {
        holder: 'editorjs',
        placeholder: '<?php echo $lang == "en" ? "What\'s on your mind?" : "Ne düşünüyorsun?"; ?>',
        tools: {},
        data: {},
        onChange: function() { if (typeof updateComposerWordCount === 'function') updateComposerWordCount(); }
    };

    if (HeaderTool) {
        editorConfig.tools.header = {
            class: HeaderTool,
            config: {
                placeholder: 'Enter a header',
                levels: [2, 3, 4],
                defaultLevel: 2
            }
        };
    }

    if (ListTool) {
        editorConfig.tools.list = {
            class: ListTool,
            inlineToolbar: true
        };
    }

    if (ParagraphTool) {
        editorConfig.tools.paragraph = {
            class: ParagraphTool,
            inlineToolbar: true
        };
    }

    editor = new EditorJS(editorConfig);
}

// Load Editor.js on demand (lazy - only when composer modal opens)
function loadEditorJsLazy() {
    if (typeof EditorJS !== 'undefined') return Promise.resolve();
    if (window._editorJsLoading) return window._editorJsLoading;
    var urls = [
        'https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest',
        'https://cdn.jsdelivr.net/npm/@editorjs/header@latest',
        'https://cdn.jsdelivr.net/npm/@editorjs/list@latest',
        'https://cdn.jsdelivr.net/npm/@editorjs/paragraph@latest'
    ];
    window._editorJsLoading = urls.reduce(function(p, url) {
        return p.then(function() {
            return new Promise(function(res, rej) {
                var s = document.createElement('script');
                s.src = url;
                s.onload = res;
                s.onerror = rej;
                document.head.appendChild(s);
            });
        });
    }, Promise.resolve());
    return window._editorJsLoading;
}

// Composer Modal Functions
function openComposerModal() {
    // Check if user is logged in
    <?php if(!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login';
        return;
    <?php endif; ?>

    const overlay = document.getElementById('composerOverlay');
    const modal = document.getElementById('composerModal');
    if (!overlay || !modal) return;
    
    overlay.classList.remove('hidden');
    modal.classList.remove('hidden');
    
    // Load Editor.js if needed, then init
    loadEditorJsLazy().then(function() {
        initEditor();
    });
    
    // Force a reflow
    modal.offsetHeight; 
    
    overlay.classList.add('opacity-100');
    modal.classList.remove('translate-y-full', 'lg:scale-95', 'lg:opacity-0');
    modal.classList.add('translate-y-0', 'lg:scale-100', 'lg:opacity-100');
    
    document.body.style.overflow = 'hidden';
}

function closeComposerModal() {
    const overlay = document.getElementById('composerOverlay');
    const modal = document.getElementById('composerModal');
    
    overlay.classList.remove('opacity-100');
    modal.classList.add('translate-y-full', 'lg:scale-95', 'lg:opacity-0');
    modal.classList.remove('translate-y-0', 'lg:scale-100', 'lg:opacity-100');
    
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    
    setTimeout(() => {
        overlay.classList.add('hidden');
        modal.classList.add('hidden');
        // We don't destroy editor to save re-init cost, or we can:
        if(editor) {
            editor.clear();
        }
        document.getElementById('imagePreview').classList.add('hidden');
        document.getElementById('videoPreview').classList.add('hidden');
        document.getElementById('videoUrlSection').classList.add('hidden');
        document.getElementById('locationSection').classList.add('hidden');
        document.getElementById('feelingDisplay').classList.add('hidden');
        clearLinkPreviewModal();
    }, 300);
}

// En az 1 sözcük (boş gönderi engelle)
var MIN_POST_WORDS = 1;
function composerWordCount(text) {
    if (!text || !String(text).trim()) return 0;
    return String(text).trim().split(/\s+/).filter(Boolean).length;
}
function updateComposerWordCount() {
    var el = document.getElementById('composerWordCount');
    if (!el || typeof editor === 'undefined' || !editor) return;
    editor.save().then(function(outputData) {
        var text = '';
        if (outputData.blocks) {
            outputData.blocks.forEach(function(block) {
                if (block.data) {
                    if (block.data.text) text += block.data.text + ' ';
                    if (block.data.items) block.data.items.forEach(function(item) { text += item + ' '; });
                }
            });
        }
        var n = composerWordCount(text);
        el.textContent = n + ' <?php echo ($lang ?? "tr") == "en" ? "words" : "sözcük"; ?>';
        el.classList.remove('text-red-500', 'text-emerald-600');
        if (n >= MIN_POST_WORDS) el.classList.add('text-emerald-600');
    }).catch(function() {});
}

// Handle Form Submit with Editor.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('composerForm');
    if(form) {
        form.addEventListener('submit', async function(e) {
            if (typeof editor === 'undefined' || !editor) return;

            e.preventDefault();
            try {
                const outputData = await editor.save();
                var plainText = '';
                if (outputData.blocks) {
                    outputData.blocks.forEach(function(block) {
                        if (block.data) {
                            if (block.data.text) plainText += block.data.text + ' ';
                            if (block.data.items) block.data.items.forEach(function(item) { plainText += item + ' '; });
                        }
                    });
                }
                if (composerWordCount(plainText) < MIN_POST_WORDS) {
                    alert('<?php echo ($lang ?? "tr") == "en" ? "Please write at least one word." : "En az bir sözcük yazın."; ?>');
                    return;
                }
                
                let htmlContent = '';
                if(outputData.blocks) {
                    outputData.blocks.forEach(block => {
                        switch (block.type) {
                            case 'header':
                                htmlContent += `<h${block.data.level}>${block.data.text}</h${block.data.level}>`;
                                break;
                            case 'paragraph':
                                htmlContent += `<p>${block.data.text}</p>`;
                                break;
                            case 'list':
                                const tag = block.data.style === 'ordered' ? 'ol' : 'ul';
                                htmlContent += `<${tag}>`;
                                block.data.items.forEach(item => htmlContent += `<li>${item}</li>`);
                                htmlContent += `</${tag}>`;
                                break;
                        }
                    });
                }
                
                const hiddenInput = document.getElementById('composerContent');
                if(hiddenInput) hiddenInput.value = htmlContent;
                
                HTMLFormElement.prototype.submit.call(this);
                
            } catch (error) {
                console.error('Saving failed: ', error);
                alert('Failed to process text. Please try again.');
            }
        });
    }
});

// Image Editor Globals
let imageEditorHeader = null;
let editedImageBlob = null;

function previewModalImage(event) {
    const file = event.target.files[0];
    if (file) {
        removeModalVideoFile();
        removeModalVideoUrl();
        removeLink();
        
        // Open Image Editor (lazy load Cropper.js + ImageEditor on first use)
        function initAndOpenEditor(file) {
            if(!imageEditorHeader) {
                imageEditorHeader = new ImageEditor({
                    lang: '<?php echo $lang ?? 'tr'; ?>',
                    onSave: function(blob) {
                        editedImageBlob = blob;
                        document.getElementById('previewImg').src = URL.createObjectURL(blob);
                        document.getElementById('imagePreview').classList.remove('hidden');
                    }
                });
            }
            imageEditorHeader.onSave = function(blob) {
                 editedImageBlob = blob;
                 document.getElementById('previewImg').src = URL.createObjectURL(blob);
                 document.getElementById('imagePreview').classList.remove('hidden');
            };
            imageEditorHeader.open(file);
        }

        if(typeof ImageEditor !== 'undefined') {
            initAndOpenEditor(file);
        } else if(typeof loadCropperLazy === 'function') {
            loadCropperLazy(function() { initAndOpenEditor(file); });
        } else {
             const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }
}

function removeModalImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('previewImg').src = '#';
}

function previewModalVideo(event) {
    const file = event.target.files[0];
    if (file) {
        removeModalImage(); // Clear image if video is selected
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewVid').src = e.target.result;
            document.getElementById('videoPreview').classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}

function removeModalVideoFile() {
    document.getElementById('videoFileInput').value = '';
    document.getElementById('videoPreview').classList.add('hidden');
    document.getElementById('previewVid').src = '';
}

function toggleVideoUrl() {
    const section = document.getElementById('videoUrlSection');
    section.classList.toggle('hidden');
    if (!section.classList.contains('hidden')) {
        document.getElementById('videoUrlInput').focus();
    }
}

function removeModalVideoUrl() {
    document.getElementById('videoUrlSection').classList.add('hidden');
    document.getElementById('videoUrlInput').value = '';
}

function removeVideo() {
    if(document.getElementById('videoSection')) document.getElementById('videoSection').classList.add('hidden');
    if(document.getElementById('videoInput')) document.getElementById('videoInput').value = '';
}

// Link functions
function toggleLink() {
    const linkSection = document.getElementById('linkSection');
    linkSection.classList.toggle('hidden');
    if (!linkSection.classList.contains('hidden')) {
        document.getElementById('linkInput').focus();
    }
}

function removeLink() {
    document.getElementById('linkSection').classList.add('hidden');
    document.getElementById('linkInput').value = '';
}

// Link Preview Logic for Modal (Synced with Feed)
// Link Preview Logic for Modal (Synced with Feed)
let typingTimerModal;
const urlRegexModal = /(https?:\/\/[^\s]+)/g;

document.addEventListener('DOMContentLoaded', function() {
    // Editor.js Init (Safe)
    if(document.getElementById('editorjs')) {
        // Only init if we are NOT on create_post_page (which handles its own editor) 
        // OR if create_post_page uses the same ID.
        // But create_post_page suppresses the modal.
        // If create_post_page WANTS to use Editor.js, it should call init function or have its own script.
        // Let's make initEditor callable globally.
    }

    const modalTextarea = document.getElementById('composerTextarea'); // Legacy or Standalone
    if(modalTextarea) {
        modalTextarea.addEventListener('input', function() {
            clearTimeout(typingTimerModal);
            typingTimerModal = setTimeout(checkUrlModal, 1000);
        });
    }
});

function checkUrlModal() {
    const modalTextarea = document.getElementById('composerTextarea');
    if (!modalTextarea) return; // Safety Check

    const text = modalTextarea.value;
    const matches = text.match(urlRegexModal);
    
    // Check linkInput existence
    const linkInput = document.getElementById('linkInput');
    if (matches && matches.length > 0 && linkInput && linkInput.value === '') {
        const url = matches[0];
        fetchPreviewModal(url);
    }
}

function fetchPreviewModal(url) {
    const previewContainer = document.getElementById('link-preview-container-modal');
    const titleEl = document.getElementById('preview-title-modal');
    const descEl = document.getElementById('preview-desc-modal');
    const imgEl = document.getElementById('preview-image-modal');
    const domainEl = document.getElementById('preview-domain-modal');

    fetch(`api/fetch_url_preview.php?url=${encodeURIComponent(url)}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && data.data.title) {
            const meta = data.data;
            previewContainer.classList.remove('hidden');
            titleEl.textContent = meta.title;
            descEl.textContent = meta.description || '';
            domainEl.textContent = new URL(url).hostname;
            if (meta.image) imgEl.style.backgroundImage = `url('${meta.image}')`;
            
            document.getElementById('linkInput').value = url;
            document.getElementById('meta-link-title-modal').value = meta.title;
            document.getElementById('meta-link-desc-modal').value = meta.description;
            document.getElementById('meta-link-image-modal').value = meta.image;
        }
    });
}

function clearLinkPreviewModal() {
    document.getElementById('link-preview-container-modal').classList.add('hidden');
    document.getElementById('linkInput').value = '';
    document.getElementById('meta-link-title-modal').value = '';
    document.getElementById('meta-link-desc-modal').value = '';
    document.getElementById('meta-link-image-modal').value = '';
}

// Video functions
function toggleVideo() {
    const videoSection = document.getElementById('videoSection');
    videoSection.classList.toggle('hidden');
    if (!videoSection.classList.contains('hidden')) {
        document.getElementById('videoInput').focus();
    }
}

function removeVideo() {
    document.getElementById('videoSection').classList.add('hidden');
    document.getElementById('videoInput').value = '';
}

// Feeling/Activity functions
function openFeelingPicker() {
    const overlay = document.getElementById('feelingPicker');
    const modal = document.getElementById('feelingPickerModal');
    overlay.classList.remove('hidden');
    modal.classList.remove('hidden');
    setTimeout(() => {
        overlay.classList.add('opacity-100');
        modal.classList.remove('translate-y-full', 'lg:scale-95', 'lg:opacity-0');
        modal.classList.add('translate-y-0', 'lg:scale-100', 'lg:opacity-100');
    }, 10);
}

function closeFeelingPicker() {
    const overlay = document.getElementById('feelingPicker');
    const modal = document.getElementById('feelingPickerModal');
    
    overlay.classList.remove('opacity-100');
    modal.classList.add('translate-y-full', 'lg:scale-95', 'lg:opacity-0');
    modal.classList.remove('translate-y-0', 'lg:scale-100', 'lg:opacity-100');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
        modal.classList.add('hidden');
    }, 300);
}

function selectFeeling(action, value, text) {
    document.getElementById('feelingAction').value = action;
    document.getElementById('feelingValue').value = value;
    document.getElementById('feelingText').textContent = text;
    document.getElementById('feelingDisplay').classList.remove('hidden');
    closeFeelingPicker();
}

function removeFeel() {
    document.getElementById('feelingAction').value = '';
    document.getElementById('feelingValue').value = '';
    document.getElementById('feelingDisplay').classList.add('hidden');
}


// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    // ImageEditor is now lazy loaded - no eager init needed

    const form = document.getElementById('composerForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Append edited image if it exists
            if (editedImageBlob) {
                formData.set('image', editedImageBlob, 'edited_image.jpg');
            }
            
            // Butonu pasif yap ve yükleniyor ikonu göster
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<?php echo heroicon("spinner", "animate-spin inline-block mr-2 w-4 h-4"); ?><?php echo $lang == "en" ? "Posting..." : "Paylaşılıyor..."; ?>';
            
            try {
                const response = await fetch('api/create_post.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeComposerModal();
                    editedImageBlob = null;
                    document.getElementById('imageInput').value = ''; 
                    
                    // Reload page
                    window.location.reload();
                } else {
                    alert(data.message || '<?php echo $lang == "en" ? "Error creating post" : "Paylaşım oluşturulamadı"; ?>');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<?php echo heroicon("paper_plane", "inline-block mr-2 w-4 h-4"); ?><?php echo $lang == "en" ? "Post" : "Paylaş"; ?>';
                }
            } catch (error) {
                console.error(error);
                alert('<?php echo $lang == "en" ? "Network error" : "Bağlantı hatası"; ?>');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<?php echo heroicon("paper_plane", "inline-block mr-2 w-4 h-4"); ?><?php echo $lang == "en" ? "Post" : "Paylaş"; ?>';
            }
        });
    }
});

// Sayfa tamamen yüklendikten sonra çalıştır
window.addEventListener('load', function() {
    // 1 saniye bekle ve çalıştır
    setTimeout(function() {

    }, 1000);
});

// Currency Exchange Rate Update Fonksiyonu (DÜZELTİLMİŞ HALİ)




// Toast Notification System
function showToast(message, type = 'success') {
    const toast = document.getElementById('global-toast');
    const toastContent = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');
    
    toastContent.textContent = message;
    
    // Set colors based on type
    if (type === 'success') {
        toast.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 bg-emerald-500 text-white px-6 py-3 rounded-2xl shadow-2xl transition-all duration-500 translate-y-20 opacity-0';
        toastIcon.className = '';
        toastIcon.innerHTML = '<?php echo heroicon('check_circle', 'w-6 h-6'); ?>';
    } else {
        toast.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 bg-red-500 text-white px-6 py-3 rounded-2xl shadow-2xl transition-all duration-500 translate-y-20 opacity-0';
        toastIcon.className = '';
        toastIcon.innerHTML = '<?php echo heroicon('exclamation_circle', 'w-6 h-6'); ?>';
    }
    
    // Show
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.remove('translate-y-20', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);
    
    // Hide
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
        setTimeout(() => toast.classList.add('hidden'), 500);
    }, 3000);
}

</script>


<!-- Custom Modal System -->
<script>
/**
 * Custom Modal System for Kalkan Social
 * Replaces native confirm() and alert() with beautiful, site-themed modals
 */

const KalkanModal = {
    /**
     * Show a confirmation dialog
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {function} onConfirm - Callback when user confirms
     * @param {function} onCancel - Callback when user cancels (optional)
     */
    showConfirm(title, message, onConfirm, onCancel) {
        const modal = this._createModal('confirm', title, message);
        
        const confirmBtn = modal.querySelector('[data-action="confirm"]');
        const cancelBtn = modal.querySelector('[data-action="cancel"]');
        
        confirmBtn.onclick = () => {
            this._closeModal(modal);
            if (onConfirm) onConfirm();
        };
        
        cancelBtn.onclick = () => {
            this._closeModal(modal);
            if (onCancel) onCancel();
        };
        
        // Close on backdrop click
        modal.onclick = (e) => {
            if (e.target === modal) {
                this._closeModal(modal);
                if (onCancel) onCancel();
            }
        };
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('active'), 10);
    },
    
    /**
     * Show an alert dialog
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {function} onClose - Callback when user closes (optional)
     */
    showAlert(title, message, onClose) {
        const modal = this._createModal('alert', title, message);
        
        const okBtn = modal.querySelector('[data-action="ok"]');
        
        okBtn.onclick = () => {
            this._closeModal(modal);
            if (onClose) onClose();
        };
        
        // Close on backdrop click
        modal.onclick = (e) => {
            if (e.target === modal) {
                this._closeModal(modal);
                if (onClose) onClose();
            }
        };
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('active'), 10);
    },

    /**
     * Show a prompt dialog with input
     * @param {string} title - Modal title
     * @param {string} defaultValue - Default input value
     * @param {function} onConfirm - Callback when user confirms (receives value)
     * @param {function} onCancel - Callback when user cancels
     */
    showPrompt(title, defaultValue, onConfirm, onCancel) {
        const modal = this._createModal('prompt', title, defaultValue);
        
        const confirmBtn = modal.querySelector('[data-action="confirm"]');
        const cancelBtn = modal.querySelector('[data-action="cancel"]');
        const input = modal.querySelector('input');
        
        setTimeout(() => {
            input.focus();
            input.select();
        }, 100);
        
        confirmBtn.onclick = () => {
            this._closeModal(modal);
            if (onConfirm) onConfirm(input.value);
        };
        
        cancelBtn.onclick = () => {
            this._closeModal(modal);
            if (onCancel) onCancel();
        };
        
        input.onkeydown = (e) => {
            if (e.key === 'Enter') confirmBtn.click();
            if (e.key === 'Escape') cancelBtn.click();
        };
        
        // Close on backdrop click
        modal.onclick = (e) => {
            if (e.target === modal) {
                this._closeModal(modal);
                if (onCancel) onCancel();
            }
        };
        
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('active'), 10);
    },
    
    /**
     * Create modal HTML
     * @private
     */
    _createModal(type, title, messageOrValue) {
        const isDark = document.documentElement.classList.contains('dark');
        const lang = document.documentElement.lang || 'tr';
        
        const texts = {
            cancel: lang === 'en' ? 'Cancel' : 'İptal',
            confirm: lang === 'en' ? 'Confirm' : 'Onayla',
            save: lang === 'en' ? 'Save' : 'Kaydet',
            ok: lang === 'en' ? 'OK' : 'Tamam'
        };
        
        const modal = document.createElement('div');
        modal.className = 'kalkan-modal';
        
        let bodyContent = '';
        if (type === 'prompt') {
            bodyContent = `<input type="text" class="kalkan-modal-input" value="${this._escapeHtml(messageOrValue || '')}">`;
        } else {
            bodyContent = `<p>${this._escapeHtml(messageOrValue)}</p>`;
        }
        
        let footerContent = '';
        if (type === 'confirm') {
            footerContent = `
                <button class="kalkan-btn kalkan-btn-secondary" data-action="cancel">${texts.cancel}</button>
                <button class="kalkan-btn kalkan-btn-danger" data-action="confirm">${texts.confirm}</button>
            `;
        } else if (type === 'prompt') {
             footerContent = `
                <button class="kalkan-btn kalkan-btn-secondary" data-action="cancel">${texts.cancel}</button>
                <button class="kalkan-btn kalkan-btn-primary" data-action="confirm">${texts.save}</button>
            `;
        } else {
            footerContent = `<button class="kalkan-btn kalkan-btn-primary" data-action="ok">${texts.ok}</button>`;
        }
        
        modal.innerHTML = `
            <div class="kalkan-modal-content">
                <div class="kalkan-modal-header">
                    <h3>${this._escapeHtml(title)}</h3>
                </div>
                <div class="kalkan-modal-body">
                    ${bodyContent}
                </div>
                <div class="kalkan-modal-footer">
                    ${footerContent}
                </div>
            </div>
        `;
        
        return modal;
    },
    
    /**
     * Close and remove modal
     * @private
     */
    _closeModal(modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    },
    
    /**
     * Escape HTML to prevent XSS
     * @private
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Make globally available
window.KalkanModal = KalkanModal;
</script>

<style>
    .kalkan-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
        padding: 1rem;
    }
    
    .kalkan-modal.active {
        opacity: 1;
    }
    
    .kalkan-modal-content {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 100%;
        overflow: hidden;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    
    .kalkan-modal.active .kalkan-modal-content {
        transform: scale(1);
    }
    
    .dark .kalkan-modal-content {
        background: #1e293b;
        color: white;
    }
    
    .kalkan-modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .dark .kalkan-modal-header {
        border-bottom-color: #334155;
    }
    
    .kalkan-modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
    }
    
    .dark .kalkan-modal-header h3 {
        color: white;
    }
    
    .kalkan-modal-body {
        padding: 1.5rem;
    }
    
    .kalkan-modal-body p {
        margin: 0;
        color: #64748b;
        line-height: 1.6;
    }
    
    .dark .kalkan-modal-body p {
        color: #cbd5e1;
    }
    
    .kalkan-modal-input {
        width: 100%;
        padding: 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #334155;
        font-size: 1rem;
        outline: none;
        transition: all 0.2s;
    }
    
    .kalkan-modal-input:focus {
        border-color: #ec4899;
        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        background: white;
    }
    
    .dark .kalkan-modal-input {
        background: #0f172a;
        border-color: #334155;
        color: white;
    }
    
    .dark .kalkan-modal-input:focus {
        border-color: #db2777;
        background: #1e293b;
    }
    
    .kalkan-modal-footer {
        padding: 1rem 1.5rem;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        background: #f8fafc;
    }
    
    .dark .kalkan-modal-footer {
        background: #0f172a;
    }
    
    .kalkan-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 80px;
    }
    
    .kalkan-btn:active {
        transform: scale(0.95);
    }
    
    .kalkan-btn-primary {
        background: #ec4899;
        color: white;
    }
    
    .kalkan-btn-primary:hover {
        background: #db2777;
    }
    
    .kalkan-btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .kalkan-btn-danger:hover {
        background: #dc2626;
    }
    
    .kalkan-btn-secondary {
        background: #e2e8f0;
        color: #475569;
    }
    
    .kalkan-btn-secondary:hover {
        background: #cbd5e1;
    }
    
    .dark .kalkan-btn-secondary {
        background: #334155;
        color: #cbd5e1;
    }
    
    .dark .kalkan-btn-secondary:hover {
        background: #475569;
    }
    
    @media (max-width: 640px) {
        .kalkan-modal {
            padding: 0.5rem;
        }
        
        .kalkan-modal-content {
            max-width: 100%;
        }
        
        .kalkan-modal-footer {
            flex-direction: column-reverse;
        }
        
        .kalkan-btn {
            width: 100%;
        }
    }
</style>

<!-- Offline Indicator -->
<div id="offline-banner" class="hidden fixed top-0 left-0 right-0 z-[9998] py-2 px-4 text-center text-sm font-bold bg-amber-500 text-slate-900" style="transform: translateY(-100%); transition: transform 0.3s ease;">
    <?php echo ($lang ?? 'tr') == 'en' ? 'You are offline. Some features may be limited.' : 'Çevrimdışısınız. Bazı özellikler sınırlı olabilir.'; ?>
</div>
<script>
(function(){var b=document.getElementById('offline-banner');if(!b)return;
function show(){b.classList.remove('hidden');b.style.transform='translateY(0)';}
function hide(){b.style.transform='translateY(-100%)';setTimeout(function(){b.classList.add('hidden');},300);}
if(!navigator.onLine)show();
window.addEventListener('offline',show);
window.addEventListener('online',hide);
})();
</script>

<!-- Global Toast Container -->
<div id="global-toast" class="hidden fixed bottom-24 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 bg-emerald-500 text-white px-6 py-3 rounded-2xl shadow-2xl transition-all duration-500 translate-y-20 opacity-0">
    <div id="toast-icon"><?php echo heroicon('check_circle', 'w-6 h-6'); ?></div>
    <span id="toast-message" class="font-bold text-sm"></span>
</div>


<style>
    header { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .animate-bounce-slow { animation: bounce 3s infinite; }
    @keyframes bounce { 0%, 100% { transform: translateY(-5%); animation-timing-function: cubic-bezier(0.8,0,1,1); } 50% { transform: none; animation-timing-function: cubic-bezier(0,0,0.2,1); } }
</style>

<div class="h-16 lg:h-20 sm:block hidden"></div>
<div class="h-16 lg:hidden block"></div>

<?php include __DIR__ . '/cookie_consent.php'; ?>
