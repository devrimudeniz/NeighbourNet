<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/optimize_upload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $currency = $_POST['currency'];
    $category = $_POST['category'];
    $user_id = $_SESSION['user_id'];
    
    // Image Upload (Multiple)
    $primary_image = '';
    
    // Insert Listing into Database (Status: Pending)
    $status = 'pending';
    $stmt = $pdo->prepare("INSERT INTO marketplace_listings (user_id, title, description, price, currency, category, status, created_at, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");
    if ($stmt->execute([$user_id, $title, $description, $price, $currency, $category, $status])) {
        $listing_id = $pdo->lastInsertId();
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_count = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['images']['error'][$i] == 0) {
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
                        
                        // Set first image as primary
                        $is_primary = ($i == 0) ? 1 : 0;
                        if ($is_primary) $primary_image = $img_url;
                        
                        // Insert into listing_images
                        $stmt_img = $pdo->prepare("INSERT INTO listing_images (listing_id, image_url, is_primary) VALUES (?, ?, ?)");
                        $stmt_img->execute([$listing_id, $img_url, $is_primary]);
                    }
                }
            }
            
            // Update primary image in main table
            if ($primary_image) {
                $stmt_upd = $pdo->prepare("UPDATE marketplace_listings SET image_url = ? WHERE id = ?");
                $stmt_upd->execute([$primary_image, $listing_id]);
            }
        }
    }
    
    header("Location: marketplace?pending=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['new_listing']; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex items-center justify-center p-6">

    <div class="max-w-xl w-full bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-200 dark:border-slate-700">
        <div class="flex justify-between items-center mb-6 border-b border-slate-200 dark:border-slate-700 pb-4">
            <h2 class="text-2xl font-bold"><?php echo $t['create_listing']; ?></h2>
            <a href="marketplace" class="text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors"><?php echo $t['cancel']; ?></a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <!-- Category -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['listing_type'] ?? 'Kategori'; ?></label>
                <select name="category" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-green-500 transition-colors">
                    <option value=""><?php echo $t['mp_all_categories'] ?? 'Kategori Seçin'; ?>...</option>
                    <option value="vehicles">🚗 <?php echo $t['mp_vehicles'] ?? 'Araçlar'; ?></option>
                    <option value="electronics">💻 <?php echo $t['mp_electronics'] ?? 'Elektronik'; ?></option>
                    <option value="home_appliances">🧊 <?php echo $t['mp_home_appliances'] ?? 'Beyaz Eşya'; ?></option>
                    <option value="furniture">🛋️ <?php echo $t['mp_furniture'] ?? 'Mobilya & Ev'; ?></option>
                    <option value="clothing">👕 <?php echo $t['mp_clothing'] ?? 'Giyim & Aksesuar'; ?></option>
                    <option value="sports">⚽ <?php echo $t['mp_sports'] ?? 'Spor & Outdoor'; ?></option>
                    <option value="garden">🌱 <?php echo $t['mp_garden'] ?? 'Bahçe & Yapı'; ?></option>
                    <option value="kids">👶 <?php echo $t['mp_kids'] ?? 'Bebek & Çocuk'; ?></option>
                    <option value="hobbies">🎮 <?php echo $t['mp_hobbies'] ?? 'Hobi & Eğlence'; ?></option>
                    <option value="pets">🐾 <?php echo $t['mp_pets'] ?? 'Evcil Hayvan'; ?></option>
                    <option value="services">🔧 <?php echo $t['mp_services'] ?? 'Hizmetler'; ?></option>
                    <option value="other">📦 <?php echo $t['mp_other'] ?? 'Diğer'; ?></option>
                </select>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['listing_title']; ?></label>
                <input type="text" name="title" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-green-500 transition-colors" placeholder="<?php echo $t['placeholder_title']; ?>">
            </div>

            <!-- Price -->
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['price']; ?></label>
                    <input type="number" step="0.01" name="price" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-green-500 transition-colors" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['currency']; ?></label>
                    <select name="currency" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-green-500 transition-colors">
                        <option value="TRY">TRY ₺</option>
                        <option value="GBP">GBP £</option>
                        <option value="EUR">EUR €</option>
                        <option value="USD">USD $</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['listing_desc']; ?></label>
                <textarea name="description" rows="4" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-green-500 transition-colors" placeholder="<?php echo $t['placeholder_desc']; ?>"></textarea>
            </div>

            <!-- Images -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2"><?php echo $t['listing_images']; ?></label>
                <input type="file" name="images[]" multiple accept="image/*" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-green-500 transition-colors text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-green-600 file:text-white hover:file:bg-green-500">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition-all mt-4">
                <?php echo $t['publish']; ?>
            </button>
        </form>
    </div>

</body>
</html>
