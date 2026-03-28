<?php
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['create_lost_pet_alert']; ?> | <?php echo $t['pati_safe']; ?></title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen">

    <div class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md fixed top-0 w-full z-10 border-b border-slate-200 dark:border-slate-800 flex items-center px-4">
        <a href="pati_safe" class="text-2xl mr-4"><i class="fas fa-arrow-left"></i></a>
        <h1 class="font-bold text-lg"><?php echo $t['create_lost_pet_alert']; ?></h1>
    </div>

    <main class="container mx-auto px-4 pt-24 max-w-lg pb-24">
        
        <form id="lostPetForm" class="space-y-6">
            
            <!-- Photo Upload -->
            <div class="space-y-4">
                <label class="w-full h-48 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-red-500 transition-colors bg-white dark:bg-slate-800 overflow-hidden" id="dropzone">
                    <div id="upload-placeholder" class="text-center text-slate-400">
                        <i class="fas fa-images text-3xl mb-2"></i>
                        <p class="text-sm font-bold"><?php echo $t['add_photo']; ?></p>
                        <p class="text-xs opacity-60 mt-1"><?php echo $t['add_more_photos']; ?></p>
                    </div>
                    <input type="file" name="photos[]" id="photoInput" class="hidden" accept="image/*" multiple required>
                </label>
                
                <!-- Preview Grid -->
                <div id="preview-grid" class="grid grid-cols-4 gap-2 hidden">
                    <!-- Previews will be injected here -->
                </div>
            </div>

            <!-- Status Type -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Alert Type' : 'İlan Türü'; ?></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer has-[:checked]:border-red-500 has-[:checked]:bg-red-50 dark:has-[:checked]:bg-red-900/20 transition-all">
                        <input type="radio" name="status_type" value="lost" class="accent-red-500" checked>
                        <span class="font-bold text-sm"><i class="fas fa-search text-red-500 mr-1"></i> <?php echo $lang == 'en' ? 'Lost' : 'Kayıp'; ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer has-[:checked]:border-pink-500 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-900/20 transition-all">
                        <input type="radio" name="status_type" value="adoption" class="accent-pink-500">
                        <span class="font-bold text-sm"><i class="fas fa-heart text-pink-500 mr-1"></i> <?php echo $lang == 'en' ? 'Adoption' : 'Sahiplendirme'; ?></span>
                    </label>
                </div>
            </div>

            <!-- Details Row 1 -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $t['pet_name']; ?></label>
                    <input type="text" name="pet_name" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none" placeholder="Boncuk" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $t['pet_type']; ?></label>
                    <select name="pet_type" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 outline-none">
                        <option value="cat"><?php echo $t['cat']; ?></option>
                        <option value="dog"><?php echo $t['dog']; ?></option>
                        <option value="bird"><?php echo $t['bird']; ?></option>
                        <option value="other"><?php echo $t['other']; ?></option>
                    </select>
                </div>
            </div>

            <!-- Details Row 2: Gender & Color -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Gender' : 'Cinsiyet'; ?></label>
                    <select name="gender" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 outline-none">
                        <option value="unknown"><?php echo $lang == 'en' ? 'Unknown' : 'Bilinmiyor'; ?></option>
                        <option value="male"><?php echo $lang == 'en' ? 'Male' : 'Erkek'; ?></option>
                        <option value="female"><?php echo $lang == 'en' ? 'Female' : 'Dişi'; ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Color' : 'Renk'; ?></label>
                    <input type="text" name="color" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none" placeholder="<?php echo $lang == 'en' ? 'e.g. Orange tabby' : 'ör. Turuncu tekir'; ?>">
                </div>
            </div>

            <!-- Collar & Chip Checkboxes -->
            <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:border-blue-500 transition-all">
                    <input type="checkbox" name="has_collar" value="1" class="w-5 h-5 accent-blue-500 rounded">
                    <span class="font-bold text-sm"><i class="fas fa-ring text-blue-500 mr-1"></i> <?php echo $lang == 'en' ? 'Has Collar' : 'Tasmalı'; ?></span>
                </label>
                <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:border-green-500 transition-all">
                    <input type="checkbox" name="has_chip" value="1" class="w-5 h-5 accent-green-500 rounded">
                    <span class="font-bold text-sm"><i class="fas fa-microchip text-green-500 mr-1"></i> <?php echo $lang == 'en' ? 'Has Chip' : 'Çipli'; ?></span>
                </label>
            </div>

            <!-- Location Text -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['last_seen_location']; ?></label>
                <div class="relative">
                    <i class="fas fa-map-marker-alt absolute left-4 top-3.5 text-red-500"></i>
                    <input type="text" name="location" id="locationInput" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 pl-10 focus:border-red-500 outline-none" placeholder="<?php echo $t['location_placeholder']; ?>" required>
                </div>
            </div>

            <!-- Map Location Picker -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Pin Location on Map' : 'Haritada Konum Seç'; ?></label>
                <div id="map" class="w-full h-48 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden"></div>
                <input type="hidden" name="lat" id="latInput">
                <input type="hidden" name="lng" id="lngInput">
                <p class="text-xs text-slate-400 mt-1"><?php echo $lang == 'en' ? 'Click on the map to set location' : 'Konum belirlemek için haritaya tıklayın'; ?></p>
            </div>

            <!-- Distinctive Features -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Distinctive Features' : 'Belirgin Özellikler'; ?></label>
                <textarea name="distinctive_features" rows="2" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none" placeholder="<?php echo $lang == 'en' ? 'e.g. White spot on forehead, broken ear' : 'ör. Alnında beyaz leke, kırık kulak'; ?>"></textarea>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['description']; ?></label>
                <textarea name="description" rows="3" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none" placeholder="<?php echo $t['description_placeholder']; ?>"></textarea>
            </div>

            <!-- Contact -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['contact_number']; ?></label>
                <input type="tel" name="contact_phone" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-red-500 outline-none" placeholder="<?php echo $t['contact_placeholder']; ?>">
            </div>

            <div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-xl text-xs font-bold flex gap-3 items-center">
                <i class="fas fa-bell text-xl"></i>
                <p><?php echo $t['alert_warning']; ?></p>
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-red-500 text-white py-4 rounded-xl font-bold shadow-lg shadow-red-500/30 hover:bg-red-600 transition-all flex justify-center items-center gap-2">
                <i class="fas fa-bullhorn"></i> <?php echo $t['publish_alert']; ?>
            </button>

        </form>

    </main>

    <!-- Leaflet CSS/JS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Multiple Photo Preview
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            const grid = document.getElementById('preview-grid');
            const placeholder = document.getElementById('upload-placeholder');
            
            grid.innerHTML = ''; // Clear previous
            
            if(files.length > 0) {
                grid.classList.remove('hidden');
                // placeholder.classList.add('hidden'); // Keep placeholder? No, maybe hide it if too many.
                
                files.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'aspect-square rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 relative group';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="text-white text-[10px] font-bold">#${index+1}</span>
                            </div>
                        `;
                        grid.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            } else {
                grid.classList.add('hidden');
                placeholder.classList.remove('hidden');
            }
        });

        // Leaflet Map Init
        const map = L.map('map').setView([36.2648, 29.6411], 14); // Kalkan center
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = null;
        map.on('click', function(e) {
            const { lat, lng } = e.latlng;
            document.getElementById('latInput').value = lat.toFixed(8);
            document.getElementById('lngInput').value = lng.toFixed(8);
            
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    document.getElementById('latInput').value = pos.lat.toFixed(8);
                    document.getElementById('lngInput').value = pos.lng.toFixed(8);
                });
            }
        });

        // Submit
        document.getElementById('lostPetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $t['publishing']; ?>';

            const formData = new FormData(this);

            fetch('api/add_lost_pet.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('<?php echo $t['alert_created']; ?>');
                    window.location.href = 'pati_safe';
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                alert('<?php echo $t['error']; ?>');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    </script>
</body>
</html>
