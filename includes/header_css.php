<?php
require_once __DIR__ . '/ads_config.php';
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_registration_conversion = (strpos($request_uri, 'index') !== false || preg_match('#^/?(\?.*)?$#', parse_url($request_uri ?: '/', PHP_URL_PATH))) && isset($_GET['registered']) && $_GET['registered'] === '1';
$ads_conv_id = defined('GOOGLE_ADS_CONVERSION_ID') ? GOOGLE_ADS_CONVERSION_ID : '';
$ads_conv_label = defined('GOOGLE_ADS_CONVERSION_LABEL') ? GOOGLE_ADS_CONVERSION_LABEL : '';
$ads_enabled = $ads_conv_id && !strpos($ads_conv_id, 'XXXX') && $ads_conv_label;
?>
<?php if ($ads_enabled): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ads_conv_id; ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo $ads_conv_id; ?>');
</script>
<?php if ($is_registration_conversion): ?>
<!-- Event snippet for Kaydolma işlemi conversion page -->
<script>
  gtag('event', 'conversion', {'send_to': '<?php echo $ads_conv_id; ?>/<?php echo $ads_conv_label; ?>'});
</script>
<?php endif; ?>
<?php endif; ?>
<!-- CSRF Token (global for AJAX calls) -->
<meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
<script>
// Auto-attach CSRF token to all fetch/XHR POST requests
(function(){
    window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    // Patch fetch to include CSRF token
    var origFetch = window.fetch;
    window.fetch = function(url, opts) {
        opts = opts || {};
        if (opts.method && opts.method.toUpperCase() === 'POST') {
            opts.headers = opts.headers || {};
            if (opts.headers instanceof Headers) {
                if (!opts.headers.has('X-CSRF-TOKEN')) opts.headers.set('X-CSRF-TOKEN', window.CSRF_TOKEN);
            } else {
                if (!opts.headers['X-CSRF-TOKEN']) opts.headers['X-CSRF-TOKEN'] = window.CSRF_TOKEN;
            }
            // For FormData, also append csrf_token field
            if (opts.body instanceof FormData && !opts.body.has('csrf_token')) {
                opts.body.append('csrf_token', window.CSRF_TOKEN);
            }
        }
        return origFetch.call(this, url, opts);
    };
    // Patch XMLHttpRequest
    var origOpen = XMLHttpRequest.prototype.open;
    var origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function(method) {
        this._method = method;
        return origOpen.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function(body) {
        if (this._method && this._method.toUpperCase() === 'POST') {
            this.setRequestHeader('X-CSRF-TOKEN', window.CSRF_TOKEN);
            if (body instanceof FormData && !body.has('csrf_token')) {
                body.append('csrf_token', window.CSRF_TOKEN);
            }
        }
        return origSend.apply(this, arguments);
    };
})();
</script>
<!-- Haptic Feedback (navbar only: Home, Feed, Services, Profile) -->
<script>
(function(){
    window.haptic = function(pattern) {
        if (navigator.vibrate) navigator.vibrate(pattern || 10);
    };
    document.addEventListener('pointerdown', function(e) {
        var t = e.target.closest('.nav-haptic');
        if (t) window.haptic(10);
    }, { passive: true });
})();
</script>
<!-- Text Size Scaling (Accessibility - Android-style) -->
<style>
html.text-scale-100 { font-size: 100%; }
html.text-scale-112 { font-size: 112.5%; }
html.text-scale-125 { font-size: 125%; }
html.text-scale-140 { font-size: 140%; }
</style>
<script>
(function(){
    var s = localStorage.getItem('textScale') || '100';
    var el = document.documentElement;
    el.classList.remove('text-scale-100','text-scale-112','text-scale-125','text-scale-140');
    el.classList.add('text-scale-' + s);
})();
window.applyTextScale = function(scale){
    var el = document.documentElement;
    el.classList.remove('text-scale-100','text-scale-112','text-scale-125','text-scale-140');
    el.classList.add('text-scale-' + scale);
    localStorage.setItem('textScale', scale);
    if (typeof highlightMenuTextScale === 'function') highlightMenuTextScale(scale);
};
window.highlightMenuTextScale = function(scale){
    ['100','112','125','140'].forEach(function(v){
        var btn = document.getElementById('menu-ts-' + v);
        if (btn) {
            var active = v === scale;
            btn.classList.toggle('border-teal-500', active);
            btn.classList.toggle('bg-teal-50', active);
            btn.classList.toggle('dark:bg-teal-900/20', active);
            btn.classList.toggle('border-slate-200', !active);
            btn.classList.toggle('dark:border-slate-600', !active);
        }
    });
};
</script>
<!-- Timeago.js (defer - non-blocking) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/timeago.js/4.0.2/timeago.min.js" defer></script>
<script defer>
(function(){
    function initTimeago() {
        if (typeof timeago === 'undefined') return;
        timeago.register('tr', function(number, index, total_sec) {
          return [
            ['az önce', 'şimdi'],
            ['%s saniye önce', '%s saniye içinde'],
            ['1 dakika önce', '1 dakika içinde'],
            ['%s dakika önce', '%s dakika içinde'],
            ['1 saat önce', '1 saat içinde'],
            ['%s saat önce', '%s saat içinde'],
            ['1 gün önce', '1 gün içinde'],
            ['%s gün önce', '%s gün içinde'],
            ['1 hafta önce', '1 hafta içinde'],
            ['%s hafta önce', '%s hafta içinde'],
            ['1 ay önce', '1 ay içinde'],
            ['%s ay önce', '%s ay içinde'],
            ['1 yıl önce', '1 yıl içinde'],
            ['%s yıl önce', '%s yıl içinde']
          ][index];
        });
        var locale = '<?php echo $lang ?? "tr"; ?>';
        var nodes = document.querySelectorAll('.timeago');
        if (nodes.length > 0) try { timeago.render(nodes, locale); } catch(e){}
        setInterval(function(){
            var n = document.querySelectorAll('.timeago:not([timeago-id])');
            if (n.length > 0) try { timeago.render(n, locale); } catch(e){}
        }, 1000);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initTimeago);
    else initTimeago();
})();
</script>
<?php

// Default Meta Values
$og_title = 'Kalkan Social - Events & Community';
$og_desc = "Discover events in Kalkan and join the community.";
$og_image = $base_url . '/assets/og.png';
$og_type = 'website';

// Dynamic Logic
if (isset($post) && !empty($post)) { // Single Post
    // Post Detail (WhatsApp, Messenger, Facebook link preview)
    $author = !empty($post['full_name']) ? $post['full_name'] : ($post['username'] ?? 'User');
    $og_title = $author . (($lang ?? 'tr') === 'en' ? "'s Post on Kalkan Social" : " - Kalkan Social'da paylaşım");
    
    // Description: post content or shared content
    $desc_text = !empty($post['content']) ? $post['content'] : ($post['shared_content'] ?? '');
    if (!empty($desc_text)) {
        $og_desc = mb_substr(strip_tags($desc_text), 0, 150) . (mb_strlen(strip_tags($desc_text)) > 150 ? '...' : '');
    }
    
    // Image: post image, media, link preview image, or shared post image
    $img_src = null;
    if (!empty($post['image_url'])) $img_src = $post['image_url'];
    elseif (!empty($post['image'])) $img_src = $post['image'];
    elseif (!empty($post['media_url']) && ($post['media_type'] ?? '') == 'image') $img_src = $post['media_url'];
    elseif (!empty($post['link_image'])) $img_src = $post['link_image'];
    elseif (!empty($post['shared_media']) && ($post['shared_media_type'] ?? '') == 'image') $img_src = $post['shared_media'];
    if ($img_src) {
        $og_image = strpos($img_src, 'http') === 0 ? $img_src : $base_url . '/' . ltrim($img_src, '/');
    }
    $og_type = 'article';

} elseif (isset($event) && !empty($event)) { // Event Detail
    $og_title = $event['title'] . ' - Kalkan Events';
    if (!empty($event['description'])) {
        $og_desc = mb_substr(strip_tags($event['description']), 0, 150) . '...';
    }
    $ev_img = $event['cover_image'] ?? $event['image_url'] ?? null;
    if (!empty($ev_img)) {
        $og_image = strpos($ev_img, 'http') === 0 ? $ev_img : $base_url . '/' . ltrim($ev_img, '/');
    }
    $og_type = 'event';

} elseif (isset($business) && !empty($business)) { // Business Detail
    $og_title = $business['name'] . ' - Kalkan Directory';
    if (!empty($business['description'])) {
        $og_desc = mb_substr(strip_tags($business['description']), 0, 150) . '...';
    } else {
        $og_desc = ($lang ?? 'tr') == 'en' ? 'Discover ' . $business['name'] . ' on Kalkan Social' : 'Kalkan Social\'da ' . $business['name'] . '\'ı keşfet';
    }
    $biz_cover = $business['cover_photo'] ?? null;
    if (!empty($biz_cover)) {
        $og_image = strpos($biz_cover, 'http') === 0 ? $biz_cover : $base_url . '/' . ltrim($biz_cover, '/');
    }
    $og_type = 'website';

} elseif (isset($property) && !empty($property)) { // Property Detail
    $og_title = $property['title'] . ' - Kalkan Real Estate';
    if (!empty($property['description'])) {
        $og_desc = mb_substr(strip_tags($property['description']), 0, 150) . '...';
    }
    // Property images usually in separate table or first one in array, checking 'images' var or logic in property_detail
    // In property_detail.php: $images = $i_stmt->fetchAll();
    // But header_css is included inside the page, so $images might be available if defined before include.
    // However, reliance on local variables can be tricky. Let's check likely vars.
    // Usually property logic sets a main image. If not available easily, fallback to default.
    // Re-checking property_detail.php: $images is fetched.
    if (isset($images) && !empty($images) && isset($images[0]['image_path'])) {
         $path = $images[0]['image_path'];
         $og_image = strpos($path, 'http') === 0 ? $path : $base_url . '/' . ltrim($path, '/');
    }
    $og_type = 'website';
    
} elseif (isset($listing) && !empty($listing)) { // Marketplace Listing
    $og_title = $listing['title'] . ' - Kalkan Marketplace';
    if (!empty($listing['description'])) {
        $og_desc = mb_substr(strip_tags($listing['description']), 0, 150) . '...';
    }
    if (!empty($listing['image'])) {
         $og_image = strpos($listing['image'], 'http') === 0 ? $listing['image'] : $base_url . '/' . ltrim($listing['image'], '/');
    }
} elseif (isset($group) && !empty($group)) { // Group Detail
     $og_title = $group['name'] . ' - Kalkan Social Group';
     if (!empty($group['description'])) {
        $og_desc = mb_substr(strip_tags($group['description']), 0, 150) . '...';
    }
    if (!empty($group['cover_image'])) {
         $og_image = strpos($group['cover_image'], 'http') === 0 ? $group['cover_image'] : $base_url . '/' . ltrim($group['cover_image'], '/');
    }
}

// Clean output
$og_title = htmlspecialchars($og_title);
$og_desc = htmlspecialchars($og_desc);
$og_image = htmlspecialchars($og_image);
$og_url = htmlspecialchars($current_url);

$lang = isset($lang) ? $lang : 'en';
$page_desc = $lang == 'en' ? 'Discover live music, parties and happy hour events in Kalkan. The local social network for events and news.' : 'Kalkan\'daki en güncel canlı müzik, parti ve sosyal etkinliklerden haberdar olun. Etkinlikler ve haberler için yerel sosyal ağ.';

// Override page description if OG description is more specific
if (isset($post) || isset($event) || isset($property) || isset($listing) || isset($business)) {
    $page_desc = $og_desc;
}
?>
<!-- Open Graph / Facebook -->
<!-- Facebook App ID -->
<meta property="fb:app_id" content="<?php echo htmlspecialchars(env_value('FACEBOOK_APP_ID_PUBLIC', env_value('FACEBOOK_APP_ID', '')), ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="<?php echo $og_type; ?>">
<meta property="og:url" content="<?php echo $og_url; ?>">
<meta property="og:title" content="<?php echo $og_title; ?>">
<meta property="og:description" content="<?php echo $og_desc; ?>">
<meta property="og:image" content="<?php echo $og_image; ?>">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo $og_url; ?>">
<meta property="twitter:title" content="<?php echo $og_title; ?>">
<meta property="twitter:description" content="<?php echo $og_desc; ?>">
<meta property="twitter:image" content="<?php echo $og_image; ?>">

<link rel="canonical" href="<?php echo explode('?', $current_url)[0]; ?>">
<meta name="description" content="<?php echo $page_desc; ?>">

<!-- Global Favicons (Ensure consistency) -->
<!-- Global Favicons (Ensure consistency) -->
<link rel="icon" href="/logo.jpg">
<link rel="icon" type="image/jpeg" sizes="32x32" href="/logo.jpg">
<link rel="icon" type="image/jpeg" sizes="16x16" href="/logo.jpg">
<link rel="icon" type="image/jpeg" sizes="192x192" href="/logo.jpg">
<link rel="icon" type="image/jpeg" sizes="512x512" href="/logo.jpg">
<link rel="apple-touch-icon" href="/logo.jpg">
<link rel="manifest" href="/manifest.php">

<!-- Local Tailwind CSS / CDN Fallback Logic -->
<?php 
// Include CDN helper if not already included
if (!defined('CDN_ENABLED')) {
    require_once __DIR__ . '/cdn_helper.php';
}

// Absolute path check for file existence
$local_css_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/css/main.min.css';

// Use CDN URL if enabled (assumes files are uploaded to CDN)
if (defined('CDN_ENABLED') && CDN_ENABLED) {
    $css_url = CDN_BASE_URL . '/css/main.min.css?v=' . ASSET_VERSION;
} elseif (file_exists($local_css_path) && filesize($local_css_path) > 0) {
    $css_url = '/assets/css/main.min.css?v=' . filemtime($local_css_path);
} else {
    $css_url = null; // Fallback to Tailwind CDN
}

if ($css_url): ?>
    <!-- Loading CSS synchronously to prevent FOUC -->
    <link rel="stylesheet" href="<?php echo $css_url; ?>">
<?php else: ?>
    <!-- CDN Fallback -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { 
                extend: { 
                    colors: { 
                        slate: { 900: '#0f172a', 800: '#1e293b' } 
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                } 
            }
        }
    </script>
<?php endif; ?>

<!-- HTMX Library for Dynamic Interactions -->
<script src="https://unpkg.com/htmx.org@2.0.4" integrity="sha384-HGfztofotfshcF7+8n44JQL2oJmowVChPTg48S+jvZoztPfvwD79OC/LTtG6dMp+" crossorigin="anonymous" defer></script>
<style>
    /* HTMX Loading Indicators */
    .htmx-indicator { opacity: 0; transition: opacity 200ms ease-in; }
    .htmx-request .htmx-indicator { opacity: 1; }
    .htmx-request.htmx-indicator { opacity: 1; }
    
    /* Like button animation during HTMX request */
    
    /* Reaction bar - hidden by default, shown via JS on long-press */
    .reaction-bar { 
        display: none; 
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.2s ease-out;
    }
    .reaction-bar.show { 
        display: flex !important; 
        opacity: 1;
        transform: translateY(0);
    }

    /* Glowing pulse animation for FAB button */
    @keyframes pulse-glow {
        0%, 100% {
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.5);
        }
        50% {
            box-shadow: 0 10px 35px -5px rgba(37, 99, 235, 0.7), 0 0 20px rgba(59, 130, 246, 0.4);
        }
    }
    .animate-pulse-glow {
        animation: pulse-glow 2s ease-in-out infinite;
    }
</style>

<!-- Long-Press Reaction Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initReactionGestures();
    
    // Re-init on Swup content replace
    document.addEventListener('swup:contentReplace', initReactionGestures);
    document.addEventListener('swup:pageview', initReactionGestures);
});

function initReactionGestures() {
    // Clean up old listeners if needed (optional, but good practice)
    
    // Use easier to manage individual listeners or improved delegation
    // We will stick to delegation but with better mobile support
    
    let pressTimer = null;
    let isLongPress = false;
    let startX = 0;
    let startY = 0;

    const handleStart = (e) => {
        const likeBtn = e.target.closest('[id^="like-btn-"]');
        if (!likeBtn) return;
        
        isLongPress = false;
        
        // Store coordinates to detect movement
        if(e.type === 'touchstart') {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        } else {
            startX = e.clientX;
            startY = e.clientY;
        }
        
        const wrapper = likeBtn.closest('.group\\/reaction'); // Find parent
        const reactionBar = wrapper?.querySelector('.reaction-bar');
        
        // Prevent default context menu on mobile
        likeBtn.oncontextmenu = function(event) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        };
        
        pressTimer = setTimeout(function() {
            isLongPress = true;
            if (reactionBar) {
                // Hide all other open bars
                document.querySelectorAll('.reaction-bar.show').forEach(b => {
                    if(b !== reactionBar) b.classList.remove('show');
                });
                
                reactionBar.classList.add('show');
                
                // Vibrate on mobile for feedback
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            }
        }, 500);
    };
    
    const handleEnd = (e) => {
        const likeBtn = e.target.closest('[id^="like-btn-"]');
        if (!likeBtn) return;
        
        clearTimeout(pressTimer);
        
        if (isLongPress) {
            // If it was a long press, prevent the click event from firing proper
            e.preventDefault(); 
            e.stopPropagation();
        }
    };
    
    const handleMove = (e) => {
        if (!pressTimer) return;
        
        let clientX, clientY;
        if(e.type === 'touchmove') {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }
        
        // If moved more than 10px, cancel long press
        if (Math.abs(clientX - startX) > 10 || Math.abs(clientY - startY) > 10) {
            clearTimeout(pressTimer);
        }
    };

    // Remove existing listeners to avoid duplicates if re-inited
    document.body.removeEventListener('touchstart', handleStart);
    document.body.removeEventListener('touchend', handleEnd);
    document.body.removeEventListener('touchmove', handleMove);
    document.body.removeEventListener('mousedown', handleStart);
    document.body.removeEventListener('mouseup', handleEnd);

    // Add Listeners
    document.body.addEventListener('touchstart', handleStart, { passive: true });
    document.body.addEventListener('touchend', handleEnd, { passive: false }); // not passive to allow preventDefault
    document.body.addEventListener('touchmove', handleMove, { passive: true });
    
    // Mouse fallback
    document.body.addEventListener('mousedown', handleStart);
    document.body.addEventListener('mouseup', handleEnd);

    
    // Close reaction bar when clicking outside
    document.body.addEventListener('click', function(e) {
        if (!e.target.closest('.reaction-bar') && !e.target.closest('[id^="like-btn-"]')) {
            document.querySelectorAll('.reaction-bar.show').forEach(bar => {
                bar.classList.remove('show');
            });
        }
    }, true);
    
    // Close reaction bar after selecting a reaction
    document.body.addEventListener('htmx:afterRequest', function(e) {
        document.querySelectorAll('.reaction-bar.show').forEach(bar => {
            bar.classList.remove('show');
        });
    });
}
</script>

<!-- Swup.js - Smooth Page Transitions -->
<script src="https://unpkg.com/swup@4" defer></script>
<script src="https://unpkg.com/@swup/preload-plugin@3" defer></script>
<script src="https://unpkg.com/@swup/fade-theme@2" defer></script>
<script src="https://unpkg.com/@swup/head-plugin@2" defer></script>
<script src="https://unpkg.com/@swup/progress-plugin@3" defer></script>

<style>
    /* Swup Page Transition Animations */
    html.is-changing .transition-main {
        transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
    }
    html.is-animating .transition-main {
        opacity: 0;
        transform: translateY(10px);
    }
    
    /* Customize the official progress bar */
    .swup-progress-bar {
        height: 4px;
        background: linear-gradient(90deg, #ec4899, #8b5cf6, #3b82f6);
        box-shadow: 0 0 10px rgba(236, 72, 153, 0.5);
        z-index: 2147483647 !important; /* Force max z-index */
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Swup to load
    const interval = setInterval(() => {
        if (typeof Swup !== 'undefined' && 
            typeof SwupPreloadPlugin !== 'undefined' && 
            typeof SwupFadeTheme !== 'undefined' && 
            typeof SwupHeadPlugin !== 'undefined' &&
            typeof SwupProgressPlugin !== 'undefined') {
            
            clearInterval(interval);
            initSwup();
        }
    }, 100);
});

function initSwup() {
    if (window.swupInitialized) return;
    window.swupInitialized = true;
    
    try {
        const swup = new Swup({
            containers: ['#swup-main'],
            animationSelector: '[class*="transition-"]',
            cache: true,
            plugins: [
                new SwupPreloadPlugin(),
                new SwupFadeTheme(),
                new SwupHeadPlugin(),
                new SwupProgressPlugin({
                    className: 'swup-progress-bar',
                    transition: 300,
                    delay: 0,
                    initialValue: 0.25,
                    hideImmediately: false
                })
            ],
            // Ignore problematic links
            linkSelector: 'a[href^="/"]:not([data-no-swup]):not([target="_blank"]):not([href*="api/"]):not([href*="login"]):not([href*="register"]):not([href*="logout"]):not([href*="auth_"]), a[href^="./"]:not([data-no-swup]):not([target="_blank"]), a[href^="?"]:not([data-no-swup])'
        });
        
        // Re-initialize scripts
        swup.hooks.on('content:replace', () => {
            // Re-init HTMX
            if (typeof htmx !== 'undefined') {
                htmx.process(document.body);
            }
            // Trigger scroll to top if needed
            requestAnimationFrame(() => {
                window.scrollTo(0, 0);
            });
            
            // Dispatch event for other scripts to re-init
            document.dispatchEvent(new CustomEvent('swup:pageview'));
        });
        
        console.log('Swup initialized with official progress plugin');
        
    } catch (e) {
        console.error('Swup initialization failed:', e);
    }
}
</script>

<!-- Non-blocking Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<?php
// Editor.js: Lazy load - only on create_post_page (other pages load on modal open)
if (!empty($needs_editor_early)): ?>
<!-- Editor.js & Plugins (create_post_page only - others load on demand) -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest" defer></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/paragraph@latest" defer></script>
<?php endif; ?>
<style>
    /* Editor.js Mobile Responsive Styles */
    .ce-block__content, .ce-toolbar__content { max-width: 100%; margin-left: 0; }
    .codex-editor__redactor { padding-bottom: 20px !important; }
    .ce-header { font-weight: 800; }
    .ce-block--selected .ce-block__content { background: rgba(0,85,255,0.1); }
    
    /* Mobile: Make toolbar always visible and inline */
    @media (max-width: 768px) {
        .ce-toolbar { position: relative !important; transform: none !important; }
        .ce-toolbar__content { max-width: 100% !important; }
        .ce-toolbar__plus { 
            position: relative !important; 
            left: 0 !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-right: 8px;
        }
        .ce-toolbar__actions { 
            position: relative !important;
            right: 0 !important;
            opacity: 1 !important;
        }
        .ce-popover {
            position: fixed !important;
            left: 10px !important;
            right: 10px !important;
            bottom: 10px !important;
            top: auto !important;
            max-height: 50vh;
            overflow-y: auto;
            border-radius: 16px !important;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.2) !important;
        }
        .ce-popover__container {
            max-width: 100% !important;
        }
        .ce-inline-toolbar {
            transform: none !important;
            left: 10px !important;
            right: 10px !important;
            width: auto !important;
        }
    }
    
    /* Ensure editor has minimum touch-friendly height */
    #editorjs-page, #editorjs {
        min-height: 150px;
        padding: 12px;
    }
    #editorjs-page .ce-paragraph, #editorjs .ce-paragraph {
        font-size: 16px;
        line-height: 1.6;
    }
</style>
<?php if (defined('CDN_ENABLED') && CDN_ENABLED): ?>
<link rel="preconnect" href="<?php echo CDN_BASE_URL; ?>" crossorigin>
<?php endif; ?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=optional" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=optional"></noscript>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<?php 
// Font preload URLs - use CDN if available
$font_base = (defined('CDN_ENABLED') && CDN_ENABLED) ? CDN_BASE_URL . '/fonts' : '/assets/css';
?>
<link rel="preload" href="<?php echo $font_base; ?>/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?php echo $font_base; ?>/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>

<!-- PageSpeed Optimization: Ensure text remains visible during webfont load -->
<style>
    @font-face {
        font-family: 'Font Awesome 6 Free';
        font-style: normal;
        font-weight: 900;
        font-display: swap;
        src: url(<?php echo $font_base; ?>/fa-solid-900.woff2) format('woff2'),
             url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2) format('woff2');
    }
    @font-face {
        font-family: 'Font Awesome 6 Free';
        font-style: normal;
        font-weight: 400;
        font-display: swap;
        src: url(<?php echo $font_base; ?>/fa-regular-400.woff2) format('woff2'),
             url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2) format('woff2');
    }
    @font-face {
        font-family: 'Font Awesome 6 Brands';
        font-style: normal;
        font-weight: 400;
        font-display: swap;
        src: url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2) format('woff2'),
             url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.ttf) format('truetype');
    }
</style>

<!-- Critical CSS to prevent FOUC/Layout Shift -->
<style>
    /* Cage the Logo & Header until CSS loads */
    header { height: 64px !important; }
    header img { width: 32px !important; height: 32px !important; }
    header .container { display: flex !important; justify-content: space-between !important; align-items: center !important; height: 64px !important; }
    
    /* PWA Nav stability */
    nav.fixed.bottom-0 { height: 72px !important; }
    
    /* Prevent modals from exploding - Handled by Tailwind hidden class */
    
    /* Mobile/Desktop responsive tweaks matching Tailwind */
    @media (min-width: 640px) {
        header { height: 80px !important; }
        header img { width: 40px !important; height: 40px !important; }
        header .container { height: 80px !important; }
    }
    /* Prevent raw unstyled content flashes - REMOVED to fix missing Navbar */
    /* Icon Stabilization */
    .fas, .far, .fab {
        display: inline-block;
        min-width: 1em;
        width: 1.25em; /* Fixed width to prevent CLS */
        text-align: center;
        height: auto;
        line-height: inherit;
        vertical-align: middle;
    }

    /* Glassmorphism Widget Style */
    .glass-widget {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 16px 0 rgba(31, 38, 135, 0.07);
    }
    .dark .glass-widget {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    @keyframes weather-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }
    .weather-anim {
        animation: weather-float 3s ease-in-out infinite;
    }

    /* ===== MOBILE-FIRST PWA OPTIMIZATIONS ===== */
    
    /* Prevent horizontal scroll globally */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
    
    /* Smoother touch scrolling */
    * {
        -webkit-overflow-scrolling: touch;
    }
    
    /* Touch-friendly tap targets (minimum 44px) */
    a, button, input[type="button"], input[type="submit"], select {
        min-height: 44px;
    }
    
    /* Prevent text size adjustment on orientation change */
    html {
        -webkit-text-size-adjust: 100%;
        text-size-adjust: 100%;
    }
    
    /* Better overscroll behavior for PWA */
    html, body {
        overscroll-behavior-y: auto !important; 
        -webkit-overflow-scrolling: touch;
    }

    /* CRITICAL FIX: Ensure scrolling works on all browsers */
    html {
        overflow-y: scroll; /* Force scrollbar space to prevent layout jump */
        height: auto;
    }
    body {
        overflow-y: auto;
        min-height: 100vh;
        -webkit-overflow-scrolling: touch !important;
    }
    
    /* Better mobile form inputs */
    input, textarea, select {
        font-size: 16px; /* Prevents iOS zoom on focus */
    }
    
    /* Safe area insets for notched devices */
    body {
        padding-left: env(safe-area-inset-left);
        padding-right: env(safe-area-inset-right);
    }
    
    /* Bottom nav safe area padding */
    .pb-safe {
        padding-bottom: max(env(safe-area-inset-bottom), 0.75rem);
    }
    
    /* Hide scrollbars but allow scrolling */
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    
    /* ===== ANDROID APP WEBVIEW FIX ===== */
    /* When inside Android WebView, ensure scroll works */
    .android-app body,
    .android-app html {
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
        overscroll-behavior-y: auto !important;
        height: auto !important;
        min-height: 100vh !important;
    }
    
    .android-app * {
        touch-action: pan-y pan-x !important;
    }
</style>

<!-- Theme Check -->
<script>
    if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
</script>
<style>body { font-family: 'Outfit', sans-serif; font-display: optional; }</style>
