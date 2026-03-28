<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
session_start();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-red-50 via-pink-50 to-orange-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-6xl">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-3 bg-red-100 dark:bg-red-900/30 px-6 py-3 rounded-full mb-4">
                <i class="fas fa-pills text-red-600 dark:text-red-400 text-2xl"></i>
                <span class="text-red-600 dark:text-red-400 font-black uppercase tracking-wider text-sm">
                    <?php echo $lang == 'en' ? 'Emergency Service' : 'Acil Hizmet'; ?>
                </span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black mb-4 bg-clip-text text-transparent bg-gradient-to-r from-red-600 to-orange-600">
                <?php echo $lang == 'en' ? 'Duty Pharmacies' : 'Nöbetçi Eczaneler'; ?>
            </h1>
            <p class="text-slate-600 dark:text-slate-400 text-lg">
                <?php echo $lang == 'en' ? 'Kaş, Antalya' : 'Kaş, Antalya'; ?>
            </p>
        </div>

        <!-- Loading State -->
        <div id="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-red-200 border-t-red-600"></div>
            <p class="mt-4 text-slate-600 dark:text-slate-400"><?php echo $lang == 'en' ? 'Loading pharmacies...' : 'Eczaneler yükleniyor...'; ?></p>
        </div>

        <!-- Pharmacy List -->
        <div id="pharmacy-list" class="grid md:grid-cols-2 gap-6 mb-12 hidden"></div>

        <!-- Map -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-3xl p-6 border border-white/20 dark:border-slate-800/50 shadow-2xl">
            <h2 class="text-2xl font-black mb-4 flex items-center gap-2">
                <i class="fas fa-map-marked-alt text-red-600"></i>
                <?php echo $lang == 'en' ? 'Map View' : 'Harita Görünümü'; ?>
            </h2>
            <div id="map" class="w-full h-96 rounded-2xl overflow-hidden"></div>
        </div>

        <!-- Error State -->
        <div id="error" class="hidden bg-red-100 dark:bg-red-900/30 border-2 border-red-500 rounded-2xl p-8 text-center">
            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-4xl mb-4"></i>
            <p class="text-red-700 dark:text-red-300 font-bold text-lg" id="error-message"></p>
        </div>
    </main>

    <script>
        let map;
        const pharmacyIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        async function loadPharmacies() {
            try {
                const response = await fetch('/api/get_duty_pharmacy.php');
                const data = await response.json();

                document.getElementById('loading').classList.add('hidden');

                if (data.success && data.result && data.result.length > 0) {
                    displayPharmacies(data.result);
                    initMap(data.result);
                } else {
                    showError('<?php echo $lang == 'en' ? 'No duty pharmacies found' : 'Nöbetçi eczane bulunamadı'; ?>');
                }
            } catch (error) {
                document.getElementById('loading').classList.add('hidden');
                showError('<?php echo $lang == 'en' ? 'Failed to load pharmacies' : 'Eczaneler yüklenemedi'; ?>');
            }
        }

        function displayPharmacies(pharmacies) {
            const container = document.getElementById('pharmacy-list');
            container.classList.remove('hidden');

            pharmacies.forEach(pharmacy => {
                const card = document.createElement('div');
                card.className = 'bg-white/70 dark:bg-slate-800 backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-slate-800/50 shadow-lg hover:shadow-2xl transition-all';
                card.innerHTML = `
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fas fa-pills text-red-600 dark:text-red-400 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-black mb-2 text-slate-900 dark:text-white">${pharmacy.name}</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-3 flex items-start gap-2">
                                <i class="fas fa-map-marker-alt mt-1 text-red-500"></i>
                                <span>${pharmacy.address}</span>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="tel:${pharmacy.phone}" class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl font-bold text-sm transition-all">
                                    <i class="fas fa-phone"></i>
                                    ${pharmacy.phone}
                                </a>
                                ${pharmacy.loc ? `
                                <a href="https://www.google.com/maps?q=${pharmacy.loc}" target="_blank" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-bold text-sm transition-all">
                                    <i class="fas fa-directions"></i>
                                    <?php echo $lang == 'en' ? 'Directions' : 'Yol Tarifi'; ?>
                                </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function initMap(pharmacies) {
            // Center on Kaş
            map = L.map('map').setView([36.2019, 29.6419], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            pharmacies.forEach(pharmacy => {
                if (pharmacy.loc) {
                    const [lat, lng] = pharmacy.loc.split(',').map(Number);
                    L.marker([lat, lng], { icon: pharmacyIcon })
                        .addTo(map)
                        .bindPopup(`
                            <div class="font-bold text-sm">${pharmacy.name}</div>
                            <div class="text-xs text-slate-600 mt-1">${pharmacy.address}</div>
                            <a href="tel:${pharmacy.phone}" class="text-green-600 font-bold text-xs mt-2 inline-block">
                                <i class="fas fa-phone"></i> ${pharmacy.phone}
                            </a>
                        `);
                }
            });
        }

        function showError(message) {
            document.getElementById('error').classList.remove('hidden');
            document.getElementById('error-message').textContent = message;
        }

        // Load on page load
        loadPharmacies();
    </script>

</body>
</html>
