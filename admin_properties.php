<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Check if moderator/founder
$u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$u_stmt->execute([$_SESSION['user_id']]);
$user = $u_stmt->fetch();

if (!in_array($user['badge'], ['founder', 'moderator'])) {
    header("Location: properties");
    exit();
}

$stmt = $pdo->prepare("SELECT p.*, pi.image_path as main_image, u.username, u.full_name 
                       FROM properties p 
                       LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.status = 'pending'
                       ORDER BY p.created_at DESC");
$stmt->execute();
$pending_properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Approvals | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-5xl mx-auto px-6 pt-24">
        <div class="mb-10">
            <h1 class="text-3xl font-black mb-2">Pending Property Approvals</h1>
            <p class="text-slate-500 dark:text-slate-400">Review and approve new listings before they go public.</p>
        </div>

        <div class="space-y-6">
            <?php foreach ($pending_properties as $prop): ?>
            <div id="prop-<?php echo $prop['id']; ?>" class="bg-white dark:bg-slate-800 rounded-[2rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col md:flex-row gap-6">
                <div class="w-full md:w-48 h-32 rounded-2xl overflow-hidden shadow-sm flex-shrink-0">
                    <img src="<?php echo $prop['main_image'] ?? 'https://via.placeholder.com/400x300'; ?>" class="w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($prop['title']); ?></h3>
                        <span class="text-lg font-black text-emerald-600"><?php echo $prop['price']; ?> <?php echo $prop['currency']; ?></span>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold text-slate-400 mb-4">
                        <span class="flex items-center gap-1"><?php echo heroicon('user', 'w-3 h-3'); ?> @<?php echo htmlspecialchars($prop['username']); ?></span>
                        <span class="flex items-center gap-1"><?php echo heroicon('location', 'w-3 h-3'); ?> <?php echo htmlspecialchars($prop['location']); ?></span>
                        <span class="flex items-center gap-1"><?php echo heroicon('calendar', 'w-3 h-3'); ?> <?php echo date('d M Y', strtotime($prop['created_at'])); ?></span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-4"><?php echo htmlspecialchars($prop['description']); ?></p>
                    
                    <div class="flex gap-3">
                        <button onclick="approveProperty(<?php echo $prop['id']; ?>)" class="bg-emerald-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-emerald-600 transition-colors">Approve</button>
                        <a href="property_detail?id=<?php echo $prop['id']; ?>" target="_blank" class="bg-slate-100 dark:bg-slate-700 px-6 py-2 rounded-xl font-bold hover:bg-slate-200 transition-colors">Review Detail</a>
                        <button onclick="deleteProperty(<?php echo $prop['id']; ?>)" class="text-red-500 font-bold hover:underline ml-auto">Delete</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($pending_properties)): ?>
                <div class="text-center py-20 opacity-30">
                    <div class="flex justify-center mb-4"><?php echo heroicon('shield', 'w-16 h-16 text-slate-400'); ?></div>
                    <p class="text-xl font-bold">No pending properties to review.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function approveProperty(id) {
            const params = new URLSearchParams();
            params.append('action', 'approve');
            params.append('property_id', id);

            fetch('api/properties.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('prop-' + id).remove();
                } else {
                    alert(data.message);
                }
            });
        }

        function deleteProperty(id) {
            if (!confirm('Are you sure you want to delete this listing?')) return;

            const params = new URLSearchParams();
            params.append('action', 'delete');
            params.append('property_id', id);

            fetch('api/properties.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('prop-' + id).remove();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
