<?php
require_once 'includes/bootstrap.php';
require_once 'includes/ui_components.php';

// Sorting & Filtering Logic
$current_cat = $_GET['cat'] ?? '';
$current_sort = $_GET['sort'] ?? 'recent'; // recent, views, liked

$where_clause = "WHERE g.status = 'published'";
$params = [];

if ($current_cat) {
    if ($current_cat === 'Featured') {
        $where_clause .= " AND g.featured = 1";
    } else {
        $where_clause .= " AND g.category = ?";
        $params[] = $current_cat;
    }
}

// Order Mapping
$order_by = "g.created_at DESC";
if ($current_sort === 'views') {
    $order_by = "g.views DESC";
} elseif ($current_sort === 'liked') {
    $order_by = "helpful_votes DESC, g.created_at DESC";
}

$stmt = $pdo->prepare("
    SELECT g.*, 
           u.full_name as author_name, u.avatar as author_avatar, u.username, u.expert_badge, 
           (SELECT COUNT(*) FROM guidebook_votes WHERE guidebook_id = g.id AND vote_type = 'helpful') as helpful_votes 
    FROM guidebooks g 
    JOIN users u ON g.user_id = u.id 
    $where_clause 
    ORDER BY g.featured DESC, $order_by
");
$stmt->execute($params);
$guides = $stmt->fetchAll();

// Check if user is expert to show "Write" button
$is_expert = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_type IN ('expert', 'local_guide')");
    $stmt->execute([$_SESSION['user_id']]);
    $is_expert = $stmt->fetchColumn() > 0 || in_array($_SESSION['badge'] ?? '', ['founder', 'moderator', 'expert', 'local_guide']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20">
<?php require_once 'includes/header.php'; ?>

<div class="container mx-auto px-4 py-8 pt-24 max-w-6xl">
    
    <!-- Hero Section -->
    <div class="mb-8 text-center relative">
        <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Kalkan <span class="text-violet-600">Guidehub</span></h1>
        <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
            Discover hidden gems, local tips, and curated experiences written by verified local experts.
        </p>
        
        <?php if($is_expert): ?>
        <div class="mt-8">
            <a href="write_guide.php" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-full font-bold shadow-lg hover:scale-105 transition-transform">
                <i class="fas fa-pen-nib"></i> Write a Guide
            </a>
        </div>
        <?php elseif(isset($_SESSION['user_id'])): ?>
        <div class="mt-8">
            <a href="expert_application.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-full font-bold shadow-sm hover:scale-105 transition-transform">
                <i class="fas fa-certificate text-violet-500"></i> Become an Expert
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sorting Tabs -->
    <div class="flex items-center justify-center gap-4 mb-8 border-b border-slate-200 dark:border-slate-800 pb-4 overflow-x-auto">
        <?php 
        $baseUrl = "guidebook.php" . ($current_cat ? "?cat=" . urlencode($current_cat) : "");
        $sep = $current_cat ? "&" : "?";
        ?>
        <a href="<?php echo $baseUrl . $sep; ?>sort=recent" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap <?php echo $current_sort == 'recent' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'; ?>">
            <i class="far fa-clock"></i> <?php echo $lang == 'en' ? 'Most Recent' : 'En Yeni'; ?>
        </a>
        <a href="<?php echo $baseUrl . $sep; ?>sort=views" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap <?php echo $current_sort == 'views' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'; ?>">
            <i class="far fa-eye"></i> <?php echo $lang == 'en' ? 'Most Viewed' : 'En Çok İzlenen'; ?>
        </a>
        <a href="<?php echo $baseUrl . $sep; ?>sort=liked" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap <?php echo $current_sort == 'liked' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'; ?>">
            <i class="far fa-thumbs-up"></i> <?php echo $lang == 'en' ? 'Most Helpful' : 'En Faydalı'; ?>
        </a>
    </div>

    <!-- Categories Filters -->
    <div class="flex flex-wrap justify-center gap-2 mb-10">
        <a href="guidebook.php<?php echo $current_sort != 'recent' ? '?sort=' . $current_sort : ''; ?>" class="px-4 py-2 rounded-full font-bold text-sm transition-all <?php echo !$current_cat ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-violet-500'; ?>">
            <?php echo $lang == 'en' ? 'All' : 'Hepsi'; ?>
        </a>
        <a href="guidebook.php?cat=Featured<?php echo $current_sort != 'recent' ? '&sort=' . $current_sort : ''; ?>" class="px-4 py-2 rounded-full font-bold text-sm transition-all <?php echo $current_cat == 'Featured' ? 'bg-yellow-400 text-slate-900 shadow-lg shadow-yellow-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-yellow-400'; ?>">
            <i class="fas fa-star mr-1"></i> Featured
        </a>
        <?php 
        $cats = ['Food & Drink', 'History', 'Beaches', 'Lifestyle', 'Nature', 'Hidden Gems'];
        foreach($cats as $cat): ?>
            <a href="guidebook.php?cat=<?php echo urlencode($cat); ?><?php echo $current_sort != 'recent' ? '&sort=' . $current_sort : ''; ?>" class="px-4 py-2 rounded-full font-bold text-sm transition-all <?php echo $current_cat == $cat ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/30' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-violet-500'; ?>">
                <?php echo $cat; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach($guides as $guide): ?>
        <a href="guidebook_detail.php?slug=<?php echo htmlspecialchars($guide['slug']); ?>" class="group block bg-white dark:bg-slate-800 rounded-[2rem] overflow-hidden shadow-lg border border-slate-100 dark:border-slate-700 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 relative">
            
            <?php if($guide['featured']): ?>
                <div class="absolute top-4 right-4 z-10 box-border">
                    <span class="px-3 py-1 bg-yellow-400 text-slate-900 text-[10px] font-black uppercase tracking-wider rounded-lg shadow-sm flex items-center gap-1">
                        <i class="fas fa-star"></i> Featured
                    </span>
                </div>
            <?php endif; ?>

            <!-- Cover -->
            <div class="h-48 overflow-hidden relative">
                 <?php $cover = !empty($guide['cover_image']) ? $guide['cover_image'] : 'https://source.unsplash.com/random/800x600/?kalkan,nature'; ?>
                <img src="<?php echo htmlspecialchars($cover); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div class="absolute top-4 left-4">
                    <span class="px-3 py-1 bg-white/90 dark:bg-slate-900/90 backdrop-blur text-xs font-black uppercase tracking-wider rounded-lg shadow-sm">
                        <?php echo htmlspecialchars($guide['category']); ?>
                    </span>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                     <?php $avatar = !empty($guide['author_avatar']) ? $guide['author_avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($guide['author_name']); ?>
                    <img src="<?php echo $avatar; ?>" class="w-8 h-8 rounded-full object-cover border border-slate-100 dark:border-slate-600">
                    <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($guide['author_name']); ?></span>
                    <i class="fas fa-check-circle text-violet-500 text-[10px]" title="Verified Expert"></i>
                </div>
                
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 leading-tight group-hover:text-violet-500 transition-colors line-clamp-2">
                    <?php echo htmlspecialchars($guide['title']); ?>
                </h3>

                <!-- Tags -->
                <?php if(!empty($guide['tags'])): 
                    $tags = json_decode($guide['tags'], true);
                    if($tags): ?>
                    <div class="flex flex-wrap gap-1 mb-4">
                        <?php foreach(array_slice($tags, 0, 3) as $tag): ?>
                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-700/50 px-2 py-0.5 rounded-md">#<?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; endif; ?>
                
                <div class="flex items-center justify-between mt-auto text-xs font-bold text-slate-400 border-t border-slate-100 dark:border-slate-700 pt-4">
                    <span class="flex items-center gap-1"><i class="far fa-clock"></i> <?php echo $guide['reading_time'] ?? 5; ?> min read</span>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1"><i class="far fa-eye"></i> <?php echo $guide['views']; ?></span>
                        <?php if($guide['helpful_votes'] > 0): ?>
                             <span class="flex items-center gap-1 text-green-500"><i class="fas fa-thumbs-up"></i> <?php echo $guide['helpful_votes']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if(empty($guides)): ?>
        <div class="text-center py-20 opacity-50">
            <i class="fas fa-book-open text-4xl mb-4"></i>
            <p>No guides published yet. Be the first!</p>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/bottom_nav.php'; ?>
</body>
</html>
