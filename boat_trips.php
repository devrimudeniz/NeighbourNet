<?php
require_once 'includes/bootstrap.php';

// Check if user is a captain or admin (can add boat trips)
$is_captain = false;
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $current_user = $u_stmt->fetch();
    
    // Check if captain, founder, or moderator (all can add boats)
    $is_captain = in_array($current_user['badge'], ['captain', 'founder', 'moderator']);
    
    // Check admin for pending review button
    $is_admin = in_array($current_user['badge'], ['founder', 'moderator']);
}

// Filtering Logic
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$search = $_GET['search'] ?? '';

// Build SQL Query
$sql = "SELECT bt.*, u.full_name as captain_name, u.avatar as captain_avatar,
        (SELECT AVG(rating) FROM trip_reviews WHERE trip_id = bt.id) as avg_rating,
        (SELECT COUNT(*) FROM trip_reviews WHERE trip_id = bt.id) as review_count
        FROM boat_trips bt
        JOIN users u ON bt.captain_id = u.id
        WHERE bt.status = 'approved' AND bt.is_active = 1";

$params = [];

if ($category) {
    $sql .= " AND bt.category = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (bt.title LIKE ? OR bt.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($min_price) {
    $sql .= " AND bt.price_per_person >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $sql .= " AND bt.price_per_person <= ?";
    $params[] = $max_price;
}

$sql .= " ORDER BY bt.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['boat_trips']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .trip-card:hover .trip-image { transform: scale(1.05); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-6 pt-24">
        
        <!-- Hero Search Section -->
        <div class="relative bg-gradient-to-br from-cyan-500 to-blue-600 rounded-[3rem] p-10 shadow-2xl mb-12 overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
            
            <div class="relative z-10">
                <h1 class="text-4xl font-black mb-2 text-white"><?php echo $t['boat_trips']; ?></h1>
                <p class="text-white/80 font-medium mb-6"><?php echo $lang == 'en' ? 'Discover amazing boat tours and activities in Kalkan' : 'Kalkan\'da muhteşem tekne turlarını ve aktivitelerini keşfedin'; ?></p>

                <!-- CTA Buttons -->
                <div class="flex flex-wrap gap-3 mb-10">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($is_captain): ?>
                            <a href="add_boat_trip" class="inline-flex items-center gap-2 bg-white text-cyan-600 px-6 py-3 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                                <i class="fas fa-plus-circle"></i> <?php echo $lang == 'en' ? 'List Your Boat' : 'Tekneni Listele'; ?>
                            </a>
                        <?php else: ?>
                            <a href="request_verification?type=captain" class="inline-flex items-center gap-2 bg-white/20 text-white border-2 border-white/50 px-6 py-3 rounded-2xl font-black hover:bg-white hover:text-cyan-600 transition-all backdrop-blur-sm">
                                <i class="fas fa-anchor"></i> <?php echo $lang == 'en' ? 'Become a Captain' : 'Kaptan Ol - Tekne Ekle'; ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($is_admin): ?>
                            <a href="admin/boat_trips.php" class="inline-flex items-center gap-2 bg-amber-500 text-white px-6 py-3 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                                <i class="fas fa-shield-alt"></i> <?php echo $lang == 'en' ? 'Review Pending' : 'Onay Bekleyenler'; ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login" class="inline-flex items-center gap-2 bg-white/20 text-white border-2 border-white/50 px-6 py-3 rounded-2xl font-black hover:bg-white hover:text-cyan-600 transition-all backdrop-blur-sm">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $lang == 'en' ? 'Login to List Boat' : 'Tekne Eklemek İçin Giriş Yap'; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <form action="boat_trips" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 bg-white/10 backdrop-blur-md p-6 rounded-[2rem] border border-white/20">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-white ml-2"><?php echo $lang == 'en' ? 'Search' : 'Ara'; ?></label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang == 'en' ? 'Blue Cave, Kekova...' : 'Mavi Mağara, Kekova...'; ?>" class="w-full bg-white/90 dark:bg-slate-900/90 p-4 rounded-2xl border-none outline-none font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-white ml-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                        <select name="category" class="w-full bg-white/90 dark:bg-slate-900/90 p-4 rounded-2xl border-none outline-none font-bold">
                            <option value=""><?php echo $lang == 'en' ? 'All' : 'Tümü'; ?></option>
                            <option value="daily_tour" <?php echo $category == 'daily_tour' ? 'selected' : ''; ?>><?php echo $t['daily_tour']; ?></option>
                            <option value="private_charter" <?php echo $category == 'private_charter' ? 'selected' : ''; ?>><?php echo $t['private_charter']; ?></option>
                            <option value="sunset_cruise" <?php echo $category == 'sunset_cruise' ? 'selected' : ''; ?>><?php echo $t['sunset_cruise']; ?></option>
                            <option value="fishing" <?php echo $category == 'fishing' ? 'selected' : ''; ?>><?php echo $t['fishing_trip']; ?></option>
                            <option value="diving" <?php echo $category == 'diving' ? 'selected' : ''; ?>><?php echo $t['diving']; ?></option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-white ml-2"><?php echo $lang == 'en' ? 'Price Range (EUR)' : 'Fiyat Aralığı (EUR)'; ?></label>
                        <div class="flex gap-2">
                            <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="Min" class="w-1/2 bg-white/90 dark:bg-slate-900/90 p-4 rounded-2xl border-none outline-none font-bold">
                            <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="Max" class="w-1/2 bg-white/90 dark:bg-slate-900/90 p-4 rounded-2xl border-none outline-none font-bold">
                        </div>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-white hover:bg-slate-100 text-cyan-600 px-6 py-4 rounded-2xl font-black shadow-lg transition-all">
                            <i class="fas fa-search mr-2"></i> <?php echo $lang == 'en' ? 'Search' : 'Ara'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Filters -->
        <div class="flex gap-4 mb-10 overflow-x-auto pb-4">
            <a href="boat_trips" class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 px-8 py-4 rounded-2xl font-bold whitespace-nowrap shadow-sm hover:border-cyan-500 transition-all <?php echo !$category ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20' : ''; ?>">
                <i class="fas fa-th mr-2 opacity-70"></i><?php echo $lang == 'en' ? 'All Trips' : 'Tüm Turlar'; ?>
            </a>
            <a href="boat_trips?category=daily_tour" class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 px-8 py-4 rounded-2xl font-bold whitespace-nowrap shadow-sm hover:border-cyan-500 transition-all <?php echo $category == 'daily_tour' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20' : ''; ?>">
                <i class="fas fa-sun text-amber-500 mr-2"></i><?php echo $t['daily_tour']; ?>
            </a>
            <a href="boat_trips?category=sunset_cruise" class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 px-8 py-4 rounded-2xl font-bold whitespace-nowrap shadow-sm hover:border-cyan-500 transition-all <?php echo $category == 'sunset_cruise' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20' : ''; ?>">
                <i class="fas fa-cloud-sun text-orange-500 mr-2"></i><?php echo $t['sunset_cruise']; ?>
            </a>
            <a href="boat_trips?category=fishing" class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 px-8 py-4 rounded-2xl font-bold whitespace-nowrap shadow-sm hover:border-cyan-500 transition-all <?php echo $category == 'fishing' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20' : ''; ?>">
                <i class="fas fa-fish text-blue-500 mr-2"></i><?php echo $t['fishing_trip']; ?>
            </a>
        </div>

        <!-- Trips Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($trips as $trip): ?>
                <div class="trip-card bg-white dark:bg-slate-800 rounded-[2.5rem] overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-2xl hover:border-cyan-500/30 transition-all group">
                    <!-- Image Section -->
                    <div class="relative h-64 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($trip['cover_photo']); ?>" class="trip-image w-full h-full object-cover transition-transform duration-500" alt="<?php echo htmlspecialchars($trip['title']); ?>">
                        
                        <!-- Category Badge -->
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1 bg-cyan-500 text-white rounded-full text-xs font-black uppercase shadow-lg">
                                <?php echo $t[$trip['category']] ?? $trip['category']; ?>
                            </span>
                        </div>

                        <!-- Rating -->
                        <?php if ($trip['avg_rating']): ?>
                            <div class="absolute top-4 right-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm px-3 py-1 rounded-full flex items-center gap-1">
                                <i class="fas fa-star text-amber-500 text-xs"></i>
                                <span class="font-black text-sm"><?php echo number_format($trip['avg_rating'], 1); ?></span>
                                <span class="text-xs text-slate-400">(<?php echo $trip['review_count']; ?>)</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <h3 class="text-xl font-black mb-2 line-clamp-1 text-slate-900 dark:text-blue-50"><?php echo htmlspecialchars($trip['title']); ?></h3>
                        <p class="text-sm text-slate-600 dark:text-slate-200 mb-4 line-clamp-2"><?php echo htmlspecialchars($trip['description']); ?> <?php echo $trip['boat_type_label']; ?></p>

                        <!-- Captain Info -->
                        <div class="flex items-center gap-2 mb-4 pb-4 border-b border-slate-100 dark:border-slate-700">
                            <img src="<?php echo htmlspecialchars($trip['captain_avatar']); ?>" class="w-8 h-8 rounded-full object-cover">
                            <div>
                                <p class="text-xs text-slate-400"><?php echo $t['captain']; ?></p>
                                <p class="text-sm font-bold"><?php echo htmlspecialchars($trip['captain_name']); ?></p>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-clock text-cyan-500"></i>
                                <span class="font-bold"><?php echo $trip['duration_hours']; ?> <?php echo $t['hours']; ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fas fa-users text-cyan-500"></i>
                                <span class="font-bold"><?php echo $lang == 'en' ? 'Max' : 'Maks'; ?> <?php echo $trip['max_capacity']; ?></span>
                            </div>
                        </div>

                        <!-- Price & CTA -->
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-400"><?php echo $t['per_person']; ?></p>
                                <p class="text-2xl font-black text-cyan-600"><?php echo $trip['price_per_person']; ?> <?php echo $trip['currency']; ?></p>
                            </div>
                            <a href="trip_detail?id=<?php echo $trip['id']; ?>" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-2xl font-black shadow-lg shadow-cyan-500/20 transition-all">
                                <?php echo $lang == 'en' ? 'View Details' : 'Detaylar'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($trips)): ?>
                <div class="col-span-full text-center py-20 opacity-30">
                    <i class="fas fa-ship text-6xl mb-4"></i>
                    <p class="text-xl font-bold"><?php echo $lang == 'en' ? 'No trips found' : 'Tur bulunamadı'; ?></p>
                    <p class="text-slate-500"><?php echo $lang == 'en' ? 'Try adjusting your filters' : 'Filtrelerinizi ayarlamayı deneyin'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
