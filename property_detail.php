<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT p.*, u.username, u.full_name, u.avatar, u.phone, u.bio 
                       FROM properties p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: properties");
    exit();
}

$i_stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ? ORDER BY is_main DESC");
$i_stmt->execute([$id]);
$images = $i_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>
        /* body { font-family: 'Outfit', sans-serif; } - Included in header_css */
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-6xl mx-auto px-6 pt-24">
        <!-- Breadcrumbs -->
        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest mb-8">
            <a href="properties" class="hover:text-emerald-500 transition-colors"><?php echo $t['property_hub']; ?></a>
            <?php echo heroicon('chevron_right', 'w-3 h-3 text-slate-300'); ?>
            <span><?php echo $t[$property['type']]; ?></span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Left Column: Media & Info -->
            <div class="lg:col-span-2 space-y-10">
                <!-- Gallery -->
                <div class="space-y-4">
                    <div class="aspect-video rounded-[2.5rem] overflow-hidden shadow-2xl bg-slate-200 dark:bg-slate-800">
                        <img id="mainImage" src="<?php echo $images[0]['image_path'] ?? 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?q=70&w=1200&auto=format&fit=crop'; ?>" class="w-full h-full object-cover" loading="lazy" width="1200" height="675">
                    </div>
                    <?php if(count($images) > 1): ?>
                    <div class="flex gap-4 overflow-x-auto pb-2 noscroll">
                        <?php foreach($images as $img): ?>
                            <button onclick="document.getElementById('mainImage').src='<?php echo $img['image_path']; ?>'" aria-label="View image <?php echo $img['id']; ?>" class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-transparent hover:border-emerald-500 transition-all flex-shrink-0 shadow-sm transition-transform active:scale-95">
                                <img src="<?php echo $img['image_path']; ?>" class="w-full h-full object-cover" loading="lazy" width="96" height="96" alt="Property thumbnail">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Title & Price -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-4 py-1.5 rounded-xl text-xs font-black uppercase border border-emerald-500/20">
                            <?php echo $t[$property['type']]; ?>
                        </span>
                        <?php if($property['is_verified']): ?>
                            <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-4 py-1.5 rounded-xl text-xs font-black uppercase border border-blue-500/20 flex items-center gap-1">
                                <?php echo heroicon('shield', 'w-3 h-3'); ?> Verified
                            </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-4xl font-black text-slate-800 dark:text-white mb-2"><?php echo htmlspecialchars($property['title']); ?></h1>
                    <p class="text-slate-400 font-bold flex items-center gap-2">
                        <?php echo heroicon('location', 'w-4 h-4 text-emerald-500'); ?>
                        <?php echo htmlspecialchars($property['location']); ?>
                    </p>
                </div>

                <!-- Specs Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 bg-white dark:bg-slate-800 p-8 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800">
                    <div class="text-center md:border-r border-slate-100 dark:border-slate-700/50">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-2">Price</p>
                        <p class="text-xl font-black text-emerald-600"><?php echo $property['price']; ?> <span class="text-xs uppercase"><?php echo $property['currency']; ?></span></p>
                    </div>
                    <div class="text-center md:border-r border-slate-100 dark:border-slate-700/50">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-2"><?php echo $t['bedrooms']; ?></p>
                        <p class="text-xl font-black"><?php echo $property['bedrooms']; ?></p>
                    </div>
                    <div class="text-center md:border-r border-slate-100 dark:border-slate-700/50">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-2"><?php echo $t['bathrooms']; ?></p>
                        <p class="text-xl font-black"><?php echo $property['bathrooms']; ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-2"><?php echo $t['area']; ?></p>
                        <p class="text-xl font-black"><?php echo $property['area_sqm']; ?> <span class="text-xs">m²</span></p>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-black">Description</h3>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($property['description'])); ?>
                    </p>
                </div>

                <!-- Amenities -->
                <div class="space-y-6">
                    <h3 class="text-2xl font-black">Features & Amenities</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo $property['has_sea_view'] ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <?php echo heroicon('water', 'w-5 h-5'); ?>
                            </div>
                            <span class="font-bold"><?php echo $t['sea_view']; ?></span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo $property['has_pool'] ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <?php echo heroicon('swimmer', 'w-5 h-5'); ?>
                            </div>
                            <span class="font-bold"><?php echo $t['private_pool']; ?></span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo $property['is_dog_friendly'] ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <?php echo heroicon('paw', 'w-5 h-5'); ?>
                            </div>
                            <span class="font-bold"><?php echo $t['dog_friendly']; ?></span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo $property['wifi_speed'] > 0 ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <?php echo heroicon('wifi', 'w-5 h-5'); ?>
                            </div>
                            <span class="font-bold"><?php echo $property['wifi_speed'] > 0 ? $property['wifi_speed'] . ' Mbps WiFi' : 'WiFi Not Listed'; ?></span>
                        </div>

                        <!-- New Specs -->
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo (isset($property['furnished']) && $property['furnished']) ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <?php echo heroicon('home', 'w-5 h-5'); ?>
                            </div>
                            <span class="font-bold">Furnished</span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo (isset($property['parking']) && $property['parking']) ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <?php echo heroicon('transportation', 'w-5 h-5'); ?>
                            </div>
                            <span class="font-bold">Parking</span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo (isset($property['air_conditioning']) && $property['air_conditioning']) ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <i class="fas fa-snowflake"></i>
                            </div>
                            <span class="font-bold">Air Conditioning</span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo (isset($property['heating']) && $property['heating']) ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <i class="fas fa-fire"></i>
                            </div>
                            <span class="font-bold">Heating</span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo (isset($property['balcony']) && $property['balcony']) ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <i class="fas fa-columns"></i>
                            </div>
                            <span class="font-bold">Balcony</span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo (isset($property['garden']) && $property['garden']) ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <i class="fas fa-tree"></i>
                            </div>
                            <span class="font-bold">Garden</span>
                        </div>
                        <div class="flex items-center gap-4 p-4 rounded-2xl <?php echo (isset($property['accessibility']) && $property['accessibility']) ? 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 opacity-30'; ?>">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <i class="fas fa-wheelchair"></i>
                            </div>
                            <span class="font-bold">Accessibility</span>
                        </div>
                        
                        <?php if(!empty($property['year_built'])): ?>
                        <div class="flex items-center gap-4 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <span class="font-bold">Year Built: <?php echo $property['year_built']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if($property['video_url']): ?>
                <!-- Virtual Tour -->
                <div class="space-y-6">
                    <h3 class="text-2xl font-black"><?php echo $t['virtual_tour']; ?></h3>
                    <div class="aspect-video rounded-[2.5rem] overflow-hidden bg-slate-900 shadow-xl border-4 border-white dark:border-slate-800">
                        <?php 
                        $video_id = '';
                        if(strpos($property['video_url'], 'youtube.com') !== false || strpos($property['video_url'], 'youtu.be') !== false) {
                            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $property['video_url'], $match);
                            $video_id = $match[1] ?? '';
                        }
                        ?>
                        <?php if($video_id): ?>
                            <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center text-white p-10 text-center">
                                <i class="fas fa-video-slash text-6xl mb-4 opacity-30"></i>
                                <p class="text-xl font-bold mb-4">Click to view virtual tour</p>
                                <a href="<?php echo htmlspecialchars($property['video_url']); ?>" target="_blank" class="px-8 py-3 bg-white text-slate-900 rounded-2xl font-black">View External Link</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Landlord card -->
            <div class="space-y-8">
                <div class="sticky top-32 space-y-6">
                    <!-- Landlord Card -->
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                        <div class="flex flex-col items-center text-center mb-8">
                            <div class="relative mb-4">
                                <img src="<?php echo $property['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($property['username']); ?>" class="w-24 h-24 rounded-[2rem] object-cover shadow-xl ring-4 ring-slate-50 dark:ring-slate-900" loading="lazy" width="96" height="96">
                                <?php if($property['is_verified']): ?>
                                    <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-500 text-white rounded-xl flex items-center justify-center shadow-lg border-2 border-white dark:border-slate-800">
                                        <i class="fas fa-check text-sm"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-xl font-black"><?php echo htmlspecialchars($property['full_name'] ?? $property['username']); ?></h4>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Landlord</p>
                        </div>

                        <?php if($property['bio']): ?>
                            <p class="text-sm text-slate-500 text-center mb-8 italic">"<?php echo htmlspecialchars($property['bio']); ?>"</p>
                        <?php endif; ?>

                        <div class="space-y-3">
                            <a href="messages?chat_with=<?php echo $property['user_id']; ?>" class="block w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[2rem] font-black text-center shadow-lg shadow-emerald-500/20 transition-all active:scale-95">
                                <i class="far fa-comment-dots mr-2"></i> Send Message
                            </a>
                            <?php if($property['phone']): ?>
                            <a href="tel:<?php echo htmlspecialchars($property['phone']); ?>" class="block w-full py-5 bg-slate-50 dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-white rounded-[2rem] font-black text-center border border-slate-100 dark:border-slate-700 transition-all active:scale-95">
                                <i class="fas fa-phone-alt mr-2 text-emerald-500"></i> Call Landlord
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-700/50 flex items-center justify-center gap-6 opacity-30">
                            <i class="fab fa-facebook-messenger text-2xl"></i>
                            <i class="fab fa-whatsapp text-2xl"></i>
                            <i class="fas fa-envelope text-2xl"></i>
                        </div>
                    </div>

                    <!-- Side Tip -->
                    <div class="bg-gradient-to-br from-indigo-500 to-violet-600 rounded-[2.5rem] p-8 text-white shadow-xl relative overflow-hidden group">
                        <div class="relative z-10">
                            <i class="fas fa-shield-alt text-3xl mb-4 text-white/50"></i>
                            <h4 class="text-lg font-black mb-2">Safe Booking Tips</h4>
                            <ul class="text-xs space-y-2 opacity-80 font-medium">
                                <li>• Always message through the app</li>
                                <li>• Never pay via wire transfer</li>
                                <li>• View property before long-term deals</li>
                            </ul>
                        </div>
                        <div class="absolute -right-10 -bottom-10 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fas fa-home text-[10rem]"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
