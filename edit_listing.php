<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/optimize_upload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get listing ID
if (!isset($_GET['id'])) {
    header("Location: marketplace");
    exit();
}

$listing_id = (int)$_GET['id'];

// Fetch listing
$stmt = $pdo->prepare("SELECT * FROM marketplace_listings WHERE id = ? AND user_id = ?");
$stmt->execute([$listing_id, $user_id]);
$listing = $stmt->fetch();

if (!$listing) {
    header("Location: marketplace");
    exit();
}

// Fetch existing images
$img_stmt = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC");
$img_stmt->execute([$listing_id]);
$images = $img_stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $currency = $_POST['currency'];
    $category = $_POST['category'];
    
    // Update listing
    $stmt = $pdo->prepare("UPDATE marketplace_listings SET title = ?, description = ?, price = ?, currency = ?, category = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $description, $price, $currency, $category, $listing_id, $user_id]);
    
    // Handle new images
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_count = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['images']['error'][$i] == 0) {
                // Struct single file
                $file_ary = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
                
                $result = gorselOptimizeEt($file_ary, $upload_dir);
                
                if (isset($result['success'])) {
                    $img_url = '/uploads/' . $result['filename'];
                    $stmt_img = $pdo->prepare("INSERT INTO listing_images (listing_id, image_url, is_primary) VALUES (?, ?, 0)");
                    $stmt_img->execute([$listing_id, $img_url]);
                }
            }
        }
    }
    
    // Handle image deletions
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $img_id) {
            $pdo->prepare("DELETE FROM listing_images WHERE id = ? AND listing_id = ?")->execute([(int)$img_id, $listing_id]);
        }
    }
    
    // Update primary image in main table
    $primary = $pdo->prepare("SELECT image_url FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
    $primary->execute([$listing_id]);
    $primary_url = $primary->fetchColumn();
    if ($primary_url) {
        $pdo->prepare("UPDATE marketplace_listings SET image_url = ? WHERE id = ?")->execute([$primary_url, $listing_id]);
    }
    
    header("Location: listing_detail?id=" . $listing_id . "&status=updated");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['edit_listing'] ?? 'İlanı Düzenle'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex items-center justify-center p-6">

    <div class="max-w-xl w-full bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-200 dark:border-slate-700">
        <div class="flex justify-between items-center mb-6 border-b border-slate-200 dark:border-slate-700 pb-4">
            <h2 class="text-2xl font-bold flex items-center gap-2">
                <i class="fas fa-edit text-blue-500"></i>
                <?php echo $t['edit_listing'] ?? 'İlanı Düzenle'; ?>
            </h2>
            <a href="listing_detail?id=<?php echo $listing_id; ?>" class="text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                <?php echo $t['cancel'] ?? 'İptal'; ?>
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <!-- Category -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['listing_type'] ?? 'Kategori'; ?></label>
                <select name="category" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition-colors">
                    <option value="vehicles" <?php echo $listing['category'] == 'vehicles' ? 'selected' : ''; ?>>🚗 <?php echo $t['mp_vehicles'] ?? 'Araçlar'; ?></option>
                    <option value="electronics" <?php echo $listing['category'] == 'electronics' ? 'selected' : ''; ?>>💻 <?php echo $t['mp_electronics'] ?? 'Elektronik'; ?></option>
                    <option value="home_appliances" <?php echo $listing['category'] == 'home_appliances' ? 'selected' : ''; ?>>🧊 <?php echo $t['mp_home_appliances'] ?? 'Beyaz Eşya'; ?></option>
                    <option value="furniture" <?php echo $listing['category'] == 'furniture' ? 'selected' : ''; ?>>🛋️ <?php echo $t['mp_furniture'] ?? 'Mobilya & Ev'; ?></option>
                    <option value="clothing" <?php echo $listing['category'] == 'clothing' ? 'selected' : ''; ?>>👕 <?php echo $t['mp_clothing'] ?? 'Giyim & Aksesuar'; ?></option>
                    <option value="sports" <?php echo $listing['category'] == 'sports' ? 'selected' : ''; ?>>⚽ <?php echo $t['mp_sports'] ?? 'Spor & Outdoor'; ?></option>
                    <option value="garden" <?php echo $listing['category'] == 'garden' ? 'selected' : ''; ?>>🌱 <?php echo $t['mp_garden'] ?? 'Bahçe & Yapı'; ?></option>
                    <option value="kids" <?php echo $listing['category'] == 'kids' ? 'selected' : ''; ?>>👶 <?php echo $t['mp_kids'] ?? 'Bebek & Çocuk'; ?></option>
                    <option value="hobbies" <?php echo $listing['category'] == 'hobbies' ? 'selected' : ''; ?>>🎮 <?php echo $t['mp_hobbies'] ?? 'Hobi & Eğlence'; ?></option>
                    <option value="pets" <?php echo $listing['category'] == 'pets' ? 'selected' : ''; ?>>🐾 <?php echo $t['mp_pets'] ?? 'Evcil Hayvan'; ?></option>
                    <option value="services" <?php echo $listing['category'] == 'services' ? 'selected' : ''; ?>>🔧 <?php echo $t['mp_services'] ?? 'Hizmetler'; ?></option>
                    <option value="other" <?php echo $listing['category'] == 'other' || $listing['category'] == 'item' || $listing['category'] == 'free' ? 'selected' : ''; ?>>📦 <?php echo $t['mp_other'] ?? 'Diğer'; ?></option>
                </select>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['listing_title'] ?? 'Başlık'; ?></label>
                <input type="text" name="title" required value="<?php echo htmlspecialchars($listing['title']); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition-colors">
            </div>

            <!-- Price -->
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['price'] ?? 'Fiyat'; ?></label>
                    <input type="number" step="0.01" name="price" value="<?php echo $listing['price']; ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['currency'] ?? 'Para Birimi'; ?></label>
                    <select name="currency" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition-colors">
                        <option value="TRY" <?php echo $listing['currency'] == 'TRY' ? 'selected' : ''; ?>>TRY ₺</option>
                        <option value="GBP" <?php echo $listing['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP £</option>
                        <option value="EUR" <?php echo $listing['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR €</option>
                        <option value="USD" <?php echo $listing['currency'] == 'USD' ? 'selected' : ''; ?>>USD $</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['listing_desc'] ?? 'Açıklama'; ?></label>
                <textarea name="description" rows="4" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition-colors"><?php echo htmlspecialchars($listing['description']); ?></textarea>
            </div>

            <!-- Existing Images -->
            <?php if(!empty($images)): ?>
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $lang == 'tr' ? 'Mevcut Görseller' : 'Current Images'; ?></label>
                <div class="flex gap-3 flex-wrap">
                    <?php foreach($images as $img): ?>
                    <div class="relative group">
                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>" class="w-20 h-20 object-cover rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity">
                            <input type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>" class="sr-only peer">
                            <i class="fas fa-times text-white text-xs peer-checked:hidden"></i>
                            <i class="fas fa-check text-white text-xs hidden peer-checked:inline"></i>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-slate-400 mt-2"><i class="fas fa-info-circle mr-1"></i><?php echo $lang == 'tr' ? 'Silmek için görselin üzerine tıklayın' : 'Click on image to mark for deletion'; ?></p>
            </div>
            <?php endif; ?>

            <!-- New Images -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $lang == 'tr' ? 'Yeni Görsel Ekle' : 'Add New Images'; ?></label>
                <input type="file" name="images[]" multiple accept="image/*" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 transition-colors text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition-all mt-4 flex items-center justify-center gap-2">
                <i class="fas fa-save"></i>
                <?php echo $t['update_listing'] ?? 'Güncelle'; ?>
            </button>
        </form>
    </div>

</body>
</html>
