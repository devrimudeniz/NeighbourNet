<?php
require_once 'includes/bootstrap.php';
require_once 'includes/opening_hours_helper.php';

// Get filters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'rating'; // rating, newest, name

// Build query
$params = []; // Initialize params

if ($category !== 'all') {
    $sql = "SELECT DISTINCT bl.*, u.username as owner_username 
            FROM business_listings bl 
            JOIN users u ON bl.owner_id = u.id 
            JOIN business_categories bc ON bl.id = bc.business_id 
            WHERE bc.category = ?";
    $params[] = $category;
} else {
    $sql = "SELECT bl.*, u.username as owner_username 
            FROM business_listings bl 
            JOIN users u ON bl.owner_id = u.id 
            WHERE 1=1";
}

if ($search) {
    $sql .= " AND (bl.name LIKE ? OR bl.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Sorting
if ($sort == 'rating') {
    $sql .= " ORDER BY bl.avg_rating DESC, bl.total_reviews DESC";
} elseif ($sort == 'newest') {
    $sql .= " ORDER BY bl.created_at DESC";
} else {
    $sql .= " ORDER BY bl.name ASC";
}

$sql .= " LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$businesses = $stmt->fetchAll();

// Get category counts
$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM business_listings")->fetchColumn(),
    'restaurant' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'restaurant'")->fetchColumn(),
    'bar' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'bar'")->fetchColumn(),
    'hotel' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'hotel'")->fetchColumn(),
    'cafe' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'cafe'")->fetchColumn(),
    'shop' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'shop'")->fetchColumn(),
    'activity' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'activity'")->fetchColumn(),
    'service' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'service'")->fetchColumn(),
    'health' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'health'")->fetchColumn(),
    'nomad' => $pdo->query("SELECT COUNT(DISTINCT business_id) FROM business_categories WHERE category = 'nomad'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['directory']; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-4xl font-extrabold mb-3 text-pink-600 dark:text-pink-400">
                    <?php echo $lang == 'en' ? 'Business Directory' : 'İşletme Rehberi'; ?>
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    <?php echo $lang == 'en' ? 'Discover the best of Kalkan - rated by our community' : 'Kalkan\'ın en iyilerini keşfedin - topluluğumuz tarafından puanlandı'; ?>
                </p>
            </div>
            
            <?php 
            $can_add_business = isset($_SESSION['user_id']) && isset($_SESSION['badge']) && 
                                in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator']);
            ?>
            
            <?php if($can_add_business): ?>
            <a href="add_business" class="bg-gradient-to-r from-pink-500 to-violet-500 text-white px-6 py-3 rounded-xl font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i><?php echo $t['add_business']; ?>
            </a>
            <?php elseif(isset($_SESSION['user_id'])): ?>
            <!-- User logged in but doesn't have business badge -->
            <a href="request_verification?type=business" class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-6 py-3 rounded-xl font-bold hover:shadow-lg hover:shadow-emerald-500/30 transition-all flex items-center gap-2">
                <i class="fas fa-store"></i><?php echo $lang == 'en' ? 'Are you a business owner? Get Verified!' : 'İşletme sahibi misin? Doğrulan!'; ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Guest CTA Banner -->
        <div class="relative overflow-hidden rounded-2xl mb-6 border border-blue-200/50 dark:border-blue-800/50" style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 50%, #f5f3ff 100%);">
            <div class="dark:hidden absolute inset-0" style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 50%, #f5f3ff 100%);"></div>
            <div class="hidden dark:block absolute inset-0" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #172554 100%);"></div>
            <div class="relative flex flex-col sm:flex-row items-center gap-4 px-6 py-5">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl shrink-0" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                    <i class="fas fa-store text-white text-lg"></i>
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <h3 class="font-bold text-slate-800 dark:text-white text-base">
                        <?php echo $lang == 'en' ? 'Own a business in Kalkan?' : 'Kalkan\'da işletmeniz mi var?'; ?>
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        <?php echo $lang == 'en' 
                            ? 'Sign up for free and add your business to reach thousands of locals & visitors.' 
                            : 'Ücretsiz kaydolun ve işletmenizi ekleyerek binlerce yerel ve ziyaretçiye ulaşın.'; ?>
                    </p>
                </div>
                <a href="login" class="shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-white text-sm transition-all hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $lang == 'en' ? 'Login to Add Yours' : 'Giriş Yap & Ekle'; ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-6 mb-6 border border-white/20 dark:border-slate-800/50">
            <!-- Search -->
            <form method="GET" class="mb-4">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="<?php echo $lang == 'en' ? 'Search businesses...' : 'İşletme ara...'; ?>"
                           class="w-full pl-12 pr-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>
            </form>

            <!-- Category Filters -->
            <div class="flex flex-wrap gap-2 mb-4">
                <a href="?category=all" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'all' ? 'bg-gradient-to-r from-pink-500 to-violet-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?> (<?php echo $counts['all']; ?>)
                </a>
                <a href="?category=restaurant" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'restaurant' ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    🍽️ <?php echo $t['restaurant']; ?> (<?php echo $counts['restaurant']; ?>)
                </a>
                <a href="?category=bar" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'bar' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    🍹 <?php echo $t['bar']; ?> (<?php echo $counts['bar']; ?>)
                </a>
                <a href="?category=hotel" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'hotel' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    🏨 <?php echo $t['hotel']; ?> (<?php echo $counts['hotel']; ?>)
                </a>
                <a href="?category=cafe" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'cafe' ? 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    ☕ <?php echo $t['cafe']; ?> (<?php echo $counts['cafe']; ?>)
                </a>
                <a href="?category=shop" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'shop' ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    🛍️ <?php echo $lang == 'en' ? 'Shop' : 'Dükkan'; ?> (<?php echo $counts['shop']; ?>)
                </a>
                <a href="?category=activity" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'activity' ? 'bg-gradient-to-r from-rose-500 to-pink-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    🎯 <?php echo $lang == 'en' ? 'Activity' : 'Aktivite'; ?> (<?php echo $counts['activity']; ?>)
                </a>
                <a href="?category=service" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'service' ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    🔧 <?php echo $lang == 'en' ? 'Service' : 'Hizmet'; ?> (<?php echo $counts['service']; ?>)
                </a>
                <a href="?category=health" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'health' ? 'bg-gradient-to-r from-red-500 to-rose-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    🏥 <?php echo $lang == 'en' ? 'Health' : 'Sağlık'; ?> (<?php echo $counts['health']; ?>)
                </a>
                <a href="?category=nomad" class="px-4 py-2 rounded-full text-sm font-bold transition-all <?php echo $category == 'nomad' ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'; ?>">
                    💻 <?php echo $t['nomad']; ?> (<?php echo $counts['nomad']; ?>)
                </a>
            </div>

            <!-- Sort -->
            <div class="flex gap-2">
                <a href="?category=<?php echo $category; ?>&sort=rating" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $sort == 'rating' ? 'bg-pink-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'; ?>">
                    ⭐ <?php echo $lang == 'en' ? 'Top Rated' : 'En İyiler'; ?>
                </a>
                <a href="?category=<?php echo $category; ?>&sort=newest" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $sort == 'newest' ? 'bg-pink-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'; ?>">
                    🆕 <?php echo $lang == 'en' ? 'Newest' : 'En Yeni'; ?>
                </a>
                <a href="?category=<?php echo $category; ?>&sort=name" class="px-3 py-1 rounded-lg text-xs font-bold <?php echo $sort == 'name' ? 'bg-pink-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'; ?>">
                    🔤 <?php echo $lang == 'en' ? 'A-Z' : 'A-Z'; ?>
                </a>
            </div>
        </div>

        <!-- Business Grid -->
        <?php if (count($businesses) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($businesses as $biz): ?>
            <a href="business_detail?id=<?php echo $biz['id']; ?>" class="block bg-white/70 dark:bg-slate-800 backdrop-blur-xl rounded-2xl border border-white/20 dark:border-slate-800/50 overflow-hidden hover:shadow-2xl hover:-translate-y-1 transition-all">
                <!-- Cover Photo -->
                <div class="h-48 bg-gradient-to-br from-pink-500 to-violet-500 relative overflow-hidden">
                    <?php if ($biz['cover_photo']): ?>
                    <img src="<?php echo $biz['cover_photo']; ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-white text-6xl">
                        <?php 
                        $icons = ['restaurant' => '🍽️', 'bar' => '🍹', 'hotel' => '🏨', 'cafe' => '☕', 'activity' => '🎯', 'shop' => '🛍️', 'service' => '🔧', 'health' => '🏥', 'nomad' => '💻'];
                        echo $icons[$biz['category']] ?? '🏪';
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Claimed Badge -->
                    <?php if ($biz['is_claimed']): ?>
                    <div class="absolute top-3 right-3 bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                        <i class="fas fa-check-circle"></i> <?php echo $lang == 'en' ? 'Claimed' : 'Sahiplenildi'; ?>
                    </div>
                    <?php endif; ?>
                    <?php 
                    $open_now = !empty($biz['opening_hours']) ? is_business_open_now($biz['opening_hours']) : null;
                    if ($open_now === true): ?>
                    <div class="absolute top-3 left-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span> <?php echo $lang == 'en' ? 'Open Now' : 'Şimdi Açık'; ?>
                    </div>
                    <?php elseif ($open_now === false): ?>
                    <div class="absolute top-3 left-3 bg-slate-600/80 text-white text-xs font-bold px-3 py-1 rounded-full">
                        <?php echo $lang == 'en' ? 'Closed' : 'Kapalı'; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-bold text-lg flex-1 text-slate-900 dark:text-white"><?php echo htmlspecialchars($biz['name']); ?></h3>
                        <?php if ($biz['total_reviews'] > 0): ?>
                        <div class="flex items-center gap-1 bg-yellow-100 dark:bg-yellow-900/40 px-2 py-1 rounded-lg">
                            <i class="fas fa-star text-yellow-600 dark:text-yellow-500 text-sm"></i>
                            <span class="font-bold text-sm text-yellow-700 dark:text-yellow-100"><?php echo number_format($biz['avg_rating'], 1); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                        <?php echo $t[$biz['category']]; ?>
                    </p>

                    <?php if ($biz['description']): ?>
                    <p class="text-sm text-slate-600 dark:text-slate-200 line-clamp-2 mb-3">
                        <?php echo htmlspecialchars(substr($biz['description'], 0, 150)) . (strlen($biz['description']) > 150 ? '...' : ''); ?>
                    </p>
                    <?php endif; ?>

                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400 mb-3">
                        <?php 
                        $review_count = $biz['total_reviews'];
                        if ($lang == 'en') {
                            echo $review_count . ' ' . ($review_count == 1 ? 'Review' : 'Reviews');
                        } else {
                            echo $review_count . ' ' . ($review_count == 1 ? 'Yorum' : 'Yorum');
                        }
                        ?>
                        <?php if ($biz['address']): ?>
                        <span><i class="fas fa-map-marker-alt mr-1"></i><?php echo substr($biz['address'], 0, 20); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Contact Buttons -->
                    <?php if ($biz['phone']): ?>
                    <div class="flex gap-2 mt-auto" onclick="event.preventDefault(); event.stopPropagation();">
                        <a href="tel:<?php echo htmlspecialchars($biz['phone']); ?>" 
                           class="flex-1 bg-gradient-to-r from-emerald-500 to-green-500 text-white text-center py-2 rounded-lg text-sm font-bold hover:shadow-lg transition-all flex items-center justify-center gap-1">
                            <i class="fas fa-phone"></i> <?php echo $lang == 'en' ? 'Call' : 'Ara'; ?>
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $biz['phone']); ?>?text=Hello%2C%20I%20saw%20your%20business%20on%20Kalkan%20Social" 
                           target="_blank"
                           class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white text-center py-2 rounded-lg text-sm font-bold hover:shadow-lg transition-all flex items-center justify-center gap-1">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-20">
            <i class="fas fa-store-slash text-6xl text-slate-300 dark:text-slate-600 mb-4"></i>
            <p class="text-slate-500 dark:text-slate-400">
                <?php echo $lang == 'en' ? 'No businesses found.' : 'İşletme bulunamadı.'; ?>
            </p>
        </div>
        <?php endif; ?>
    </main>


</body>
</html>
