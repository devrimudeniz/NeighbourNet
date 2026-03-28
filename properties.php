<?php
require_once 'includes/bootstrap.php';

// Filtering Logic
$type = $_GET['type'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT p.*, pi.image_path as main_image, u.username, u.full_name, u.avatar 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1 
          JOIN users u ON p.user_id = u.id 
          WHERE p.status = 'active'";

$params = [];

if($type) {
    $query .= " AND p.type = ?";
    $params[] = $type;
}
if($min_price) {
    $query .= " AND p.price >= ?";
    $params[] = $min_price;
}
if($max_price) {
    $query .= " AND p.price <= ?";
    $params[] = $max_price;
}
if($search) {
    $query .= " AND (p.title LIKE ? OR p.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Spec Filters
$specs = ['has_sea_view', 'has_pool', 'is_dog_friendly', 'furnished', 'parking', 'air_conditioning', 'heating', 'balcony', 'garden', 'accessibility'];
foreach ($specs as $spec) {
    if (isset($_GET[$spec]) && $_GET[$spec] == 1) {
        $query .= " AND p.$spec = 1";
    }
}

$query .= " ORDER BY p.is_featured DESC, p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Check if admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $u = $u_stmt->fetch();
    $is_admin = in_array($u['badge'], ['founder', 'moderator']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['property_hub']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>
        /* body { font-family: 'Outfit', sans-serif; } - Included in header_css */
        .property-card:hover .property-image { transform: scale(1.05); }
        .noscroll::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-6 pt-24">
        <!-- Hero Search Section -->
        <div class="relative bg-white dark:bg-slate-800 rounded-[3rem] p-10 shadow-2xl border border-slate-100 dark:border-slate-800 mb-12 overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
            
            <div class="relative z-10">
                <h1 class="text-4xl font-black mb-2 text-slate-800 dark:text-white"><?php echo $t['property_hub']; ?></h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium mb-10"><?php echo $t['accommodation']; ?> & <?php echo $t['real_estate']; ?> in Kalkan</p>

                <form action="properties" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 bg-slate-50 dark:bg-slate-900/50 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700/50">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Search</label>
                        <div class="relative">
                            <?php echo heroicon('search', 'absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400'); ?>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Location or Title" class="w-full bg-white dark:bg-slate-800 pl-12 pr-4 py-4 rounded-2xl outline-none focus:ring-2 focus:ring-emerald-500 transition-all text-sm font-bold shadow-sm">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Type</label>
                        <select name="type" class="w-full bg-white dark:bg-slate-800 px-4 py-4 rounded-2xl outline-none focus:ring-2 focus:ring-emerald-500 transition-all text-sm font-bold shadow-sm appearance-none cursor-pointer">
                            <option value=""><?php echo $t['all_property_types']; ?></option>
                            <option value="short_term" <?php echo $type == 'short_term' ? 'selected' : ''; ?>><?php echo $t['short_term']; ?></option>
                            <option value="long_term" <?php echo $type == 'long_term' ? 'selected' : ''; ?>><?php echo $t['long_term']; ?></option>
                            <option value="sale" <?php echo $type == 'sale' ? 'selected' : ''; ?>><?php echo $t['sale']; ?></option>
                            <option value="room_share" <?php echo $type == 'room_share' ? 'selected' : ''; ?>><?php echo $t['room_share']; ?></option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2"><?php echo $t['price_range']; ?> (GBP)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="Min" class="w-full bg-white dark:bg-slate-800 px-4 py-4 rounded-2xl outline-none focus:ring-2 focus:ring-emerald-500 transition-all text-sm font-bold shadow-sm">
                            <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="Max" class="w-full bg-white dark:bg-slate-800 px-4 py-4 rounded-2xl outline-none focus:ring-2 focus:ring-emerald-500 transition-all text-sm font-bold shadow-sm">
                        </div>
                    </div>

                    <!-- Specs Filters -->
                    <div class="md:col-span-4 border-t border-slate-100 dark:border-slate-700 pt-6 mt-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Features & Amenities</p>
                        <div class="flex flex-wrap gap-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="has_sea_view" value="1" <?php echo isset($_GET['has_sea_view']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Sea View</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="has_pool" value="1" <?php echo isset($_GET['has_pool']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Private Pool</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="is_dog_friendly" value="1" <?php echo isset($_GET['is_dog_friendly']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Pet Friendly</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="furnished" value="1" <?php echo isset($_GET['furnished']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Furnished</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="parking" value="1" <?php echo isset($_GET['parking']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Parking</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="air_conditioning" value="1" <?php echo isset($_GET['air_conditioning']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">A/C</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="heating" value="1" <?php echo isset($_GET['heating']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Heating</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="balcony" value="1" <?php echo isset($_GET['balcony']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Balcony</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="garden" value="1" <?php echo isset($_GET['garden']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Garden</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-100/50 dark:bg-slate-700/50 px-3 py-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                <input type="checkbox" name="accessibility" value="1" <?php echo isset($_GET['accessibility']) ? 'checked' : ''; ?> class="accent-emerald-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Accessible</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-end md:col-span-4 mt-4">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-xl shadow-lg shadow-emerald-500/30 transition-all active:scale-95 flex items-center justify-center">
                            <?php echo heroicon('adjustments', 'mr-2 w-5 h-5'); ?> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Featured Toggles / Categories -->
        <div class="flex gap-4 mb-4 overflow-x-auto pb-4 noscroll">
            <a href="post_property" class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-8 py-3 rounded-xl font-black shadow-lg shadow-emerald-500/20 whitespace-nowrap hover:scale-105 transition-all text-sm flex items-center">
                <?php echo heroicon('plus', 'mr-2 w-5 h-5'); ?> <?php echo $t['post_property']; ?>
            </a>
            <?php if($is_admin): ?>
                <a href="admin/properties.php" class="bg-amber-500 text-white px-8 py-3 rounded-xl font-black shadow-lg shadow-amber-500/20 whitespace-nowrap hover:scale-105 transition-all text-sm flex items-center">
                    <?php echo heroicon('shield', 'mr-2 w-5 h-5'); ?> Review Pending
                </a>
            <?php endif; ?>
        </div>

        <!-- Property Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($properties as $property): ?>
                <div class="property-card bg-white dark:bg-slate-800 rounded-[2.5rem] overflow-hidden shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-2xl hover:border-emerald-500/30 transition-all group flex flex-col">
                    <!-- Image Section -->
                    <div class="relative h-64 overflow-hidden bg-slate-200 dark:bg-slate-800 aspect-[4/3]">
                        <img src="<?php echo $property['main_image'] ?? 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?q=60&w=800&auto=format&fit=crop'; ?>" class="property-image w-full h-full object-cover transition-transform duration-700" loading="lazy" width="800" height="600">
                        <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-black/60 to-transparent">
                            <p class="text-white font-black text-2xl">
                                <?php echo $property['price']; ?> 
                                <span class="text-sm font-bold opacity-80"><?php echo $property['currency']; ?></span>
                                <?php if($property['type'] == 'short_term' || $property['type'] == 'long_term') echo '<span class="text-xs font-bold opacity-70"> / month</span>'; ?>
                            </p>
                        </div>
                        
                        <!-- Badges -->
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            <span class="bg-white/90 dark:bg-slate-900/90 backdrop-blur px-3 py-1.5 rounded-xl text-[10px] font-black uppercase text-emerald-600 shadow-sm border border-emerald-500/20">
                                <?php echo $t[$property['type']]; ?>
                            </span>
                            <?php if($property['is_featured']): ?>
                                <span class="bg-amber-500 text-white px-3 py-1.5 rounded-xl text-[10px] font-black uppercase shadow-sm flex items-center w-max">
                                    <?php echo heroicon('star', 'mr-1 w-3 h-3'); ?> <?php echo $t['featured_listing']; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if($property['is_verified']): ?>
                            <div class="absolute top-4 right-4 w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center shadow-lg border-2 border-white dark:border-slate-800" title="<?php echo $t['verified_landlord']; ?>">
                                <?php echo heroicon('shield', 'w-6 h-6'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                     <!-- Content Section -->
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                             <?php echo heroicon('location', 'w-3 h-3 text-emerald-500'); ?>
                            <span><?php echo htmlspecialchars($property['location']); ?></span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-4 line-clamp-1"><?php echo htmlspecialchars($property['title']); ?></h3>
                        
                        <!-- Property Specs -->
                        <div class="flex items-center gap-4 mb-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <div class="flex flex-col items-center flex-1">
                                <?php echo heroicon('bed', 'text-emerald-500/50 mb-1 w-5 h-5'); ?>
                                <span class="text-xs font-black"><?php echo $property['bedrooms']; ?></span>
                            </div>
                            <div class="w-px h-6 bg-slate-200 dark:bg-slate-700"></div>
                            <div class="flex flex-col items-center flex-1">
                                <?php echo heroicon('bath', 'text-emerald-500/50 mb-1 w-5 h-5'); ?>
                                <span class="text-xs font-black"><?php echo $property['bathrooms']; ?></span>
                            </div>
                            <div class="w-px h-6 bg-slate-200 dark:bg-slate-700"></div>
                            <div class="flex flex-col items-center flex-1">
                                <?php echo heroicon('ruler', 'text-emerald-500/50 mb-1 w-5 h-5'); ?>
                                <span class="text-xs font-black"><?php echo $property['area_sqm']; ?> m²</span>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="flex flex-wrap gap-2 mb-8">
                            <?php if($property['has_sea_view']): ?>
                                <span class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-3 py-1 rounded-lg text-[10px] font-bold border border-blue-100 dark:border-blue-500/20 flex items-center">
                                    <?php echo heroicon('water', 'mr-1 w-3 h-3'); ?> Sea View
                                </span>
                            <?php endif; ?>
                            <?php if($property['has_pool']): ?>
                                <span class="bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400 px-3 py-1 rounded-lg text-[10px] font-bold border border-cyan-100 dark:border-cyan-500/20 flex items-center">
                                    <?php echo heroicon('swimmer', 'mr-1 w-3 h-3'); ?> Pool
                                </span>
                            <?php endif; ?>
                            <?php if($property['wifi_speed'] > 50): ?>
                                <span class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 px-3 py-1 rounded-lg text-[10px] font-bold border border-indigo-100 dark:border-indigo-500/20 flex items-center">
                                    <?php echo heroicon('wifi', 'mr-1 w-3 h-3'); ?> <?php echo $property['wifi_speed']; ?> Mbps
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Footer -->
                        <div class="mt-auto flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <img src="<?php echo $property['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($property['username']); ?>" class="w-8 h-8 rounded-lg object-cover" width="32" height="32" loading="lazy">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">@<?php echo htmlspecialchars($property['username']); ?></span>
                            </div>
                            <a href="property_detail?id=<?php echo $property['id']; ?>" class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 w-10 h-10 rounded-xl flex items-center justify-center hover:bg-emerald-600 dark:hover:bg-emerald-500 hover:text-white transition-all">
                                <?php echo heroicon('arrow_right', 'w-4 h-4'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($properties)): ?>
                <div class="md:col-span-3 text-center py-20 opacity-30">
                    <div class="flex justify-center mb-4"><?php echo heroicon('home', 'w-16 h-16 text-slate-400'); ?></div>
                    <p class="text-xl font-bold"><?php echo $t['no_properties']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
