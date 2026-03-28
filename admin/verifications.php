<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";

$stmt = $pdo->prepare("SELECT vr.*, u.username, u.full_name as current_name, u.avatar 
                       FROM verification_requests vr 
                       JOIN users u ON vr.user_id = u.id 
                       WHERE vr.status = 'pending'
                       ORDER BY vr.created_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Verifications | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex transition-colors">

    <?php include "includes/sidebar.php"; ?>

    <!-- Main Content -->
    <main class="ml-72 flex-1 p-12">
        <header class="mb-12">
            <h1 class="text-4xl font-black mb-2"><?php echo $t['manage_verifications'] ?? 'Manage Verifications'; ?></h1>
            <p class="text-slate-500 dark:text-slate-400">Review pending verification requests from users.</p>
        </header>

        <div class="space-y-6">
            <?php foreach($requests as $req): ?>
                <div id="request-<?php echo $req['id']; ?>" class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-700 shadow-sm">
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <img src="<?php echo getAdminAvatar($req['avatar']); ?>" class="w-20 h-20 rounded-[1.5rem] object-cover shadow-lg bg-slate-100">
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($req['current_name']); ?></h3>
                                <span class="text-xs font-bold text-slate-400">@<?php echo htmlspecialchars($req['username']); ?></span>
                                
                                <!-- Request Type Badge -->
                                <?php if ($req['request_type'] == 'captain'): ?>
                                    <span class="px-3 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 rounded-full text-xs font-black uppercase">
                                        <i class="fas fa-ship mr-1"></i> Captain
                                    </span>
                                <?php elseif ($req['request_type'] == 'real_estate'): ?>
                                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-full text-xs font-black uppercase">
                                        <i class="fas fa-home mr-1"></i> Real Estate
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-xs font-black uppercase">
                                        <i class="fas fa-store mr-1"></i> Business
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-wrap gap-4 text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">
                                <span class="flex items-center gap-1"><i class="fas fa-calendar"></i> <?php echo date('d M H:i', strtotime($req['created_at'])); ?></span>
                            </div>

                            <!-- Business Details -->
                            <?php if ($req['request_type'] == 'business'): ?>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl">
                                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Business Name</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($req['business_name']); ?></p>
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl">
                                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Category</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($req['business_category']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Captain Details -->
                            <?php if ($req['request_type'] == 'captain'): ?>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-2xl">
                                        <p class="text-[10px] font-black uppercase text-cyan-600 dark:text-cyan-400 mb-1">Boat Name</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($req['boat_name']); ?></p>
                                    </div>
                                    <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-2xl">
                                        <p class="text-[10px] font-black uppercase text-cyan-600 dark:text-cyan-400 mb-1">License Number</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($req['boat_license_number']); ?></p>
                                    </div>
                                </div>

                                <?php if ($req['documentation_file']): ?>
                                    <div class="mb-4">
                                        <a href="../<?php echo htmlspecialchars($req['documentation_file']); ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 rounded-xl font-bold text-sm hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition-colors">
                                            <i class="fas fa-file-pdf"></i> View Documentation
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Real Estate Details -->
                            <?php if ($req['request_type'] == 'real_estate'): ?>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-2xl">
                                        <p class="text-[10px] font-black uppercase text-purple-600 dark:text-purple-400 mb-1">Agency Name</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($req['business_name']); ?></p>
                                    </div>
                                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-2xl">
                                        <p class="text-[10px] font-black uppercase text-purple-600 dark:text-purple-400 mb-1">License No</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($req['license_number']); ?></p>
                                    </div>
                                </div>
                                <?php if ($req['documentation_file']): ?>
                                    <div class="mb-4">
                                        <a href="../<?php echo htmlspecialchars($req['documentation_file']); ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-xl font-bold text-sm hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                                            <i class="fas fa-file-pdf"></i> View Documentation
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Additional Info -->
                            <?php if ($req['additional_info']): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl italic">
                                    "<?php echo htmlspecialchars($req['additional_info']); ?>"
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="flex gap-3">
                            <button onclick="handleRequest(<?php echo $req['id']; ?>, 'approve', '<?php echo $req['request_type']; ?>')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-emerald-500/20 transition-all active:scale-95">
                                <i class="fas fa-check mr-2"></i> Approve
                            </button>
                            <button onclick="handleRequest(<?php echo $req['id']; ?>, 'reject', '<?php echo $req['request_type']; ?>')" class="bg-slate-100 dark:bg-slate-700 hover:bg-red-500 hover:text-white px-8 py-4 rounded-2xl font-bold transition-all active:scale-95">
                                <i class="fas fa-times mr-2"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($requests)): ?>
                <div class="text-center py-20 opacity-30">
                    <i class="fas fa-user-shield text-6xl mb-4"></i>
                    <p class="text-xl font-bold">No pending verification requests.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function handleRequest(id, action, requestType) {
            const params = new URLSearchParams();
            params.append('action', action);
            params.append('request_id', id);
            params.append('request_type', requestType);

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
