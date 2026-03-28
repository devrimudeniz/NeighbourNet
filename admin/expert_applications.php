<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $app_id = (int)$_POST['app_id'];
    $user_id = (int)$_POST['user_id'];

    if ($action === 'approve') {
        $pdo->beginTransaction();
        try {
            // Update application status
            $stmt = $pdo->prepare("UPDATE expert_applications SET status = 'approved' WHERE id = ?");
            $stmt->execute([$app_id]);

            // Assign Badge
            // 1. Get expertise area
            $stmt = $pdo->prepare("SELECT area_of_expertise FROM expert_applications WHERE id = ?");
            $stmt->execute([$app_id]);
            $expertise = $stmt->fetchColumn();

            // 2. Add to user_badges if not exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_type = 'local_guide'");
            $check->execute([$user_id]);
            if ($check->fetchColumn() == 0) {
                $pdo->prepare("INSERT INTO user_badges (user_id, badge_type) VALUES (?, 'local_guide')")->execute([$user_id]);
            }

            // 3. Update main users table expert_badge column
            $pdo->prepare("UPDATE users SET expert_badge = ? WHERE id = ?")->execute([$expertise, $user_id]);

            // Send Notification
             $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, content, is_read, created_at) VALUES (?, ?, 'system', 'Congratulations! Your Expert application has been approved.', 0, NOW())")->execute([$user_id, $_SESSION['user_id']]);


            $pdo->commit();
            $success_msg = "Application approved and badge assigned!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE expert_applications SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$app_id]);
         $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, content, is_read, created_at) VALUES (?, ?, 'system', 'Your Expert application has been reviewed and declined at this time.', 0, NOW())")->execute([$user_id, $_SESSION['user_id']]);
        $success_msg = "Application rejected.";
    }
}

// Fetch Pending Applications
$stmt = $pdo->query("
    SELECT ea.*, u.username, u.full_name, u.avatar 
    FROM expert_applications ea 
    JOIN users u ON ea.user_id = u.id 
    WHERE ea.status = 'pending' 
    ORDER BY ea.created_at ASC
");
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expert Approvals | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white">

    <?php include "includes/sidebar.php"; ?>

    <main class="lg:ml-72 flex-1 p-6 pt-20">
        <header class="mb-8">
            <h1 class="text-3xl font-black mb-2">Expert Applications</h1>
            <p class="text-slate-500">Review users applying for the Verified Expert badge.</p>
        </header>

        <?php if (isset($success_msg)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r" role="alert">
                <p class="font-bold">Success</p>
                <p><?php echo $success_msg; ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo $error_msg; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($applications as $app): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-100 dark:border-slate-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                         <?php $avatar = !empty($app['avatar']) ? $app['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($app['full_name']); ?>
                        <img src="<?php echo $avatar; ?>" class="w-12 h-12 rounded-full object-cover shadow-md">
                        <div>
                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($app['full_name']); ?></h3>
                            <p class="text-xs text-slate-500">@<?php echo htmlspecialchars($app['username']); ?></p>
                        </div>
                        <span class="ml-auto px-2 py-1 bg-violet-100 text-violet-600 rounded-lg text-xs font-bold">
                            <?php echo htmlspecialchars($app['area_of_expertise']); ?>
                        </span>
                    </div>

                    <div class="mb-4">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Motivation</h4>
                        <p class="text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl italic border border-slate-100 dark:border-slate-700">
                            "<?php echo nl2br(htmlspecialchars($app['motivation'])); ?>"
                        </p>
                    </div>

                    <?php if(!empty($app['social_links'])): ?>
                    <div class="mb-4">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Socials</h4>
                        <a href="<?php echo htmlspecialchars($app['social_links']); ?>" target="_blank" class="text-blue-500 text-sm hover:underline truncate block">
                            <i class="fas fa-link mr-1"></i> <?php echo htmlspecialchars($app['social_links']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-xs text-slate-400 mb-4 text-right">
                        Applied: <?php echo date('d M Y, H:i', strtotime($app['created_at'])); ?>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <form method="POST">
                            <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $app['user_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="w-full py-2 rounded-xl border border-red-200 text-red-500 hover:bg-red-50 font-bold transition-colors">
                                Decline
                            </button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $app['user_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="w-full py-2 rounded-xl bg-violet-600 text-white hover:bg-violet-700 font-bold shadow-lg shadow-violet-500/20 transition-all">
                                Approve
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($applications)): ?>
                <div class="col-span-full py-12 text-center text-slate-400 italic">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fas fa-check"></i>
                    </div>
                    No pending applications. All caught up!
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
