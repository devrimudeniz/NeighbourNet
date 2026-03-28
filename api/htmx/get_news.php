<?php
/**
 * HTMX News Endpoint
 * Returns HTML partial for news grid
 */

require_once '../../includes/db.php';
require_once '../../includes/lang.php';
require_once '../../includes/news_fetcher.php';

// Fetch news
$news_items = fetchNews();

// Filter options
$filter = $_GET['filter'] ?? 'all';
if ($filter === 'uk') {
    $news_items = array_filter($news_items, function($item) {
        return $item['is_uk'];
    });
} elseif ($filter === 'tr') {
    $news_items = array_filter($news_items, function($item) {
        return $item['is_tr'];
    });
}

$total = count($news_items);
$articles_label = $lang == 'en' ? ($total === 1 ? 'article' : 'articles') : 'haber';
if (empty($news_items)): ?>
    <!-- No News -->
    <div class="text-center py-12 sm:py-16">
        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-slate-200 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-inbox text-3xl sm:text-4xl text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold text-slate-600 dark:text-slate-400">
            <?php echo $lang == 'en' ? 'No news found' : 'Haber bulunamadı'; ?>
        </h2>
        <p class="text-slate-500 dark:text-slate-500 mt-2 text-sm max-w-sm mx-auto">
            <?php echo $lang == 'en' ? 'Try another filter or check back later.' : 'Başka filtre deneyin veya daha sonra tekrar bakın.'; ?>
        </p>
    </div>
<?php else: ?>
    <!-- Result count -->
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 sm:mb-5">
        <?php echo $total; ?> <?php echo $articles_label; ?>
    </p>
    <!-- News Grid: 1 col mobile, 2 tablet, 3 desktop -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <?php foreach ($news_items as $item): ?>
            <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener noreferrer" 
               class="group block bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500/50 transition-all duration-200 hover:-translate-y-0.5 active:scale-[0.99] min-h-[280px] flex flex-col">
                
                <!-- Image -->
                <div class="aspect-[16/10] sm:aspect-video bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-600 relative overflow-hidden flex-shrink-0">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" class="w-full h-full object-cover" loading="lazy">
                    <?php else: ?>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-newspaper text-3xl sm:text-4xl text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['is_uk']): ?>
                        <span class="absolute top-2 left-2 bg-blue-600 text-white text-[10px] sm:text-xs font-bold px-2 py-0.5 sm:py-1 rounded-md shadow-sm z-10">🇬🇧 UK</span>
                    <?php elseif ($item['is_tr']): ?>
                        <span class="absolute top-2 left-2 bg-red-600 text-white text-[10px] sm:text-xs font-bold px-2 py-0.5 sm:py-1 rounded-md shadow-sm z-10">🇹🇷 TR</span>
                    <?php endif; ?>
                </div>
                
                <div class="p-4 sm:p-5 flex flex-col flex-1">
                    <div class="flex items-center justify-between gap-2 mb-2 min-h-[1.25rem]">
                        <span class="font-bold text-blue-600 dark:text-blue-400 text-[11px] sm:text-xs uppercase tracking-wider truncate">
                            <?php echo htmlspecialchars($item['source']); ?>
                        </span>
                        <time class="text-slate-400 dark:text-slate-500 text-xs flex-shrink-0" datetime="<?php echo $item['date']; ?>">
                            <?php echo $item['date_human']; ?>
                        </time>
                    </div>
                    <h2 class="font-bold text-slate-800 dark:text-white text-base sm:text-lg leading-snug mb-2 sm:mb-3 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors flex-1">
                        <?php echo htmlspecialchars($item['title']); ?>
                    </h2>
                    <?php if (!empty($item['description'])): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mb-3 hidden sm:block">
                            <?php 
                        $desc = (string)$item['description']; 
                        $short = function_exists('mb_substr') ? mb_substr($desc, 0, 120) : substr($desc, 0, 120); 
                        echo htmlspecialchars($short); 
                        echo (function_exists('mb_strlen') ? mb_strlen($desc) : strlen($desc)) > 120 ? '…' : ''; 
                        ?>
                        </p>
                    <?php endif; ?>
                    <span class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-600 dark:text-blue-400 mt-auto">
                        <?php echo $lang == 'en' ? 'Read' : 'Oku'; ?>
                        <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5" aria-hidden="true"></i>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    
    <p class="text-center mt-8 sm:mt-10 text-xs text-slate-400 dark:text-slate-500">
        <i class="fas fa-sync-alt mr-1" aria-hidden="true"></i>
        <?php echo $lang == 'en' ? 'Updates several times a day' : 'Günde birkaç kez güncellenir'; ?>
    </p>
<?php endif; ?>
