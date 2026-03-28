<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
session_start();

// Fetch all cats
$cats_stmt = $pdo->query("SELECT * FROM cats ORDER BY rarity DESC, name ASC");
$all_cats = $cats_stmt->fetchAll();

// Fetch user collection if logged in
$user_collection = [];
if (isset($_SESSION['user_id'])) {
    $coll_stmt = $pdo->prepare("SELECT cat_id FROM user_cat_collection WHERE user_id = ?");
    $coll_stmt->execute([$_SESSION['user_id']]);
    $user_collection = $coll_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Calculate stats
$total_cats = count($all_cats);
$collected_count = count($user_collection);
$progress_percent = $total_cats > 0 ? ($collected_count / $total_cats) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkan Catdex | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-amber-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 max-w-4xl">
        
        <!-- Header -->
        <div class="text-center mb-8 relative">
            <h1 class="text-4xl xs:text-5xl font-black text-amber-600 dark:text-amber-500 tracking-tight mb-2 title-font">
                Kalkan <span class="text-slate-800 dark:text-white">Catdex</span> 🐾
            </h1>
            <p class="text-slate-600 dark:text-slate-300 font-medium max-w-sm mx-auto">
                <?php echo $lang == 'en' ? 'Gotta spot \'em all! Find the famous cats of Kalkan.' : 'Hepsini bulmalısın! Kalkan\'ın ünlü kedilerini keşfet.'; ?>
            </p>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border border-amber-100 dark:border-slate-700 mb-8 transform hover:scale-[1.01] transition-transform">
            <div class="flex justify-between items-end mb-2">
                <span class="text-xs font-bold uppercase text-slate-400 tracking-widest"><?php echo $lang == 'en' ? 'Collection Status' : 'Koleksiyon Durumu'; ?></span>
                <span class="text-3xl font-black text-amber-500 font-mono"><?php echo $collected_count; ?><span class="text-lg text-slate-400">/<?php echo $total_cats; ?></span></span>
            </div>
            <div class="h-4 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full transition-all duration-1000 ease-out relative" style="width: <?php echo $progress_percent; ?>%">
                    <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                </div>
            </div>
            <?php if(!isset($_SESSION['user_id'])): ?>
            <p class="mt-3 text-center text-xs text-slate-500">
                <a href="login" class="font-bold text-amber-600 hover:underline"><?php echo $lang == 'en' ? 'Login' : 'Giriş yap'; ?></a> 
                <?php echo $lang == 'en' ? 'to save your progress!' : 'ilerlemeni kaydetmek için!'; ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach($all_cats as $cat): 
                $is_collected = in_array($cat['id'], $user_collection);
                $bg_color = $is_collected ? 'bg-white dark:bg-slate-800' : 'bg-slate-200 dark:bg-slate-800/50';
                $rarity_color = match($cat['rarity']) {
                    'common' => 'text-slate-400',
                    'rare' => 'text-blue-400',
                    'legendary' => 'text-amber-500', 
                    default => 'text-slate-400'
                };
            ?>
            <a href="cat_profile?id=<?php echo $cat['id']; ?>" class="group relative aspect-[3/4] rounded-3xl overflow-hidden shadow-md hover:shadow-xl transition-all border-4 <?php echo $is_collected ? 'border-amber-400 dark:border-amber-600' : 'border-slate-300 dark:border-slate-700 grayscale opacity-80'; ?>">
                
                <!-- Rarity Badge -->
                <div class="absolute top-2 right-2 z-10">
                    <i class="fas fa-crown <?php echo $rarity_color; ?> drop-shadow-md text-xl"></i>
                </div>

                <!-- Image -->
                <?php if($cat['master_photo']): ?>
                    <img src="<?php echo $cat['master_photo']; ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 <?php echo $is_collected ? '' : 'brightness-50 blur-[1px]'; ?>">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-800 text-slate-300">
                        <i class="fas fa-cat text-6xl"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Info Overlay -->
                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/90 to-transparent p-4 pt-12">
                    <h3 class="font-black text-white text-lg leading-tight mb-0.5">
                        <?php echo $is_collected ? htmlspecialchars($cat['name']) : '???'; ?>
                    </h3>
                    <p class="text-xs text-white/70 font-medium truncate">
                        <?php echo $is_collected ? htmlspecialchars($cat['location']) : ($lang == 'en' ? 'Unknown Location' : 'Bilinmeyen Konum'); ?>
                    </p>
                </div>

                <?php if($is_collected): ?>
                <div class="absolute top-2 left-2 bg-green-500 text-white p-1.5 rounded-full shadow-lg">
                    <i class="fas fa-check text-xs"></i>
                </div>
                <?php endif; ?>

            </a>
            <?php endforeach; ?>
        </div>
        
    </main>

</body>
</html>
