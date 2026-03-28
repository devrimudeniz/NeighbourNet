<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Check if user is a verified captain
$u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$u_stmt->execute([$_SESSION['user_id']]);
$user_badge = $u_stmt->fetchColumn();

if ($user_badge !== 'captain') {
    header("Location: request_verification");
    exit();
}

// Fetch captain's trips
$stmt = $pdo->prepare("
    SELECT bt.*, 
           (SELECT COUNT(*) FROM trip_bookings WHERE trip_id = bt.id) as booking_count,
           (SELECT AVG(rating) FROM trip_reviews WHERE trip_id = bt.id) as avg_rating
    FROM boat_trips bt
    WHERE bt.captain_id = ?
    ORDER BY bt.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$trips = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['my_trips']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-6 pt-24">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-black mb-2"><?php echo $t['my_trips']; ?></h1>
                <p class="text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Manage your boat trip listings' : 'Tekne turu ilanlarınızı yönetin'; ?></p>
            </div>
            <a href="add_boat_trip" class="bg-cyan-600 hover:bg-cyan-700 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-cyan-500/20 transition-all">
                <i class="fas fa-plus mr-2"></i> <?php echo $lang == 'en' ? 'Add New Trip' : 'Yeni Tur Ekle'; ?>
            </a>
        </div>

        <!-- Trips Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($trips as $trip): ?>
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-xl transition-all">
                    <!-- Cover Photo -->
                    <div class="relative h-48 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($trip['cover_photo']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($trip['title']); ?>">
                        
                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4">
                            <?php if ($trip['status'] === 'approved'): ?>
                                <span class="px-3 py-1 bg-emerald-500 text-white rounded-full text-xs font-black uppercase shadow-lg">
                                    <i class="fas fa-check"></i> <?php echo $lang == 'en' ? 'Active' : 'Aktif'; ?>
                                </span>
                            <?php elseif ($trip['status'] === 'pending'): ?>
                                <span class="px-3 py-1 bg-amber-500 text-white rounded-full text-xs font-black uppercase shadow-lg animate-pulse">
                                    <i class="fas fa-clock"></i> <?php echo $lang == 'en' ? 'Pending' : 'Beklemede'; ?>
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-red-500 text-white rounded-full text-xs font-black uppercase shadow-lg">
                                    <i class="fas fa-times"></i> <?php echo $lang == 'en' ? 'Rejected' : 'Reddedildi'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <h3 class="text-xl font-black mb-2 line-clamp-1"><?php echo htmlspecialchars($trip['title']); ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2"><?php echo htmlspecialchars($trip['description']); ?></p>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl text-center">
                                <p class="text-xs text-slate-400 uppercase font-bold"><?php echo $lang == 'en' ? 'Price' : 'Fiyat'; ?></p>
                                <p class="text-sm font-black"><?php echo $trip['price_per_person']; ?> <?php echo $trip['currency']; ?></p>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl text-center">
                                <p class="text-xs text-slate-400 uppercase font-bold"><?php echo $lang == 'en' ? 'Bookings' : 'Rezervasyon'; ?></p>
                                <p class="text-sm font-black"><?php echo $trip['booking_count']; ?></p>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xl text-center">
                                <p class="text-xs text-slate-400 uppercase font-bold"><?php echo $lang == 'en' ? 'Rating' : 'Puan'; ?></p>
                                <p class="text-sm font-black"><?php echo $trip['avg_rating'] ? number_format($trip['avg_rating'], 1) : '-'; ?></p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="trip_detail?id=<?php echo $trip['id']; ?>" class="flex-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 px-4 py-3 rounded-xl font-bold text-sm text-center hover:bg-cyan-200 dark:hover:bg-cyan-900/50 transition-colors">
                                <i class="fas fa-eye mr-1"></i> <?php echo $lang == 'en' ? 'View' : 'Görüntüle'; ?>
                            </a>
                            <button class="flex-1 bg-slate-100 dark:bg-slate-700 px-4 py-3 rounded-xl font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                <i class="fas fa-edit mr-1"></i> <?php echo $lang == 'en' ? 'Edit' : 'Düzenle'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($trips)): ?>
                <div class="col-span-full text-center py-20 opacity-30">
                    <i class="fas fa-ship text-6xl mb-4"></i>
                    <p class="text-xl font-bold mb-2"><?php echo $lang == 'en' ? 'No trips yet' : 'Henüz tur yok'; ?></p>
                    <p class="text-slate-500"><?php echo $lang == 'en' ? 'Create your first boat trip listing' : 'İlk tekne turu ilanınızı oluşturun'; ?></p>
                    <a href="add_boat_trip" class="inline-block mt-6 bg-cyan-600 hover:bg-cyan-700 text-white px-8 py-4 rounded-2xl font-black shadow-lg transition-all">
                        <i class="fas fa-plus mr-2"></i> <?php echo $lang == 'en' ? 'Add Your First Trip' : 'İlk Turunuzu Ekleyin'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
