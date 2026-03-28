<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/jobs_helper.php';

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job details
$stmt = $pdo->prepare("
    SELECT jl.*, u.username as employer_username, u.full_name as employer_name, u.avatar as employer_avatar, u.badge
    FROM job_listings jl
    JOIN users u ON jl.employer_id = u.id
    WHERE jl.id = ? AND jl.is_active = 1
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    header('Location: jobs.php');
    exit();
}

$categories = getJobCategories($lang);
$types = getJobTypes($lang);
$cat = $categories[$job['category']] ?? $categories['other'];
$typeLabel = $types[$job['employment_type']] ?? $types['seasonal'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <script>
        // Force light mode
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    </script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-3xl">
        <!-- Back Button -->
        <a href="jobs.php" class="inline-flex items-center gap-2 text-pink-500 font-bold mb-6 hover:underline">
            <i class="fas fa-arrow-left"></i>
            <?php echo $lang == 'en' ? 'Back to Jobs' : 'İş İlanlarına Dön'; ?>
        </a>

        <!-- Job Card -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-3xl p-8 border border-white/20 dark:border-slate-800/50 shadow-xl">
            <!-- Header -->
            <div class="flex items-start gap-4 mb-6">
                <img src="<?php echo $job['employer_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($job['employer_name']); ?>" 
                     class="w-16 h-16 rounded-2xl object-cover border-2 border-slate-100 dark:border-slate-700" loading="lazy">
                <div class="flex-1">
                    <h1 class="text-2xl font-extrabold mb-1"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <a href="profile.php?username=<?php echo $job['employer_username']; ?>" class="text-pink-500 font-bold hover:underline">
                        <?php echo htmlspecialchars($job['employer_name']); ?>
                        <?php if($job['badge'] == 'verified_business'): ?>
                        <i class="fas fa-check-circle text-blue-500 text-sm ml-1" title="<?php echo $lang == 'en' ? 'Verified' : 'Onaylı'; ?>"></i>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Tags -->
            <div class="flex flex-wrap gap-2 mb-6">
                <span class="bg-slate-100 dark:bg-slate-800 px-4 py-2 rounded-full font-bold text-sm">
                    <?php echo $cat['icon']; ?> <?php echo $lang == 'en' ? $cat['en'] : $cat['tr']; ?>
                </span>
                <span class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-4 py-2 rounded-full font-bold text-sm">
                    <?php echo $typeLabel['icon']; ?> <?php echo $lang == 'en' ? $typeLabel['en'] : $typeLabel['tr']; ?>
                </span>
                <?php if ($job['salary_range']): ?>
                <span class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 px-4 py-2 rounded-full font-bold text-sm">
                    💰 <?php echo htmlspecialchars($job['salary_range']); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <h2 class="text-lg font-bold mb-3"><?php echo $lang == 'en' ? 'Job Description' : 'İş Açıklaması'; ?></h2>
                <div class="text-slate-700 dark:text-slate-300 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </div>
            </div>

            <!-- Requirements -->
            <?php if ($job['requirements']): ?>
            <div class="mb-6">
                <h2 class="text-lg font-bold mb-3"><?php echo $lang == 'en' ? 'Requirements' : 'Gereksinimler'; ?></h2>
                <div class="text-slate-700 dark:text-slate-300 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contact -->
            <?php if ($job['contact_info']): ?>
            <div class="bg-gradient-to-r from-pink-500 to-violet-500 rounded-2xl p-6 text-white">
                <h2 class="text-lg font-bold mb-2"><?php echo $lang == 'en' ? 'How to Apply' : 'Nasıl Başvurulur'; ?></h2>
                <p class="text-white/90"><?php echo htmlspecialchars($job['contact_info']); ?></p>
            </div>
            <?php endif; ?>

            <!-- Posted Date -->
            <p class="text-center text-slate-400 text-sm mt-6">
                <?php echo $lang == 'en' ? 'Posted' : 'Yayınlanma'; ?>: 
                <?php echo date('d.m.Y', strtotime($job['created_at'])); ?>
            </p>

            <!-- Owner Actions -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $job['employer_id']): ?>
            <div class="mt-6 pt-6 border-t border-slate-200">
                <button onclick="deleteJob()" class="w-full bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-xl font-bold transition-colors">
                    <i class="fas fa-trash mr-2"></i>
                    <?php echo $lang == 'en' ? 'Delete This Job' : 'Bu İlanı Sil'; ?>
                </button>
                <p class="text-center text-xs text-slate-400 mt-2">
                    <?php echo $lang == 'en' ? 'Only you can see this button' : 'Bu butonu sadece siz görebilirsiniz'; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function deleteJob() {
        const confirmMsg = '<?php echo $lang == "en" ? "Are you sure you want to delete this job listing? This action cannot be undone." : "Bu iş ilanını silmek istediğinize emin misiniz? Bu işlem geri alınamaz."; ?>';
        
        if (confirm(confirmMsg)) {
            fetch('api/delete_job.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'job_id=<?php echo $job_id; ?>'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('<?php echo $lang == "en" ? "Job deleted successfully!" : "İlan başarıyla silindi!"; ?>');
                    window.location.href = 'jobs.php';
                } else {
                    alert(data.error || 'Error');
                }
            });
        }
    }
    </script>
    <script>
        // Force light mode
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        function toggleTheme() { alert('Bu sayfa sadece açık tema destekler.'); }
    </script>

</body>
</html>
