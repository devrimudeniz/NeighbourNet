<?php
require_once 'includes/db.php';

// Get business ID from URL
$business_id = 0;
if (isset($_GET['id'])) {
    $business_id = (int)$_GET['id'];
} else {
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

// Detect language
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
    SELECT bmi.*, bmc.name as category_name, bmc.name_en as category_name_en
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

// Get theme settings
$theme = $business['menu_theme'] ?? 'default';
$primary_color = $business['menu_primary_color'] ?? '#0055FF';

// Theme configurations
$themes = [
    'default' => [
        'bg' => 'from-slate-50 to-slate-100',
        'card' => 'bg-white',
        'header' => 'bg-white/90',
        'category' => 'from-violet-500 to-purple-600',
        'text' => 'text-slate-800',
        'accent' => 'text-violet-500'
    ],
    'elegant' => [
        'bg' => 'from-amber-50 to-orange-50',
        'card' => 'bg-white',
        'header' => 'bg-white/95',
        'category' => 'from-amber-600 to-orange-600',
        'text' => 'text-slate-900',
        'accent' => 'text-amber-600'
    ],
    'modern' => [
        'bg' => 'from-blue-50 to-cyan-50',
        'card' => 'bg-white',
        'header' => 'bg-white/90',
        'category' => 'from-blue-600 to-cyan-600',
        'text' => 'text-slate-800',
        'accent' => 'text-blue-600'
    ],
    'minimal' => [
        'bg' => 'from-gray-50 to-gray-100',
        'card' => 'bg-white',
        'header' => 'bg-white/95',
        'category' => 'from-gray-700 to-gray-900',
        'text' => 'text-gray-900',
        'accent' => 'text-gray-700'
    ]
];

$current_theme = $themes[$theme] ?? $themes['default'];
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo $primary_color; ?>;
        }
        body { 
            font-family: 'Inter', sans-serif;
        }
        .font-display {
            font-family: 'Playfair Display', serif;
        }
        .primary-color { color: var(--primary-color); }
        .bg-primary { background-color: var(--primary-color); }
        .border-primary { border-color: var(--primary-color); }
        .hover-primary:hover { background-color: var(--primary-color); }
        
        .menu-item-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .menu-item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        
        .category-nav {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .category-nav::-webkit-scrollbar {
            display: none;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient-to-br <?php echo $current_theme['bg']; ?> min-h-screen">

    <!-- Header -->
    <header class="sticky-header <?php echo $current_theme['header']; ?> border-b border-slate-200 shadow-lg">
        <div class="container mx-auto px-4 py-4 max-w-5xl">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <?php if (!empty($business['menu_logo'])): ?>
                    <img src="/<?php echo htmlspecialchars($business['menu_logo']); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>" class="h-16 mb-2 rounded-xl shadow-md">
                    <?php endif; ?>
                    <h1 class="text-3xl font-display font-black <?php echo $current_theme['text']; ?> mb-1">
                        <?php echo htmlspecialchars($business['name']); ?>
                    </h1>
                    <?php if ($business['address']): ?>
                    <p class="text-sm text-slate-600 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt text-xs primary-color"></i>
                        <?php echo htmlspecialchars($business['address']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <button onclick="toggleLanguage()" class="px-4 py-2.5 rounded-xl glass-effect hover:bg-white text-slate-700 font-bold text-sm transition-all shadow-md hover:shadow-lg">
                    <?php echo $lang == 'en' ? '🇹🇷 TR' : '🇬🇧 EN'; ?>
                </button>
            </div>
            
            <!-- Social Media Links -->
            <?php if (!empty($business['instagram_url']) || !empty($business['facebook_url']) || !empty($business['tripadvisor_url']) || !empty($business['google_maps_url']) || !empty($business['phone']) || !empty($business['website'])): ?>
            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-200">
                <?php if (!empty($business['instagram_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['instagram_url']); ?>" target="_blank" class="flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 text-white hover:scale-110 transition-transform shadow-md">
                    <i class="fab fa-instagram text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['facebook_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['facebook_url']); ?>" target="_blank" class="flex items-center justify-center w-11 h-11 rounded-xl bg-blue-600 text-white hover:scale-110 transition-transform shadow-md">
                    <i class="fab fa-facebook-f text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['tripadvisor_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['tripadvisor_url']); ?>" target="_blank" class="flex items-center justify-center w-11 h-11 rounded-xl bg-green-600 text-white hover:scale-110 transition-transform shadow-md">
                    <i class="fab fa-tripadvisor text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['google_maps_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['google_maps_url']); ?>" target="_blank" class="flex items-center justify-center w-11 h-11 rounded-xl bg-red-500 text-white hover:scale-110 transition-transform shadow-md">
                    <i class="fas fa-map-marker-alt text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" class="flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-600 text-white hover:scale-110 transition-transform shadow-md">
                    <i class="fas fa-phone text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['website'])): ?>
                <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" class="flex items-center justify-center w-11 h-11 rounded-xl bg-slate-700 text-white hover:scale-110 transition-transform shadow-md">
                    <i class="fas fa-globe text-lg"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 max-w-5xl">
        
        <?php if (empty($categories) && empty($all_items)): ?>
        <!-- Empty State -->
        <div class="<?php echo $current_theme['card']; ?> rounded-3xl shadow-2xl p-16 text-center animate-fade-in-up">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br <?php echo $current_theme['category']; ?> flex items-center justify-center">
                <i class="fas fa-utensils text-white text-4xl"></i>
            </div>
            <h2 class="text-3xl font-display font-bold <?php echo $current_theme['text']; ?> mb-3">
                <?php echo $lang == 'en' ? 'Menu Coming Soon' : 'Menü Yakında'; ?>
            </h2>
            <p class="text-slate-500 text-lg">
                <?php echo $lang == 'en' ? 'Our digital menu is being prepared' : 'Dijital menümüz hazırlanıyor'; ?>
            </p>
        </div>
        <?php else: ?>

        <!-- Categories Navigation -->
        <?php if (count($categories) > 1): ?>
        <div class="<?php echo $current_theme['card']; ?> rounded-2xl shadow-lg p-4 mb-8 animate-fade-in-up">
            <div class="flex gap-2 overflow-x-auto category-nav pb-2">
                <?php foreach ($categories as $category): 
                    $cat_name = $lang == 'en' && !empty($category['name_en']) ? $category['name_en'] : $category['name'];
                ?>
                <a href="#category-<?php echo $category['id']; ?>" class="px-6 py-3 rounded-xl glass-effect hover-primary hover:text-white <?php echo $current_theme['text']; ?> font-bold text-sm transition-all whitespace-nowrap shadow-sm hover:shadow-md">
                    <?php echo htmlspecialchars($cat_name); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Menu Categories -->
        <div class="space-y-12">
            <?php foreach ($categories as $category): 
                $cat_name = $lang == 'en' && !empty($category['name_en']) ? $category['name_en'] : $category['name'];
                $cat_desc = $lang == 'en' && !empty($category['description_en']) ? $category['description_en'] : $category['description'];
            ?>
            <?php if (isset($items_by_category[$category['id']]) && !empty($items_by_category[$category['id']])): ?>
            <section id="category-<?php echo $category['id']; ?>" class="scroll-mt-32 animate-fade-in-up">
                <div class="bg-gradient-to-r <?php echo $current_theme['category']; ?> rounded-3xl p-8 mb-6 shadow-2xl">
                    <h2 class="text-3xl font-display font-black text-white mb-2">
                        <?php echo htmlspecialchars($cat_name); ?>
                    </h2>
                    <?php if ($cat_desc): ?>
                    <p class="text-white/90 text-sm">
                        <?php echo htmlspecialchars($cat_desc); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($items_by_category[$category['id']] as $item): 
                        $item_name = $lang == 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
                        $item_desc = $lang == 'en' && !empty($item['description_en']) ? $item['description_en'] : $item['description'];
                    ?>
                    <div class="menu-item-card <?php echo $current_theme['card']; ?> rounded-2xl shadow-lg overflow-hidden">
                        <?php if ($item['image_url']): ?>
                        <div class="aspect-video overflow-hidden">
                            <img src="/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item_name); ?>" class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <h3 class="text-xl font-bold <?php echo $current_theme['text']; ?> flex-1">
                                    <?php echo htmlspecialchars($item_name); ?>
                                </h3>
                                <?php if ($item['price']): ?>
                                <div class="text-2xl font-black primary-color whitespace-nowrap">
                                    ₺<?php echo number_format($item['price'], 2); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($item_desc): ?>
                            <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                                <?php echo htmlspecialchars($item_desc); ?>
                            </p>
                            <?php endif; ?>

                            <div class="flex items-center gap-2 flex-wrap">
                                <?php if ($item['is_vegetarian']): ?>
                                <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-green-200">
                                    <i class="fas fa-leaf"></i> <?php echo $lang == 'en' ? 'Vegetarian' : 'Vejetaryen'; ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($item['is_vegan']): ?>
                                <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-emerald-200">
                                    <i class="fas fa-seedling"></i> <?php echo $lang == 'en' ? 'Vegan' : 'Vegan'; ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($item['is_spicy']): ?>
                                <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-200">
                                    <i class="fas fa-pepper-hot"></i> <?php echo $lang == 'en' ? 'Spicy' : 'Acılı'; ?>
                                </span>
                                <?php endif; ?>
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
        <div class="mt-16 <?php echo $current_theme['card']; ?> rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up">
            <div class="p-8 border-b border-slate-200">
                <h3 class="text-2xl font-display font-black <?php echo $current_theme['text']; ?> flex items-center gap-3">
                    <i class="fas fa-map-marker-alt primary-color"></i>
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
        <div class="mt-8 <?php echo $current_theme['card']; ?> rounded-3xl shadow-2xl p-8 animate-fade-in-up">
            <h3 class="text-2xl font-display font-black <?php echo $current_theme['text']; ?> mb-6 flex items-center gap-3">
                <i class="fas fa-address-card primary-color"></i>
                <?php echo $lang == 'en' ? 'Contact Information' : 'İletişim Bilgileri'; ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($business['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" class="flex items-center gap-4 p-4 rounded-2xl glass-effect hover:bg-white transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-phone text-emerald-600 text-lg"></i>
                    </div>
                    <span class="font-bold <?php echo $current_theme['text']; ?>"><?php echo htmlspecialchars($business['phone']); ?></span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($business['email']); ?>" class="flex items-center gap-4 p-4 rounded-2xl glass-effect hover:bg-white transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-envelope text-blue-600 text-lg"></i>
                    </div>
                    <span class="font-bold <?php echo $current_theme['text']; ?> text-sm"><?php echo htmlspecialchars($business['email']); ?></span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['website'])): ?>
                <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" class="flex items-center gap-4 p-4 rounded-2xl glass-effect hover:bg-white transition-all group md:col-span-2">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-globe text-slate-600 text-lg"></i>
                    </div>
                    <span class="font-bold <?php echo $current_theme['text']; ?>"><?php echo htmlspecialchars($business['website']); ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-16 text-center pb-8 animate-fade-in-up">
            <div class="inline-flex items-center gap-2 text-sm text-slate-500 mb-2">
                <span><?php echo $lang == 'en' ? 'Powered by' : 'Tarafından sunulmaktadır'; ?></span>
                <a href="https://kalkansocial.com" target="_blank" class="font-bold primary-color hover:underline">
                    Kalkan Social
                </a>
            </div>
            <div class="text-xs text-slate-400">
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
    
    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.animate-fade-in-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(el);
    });
    </script>

</body>
</html>
