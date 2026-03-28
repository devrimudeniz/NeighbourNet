<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/optimize_upload.php';
session_start();

// PERMISSION CHECK: Only verified_business can add listings
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

if (!isset($_SESSION['badge']) || !in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])) {
    header('Location: directory?error=permission');
    exit();
}

$success = false;
$error = '';

// Check if user already has a business (SPAM PROTECTION - VIP users can add multiple)
$is_vip = in_array($_SESSION['badge'] ?? '', ['vip_business', 'founder', 'moderator']);
if (!$is_vip) {
    $check_stmt = $pdo->prepare("SELECT id FROM business_listings WHERE owner_id = ?");
    $check_stmt->execute([$_SESSION['user_id']]);
    if ($check_stmt->fetch()) {
        $error = $lang == 'en' 
            ? 'You already have a business listing. Upgrade to VIP Business to add multiple businesses.' 
            : 'Zaten bir işletme kaydınız var. Birden fazla işletme eklemek için VIP Business\'a yükseltin.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    // VALIDATION
    if (!$name || !$category || !$description || !$address || !$phone) {
        $error = $lang == 'en' 
            ? 'Please fill all required fields (Name, Category, Description, Address, Phone)' 
            : 'Lütfen tüm zorunlu alanları doldurun (Ad, Kategori, Açıklama, Adres, Telefon)';
    }
    
    // IMAGE UPLOAD (REQUIRED)
    $cover_photo = null;
    if (!isset($_FILES['cover_photo']) || $_FILES['cover_photo']['error'] != 0) {
        $error = $lang == 'en' 
            ? 'Cover photo is required. Please upload an image of your business.' 
            : 'Kapak fotoğrafı zorunludur. Lütfen işletmenizin bir fotoğrafını yükleyin.';
    } else {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['cover_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = $lang == 'en' 
                ? 'Invalid image format. Allowed: JPG, PNG, WEBP' 
                : 'Geçersiz resim formatı. İzin verilenler: JPG, PNG, WEBP';
        } else {
            // Upload image using optimizer
            $upload_dir = 'uploads/businesses/';
            $result = gorselOptimizeEt($_FILES['cover_photo'], $upload_dir);
            
            if (isset($result['success'])) {
                $cover_photo = $result['path'];
            } else {
                $error = $lang == 'en' 
                    ? 'Image upload failed. ' . ($result['error'] ?? '')
                    : 'Resim yükleme başarısız. ' . ($result['error'] ?? '');
            }
        }
    }
    
    // INSERT if no errors
    if (!$error && $cover_photo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO business_listings 
                (owner_id, name, category, description, address, phone, website, cover_photo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $name, 
                $category, 
                $description, 
                $address, 
                $phone, 
                $website, 
                $cover_photo
            ]);
            
            $success = true;
            $business_id = $pdo->lastInsertId();
            
            // Save menu items if provided (for restaurant, cafe, bar)
            if (in_array($category, ['restaurant', 'cafe', 'bar']) && isset($_POST['menu_names'])) {
                $menu_names = $_POST['menu_names'];
                $menu_prices = $_POST['menu_prices'] ?? [];
                $menu_currencies = $_POST['menu_currencies'] ?? [];
                
                $menu_stmt = $pdo->prepare("INSERT INTO business_menu_items (business_id, name, price, currency) VALUES (?, ?, ?, ?)");
                
                for ($i = 0; $i < count($menu_names); $i++) {
                    $item_name = trim($menu_names[$i] ?? '');
                    $item_price = floatval($menu_prices[$i] ?? 0);
                    $item_currency = $menu_currencies[$i] ?? 'TRY';
                    
                    if (!empty($item_name)) {
                        $menu_stmt->execute([$business_id, $item_name, $item_price, $item_currency]);
                    }
                }
            }

            // JOIN TABLES - CATEGORIES
            // 1. Insert Primary Category
            $cat_stmt = $pdo->prepare("INSERT IGNORE INTO business_categories (business_id, category) VALUES (?, ?)");
            $cat_stmt->execute([$business_id, $category]);

            // 2. Insert Additional Categories
            if (isset($_POST['additional_categories']) && is_array($_POST['additional_categories'])) {
                foreach ($_POST['additional_categories'] as $add_cat) {
                    if ($add_cat !== $category) { // Avoid duplicate of primary
                         $cat_stmt->execute([$business_id, $add_cat]);
                    }
                }
            }
            

            
            // Save GALLERY PHOTOS (Additional photos for slider)
            if (isset($_FILES['gallery_photos'])) {
                $gallery_upload_dir = 'uploads/businesses/gallery/';
                if (!is_dir($gallery_upload_dir)) {
                    mkdir($gallery_upload_dir, 0777, true);
                }
                
                $gallery_stmt = $pdo->prepare("INSERT INTO business_photos (business_id, user_id, photo_url) VALUES (?, ?, ?)");
                
                $files = $_FILES['gallery_photos'];
                $file_count = count($files['name']);
                
                // Limit to 5 files
                if ($file_count > 5) {
                    $file_count = 5;
                }

                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] === 0) {
                        $file_ary = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        $result = gorselOptimizeEt($file_ary, $gallery_upload_dir);
                        
                        if (isset($result['success'])) {
                            $gallery_stmt->execute([$business_id, $_SESSION['user_id'], $result['path']]);
                        }
                    }
                }
            }
            
            // Save MENU PHOTOS if provided (for restaurant, cafe, bar)
            if (in_array($category, ['restaurant', 'cafe', 'bar']) && isset($_FILES['menu_photos'])) {
                $menu_upload_dir = 'uploads/businesses/menus/';
                if (!is_dir($menu_upload_dir)) {
                    mkdir($menu_upload_dir, 0777, true);
                }
                
                $menu_img_stmt = $pdo->prepare("INSERT INTO business_menu_images (business_id, image_path) VALUES (?, ?)");
                
                $files = $_FILES['menu_photos'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === 0) {
                        // Construct single file array for the helper
                        $file_ary = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        $result = gorselOptimizeEt($file_ary, $menu_upload_dir);
                        
                        if (isset($result['success'])) {
                            $menu_img_stmt->execute([$business_id, $result['path']]);
                        }
                    }
                }
            }
            
            // Redirect to business detail
            header("Location: business_detail?id=$business_id");
            exit();
        } catch (PDOException $e) {
            $error = $lang == 'en' 
                ? 'Error adding business. Please try again.' 
                : 'İşletme eklenirken hata oluştu. Lütfen tekrar deneyin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['add_business']; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <script>
        // Force light mode
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    </script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-2xl">
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 border border-white/20 dark:border-slate-800/50">
            <h1 class="text-3xl font-extrabold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                <?php echo $t['add_business']; ?>
            </h1>

            <?php if ($error): ?>
            <div class="bg-red-100 dark:bg-red-900/30 border border-red-500 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl mb-6">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- COVER PHOTO (REQUIRED) -->
                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Cover Photo' : 'Kapak Fotoğrafı'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="cover_photo" accept="image/*" required 
                           class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <p class="text-xs text-slate-500 mt-2">
                        <?php echo $lang == 'en' 
                            ? 'Upload a high-quality photo of your business. JPG, PNG, or WEBP. Max 5MB.' 
                            : 'İşletmenizin yüksek kaliteli bir fotoğrafını yükleyin. JPG, PNG veya WEBP. Maks 5MB.'; ?>
                    </p>
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Business Name' : 'İşletme Adı'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <select name="category" id="categorySelect" required class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500" onchange="toggleMenuSection()">
                        <option value=""><?php echo $lang == 'en' ? 'Select category...' : 'Kategori seçin...'; ?></option>
                        <option value="restaurant">🍽️ <?php echo $t['restaurant']; ?></option>
                        <option value="bar">🍹 <?php echo $t['bar']; ?></option>
                        <option value="hotel">🏨 <?php echo $t['hotel']; ?></option>
                        <option value="cafe">☕ <?php echo $t['cafe']; ?></option>
                        <option value="activity">🎯 <?php echo $lang == 'en' ? 'Activity' : 'Aktivite'; ?></option>
                        <option value="shop">🛍️ <?php echo $lang == 'en' ? 'Shop' : 'Dükkan'; ?></option>
                        <option value="service">🔧 <?php echo $lang == 'en' ? 'Service' : 'Hizmet'; ?></option>
                        <option value="health">🏥 <?php echo $lang == 'en' ? 'Health' : 'Sağlık'; ?></option>
                        <option value="nomad">💻 <?php echo $t['nomad']; ?></option>
                        <option value="other">🏪 <?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?></option>
                    </select>
                </div>

                <!-- Additional Categories -->
                <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                    <label class="block font-bold mb-3 text-sm text-slate-700 dark:text-slate-300">
                        <?php echo $lang == 'en' ? 'Additional Categories (Optional)' : 'Ek Kategoriler (İsteğe Bağlı)'; ?>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php 
                        $cats = [
                            'restaurant' =>['icon'=>'🍽️', 'name'=>$t['restaurant']],
                            'bar' => ['icon'=>'🍹', 'name'=>$t['bar']],
                            'hotel' => ['icon'=>'🏨', 'name'=>$t['hotel']],
                            'cafe' => ['icon'=>'☕', 'name'=>$t['cafe']],
                            'activity' => ['icon'=>'🎯', 'name'=>$lang=='en'?'Activity':'Aktivite'],
                            'shop' => ['icon'=>'🛍️', 'name'=>$lang=='en'?'Shop':'Dükkan'],
                            'service' => ['icon'=>'🔧', 'name'=>$lang=='en'?'Service':'Hizmet'],
                            'health' => ['icon'=>'🏥', 'name'=>$lang=='en'?'Health':'Sağlık'],
                            'nomad' => ['icon'=>'💻', 'name'=>$t['nomad']]
                        ];
                        foreach($cats as $k => $v): ?>
                        <label class="flex items-center space-x-2 text-sm cursor-pointer hover:bg-white dark:hover:bg-slate-700 p-2 rounded-lg transition-colors">
                            <input type="checkbox" name="additional_categories[]" value="<?php echo $k; ?>" class="rounded text-pink-500 focus:ring-pink-500">
                            <span><?php echo $v['icon'] . ' ' . $v['name']; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        <?php echo $lang == 'en' ? 'Select all that apply to your business.' : 'İşletmenize uyan diğer kategorileri seçin.'; ?>
                    </p>
                </div>

                <!-- GALLERY PHOTOS -->
                <div class="mt-4 border-2 border-dashed border-blue-300 dark:border-blue-700 rounded-xl p-6 bg-blue-50 dark:bg-blue-900/20">
                    <label class="block font-bold mb-3 flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <i class="fas fa-images"></i>
                        <?php echo $lang == 'en' ? 'Gallery Photos (Slider)' : 'Galeri Fotoğrafları (Slider)'; ?>
                    </label>
                    <p class="text-xs text-slate-500 mb-4">
                        <?php echo $lang == 'en' 
                            ? 'Upload multiple photos to appear in the top slider. Max 5MB per image.' 
                            : 'Üst sliderda görünecek çoklu fotoğrafları yükleyin. Resim başına maks 5MB.'; ?>
                    </p>
                    
                    <input type="file" name="gallery_photos[]" multiple accept="image/*" 
                           onchange="document.getElementById('galleryFileCount').innerText = this.files.length + ' <?php echo $lang == 'en' ? 'files selected' : 'dosya seçildi'; ?>'"
                           class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="galleryFileCount" class="text-xs text-blue-600 font-bold mt-2 ml-2"></p>
                </div>

                <!-- Menu Section (only for restaurant, cafe, bar) -->
                <div id="menuSection" class="hidden">
                    <div class="border-2 border-dashed border-pink-300 dark:border-pink-700 rounded-xl p-6 bg-pink-50 dark:bg-pink-900/20">
                        <label class="block font-bold mb-3 flex items-center gap-2 text-pink-600 dark:text-pink-400">
                            <i class="fas fa-utensils"></i>
                            <?php echo $lang == 'en' ? 'Menu Items (Optional)' : 'Menü Öğeleri (İsteğe Bağlı)'; ?>
                        </label>
                        <p class="text-xs text-slate-500 mb-4">
                            <?php echo $lang == 'en' ? 'Add your menu items. You can add more later from your business dashboard.' : 'Menü öğelerinizi ekleyin. Daha sonra işletme panelinizden ekleyebilirsiniz.'; ?>
                        </p>
                        
                        <div id="menuItems" class="space-y-3">
                            <div class="menu-item flex gap-2">
                                <input type="text" name="menu_names[]" placeholder="<?php echo $lang == 'en' ? 'Item name (e.g. Margherita Pizza)' : 'Ürün adı (örn. Margherita Pizza)'; ?>" class="flex-1 px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
                                <input type="number" name="menu_prices[]" placeholder="<?php echo $lang == 'en' ? 'Price' : 'Fiyat'; ?>" step="0.01" class="w-24 px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
                                <select name="menu_currencies[]" class="w-20 px-2 py-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
                                    <option value="TRY">₺</option>
                                    <option value="GBP">£</option>
                                    <option value="EUR">€</option>
                                    <option value="USD">$</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="button" onclick="addMenuItem()" class="mt-3 w-full py-2 border-2 border-dashed border-pink-400 text-pink-600 rounded-xl font-bold hover:bg-pink-100 dark:hover:bg-pink-900/30 transition-colors text-sm">
                            <i class="fas fa-plus mr-2"></i><?php echo $lang == 'en' ? 'Add Another Item' : 'Başka Öğe Ekle'; ?>
                        </button>
                    </div>

                    <!-- Menu Photos Upload -->
                    <div class="mt-4 border-2 border-dashed border-purple-300 dark:border-purple-700 rounded-xl p-6 bg-purple-50 dark:bg-purple-900/20">
                        <label class="block font-bold mb-3 flex items-center gap-2 text-purple-600 dark:text-purple-400">
                            <i class="fas fa-images"></i>
                            <?php echo $lang == 'en' ? 'Menu Photos' : 'Menü Fotoğrafları'; ?>
                        </label>
                        <p class="text-xs text-slate-500 mb-4">
                            <?php echo $lang == 'en' ? 'Upload photos of your menu pages. Max 5MB per image.' : 'Menü sayfalarınızın fotoğraflarını yükleyin. Resim başına maks 5MB.'; ?>
                        </p>
                        
                        <input type="file" id="menuPhotosInput" name="menu_photos[]" multiple accept="image/*" 
                               onchange="document.getElementById('fileCount').innerText = this.files.length + ' <?php echo $lang == 'en' ? 'files selected' : 'dosya seçildi'; ?>'"
                               class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <p id="fileCount" class="text-xs text-purple-600 font-bold mt-2 ml-2"></p>
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Description' : 'Açıklama'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" rows="4" required class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500" placeholder="<?php echo $lang == 'en' ? 'Tell customers about your business...' : 'Müşterilere işletmeniz hakkında bilgi verin...'; ?>"></textarea>
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Address' : 'Adres'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="address" required class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Phone' : 'Telefon'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="phone" required class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <div>
                    <label class="block font-bold mb-2"><?php echo $lang == 'en' ? 'Website (Optional)' : 'Web Sitesi (İsteğe Bağlı)'; ?></label>
                    <input type="url" name="website" placeholder="https://" class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <?php if($is_vip): ?>
                <!-- VIP User Message -->
                <div class="bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 border border-amber-400 text-amber-700 dark:text-amber-400 px-4 py-3 rounded-xl">
                    <p class="text-sm font-bold flex items-center gap-2">
                        <i class="fas fa-crown text-amber-500"></i>
                        <?php echo $lang == 'en' ? 'VIP Business Member' : 'VIP Business Üyesi'; ?>
                    </p>
                    <p class="text-sm mt-1">
                        <?php echo $lang == 'en' 
                            ? 'You can add unlimited businesses. Enjoy your VIP benefits!' 
                            : 'Sınırsız işletme ekleyebilirsiniz. VIP ayrıcalıklarınızın keyfini çıkarın!'; ?>
                    </p>
                </div>
                <?php else: ?>
                <!-- Standard User Warning -->
                <div class="bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-500 text-yellow-700 dark:text-yellow-400 px-4 py-3 rounded-xl">
                    <p class="text-sm font-bold mb-2">
                        <?php echo $lang == 'en' ? '⚠️ Important:' : '⚠️ Önemli:'; ?>
                    </p>
                    <p class="text-sm mb-3">
                        <?php echo $lang == 'en' 
                            ? 'You can only add ONE business per account. Make sure all information is accurate before submitting.' 
                            : 'Her hesap için sadece BİR işletme ekleyebilirsiniz. Göndermeden önce tüm bilgilerin doğru olduğundan emin olun.'; ?>
                    </p>
                    <a href="vip_business" class="inline-flex items-center gap-2 text-sm font-bold text-amber-600 hover:text-amber-700">
                        <i class="fas fa-crown"></i>
                        <?php echo $lang == 'en' ? 'Upgrade to VIP for unlimited businesses →' : 'Sınırsız işletme için VIP\'e yükseltin →'; ?>
                    </a>
                </div>
                <?php endif; ?>

                <button type="submit" class="w-full bg-gradient-to-r from-pink-500 to-violet-500 text-white py-3 rounded-xl font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                    <?php echo $t['add_business']; ?>
                </button>
            </form>
        </div>
    </main>

    <script>
        // Force light mode
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        function toggleTheme() { alert('Bu sayfa sadece açık tema destekler.'); }
        
        // Toggle menu section based on category
        function toggleMenuSection() {
            const category = document.getElementById('categorySelect').value;
            const menuSection = document.getElementById('menuSection');
            
            if (['restaurant', 'cafe', 'bar'].includes(category)) {
                menuSection.classList.remove('hidden');
            } else {
                menuSection.classList.add('hidden');
            }
        }
        
        // Add new menu item row
        function addMenuItem() {
            const container = document.getElementById('menuItems');
            const newItem = document.createElement('div');
            newItem.className = 'menu-item flex gap-2';
            newItem.innerHTML = `
                <input type="text" name="menu_names[]" placeholder="<?php echo $lang == 'en' ? 'Item name' : 'Ürün adı'; ?>" class="flex-1 px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
                <input type="number" name="menu_prices[]" placeholder="<?php echo $lang == 'en' ? 'Price' : 'Fiyat'; ?>" step="0.01" class="w-24 px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
                <select name="menu_currencies[]" class="w-20 px-2 py-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
                    <option value="TRY">₺</option>
                    <option value="GBP">£</option>
                    <option value="EUR">€</option>
                    <option value="USD">$</option>
                </select>
                <button type="button" onclick="this.parentElement.remove()" class="px-3 text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(newItem);
        }
    </script>

</body>
</html>
