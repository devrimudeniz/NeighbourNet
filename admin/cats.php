<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";
require_once "../includes/icon_helper.php";

$stmt = $pdo->query("SELECT * FROM cats ORDER BY name ASC");
$cats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cats | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex transition-colors">

    <?php include "includes/sidebar.php"; ?>

    <!-- Main Content -->
    <main class="ml-0 lg:ml-72 flex-1 p-6 lg:p-12 mt-24 lg:mt-0">
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl lg:text-4xl font-black mb-2">Manage Cats</h1>
                <p class="text-slate-500 dark:text-slate-400">Add or edit cats in the Catdex.</p>
            </div>
            <a href="add_cat" class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i> Add New Cat
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach($cats as $cat): ?>
                <div id="cat-<?php echo $cat['id']; ?>" class="bg-white dark:bg-slate-800 rounded-[2rem] overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 group">
                    <div class="h-48 relative">
                        <?php if($cat['master_photo']): ?>
                            <img src="../<?php echo $cat['master_photo']; ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                <i class="fas fa-cat text-4xl text-slate-400"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-3 py-1 rounded-lg text-xs font-black uppercase shadow-sm
                            <?php echo $cat['rarity'] == 'legendary' ? 'text-amber-500' : ($cat['rarity'] == 'rare' ? 'text-blue-500' : 'text-slate-500'); ?>">
                            <?php echo $cat['rarity']; ?>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($cat['name']); ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 flex items-center gap-1">
                            <i class="fas fa-map-marker-alt text-amber-500"></i>
                            <?php echo htmlspecialchars($cat['location']); ?>
                        </p>

                        <div class="flex gap-2">
                             
                            <a href="edit_cat?id=<?php echo $cat['id']; ?>" class="flex-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 py-3 rounded-xl font-bold text-center text-sm transition-colors">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            
                            <button onclick="deleteCat(<?php echo $cat['id']; ?>)" class="flex-1 bg-red-50 dark:bg-red-900/20 hover:bg-red-500 hover:text-white text-red-500 py-3 rounded-xl font-bold transition-all text-sm">
                                <i class="fas fa-trash-alt mr-1"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <script>
        function deleteCat(id) {
            if(!confirm('Are you sure you want to delete this cat and all its collection data?')) return;

            const params = new URLSearchParams();
            params.append('action', 'delete_cat');
            params.append('cat_id', id);

            fetch('api_admin.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const el = document.getElementById('cat-' + id);
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
