<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
require_once 'includes/ui_components.php';

// $lang is already set by lang.php

// Check if user has business badge
if (!isset($_SESSION['user_id']) || !isset($_SESSION['badge']) || 
    !in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])) {
    header('Location: request_verification?type=business');
    exit();
}

$business_id = isset($_GET['business_id']) ? (int)$_GET['business_id'] : 0;
$user_id = $_SESSION['user_id'];

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM business_listings WHERE id = ? AND owner_id = ?");
$stmt->execute([$business_id, $user_id]);
$business = $stmt->fetch();

if (!$business) {
    header('Location: business_panel.php');
    exit();
}

// Get menu categories
$categories_stmt = $pdo->prepare("
    SELECT bmc.*, 
           (SELECT COUNT(*) FROM business_menu_items WHERE category_id = bmc.id) as items_count
    FROM business_menu_categories bmc
    WHERE bmc.business_id = ?
    ORDER BY bmc.sort_order ASC, bmc.id ASC
");
$categories_stmt->execute([$business_id]);
$categories = $categories_stmt->fetchAll();

// Get all menu items grouped by category
$items_stmt = $pdo->prepare("
    SELECT bmi.*, bmc.name as category_name
    FROM business_menu_items bmi
    LEFT JOIN business_menu_categories bmc ON bmi.category_id = bmc.id
    WHERE bmi.business_id = ?
    ORDER BY bmc.sort_order ASC, bmi.sort_order ASC, bmi.id ASC
");
$items_stmt->execute([$business_id]);
$all_items = $items_stmt->fetchAll();

// Her ürün için alerjen ID'lerini ekle
foreach ($all_items as &$it) {
    $a_stmt = $pdo->prepare("SELECT allergen_id FROM menu_item_allergens WHERE item_id = ?");
    $a_stmt->execute([$it['id']]);
    $it['allergen_ids'] = $a_stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($it);

// Group items by category
$items_by_category = [];
foreach ($all_items as $item) {
    $cat_id = $item['category_id'] ?? 0;
    if (!isset($items_by_category[$cat_id])) {
        $items_by_category[$cat_id] = [];
    }
    $items_by_category[$cat_id][] = $item;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Menu Manager' : 'Menü Yöneticisi'; ?> - <?php echo htmlspecialchars($business['name']); ?></title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        @keyframes popIn {
            from { opacity: 0; transform: translate(-50%, 20px) scale(0.95); }
            to { opacity: 1; transform: translate(-50%, 0) scale(1); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32">

    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 md:pt-28 max-w-6xl">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="business_panel.php" class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-arrow-left text-slate-600 dark:text-slate-300"></i>
                </a>
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white">
                        <?php echo htmlspecialchars($business['name']); ?>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                        <?php echo $lang == 'en' ? 'Menu Manager' : 'Menü Yöneticisi'; ?>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button onclick="openCategoryModal()" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-105 transition-all">
                    <i class="fas fa-plus"></i>
                    <?php echo $lang == 'en' ? 'Add Category' : 'Kategori Ekle'; ?>
                </button>
                <button onclick="openItemModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-105 transition-all">
                    <i class="fas fa-utensils"></i>
                    <?php echo $lang == 'en' ? 'Add Item' : 'Ürün Ekle'; ?>
                </button>
                <a href="qr_menu.php?business_id=<?php echo $business_id; ?>" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-105 transition-all">
                    <i class="fas fa-qrcode"></i>
                    <?php echo $lang == 'en' ? 'View QR Code' : 'QR Kodu Gör'; ?>
                </a>
                <?php 
                // Check if business has approved subdomain
                $menu_url = 'menu/' . $business_id;
                if (!empty($business['subdomain']) && $business['subdomain_status'] === 'approved') {
                    $menu_url = 'https://' . $business['subdomain'] . '.kalkansocial.com';
                }
                ?>
                <a href="<?php echo $menu_url; ?>" target="_blank" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-105 transition-all">
                    <i class="fas fa-external-link-alt"></i>
                    <?php echo $lang == 'en' ? 'Preview Menu' : 'Menüyü Önizle'; ?>
                </a>
            </div>
        </div>

        <?php if (empty($categories)): ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-lg border border-slate-200 dark:border-slate-700 p-12 text-center">
            <div class="w-20 h-20 bg-violet-50 dark:bg-violet-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-utensils text-violet-500 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-3">
                <?php echo $lang == 'en' ? 'Create Your First Menu' : 'İlk Menünüzü Oluşturun'; ?>
            </h2>
            <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                <?php echo $lang == 'en' ? 'Start by adding categories (e.g., Appetizers, Main Courses, Desserts) then add items to each category' : 'Kategoriler ekleyerek başlayın (örn. Mezeler, Ana Yemekler, Tatlılar) ardından her kategoriye ürün ekleyin'; ?>
            </p>
            <button onclick="openCategoryModal()" class="inline-flex items-center gap-2 bg-gradient-to-r from-violet-500 to-purple-600 text-white px-8 py-4 rounded-xl font-bold hover:shadow-lg hover:shadow-violet-500/30 transition-all">
                <i class="fas fa-plus"></i>
                <?php echo $lang == 'en' ? 'Add First Category' : 'İlk Kategoriyi Ekle'; ?>
            </button>
        </div>
        <?php else: ?>

        <!-- Menu Categories & Items -->
        <div class="space-y-6">
            <?php foreach ($categories as $category): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Category Header -->
                <div class="bg-gradient-to-r from-violet-500 to-purple-600 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-white mb-1">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </h3>
                            <?php if ($category['description']): ?>
                            <p class="text-white/80 text-sm"><?php echo htmlspecialchars($category['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="editCategory(<?php echo $category['id']; ?>)" class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                                <i class="fas fa-edit text-white"></i>
                            </button>
                            <button onclick="deleteCategory(<?php echo $category['id']; ?>)" class="w-10 h-10 rounded-xl bg-white/20 hover:bg-red-500 flex items-center justify-center transition-colors">
                                <i class="fas fa-trash text-white"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="p-6">
                    <?php if (isset($items_by_category[$category['id']]) && !empty($items_by_category[$category['id']])): ?>
                    <div class="space-y-4">
                        <?php foreach ($items_by_category[$category['id']] as $item): ?>
                        <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-violet-300 dark:hover:border-violet-700 transition-colors">
                            <?php if ($item['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-20 h-20 rounded-xl object-cover flex-shrink-0">
                            <?php endif; ?>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-800 dark:text-white text-lg mb-1">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h4>
                                        <?php if ($item['description']): ?>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2">
                                            <?php echo htmlspecialchars($item['description']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="text-xl font-black text-violet-500">
                                            <?php echo $item['price'] ? '₺' . number_format($item['price'], 2) : '-'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-wrap">
                                    <?php if ($item['is_vegetarian']): ?>
                                    <span class="inline-flex items-center gap-1 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2 py-1 rounded-lg text-xs font-bold">
                                        <i class="fas fa-leaf"></i> <?php echo $lang == 'en' ? 'Vegetarian' : 'Vejetaryen'; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($item['is_vegan']): ?>
                                    <span class="inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 px-2 py-1 rounded-lg text-xs font-bold">
                                        <i class="fas fa-seedling"></i> <?php echo $lang == 'en' ? 'Vegan' : 'Vegan'; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($item['is_spicy']): ?>
                                    <span class="inline-flex items-center gap-1 bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-2 py-1 rounded-lg text-xs font-bold">
                                        <i class="fas fa-pepper-hot"></i> <?php echo $lang == 'en' ? 'Spicy' : 'Acılı'; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!$item['is_available']): ?>
                                    <span class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1 rounded-lg text-xs font-bold">
                                        <i class="fas fa-ban"></i> <?php echo $lang == 'en' ? 'Unavailable' : 'Mevcut Değil'; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-2 mt-3">
                                    <button onclick="editItem(<?php echo $item['id']; ?>)" class="text-violet-500 hover:text-violet-600 text-sm font-bold">
                                        <i class="fas fa-edit"></i> <?php echo $lang == 'en' ? 'Edit' : 'Düzenle'; ?>
                                    </button>
                                    <button onclick="deleteItem(<?php echo $item['id']; ?>)" class="text-red-500 hover:text-red-600 text-sm font-bold">
                                        <i class="fas fa-trash"></i> <?php echo $lang == 'en' ? 'Delete' : 'Sil'; ?>
                                    </button>
                                    <button onclick="toggleAvailability(<?php echo $item['id']; ?>, <?php echo $item['is_available'] ? 'false' : 'true'; ?>)" class="text-slate-500 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300 text-sm font-bold">
                                        <i class="fas fa-<?php echo $item['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        <?php echo $item['is_available'] ? ($lang == 'en' ? 'Mark Unavailable' : 'Mevcut Değil Yap') : ($lang == 'en' ? 'Mark Available' : 'Mevcut Yap'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-utensils text-slate-300 dark:text-slate-600 text-3xl mb-3"></i>
                        <p class="text-slate-500 dark:text-slate-400 mb-4">
                            <?php echo $lang == 'en' ? 'No items in this category yet' : 'Bu kategoride henüz ürün yok'; ?>
                        </p>
                        <button onclick="openItemModal(<?php echo $category['id']; ?>)" class="inline-flex items-center gap-2 bg-violet-500 text-white px-4 py-2 rounded-xl font-bold hover:bg-violet-600 transition-all text-sm">
                            <i class="fas fa-plus"></i>
                            <?php echo $lang == 'en' ? 'Add First Item' : 'İlk Ürünü Ekle'; ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

    </main>

    <!-- Category Modal -->
    <div id="categoryModal" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-2xl font-black text-slate-800 dark:text-white">
                    <?php echo $lang == 'en' ? 'Add Category' : 'Kategori Ekle'; ?>
                </h3>
            </div>
            <form id="categoryForm" class="p-6 space-y-4">
                <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
                <input type="hidden" name="category_id" id="category_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇹🇷 <?php echo $lang == 'en' ? 'Category Name (Turkish)' : 'Kategori Adı (Türkçe)'; ?> *
                        </label>
                        <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇬🇧 <?php echo $lang == 'en' ? 'Category Name (English)' : 'Kategori Adı (İngilizce)'; ?>
                        </label>
                        <input type="text" name="name_en" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇹🇷 <?php echo $lang == 'en' ? 'Description (Turkish)' : 'Açıklama (Türkçe)'; ?>
                        </label>
                        <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇬🇧 <?php echo $lang == 'en' ? 'Description (English)' : 'Açıklama (İngilizce)'; ?>
                        </label>
                        <textarea name="description_en" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-slate-700 hover:bg-slate-800 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all">
                        <?php echo $lang == 'en' ? 'Save' : 'Kaydet'; ?>
                    </button>
                    <button type="button" onclick="closeCategoryModal()" class="px-6 py-3 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-300 dark:hover:bg-slate-600 transition-all">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Item Modal -->
    <div id="itemModal" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-2xl font-black text-slate-800 dark:text-white">
                    <?php echo $lang == 'en' ? 'Add Menu Item' : 'Menü Ürünü Ekle'; ?>
                </h3>
            </div>
            <form id="itemForm" class="p-6 space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
                <input type="hidden" name="item_id" id="item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇹🇷 <?php echo $lang == 'en' ? 'Item Name (Turkish)' : 'Ürün Adı (Türkçe)'; ?> *
                        </label>
                        <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇬🇧 <?php echo $lang == 'en' ? 'Item Name (English)' : 'Ürün Adı (İngilizce)'; ?>
                        </label>
                        <input type="text" name="name_en" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                        <?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?> *
                    </label>
                    <select name="category_id" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none">
                        <option value=""><?php echo $lang == 'en' ? 'Select Category' : 'Kategori Seçin'; ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇹🇷 <?php echo $lang == 'en' ? 'Description (Turkish)' : 'Açıklama (Türkçe)'; ?>
                        </label>
                        <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            🇬🇧 <?php echo $lang == 'en' ? 'Description (English)' : 'Açıklama (İngilizce)'; ?>
                        </label>
                        <textarea name="description_en" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none"></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <?php echo $lang == 'en' ? 'Price (₺)' : 'Fiyat (₺)'; ?>
                        </label>
                        <input type="number" name="price" step="0.01" min="0" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <?php echo $lang == 'en' ? 'Image' : 'Görsel'; ?>
                        </label>
                        <input type="file" name="image" accept="image/*" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                </div>
                
                <!-- Allergens -->
                <?php
                // Get allergens
                $allergens_stmt = $pdo->query("SELECT * FROM menu_allergens ORDER BY name_tr");
                $allergens = $allergens_stmt->fetchAll();
                ?>
                <?php if (!empty($allergens)): ?>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">
                        🥜 <?php echo $lang == 'en' ? 'Allergens' : 'Alerjenler'; ?>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-48 overflow-y-auto p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700">
                        <?php foreach ($allergens as $allergen): ?>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-white dark:hover:bg-slate-800 p-2 rounded-lg transition-colors">
                            <input type="checkbox" name="allergens[]" value="<?php echo $allergen['id']; ?>" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-violet-500 focus:ring-violet-500">
                            <i class="fas <?php echo $allergen['icon']; ?> text-orange-500 text-sm"></i>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                <?php echo $lang == 'en' ? htmlspecialchars($allergen['name_en']) : htmlspecialchars($allergen['name_tr']); ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_vegetarian" class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-violet-500 focus:ring-violet-500">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            <?php echo $lang == 'en' ? 'Vegetarian' : 'Vejetaryen'; ?>
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_vegan" class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-violet-500 focus:ring-violet-500">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            <?php echo $lang == 'en' ? 'Vegan' : 'Vegan'; ?>
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_spicy" class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-violet-500 focus:ring-violet-500">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            <?php echo $lang == 'en' ? 'Spicy' : 'Acılı'; ?>
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_available" checked class="w-5 h-5 rounded border-slate-300 dark:border-slate-600 text-violet-500 focus:ring-violet-500">
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            <?php echo $lang == 'en' ? 'Available' : 'Mevcut'; ?>
                        </span>
                    </label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-slate-700 hover:bg-slate-800 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all">
                        <?php echo $lang == 'en' ? 'Save' : 'Kaydet'; ?>
                    </button>
                    <button type="button" onclick="closeItemModal()" class="px-6 py-3 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-300 dark:hover:bg-slate-600 transition-all">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const saveSuccessMsg = '<?php echo $lang == 'en' ? 'Saved successfully!' : 'Başarıyla kaydedildi!'; ?>';
    function showSaveToast() {
        const existing = document.getElementById('menu-save-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'menu-save-toast';
        toast.className = 'fixed bottom-28 left-1/2 -translate-x-1/2 z-[200] flex items-center gap-3 bg-emerald-500 text-white px-6 py-3 rounded-2xl shadow-2xl animate-[popIn_0.3s_ease-out]';
        toast.innerHTML = '<i class="fas fa-check-circle text-xl"></i><span class="font-bold">' + saveSuccessMsg + '</span>';
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, 10px)';
            toast.style.transition = 'all 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }
    // Category Modal Functions
    function openCategoryModal() {
        document.getElementById('categoryModal').classList.remove('hidden');
        document.getElementById('categoryForm').reset();
        document.getElementById('category_id').value = '';
        document.querySelector('#categoryModal h3').textContent = '<?php echo $lang == 'en' ? 'Add Category' : 'Kategori Ekle'; ?>';
    }

    function closeCategoryModal() {
        document.getElementById('categoryModal').classList.add('hidden');
    }

    // Item Modal Functions
    function openItemModal(categoryId = null) {
        document.getElementById('itemModal').classList.remove('hidden');
        document.getElementById('itemForm').reset();
        document.getElementById('item_id').value = '';
        document.querySelector('#itemModal h3').textContent = '<?php echo $lang == 'en' ? 'Add Menu Item' : 'Menü Ürünü Ekle'; ?>';
        if (categoryId) {
            document.querySelector('#itemForm [name="category_id"]').value = categoryId;
        }
    }

    function closeItemModal() {
        document.getElementById('itemModal').classList.add('hidden');
    }

    // Edit Functions
    async function editCategory(id) {
        try {
            const categories = <?php echo json_encode($categories); ?>;
            const category = categories.find(c => c.id == id);
            
            if (category) {
                openCategoryModal(); // önce modal aç (reset yapacak)
                document.getElementById('category_id').value = category.id;
                document.querySelector('#categoryForm [name="name"]').value = category.name || '';
                document.querySelector('#categoryForm [name="name_en"]').value = category.name_en || '';
                document.querySelector('#categoryForm [name="description"]').value = category.description || '';
                document.querySelector('#categoryForm [name="description_en"]').value = category.description_en || '';
                document.querySelector('#categoryModal h3').textContent = '<?php echo $lang == 'en' ? 'Edit Category' : 'Kategori Düzenle'; ?>';
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function editItem(id) {
        try {
            const items = <?php echo json_encode($all_items); ?>;
            const item = items.find(i => i.id == id);
            
            if (item) {
                openItemModal(); // önce modal aç (reset yapacak)
                // sonra düzenleme verilerini doldur
                document.getElementById('item_id').value = item.id;
                document.querySelector('#itemForm [name="name"]').value = item.name || '';
                document.querySelector('#itemForm [name="name_en"]').value = item.name_en || '';
                document.querySelector('#itemForm [name="description"]').value = item.description || '';
                document.querySelector('#itemForm [name="description_en"]').value = item.description_en || '';
                document.querySelector('#itemForm [name="category_id"]').value = item.category_id || '';
                document.querySelector('#itemForm [name="price"]').value = item.price ?? '';
                
                document.querySelectorAll('#itemForm [name="allergens[]"]').forEach(cb => cb.checked = false);
                (item.allergen_ids || []).forEach(aid => {
                    const cb = document.querySelector('#itemForm [name="allergens[]"][value="' + String(aid) + '"]');
                    if (cb) cb.checked = true;
                });
                document.querySelector('#itemForm [name="is_vegetarian"]').checked = item.is_vegetarian == 1;
                document.querySelector('#itemForm [name="is_vegan"]').checked = item.is_vegan == 1;
                document.querySelector('#itemForm [name="is_spicy"]').checked = item.is_spicy == 1;
                document.querySelector('#itemForm [name="is_available"]').checked = item.is_available != 0;
                
                document.querySelector('#itemModal h3').textContent = '<?php echo $lang == 'en' ? 'Edit Menu Item' : 'Menü Ürününü Düzenle'; ?>';
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // Form Submissions
    document.getElementById('categoryForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('api/menu/save_category.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                closeCategoryModal();
                showSaveToast();
                setTimeout(() => location.reload(), 2200);
            } else {
                alert(data.message || 'Error saving category');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error');
        }
    });

    document.getElementById('itemForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('api/menu/save_item.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                closeItemModal();
                showSaveToast();
                setTimeout(() => location.reload(), 2200);
            } else {
                alert(data.message || 'Error saving item');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error');
        }
    });

    // Delete Functions
    async function deleteCategory(id) {
        if (!confirm('<?php echo $lang == 'en' ? 'Delete this category and all its items?' : 'Bu kategoriyi ve tüm ürünlerini silmek istediğinizden emin misiniz?'; ?>')) return;
        
        try {
            const response = await fetch('api/menu/delete_category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category_id: id })
            });
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting category');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error');
        }
    }

    async function deleteItem(id) {
        if (!confirm('<?php echo $lang == 'en' ? 'Delete this item?' : 'Bu ürünü silmek istediğinizden emin misiniz?'; ?>')) return;
        
        try {
            const response = await fetch('api/menu/delete_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: id })
            });
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting item');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error');
        }
    }

    async function toggleAvailability(id, available) {
        try {
            const response = await fetch('api/menu/toggle_availability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: id, is_available: available })
            });
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error updating availability');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error');
        }
    }

    // Close modals on outside click
    document.getElementById('categoryModal').addEventListener('click', (e) => {
        if (e.target.id === 'categoryModal') closeCategoryModal();
    });

    document.getElementById('itemModal').addEventListener('click', (e) => {
        if (e.target.id === 'itemModal') closeItemModal();
    });
    </script>

</body>
</html>
