<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/optimize_upload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Check if user is a verified captain OR admin
$u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$u_stmt->execute([$_SESSION['user_id']]);
$user_badge = $u_stmt->fetchColumn();

// Only captains, founders, and moderators can add boat trips
$allowed_badges = ['captain', 'founder', 'moderator'];
if (!in_array($user_badge, $allowed_badges)) {
    // Redirect to captain verification request
    header("Location: request_verification?type=captain");
    exit();
}

// Handle form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'daily_tour';
    $duration_hours = floatval($_POST['duration_hours'] ?? 0);
    $max_capacity = intval($_POST['max_capacity'] ?? 0);
    $price_per_person = floatval($_POST['price_per_person'] ?? 0);
    $currency = $_POST['currency'] ?? 'EUR';
    $boat_name = trim($_POST['boat_name'] ?? '');
    $boat_type = trim($_POST['boat_type'] ?? '');
    $departure_location = trim($_POST['departure_location'] ?? '');
    
    // Amenities (checkboxes)
    $amenities = [];
    if (isset($_POST['wifi'])) $amenities[] = 'wifi';
    if (isset($_POST['lunch'])) $amenities[] = 'lunch';
    if (isset($_POST['drinks'])) $amenities[] = 'drinks';
    if (isset($_POST['snorkeling'])) $amenities[] = 'snorkeling';
    if (isset($_POST['fishing_gear'])) $amenities[] = 'fishing_gear';
    if (isset($_POST['bathroom'])) $amenities[] = 'bathroom';
    $amenities_json = json_encode($amenities);
    
    // Handle cover photo upload
    $cover_photo = null;
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === 0) {
        $upload_dir = 'assets/boat_trips/';
        $result = gorselOptimizeEt($_FILES['cover_photo'], $upload_dir);
        
        if (isset($result['success'])) {
            $cover_photo = $result['path'];
        }
    }
    
    if ($title && $description && $cover_photo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO boat_trips 
                (captain_id, title, description, category, duration_hours, max_capacity, 
                 price_per_person, currency, boat_name, boat_type, departure_location, 
                 cover_photo, amenities, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $category,
                $duration_hours,
                $max_capacity,
                $price_per_person,
                $currency,
                $boat_name,
                $boat_type,
                $departure_location,
                $cover_photo,
                $amenities_json
            ]);
            
            $success = true;
        } catch (PDOException $e) {
            $error = $lang == 'en' ? 'Failed to create trip listing.' : 'Tur ilanı oluşturulamadı.';
        }
    } else {
        $error = $lang == 'en' ? 'Please fill all required fields and upload a cover photo.' : 'Lütfen tüm gerekli alanları doldurun ve kapak fotoğrafı yükleyin.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['add_boat_trip']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-6 pt-24">
        
        <?php if ($success): ?>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-500/30 rounded-[2.5rem] p-12 text-center shadow-xl mb-8">
                <div class="w-24 h-24 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check-double text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                </div>
                <h2 class="text-3xl font-black mb-4"><?php echo $lang == 'en' ? 'Trip Submitted!' : 'Tur Gönderildi!'; ?></h2>
                <p class="text-slate-600 dark:text-slate-400 mb-8">
                    <?php echo $lang == 'en' 
                        ? 'Your boat trip listing has been submitted for admin approval. You will be notified once it is approved.' 
                        : 'Tekne turu ilanınız yönetici onayı için gönderildi. Onaylandığında bilgilendirileceksiniz.'; ?>
                </p>
                <div class="flex gap-4 justify-center">
                    <a href="add_boat_trip" class="bg-cyan-600 hover:bg-cyan-700 text-white px-8 py-4 rounded-2xl font-black shadow-lg transition-all">
                        <?php echo $lang == 'en' ? 'Add Another Trip' : 'Başka Tur Ekle'; ?>
                    </a>
                    <a href="my_trips" class="bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 px-8 py-4 rounded-2xl font-black transition-all">
                        <?php echo $t['my_trips']; ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Header -->
            <div class="mb-10 text-center">
                <h1 class="text-4xl font-black mb-2"><?php echo $t['add_boat_trip']; ?></h1>
                <p class="text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Create a new boat trip listing for travelers in Kalkan' : 'Kalkan\'daki gezginler için yeni bir tekne turu ilanı oluşturun'; ?></p>
                <p class="text-[10px] font-bold text-cyan-500 uppercase tracking-widest mt-2"><i class="fas fa-ship"></i> <?php echo $lang == 'en' ? 'Captain Account' : 'Kaptan Hesabı'; ?></p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 dark:bg-red-900/30 border border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-2xl mb-8">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                
                <!-- Basic Info Card -->
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-info-circle text-cyan-500"></i>
                        <?php echo $lang == 'en' ? 'Basic Information' : 'Temel Bilgiler'; ?>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Trip Title' : 'Tur Başlığı'; ?> *</label>
                            <input type="text" name="title" required placeholder="<?php echo $lang == 'en' ? 'e.g., Blue Cave & Kekova Island Tour' : 'örn., Mavi Mağara & Kekova Adası Turu'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?> *</label>
                            <select name="category" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                                <option value="daily_tour"><?php echo $t['daily_tour']; ?></option>
                                <option value="private_charter"><?php echo $t['private_charter']; ?></option>
                                <option value="sunset_cruise"><?php echo $t['sunset_cruise']; ?></option>
                                <option value="fishing"><?php echo $t['fishing_trip']; ?></option>
                                <option value="diving"><?php echo $t['diving']; ?></option>
                                <option value="other"><?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?></option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $t['duration']; ?> (<?php echo $t['hours']; ?>) *</label>
                            <input type="number" name="duration_hours" step="0.5" min="0.5" required placeholder="4.5" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $t['max_capacity']; ?> *</label>
                            <input type="number" name="max_capacity" min="1" required placeholder="12" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Price' : 'Fiyat'; ?> (<?php echo $t['per_person']; ?>) *</label>
                            <div class="flex gap-2">
                                <input type="number" name="price_per_person" step="0.01" min="0" required placeholder="50.00" class="flex-1 bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                                <select name="currency" class="bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                                    <option value="EUR">EUR (€)</option>
                                    <option value="USD">USD ($)</option>
                                    <option value="GBP">GBP (£)</option>
                                    <option value="TRY">TRY (₺)</option>
                                </select>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Description' : 'Açıklama'; ?> *</label>
                            <textarea name="description" rows="6" required placeholder="<?php echo $lang == 'en' ? 'Describe your boat trip, what\'s included, highlights...' : 'Tekne turunuzu, nelerin dahil olduğunu, öne çıkan özellikleri açıklayın...'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-medium"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Boat Details Card -->
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-ship text-cyan-500"></i>
                        <?php echo $lang == 'en' ? 'Boat Details' : 'Tekne Detayları'; ?>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $t['boat_name']; ?> *</label>
                            <input type="text" name="boat_name" required placeholder="<?php echo $lang == 'en' ? 'e.g., Sea Breeze' : 'örn., Deniz Esintisi'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Boat Type' : 'Tekne Tipi'; ?> *</label>
                            <input type="text" name="boat_type" required placeholder="<?php echo $lang == 'en' ? 'e.g., Gulet, Yacht, Speedboat' : 'örn., Gulet, Yat, Sürat Teknesi'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $t['departure_location']; ?> *</label>
                            <input type="text" name="departure_location" required placeholder="<?php echo $lang == 'en' ? 'e.g., Kalkan Harbor' : 'örn., Kalkan Limanı'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                        </div>
                    </div>
                </div>

                <!-- Amenities Card -->
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-check-circle text-cyan-500"></i>
                        <?php echo $t['amenities']; ?>
                    </h3>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors">
                            <input type="checkbox" name="wifi" class="w-5 h-5 accent-cyan-500">
                            <span class="font-bold text-sm">📶 WiFi</span>
                        </label>
                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors">
                            <input type="checkbox" name="lunch" class="w-5 h-5 accent-cyan-500">
                            <span class="font-bold text-sm">🍽️ <?php echo $lang == 'en' ? 'Lunch' : 'Öğle Yemeği'; ?></span>
                        </label>
                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors">
                            <input type="checkbox" name="drinks" class="w-5 h-5 accent-cyan-500">
                            <span class="font-bold text-sm">🥤 <?php echo $lang == 'en' ? 'Drinks' : 'İçecekler'; ?></span>
                        </label>
                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors">
                            <input type="checkbox" name="snorkeling" class="w-5 h-5 accent-cyan-500">
                            <span class="font-bold text-sm">🤿 <?php echo $lang == 'en' ? 'Snorkeling Gear' : 'Şnorkel Ekipmanı'; ?></span>
                        </label>
                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors">
                            <input type="checkbox" name="fishing_gear" class="w-5 h-5 accent-cyan-500">
                            <span class="font-bold text-sm">🎣 <?php echo $lang == 'en' ? 'Fishing Gear' : 'Balık Avı Ekipmanı'; ?></span>
                        </label>
                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors">
                            <input type="checkbox" name="bathroom" class="w-5 h-5 accent-cyan-500">
                            <span class="font-bold text-sm">🚻 <?php echo $lang == 'en' ? 'Bathroom' : 'Tuvalet'; ?></span>
                        </label>
                    </div>
                </div>

                <!-- Cover Photo Card -->
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-camera text-cyan-500"></i>
                        <?php echo $lang == 'en' ? 'Cover Photo' : 'Kapak Fotoğrafı'; ?> *
                    </h3>

                    <input type="file" name="cover_photo" accept="image/*" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
                    <p class="text-xs text-slate-400 mt-2 ml-2"><?php echo $lang == 'en' ? 'Max 5MB. JPG, PNG, WEBP' : 'Maks 5MB. JPG, PNG, WEBP'; ?></p>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-black py-6 rounded-[2rem] shadow-2xl shadow-cyan-500/30 transition-all transform active:scale-95 text-xl">
                    <i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit for Approval' : 'Onay İçin Gönder'; ?>
                </button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
