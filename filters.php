<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user_filters table exists
$table_exists = false;
try {
    $check = $pdo->query("SHOW TABLES LIKE 'user_filters'");
    $table_exists = $check->rowCount() > 0;
} catch (PDOException $e) {
    $table_exists = false;
}

$muted_users = [];
$blocked_users = [];

if ($table_exists) {
    // Get filtered users
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar, f.filter_type, f.created_at
        FROM user_filters f
        JOIN users u ON f.target_id = u.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $filters = $stmt->fetchAll();

    $muted_users = array_filter($filters, fn($f) => $f['filter_type'] == 'mute');
    $blocked_users = array_filter($filters, fn($f) => $f['filter_type'] == 'block');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Muted & Blocked Users' : 'Sessize Alınan ve Engellenenler'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <!-- Spacer for fixed header -->
    <div class="h-24"></div>

    <main class="max-w-4xl mx-auto px-4 py-6">
        <!-- Back Button -->
        <a href="profile" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-pink-500 mb-6 transition-colors">
            <?php echo heroicon('arrow_left', 'w-4 h-4'); ?>
            <?php echo $lang == 'en' ? 'Back to Profile' : 'Profile\'e Dön'; ?>
        </a>

        <div class="mb-8">
            <h1 class="text-2xl font-black bg-gradient-to-r from-pink-500 to-violet-600 bg-clip-text text-transparent">
                <?php echo $lang == 'en' ? 'Muted & Blocked Users' : 'Sessize Alınan ve Engellenenler'; ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                <?php echo $lang == 'en' ? 'Manage your privacy and interactions.' : 'Gizliliğinizi ve etkileşimlerinizi yönetin.'; ?>
            </p>
        </div>

        <?php if (!$table_exists): ?>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 text-center">
            <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <?php echo heroicon('exclamation_circle', 'w-8 h-8 text-amber-500'); ?>
            </div>
            <p class="text-amber-700 dark:text-amber-400 font-bold">
                <?php echo $lang == 'en' ? 'This feature is not yet available.' : 'Bu özellik henüz kullanıma hazır değil.'; ?>
            </p>
        </div>
        <?php else: ?>

        <div class="space-y-8">
            <!-- Muted Section -->
            <section class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <?php echo heroicon('speaker_x_mark', 'w-5 h-5 text-orange-500'); ?>
                        <?php echo $lang == 'en' ? 'Muted Users' : 'Sessize Alınanlar'; ?>
                        <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 px-2 py-0.5 rounded-full"><?php echo count($muted_users); ?></span>
                    </h2>
                </div>
                
                <?php if (empty($muted_users)): ?>
                    <div class="p-8 text-center text-slate-400">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <?php echo heroicon('speaker_wave', 'w-8 h-8'); ?>
                        </div>
                        <p><?php echo $lang == 'en' ? 'You haven\'t muted anyone yet.' : 'Henüz kimseyi sessize almadınız.'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php foreach ($muted_users as $user): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600" loading="lazy">
                                    <div>
                                        <h4 class="font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                        <p class="text-xs text-slate-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                                    </div>
                                </div>
                                <button onclick="handleFilterAction(<?php echo $user['id']; ?>, 'unmute')" class="text-sm font-bold text-orange-500 hover:text-orange-600 px-4 py-2 rounded-xl border border-orange-200 dark:border-orange-800 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all flex items-center gap-2">
                                    <?php echo heroicon('speaker_wave', 'w-4 h-4'); ?>
                                    <span class="hidden sm:inline"><?php echo $lang == 'en' ? 'Unmute' : 'Sesi Aç'; ?></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Blocked Section -->
            <section class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                    <h2 class="text-lg font-bold flex items-center gap-2">
                        <?php echo heroicon('no_symbol', 'w-5 h-5 text-red-500'); ?>
                        <?php echo $lang == 'en' ? 'Blocked Users' : 'Engellenenler'; ?>
                        <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-600 px-2 py-0.5 rounded-full"><?php echo count($blocked_users); ?></span>
                    </h2>
                </div>
                
                <?php if (empty($blocked_users)): ?>
                    <div class="p-8 text-center text-slate-400">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <?php echo heroicon('shield', 'w-8 h-8'); ?>
                        </div>
                        <p><?php echo $lang == 'en' ? 'You haven\'t blocked anyone yet.' : 'Henüz kimseyi engellemediniz.'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php foreach ($blocked_users as $user): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-red-200 dark:border-red-800 grayscale" loading="lazy">
                                    <div>
                                        <h4 class="font-bold text-red-600 dark:text-red-400"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                        <p class="text-xs text-slate-500">@<?php echo htmlspecialchars($user['username']); ?></p>
                                    </div>
                                </div>
                                <button onclick="handleFilterAction(<?php echo $user['id']; ?>, 'unblock')" class="text-sm font-bold text-red-500 hover:text-red-600 px-4 py-2 rounded-xl border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all flex items-center gap-2">
                                    <?php echo heroicon('check', 'w-4 h-4'); ?>
                                    <span class="hidden sm:inline"><?php echo $lang == 'en' ? 'Unblock' : 'Engeli Kaldır'; ?></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <?php endif; ?>
    </main>

    <script>
    async function handleFilterAction(targetId, action) {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.classList.add('opacity-50');
        
        try {
            const formData = new FormData();
            formData.append('target_id', targetId);
            formData.append('action', action);

            const res = await fetch('api/user_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                // Remove the row with animation
                const row = btn.closest('.flex');
                row.style.transition = 'all 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    row.remove();
                    location.reload();
                }, 300);
            } else {
                alert(data.message || '<?php echo $lang == "en" ? "An error occurred" : "Bir hata oluştu"; ?>');
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            }
        } catch (e) {
            alert('<?php echo $lang == "en" ? "Connection error" : "Bağlantı hatası"; ?>');
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }
    </script>

</body>
</html>
