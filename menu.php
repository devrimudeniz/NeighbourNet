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

// Theme settings - gelişmiş tema sistemi
require_once __DIR__ . '/includes/menu_themes.php';
$theme_id = $business['menu_theme'] ?? 'default';
$t = get_menu_theme($theme_id, $business['menu_primary_color'] ?? null);
$primary_color = $t['primary'];
$is_dark_theme = !empty($t['dark']);

// Magazine layout: kategorilere göre grupla (sadece cat=all iken)
$grouped_items = [];
$use_magazine = ($t['layout'] ?? 'classic') === 'magazine' && $active_cat === 'all' && !empty($menu_items);
if ($use_magazine) {
    foreach ($menu_items as $item) {
        $cid = $item['category_id'];
        $cname = ($lang == 'en' && !empty($item['category_name_en'])) ? $item['category_name_en'] : ($item['category_name'] ?? '');
        if (!isset($grouped_items[$cid])) {
            $grouped_items[$cid] = ['name' => $cname, 'items' => []];
        }
        $grouped_items[$cid]['items'][] = $item;
    }
}

$whatsapp_phone = preg_replace('/[^0-9]/', '', $business['phone'] ?? '');
$has_whatsapp_order = !empty($whatsapp_phone);

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
        'contact' => 'İletişim Bilgileri',
        'add_to_cart' => 'Sepete Ekle',
        'added_to_cart' => 'Sepete eklendi',
        'send_whatsapp' => 'WhatsApp ile Sipariş Ver',
        'your_order' => 'Siparişiniz',
        'total' => 'Toplam',
        'cart_empty' => 'Sepetiniz boş'
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
        'contact' => 'Contact Information',
        'add_to_cart' => 'Add to Cart',
        'added_to_cart' => 'Added to cart',
        'send_whatsapp' => 'Order via WhatsApp',
        'your_order' => 'Your Order',
        'total' => 'Total',
        'cart_empty' => 'Your cart is empty'
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
    <link href="https://fonts.googleapis.com/css2?family=<?php echo get_menu_theme_fonts_url($theme_id); ?>" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '<?php echo $primary_color; ?>',
                    },
                    fontFamily: {
                        'heading': ['<?php echo addslashes($t['font_heading']); ?>', 'serif'],
                        'body': ['<?php echo addslashes($t['font_body']); ?>', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        body { 
            font-family: '<?php echo addslashes($t['font_body']); ?>', sans-serif; 
            background: <?php echo $t['bg']; ?>;
            -webkit-tap-highlight-color: transparent;
            <?php if ($is_dark_theme): ?>
            color: #f8fafc;
            <?php endif; ?>
        }
        h1, h2, h3, .font-heading { font-family: '<?php echo addslashes($t['font_heading']); ?>', serif; }

        .glass { 
            background: <?php echo $is_dark_theme ? 'rgba(15, 23, 42, 0.9)' : 'rgba(255, 255, 255, 0.85)'; ?>; 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px); 
            border-bottom: 1px solid <?php echo $is_dark_theme ? 'rgba(255,255,255,0.08)' : 'rgba(255, 255, 255, 0.3)'; ?>;
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
            background: <?php echo $t['card_bg']; ?>; 
            border-radius: <?php echo $t['card_radius']; ?>; 
            padding: 16px 20px; 
            box-shadow: <?php echo $t['card_shadow']; ?>;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: 1px solid <?php echo $t['card_border']; ?>;
        }
        .action-card:hover { 
            transform: translateY(-4px) scale(1.02); 
            box-shadow: 0 12px 30px -8px rgba(0,0,0,<?php echo $is_dark_theme ? '0.4' : '0.12'; ?>);
        }
        .action-card:active { transform: scale(0.98); }

        .item-card { 
            background: <?php echo $t['card_bg']; ?>; 
            border-radius: <?php echo $t['card_radius']; ?>; 
            box-shadow: <?php echo $t['card_shadow']; ?>; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.2); 
            border: 1px solid <?php echo $t['card_border']; ?>;
            overflow: hidden;
        }
        .item-card:hover { 
            transform: translateY(-6px); 
            box-shadow: 0 20px 40px -12px rgba(0,0,0,<?php echo $is_dark_theme ? '0.5' : '0.12'; ?>);
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
            color: <?php echo $is_dark_theme ? '#94a3b8' : '#64748b'; ?>;
            background: transparent;
        }
        .cat-pill:hover { color: <?php echo $is_dark_theme ? '#f1f5f9' : '#0f172a'; ?>; }
        .cat-pill.active {
            background: <?php echo $primary_color; ?>;
            color: #ffffff;
            box-shadow: 0 4px 12px <?php echo $primary_color; ?>40;
        }

        .search-input {
            background: <?php echo $is_dark_theme ? 'rgba(30,41,59,0.8)' : 'rgba(255, 255, 255, 0.9)'; ?>;
            border: 1px solid <?php echo $is_dark_theme ? 'rgba(255,255,255,0.1)' : 'rgba(15, 23, 42, 0.08)'; ?>;
            color: <?php echo $is_dark_theme ? '#f8fafc' : '#0f172a'; ?>;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .search-input::placeholder { color: <?php echo $is_dark_theme ? '#94a3b8' : '#64748b'; ?>; }
        .search-input:focus {
            background: <?php echo $is_dark_theme ? 'rgba(30,41,59,0.95)' : '#ffffff'; ?>;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 0 4px <?php echo $primary_color; ?>2a;
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
        <?php if ($is_dark_theme): ?>
        .theme-text { color: #f8fafc !important; }
        .theme-text-muted { color: #94a3b8 !important; }
        .welcome-bubble { background: <?php echo $t['welcome_bg']; ?> !important; border-color: <?php echo $t['card_border']; ?> !important; }
        .action-card span, .action-card .font-bold { color: #f8fafc !important; }
        .item-card h3, .item-card .font-black { color: #f8fafc !important; }
        .item-card p { color: #94a3b8 !important; }
        .empty-menu { background: <?php echo $t['card_bg']; ?> !important; color: #94a3b8 !important; }
        .location-section { background: <?php echo $t['card_bg']; ?> !important; border-color: <?php echo $t['card_border']; ?> !important; }
        .footer-bg { background: <?php echo $t['card_bg']; ?> !important; border-color: <?php echo $t['card_border']; ?> !important; color: #94a3b8 !important; }
        .bottom-sheet { background: <?php echo $t['card_bg']; ?> !important; }
        <?php else: ?>
        .welcome-bubble { background: <?php echo $t['welcome_bg']; ?> !important; }
        <?php endif; ?>

        /* === TEMA VARYASYONLARI (layout, card, item, cat) === */
        [data-font-scale="small"] .item-card h3 { font-size: 0.9rem; }
        [data-font-scale="small"] .action-card span { font-size: 0.85rem; }
        [data-font-scale="large"] .item-card h3 { font-size: 1.1rem; }
        [data-font-scale="large"] .action-card span { font-size: 1rem; }
        [data-font-scale="large"] .welcome-bubble p { font-size: 1.05rem; }
        [data-spacing="tight"] .item-card { padding: 12px 16px !important; }
        [data-spacing="tight"] section { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
        [data-spacing="relaxed"] .item-card { padding: 20px 24px !important; }
        [data-spacing="relaxed"] .action-card { padding: 20px 24px !important; }
        [data-spacing="relaxed"] #menu-items { gap: 1.5rem !important; }
        [data-animation="none"] .item-card:hover, [data-animation="none"] .action-card:hover { transform: none !important; }
        [data-animation="pronounced"] .item-card:hover { transform: translateY(-10px) scale(1.02) !important; }

        [data-card-style="flat"] .item-card, [data-card-style="flat"] .action-card { box-shadow: none !important; border-width: 1px !important; }
        [data-card-style="bordered"] .item-card, [data-card-style="bordered"] .action-card { box-shadow: none !important; border-width: 2px !important; }
        [data-card-style="elevated"] .item-card, [data-card-style="elevated"] .action-card { box-shadow: 0 12px 40px -12px rgba(0,0,0,0.25) !important; }
        [data-card-style="glass"] .item-card, [data-card-style="glass"] .action-card { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }

        [data-cat-style="tabs"] .cat-pill { border-radius: 0; border-bottom: 3px solid transparent; padding: 12px 16px; }
        [data-cat-style="tabs"] .cat-pill.active { border-bottom-color: <?php echo $primary_color; ?>; background: transparent !important; }
        [data-cat-style="buttons"] .cat-pill { border-radius: 10px; padding: 10px 18px; }
        [data-cat-style="segmented"] .cat-pills-wrap { background: <?php echo $is_dark_theme ? 'rgba(15,23,42,0.5)' : 'rgba(0,0,0,0.05)'; ?> !important; padding: 4px !important; border-radius: 12px; gap: 2px; }
        [data-cat-style="segmented"] .cat-pill { border-radius: 8px; text-align: center; }
        [data-cat-style="segmented"] .cat-pill.active { box-shadow: 0 2px 8px rgba(0,0,0,0.15); }

        [data-item-layout="vertical"] .item-card { flex-direction: column !important; }
        [data-item-layout="vertical"] .item-card .img-container { width: 100% !important; height: 140px !important; flex-shrink: 0; }
        [data-item-layout="vertical"] .item-card .flex-1 { width: 100%; }
        [data-item-layout="compact"] .item-card { flex-direction: row; padding: 12px 16px !important; }
        [data-item-layout="compact"] .item-card .img-container { width: 48px !important; height: 48px !important; min-width: 48px; }
        [data-item-layout="compact"] .item-card h3 { font-size: 0.9rem; margin-bottom: 0 !important; }
        [data-item-layout="compact"] .item-card p { display: none !important; }
        [data-item-layout="compact"] .item-card .flex.items-center.gap-1\.5 { margin-top: 0 !important; }

        [data-welcome-style="minimal"] .welcome-bubble { padding: 1rem 1.5rem !important; border-radius: 12px !important; }
        [data-welcome-style="card"] .welcome-bubble { border-radius: 16px !important; border-left: 4px solid <?php echo $primary_color; ?> !important; }
        [data-welcome-style="hero"] .header-intro { padding: 2rem 1.5rem !important; }
        [data-welcome-style="hero"] .welcome-bubble { background: <?php echo $t['welcome_bg']; ?> !important; padding: 2rem !important; text-align: center; font-size: 1.15rem !important; }

        [data-layout="hero"] .header-intro { max-width: 100% !important; background: linear-gradient(180deg, <?php echo $t['welcome_bg']; ?> 0%, transparent 100%); padding: 2.5rem 1.5rem 3rem !important; margin: 0 -1rem; padding-left: calc(1.5rem + 1rem) !important; padding-right: calc(1.5rem + 1rem) !important; }
        [data-layout="hero"] .header-intro .w-20 { width: 5rem !important; height: 5rem !important; }
        [data-layout="compact"] .header-intro { padding-top: 1rem !important; padding-bottom: 1.5rem !important; }
        [data-layout="compact"] .header-intro .w-20 { width: 4rem !important; height: 4rem !important; }
        [data-layout="compact"] .welcome-bubble { padding: 1rem 1.25rem !important; margin-bottom: 1rem !important; }
        [data-layout="magazine"] .magazine-cat-title { font-family: inherit; font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid <?php echo $primary_color; ?>; }
    </style>
</head>
<body class="pb-24 <?php echo $is_dark_theme ? 'menu-theme-dark' : ''; ?>" 
      data-theme="<?php echo htmlspecialchars($theme_id); ?>"
      data-layout="<?php echo htmlspecialchars($t['layout'] ?? 'classic'); ?>"
      data-card-style="<?php echo htmlspecialchars($t['card_style'] ?? 'rounded'); ?>"
      data-item-layout="<?php echo htmlspecialchars($t['item_layout'] ?? 'horizontal'); ?>"
      data-cat-style="<?php echo htmlspecialchars($t['cat_style'] ?? 'pills'); ?>"
      data-welcome-style="<?php echo htmlspecialchars($t['welcome_style'] ?? 'bubble'); ?>"
      data-font-scale="<?php echo htmlspecialchars($t['font_scale'] ?? 'medium'); ?>"
      data-spacing="<?php echo htmlspecialchars($t['spacing'] ?? 'normal'); ?>"
      data-animation="<?php echo htmlspecialchars($t['animation'] ?? 'subtle'); ?>"
      data-business-phone="<?php echo htmlspecialchars($whatsapp_phone); ?>"
      data-business-name="<?php echo htmlspecialchars($business['name']); ?>"
      data-lang="<?php echo $lang; ?>"
      data-added-to-cart="<?php echo htmlspecialchars($texts[$lang]['added_to_cart']); ?>">

    <!-- Header & Intro -->
    <section class="header-intro px-5 pt-8 pb-8 max-w-lg mx-auto">
        <div class="flex flex-col items-center stagger-item">
            <!-- Logo -->
            <?php if (!empty($business['menu_logo'])): ?>
            <div class="w-20 h-20 rounded-2xl shadow-2xl flex items-center justify-center mb-6 border-4 overflow-hidden animate-float <?php echo $is_dark_theme ? 'bg-slate-800 border-slate-600' : 'bg-white border-white'; ?>">
                <img src="/<?php echo htmlspecialchars($business['menu_logo']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($business['name']); ?>">
            </div>
            <?php else: ?>
            <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-6 shadow-2xl border-4 overflow-hidden animate-float <?php echo $is_dark_theme ? 'bg-gradient-to-br from-slate-600 to-slate-800 border-slate-600' : 'bg-gradient-to-br from-slate-700 to-slate-900 border-white'; ?>">
                <i class="fas fa-utensils text-white text-3xl"></i>
            </div>
            <?php endif; ?>

            <!-- Language Switcher -->
            <div class="flex items-center gap-3 mb-6 <?php echo $is_dark_theme ? 'bg-slate-800/60 border-slate-600/50' : 'bg-white/60 border-white/80'; ?> backdrop-blur-lg px-4 py-2 rounded-full border shadow-sm">
                <a href="?lang=tr<?php echo $active_cat !== 'all' ? '&cat='.$active_cat : ''; ?>" class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 px-2 py-1 rounded-full transition-all <?php echo $lang == 'tr' ? 'bg-slate-700 text-white' : ($is_dark_theme ? 'text-slate-400 hover:text-slate-200' : 'text-slate-400 hover:text-slate-600'); ?>">
                    🇹🇷 TR
                </a>
                <a href="?lang=en<?php echo $active_cat !== 'all' ? '&cat='.$active_cat : ''; ?>" class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 px-2 py-1 rounded-full transition-all <?php echo $lang == 'en' ? 'bg-slate-700 text-white' : ($is_dark_theme ? 'text-slate-400 hover:text-slate-200' : 'text-slate-400 hover:text-slate-600'); ?>">
                    🇬🇧 EN
                </a>
            </div>
            <!-- Currency Toggle -->
            <div class="flex items-center gap-2 mb-6 <?php echo $is_dark_theme ? 'bg-slate-800/60 border-slate-600/50' : 'bg-white/60 border-white/80'; ?> backdrop-blur-lg px-4 py-2 rounded-full border shadow-sm">
                <span class="text-[10px] font-bold text-slate-500 mr-1"><?php echo $lang == 'en' ? 'Prices' : 'Fiyat'; ?>:</span>
                <button type="button" onclick="setMenuCurrency('try')" class="menu-currency-btn bg-slate-700 text-white text-[10px] font-bold px-2 py-1 rounded-full transition-all" data-currency="try">₺</button>
                <button type="button" onclick="setMenuCurrency('gbp')" class="menu-currency-btn <?php echo $is_dark_theme ? 'text-slate-400' : 'text-slate-500'; ?> text-[10px] font-bold px-2 py-1 rounded-full transition-all hover:opacity-80" data-currency="gbp">£</button>
                <button type="button" onclick="setMenuCurrency('usd')" class="menu-currency-btn <?php echo $is_dark_theme ? 'text-slate-400' : 'text-slate-500'; ?> text-[10px] font-bold px-2 py-1 rounded-full transition-all hover:opacity-80" data-currency="usd">$</button>
                <button type="button" onclick="setMenuCurrency('eur')" class="menu-currency-btn <?php echo $is_dark_theme ? 'text-slate-400' : 'text-slate-500'; ?> text-[10px] font-bold px-2 py-1 rounded-full transition-all hover:opacity-80" data-currency="eur">€</button>
            </div>

            <!-- Welcome Bubble -->
            <div class="welcome-bubble rounded-3xl rounded-bl-sm p-6 mb-8 w-full shadow-xl border <?php echo $is_dark_theme ? '' : 'bg-white border-slate-100'; ?>">
                <p class="text-[15px] font-medium leading-relaxed <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo $texts[$lang]['welcome']; ?></p>
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
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.006 4.295c-2.67 0-5.338.784-7.645 2.353-.188.131-.315.327-.358.55s.007.452.14.64l1.116 1.575c.123.174.306.296.51.34s.415-.007.588-.13c1.302-.926 2.834-1.415 4.434-1.415 1.6 0 3.132.489 4.434 1.415.173.123.384.174.588.13s.387-.166.51-.34l1.116-1.575c.133-.188.183-.417.14-.64s-.17-.419-.358-.55c-2.307-1.569-4.975-2.353-7.645-2.353zm-5.646 7.705c0 1.657 1.343 3 3 3s3-1.343 3-3-1.343-3-3-3-3 1.343-3 3zm2 0c0-.552.448-1 1-1s1 .448 1 1-.448 1-1 1-1-.448-1-1zm5.64 0c0 1.657 1.343 3 3 3s3-1.343 3-3-1.343-3-3-3-3 1.343-3 3zm2 0c0-.552.448-1 1-1s1 .448 1 1-.448 1-1 1-1-.448-1-1z"/>
                    </svg>
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
                    <span class="font-bold <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo $texts[$lang]['explore_menu']; ?></span>
                </div>
                <i class="fas fa-chevron-right <?php echo $is_dark_theme ? 'text-slate-500 group-hover:text-slate-300' : 'text-slate-300 group-hover:text-slate-700'; ?> group-hover:translate-x-1 transition-all"></i>
            </button>
            
            <?php if (!empty($business['tripadvisor_url'])): ?>
            <a href="<?php echo htmlspecialchars($business['tripadvisor_url']); ?>" target="_blank" class="action-card text-left flex items-center justify-between stagger-item group">
                <div class="flex items-center gap-4">
                    <span class="w-12 h-12 bg-gradient-to-br from-green-50 to-green-100 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">⭐</span>
                    <span class="font-bold <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo $texts[$lang]['rate_us']; ?></span>
                </div>
                <i class="fas fa-chevron-right <?php echo $is_dark_theme ? 'text-slate-500 group-hover:text-green-400' : 'text-slate-300 group-hover:text-green-600'; ?> group-hover:translate-x-1 transition-all"></i>
            </a>
            <?php endif; ?>
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
            <nav class="overflow-x-auto hide-scrollbar flex space-x-2 px-5 pb-4 cat-pills-wrap">
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
        <?php if (empty($menu_items) && !$use_magazine): ?>
        <div class="empty-menu rounded-3xl shadow-xl p-12 text-center <?php echo $is_dark_theme ? '' : 'bg-white'; ?>">
            <i class="fas fa-utensils text-5xl mb-4 <?php echo $is_dark_theme ? 'text-slate-600' : 'text-slate-300'; ?>"></i>
            <h2 class="text-2xl font-bold mb-2 <?php echo $is_dark_theme ? 'text-slate-200' : 'text-slate-800'; ?>">
                <?php echo $lang == 'en' ? 'No items found' : 'Ürün bulunamadı'; ?>
            </h2>
        </div>
        <?php elseif ($use_magazine): ?>
        <?php foreach ($grouped_items as $group): ?>
        <section class="magazine-cat-section mb-8">
            <h2 class="magazine-cat-title <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo htmlspecialchars($group['name']); ?></h2>
            <div class="space-y-4">
        <?php foreach ($group['items'] as $item): 
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
                "price_tl" => (float)($item["price"] ?? 0),
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
                            <h3 class="font-bold text-base leading-tight <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo htmlspecialchars($item_name); ?></h3>
                            <?php if($item['is_chef_special']): ?>
                                <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wide">★ <?php echo $lang == 'en' ? 'Special' : 'Özel'; ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[12px] line-clamp-2 leading-relaxed <?php echo $is_dark_theme ? 'text-slate-400' : 'text-slate-500'; ?>"><?php echo htmlspecialchars($item_desc); ?></p>
                        
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
                    <span class="menu-price text-lg font-black font-heading" style="color: <?php echo $primary_color; ?>" data-price-tl="<?php echo $item['price']; ?>"><?php echo number_format($item['price'], 2); ?> ₺</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
        <?php else: ?>
        <?php foreach ($menu_items as $item): 
            $item_name = $lang == 'en' && !empty($item['name_en']) ? $item['name_en'] : $item['name'];
            $item_desc = $lang == 'en' && !empty($item['description_en']) ? $item['description_en'] : $item['description'];
            $allergen_stmt = $pdo->prepare("SELECT a.* FROM menu_allergens a JOIN menu_item_allergens mia ON a.id = mia.allergen_id WHERE mia.item_id = ?");
            $allergen_stmt->execute([$item['id']]);
            $item_allergens = $allergen_stmt->fetchAll();
            $item_data = [
                "id" => $item['id'],
                "name" => $item_name,
                "desc" => $item_desc,
                "price" => $item["price"] ? number_format($item["price"], 2) . " ₺" : "",
                "price_tl" => (float)($item["price"] ?? 0),
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
                    <img src="/<?php echo htmlspecialchars($item['image_url']); ?>" class="w-full h-full object-cover opacity-0" loading="lazy" onload="this.classList.remove('opacity-0'); this.parentElement.classList.remove('shimmer-loading');" alt="<?php echo htmlspecialchars($item_name); ?>">
                </div>
                <?php endif; ?>
                <div class="flex-1 py-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-bold text-base leading-tight <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo htmlspecialchars($item_name); ?></h3>
                            <?php if($item['is_chef_special']): ?><span class="bg-amber-100 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-wide">★ <?php echo $lang == 'en' ? 'Special' : 'Özel'; ?></span><?php endif; ?>
                        </div>
                        <p class="text-[12px] line-clamp-2 leading-relaxed <?php echo $is_dark_theme ? 'text-slate-400' : 'text-slate-500'; ?>"><?php echo htmlspecialchars($item_desc); ?></p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <?php if ($item['is_vegetarian']): ?><span class="text-[9px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-bold">🌱 <?php echo $lang == 'en' ? 'VEG' : 'VEJ'; ?></span><?php endif; ?>
                            <?php if ($item['is_vegan']): ?><span class="text-[9px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-bold">🥬 VEGAN</span><?php endif; ?>
                            <?php if ($item['is_spicy']): ?><span class="text-[9px] bg-red-50 text-red-700 px-2 py-0.5 rounded-full font-bold">🌶️ <?php echo $lang == 'en' ? 'SPICY' : 'ACILI'; ?></span><?php endif; ?>
                        </div>
                    </div>
                    <?php if ($item['price']): ?><span class="menu-price text-lg font-black font-heading" style="color: <?php echo $primary_color; ?>" data-price-tl="<?php echo $item['price']; ?>"><?php echo number_format($item['price'], 2); ?> ₺</span><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Location Section -->
    <?php if (!empty($business['latitude']) && !empty($business['longitude']) || !empty($business['google_maps_url'])): ?>
    <section id="rate-section" class="location-section px-5 py-16 text-center border-t <?php echo $is_dark_theme ? 'border-slate-700' : 'bg-white border-slate-100'; ?>">
        <div class="max-w-lg mx-auto">
            <h3 class="text-2xl font-bold mb-6 font-heading <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo $texts[$lang]['our_location']; ?> 📍</h3>
            <?php if (!empty($business['latitude']) && !empty($business['longitude'])): ?>
            <div id="menu-map" class="rounded-2xl overflow-hidden shadow-xl mb-6 border <?php echo $is_dark_theme ? 'border-slate-700' : 'border-slate-100'; ?>" style="height:210px;"></div>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
            (function(){
                var lat = <?php echo (float)$business['latitude']; ?>;
                var lng = <?php echo (float)$business['longitude']; ?>;
                var map = L.map('menu-map', { scrollWheelZoom: false, zoomControl: false }).setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([lat, lng]).addTo(map);
                L.control.zoom({ position: 'bottomright' }).addTo(map);
                // Fix map rendering on lazy-loaded sections
                setTimeout(function(){ map.invalidateSize(); }, 300);
            })();
            </script>
            <?php endif; ?>
            <?php if (!empty($business['google_maps_url'])): ?>
            <a href="<?php echo htmlspecialchars($business['google_maps_url']); ?>" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-800 text-white rounded-xl font-bold text-xs uppercase tracking-widest shadow-lg active:scale-95 transition-all">
                <i class="fas fa-directions"></i> <?php echo $texts[$lang]['directions']; ?>
            </a>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer-bg text-center py-8 border-t <?php echo $is_dark_theme ? 'border-slate-700' : 'border-slate-100 bg-white'; ?>">
        <div class="text-sm mb-2 <?php echo $is_dark_theme ? 'text-slate-400' : 'text-slate-500'; ?>">
            <?php echo $lang == 'en' ? 'Powered by' : 'Tarafından sunulmaktadır'; ?>
            <a href="https://kalkansocial.com" target="_blank" class="font-bold text-slate-700 hover:underline">
                Kalkan Social
            </a>
        </div>
        <div class="text-xs <?php echo $is_dark_theme ? 'text-slate-500' : 'text-slate-400'; ?>">
            <?php echo $lang == 'en' ? 'Digital Menu System' : 'Dijital Menü Sistemi'; ?>
        </div>
    </div>

    <?php if ($has_whatsapp_order): ?>
    <!-- Floating Cart FAB -->
    <button id="cart-fab" onclick="openCartSheet()" class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-white transition-all hover:scale-110 active:scale-95" style="background: #25D366; display: none;">
        <i class="fas fa-shopping-cart text-2xl"></i>
        <span id="cart-count" class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center" style="display: none;">0</span>
    </button>
    <!-- Cart Bottom Sheet -->
    <div id="cart-overlay" class="fixed inset-0 z-[1001] bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300" onclick="closeCartSheet()"></div>
    <div id="cart-sheet" class="fixed bottom-0 left-0 right-0 max-w-lg mx-auto z-[1002] rounded-t-[32px] shadow-2xl overflow-hidden flex flex-col bg-white transform translate-y-full transition-transform duration-300 max-h-[85vh] <?php echo $is_dark_theme ? 'bg-slate-900' : 'bg-white'; ?>">
        <div class="flex-shrink-0 p-4 border-b <?php echo $is_dark_theme ? 'border-slate-700' : 'border-slate-100'; ?> flex items-center justify-between">
            <h3 class="text-lg font-bold <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo $texts[$lang]['your_order']; ?></h3>
            <button onclick="closeCartSheet()" class="w-10 h-10 rounded-full <?php echo $is_dark_theme ? 'bg-slate-700 hover:bg-slate-600' : 'bg-slate-100 hover:bg-slate-200'; ?> flex items-center justify-center">
                <i class="fas fa-times <?php echo $is_dark_theme ? 'text-slate-300' : 'text-slate-600'; ?>"></i>
            </button>
        </div>
        <div id="cart-list" class="flex-1 overflow-y-auto p-4 space-y-3">
            <!-- Cart items injected here -->
        </div>
        <div id="cart-footer" class="flex-shrink-0 p-4 border-t <?php echo $is_dark_theme ? 'border-slate-700' : 'border-slate-100'; ?>" style="display: none;">
            <div class="flex justify-between items-center mb-3">
                <span class="font-bold <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>"><?php echo $texts[$lang]['total']; ?>:</span>
                <span id="cart-total" class="text-xl font-black" style="color: <?php echo $primary_color; ?>">0 ₺</span>
            </div>
            <button onclick="sendToWhatsApp()" class="w-full py-4 rounded-xl font-bold text-white flex items-center justify-center gap-2" style="background: #25D366;">
                <i class="fab fa-whatsapp text-2xl"></i> <?php echo $texts[$lang]['send_whatsapp']; ?>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bottom Sheet (Item Detail) -->
    <div id="overlay" class="fixed inset-0" onclick="closeDetail()"></div>
    <div id="bottom-sheet" class="bottom-sheet fixed bottom-0 left-0 right-0 max-w-lg mx-auto rounded-t-[32px] shadow-2xl overflow-hidden flex flex-col <?php echo $is_dark_theme ? '' : 'bg-white'; ?>">
        <div class="flex-shrink-0 p-6 border-b <?php echo $is_dark_theme ? 'border-slate-700' : 'border-slate-100'; ?>">
            <button onclick="closeDetail()" class="w-10 h-10 rounded-full <?php echo $is_dark_theme ? 'bg-slate-700 hover:bg-slate-600' : 'bg-slate-100 hover:bg-slate-200'; ?> flex items-center justify-center transition-colors ml-auto">
                <i class="fas fa-times <?php echo $is_dark_theme ? 'text-slate-300' : 'text-slate-600'; ?>"></i>
            </button>
        </div>
        <div id="detail-content" class="flex-1 overflow-y-auto p-6">
            <!-- Content will be injected here -->
        </div>
    </div>

    <script>
    // Business & Cart (WhatsApp order)
    const businessPhone = document.body.dataset.businessPhone || '';
    const businessName = document.body.dataset.businessName || '';
    const menuLang = document.body.dataset.lang || 'tr';
    let cart = {};
    let currentDetailItem = null;
    let detailQty = 1;

    function showToast(msg) {
        let el = document.getElementById('cart-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'cart-toast';
            el.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-[1100] px-5 py-3 rounded-xl text-white font-medium shadow-lg opacity-0 pointer-events-none transition-opacity duration-300';
            el.style.background = '<?php echo $primary_color; ?>';
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.classList.remove('opacity-0');
        setTimeout(() => el.classList.add('opacity-0'), 2000);
    }
    function addToCart(item, qty) {
        if (!item || qty < 1) return;
        const key = String(item.id);
        if (cart[key]) cart[key].qty += qty; else cart[key] = { item, qty };
        updateCartUI();
        showToast(document.body.dataset.addedToCart || 'Sepete eklendi');
    }
    function removeFromCart(itemId) {
        delete cart[String(itemId)];
        updateCartUI();
    }
    function adjustDetailQty(delta) {
        detailQty = Math.max(1, Math.min(99, detailQty + delta));
        const el = document.getElementById('detail-qty');
        if (el) el.textContent = detailQty;
    }
    function addToCartFromDetail() {
        if (!currentDetailItem) return;
        addToCart(currentDetailItem, detailQty);
    }
    function updateCartUI() {
        const fab = document.getElementById('cart-fab');
        const countEl = document.getElementById('cart-count');
        const footer = document.getElementById('cart-footer');
        const list = document.getElementById('cart-list');
        const totalEl = document.getElementById('cart-total');
        if (!fab) return;
        const totalItems = Object.values(cart).reduce((s, x) => s + x.qty, 0);
        const totalTl = Object.values(cart).reduce((s, x) => s + x.item.price_tl * x.qty, 0);
        fab.style.display = totalItems > 0 ? 'flex' : 'none';
        if (countEl) {
            countEl.textContent = totalItems;
            countEl.style.display = totalItems > 0 ? 'flex' : 'none';
        }
        if (totalItems === 0) {
            list.innerHTML = '<p class="text-slate-500 py-8 text-center"><?php echo addslashes($texts[$lang]['cart_empty']); ?></p>';
            footer.style.display = 'none';
            return;
        }
        footer.style.display = 'block';
        totalEl.textContent = totalTl.toFixed(2) + ' ₺';
        list.innerHTML = Object.entries(cart).map(([id, { item, qty }]) => `
            <div class="flex items-center justify-between gap-3 p-3 rounded-xl <?php echo $is_dark_theme ? 'bg-slate-800' : 'bg-slate-50'; ?>">
                <div class="flex-1 min-w-0">
                    <div class="font-bold <?php echo $is_dark_theme ? 'text-slate-100' : 'text-slate-800'; ?>">${item.name}</div>
                    <div class="text-sm <?php echo $is_dark_theme ? 'text-slate-400' : 'text-slate-500'; ?>">${qty} x ${(item.price_tl).toFixed(2)} ₺</div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold" style="color: <?php echo $primary_color; ?>">${(item.price_tl * qty).toFixed(2)} ₺</span>
                    <button type="button" onclick="removeFromCart(${item.id})" class="w-8 h-8 rounded-full bg-red-100 text-red-600 hover:bg-red-200 flex items-center justify-center text-sm"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        `).join('');
    }
    function openCartSheet() {
        const o = document.getElementById('cart-overlay'), s = document.getElementById('cart-sheet');
        if (!o || !s) return;
        o.classList.add('opacity-100', 'pointer-events-auto');
        s.classList.remove('translate-y-full');
    }
    function closeCartSheet() {
        const o = document.getElementById('cart-overlay'), s = document.getElementById('cart-sheet');
        if (!o || !s) return;
        o.classList.remove('opacity-100', 'pointer-events-auto');
        s.classList.add('translate-y-full');
    }
    function sendToWhatsApp() {
        if (!businessPhone || Object.keys(cart).length === 0) return;
        const lines = Object.values(cart).map(({ item, qty }) => `- ${item.name} x${qty} - ${(item.price_tl * qty).toFixed(2)}₺`);
        const totalTl = Object.values(cart).reduce((s, x) => s + x.item.price_tl * x.qty, 0);
        const msg = (menuLang === 'tr' ? 'Merhaba, şu siparişi vermek istiyorum:\n\n' : 'Hello, I would like to place this order:\n\n') + lines.join('\n') + '\n\n' + (menuLang === 'tr' ? 'Toplam: ' : 'Total: ') + totalTl.toFixed(2) + ' ₺';
        const url = 'https://wa.me/' + businessPhone + '?text=' + encodeURIComponent(msg);
        window.open(url, '_blank');
    }

    // Currency
    let menuCurrency = 'try';
    let menuRates = { try_to_gbp: 0.0175, try_to_usd: 0.022, try_to_eur: 0.020 };
    const currencySymbols = { try: '₺', gbp: '£', usd: '$', eur: '€' };

    async function loadMenuRates() {
        try {
            const r = await fetch('/api/get_exchange_rate.php');
            const d = await r.json();
            if (d.status === 'success' && d.try_to_gbp) {
                menuRates = { try_to_gbp: d.try_to_gbp, try_to_usd: d.try_to_usd || (d.try_to_gbp * 1.27), try_to_eur: d.try_to_eur || (d.try_to_gbp * 1.17) };
            }
        } catch (e) {}
    }

    function setMenuCurrency(cur) {
        menuCurrency = cur;
        document.querySelectorAll('.menu-currency-btn').forEach(btn => {
            btn.classList.toggle('bg-slate-700', btn.dataset.currency === cur);
            btn.classList.toggle('text-white', btn.dataset.currency === cur);
            btn.classList.toggle('<?php echo $is_dark_theme ? "text-slate-400" : "text-slate-500"; ?>', btn.dataset.currency !== cur);
        });
        document.querySelectorAll('.menu-price').forEach(el => {
            const tl = parseFloat(el.dataset.priceTl);
            if (isNaN(tl)) return;
            let val = tl, sym = '₺';
            if (cur === 'gbp') { val = tl * menuRates.try_to_gbp; sym = '£'; }
            else if (cur === 'usd') { val = tl * menuRates.try_to_usd; sym = '$'; }
            else if (cur === 'eur') { val = tl * menuRates.try_to_eur; sym = '€'; }
            el.textContent = val < 1 ? val.toFixed(2) + ' ' + sym : val.toFixed(2) + ' ' + sym;
        });
    }

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
        // Magazine: tüm ürünleri gizli bölümleri sakla
        document.querySelectorAll('.magazine-cat-section').forEach(section => {
            const cards = section.querySelectorAll('.item-card');
            const anyVisible = Array.from(cards).some(c => c.style.display !== 'none');
            section.style.display = anyVisible ? '' : 'none';
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
        
        // Add to cart (WhatsApp order)
        const canOrder = typeof businessPhone !== 'undefined' && businessPhone && item.price_tl > 0;
        if (canOrder) {
            html += `<div class="mt-6 pt-4 border-t <?php echo $is_dark_theme ? 'border-slate-700' : 'border-slate-100'; ?>">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="event.stopPropagation(); adjustDetailQty(-1)" class="w-10 h-10 rounded-full <?php echo $is_dark_theme ? 'bg-slate-700 hover:bg-slate-600' : 'bg-slate-200 hover:bg-slate-300'; ?> flex items-center justify-center font-bold">−</button>
                        <span id="detail-qty" class="w-8 text-center font-bold">1</span>
                        <button type="button" onclick="event.stopPropagation(); adjustDetailQty(1)" class="w-10 h-10 rounded-full <?php echo $is_dark_theme ? 'bg-slate-700 hover:bg-slate-600' : 'bg-slate-200 hover:bg-slate-300'; ?> flex items-center justify-center font-bold">+</button>
                    </div>
                    <button type="button" onclick="event.stopPropagation(); addToCartFromDetail()" class="flex-1 py-3 px-6 rounded-xl font-bold text-white flex items-center justify-center gap-2" style="background: #25D366;">
                        <i class="fas fa-cart-plus"></i> <?php echo $texts[$lang]['add_to_cart']; ?>
                    </button>
                </div>
            </div>`;
        }
        
        content.innerHTML = html;
        if (canOrder) { currentDetailItem = item; detailQty = 1; }
        overlay.classList.add('active');
        sheet.classList.add('open');
    }

    // Close detail
    function closeDetail() {
        document.getElementById('overlay').classList.remove('active');
        document.getElementById('bottom-sheet').classList.remove('open');
    }

    // GSAP Animations + Currency init
    window.addEventListener('load', () => {
        const items = document.querySelectorAll('.stagger-item');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
                item.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            }, index * 100);
        });
        loadMenuRates().then(() => {
            document.querySelector('.menu-currency-btn[data-currency="try"]')?.classList.add('bg-slate-700', 'text-white');
        });
    });
    </script>

</body>
</html>
