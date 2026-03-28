<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";

// Fetch all boat trips
$stmt = $pdo->prepare("
    SELECT bt.*, u.full_name as captain_name, u.username 
    FROM boat_trips bt
    JOIN users u ON bt.captain_id = u.id
    ORDER BY bt.created_at DESC
");
$stmt->execute();
$trips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Boat Trips | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex transition-colors">

    <?php include "includes/sidebar.php"; ?>

    <!-- Main Content -->
    <main class="ml-72 flex-1 p-12">
        <header class="mb-12">
            <h1 class="text-4xl font-black mb-2">Manage Boat Trips</h1>
            <p class="text-slate-500 dark:text-slate-400">Review and manage boat trip listings.</p>
        </header>

        <div class="space-y-6">
            <?php foreach($trips as $trip): ?>
                <div id="trip-<?php echo $trip['id']; ?>" class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col md:flex-row gap-8 items-start">
                    <!-- Photo -->
                    <img src="../<?php echo htmlspecialchars($trip['cover_photo']); ?>" class="w-32 h-24 rounded-2xl object-cover shadow-lg bg-slate-100">
                    
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($trip['title']); ?></h3>
                            
                            <!-- Status Badge -->
                            <?php if ($trip['status'] === 'approved'): ?>
                                <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-xs font-black uppercase">Active</span>
                            <?php elseif ($trip['status'] === 'pending'): ?>
                                <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-xs font-black uppercase">Pending</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs font-black uppercase">Rejected</span>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap gap-4 text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">
                            <span class="flex items-center gap-1"><i class="fas fa-user-captain"></i> <?php echo htmlspecialchars($trip['captain_name']); ?></span>
                            <span class="flex items-center gap-1"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($trip['category']); ?></span>
                            <span class="flex items-center gap-1"><i class="fas fa-coins"></i> <?php echo $trip['price_per_person'] . ' ' . $trip['currency']; ?></span>
                        </div>

                        <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mb-4">
                            <?php echo htmlspecialchars($trip['description']); ?>
                        </p>

                        <a href="../trip_detail?id=<?php echo $trip['id']; ?>" target="_blank" class="text-cyan-500 text-sm font-bold hover:underline">View Public Page <i class="fas fa-external-link-alt ml-1"></i></a>
                    </div>

                    <div class="flex flex-col gap-2 min-w-[140px]">
                        <?php if ($trip['status'] !== 'approved'): ?>
                            <button onclick="handleTrip(<?php echo $trip['id']; ?>, 'approve')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-emerald-500/20 transition-all active:scale-95 text-sm">
                                <i class="fas fa-check mr-2"></i> Approve
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($trip['status'] !== 'rejected'): ?>
                            <button onclick="handleTrip(<?php echo $trip['id']; ?>, 'reject')" class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-amber-500/20 transition-all active:scale-95 text-sm">
                                <i class="fas fa-ban mr-2"></i> Reject
                            </button>
                        <?php endif; ?>

                        <button onclick="handleTrip(<?php echo $trip['id']; ?>, 'delete')" class="bg-slate-100 dark:bg-slate-700 hover:bg-red-500 hover:text-white dark:hover:bg-red-500 px-6 py-3 rounded-xl font-bold transition-all active:scale-95 text-sm">
                            <i class="fas fa-trash mr-2"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($trips)): ?>
                <div class="text-center py-20 opacity-30">
                    <i class="fas fa-ship text-6xl mb-4"></i>
                    <p class="text-xl font-bold">No boat trips found.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function handleTrip(id, subAction) {
            if (subAction === 'delete' && !confirm('Are you sure you want to delete this trip? It cannot be undone.')) return;

            const params = new URLSearchParams();
            params.append('action', 'manage_trip');
            params.append('sub_action', subAction);
            params.append('trip_id', id);

            fetch('api_admin.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Start simple animation or reload
                    if (subAction === 'delete') {
                        const el = document.getElementById('trip-' + id);
                        el.style.opacity = '0';
                        setTimeout(() => el.remove(), 300);
                    } else {
                        // Reload to show updated status
                        location.reload(); 
                    }
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
