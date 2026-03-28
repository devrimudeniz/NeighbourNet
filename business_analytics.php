<?php
/**
 * Business Analytics Dashboard
 * Shows profile views, engagement stats and charts for business accounts
 */
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
require_once 'includes/analytics_helper.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if user is a business
$is_business = ($user['role'] == 'venue' || in_array($user['badge'], ['founder', 'business', 'moderator', 'verified_business']));

if (!$is_business) {
    header('Location: profile?uid=' . $user_id);
    exit();
}

// Get period from query string
$period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
$period = in_array($period, [7, 14, 30, 90]) ? $period : 30;

// Get stats
$stats = getProfileStats($pdo, $user_id, $period);
$engagement = getEngagementStats($pdo, $user_id, $period);
$top_posts = getTopPosts($pdo, $user_id, 5);

// Prepare chart data
$chart_labels = [];
$chart_data = [];
$start_date = new DateTime("-{$period} days");
$end_date = new DateTime();

while ($start_date <= $end_date) {
    $date_str = $start_date->format('Y-m-d');
    $chart_labels[] = $start_date->format('d M');
    $chart_data[] = $stats['daily'][$date_str] ?? 0;
    $start_date->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Business Analytics' : 'İşletme Analizi'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <!-- Header -->
    <header class="fixed top-0 left-0 w-full z-50 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border-b border-white/20 dark:border-slate-800/50 shadow-sm">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="profile?uid=<?php echo $user_id; ?>" class="flex items-center gap-2 text-slate-600 dark:text-slate-300 hover:text-pink-500 transition-colors">
                <i class="fas fa-arrow-left w-5 h-5"></i>
                <span class="font-bold"><?php echo $lang == 'en' ? 'Back to Profile' : 'Profile Geri Dön'; ?></span>
            </a>
            <h1 class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                <?php echo $lang == 'en' ? 'Analytics' : 'Analiz'; ?>
            </h1>
        </div>
    </header>

    <div class="h-20"></div>

    <main class="container mx-auto px-6 py-8">
        <!-- Period Selector -->
        <div class="flex gap-2 mb-8 overflow-x-auto pb-2">
            <?php foreach ([7 => '7 ' . ($lang == 'en' ? 'Days' : 'Gün'), 14 => '14 ' . ($lang == 'en' ? 'Days' : 'Gün'), 30 => '30 ' . ($lang == 'en' ? 'Days' : 'Gün'), 90 => '90 ' . ($lang == 'en' ? 'Days' : 'Gün')] as $p => $label): ?>
                <a href="?period=<?php echo $p; ?>" 
                   class="px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all <?php echo $period == $p ? 'bg-gradient-to-r from-pink-500 to-violet-500 text-white shadow-lg' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Views -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-500">
                        <i class="fas fa-eye w-5 h-5"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide"><?php echo $lang == 'en' ? 'Profile Views' : 'Profil Görüntüleme'; ?></p>
                </div>
                <h3 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo number_format($stats['total_views']); ?></h3>
                <p class="text-sm text-slate-500 mt-1"><?php echo $stats['unique_visitors']; ?> <?php echo $lang == 'en' ? 'unique visitors' : 'tekil ziyaretçi'; ?></p>
            </div>

            <!-- Member vs Guest -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-500">
                        <i class="fas fa-users w-5 h-5"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide"><?php echo $lang == 'en' ? 'Visitor Types' : 'Ziyaretçi Türleri'; ?></p>
                </div>
                <div class="flex items-baseline gap-4">
                    <div>
                        <h3 class="text-2xl font-black text-blue-500"><?php echo $stats['member_views']; ?></h3>
                        <p class="text-xs text-slate-500"><?php echo $lang == 'en' ? 'Members' : 'Üyeler'; ?></p>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-emerald-500"><?php echo $stats['guest_views']; ?></h3>
                        <p class="text-xs text-slate-500"><?php echo $lang == 'en' ? 'Guests' : 'Misafirler'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Likes -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-500">
                        <i class="fas fa-heart w-5 h-5"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide"><?php echo $lang == 'en' ? 'Likes Received' : 'Alınan Beğeniler'; ?></p>
                </div>
                <h3 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo number_format($engagement['total_likes']); ?></h3>
                <p class="text-sm text-slate-500 mt-1">~<?php echo $engagement['avg_likes_per_post']; ?>/<?php echo $lang == 'en' ? 'post' : 'gönderi'; ?></p>
            </div>

            <!-- Total Comments -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-500">
                        <i class="fas fa-comment w-5 h-5"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide"><?php echo $lang == 'en' ? 'Comments' : 'Yorumlar'; ?></p>
                </div>
                <h3 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo number_format($engagement['total_comments']); ?></h3>
                <p class="text-sm text-slate-500 mt-1">~<?php echo $engagement['avg_comments_per_post']; ?>/<?php echo $lang == 'en' ? 'post' : 'gönderi'; ?></p>
            </div>
        </div>

        <!-- Views Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm mb-8">
            <h3 class="font-bold text-lg mb-6 flex items-center gap-2">
                <i class="fas fa-chart-bar w-5 h-5 text-pink-500"></i>
                <?php echo $lang == 'en' ? 'Profile Views Over Time' : 'Zamana Göre Görüntülenmeler'; ?>
            </h3>
            <div class="h-64">
                <canvas id="viewsChart"></canvas>
            </div>
        </div>

        <!-- Device Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Device Stats -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
                <h3 class="font-bold text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-mobile-alt w-5 h-5 text-blue-500"></i>
                    <?php echo $lang == 'en' ? 'Devices' : 'Cihazlar'; ?>
                </h3>
                <div class="space-y-4">
                    <?php 
                    $total_devices = array_sum($stats['devices']) ?: 1;
                    $device_icons = ['mobile' => 'fa-mobile-alt', 'desktop' => 'fa-desktop', 'tablet' => 'fa-tablet-alt'];
                    $device_colors = ['mobile' => 'pink', 'desktop' => 'blue', 'tablet' => 'violet'];
                    foreach ($stats['devices'] as $device => $count): 
                        $percent = round(($count / $total_devices) * 100);
                    ?>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="flex items-center gap-2 text-sm font-medium">
                                <i class="fas <?php echo $device_icons[$device]; ?> w-4 h-4 text-<?php echo $device_colors[$device]; ?>-500"></i>
                                <?php echo ucfirst($device); ?>
                            </span>
                            <span class="text-sm text-slate-500"><?php echo $count; ?> (<?php echo $percent; ?>%)</span>
                        </div>
                        <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-<?php echo $device_colors[$device]; ?>-500 rounded-full" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Referrers -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
                <h3 class="font-bold text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-chart-line w-5 h-5 text-emerald-500"></i>
                    <?php echo $lang == 'en' ? 'Traffic Sources' : 'Trafik Kaynakları'; ?>
                </h3>
                <?php if (empty($stats['top_referrers'])): ?>
                    <p class="text-slate-500 text-sm"><?php echo $lang == 'en' ? 'No referrer data yet' : 'Henüz kaynak verisi yok'; ?></p>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($stats['top_referrers'] as $ref): 
                            $domain = parse_url($ref['referrer'], PHP_URL_HOST) ?: $ref['referrer'];
                        ?>
                        <li class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                            <span class="text-sm font-medium truncate max-w-[200px]"><?php echo htmlspecialchars($domain); ?></span>
                            <span class="text-sm text-slate-500 font-bold"><?php echo $ref['count']; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Posts -->
        <?php if (!empty($top_posts)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm">
            <h3 class="font-bold text-lg mb-6 flex items-center gap-2">
                <i class="fas fa-fire w-5 h-5 text-orange-500"></i>
                <?php echo $lang == 'en' ? 'Top Performing Posts' : 'En Çok Etkileşim Alan Gönderiler'; ?>
            </h3>
            <div class="space-y-4">
                <?php foreach ($top_posts as $i => $post): ?>
                <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    <span class="text-2xl font-black text-slate-300 dark:text-slate-600 w-8">#<?php echo $i + 1; ?></span>
                    <?php if ($post['image']): ?>
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" class="w-16 h-16 rounded-lg object-cover" loading="lazy">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-lg bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-slate-400">
                            <i class="fas fa-file-alt w-6 h-6"></i>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-700 dark:text-slate-200 line-clamp-2"><?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?></p>
                        <p class="text-xs text-slate-400 mt-1"><?php echo date('d M Y', strtotime($post['created_at'])); ?></p>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="flex items-center gap-1 text-red-500">
                            <i class="fas fa-heart w-4 h-4"></i> <?php echo $post['likes']; ?>
                        </span>
                        <span class="flex items-center gap-1 text-blue-500">
                            <i class="fas fa-comment w-4 h-4"></i> <?php echo $post['comments']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/bottom_nav.php'; ?>

    <script>
    // Views Chart
    const ctx = document.getElementById('viewsChart').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: '<?php echo $lang == "en" ? "Views" : "Görüntüleme"; ?>',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#ec4899',
                backgroundColor: 'rgba(236, 72, 153, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#ec4899'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        color: isDark ? '#94a3b8' : '#64748b'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: isDark ? '#94a3b8' : '#64748b',
                        maxTicksLimit: 10
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
