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
    $lang = in_array($_GET['lang'], ['tr', 'en']) ? $_GET['lang'] : 'tr';
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    $lang = $browser_lang === 'en' ? 'en' : 'tr';
}

// Get active category
$active_cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';

// Get menu categories
$categories_stmt = $pdo->prepare("
    SELECT * FROM business_menu_categories
    WHERE business_id = ?
    ORDER BY sort_order ASC, id ASC
");
$categories_stmt->execute([$business_id]);
$categories = $categories_stmt->fetchAll();

// Get menu items
$items_sql = "SELECT bmi.*, bmc.name as category_name, bmc.name_en as category_name_en
    FROM business_menu_items bmi
    LEFT JOIN business_menu_categories bmc ON bmi.category_id = bmc.id
    WHERE bmi.business_id = ? AND bmi.is_available = 1";

if ($active_cat !== 'all') {
    $items_sql .= " AND bmc.id = ?";
}
$items_sql .= " ORDER BY bmc.sort_order ASC, bmi.sort_order ASC, bmi.id ASC";

$items_stmt = $pdo->prepare($items_sql);
if ($active_cat !== 'all') {
    $items_stmt->execute([$business_id, (int)$active_cat]);
} else {
    $items_stmt->execute([$business_id]);
}
$menu_items = $items_stmt->fetchAll();

// Track view
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO business_menu_views (business_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$business_id, $ip, $user_agent]);
} catch (Exception $e) {}

// Theme settings
$theme = $business['menu_theme'] ?? 'default';
$primary_color = $business['menu_primary_color'] ?? '#0055FF';

$texts = [
    'tr' => [
        'welcome' => htmlspecialchars($business['name']) . '\'a Hoş Geldiniz!',
        'explore_menu' => 'Menüyü İncele',
        'rate_us' => 'Bizi Değerlendirin',
        'all' => 'Tümü',
        'search_placeholder' => 'Yemek ara...',
        'allergens' => 'Alerjenler',
        'close' => 'Kapat',
        'our_location' => 'Konumumuz',
        'directions' => 'Yol Tarifi Al',
        'contact' => 'İletişim Bilgileri'
    ],
    'en' => [
        'welcome' => 'Welcome to ' . htmlspecialchars($business['name']) . '!',
        'explore_menu' => 'Explore Menu',
        'rate_us' => 'Rate Us',
        'all' => 'All',
        'search_placeholder' => 'Search dishes...',
        'allergens' => 'Allergens',
        'close' => 'Close',
        'our_location' => 'Our Location',
        'directions' => 'Get Directions',
        'contact' => 'Contact Information'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?php echo htmlspecialchars($business['name']); ?> - <?php echo $lang == 'en' ? 'Digital Menu' : 'Dijital Menü'; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($business['description'] ?? ''); ?>">
    <link rel="icon" href="/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '<?php echo $primary_color; ?>',
                    },
                    fontFamily: {
                        'heading': ['Outfit', 'sans-serif'],
                        'body': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            -webkit-tap-highlight-color: transparent;
        }
        h1, h2, h3, .font-heading { font-family: 'Outfit', sans-serif; }

        .glass { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px); 
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        .stagger-item { opacity: 0; transform: translateY(25px); }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        .animate-float { animation: float 3s ease-in-out infinite; }

        .action-card { 
            background: #ffffff; 
            border-radius: 20px; 
            padding: 16px 20px; 
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.06);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: 1px solid rgba(15, 23, 42, 0.04);
        }
        .action-card:hover { 
            transform: translateY(-4px) scale(1.02); 
            box-shadow: 0 12px 30px -8px rgba(15, 23, 42, 0.12);
        }
        .action-card:active { transform: scale(0.98); }

        .item-card { 
            background: #ffffff; 
            border-radius: 20px; 
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04); 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.2); 
            border: 1px solid rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .item-card:hover { 
            transform: translateY(-6px); 
            box-shadow: 0 20px 40px -12px rgba(15, 23, 42, 0.12);
        }
        .item-card:active { transform: scale(0.98); }

        .cat-pill {
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            white-space: nowrap;
            transition: all 0.3s ease;
            color: #64748b;
            background: transparent;
        }
        .cat-pill:hover { color: #0f172a; }
        .cat-pill.active {
            background: <?php echo $primary_color; ?>;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 85, 255, 0.25);
        }

        .search-input {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .search-input:focus {
            background: #ffffff;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 0 4px <?php echo $primary_color; ?>1a;
        }

        .img-container {
            position: relative;
            overflow: hidden;
            background: #f1f5f9;
        }
        .img-container img {
            transition: transform 0.5s ease, opacity 0.3s ease;
        }
        .img-container:hover img {
            transform: scale(1.08);
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .shimmer-loading {
            background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        #bottom-sheet { 
            transform: translateY(105%); 
            transition: transform 0.5s cubic-bezier(0.32, 0.72, 0, 1); 
            z-index: 1000; 
            max-height: 92vh;
        }
        #bottom-sheet.open { transform: translateY(0); }

        #overlay { 
            opacity: 0; 
            transition: opacity 0.4s ease; 
            pointer-events: none; 
            z-index: 999; 
            background: rgba(15, 23, 42, 0.4); 
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        #overlay.active { opacity: 1; pointer-events: auto; }
    </style>
</head>
<body class="pb-24">

    <!-- Header & Intro -->
    <section class="px-5 pt-8 pb-8 max-w-lg mx-auto">
        <div class="flex flex-col items-center stagger-item">
            <!-- Logo -->
            <?php if (!empty($business['menu_logo'])): ?>
            <div class="w-20 h-20 rounded-2xl bg-white shadow-2xl flex items-center justify-center mb-6 border-4 border-white overflow-hidden animate-float">
                <img src="/<?php echo htmlspecialchars($business['menu_logo']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($business['name']); ?>">
            </div>
            <?php else: ?>
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center mb-6 shadow-2xl border-4 border-white overflow-hidden animate-float">
                <i class="fas fa-utensils text-white text-3xl"></i>
            </div>
            <?php endif; ?>

            <!-- Language Switcher -->
            <div class="flex items-center gap-3 mb-6 bg-white/60 backdrop-blur-lg px-4 py-2 rounded-full border border-white/80 shadow-sm">
                <a href="?lang=tr<?php echo $active_cat !== 'all' ? '&cat='.$active_cat : ''; ?>" class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 px-2 py-1 rounded-full transition-all <?php echo $lang == 'tr' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-slate-600'; ?>">
                    🇹🇷 TR
                </a>
                <a href="?lang=en<?php echo $active_cat !== 'all' ? '&cat='.$active_cat : ''; ?>" class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 px-2 py-1 rounded-full transition-all <?php echo $lang == 'en' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-slate-600'; ?>">
                    🇬🇧 EN
                </a>
            </div>

            <!-- Welcome Bubble -->
            <div class="bg-white rounded-3xl rounded-bl-sm p-6 mb-8 w-full shadow-xl border border-slate-100">
                <p class="text-[15px] font-medium text-slate-800 leading-relaxed"><?php echo $texts[$lang]['welcome']; ?></p>
            </div>
            
            <!-- Social Media -->
            <?php if (!empty($business['instagram_url']) || !empty($business['facebook_url']) || !empty($business['tripadvisor_url']) || !empty($business['google_maps_url']) || !empty($business['phone']) || !empty($business['website'])): ?>
            <div class="flex items-center justify-center gap-2 mb-6 flex-wrap">
                <?php if (!empty($business['instagram_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['instagram_url']); ?>" target="_blank" class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 text-white flex items-center justify-center hover:scale-110 transition-transform shadow-md">
                    <i class="fab fa-instagram text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['facebook_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['facebook_url']); ?>" target="_blank" class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center hover:scale-110 transition-transform shadow-md">
                    <i class="fab fa-facebook-f text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['tripadvisor_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['tripadvisor_url']); ?>" target="_blank" class="w-11 h-11 rounded-xl bg-green-600 text-white flex items-center justify-center hover:scale-110 transition-transform shadow-md">
                    <i class="fab fa-tripadvisor text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['google_maps_url'])): ?>
                <a href="<?php echo htmlspecialchars($business['google_maps_url']); ?>" target="_blank" class="w-11 h-11 rounded-xl bg-red-500 text-white flex items-center justify-center hover:scale-110 transition-transform shadow-md">
                    <i class="fas fa-map-marker-alt text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center hover:scale-110 transition-transform shadow-md">
                    <i class="fas fa-phone text-lg"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($business['website'])): ?>
                <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" class="w-11 h-11 rounded-xl bg-slate-700 text-white flex items-center justify-center hover:scale-110 transition-transform shadow-md">
                    <i class="fas fa-globe text-lg"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Grid -->
        <div class="grid grid-cols-1 gap-3">
            <button onclick="scrollToMenu()" class="action-card text-left flex items-center justify-between stagger-item group">
                <div class="flex items-center gap-4">
                    <span class="w-12 h-12 bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">🍽️</span>
                    <span class="font-bold text-slate-800"><?php echo $texts[$lang]['explore_menu']; ?></span>
                </div>
                <i class="fas fa-chevron-right text-slate-300 group-hover:text-slate-700 group-hover:translate-x-1 transition-all"></i>
            </button>
        </div>
    </section>

    <!-- Sticky Navigation -->
    <div id="menu-anchor" class="sticky top-0 z-40 glass">
        <div class="max-w-lg mx-auto">
            <!-- Search -->
            <div class="px-5 pt-4 pb-3">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="search-input" class="search-input w-full pl-11 pr-4 py-3 rounded-xl text-sm font-medium outline-none" placeholder="<?php echo $texts[$lang]['search_placeholder']; ?>" oninput="filterItems(this.value)">
                </div>
            </div>
            <!-- Categories -->
            <nav class="overflow-x-auto hide-scrollbar flex space-x-2 px-5 pb-4">
                <a href="?lang=<?php echo $lang; ?>&cat=all" class="cat-pill <?php echo $active_cat == 'all' ? 'active' : ''; ?>">
                    <?php echo $texts[$lang]['all']; ?>
                </a>
                <?php foreach ($categories as $cat): 
                    $cat_name = $lang == 'en' && !empty($cat['name_en']) ? $cat['name_en'] : $cat['name'];
                ?>
                    <a href="?lang=<?php echo $lang; ?>&cat=<?php echo $cat['id']; ?>" class="cat-pill <?php echo $active_cat == $cat['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- Product List -->
    <main id="menu-items" class="max-w-lg mx-auto px-5 mt-4 space-y-4 pb-8">
        <?php if (empty($menu_items)): ?>
        <div class="bg-white rounded-3xl shadow-xl p-12 text-center">
            <i class="fas fa-utensils text-slate-300 text-5xl mb-4"></i>
            <h2 class="text-2xl font-bold text-slate-800 mb-2">
                <?php echo $lang == 'en' ? 'No items found' : 'Ürün bulunamadı'; ?>
            </h2>
        </div>
        <?php else: ?>
        <?php foreach ($menu_items as $item): 
            $item_name = $lang == 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
            $item_desc = $lang == 'en' && !empty($item['description_en']) ? $item['description_en'] : $item['description'];
            
            // Get allergens
            $allergen_stmt = $pdo->prepare("
                SELECT a.* FROM menu_allergens a
                JOIN menu_item_allergens mia ON a.id = mia.allergen_id
                WHERE mia.item_id = ?
            ");
            $allergen_stmt->execute([$item['id']]);
            $item_allergens = $allergen_stmt->fetchAll();
            
            $item_data = [
                "id" => $item['id'],
                "name" => $item_name, 
                "desc" => $item_desc, 
                "price" => $item["price"] ? number_format($item["price"], 2) . " ₺" : "",
                "image" => $item["image_url"] ? "/" . $item["image_url"] : "",
                "is_vegetarian" => (bool)$item["is_vegetarian"],
                "is_vegan" => (bool)$item["is_vegan"],
                "is_spicy" => (bool)$item["is_spicy"],
                "allergens" => $item_allergens
            ];
        ?>
            <div onclick='openDetail(<?php echo htmlspecialchars(json_encode($item_data), ENT_QUOTES, 'UTF-8'); ?>)' 
                 class="item-card p-4 flex gap-4 cursor-pointer stagger-item"
                 data-name="<?php echo strtolower(htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8')); ?>">
                <?php if ($item['image_url']): ?>
                <div class="img-container w-24 h-24 md:w-28 md:h-28 rounded-2xl flex-shrink-0 shimmer-loading">
                    <img src="/<?php echo htmlspecialchars($item['image_url']); ?>" 
                         class="w-full h-full object-cover opacity-0" 
                         loading="lazy" 
                         onload="this.classList.remove('opacity-0'); this.parentElement.classList.remove('shimmer-loading');"
                         alt="<?php echo htmlspecialchars($item_name); ?>">
                </div>
                <?php endif; ?>
                <div class="flex-1 py-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-bold text-slate-800 text-base leading-tight"><?php echo htmlspecialchars($item_name); ?></h3>
                            <?php if($item['is_chef_special']): ?>
                                <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wide">★ <?php echo $lang == 'en' ? 'Special' : 'Özel'; ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed"><?php echo htmlspecialchars($item_desc); ?></p>
                        
                        <!-- Tags -->
                        <div class="flex items-center gap-1.5 mt-2">
                            <?php if ($item['is_vegetarian']): ?>
                            <span class="text-[9px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-bold">🌱 <?php echo $lang == 'en' ? 'VEG' : 'VEJ'; ?></span>
                            <?php endif; ?>
                            <?php if ($item['is_vegan']): ?>
                            <span class="text-[9px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-bold">🥬 VEGAN</span>
                            <?php endif; ?>
                            <?php if ($item['is_spicy']): ?>
                            <span class="text-[9px] bg-red-50 text-red-700 px-2 py-0.5 rounded-full font-bold">🌶️ <?php echo $lang == 'en' ? 'SPICY' : 'ACILI'; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($item['price']): ?>
                    <span class="text-lg font-black text-slate-800 font-heading"><?php echo number_format($item['price'], 2); ?> ₺</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Location Section -->
    <?php if (!empty($business['latitude']) && !empty($business['longitude']) || !empty($business['google_maps_url'])): ?>
    <section id="rate-section" class="px-5 py-16 bg-white text-center border-t border-slate-100">
        <div class="max-w-lg mx-auto">
            <h3 class="text-2xl font-bold text-slate-800 mb-6 font-heading"><?php echo $texts[$lang]['our_location']; ?> 📍</h3>
            <div class="rounded-2xl overflow-hidden shadow-xl mb-6 border border-slate-100 h-52">
                <?php if (!empty($business['latitude']) && !empty($business['longitude'])): ?>
                <iframe 
                    src="https://www.google.com/maps?q=<?php echo $business['latitude']; ?>,<?php echo $business['longitude']; ?>&output=embed" 
                    class="w-full h-full border-0 grayscale hover:grayscale-0 transition-all duration-500" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
                <?php endif; ?>
            </div>
            <?php if (!empty($business['google_maps_url'])): ?>
            <a href="<?php echo htmlspecialchars($business['google_maps_url']); ?>" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-800 text-white rounded-xl font-bold text-xs uppercase tracking-widest shadow-lg active:scale-95 transition-all">
                <i class="fas fa-directions"></i> <?php echo $texts[$lang]['directions']; ?>
            </a>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <div class="text-center py-8 border-t border-slate-100 bg-white">
        <div class="text-sm text-slate-500 mb-2">
            <?php echo $lang == 'en' ? 'Powered by' : 'Tarafından sunulmaktadır'; ?>
            <a href="https://kalkansocial.com" target="_blank" class="font-bold text-slate-700 hover:underline">
                Kalkan Social
            </a>
        </div>
        <div class="text-xs text-slate-400">
            <?php echo $lang == 'en' ? 'Digital Menu System' : 'Dijital Menü Sistemi'; ?>
        </div>
    </div>

    <!-- Bottom Sheet (Item Detail) -->
    <div id="overlay" class="fixed inset-0" onclick="closeDetail()"></div>
    <div id="bottom-sheet" class="fixed bottom-0 left-0 right-0 max-w-lg mx-auto bg-white rounded-t-[32px] shadow-2xl overflow-hidden flex flex-col">
        <div class="flex-shrink-0 p-6 border-b border-slate-100">
            <button onclick="closeDetail()" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors ml-auto">
                <i class="fas fa-times text-slate-600"></i>
            </button>
        </div>
        <div id="detail-content" class="flex-1 overflow-y-auto p-6">
            <!-- Content will be injected here -->
        </div>
    </div>

    <script>
    // Filter items by search
    function filterItems(query) {
        const items = document.querySelectorAll('.item-card');
        const lowerQuery = query.toLowerCase();
        
        items.forEach(item => {
            const name = item.getAttribute('data-name');
            if (name.includes(lowerQuery)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Scroll to menu
    function scrollToMenu() {
        document.getElementById('menu-anchor').scrollIntoView({ behavior: 'smooth' });
    }

    // Open detail bottom sheet
    function openDetail(item) {
        const overlay = document.getElementById('overlay');
        const sheet = document.getElementById('bottom-sheet');
        const content = document.getElementById('detail-content');
        
        let html = '';
        
        if (item.image) {
            html += `<div class="aspect-video rounded-2xl overflow-hidden mb-6 shadow-lg">
                <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover">
            </div>`;
        }
        
        html += `<h2 class="text-2xl font-black text-slate-800 mb-2 font-heading">${item.name}</h2>`;
        
        if (item.price) {
            html += `<div class="text-3xl font-black mb-4" style="color: <?php echo $primary_color; ?>">${item.price}</div>`;
        }
        
        if (item.desc) {
            html += `<p class="text-slate-600 mb-6 leading-relaxed">${item.desc}</p>`;
        }
        
        // Tags
        let tags = '';
        if (item.is_vegetarian) tags += '<span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-green-200"><i class="fas fa-leaf"></i> <?php echo $lang == "en" ? "Vegetarian" : "Vejetaryen"; ?></span> ';
        if (item.is_vegan) tags += '<span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-emerald-200"><i class="fas fa-seedling"></i> Vegan</span> ';
        if (item.is_spicy) tags += '<span class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-red-200"><i class="fas fa-pepper-hot"></i> <?php echo $lang == "en" ? "Spicy" : "Acılı"; ?></span> ';
        
        if (tags) {
            html += `<div class="flex items-center gap-2 flex-wrap mb-6">${tags}</div>`;
        }
        
        // Allergens
        if (item.allergens && item.allergens.length > 0) {
            html += `<div class="bg-orange-50 border border-orange-200 rounded-2xl p-4">
                <h4 class="text-sm font-bold text-orange-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $texts[$lang]['allergens']; ?>
                </h4>
                <div class="flex items-center gap-2 flex-wrap">`;
            
            item.allergens.forEach(allergen => {
                const allergenName = '<?php echo $lang; ?>' === 'en' ? allergen.name_en : allergen.name_tr;
                html += `<span class="inline-flex items-center gap-1.5 bg-white text-orange-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-orange-300">
                    <i class="fas ${allergen.icon}"></i> ${allergenName}
                </span>`;
            });
            
            html += `</div></div>`;
        }
        
        content.innerHTML = html;
        overlay.classList.add('active');
        sheet.classList.add('open');
    }

    // Close detail
    function closeDetail() {
        document.getElementById('overlay').classList.remove('active');
        document.getElementById('bottom-sheet').classList.remove('open');
    }

    // GSAP Animations
    window.addEventListener('load', () => {
        const items = document.querySelectorAll('.stagger-item');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
                item.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            }, index * 100);
        });
    });
    </script>

</body>
</html>
