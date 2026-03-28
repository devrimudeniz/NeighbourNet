<?php
require_once 'includes/bootstrap.php';

// Categories Definition
$categories = [
    'all' => ['icon' => 'th-large', 'color' => 'slate'],
    'vehicles' => ['icon' => 'car', 'color' => 'blue'],
    'electronics' => ['icon' => 'laptop', 'color' => 'purple'],
    'home_appliances' => ['icon' => 'blender', 'color' => 'cyan'],
    'furniture' => ['icon' => 'couch', 'color' => 'amber'],
    'clothing' => ['icon' => 'tshirt', 'color' => 'pink'],
    'sports' => ['icon' => 'futbol', 'color' => 'green'],
    'garden' => ['icon' => 'leaf', 'color' => 'lime'],
    'kids' => ['icon' => 'baby-carriage', 'color' => 'rose'],
    'hobbies' => ['icon' => 'gamepad', 'color' => 'violet'],
    'pets' => ['icon' => 'paw', 'color' => 'orange'],
    'services' => ['icon' => 'tools', 'color' => 'indigo'],
    'other' => ['icon' => 'ellipsis-h', 'color' => 'gray']
];

// Get filters from URL
$category = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min']) ? (float)$_GET['min'] : null;
$max_price = isset($_GET['max']) ? (float)$_GET['max'] : null;

// Build Query
// Build Query to show ACTIVE listings OR (OWN listings that are pending)
$user_id = $_SESSION['user_id'] ?? 0;
$sql = "SELECT m.*, u.username, u.full_name, u.avatar, u.badge 
        FROM marketplace_listings m 
        JOIN users u ON m.user_id = u.id 
        WHERE (m.status = 'active' OR (m.user_id = ? AND m.status = 'pending')) 
        AND m.expires_at > NOW()";
$params = [$user_id];

if ($category !== 'all') {
    $sql .= " AND m.category = ?";
    $params[] = $category;
}

if ($min_price !== null) {
    $sql .= " AND m.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== null) {
    $sql .= " AND m.price <= ?";
    $params[] = $max_price;
}

// Sort
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY m.created_at ASC";
        break;
    case 'price_low':
        $sql .= " ORDER BY m.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY m.price DESC";
        break;
    default:
        $sql .= " ORDER BY m.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();
$total_count = count($listings);

// Build filter URL helper
function buildFilterUrl($newParams = []) {
    $current = $_GET;
    foreach ($newParams as $key => $value) {
        if ($value === null || $value === '') {
            unset($current[$key]);
        } else {
            $current[$key] = $value;
        }
    }
    return '?' . http_build_query($current);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['marketplace']; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 sm:px-6 pt-28 sm:pt-32">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-green-500 to-emerald-600">
                    <?php echo $t['marketplace']; ?>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm"><?php echo $t['mp_discover'] ?? 'Topluluk içindeki fırsatları keşfet'; ?></p>
            </div>
            
            <?php if(isset($_SESSION['user_id'])): ?>
            <a href="add_listing" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:shadow-lg hover:shadow-green-500/20 transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i> <?php echo $t['mp_create_listing'] ?? 'İlan Ver'; ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if(isset($_GET['pending'])): ?>
        <div class="mb-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 rounded-2xl flex items-center gap-4 animate-in fade-in slide-in-from-top-4">
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-800 rounded-full flex items-center justify-center flex-shrink-0 text-yellow-600 dark:text-yellow-400">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <h3 class="font-bold text-yellow-800 dark:text-yellow-300"><?php echo $lang == 'en' ? 'Listing Submitted for Approval' : 'İlan Onay İçin Gönderildi'; ?></h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-400"><?php echo $lang == 'en' ? 'Your listing is currently pending approval by our moderators. It will be visible to everyone once approved.' : 'İlanınız moderatörlerimiz tarafından incelenmek üzere beklemeye alındı. Onaylandıktan sonra herkes tarafından görülebilir.'; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Sidebar Filters -->
            <aside class="lg:w-72 flex-shrink-0">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden sticky top-28">
                    
                    <!-- Collapse All Button -->
                    <button onclick="toggleAllFilters()" id="collapseAllBtn" 
                            style="width: 100%; padding: 10px 16px; background: linear-gradient(135deg, #10b981, #059669); color: white; font-weight: bold; font-size: 13px; display: flex; align-items: center; justify-content: space-between; border: none; cursor: pointer;">
                        <span><i class="fas fa-filter" style="margin-right: 8px;"></i><?php echo $lang == 'en' ? 'Filters' : 'Filtreler'; ?></span>
                        <i class="fas fa-chevron-down" id="collapseIcon"></i>
                    </button>
                    
                    <div id="filtersContent" style="display: none;">
                    <!-- Categories -->
                    <div class="p-4 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-sm mb-3 flex items-center gap-2 cursor-pointer" onclick="toggleSection('categoriesSection')">
                            <i class="fas fa-layer-group text-green-500"></i>
                            <?php echo $t['categories'] ?? 'Kategoriler'; ?>
                            <i class="fas fa-chevron-down text-xs ml-auto" id="categoriesIcon"></i>
                        </h3>
                        <div id="categoriesSection" class="space-y-1 max-h-80 overflow-y-auto custom-scrollbar">
                            <?php foreach($categories as $cat_key => $cat_info): 
                                $cat_name = $cat_key === 'all' ? ($t['mp_all_categories'] ?? 'Tüm Kategoriler') : ($t['mp_' . $cat_key] ?? ucfirst($cat_key));
                                $is_active = $category === $cat_key;
                            ?>
                            <a href="<?php echo buildFilterUrl(['cat' => $cat_key === 'all' ? null : $cat_key]); ?>" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all <?php echo $is_active ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-400'; ?>">
                                <i class="fas fa-<?php echo $cat_info['icon']; ?> w-5 text-center"></i>
                                <span class="text-sm"><?php echo $cat_name; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Sort -->
                    <div class="p-4 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-sm mb-3 flex items-center gap-2 cursor-pointer" onclick="toggleSection('sortSection')">
                            <i class="fas fa-sort text-blue-500"></i>
                            <?php echo $t['mp_sort_by'] ?? 'Sırala'; ?>
                            <i class="fas fa-chevron-down text-xs ml-auto" id="sortIcon"></i>
                        </h3>
                        <div id="sortSection" class="space-y-1">
                            <?php 
                            $sort_options = [
                                'newest' => $t['mp_sort_newest'] ?? 'En Yeni',
                                'oldest' => $t['mp_sort_oldest'] ?? 'En Eski',
                                'price_low' => $t['mp_sort_price_low'] ?? 'Ucuzdan Pahalıya',
                                'price_high' => $t['mp_sort_price_high'] ?? 'Pahalıdan Ucuza'
                            ];
                            foreach($sort_options as $sort_key => $sort_name): 
                                $is_active = $sort === $sort_key;
                            ?>
                            <a href="<?php echo buildFilterUrl(['sort' => $sort_key]); ?>" 
                               class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all text-sm <?php echo $is_active ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-600 dark:text-slate-400'; ?>">
                                <?php if($is_active): ?><i class="fas fa-check text-xs"></i><?php endif; ?>
                                <?php echo $sort_name; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Price Filter -->
                    <form method="GET" class="p-4">
                        <!-- Preserve other filters -->
                        <?php if($category !== 'all'): ?><input type="hidden" name="cat" value="<?php echo $category; ?>"><?php endif; ?>
                        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                        
                        <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                            <i class="fas fa-tag text-amber-500"></i>
                            <?php echo $t['mp_price_range'] ?? 'Fiyat Aralığı'; ?>
                        </h3>
                        <div class="flex gap-2 mb-3">
                            <input type="number" name="min" placeholder="<?php echo $t['mp_min_price'] ?? 'Min'; ?>" 
                                   value="<?php echo $min_price; ?>"
                                   class="w-1/2 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:border-green-500 outline-none">
                            <input type="number" name="max" placeholder="<?php echo $t['mp_max_price'] ?? 'Max'; ?>" 
                                   value="<?php echo $max_price; ?>"
                                   class="w-1/2 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:border-green-500 outline-none">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg text-sm font-bold transition-colors">
                                <?php echo $t['mp_apply_filters'] ?? 'Uygula'; ?>
                            </button>
                            <a href="marketplace" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-lg text-sm font-bold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                <?php echo $t['mp_clear_filters'] ?? 'Temizle'; ?>
                            </a>
                        </div>
                    </form>
                    </div><!-- End filtersContent -->
                </div>
            </aside>
            
            <!-- Listings Grid -->
            <div class="flex-1">
                <!-- Results Count -->
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <span class="font-bold text-slate-900 dark:text-white"><?php echo $total_count; ?></span> <?php echo $t['mp_results'] ?? 'sonuç'; ?>
                    </p>
                    
                    <!-- Mobile Filter Toggle -->
                    <button onclick="document.getElementById('mobile-filters').classList.toggle('hidden')" class="lg:hidden bg-slate-100 dark:bg-slate-800 px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2">
                        <i class="fas fa-filter"></i> <?php echo $t['mp_filter_by'] ?? 'Filtrele'; ?>
                    </button>
                </div>
                
                <!-- Mobile Filters (Hidden by default) -->
                <div id="mobile-filters" class="lg:hidden hidden mb-6 bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php foreach($categories as $cat_key => $cat_info): 
                            $cat_name = $cat_key === 'all' ? ($t['mp_all_categories'] ?? 'Tümü') : ($t['mp_' . $cat_key] ?? ucfirst($cat_key));
                            $is_active = $category === $cat_key;
                        ?>
                        <a href="<?php echo buildFilterUrl(['cat' => $cat_key === 'all' ? null : $cat_key]); ?>" 
                           class="px-3 py-1.5 rounded-full text-xs font-bold transition-all <?php echo $is_active ? 'bg-green-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400'; ?>">
                            <i class="fas fa-<?php echo $cat_info['icon']; ?> mr-1"></i><?php echo $cat_name; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($sort_options as $sort_key => $sort_name): 
                            $is_active = $sort === $sort_key;
                        ?>
                        <a href="<?php echo buildFilterUrl(['sort' => $sort_key]); ?>" 
                           class="px-3 py-1.5 rounded-full text-xs font-bold transition-all <?php echo $is_active ? 'bg-blue-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400'; ?>">
                            <?php echo $sort_name; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if($total_count > 0): ?>
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach($listings as $item): 
                            $cat_label = $t['mp_' . $item['category']] ?? ($item['category'] == 'item' ? ($t['item_category'] ?? 'İkinci El') : ($item['category'] == 'service' ? ($t['service_category'] ?? 'Hizmet') : ucfirst($item['category'])));
                        ?>
                        <a href="listing_detail?id=<?php echo $item['id']; ?>" class="block bg-white dark:bg-slate-800 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all group">
                            <!-- Image -->
                            <div class="aspect-square relative overflow-hidden bg-slate-100 dark:bg-slate-900">
                                <?php if($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500" loading="lazy">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                                        <i class="fas fa-box-open text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                
                                <div class="absolute top-2 left-2 flex gap-1">
                                    <span class="bg-black/60 backdrop-blur text-white px-2 py-1 rounded-lg text-[10px] font-bold uppercase">
                                        <?php echo $cat_label; ?>
                                    </span>
                                    <?php if($item['status'] == 'pending'): ?>
                                    <span class="bg-yellow-500/90 backdrop-blur text-white px-2 py-1 rounded-lg text-[10px] font-bold uppercase flex items-center gap-1">
                                        <i class="fas fa-clock text-[8px]"></i> <?php echo $lang == 'en' ? 'Pending' : 'Bekliyor'; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($item['price'] > 0): ?>
                                <div class="absolute bottom-2 right-2 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-2 py-1 rounded-lg font-bold text-sm shadow-lg">
                                    <?php echo number_format($item['price'], 0); ?> <?php echo $item['currency']; ?>
                                </div>
                                <?php else: ?>
                                <div class="absolute bottom-2 right-2 bg-green-500 text-white px-2 py-1 rounded-lg font-bold text-sm shadow-lg">
                                    <?php echo $t['free'] ?? 'FREE'; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Content -->
                            <div class="p-3">
                                <h3 class="font-bold text-sm mb-1 leading-tight text-slate-900 dark:text-white line-clamp-2">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>
                                
                                <div class="flex items-center gap-2 mt-2">
                                    <img src="<?php echo $item['avatar']; ?>" class="w-5 h-5 rounded-full object-cover" loading="lazy">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo htmlspecialchars($item['full_name']); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-20 bg-white dark:bg-slate-800/50 rounded-3xl border border-slate-200 dark:border-slate-700 border-dashed">
                        <i class="fas fa-store-slash text-6xl text-slate-300 dark:text-slate-600 mb-4"></i>
                        <h3 class="text-xl font-bold mb-2"><?php echo $t['mp_no_listings'] ?? 'Henüz ilan yok'; ?></h3>
                        <p class="text-slate-500 mb-6"><?php echo $t['mp_be_first'] ?? 'İlk ilanı sen oluştur ve topluluğa katıl.'; ?></p>
                        <a href="add_listing" class="text-green-600 font-bold hover:underline"><?php echo $t['mp_create_listing'] ?? 'İlan Ver'; ?> →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Mobile Add Button -->
    <?php if(isset($_SESSION['user_id'])): ?>
    <div class="lg:hidden fixed bottom-20 right-4 z-40">
        <a href="add_listing" class="w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white shadow-xl shadow-green-600/30">
            <i class="fas fa-plus text-xl"></i>
        </a>
    </div>
    <?php endif; ?>

    <script>
        // Toggle all filters
        function toggleAllFilters() {
            const content = document.getElementById('filtersContent');
            const icon = document.getElementById('collapseIcon');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
            } else {
                content.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
            }
        }
        
        // Toggle individual sections
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const iconId = sectionId.replace('Section', 'Icon');
            const icon = document.getElementById(iconId);
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                if (icon) icon.className = 'fas fa-chevron-down text-xs ml-auto';
            } else {
                section.style.display = 'none';
                if (icon) icon.className = 'fas fa-chevron-up text-xs ml-auto';
            }
        }
    </script>

</body>
</html>
