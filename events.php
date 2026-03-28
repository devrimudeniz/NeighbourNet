<?php
require_once 'includes/bootstrap.php';

// Get Filter
$category = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$user_id = $_SESSION['user_id'] ?? 0;

require_once 'includes/CacheHelper.php';
$cache = new CacheHelper();
$cacheKey = "events_cat_{$category}_date_" . date('YmdH'); // Hourly cache key

// Try Cache only for guests to improve SEO & Performance
$grouped_events = [];
$is_cached = false;

if ($user_id == 0 && ($cachedData = $cache->get($cacheKey))) {
    $grouped_events = $cachedData;
    $is_cached = true;
} else {
    // Build Query for Events (venue fallback: venue_name or full_name)
    $sql = "SELECT e.*, COALESCE(NULLIF(TRIM(u.venue_name), ''), u.full_name, u.username) as venue_display";
    if ($user_id > 0) {
        $sql .= ", (SELECT COUNT(*) FROM likes WHERE user_id = ? AND event_id = e.id) as is_liked";
    } else {
        $sql .= ", 0 as is_liked";
    }
    $sql .= " FROM events e JOIN users u ON e.user_id = u.id WHERE e.event_date >= CURDATE() AND e.status = 'approved'";
    $params = [];
    if ($user_id > 0) {
        $params[] = $user_id;
    }

    if ($category !== 'all') {
        $sql .= " AND e.category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY e.event_date ASC, e.start_time ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    // Group by Date
    foreach ($events as $event) {
        $grouped_events[$event['event_date']][] = $event;
    }
    
    // Save to cache if guest (1 Hour)
    if ($user_id == 0) {
        $cache->set($cacheKey, $grouped_events, 3600);
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="h-full <?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Events' : 'Etkinlikler'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>

    <main id="swup-main" class="transition-main container mx-auto px-4 sm:px-6 pt-24 pb-20 max-w-6xl">
        
        <!-- Hero & Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                    <span class="w-12 h-12 rounded-2xl bg-violet-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/40">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </span>
                    <?php echo $lang == 'en' ? 'Events' : 'Etkinlikler'; ?>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm sm:text-base">
                    <?php echo $lang == 'en' ? 'Discover what\'s on in Kalkan' : 'Kalkan\'da ne var ne yok keşfet'; ?>
                </p>
            </div>
            <?php if ($user_id > 0): ?>
            <a href="add_event" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-5 py-3 rounded-xl font-bold shadow-lg shadow-violet-500/30 transition-all shrink-0">
                <i class="fas fa-plus-circle"></i>
                <?php echo $lang == 'en' ? 'Add Your Event' : 'Etkinlik Ekle'; ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Category Filters Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden mb-8">
            <div class="p-4 sm:p-5 overflow-x-auto hide-scrollbar">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 flex items-center gap-2">
                    <i class="fas fa-filter text-violet-500"></i>
                    <?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?>
                </h3>
                <div class="flex flex-wrap sm:flex-nowrap gap-2 min-w-0">
                    <a href="?cat=all" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'all' ? 'bg-slate-800 text-white dark:bg-violet-500 dark:text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>🔥</span>
                        <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?>
                    </a>
                    <a href="?cat=Music" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'Music' ? 'bg-pink-500 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>🎵</span>
                        <?php echo $lang == 'en' ? 'Live Music' : 'Canlı Müzik'; ?>
                    </a>
                    <a href="?cat=Party" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'Party' ? 'bg-violet-500 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>🎧</span>
                        <?php echo $lang == 'en' ? 'Party & DJ' : 'Parti & DJ'; ?>
                    </a>
                    <a href="?cat=Food" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'Food' ? 'bg-orange-500 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>🍷</span>
                        <?php echo $lang == 'en' ? 'Food & Wine' : 'Yemek & Şarap'; ?>
                    </a>
                    <a href="?cat=Boat Trip" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'Boat Trip' ? 'bg-blue-500 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>⚓</span>
                        <?php echo $lang == 'en' ? 'Boat Trips' : 'Tekne Turları'; ?>
                    </a>
                    <a href="?cat=Market" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'Market' ? 'bg-green-500 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>🛍️</span>
                        <?php echo $lang == 'en' ? 'Local Market' : 'Pazar Yeri'; ?>
                    </a>
                    <a href="?cat=Wellness" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'Wellness' ? 'bg-teal-500 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>🧘</span>
                        <?php echo $lang == 'en' ? 'Wellness' : 'Sağlık & Spor'; ?>
                    </a>
                    <a href="?cat=Other" class="whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == 'Other' ? 'bg-slate-600 text-white shadow-md' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500'; ?>">
                        <span>✨</span>
                        <?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?>
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($grouped_events)): ?>
            <!-- Empty State -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden text-center py-16 sm:py-24 px-6">
                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-violet-100 to-fuchsia-100 dark:from-violet-900/30 dark:to-fuchsia-900/30 flex items-center justify-center">
                    <i class="fas fa-calendar-plus text-4xl text-violet-500 dark:text-violet-400"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
                    <?php echo $lang == 'en' ? 'No events scheduled yet' : 'Henüz planlanmış etkinlik yok'; ?>
                </h2>
                <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-sm mx-auto text-sm">
                    <?php echo $lang == 'en' ? 'Be the first to add an event! Your submission will be reviewed by admins.' : 'İlk etkinliği sen ekle! Gönderin admin onayından geçecektir.'; ?>
                </p>
                <?php if ($user_id > 0): ?>
                <a href="add_event" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-6 py-3.5 rounded-xl font-bold shadow-lg shadow-violet-500/30 hover:shadow-xl transition-all">
                    <i class="fas fa-plus"></i>
                    <?php echo $lang == 'en' ? 'Add Your Event' : 'Etkinlik Ekle'; ?>
                </a>
                <?php else: ?>
                <a href="login?redirect=add_event" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-6 py-3.5 rounded-xl font-bold shadow-lg shadow-violet-500/30 hover:shadow-xl transition-all">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $lang == 'en' ? 'Login to Add Event' : 'Etkinlik Eklemek İçin Giriş Yap'; ?>
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_events as $date => $day_events): 
                // Turkish Date using IntlDateFormatter
                $d = new DateTime($date);
                // Localized Date
                $locale = ($lang == 'en') ? 'en_GB' : 'tr_TR';
                $formatter = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
                $date_str = $formatter->format($d);
                
                if ($date == date('Y-m-d')) {
                    $date_str = ($lang == 'en') ? "Today 🔥" : "Bugün 🔥";
                } elseif ($date == date('Y-m-d', strtotime('+1 day'))) {
                    $date_str = ($lang == 'en') ? "Tomorrow 🚀" : "Yarın 🚀";
                }
            ?>
            <div class="mb-10">
                <h3 class="text-xl font-bold mb-5 flex items-center gap-3 sticky top-36 z-30 py-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur w-max px-4 rounded-full border border-slate-200 dark:border-slate-700 shadow-xl text-slate-900 dark:text-white transition-colors">
                    <span class="w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
                    <?php echo $date_str; ?>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($day_events as $event): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-700 shadow-2xl group relative transition-colors duration-300">
                        <!-- Image -->
                        <div class="h-64 relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent z-10 opacity-70"></div>
                            <?php if ($event['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($event['image_url']); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                            <?php else: ?>
                                <div class="w-full h-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-slate-400 dark:text-slate-600"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Category Badge -->
                            <div class="absolute top-4 right-4 z-20 flex flex-col gap-1 items-end">
                                <?php 
                                $evt_date = $event['event_date'];
                                if ($evt_date == date('Y-m-d')): ?>
                                <span class="bg-amber-500 text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-lg"><?php echo $lang == 'en' ? 'TODAY' : 'BUGÜN'; ?></span>
                                <?php elseif ($evt_date == date('Y-m-d', strtotime('+1 day'))): ?>
                                <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-lg"><?php echo $lang == 'en' ? 'TOMORROW' : 'YARIN'; ?></span>
                                <?php endif; ?>
                                <span class="bg-black/50 backdrop-blur-md text-white px-3 py-1 rounded-full text-xs font-bold border border-white/10 uppercase tracking-wider">
                                    <?php echo $event['category']; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6 relative z-20 -mt-20">
                            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl p-5 rounded-2xl border border-slate-200 dark:border-slate-600/50 shadow-xl transition-colors duration-300">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-xs font-bold uppercase tracking-widest text-pink-500 dark:text-pink-400 mb-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i> 
                                        <?php echo htmlspecialchars($event['venue_display'] ?? $event['venue_name'] ?? ''); ?>
                                    </h4>
                                    <span class="bg-slate-100 dark:bg-white text-slate-900 px-3 py-1 rounded-lg text-xs font-black">
                                        <?php echo date('H:i', strtotime($event['start_time'])); ?>
                                    </span>
                                </div>
                                
                                <h3 class="text-xl font-bold mb-2 leading-tight text-slate-900 dark:text-white"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed mb-4">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                                
                                <?php if(!empty($event['event_location'])): ?>
                                <p class="text-xs text-slate-400 mb-4 flex items-center gap-1">
                                    <i class="fas fa-location-arrow"></i> <?php echo htmlspecialchars($event['event_location']); ?>
                                </p>
                                <?php endif; ?>

                                <div class="flex gap-2 flex-wrap">
                                    <a href="event_detail?id=<?php echo $event['id']; ?>" class="flex-1 min-w-[100px] bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white py-3 rounded-xl text-sm font-bold transition-colors text-center border border-slate-200 dark:border-transparent">
                                        <?php echo $lang == 'en' ? 'Details' : 'Detaylar'; ?>
                                    </a>
                                    <a href="api/get_event_ics.php?id=<?php echo $event['id']; ?>" class="flex items-center justify-center gap-1.5 bg-violet-100 dark:bg-violet-900/30 hover:bg-violet-200 dark:hover:bg-violet-900/50 text-violet-600 dark:text-violet-400 py-3 px-4 rounded-xl text-sm font-bold transition-colors border border-violet-200 dark:border-violet-700" title="<?php echo $lang == 'en' ? 'Add to Calendar' : 'Takvimime Ekle'; ?>">
                                        <i class="far fa-calendar-plus"></i>
                                    </a>
                                    <button onclick="toggleEventLike(this, <?php echo $event['id']; ?>)" aria-label="Like event" class="favorite-btn flex-1 min-w-[60px] bg-slate-100 dark:bg-slate-700/50 hover:bg-pink-100 dark:hover:bg-pink-600/20 <?php echo $event['is_liked'] ? 'text-pink-500' : 'text-slate-400'; ?> hover:text-pink-500 py-3 rounded-xl text-sm font-bold transition-colors text-center flex items-center justify-center gap-2 border border-slate-200 dark:border-transparent">
                                        <i class="<?php echo $event['is_liked'] ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    


    <script>
    function toggleEventLike(btn, eventId) {
        const icon = btn.querySelector('i');
        const isLiked = icon.classList.contains('fas');
        
        // Optimistic UI Update
        if (isLiked) {
            icon.classList.replace('fas', 'far');
            btn.classList.add('text-slate-400');
            btn.classList.remove('text-pink-500');
        } else {
            icon.classList.replace('far', 'fas');
            btn.classList.add('text-pink-500');
            btn.classList.remove('text-slate-400');
        }

        const formData = new FormData();
        formData.append('event_id', eventId);

        fetch('api/like.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'error') {
                // Revert UI Update on error
                if (isLiked) {
                    icon.classList.replace('far', 'fas');
                    btn.classList.add('text-pink-500');
                    btn.classList.remove('text-slate-400');
                } else {
                    icon.classList.replace('fas', 'far');
                    btn.classList.add('text-slate-400');
                    btn.classList.remove('text-pink-500');
                }
                alert(data.message);
            }
        })
        .catch(err => {
            console.error('Like error:', err);
            // Revert UI Update on error
            if (isLiked) {
                icon.classList.replace('far', 'fas');
                btn.classList.add('text-pink-500');
                btn.classList.remove('text-slate-400');
            } else {
                icon.classList.replace('fas', 'far');
                btn.classList.add('text-slate-400');
                btn.classList.remove('text-pink-500');
            }
        });
    }
    </script>
</body>
</html>
