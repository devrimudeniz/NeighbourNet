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

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $instagram_url = trim($_POST['instagram_url'] ?? '');
        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $tripadvisor_url = trim($_POST['tripadvisor_url'] ?? '');
        $google_maps_url = trim($_POST['google_maps_url'] ?? '');
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        $menu_theme = $_POST['menu_theme'] ?? 'default';
        $menu_primary_color = $_POST['menu_primary_color'] ?? '#0055FF';
        
        // Handle logo upload / remove
        $menu_logo = $business['menu_logo'];
        if (!empty($_POST['remove_logo'])) {
            if ($menu_logo && file_exists($menu_logo)) {
                unlink($menu_logo);
            }
            $menu_logo = null;
        } elseif (isset($_FILES['menu_logo']) && $_FILES['menu_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/business_logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['menu_logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'logo_' . $business_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['menu_logo']['tmp_name'], $upload_path)) {
                    // Delete old logo if exists
                    if ($menu_logo && file_exists($menu_logo)) {
                        unlink($menu_logo);
                    }
                    $menu_logo = $upload_path;
                }
            }
        }
        
        // Update database
        $update_stmt = $pdo->prepare("
            UPDATE business_listings SET
                instagram_url = ?,
                facebook_url = ?,
                tripadvisor_url = ?,
                google_maps_url = ?,
                latitude = ?,
                longitude = ?,
                menu_theme = ?,
                menu_primary_color = ?,
                menu_logo = ?
            WHERE id = ? AND owner_id = ?
        ");
        
        $update_stmt->execute([
            $instagram_url,
            $facebook_url,
            $tripadvisor_url,
            $google_maps_url,
            $latitude,
            $longitude,
            $menu_theme,
            $menu_primary_color,
            $menu_logo,
            $business_id,
            $user_id
        ]);
        
        $success = $lang == 'en' ? 'Settings saved successfully!' : 'Ayarlar başarıyla kaydedildi!';
        
        // Refresh business data
        $stmt->execute([$business_id, $user_id]);
        $business = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = $lang == 'en' ? 'Error saving settings' : 'Ayarlar kaydedilirken hata oluştu';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Business Settings' : 'İşletme Ayarları'; ?> - <?php echo htmlspecialchars($business['name']); ?></title>
    <link rel="icon" href="/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen">
    
    <?php require_once 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8 mt-24 mb-20">
        
        <!-- Back Button & Title -->
        <div class="mb-8">
            <a href="business_panel.php" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-[#0055FF] mb-4">
                <i class="fas fa-arrow-left"></i>
                <?php echo $lang == 'en' ? 'Back to Panel' : 'Panele Dön'; ?>
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white mb-2">
                <?php echo $lang == 'en' ? 'Business Settings' : 'İşletme Ayarları'; ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400">
                <?php echo htmlspecialchars($business['name']); ?>
            </p>
        </div>

        <?php if ($success): ?>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                <p class="text-emerald-700 dark:text-emerald-300 font-bold"><?php echo $success; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                <p class="text-red-700 dark:text-red-300 font-bold"><?php echo $error; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <!-- Social Media Links -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-black text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-share-alt text-blue-500"></i>
                    <?php echo $lang == 'en' ? 'Social Media & Links' : 'Sosyal Medya & Linkler'; ?>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Instagram -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fab fa-instagram text-pink-500"></i> Instagram
                        </label>
                        <input type="url" name="instagram_url" value="<?php echo htmlspecialchars($business['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/username" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <!-- Facebook -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fab fa-facebook text-blue-600"></i> Facebook
                        </label>
                        <input type="url" name="facebook_url" value="<?php echo htmlspecialchars($business['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/page" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <!-- TripAdvisor Review Link -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fab fa-tripadvisor text-green-600"></i> TripAdvisor <?php echo $lang == 'en' ? 'Review Link' : 'Yorum Linki'; ?>
                        </label>
                        <input type="url" name="tripadvisor_url" value="<?php echo htmlspecialchars($business['tripadvisor_url'] ?? ''); ?>" placeholder="https://tripadvisor.com/UserReviewEdit-..." class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            <i class="fas fa-info-circle"></i> 
                            <?php echo $lang == 'en' ? 'Direct link to write a review on TripAdvisor' : 'TripAdvisor\'da yorum yazmak için direkt link'; ?>
                        </p>
                    </div>

                    <!-- Google Maps -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fas fa-map-marker-alt text-red-500"></i> Google Maps
                        </label>
                        <input type="url" name="google_maps_url" value="<?php echo htmlspecialchars($business['google_maps_url'] ?? ''); ?>" placeholder="https://maps.google.com/..." class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-black text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                    <i class="fas fa-map-pin text-red-500"></i>
                    <?php echo $lang == 'en' ? 'Location' : 'Konum'; ?>
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    <?php echo $lang == 'en' ? 'Search your business name or address to find the location' : 'İşletme adınızı veya adresinizi yazarak konumunuzu bulun'; ?>
                </p>

                <!-- Search Box -->
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                        <i class="fas fa-search text-blue-500"></i>
                        <?php echo $lang == 'en' ? 'Search Location' : 'Konum Ara'; ?>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="location-search" 
                               value="<?php echo htmlspecialchars($business['name'] ?? ''); ?>" 
                               placeholder="<?php echo $lang == 'en' ? 'e.g. My Restaurant, Kalkan' : 'ör. Restoranım, Kalkan'; ?>" 
                               class="flex-1 px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                        <button type="button" onclick="searchLocation()" 
                                class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors flex items-center gap-2">
                            <i class="fas fa-search"></i>
                            <span class="hidden sm:inline"><?php echo $lang == 'en' ? 'Search' : 'Ara'; ?></span>
                        </button>
                    </div>
                    <!-- Search Results -->
                    <div id="search-results" class="hidden mt-2 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl shadow-lg overflow-hidden max-h-60 overflow-y-auto z-10 relative"></div>
                </div>

                <!-- Map Preview -->
                <div id="location-map" class="w-full h-48 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 mb-4 overflow-hidden <?php echo (!empty($business['latitude']) && !empty($business['longitude'])) ? '' : 'hidden'; ?>"></div>

                <!-- Hidden lat/lng fields -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1"><?php echo $lang == 'en' ? 'Latitude' : 'Enlem'; ?></label>
                        <input type="number" step="0.00000001" name="latitude" id="input-lat" value="<?php echo $business['latitude'] ?? ''; ?>" placeholder="36.2667" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm font-mono outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1"><?php echo $lang == 'en' ? 'Longitude' : 'Boylam'; ?></label>
                        <input type="number" step="0.00000001" name="longitude" id="input-lng" value="<?php echo $business['longitude'] ?? ''; ?>" placeholder="29.4167" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm font-mono outline-none">
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    <i class="fas fa-info-circle"></i> 
                    <?php echo $lang == 'en' ? 'Coordinates are auto-filled when you select a result. You can also drag the pin on the map.' : 'Sonuç seçtiğinizde koordinatlar otomatik dolar. Haritadaki pin\'i sürükleyerek de ayarlayabilirsiniz.'; ?>
                </p>
            </div>

            <!-- Leaflet CSS/JS for map -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
            var locationMap = null;
            var locationMarker = null;

            function initMap(lat, lng) {
                var mapEl = document.getElementById('location-map');
                mapEl.classList.remove('hidden');

                if (locationMap) {
                    locationMap.setView([lat, lng], 16);
                    locationMarker.setLatLng([lat, lng]);
                    return;
                }

                locationMap = L.map('location-map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(locationMap);

                locationMarker = L.marker([lat, lng], { draggable: true }).addTo(locationMap);

                // Drag pin to update coordinates
                locationMarker.on('dragend', function(e) {
                    var pos = e.target.getLatLng();
                    document.getElementById('input-lat').value = pos.lat.toFixed(8);
                    document.getElementById('input-lng').value = pos.lng.toFixed(8);
                });
            }

            function searchLocation() {
                var query = document.getElementById('location-search').value.trim();
                if (!query) return;

                // Kalkan bölgesine öncelik ver
                var searchQuery = query;
                if (!/kalkan|kaş|kas|antalya/i.test(query)) {
                    searchQuery = query + ', Kalkan, Antalya, Turkey';
                }

                var resultsDiv = document.getElementById('search-results');
                resultsDiv.innerHTML = '<div class="p-3 text-sm text-slate-500"><i class="fas fa-spinner fa-spin mr-2"></i><?php echo $lang == "en" ? "Searching..." : "Aranıyor..."; ?></div>';
                resultsDiv.classList.remove('hidden');

                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(searchQuery) + '&limit=5&addressdetails=1', {
                    headers: { 'Accept-Language': '<?php echo $lang; ?>' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data || data.length === 0) {
                        resultsDiv.innerHTML = '<div class="p-3 text-sm text-slate-500"><?php echo $lang == "en" ? "No results found. Try a different search." : "Sonuç bulunamadı. Farklı bir arama deneyin."; ?></div>';
                        return;
                    }
                    var html = '';
                    data.forEach(function(item) {
                        html += '<button type="button" onclick="selectLocation(' + item.lat + ',' + item.lon + ',this)" class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-slate-600 border-b border-slate-100 dark:border-slate-600 last:border-0 transition-colors">';
                        html += '<div class="font-bold text-sm text-slate-800 dark:text-white">' + (item.display_name.split(',').slice(0, 3).join(',')) + '</div>';
                        html += '<div class="text-xs text-slate-500 dark:text-slate-400 truncate">' + item.display_name + '</div>';
                        html += '</button>';
                    });
                    resultsDiv.innerHTML = html;
                })
                .catch(function() {
                    resultsDiv.innerHTML = '<div class="p-3 text-sm text-red-500"><?php echo $lang == "en" ? "Search failed. Please try again." : "Arama başarısız. Tekrar deneyin."; ?></div>';
                });
            }

            function selectLocation(lat, lng, btn) {
                document.getElementById('input-lat').value = parseFloat(lat).toFixed(8);
                document.getElementById('input-lng').value = parseFloat(lng).toFixed(8);
                document.getElementById('search-results').classList.add('hidden');
                initMap(lat, lng);
            }

            // Enter key to search
            document.getElementById('location-search').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); searchLocation(); }
            });

            // Init map if coordinates exist
            <?php if (!empty($business['latitude']) && !empty($business['longitude'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                initMap(<?php echo (float)$business['latitude']; ?>, <?php echo (float)$business['longitude']; ?>);
            });
            <?php endif; ?>
            </script>

            <!-- QR Menu Logo -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-black text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                    <i class="fas fa-image text-amber-500"></i>
                    <?php echo $lang == 'en' ? 'Menu Logo' : 'Menü Logosu'; ?>
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    <?php echo $lang == 'en' ? 'Logo shown at the top of your QR menu. Recommended: square image, min 200×200px (JPG, PNG, WebP)' : 'QR menünüzün üst kısmında görünen logo. Önerilen: kare görsel, min 200×200px (JPG, PNG, WebP)'; ?>
                </p>
                
                <div class="flex flex-col sm:flex-row items-start gap-6">
                    <?php if (!empty($business['menu_logo']) && file_exists($business['menu_logo'])): ?>
                    <div class="flex-shrink-0">
                        <img src="/<?php echo htmlspecialchars($business['menu_logo']); ?>" alt="Logo" class="w-24 h-24 rounded-2xl object-cover border-2 border-slate-200 dark:border-slate-600 shadow-md">
                        <label class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 cursor-pointer hover:text-red-600">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded">
                            <?php echo $lang == 'en' ? 'Remove logo' : 'Logoyu kaldır'; ?>
                        </label>
                    </div>
                    <?php else: ?>
                    <div class="w-24 h-24 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center border-2 border-dashed border-slate-300 dark:border-slate-600">
                        <i class="fas fa-image text-3xl text-slate-400"></i>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <?php echo $lang == 'en' ? 'Upload logo' : 'Logo yükle'; ?>
                        </label>
                        <input type="file" name="menu_logo" accept="image/jpeg,image/png,image/gif,image/webp" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-200 dark:file:bg-slate-600 file:text-slate-700 dark:file:text-slate-200 file:font-bold file:cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Menu Theme - QR Menü Görünümü -->
            <?php require_once __DIR__ . '/includes/menu_themes.php'; ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <h2 class="text-xl font-black text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                    <i class="fas fa-palette text-violet-500"></i>
                    <?php echo $lang == 'en' ? 'Menu Theme' : 'Menü Teması'; ?>
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    <?php echo $lang == 'en' ? 'Choose how your digital QR menu looks to customers' : 'Dijital QR menünüzün müşterilere nasıl görüneceğini seçin'; ?>
                </p>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <?php 
                    $current_theme = $business['menu_theme'] ?? 'default';
                    foreach ($MENU_THEMES as $tid => $th): 
                        $name = $lang == 'en' ? $th['name_en'] : $th['name_tr'];
                        $desc = $lang == 'en' ? $th['desc_en'] : $th['desc_tr'];
                        $is_selected = $current_theme === $tid;
                    ?>
                    <label class="block cursor-pointer">
                        <input type="radio" name="menu_theme" value="<?php echo htmlspecialchars($tid); ?>" <?php echo $is_selected ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="p-4 rounded-2xl border-2 transition-all peer-checked:border-violet-500 peer-checked:ring-2 peer-checked:ring-violet-500/30 <?php echo $is_selected ? 'bg-violet-50 dark:bg-violet-900/20 border-violet-500' : 'border-slate-200 dark:border-slate-600 hover:border-violet-300 dark:hover:border-violet-700'; ?>">
                            <div class="w-full h-12 rounded-xl mb-2" style="background: <?php echo $th['bg']; ?>; border: 1px solid <?php echo $th['card_border']; ?>;"></div>
                            <div class="font-bold text-sm text-slate-800 dark:text-white"><?php echo htmlspecialchars($name); ?></div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($desc); ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                        <?php echo $lang == 'en' ? 'Accent Color (optional)' : 'Vurgu Rengi (isteğe bağlı)'; ?>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="menu_primary_color" value="<?php echo htmlspecialchars($business['menu_primary_color'] ?? '#0055FF'); ?>" class="w-14 h-14 rounded-xl border-2 border-slate-200 dark:border-slate-600 cursor-pointer">
                        <input type="text" value="<?php echo htmlspecialchars($business['menu_primary_color'] ?? '#0055FF'); ?>" class="w-28 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm font-mono" id="menu_color_hex" readonly>
                        <span class="text-xs text-slate-500"><?php echo $lang == 'en' ? 'Overrides theme primary' : 'Tema rengini geçersiz kılar'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 bg-slate-700 hover:bg-slate-800 text-white px-8 py-4 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-105 transition-all">
                    <i class="fas fa-save"></i>
                    <?php echo $lang == 'en' ? 'Save Settings' : 'Ayarları Kaydet'; ?>
                </button>
            </div>
        </form>

    </main>

    <!-- Sticky Footer Spacer for Mobile Nav -->
    <div class="h-20 md:hidden"></div>

    <script>
    document.querySelector('input[name="menu_primary_color"]').addEventListener('input', function() {
        document.getElementById('menu_color_hex').value = this.value;
    });
    </script>

</body>
</html>
