<?php
require_once 'includes/bootstrap.php';
require_once 'includes/jobs_helper.php';

$categories = getJobCategories($lang);
$types = getJobTypes($lang);

// Filters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query
$sql = "SELECT jl.*, u.username as employer_name, u.full_name, u.avatar as employer_avatar, u.badge
        FROM job_listings jl
        JOIN users u ON jl.employer_id = u.id
        WHERE jl.is_active = 1";
$params = [];

if ($category !== 'all') {
    $sql .= " AND jl.category = ?";
    $params[] = $category;
}

if ($type !== 'all') {
    $sql .= " AND jl.employment_type = ?";
    $params[] = $type;
}

$sql .= " ORDER BY jl.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Build filter URL helper
function filterUrl($cat, $typ, $base = 'jobs') {
    $q = [];
    if ($cat !== 'all') $q[] = 'category=' . urlencode($cat);
    if ($typ !== 'all') $q[] = 'type=' . urlencode($typ);
    return $base . (empty($q) ? '' : '?' . implode('&', $q));
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 sm:px-6 pt-24 pb-20 max-w-6xl">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                    <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-briefcase text-xl"></i>
                    </span>
                    <?php echo $lang == 'en' ? 'Job Board' : 'İş İlanları'; ?>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm sm:text-base">
                    <?php echo $lang == 'en' ? 'Find seasonal & year-round jobs in Kalkan' : 'Kalkan\'da sezonluk ve yıl boyu iş fırsatları'; ?>
                </p>
            </div>
            <?php if(isset($_SESSION['user_id']) && isset($_SESSION['badge']) && in_array($_SESSION['badge'], ['business', 'verified_business'])): ?>
            <a href="post_job" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl font-bold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all shrink-0">
                <i class="fas fa-plus"></i>
                <?php echo $t['post_job']; ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Filters Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden mb-8">
            <!-- Categories -->
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 flex items-center gap-2">
                    <i class="fas fa-tags text-indigo-500"></i>
                    <?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?>
                </h3>
                <div class="flex flex-wrap gap-2">
                    <a href="<?php echo filterUrl('all', $type); ?>" 
                       class="px-4 py-2 rounded-xl text-sm font-bold transition-all <?php echo $category == 'all' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'; ?>">
                        <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?>
                    </a>
                    <?php foreach ($categories as $key => $c): ?>
                    <a href="<?php echo filterUrl($key, $type); ?>" 
                       class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $category == $key ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'; ?>">
                        <span><?php echo $c['icon']; ?></span>
                        <span><?php echo $c[$lang]; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Employment Type -->
            <div class="p-4 sm:p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 flex items-center gap-2">
                    <i class="fas fa-clock text-amber-500"></i>
                    <?php echo $lang == 'en' ? 'Employment Type' : 'Çalışma Şekli'; ?>
                </h3>
                <div class="flex flex-wrap gap-2">
                    <a href="<?php echo filterUrl($category, 'all'); ?>" 
                       class="px-4 py-2 rounded-xl text-sm font-bold transition-all <?php echo $type == 'all' ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'; ?>">
                        <?php echo $lang == 'en' ? 'All Types' : 'Tümü'; ?>
                    </a>
                    <?php foreach ($types as $key => $typ): ?>
                    <a href="<?php echo filterUrl($category, $key); ?>" 
                       class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?php echo $type == $key ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'; ?>">
                        <span><?php echo $typ['icon']; ?></span>
                        <span><?php echo $typ[$lang]; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Results count -->
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <span class="font-bold text-slate-700 dark:text-slate-300"><?php echo count($jobs); ?></span>
                <?php echo $lang == 'en' ? 'job' : 'ilan'; ?><?php echo count($jobs) !== 1 ? ($lang == 'en' ? 's' : '') : ''; ?>
            </p>
        </div>

        <!-- Job Listings -->
        <?php if (count($jobs) > 0): ?>
        <div class="space-y-4">
            <?php foreach ($jobs as $job): 
                $jobCat = $categories[$job['category']] ?? $categories['other'];
                $jobType = $types[$job['employment_type']] ?? $types['seasonal'];
                $desc = $job['description'] ?? '';
                $descShort = mb_strlen($desc) > 180 ? mb_substr($desc, 0, 180) . '...' : $desc;
                $avatar = !empty($job['employer_avatar']) ? htmlspecialchars($job['employer_avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($job['full_name'] ?? '') . '&background=6366f1&color=fff&size=80';
            ?>
            <a href="job_detail?id=<?php echo $job['id']; ?>" 
               class="block bg-white dark:bg-slate-800 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-lg transition-all group">
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Employer Avatar -->
                    <div class="flex-shrink-0">
                        <img src="<?php echo $avatar; ?>" alt="" class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl object-cover border-2 border-slate-100 dark:border-slate-700 group-hover:border-indigo-200 dark:group-hover:border-indigo-800 transition-colors">
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-1">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </h3>
                            <?php if ($job['badge'] == 'verified_business'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold shrink-0">
                                <i class="fas fa-check-circle"></i> <?php echo $lang == 'en' ? 'Verified' : 'Onaylı'; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-indigo-600 dark:text-indigo-400 font-bold text-sm mb-3">
                            <?php echo htmlspecialchars($job['full_name']); ?>
                        </p>
                        <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed line-clamp-2 mb-4">
                            <?php echo htmlspecialchars($descShort); ?>
                        </p>
                        <!-- Tags -->
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold">
                                <?php echo $jobCat['icon']; ?> <?php echo $jobCat[$lang]; ?>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-xs font-bold">
                                <?php echo $jobType['icon']; ?> <?php echo $jobType[$lang]; ?>
                            </span>
                            <?php if (!empty($job['salary_range'])): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold">
                                <i class="fas fa-lira-sign"></i> <?php echo htmlspecialchars($job['salary_range']); ?>
                            </span>
                            <?php endif; ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-xs font-bold ml-auto">
                                <i class="fas fa-users"></i> <?php echo (int)($job['applications_count'] ?? 0); ?> <?php echo $t['applications'] ?? ($lang == 'en' ? 'Applications' : 'Başvuru'); ?>
                            </span>
                        </div>
                    </div>
                    <!-- Arrow -->
                    <div class="hidden sm:flex items-center text-slate-300 dark:text-slate-600 group-hover:text-indigo-500 transition-colors shrink-0">
                        <i class="fas fa-chevron-right text-xl"></i>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 sm:p-16 text-center border border-slate-200 dark:border-slate-700">
            <div class="w-20 h-20 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-briefcase text-3xl text-slate-400 dark:text-slate-500"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-200 mb-2">
                <?php echo $lang == 'en' ? 'No jobs found' : 'İlan bulunamadı'; ?>
            </h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-6 max-w-sm mx-auto">
                <?php echo $lang == 'en' ? 'Try changing your filters or check back later for new listings.' : 'Filtreleri değiştirmeyi deneyin veya yeni ilanlar için daha sonra tekrar bakın.'; ?>
            </p>
            <a href="<?php echo filterUrl('all', 'all'); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                <i class="fas fa-undo"></i> <?php echo $lang == 'en' ? 'Reset Filters' : 'Filtreleri Sıfırla'; ?>
            </a>
        </div>
        <?php endif; ?>
    </main>

</body>
</html>
