<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";
require_once "../includes/icon_helper.php";

$stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar, cat.name as cat_name, cat.location 
                       FROM user_cat_collection c 
                       JOIN users u ON c.user_id = u.id 
                       JOIN cats cat ON c.cat_id = cat.id
                       WHERE c.status = 'pending' AND c.user_photo IS NOT NULL
                       ORDER BY c.found_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catdex Approvals | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex transition-colors">

    <?php include "includes/sidebar.php"; ?>

    <!-- Main Content -->
    <main class="ml-0 lg:ml-72 flex-1 p-6 lg:p-12 mt-16 lg:mt-0">
        <header class="mb-12">
            <h1 class="text-3xl lg:text-4xl font-black mb-2">Catdex Approvals</h1>
            <p class="text-slate-500 dark:text-slate-400">Review user uploaded cat photos.</p>
        </header>

        <div class="space-y-6">
            <?php foreach($requests as $req): ?>
                <div id="request-<?php echo $req['id']; ?>" class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-6 lg:p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        
                        <!-- Photo Preview (Clickable) -->
                        <div class="relative group cursor-zoom-in" onclick="openLightbox('../<?php echo $req['user_photo']; ?>')">
                            <img src="../<?php echo $req['user_photo']; ?>" class="w-32 h-32 lg:w-40 lg:h-40 rounded-[1.5rem] object-cover shadow-lg bg-slate-100">
                            <div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-colors rounded-[1.5rem] flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <i class="fas fa-search-plus text-white text-2xl"></i>
                            </div>
                        </div>
                        
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-amber-500"><?php echo htmlspecialchars($req['cat_name']); ?></h3>
                                <div class="px-3 py-1 bg-slate-100 dark:bg-slate-700 rounded-full text-xs font-bold text-slate-500">
                                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($req['location']); ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 mb-6">
                                <?php echo renderAvatar($req['avatar'], ['size' => 'sm']); ?>
                                <div>
                                    <div class="font-bold text-sm">@<?php echo htmlspecialchars($req['username']); ?></div>
                                    <div class="text-xs text-slate-400 uppercase font-black tracking-widest"><?php echo date('d M H:i', strtotime($req['found_at'])); ?></div>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <button onclick="handleRequest(<?php echo $req['id']; ?>, 'approve')" class="flex-1 sm:flex-none bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-emerald-500/20 transition-all active:scale-95">
                                    <i class="fas fa-check mr-2"></i> Approve
                                </button>
                                <button onclick="handleRequest(<?php echo $req['id']; ?>, 'reject')" class="flex-1 sm:flex-none bg-slate-100 dark:bg-slate-700 hover:bg-red-500 hover:text-white px-8 py-4 rounded-2xl font-bold transition-all active:scale-95 text-slate-600 dark:text-slate-300">
                                    <i class="fas fa-times mr-2"></i> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($requests)): ?>
                <div class="text-center py-20 opacity-30">
                    <i class="fas fa-cat text-6xl mb-4"></i>
                    <p class="text-xl font-bold">No pending cat photos.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Lightbox -->
        <div id="lightbox" class="fixed inset-0 z-[100] bg-black/90 hidden flex items-center justify-center p-4" onclick="this.classList.add('hidden')">
             <img id="lightbox-img" src="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
        </div>

    </main>

    <script>
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').classList.remove('hidden');
        }

        function handleRequest(id, action) {
            const params = new URLSearchParams();
            params.append('action', 'handle_cat_photo');
            params.append('collection_id', id);
            params.append('decision', action); // approve or reject

            fetch('api_admin.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const el = document.getElementById('request-' + id);
                    el.style.opacity = '0';
                    el.style.transform = 'translateX(50px)';
                    setTimeout(() => el.remove(), 300);
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
