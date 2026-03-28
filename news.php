<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';

// Filter options (for HTMX request)
$filter = $_GET['filter'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/seo_tags.php'; ?>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-24 transition-colors safe-area-pb">

    <?php include 'includes/header.php'; ?>

    <main class="mx-auto px-3 sm:px-4 pt-16 sm:pt-20 md:pt-24 max-w-6xl min-h-[60vh]">
        
        <!-- Page Header: compact on mobile -->
        <header class="text-center mb-6 sm:mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-gradient-to-br from-blue-500 to-violet-600 rounded-2xl sm:rounded-3xl mb-4 sm:mb-6 shadow-lg shadow-blue-500/25">
                <i class="fas fa-newspaper text-white text-2xl sm:text-3xl" aria-hidden="true"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white mb-2">
                <?php echo $lang == 'en' ? 'Kalkan in the News' : 'Basında Kalkan'; ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm sm:text-base max-w-xl mx-auto px-1">
                <?php echo $lang == 'en' ? 'Latest news about Kalkan and Kaş from international press' : 'Uluslararası basında Kalkan ve Kaş ile ilgili son haberler'; ?>
            </p>
        </header>

        <!-- Filter Tabs: scrollable on small screens, UI rules (no bg-white for unselected) -->
        <nav class="mb-6 sm:mb-8" aria-label="<?php echo $lang == 'en' ? 'Filter by source' : 'Kaynağa göre filtrele'; ?>">
            <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar sm:flex-wrap sm:justify-center -mx-3 px-3 sm:mx-0 sm:px-0">
                <a href="news" class="flex-shrink-0 px-5 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-98 <?php echo $filter === 'all' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600'; ?>">
                    <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?>
                </a>
                <a href="news?filter=uk" class="flex-shrink-0 px-5 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-98 <?php echo $filter === 'uk' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600'; ?>">
                    🇬🇧 UK
                </a>
                <a href="news?filter=tr" class="flex-shrink-0 px-5 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-98 <?php echo $filter === 'tr' ? 'bg-red-600 text-white shadow-lg shadow-red-500/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 hover:bg-slate-300 dark:hover:bg-slate-600'; ?>">
                    🇹🇷 TR
                </a>
            </div>
        </nav>

        <!-- News Container - HTMX -->
        <div id="news-container" 
             hx-get="api/htmx/get_news.php?filter=<?php echo htmlspecialchars($filter); ?>" 
             hx-trigger="load"
             hx-swap="innerHTML">
            
            <!-- Skeleton: 1 col mobile, 2 tablet, 3 desktop -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                <?php for($i = 0; $i < 6; $i++): ?>
                <div class="bg-slate-200 dark:bg-slate-800 rounded-2xl overflow-hidden animate-pulse">
                    <div class="aspect-[16/10] sm:aspect-video bg-slate-300 dark:bg-slate-700"></div>
                    <div class="p-4 sm:p-5">
                        <div class="flex justify-between gap-2 mb-3">
                            <div class="h-3 bg-slate-300 dark:bg-slate-600 rounded w-24"></div>
                            <div class="h-3 bg-slate-300 dark:bg-slate-600 rounded w-14"></div>
                        </div>
                        <div class="h-4 bg-slate-300 dark:bg-slate-600 rounded w-full mb-2"></div>
                        <div class="h-4 bg-slate-300 dark:bg-slate-600 rounded w-4/5 mb-3"></div>
                        <div class="h-3 bg-slate-300 dark:bg-slate-600 rounded w-full mb-1"></div>
                        <div class="h-3 bg-slate-300 dark:bg-slate-600 rounded w-2/3"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="text-center py-6">
                <i class="fas fa-circle-notch fa-spin text-xl text-blue-500" aria-hidden="true"></i>
                <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm"><?php echo $lang == 'en' ? 'Loading...' : 'Yükleniyor...'; ?></p>
            </div>
        </div>
        
    </main>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .active\:scale-98:active { transform: scale(0.98); }
        @media (min-width: 640px) { .safe-area-pb { padding-bottom: 6rem; } }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const newsItems = document.querySelectorAll('.news-image-placeholder');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const url = el.dataset.newsUrl;
                        if (url && !el.dataset.loaded) {
                            fetchImage(el, url);
                            el.dataset.loaded = 'true';
                            observer.unobserve(el);
                        }
                    }
                });
            });

            newsItems.forEach(item => observer.observe(item));
        });

        function fetchImage(element, url) {
            fetch(`api/get_og_image.php?url=${encodeURIComponent(url)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success' && data.image) {
                        const img = document.createElement('img');
                        img.src = data.image;
                        img.className = 'w-full h-full object-cover animate-fade-in';
                        img.alt = 'News Image';
                        
                        // Clear icon and append image
                        element.innerHTML = ''; 
                        element.appendChild(img);
                        
                        // Add source badge back if it existed (we need to be careful not to wipe the badge)
                        // Actually, looking at the DOM structure, the badge is sibling if absolute, or inside.
                        // In the PHP below, I will restructure to put the badge OUTSIDE the image container if possible, 
                        // or just append the image BEFORE the badge.
                        
                        // Let's rely on the PHP structure I'm about to modify.
                        // If I clear innerHTML, I lose the badge if it's inside.
                        // Use insertAdjacentElement or just hide the icon.
                    }
                })
                .catch(err => console.error('Error fetching image:', err));
        }

        // Add fade in animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            .animate-fade-in { animation: fadeIn 0.5s ease-in; }
        `;
        document.head.appendChild(style);
    </script>

    <?php include 'includes/bottom_nav.php'; ?>

</body>
</html>
