<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/optimize_upload.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get business details
$stmt = $pdo->prepare("SELECT * FROM business_listings WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

if (!$business) {
    header('Location: directory');
    exit();
}

// Check if current user is the owner
if ($business['owner_id'] != $_SESSION['user_id']) {
    // Allow moderators and founders to edit
    if (!isset($_SESSION['badge']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
        header('Location: business_detail?id=' . $business_id . '&error=permission');
        exit();
    }
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    // Process opening hours
    $opening_hours = [];
    $opening_hours['is_24_7'] = isset($_POST['is_24_7']);
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    foreach ($days as $day) {
        $opening_hours[$day] = [
            'open' => $_POST['hours_open_' . $day] ?? '09:00',
            'close' => $_POST['hours_close_' . $day] ?? '22:00',
            'closed' => isset($_POST['hours_closed_' . $day])
        ];
    }
    $opening_hours_json = json_encode($opening_hours);
    
    // VALIDATION
    if (!$name || !$category || !$description || !$address || !$phone) {
        $error = $lang == 'en' 
            ? 'Please fill all required fields' 
            : 'Lütfen tüm zorunlu alanları doldurun';
    }
    
    // Check for new cover photo
    $cover_photo = $business['cover_photo']; // Keep existing by default
    
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['cover_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = $lang == 'en' 
                ? 'Invalid image format. Allowed: JPG, PNG, WEBP' 
                : 'Geçersiz resim formatı. İzin verilenler: JPG, PNG, WEBP';
        } else {
            $upload_dir = 'uploads/businesses/';
            $result = gorselOptimizeEt($_FILES['cover_photo'], $upload_dir);
            
            if (isset($result['success'])) {
                $cover_photo = $result['path'];
            } else {
                $error = $lang == 'en' 
                    ? 'Image upload failed.' 
                    : 'Resim yükleme başarısız.';
            }
        }
    }
    
    // UPDATE if no errors
    if (!$error) {
        try {
            $stmt = $pdo->prepare("
                UPDATE business_listings 
                SET name = ?, category = ?, description = ?, address = ?, phone = ?, website = ?, cover_photo = ?, opening_hours = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, 
                $category, 
                $description, 
                $address, 
                $phone, 
                $website, 
                $cover_photo,
                $opening_hours_json,
                $business_id
            ]);
            
            // JOIN TABLES - UPDATE CATEGORIES
            // 1. Clear existing
            $pdo->prepare("DELETE FROM business_categories WHERE business_id = ?")->execute([$business_id]);
            
            // 2. Insert Primary
            $cat_stmt = $pdo->prepare("INSERT IGNORE INTO business_categories (business_id, category) VALUES (?, ?)");
            $cat_stmt->execute([$business_id, $category]);
            
            // 3. Insert Additional
            if (isset($_POST['additional_categories']) && is_array($_POST['additional_categories'])) {
                foreach ($_POST['additional_categories'] as $add_cat) {
                    if ($add_cat !== $category) {
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
            
            $success = true;
            
            // Refresh business data
            $stmt = $pdo->prepare("SELECT * FROM business_listings WHERE id = ?");
            $stmt->execute([$business_id]);
            $business = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = $lang == 'en' 
                ? 'Error updating business: ' . $e->getMessage()
                : 'İşletme güncellenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Get menu photos
$menu_stmt = $pdo->prepare("SELECT * FROM business_menu_images WHERE business_id = ? ORDER BY created_at ASC");
$menu_stmt->execute([$business_id]);
$menu_photos = $menu_stmt->fetchAll();

// Get gallery photos (only owner's photos)
$gallery_stmt = $pdo->prepare("SELECT * FROM business_photos WHERE business_id = ? AND user_id = ? ORDER BY created_at DESC");
$gallery_stmt->execute([$business_id, $business['owner_id']]);
$gallery_photos = $gallery_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Edit Business' : 'İşletmeyi Düzenle'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <script>
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    </script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-2xl">
        <div class="bg-white/70 backdrop-blur-xl rounded-2xl p-8 border border-white/20">
            
            <!-- Back Button -->
            <a href="business_detail?id=<?php echo $business_id; ?>" class="inline-flex items-center gap-2 text-pink-500 hover:text-pink-600 mb-6">
                <i class="fas fa-arrow-left"></i> <?php echo $lang == 'en' ? 'Back to Business' : 'İşletmeye Dön'; ?>
            </a>
            
            <h1 class="text-3xl font-extrabold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                <?php echo $lang == 'en' ? 'Edit Business' : 'İşletmeyi Düzenle'; ?>
            </h1>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-500 text-green-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $lang == 'en' ? 'Business updated successfully!' : 'İşletme başarıyla güncellendi!'; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-500 text-red-700 px-4 py-3 rounded-xl mb-6">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Current Cover Photo -->
                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Current Cover Photo' : 'Mevcut Kapak Fotoğrafı'; ?>
                    </label>
                    <?php if ($business['cover_photo']): ?>
                    <img src="<?php echo $business['cover_photo']; ?>" class="w-full h-48 object-cover rounded-xl mb-2">
                    <?php endif; ?>
                    <input type="file" name="cover_photo" accept="image/*" 
                           class="w-full px-4 py-3 rounded-xl bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <p class="text-xs text-slate-500 mt-1">
                        <?php echo $lang == 'en' 
                            ? 'Leave empty to keep current photo. Upload new to replace.' 
                            : 'Mevcut fotoğrafı korumak için boş bırakın. Değiştirmek için yeni yükleyin.'; ?>
                    </p>
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Business Name' : 'İşletme Adı'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($business['name']); ?>"
                           class="w-full px-4 py-3 rounded-xl bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <select name="category" required class="w-full px-4 py-3 rounded-xl bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="restaurant" <?php echo $business['category'] == 'restaurant' ? 'selected' : ''; ?>>🍽️ <?php echo $t['restaurant']; ?></option>
                        <option value="bar" <?php echo $business['category'] == 'bar' ? 'selected' : ''; ?>>🍹 <?php echo $t['bar']; ?></option>
                        <option value="hotel" <?php echo $business['category'] == 'hotel' ? 'selected' : ''; ?>>🏨 <?php echo $t['hotel']; ?></option>
                        <option value="cafe" <?php echo $business['category'] == 'cafe' ? 'selected' : ''; ?>>☕ <?php echo $t['cafe']; ?></option>
                        <option value="activity" <?php echo $business['category'] == 'activity' ? 'selected' : ''; ?>>🎯 <?php echo $lang == 'en' ? 'Activity' : 'Aktivite'; ?></option>
                        <option value="shop" <?php echo $business['category'] == 'shop' ? 'selected' : ''; ?>>🛍️ <?php echo $lang == 'en' ? 'Shop' : 'Dükkan'; ?></option>
                        <option value="service" <?php echo $business['category'] == 'service' ? 'selected' : ''; ?>>🔧 <?php echo $lang == 'en' ? 'Service' : 'Hizmet'; ?></option>
                        <option value="health" <?php echo $business['category'] == 'health' ? 'selected' : ''; ?>>🏥 <?php echo $lang == 'en' ? 'Health' : 'Sağlık'; ?></option>
                        <option value="other" <?php echo $business['category'] == 'other' ? 'selected' : ''; ?>>🏪 <?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?></option>
                    </select>
                </div>

                <!-- Additional Categories -->
                <?php 
                // Fetch current categories
                $cat_check_stmt = $pdo->prepare("SELECT category FROM business_categories WHERE business_id = ?");
                $cat_check_stmt->execute([$business_id]);
                $existing_cats = $cat_check_stmt->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl">
                    <label class="block font-bold mb-3 text-sm text-slate-700">
                        <?php echo $lang == 'en' ? 'Additional Categories' : 'Ek Kategoriler'; ?>
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
                        foreach($cats as $k => $v): 
                            $is_checked = in_array($k, $existing_cats);
                            $is_primary = ($k === $business['category']);
                        ?>
                        <label class="flex items-center space-x-2 text-sm cursor-pointer hover:bg-white p-2 rounded-lg transition-colors <?php echo $is_primary ? 'opacity-50' : ''; ?>">
                            <input type="checkbox" name="additional_categories[]" value="<?php echo $k; ?>" 
                                   class="rounded text-pink-500 focus:ring-pink-500"
                                   <?php echo $is_checked ? 'checked' : ''; ?>
                                   <?php echo $is_primary ? 'disabled' : ''; ?>>
                            <span><?php echo $v['icon'] . ' ' . $v['name']; ?> <?php echo $is_primary ? '('.($lang=='en'?'Primary':'Ana').')' : ''; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Gallery Photos Management -->
                <div class="border-2 border-dashed border-blue-300 rounded-xl p-6 bg-blue-50">
                    <label class="block font-bold mb-3 flex items-center gap-2 text-blue-600">
                        <i class="fas fa-images"></i>
                        <?php echo $lang == 'en' ? 'Gallery Photos' : 'Galeri Fotoğrafları'; ?>
                    </label>
                    <p class="text-xs text-slate-500 mb-4">
                        <?php echo $lang == 'en' ? 'These photos will appear in the main slider.' : 'Bu fotoğraflar ana sliderda görünecek.'; ?>
                    </p>
                    
                    <?php if (isset($gallery_photos) && count($gallery_photos) > 0): ?>
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <?php foreach ($gallery_photos as $photo): ?>
                        <div class="relative group">
                            <img src="<?php echo $photo['photo_url']; ?>" class="w-full h-24 object-cover rounded-lg">
                            <a href="api/delete_business_photo.php?id=<?php echo $photo['id']; ?>&business_id=<?php echo $business_id; ?>" 
                               onclick="return confirm('<?php echo $lang == 'en' ? 'Delete this photo?' : 'Bu fotoğrafı sil?'; ?>')"
                               class="absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-times text-xs"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <input type="file" name="gallery_photos[]" multiple accept="image/*" 
                           class="w-full px-4 py-3 rounded-xl bg-white border-none focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-slate-500 mt-1">
                        <?php echo $lang == 'en' ? 'Add more gallery photos' : 'Daha fazla galeri fotoğrafı ekleyin'; ?>
                    </p>
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Description' : 'Açıklama'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" rows="4" required 
                              class="w-full px-4 py-3 rounded-xl bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-pink-500"><?php echo htmlspecialchars($business['description']); ?></textarea>
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Address' : 'Adres'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="address" required value="<?php echo htmlspecialchars($business['address']); ?>"
                           class="w-full px-4 py-3 rounded-xl bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <div>
                    <label class="block font-bold mb-2">
                        <?php echo $lang == 'en' ? 'Phone' : 'Telefon'; ?> 
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="phone" required value="<?php echo htmlspecialchars($business['phone']); ?>"
                           class="w-full px-4 py-3 rounded-xl bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <div>
                    <label class="block font-bold mb-2"><?php echo $lang == 'en' ? 'Website (Optional)' : 'Web Sitesi (İsteğe Bağlı)'; ?></label>
                    <input type="url" name="website" value="<?php echo htmlspecialchars($business['website'] ?? ''); ?>" placeholder="https://"
                           class="w-full px-4 py-3 rounded-xl bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>

                <!-- Operating Hours -->
                <?php 
                $current_hours = [];
                if (!empty($business['opening_hours'])) {
                    $current_hours = json_decode($business['opening_hours'], true) ?: [];
                }
                $days_tr = [
                    'monday' => 'Pazartesi',
                    'tuesday' => 'Salı', 
                    'wednesday' => 'Çarşamba',
                    'thursday' => 'Perşembe',
                    'friday' => 'Cuma',
                    'saturday' => 'Cumartesi',
                    'sunday' => 'Pazar'
                ];
                $days_en = [
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday', 
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday'
                ];
                $day_names = $lang == 'en' ? $days_en : $days_tr;
                ?>
                <div style="border: 2px dashed #22c55e; border-radius: 12px; padding: 24px; background: #f0fdf4;">
                    <label style="display: block; font-weight: bold; margin-bottom: 12px; color: #16a34a; font-size: 16px;">
                        <i class="fas fa-clock" style="margin-right: 8px;"></i>
                        <?php echo $lang == 'en' ? 'Operating Hours' : 'Çalışma Saatleri'; ?>
                    </label>
                    
                    <!-- 24/7 Option -->
                    <?php $is_24_7 = ($current_hours['is_24_7'] ?? false); ?>
                    <label style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: linear-gradient(135deg, #fbbf24, #f59e0b); border-radius: 10px; margin-bottom: 16px; cursor: pointer; color: white; font-weight: bold;">
                        <input type="checkbox" name="is_24_7" id="is24_7" <?php echo $is_24_7 ? 'checked' : ''; ?>
                               style="width: 20px; height: 20px;" onchange="toggle24_7(this.checked)">
                        <span style="font-size: 16px;">🌙 <?php echo $lang == 'en' ? 'Open 24/7 (Always Open)' : '7/24 Açık (Her Zaman Açık)'; ?></span>
                    </label>
                    
                    <div id="hoursDetail" style="<?php echo $is_24_7 ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                    <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">
                        <?php echo $lang == 'en' ? 'Set your business hours for each day' : 'Her gün için çalışma saatlerinizi ayarlayın'; ?>
                    </p>
                    
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($day_names as $day_key => $day_label): 
                            $day_data = $current_hours[$day_key] ?? ['open' => '09:00', 'close' => '22:00', 'closed' => false];
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: white; border-radius: 8px;">
                            <span style="width: 100px; font-weight: 600; font-size: 14px;"><?php echo $day_label; ?></span>
                            
                            <input type="time" name="hours_open_<?php echo $day_key; ?>" 
                                   value="<?php echo htmlspecialchars($day_data['open']); ?>"
                                   style="padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 14px;">
                            
                            <span style="color: #64748b;">-</span>
                            
                            <input type="time" name="hours_close_<?php echo $day_key; ?>" 
                                   value="<?php echo htmlspecialchars($day_data['close']); ?>"
                                   style="padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 14px;">
                            
                            <label style="display: flex; align-items: center; gap: 6px; margin-left: auto; font-size: 13px; color: #ef4444; cursor: pointer;">
                                <input type="checkbox" name="hours_closed_<?php echo $day_key; ?>" 
                                       <?php echo ($day_data['closed'] ?? false) ? 'checked' : ''; ?>
                                       style="width: 16px; height: 16px;">
                                <?php echo $lang == 'en' ? 'Closed' : 'Kapalı'; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    </div><!-- End hoursDetail -->
                </div>

                <!-- Menu Photos (for restaurant, cafe, bar) -->
                <?php if (in_array($business['category'], ['restaurant', 'cafe', 'bar'])): ?>
                <div class="border-2 border-dashed border-purple-300 rounded-xl p-6 bg-purple-50">
                    <label class="block font-bold mb-3 flex items-center gap-2 text-purple-600">
                        <i class="fas fa-images"></i>
                        <?php echo $lang == 'en' ? 'Menu Photos' : 'Menü Fotoğrafları'; ?>
                    </label>
                    
                    <?php if (count($menu_photos) > 0): ?>
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <?php foreach ($menu_photos as $menu): ?>
                        <div class="relative group">
                            <img src="<?php echo $menu['image_path']; ?>" class="w-full h-24 object-cover rounded-lg">
                            <a href="api/delete_menu_photo.php?id=<?php echo $menu['id']; ?>&business_id=<?php echo $business_id; ?>" 
                               onclick="return confirm('<?php echo $lang == 'en' ? 'Delete this photo?' : 'Bu fotoğrafı sil?'; ?>')"
                               class="absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-times text-xs"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <input type="file" name="menu_photos[]" multiple accept="image/*" 
                           class="w-full px-4 py-3 rounded-xl bg-white border-none focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-slate-500 mt-1">
                        <?php echo $lang == 'en' ? 'Add more menu photos' : 'Daha fazla menü fotoğrafı ekleyin'; ?>
                    </p>
                </div>
                <?php endif; ?>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-pink-500 to-violet-500 text-white py-3 rounded-xl font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $lang == 'en' ? 'Save Changes' : 'Değişiklikleri Kaydet'; ?>
                    </button>
                    <a href="business_detail?id=<?php echo $business_id; ?>" class="px-6 py-3 rounded-xl font-bold bg-slate-200 hover:bg-slate-300 transition-colors flex items-center">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        function toggleTheme() { alert('Bu sayfa sadece açık tema destekler.'); }
        
        function toggle24_7(checked) {
            const detail = document.getElementById('hoursDetail');
            if (checked) {
                detail.style.opacity = '0.5';
                detail.style.pointerEvents = 'none';
            } else {
                detail.style.opacity = '1';
                detail.style.pointerEvents = 'auto';
            }
        }
    </script>

</body>
</html>
