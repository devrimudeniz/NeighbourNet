<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/optimize_upload.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: boat_trips");
    exit();
}

$trip_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Check user permissions
$u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$u_stmt->execute([$user_id]);
$user_badge = $u_stmt->fetchColumn();

$is_admin = in_array($user_badge, ['founder', 'moderator']);
$is_captain = in_array($user_badge, ['captain']);

if (!$is_captain && !$is_admin) {
    header("Location: boat_trips");
    exit();
}

// Fetch Trip
$stmt = $pdo->prepare("SELECT * FROM boat_trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header("Location: boat_trips");
    exit();
}

// Check Ownership (if not admin)
if (!$is_admin && $trip['captain_id'] != $user_id) {
    header("Location: boat_trips?error=unauthorized");
    exit();
}

// Handle Update
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
    
    // Amenities
    $amenities = [];
    if (isset($_POST['wifi'])) $amenities[] = 'wifi';
    if (isset($_POST['lunch'])) $amenities[] = 'lunch';
    if (isset($_POST['drinks'])) $amenities[] = 'drinks';
    if (isset($_POST['snorkeling'])) $amenities[] = 'snorkeling';
    if (isset($_POST['fishing_gear'])) $amenities[] = 'fishing_gear';
    if (isset($_POST['bathroom'])) $amenities[] = 'bathroom';
    $amenities_json = json_encode($amenities);

    // Image Upload (Optional replacement)
    $cover_photo = $trip['cover_photo'];
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === 0) {
        $upload_dir = 'assets/boat_trips/';
        $result = gorselOptimizeEt($_FILES['cover_photo'], $upload_dir);
        
        if (isset($result['success'])) {
            $cover_photo = $result['path'];
        } else {
             $error = $lang == 'en' 
                    ? 'Image upload failed. ' . ($result['error'] ?? '')
                    : 'Resim yükleme başarısız. ' . ($result['error'] ?? '');
        }
    }

    if (!$error && $title && $description) {
        try {
            $update_sql = "UPDATE boat_trips SET 
                title=?, description=?, category=?, duration_hours=?, max_capacity=?, 
                price_per_person=?, currency=?, boat_name=?, boat_type=?, departure_location=?, 
                cover_photo=?, amenities=? WHERE id=?";
            
            $upd = $pdo->prepare($update_sql);
            $upd->execute([
                $title, $description, $category, $duration_hours, $max_capacity,
                $price_per_person, $currency, $boat_name, $boat_type, $departure_location,
                $cover_photo, $amenities_json, $trip_id
            ]);

            $success = true;
            // Refetch data
            $stmt->execute([$trip_id]);
            $trip = $stmt->fetch();
        } catch (PDOException $e) {
             $error = $lang == 'en' ? 'Update failed.' : 'Güncelleme başarısız.';
        }
    }
}

// Decode amenities for form
$current_amenities = json_decode($trip['amenities'] ?? '[]', true);
if (!is_array($current_amenities)) $current_amenities = [];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['edit_boat_trip'] ?? 'Turu Düzenle'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white pb-20">

    <?php include 'includes/header.php'; ?>

    <div class="pt-20 px-4 max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6"><?php echo $t['edit_boat_trip'] ?? 'Tekne Turunu Düzenle'; ?></h1>

        <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6">
            <?php echo $lang == 'en' ? 'Trip updated successfully!' : 'Tur başarıyla güncellendi!'; ?>
            <a href="trip_detail?id=<?php echo $trip_id; ?>" class="underline font-bold ml-2">Görüntüle</a>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <!-- Title -->
            <div>
                <label class="block font-bold mb-1">Tur Başlığı</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($trip['title']); ?>" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
            </div>

            <!-- Description -->
            <div>
                <label class="block font-bold mb-1">Açıklama</label>
                <textarea name="description" rows="4" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700"><?php echo htmlspecialchars($trip['description']); ?></textarea>
            </div>

            <!-- Boat Info -->
            <div class="grid grid-cols-2 gap-4">
                 <div>
                    <label class="block font-bold mb-1">Tekne Adı</label>
                    <input type="text" name="boat_name" value="<?php echo htmlspecialchars($trip['boat_name']); ?>" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                </div>
                 <div>
                    <label class="block font-bold mb-1">Tekne Tipi</label>
                    <input type="text" name="boat_type" value="<?php echo htmlspecialchars($trip['boat_type']); ?>" placeholder="Gulet, Motor Yaz, vb." class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                </div>
            </div>

            <!-- Category & Duration -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                     <label class="block font-bold mb-1">Kategori</label>
                     <select name="category" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                         <option value="daily_tour" <?php echo $trip['category'] == 'daily_tour' ? 'selected' : ''; ?>>Günlük Tur</option>
                         <option value="sunset_tour" <?php echo $trip['category'] == 'sunset_tour' ? 'selected' : ''; ?>>Gün Batımı</option>
                         <option value="private_charter" <?php echo $trip['category'] == 'private_charter' ? 'selected' : ''; ?>>Özel Kiralama</option>
                         <option value="fishing" <?php echo $trip['category'] == 'fishing' ? 'selected' : ''; ?>>Balık Turu</option>
                     </select>
                </div>
                <div>
                    <label class="block font-bold mb-1">Süre (Saat)</label>
                    <input type="number" step="0.5" name="duration_hours" value="<?php echo $trip['duration_hours']; ?>" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                </div>
            </div>

             <!-- Capacity & Departure -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-1">Kapasite</label>
                    <input type="number" name="max_capacity" value="<?php echo $trip['max_capacity']; ?>" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                </div>
                 <div>
                    <label class="block font-bold mb-1">Kalkış Yeri</label>
                    <input type="text" name="departure_location" value="<?php echo htmlspecialchars($trip['departure_location']); ?>" placeholder="Kalkan Limanı" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                </div>
            </div>

             <!-- Price -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-1">Kişi Başı Fiyat</label>
                    <input type="number" step="0.01" name="price_per_person" value="<?php echo $trip['price_per_person']; ?>" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                </div>
                 <div>
                    <label class="block font-bold mb-1">Para Birimi</label>
                    <select name="currency" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border dark:border-slate-700">
                        <option value="TRY" <?php echo $trip['currency'] == 'TRY' ? 'selected' : ''; ?>>TRY ₺</option>
                        <option value="GBP" <?php echo $trip['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP £</option>
                        <option value="EUR" <?php echo $trip['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR €</option>
                        <option value="USD" <?php echo $trip['currency'] == 'USD' ? 'selected' : ''; ?>>USD $</option>
                    </select>
                </div>
            </div>

            <!-- Amenities -->
            <div>
                <label class="block font-bold mb-2">İmkanlar</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-3 rounded-lg cursor-pointer">
                        <input type="checkbox" name="lunch" <?php echo in_array('lunch', $current_amenities) ? 'checked' : ''; ?>>
                        <i class="fas fa-utensils text-orange-500"></i> Yemek
                    </label>
                    <label class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-3 rounded-lg cursor-pointer">
                        <input type="checkbox" name="drinks" <?php echo in_array('drinks', $current_amenities) ? 'checked' : ''; ?>>
                        <i class="fas fa-glass-martini-alt text-purple-500"></i> İçecekler
                    </label>
                    <label class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-3 rounded-lg cursor-pointer">
                        <input type="checkbox" name="snorkeling" <?php echo in_array('snorkeling', $current_amenities) ? 'checked' : ''; ?>>
                        <i class="fas fa-swimmer text-blue-500"></i> Şnorkel
                    </label>
                    <label class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-3 rounded-lg cursor-pointer">
                        <input type="checkbox" name="wifi" <?php echo in_array('wifi', $current_amenities) ? 'checked' : ''; ?>>
                        <i class="fas fa-wifi text-indigo-500"></i> Wi-Fi
                    </label>
                     <label class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-3 rounded-lg cursor-pointer">
                        <input type="checkbox" name="fishing_gear" <?php echo in_array('fishing_gear', $current_amenities) ? 'checked' : ''; ?>>
                        <i class="fas fa-fish text-teal-500"></i> Olta
                    </label>
                    <label class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-3 rounded-lg cursor-pointer">
                        <input type="checkbox" name="bathroom" <?php echo in_array('bathroom', $current_amenities) ? 'checked' : ''; ?>>
                        <i class="fas fa-toilet text-gray-500"></i> WC/Duş
                    </label>
                </div>
            </div>

            <!-- Cover Photo -->
             <div>
                <label class="block font-bold mb-2">Kapak Fotoğrafı (Değiştirmek için seçin)</label>
                <?php if ($trip['cover_photo']): ?>
                    <img src="<?php echo htmlspecialchars($trip['cover_photo']); ?>" class="w-full h-40 object-cover rounded-xl mb-3">
                <?php endif; ?>
                <input type="file" name="cover_photo" accept="image/*" class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none">
            </div>

            <button type="submit" class="w-full py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 rounded-xl font-black !text-white shadow-xl shadow-blue-500/30 active:scale-95 transition-all text-lg tracking-wide">
                <?php echo $lang == 'en' ? 'Save Changes' : 'Değişiklikleri Kaydet'; ?>
            </button>
        </form>
    </div>

    <?php // include 'includes/bottom_nav.php'; ?>
</body>
</html>
