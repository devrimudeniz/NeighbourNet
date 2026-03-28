<?php
require_once '../includes/db.php';
require_once '../includes/lang.php';
session_start();

// Security
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Fetch Listings
$sql = "SELECT m.*, u.username, u.full_name, u.avatar 
        FROM marketplace_listings m 
        LEFT JOIN users u ON m.user_id = u.id 
        WHERE m.status = ? ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$status_filter]);
$stmt->execute([$status_filter]);
$listings = $stmt->fetchAll();




?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Approvals | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white min-h-screen">

    <?php include "includes/sidebar.php"; ?>

    <main class="lg:ml-72 p-4 sm:p-6 lg:p-10 pt-20 lg:pt-10">
        
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black mb-2">Marketplace Listings</h1>
                <p class="text-slate-500">Manage marketplace approvals</p>
            </div>
            
            <div class="flex gap-2">
                <a href="?status=pending" class="px-4 py-2 rounded-xl font-bold transition-colors <?php echo $status_filter == 'pending' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'; ?>">
                    Pending
                </a>
                <a href="?status=active" class="px-4 py-2 rounded-xl font-bold transition-colors <?php echo $status_filter == 'active' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'; ?>">
                    Active
                </a>
                <a href="?status=rejected" class="px-4 py-2 rounded-xl font-bold transition-colors <?php echo $status_filter == 'rejected' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-100'; ?>">
                    Rejected
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach($listings as $item): ?>
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-4 mb-4">
                    <?php if($item['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="w-16 h-16 rounded-xl object-cover bg-slate-100">
                    <?php else: ?>
                    <div class="w-16 h-16 rounded-xl bg-slate-100 flex items-center justify-center">
                        <i class="fas fa-box text-slate-300"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold truncate"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="text-sm text-slate-500"><?php echo number_format($item['price']); ?> <?php echo $item['currency']; ?></p>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 text-sm text-slate-600 dark:text-slate-300 mb-4 h-24 overflow-y-auto">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </div>
                
                <div class="flex items-center gap-3 mb-6">
                    <img src="../<?php echo $item['avatar']; ?>" class="w-8 h-8 rounded-full object-cover">
                    <div class="text-xs">
                        <p class="font-bold"><?php echo htmlspecialchars($item['full_name']); ?></p>
                        <p class="text-slate-400">@<?php echo htmlspecialchars($item['username']); ?></p>
                    </div>
                </div>
                
                <?php if($status_filter == 'pending'): ?>
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="approveListing(<?php echo $item['id']; ?>)" class="bg-green-500 hover:bg-green-600 text-white py-3 rounded-xl font-bold transition-colors">
                        Approve
                    </button>
                    <button onclick="rejectListing(<?php echo $item['id']; ?>)" class="bg-red-100 text-red-500 hover:bg-red-200 py-3 rounded-xl font-bold transition-colors">
                        Reject
                    </button>
                </div>
                <?php elseif($status_filter == 'active'): ?>
                 <button onclick="rejectListing(<?php echo $item['id']; ?>)" class="w-full bg-red-100 text-red-500 hover:bg-red-200 py-3 rounded-xl font-bold transition-colors">
                    Reject / Remove
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($listings)): ?>
            <div class="col-span-full py-20 text-center text-slate-400">
                <i class="fas fa-clipboard-check text-4xl mb-4 opacity-50"></i>
                <p>No listings found in this category.</p>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <script>
        async function approveListing(id) {
            if(!confirm('Approve this listing?')) return;
            
            const formData = new FormData();
            formData.append('action', 'approve');
            formData.append('id', id);
            
            try {
                const res = await fetch('api_listings.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) location.reload();
            } catch(e) {
                alert('Error');
            }
        }
        
        async function rejectListing(id) {
            if(!confirm('Reject/Delete this listing?')) return;
            
            const formData = new FormData();
            formData.append('action', 'reject');
            formData.append('id', id);
            
            try {
                const res = await fetch('api_listings.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) location.reload();
            } catch(e) {
                alert('Error');
            }
        }
    </script>
</body>
</html>
