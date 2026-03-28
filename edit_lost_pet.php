<?php
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login?redirect=edit_lost_pet');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    header('Location: pati_safe');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM lost_pets WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pet) {
    header('Location: pati_safe');
    exit();
}

// Extra photos
$extra_photos = [];
try {
    $ph = $pdo->prepare("SELECT photo_url FROM lost_pet_photos WHERE lost_pet_id = ? ORDER BY id");
    $ph->execute([$id]);
    $extra_photos = $ph->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

$all_photos = array_filter(array_merge([$pet['photo_url']], $extra_photos));
$status_type = $pet['status_type'] ?? 'lost';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?? 'tr'; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Edit listing' : 'İlanı düzenle'; ?> | <?php echo $t['pati_safe']; ?></title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen">

    <div class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md fixed top-0 w-full z-10 border-b border-slate-200 dark:border-slate-800 flex items-center px-4">
        <a href="pati_safe" class="text-2xl mr-4"><i class="fas fa-arrow-left"></i></a>
        <h1 class="font-bold text-lg"><?php echo $lang == 'en' ? 'Edit listing' : 'İlanı düzenle'; ?></h1>
    </div>

    <main class="container mx-auto px-4 pt-24 max-w-lg pb-24">
        <form id="lostPetForm" class="space-y-6">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <!-- Photo: show current + optional new upload -->
            <div class="space-y-4">
                <label class="w-full min-h-[120px] border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-red-500 transition-colors bg-white dark:bg-slate-800 overflow-hidden p-3" id="dropzone">
                    <div id="upload-placeholder" class="text-center text-slate-400">
                        <i class="fas fa-images text-3xl mb-2"></i>
                        <p class="text-sm font-bold"><?php echo $lang == 'en' ? 'Change photos (optional)' : 'Fotoğrafları değiştir (isteğe bağlı)'; ?></p>
                    </div>
                    <input type="file" name="photos[]" id="photoInput" class="hidden" accept="image/*" multiple>
                </label>
                <?php if (!empty($all_photos)): ?>
                <div class="flex gap-2 flex-wrap" id="current-photos">
                    <?php foreach ($all_photos as $url): if (empty($url)) continue; ?>
                    <img src="<?php echo htmlspecialchars($url); ?>" class="w-20 h-20 object-cover rounded-xl border border-slate-200 dark:border-slate-700" alt="">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div id="preview-grid" class="grid grid-cols-4 gap-2 hidden"></div>
            </div>

            <!-- Status Type -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Alert Type' : 'İlan Türü'; ?></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20 transition-all">
                        <input type="radio" name="status_type" value="lost" class="accent-red-500" <?php echo $status_type === 'lost' ? 'checked' : ''; ?>>
                        <span class="font-bold text-sm"><i class="fas fa-search text-red-500 mr-1"></i> <?php echo $lang == 'en' ? 'Lost' : 'Kayıp'; ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer has-[:checked]:border-pink-500 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-900/20 transition-all">
                        <input type="radio" name="status_type" value="adoption" class="accent-pink-500" <?php echo $status_type === 'adoption' ? 'checked' : ''; ?>>
                        <span class="font-bold text-sm"><i class="fas fa-heart text-pink-500 mr-1"></i> <?php echo $lang == 'en' ? 'Adoption' : 'Sahiplendirme'; ?></span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $t['pet_name']; ?></label>
                    <input type="text" name="pet_name" value="<?php echo htmlspecialchars($pet['pet_name']); ?>" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $t['pet_type']; ?></label>
                    <select name="pet_type" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 outline-none">
                        <option value="cat" <?php echo ($pet['pet_type'] ?? '') === 'cat' ? 'selected' : ''; ?>><?php echo $t['cat']; ?></option>
                        <option value="dog" <?php echo ($pet['pet_type'] ?? '') === 'dog' ? 'selected' : ''; ?>><?php echo $t['dog']; ?></option>
                        <option value="bird" <?php echo ($pet['pet_type'] ?? '') === 'bird' ? 'selected' : ''; ?>><?php echo $t['bird']; ?></option>
                        <option value="other" <?php echo ($pet['pet_type'] ?? '') === 'other' ? 'selected' : ''; ?>><?php echo $t['other']; ?></option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Gender' : 'Cinsiyet'; ?></label>
                    <select name="gender" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 outline-none">
                        <option value="unknown" <?php echo ($pet['gender'] ?? '') === 'unknown' ? 'selected' : ''; ?>><?php echo $lang == 'en' ? 'Unknown' : 'Bilinmiyor'; ?></option>
                        <option value="male" <?php echo ($pet['gender'] ?? '') === 'male' ? 'selected' : ''; ?>><?php echo $lang == 'en' ? 'Male' : 'Erkek'; ?></option>
                        <option value="female" <?php echo ($pet['gender'] ?? '') === 'female' ? 'selected' : ''; ?>><?php echo $lang == 'en' ? 'Female' : 'Dişi'; ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Color' : 'Renk'; ?></label>
                    <input type="text" name="color" value="<?php echo htmlspecialchars($pet['color'] ?? ''); ?>" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:border-blue-500 transition-all">
                    <input type="checkbox" name="has_collar" value="1" class="w-5 h-5 accent-blue-500 rounded" <?php echo !empty($pet['has_collar']) ? 'checked' : ''; ?>>
                    <span class="font-bold text-sm"><i class="fas fa-ring text-blue-500 mr-1"></i> <?php echo $lang == 'en' ? 'Has Collar' : 'Tasmalı'; ?></span>
                </label>
                <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:border-green-500 transition-all">
                    <input type="checkbox" name="has_chip" value="1" class="w-5 h-5 accent-green-500 rounded" <?php echo !empty($pet['has_chip']) ? 'checked' : ''; ?>>
                    <span class="font-bold text-sm"><i class="fas fa-microchip text-green-500 mr-1"></i> <?php echo $lang == 'en' ? 'Has Chip' : 'Çipli'; ?></span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['last_seen_location']; ?></label>
                <div class="relative">
                    <i class="fas fa-map-marker-alt absolute left-4 top-3.5 text-red-500"></i>
                    <input type="text" name="location" id="locationInput" value="<?php echo htmlspecialchars($pet['location']); ?>" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 pl-10 focus:border-red-500 outline-none" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Pin Location on Map' : 'Haritada Konum Seç'; ?></label>
                <div id="map" class="w-full h-48 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden"></div>
                <input type="hidden" name="lat" id="latInput" value="<?php echo htmlspecialchars($pet['lat'] ?? ''); ?>">
                <input type="hidden" name="lng" id="lngInput" value="<?php echo htmlspecialchars($pet['lng'] ?? ''); ?>">
            </div>

            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Distinctive Features' : 'Belirgin Özellikler'; ?></label>
                <textarea name="distinctive_features" rows="2" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none"><?php echo htmlspecialchars($pet['distinctive_features'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['description']; ?></label>
                <textarea name="description" rows="3" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none"><?php echo htmlspecialchars($pet['description'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['contact_number']; ?></label>
                <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($pet['contact_phone'] ?? ''); ?>" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none">
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-red-500 text-white py-4 rounded-xl font-bold shadow-lg shadow-red-500/30 hover:bg-red-600 transition-all flex justify-center items-center gap-2">
                <i class="fas fa-save"></i> <?php echo $lang == 'en' ? 'Save changes' : 'Değişiklikleri kaydet'; ?>
            </button>
        </form>
    </main>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function() {
        var latVal = parseFloat(document.getElementById('latInput').value) || 36.2648;
        var lngVal = parseFloat(document.getElementById('lngInput').value) || 29.6411;
        var map = L.map('map').setView([latVal, lngVal], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        var marker = null;
        if (latVal && lngVal) {
            marker = L.marker([latVal, lngVal], { draggable: true }).addTo(map);
            marker.on('dragend', function(e) {
                var pos = e.target.getLatLng();
                document.getElementById('latInput').value = pos.lat.toFixed(8);
                document.getElementById('lngInput').value = pos.lng.toFixed(8);
            });
        }
        map.on('click', function(e) {
            var lat = e.latlng.lat, lng = e.latlng.lng;
            document.getElementById('latInput').value = lat.toFixed(8);
            document.getElementById('lngInput').value = lng.toFixed(8);
            if (marker) marker.setLatLng(e.latlng);
            else {
                marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                marker.on('dragend', function(ev) {
                    var p = ev.target.getLatLng();
                    document.getElementById('latInput').value = p.lat.toFixed(8);
                    document.getElementById('lngInput').value = p.lng.toFixed(8);
                });
            }
        });

        document.getElementById('photoInput').addEventListener('change', function(e) {
            var files = Array.from(e.target.files);
            var grid = document.getElementById('preview-grid');
            grid.innerHTML = '';
            if (files.length > 0) {
                grid.classList.remove('hidden');
                files.forEach(function(file, index) {
                    var reader = new FileReader();
                    reader.onload = function(ev) {
                        var div = document.createElement('div');
                        div.className = 'aspect-square rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700';
                        div.innerHTML = '<img src="' + ev.target.result + '" class="w-full h-full object-cover">';
                        grid.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            } else {
                grid.classList.add('hidden');
            }
        });

        document.getElementById('lostPetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('submitBtn');
            var orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $lang == "en" ? "Saving..." : "Kaydediliyor..."; ?>';
            var formData = new FormData(this);
            fetch('api/update_lost_pet.php', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.href = 'pati_safe';
                    } else {
                        alert(data.message || '<?php echo $t["error"]; ?>');
                        btn.disabled = false;
                        btn.innerHTML = orig;
                    }
                })
                .catch(function() {
                    alert('<?php echo $t["error"]; ?>');
                    btn.disabled = false;
                    btn.innerHTML = orig;
                });
        });
    })();
    </script>
</body>
</html>
