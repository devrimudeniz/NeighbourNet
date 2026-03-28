<?php
require_once 'includes/db.php';
require_once 'includes/cdn_helper.php';

// Get business ID from URL
$business_id = 0;

// Check if it's a direct URL like /menu/123
if (isset($_GET['id'])) {
    $business_id = (int)$_GET['id'];
}
// Or from path like /menu/123
else {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('/\/menu\/(\d+)/', $path, $matches)) {
        $business_id = (int)$matches[1];
    }
}

if ($business_id === 0) {
    header('HTTP/1.0 404 Not Found');
    echo 'Menu not found';
    exit();
}

// Get business details
$stmt = $pdo->prepare("SELECT * FROM business_listings WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

if (!$business) {
    header('HTTP/1.0 404 Not Found');
    echo 'Business not found';
    exit();
}

// Detect language from browser or default to Turkish
$lang = 'tr';
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'en' ? 'en' : 'tr';
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    $lang = $browser_lang === 'en' ? 'en' : 'tr';
}

// Get menu categories
$categories_stmt = $pdo->prepare("
    SELECT * FROM business_menu_categories
    WHERE business_id = ?
    ORDER BY sort_order ASC, id ASC
");
$categories_stmt->execute([$business_id]);
$categories = $categories_stmt->fetchAll();

// Get all menu items
$items_stmt = $pdo->prepare("
    SELECT bmi.*, bmc.name as category_name
    FROM business_menu_items bmi
    LEFT JOIN business_menu_categories bmc ON bmi.category_id = bmc.id
    WHERE bmi.business_id = ? AND bmi.is_available = 1
    ORDER BY bmc.sort_order ASC, bmi.sort_order ASC, bmi.id ASC
");
$items_stmt->execute([$business_id]);
$all_items = $items_stmt->fetchAll();

// Group items by category
$items_by_category = [];
foreach ($all_items as $item) {
    $cat_id = $item['category_id'] ?? 0;
    if (!isset($items_by_category[$cat_id])) {
        $items_by_category[$cat_id] = [];
    }
    $items_by_category[$cat_id][] = $item;
}

// Track view
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO business_menu_views (business_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$business_id, $ip, $user_agent]);
} catch (Exception $e) {
    // Silently fail
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business['name']); ?> - <?php echo $lang == 'en' ? 'Digital Menu' : 'Dijital Menü'; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($business['description'] ?? ''); ?>">
    <link rel="icon" href="/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .menu-item-enter {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

    <!-- Header -->
    <header class="sticky-header bg-white/90 border-b border-slate-200 shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <?php if (!empty($business['menu_logo'])): ?>
                    <img src="/<?php echo htmlspecialchars($business['menu_logo']); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>" class="h-12 mb-2 rounded-lg">
                    <?php endif; ?>
                    <h1 class="text-2xl font-black text-slate-800">
                        <?php echo htmlspecialchars($business['name']); ?>
                    </h1>
                    <?php if ($business['address']): ?>
                    <p class="text-sm text-slate-500 flex items-center gap-1 mt-1">
                        <i class="fas fa-map-marker-alt text-xs"></i>
                        <?php echo htmlspecialchars($business['address']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleLanguage()" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition-colors">
                        <?php echo $lang == 'en' ? '🇹🇷 TR' : '🇬🇧 EN'; ?>
                    </button>
                </div>
            </div>
            
            <!-- Social Media Links -->
            <?php if (!empty($business['instagram_url']) || !empty($business['facebook_url']) || !empty($business['tripadvisor_url']) || !empty($business['google_maps_url'])): ?>
            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-200">
                <?php if (!empty($business['instagram_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['instagram_url']); ?>" target="_blank" class="flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 text-white hover:scale-110 transition-transform">
                    <i class="fab fa-instagram"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['facebook_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['facebook_url']); ?>" target="_blank" class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white hover:scale-110 transition-transform">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['tripadvisor_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['tripadvisor_url']); ?>" target="_blank" class="flex items-center justify-center w-10 h-10 rounded-full bg-green-600 text-white hover:scale-110 transition-transform">
                    <i class="fab fa-tripadvisor"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['google_maps_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['google_maps_url']); ?>" target="_blank" class="flex items-center justify-center w-10 h-10 rounded-full bg-red-500 text-white hover:scale-110 transition-transform">
                    <i class="fas fa-map-marker-alt"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-600 text-white hover:scale-110 transition-transform">
                    <i class="fas fa-phone"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['website'])): ?>
                <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" class="flex items-center justify-center w-10 h-10 rounded-full bg-slate-700 text-white hover:scale-110 transition-transform">
                    <i class="fas fa-globe"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6 max-w-4xl">
        
        <?php if (empty($categories) && empty($all_items)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-3xl shadow-lg p-12 text-center">
            <i class="fas fa-utensils text-slate-300 text-5xl mb-4"></i>
            <h2 class="text-2xl font-bold text-slate-800 mb-2">
                <?php echo $lang == 'en' ? 'Menu Coming Soon' : 'Menü Yakında'; ?>
            </h2>
            <p class="text-slate-500">
                <?php echo $lang == 'en' ? 'Our digital menu is being prepared' : 'Dijital menümüz hazırlanıyor'; ?>
            </p>
        </div>
        <?php else: ?>

        <!-- Categories Navigation -->
        <?php if (count($categories) > 1): ?>
        <div class="bg-white rounded-2xl shadow-md p-4 mb-6 overflow-x-auto">
            <div class="flex gap-2 min-w-max">
                <?php foreach ($categories as $category): ?>
                <a href="#category-<?php echo $category['id']; ?>" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-violet-500 hover:text-white text-slate-700 font-bold text-sm transition-all whitespace-nowrap">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Menu Categories -->
        <div class="space-y-8">
            <?php foreach ($categories as $category): ?>
            <?php if (isset($items_by_category[$category['id']]) && !empty($items_by_category[$category['id']])): ?>
            <section id="category-<?php echo $category['id']; ?>" class="scroll-mt-32">
                <div class="bg-gradient-to-r from-violet-500 to-purple-600 rounded-2xl p-6 mb-4 shadow-lg">
                    <h2 class="text-2xl font-black text-white">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h2>
                    <?php if ($category['description']): ?>
                    <p class="text-white/80 text-sm mt-1">
                        <?php echo htmlspecialchars($category['description']); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="space-y-4">
                    <?php foreach ($items_by_category[$category['id']] as $item): ?>
                    <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all overflow-hidden menu-item-enter">
                        <div class="flex gap-4 p-4">
                            <?php if ($item['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-24 h-24 rounded-xl object-cover flex-shrink-0">
                            <?php endif; ?>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <h3 class="font-bold text-slate-800 text-lg">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h3>
                                    <?php if ($item['price']): ?>
                                    <div class="text-xl font-black text-violet-500 flex-shrink-0">
                                        ₺<?php echo number_format($item['price'], 2); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($item['description']): ?>
                                <p class="text-sm text-slate-600 mb-3">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </p>
                                <?php endif; ?>

                                <div class="flex items-center gap-2 flex-wrap">
                                    <?php if ($item['is_vegetarian']): ?>
                                    <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-2 py-1 rounded-lg text-xs font-bold">
                                        <i class="fas fa-leaf"></i> <?php echo $lang == 'en' ? 'Vegetarian' : 'Vejetaryen'; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($item['is_vegan']): ?>
                                    <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 px-2 py-1 rounded-lg text-xs font-bold">
                                        <i class="fas fa-seedling"></i> <?php echo $lang == 'en' ? 'Vegan' : 'Vegan'; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($item['is_spicy']): ?>
                                    <span class="inline-flex items-center gap-1 bg-red-100 text-red-700 px-2 py-1 rounded-lg text-xs font-bold">
                                        <i class="fas fa-pepper-hot"></i> <?php echo $lang == 'en' ? 'Spicy' : 'Acılı'; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

        <!-- Location Map -->
        <?php if (!empty($business['latitude']) && !empty($business['longitude'])): ?>
        <div class="mt-12 bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-red-500"></i>
                    <?php echo $lang == 'en' ? 'Location' : 'Konum'; ?>
                </h3>
            </div>
            <div class="aspect-video">
                <iframe 
                    src="https://www.google.com/maps?q=<?php echo $business['latitude']; ?>,<?php echo $business['longitude']; ?>&output=embed" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact Info -->
        <?php if (!empty($business['phone']) || !empty($business['email']) || !empty($business['website'])): ?>
        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-xl font-black text-slate-800 mb-4 flex items-center gap-2">
                <i class="fas fa-address-card text-blue-500"></i>
                <?php echo $lang == 'en' ? 'Contact Information' : 'İletişim Bilgileri'; ?>
            </h3>
            <div class="space-y-3">
                <?php if (!empty($business['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" class="flex items-center gap-3 text-slate-700 hover:text-blue-600 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-phone text-emerald-600"></i>
                    </div>
                    <span class="font-bold"><?php echo htmlspecialchars($business['phone']); ?></span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($business['email']); ?>" class="flex items-center gap-3 text-slate-700 hover:text-blue-600 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-envelope text-blue-600"></i>
                    </div>
                    <span class="font-bold"><?php echo htmlspecialchars($business['email']); ?></span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['website'])): ?>
                <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" class="flex items-center gap-3 text-slate-700 hover:text-blue-600 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                        <i class="fas fa-globe text-slate-600"></i>
                    </div>
                    <span class="font-bold"><?php echo htmlspecialchars($business['website']); ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-12 text-center pb-8">
            <div class="inline-flex items-center gap-2 text-sm text-slate-500">
                <span><?php echo $lang == 'en' ? 'Powered by' : 'Tarafından sunulmaktadır'; ?></span>
                <a href="https://kalkansocial.com" target="_blank" class="font-bold text-violet-500 hover:text-violet-600">
                    Kalkan Social
                </a>
            </div>
            <div class="mt-2 text-xs text-slate-400">
                <?php echo $lang == 'en' ? 'Digital Menu System' : 'Dijital Menü Sistemi'; ?>
            </div>
        </div>

    </main>

    <script>
    function toggleLanguage() {
        const currentLang = '<?php echo $lang; ?>';
        const newLang = currentLang === 'en' ? 'tr' : 'en';
        const url = new URL(window.location.href);
        url.searchParams.set('lang', newLang);
        window.location.href = url.toString();
    }

    // Smooth scroll for category links
    document.querySelectorAll('a[href^="#category-"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    </script>

</body>
</html>
