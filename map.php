<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/pharmacy_helper.php';

// Auto-update Pharmacy if not updated today
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM pharmacies WHERE duty_date = CURDATE() AND is_on_duty = 1");
    if ($stmt->fetchColumn() == 0) {
        updatePharmaciesFromApi($pdo);
    }
} catch (Exception $e) {}

$is_dark = (defined('CURRENT_THEME') && CURRENT_THEME == 'dark');
$t_cat = $lang == 'en' ? 'Categories' : 'Kategoriler';
$t_all = $lang == 'en' ? 'All' : 'Tümü';
$t_party = $lang == 'en' ? 'Party & Nightlife' : 'Parti & Gece Hayatı';
$t_food = $lang == 'en' ? 'Food & Dining' : 'Yemek & Tadım';
$t_music = $lang == 'en' ? 'Live Music' : 'Canlı Müzik';
$t_trail = $lang == 'en' ? 'Lycian Way' : 'Likya Yolu';
$t_details = $lang == 'en' ? 'Details' : 'Detaylar';
$t_call_now = $lang == 'en' ? 'Call Now' : 'Hemen Ara';
$t_duty_pharmacy = $lang == 'en' ? 'On Duty Today (24h)' : 'Bugün Nöbetçi (24 Saat Açık)';
$t_pharmacy = $lang == 'en' ? 'PHARMACY' : 'ECZANE';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo $is_dark ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title><?php echo $t['live_map']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.7.0/gpx.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" type="text/css">
    <script src="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: 'Outfit', sans-serif; overflow: hidden; height: 100%; }
        #map { 
            height: 100dvh; 
            height: 100vh; 
            width: 100vw; 
            z-index: 1; 
            position: fixed;
            inset: 0;
        }
        
        /* Safe area for notched phones */
        .safe-top { padding-top: env(safe-area-inset-top, 0); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 0); }
        .safe-left { padding-left: env(safe-area-inset-left, 0); }
        .safe-right { padding-right: env(safe-area-inset-right, 0); }

        /* Top bar */
        .map-top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 500;
            padding: 12px 16px;
            padding-top: calc(12px + env(safe-area-inset-top, 0px));
            pointer-events: none;
        }
        .map-top-bar > * { pointer-events: auto; }

        /* Filter panel - responsive */
        #filterDrawer {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 501;
            width: min(320px, 100vw - 32px);
            max-width: 100%;
            height: 100dvh;
            height: 100vh;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(16px);
            box-shadow: -8px 0 32px rgba(0,0,0,0.12);
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            overflow-y: auto;
            padding: 20px;
            padding-top: calc(20px + env(safe-area-inset-top, 0px));
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
        html.dark #filterDrawer { background: rgba(15,23,42,0.98); box-shadow: -8px 0 32px rgba(0,0,0,0.4); }
        #filterDrawer.open { transform: translateX(0); }

        /* Trail sidebar - responsive */
        #trailGuide {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: min(380px, 100vw - 24px);
            max-width: 100%;
            height: 100dvh;
            height: 100vh;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(16px);
            box-shadow: 8px 0 32px rgba(0,0,0,0.12);
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            overflow-y: auto;
            overflow-x: hidden;
        }
        html.dark #trailGuide { background: rgba(15,23,42,0.98); box-shadow: 8px 0 32px rgba(0,0,0,0.4); }
        #trailGuide.open { transform: translateX(0); }

        .section-card {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(241,245,249,0.8);
            border: 1px solid rgba(0,0,0,0.04);
        }
        html.dark .section-card { background: rgba(30,41,59,0.6); border-color: rgba(255,255,255,0.06); }
        .section-card:hover, .section-card:active { background: rgba(16,185,129,0.12); }
        html.dark .section-card:hover { background: rgba(16,185,129,0.15); }

        .poi-popup-content { font-size: 14px; line-height: 1.5; color: #334155; }
        html.dark .poi-popup-content { color: #e2e8f0; }
        .poi-type-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; }

        .custom-marker {
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
            transition: transform 0.15s;
        }
        .custom-marker:hover { transform: scale(1.15); z-index: 1000 !important; }

        .marker-party { background: linear-gradient(135deg,#ec4899,#db2777) !important; color: white !important; }
        .marker-food { background: linear-gradient(135deg,#f97316,#ea580c) !important; color: white !important; }
        .marker-music { background: linear-gradient(135deg,#8b5cf6,#7c3aed) !important; color: white !important; }
        .marker-default { background: linear-gradient(135deg,#3b82f6,#2563eb) !important; color: white !important; }

        html.dark .leaflet-layer { filter: brightness(0.85) contrast(1.15); }

        /* Event/Pharmacy drawer - bottom sheet mobile */
        #eventDrawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 600;
            background: white;
            border-radius: 24px 24px 0 0;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
            max-height: 85dvh;
            overflow-y: auto;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
        html.dark #eventDrawer { background: #0f172a; box-shadow: 0 -10px 40px rgba(0,0,0,0.5); }
        #eventDrawer.open { transform: translateY(0); }
        @media (min-width: 768px) {
            #eventDrawer {
                left: 24px;
                right: auto;
                bottom: 24px;
                width: 400px;
                max-height: 80vh;
                border-radius: 20px;
            }
        }

        .drawer-handle {
            width: 40px;
            height: 4px;
            background: #cbd5e1;
            border-radius: 2px;
            margin: 12px auto 8px;
            cursor: pointer;
        }
        html.dark .drawer-handle { background: #475569; }
        @media (min-width: 768px) { .drawer-handle { display: none; } }

        /* Geolocate button */
        .btn-geo {
            width: 44px;
            height: 44px;
            min-width: 44px;
            min-height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: none;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }
        html.dark .btn-geo { background: #1e293b; color: #94a3b8; box-shadow: 0 2px 12px rgba(0,0,0,0.3); }
        .btn-geo:hover { background: #f1f5f9; color: #0055FF; transform: scale(1.05); }
        html.dark .btn-geo:hover { background: #334155; color: #60a5fa; }
        .btn-geo:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-900">

    <!-- Top bar -->
    <div class="map-top-bar safe-top safe-left safe-right">
        <div class="flex items-center gap-2 sm:gap-3 max-w-2xl mx-auto">
            <a href="index" class="nav-haptic w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-white/95 dark:bg-slate-800/95 backdrop-blur-md shadow-lg text-slate-700 dark:text-white hover:scale-105 active:scale-95 transition-transform" aria-label="Back">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="flex-1 flex items-center gap-2 bg-white/95 dark:bg-slate-800/95 backdrop-blur-md rounded-xl shadow-lg h-11 sm:h-12 px-4">
                <i class="fas fa-search text-slate-400 text-sm"></i>
                <input type="text" id="mapSearch" placeholder="<?php echo $t['search_placeholder']; ?>" 
                    class="flex-1 bg-transparent border-none outline-none text-sm sm:text-base text-slate-800 dark:text-white placeholder-slate-400 min-w-0" 
                    autocomplete="off">
            </div>
            <button type="button" onclick="toggleFilters()" class="nav-haptic w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-white/95 dark:bg-slate-800/95 backdrop-blur-md shadow-lg text-pink-500 hover:scale-105 active:scale-95 transition-transform" aria-label="Filters">
                <i class="fas fa-filter text-lg"></i>
            </button>
            <button type="button" onclick="toggleTrails()" class="nav-haptic w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-emerald-500 text-white shadow-lg hover:scale-105 active:scale-95 transition-transform" title="Lycian Way">
                <i class="fas fa-hiking text-lg"></i>
            </button>
            <button type="button" id="btnGeo" onclick="geolocate()" class="btn-geo" aria-label="<?php echo $lang == 'en' ? 'My location' : 'Konumum'; ?>">
                <i class="fas fa-location-crosshairs"></i>
            </button>
        </div>
    </div>

    <!-- Filter panel -->
    <div id="filterDrawer">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white"><?php echo $t_cat; ?></h3>
            <button type="button" onclick="toggleFilters()" class="w-9 h-9 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 dark:hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="space-y-1">
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="checkbox" value="all" class="category-filter rounded text-pink-500 focus:ring-pink-500" onchange="filterMap()" checked>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><?php echo $t_all; ?></span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="checkbox" value="party" class="category-filter rounded text-pink-500 focus:ring-pink-500" onchange="filterMap()" checked>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><?php echo $t_party; ?></span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="checkbox" value="food" class="category-filter rounded text-pink-500 focus:ring-pink-500" onchange="filterMap()" checked>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><?php echo $t_food; ?></span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <input type="checkbox" value="music" class="category-filter rounded text-pink-500 focus:ring-pink-500" onchange="filterMap()" checked>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><?php echo $t_music; ?></span>
            </label>
        </div>
    </div>

    <!-- Trail sidebar -->
    <div id="trailGuide">
        <div class="p-6 safe-top safe-bottom">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white"><?php echo $t_trail; ?></h2>
                <button type="button" onclick="toggleTrails()" class="w-9 h-9 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 dark:hover:text-white transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="sectionContainer" class="space-y-3"></div>
        </div>
    </div>

    <div id="map"></div>

    <!-- Event/Pharmacy drawer -->
    <div id="eventDrawer">
        <div class="drawer-handle" onclick="closeDrawer()"></div>
        <div class="p-6 pt-0 relative">
            <button type="button" onclick="closeDrawer()" class="absolute top-2 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-red-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
            <img id="drawerImage" src="" alt="" class="w-full h-44 sm:h-52 object-cover rounded-2xl mb-4 shadow-md bg-slate-200 dark:bg-slate-700">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span id="drawerCategory" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-pink-100 text-pink-600 dark:bg-pink-900/30 dark:text-pink-300 uppercase"></span>
                <span class="text-xs text-slate-400">•</span>
                <span id="drawerTime" class="text-xs font-semibold text-slate-500 dark:text-slate-400"></span>
            </div>
            <h2 id="drawerTitle" class="text-xl font-bold text-slate-800 dark:text-white mb-1"></h2>
            <p id="drawerVenue" class="text-sm font-medium text-pink-500 dark:text-pink-400 mb-3"></p>
            <div id="drawerDescWrap">
                <p id="drawerDesc" class="text-sm text-slate-600 dark:text-slate-300 mb-6 line-clamp-3"></p>
            </div>
            <div class="flex gap-3">
                <a id="drawerLink" href="#" class="flex-1 bg-gradient-to-r from-pink-500 to-violet-500 text-white font-bold py-3.5 rounded-xl text-center shadow-lg hover:opacity-90 transition-opacity">
                    <?php echo $t_details; ?>
                </a>
                <a id="drawerDirections" href="#" target="_blank" rel="noopener" class="w-14 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-white font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <i class="fas fa-location-arrow"></i>
                </a>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const MAP_LANG = '<?php echo $lang; ?>';
        const lycianBounds = L.latLngBounds(L.latLng(35.8, 28.5), L.latLng(37.2, 31.0));
        const map = L.map('map', {
            zoomControl: false,
            gestureHandling: true,
            maxBounds: lycianBounds,
            maxBoundsViscosity: 1.0,
            minZoom: 8,
            maxZoom: 18
        }).setView([36.2662, 29.4124], 10);

        L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenTopoMap, OSM',
            maxZoom: 17
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        let markers = [];
        let allEvents = [];
        let allPharmacies = [];
        let trailLayers = [];

        function getIcon(category) {
            let className = 'marker-default';
            let iconClass = 'fa-star';
            if (['party', 'dance', 'nightlife'].includes(category)) { className = 'marker-party'; iconClass = 'fa-cocktail'; }
            else if (['food', 'dinner', 'tasting'].includes(category)) { className = 'marker-food'; iconClass = 'fa-utensils'; }
            else if (['music', 'concert', 'live'].includes(category)) { className = 'marker-music'; iconClass = 'fa-music'; }
            return L.divIcon({
                className: 'custom-div-icon',
                html: `<div class="custom-marker ${className} w-10 h-10"><i class="fas ${iconClass} text-lg"></i></div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });
        }

        const drawer = document.getElementById('eventDrawer');
        const filterDrawer = document.getElementById('filterDrawer');
        const trailGuide = document.getElementById('trailGuide');

        function openDrawer(evt) {
            document.getElementById('drawerImage').src = evt.image || 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600';
            document.getElementById('drawerImage').alt = evt.title;
            document.getElementById('drawerTitle').textContent = evt.title;
            document.getElementById('drawerVenue').innerHTML = '<i class="fas fa-map-marker-alt mr-1"></i> ' + (evt.venue || '');
            document.getElementById('drawerTime').textContent = (evt.date || '') + ' ' + (evt.time || '');
            document.getElementById('drawerCategory').textContent = evt.category || '';
            document.getElementById('drawerCategory').className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-pink-100 text-pink-600 dark:bg-pink-900/30 dark:text-pink-300 uppercase';
            document.getElementById('drawerDesc').textContent = evt.desc || '';
            document.getElementById('drawerDescWrap').innerHTML = '<p id="drawerDesc" class="text-sm text-slate-600 dark:text-slate-300 mb-6 line-clamp-3">' + (evt.desc || '') + '</p>';
            document.getElementById('drawerLink').href = 'event_detail.php?id=' + (evt.id || '');
            document.getElementById('drawerLink').style.display = '';
            document.getElementById('drawerDirections').href = 'https://www.google.com/maps/dir/?api=1&destination=' + evt.lat + ',' + evt.lng;
            drawer.classList.add('open');
            map.flyTo([evt.lat, evt.lng], 16, { duration: 0.5, padding: [0, 0, 120, 0] });
        }

        function openPharmacyDrawer(p) {
            document.getElementById('drawerImage').src = 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=600';
            document.getElementById('drawerImage').alt = p.name;
            document.getElementById('drawerTitle').textContent = p.name;
            document.getElementById('drawerVenue').innerHTML = '<i class="fas fa-map-marker-alt mr-1"></i> ' + (p.address || '');
            document.getElementById('drawerTime').textContent = '<?php echo $t_duty_pharmacy; ?>';
            document.getElementById('drawerCategory').textContent = '<?php echo $t_pharmacy; ?>';
            document.getElementById('drawerCategory').className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-300 uppercase';
            document.getElementById('drawerDescWrap').innerHTML = '<div class="text-center bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-100 dark:border-red-900/50 mb-6"><p class="text-lg font-bold text-red-600 dark:text-red-400 mb-2">' + (p.phone || '') + '</p><a href="tel:' + (p.phone || '') + '" class="inline-block bg-red-600 text-white px-6 py-2.5 rounded-full font-bold shadow-lg hover:bg-red-700 transition-colors"><i class="fas fa-phone-alt mr-2"></i> <?php echo $t_call_now; ?></a></div>';
            document.getElementById('drawerLink').style.display = 'none';
            document.getElementById('drawerDirections').href = 'https://www.google.com/maps/dir/?api=1&destination=' + p.lat + ',' + p.lng;
            drawer.classList.add('open');
            map.flyTo([p.lat, p.lng], 16, { duration: 0.5, padding: [0, 0, 120, 0] });
        }

        function closeDrawer() {
            drawer.classList.remove('open');
        }

        function toggleFilters() {
            filterDrawer.classList.toggle('open');
        }

        function toggleTrails() {
            trailGuide.classList.toggle('open');
            if (trailLayers.length === 0) loadLycianWay();
        }

        function filterMap() {
            const checked = Array.from(document.querySelectorAll('.category-filter:checked')).map(c => c.value);
            let filtered = allEvents;
            if (!checked.includes('all') && checked.length > 0) {
                filtered = allEvents.filter(e => {
                    if (checked.includes('party') && ['party','dance','nightlife'].includes(e.category)) return true;
                    if (checked.includes('food') && ['food','dinner','tasting'].includes(e.category)) return true;
                    if (checked.includes('music') && ['music','concert','live'].includes(e.category)) return true;
                    return false;
                });
            }
            renderMarkers(filtered);
        }

        function renderMarkers(events) {
            markers.forEach(m => map.removeLayer(m));
            markers = [];
            (events || []).forEach(evt => {
                const m = L.marker([evt.lat, evt.lng], { icon: getIcon(evt.category) })
                    .addTo(map)
                    .on('click', () => openDrawer(evt));
                markers.push(m);
            });
        }

        function renderPharmacy(p) {
            const icon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="relative w-11 h-11 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center shadow-lg border-2 border-red-500 text-red-600 dark:text-red-400"><i class="fas fa-plus text-xl"></i><span class="absolute -top-1 -right-1 bg-red-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">24h</span></div>',
                iconSize: [44, 44],
                iconAnchor: [22, 22]
            });
            L.marker([p.lat, p.lng], { icon: icon, zIndexOffset: 1000 })
                .addTo(map)
                .on('click', () => openPharmacyDrawer(p));
        }

        function renderTrack(t) {
            const icon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="w-12 h-12 relative"><div class="absolute inset-0 bg-emerald-500 rounded-full animate-ping opacity-20"></div><img src="' + (t.avatar || 'assets/img/default_avatar.png') + '" class="w-10 h-10 rounded-full border-2 border-emerald-500 shadow-lg relative z-10 mx-auto mt-1"><div class="absolute -bottom-1 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full z-20 whitespace-nowrap">' + (t.username || '') + '</div></div>',
                iconSize: [48, 48],
                iconAnchor: [24, 24]
            });
            L.marker([t.lat, t.lng], { icon: icon, zIndexOffset: 2000 })
                .addTo(map)
                .bindPopup('<div class="text-center font-bold">' + (t.username || '') + '<br><span class="text-[10px] text-slate-400">Live</span></div>');
        }

        let userLocMarker = null;
        function geolocate() {
            const btn = document.getElementById('btnGeo');
            if (!navigator.geolocation) return;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude, lng = pos.coords.longitude;
                    if (userLocMarker) map.removeLayer(userLocMarker);
                    userLocMarker = L.marker([lat, lng], {
                        icon: L.divIcon({
                            className: 'custom-div-icon',
                            html: '<div class="w-8 h-8 rounded-full bg-blue-500 border-3 border-white shadow-lg flex items-center justify-center"><i class="fas fa-user text-white text-xs"></i></div>',
                            iconSize: [32, 32],
                            iconAnchor: [16, 16]
                        }),
                        zIndexOffset: 3000
                    }).addTo(map);
                    map.flyTo([lat, lng], 14, { duration: 0.8 });
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                },
                function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                }
            );
        }

        document.getElementById('mapSearch').addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();
            if (!val) { renderMarkers(allEvents); return; }
            const filtered = allEvents.filter(evt =>
                (evt.title || '').toLowerCase().includes(val) ||
                (evt.venue || '').toLowerCase().includes(val)
            );
            renderMarkers(filtered);
        });

        async function loadLycianWay() {
            const btn = document.querySelector('button[title="Lycian Way"]');
            if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                const res = await fetch('api/get_trail_data.php?lang=' + MAP_LANG);
                const result = await res.json();
                if (result.status !== 'success') throw new Error('No data');
                const data = result.data;

                const sectionContainer = document.getElementById('sectionContainer');
                sectionContainer.innerHTML = '';
                (data.sections || []).forEach(s => {
                    const card = document.createElement('div');
                    card.className = 'section-card';
                    card.innerHTML = '<div class="flex justify-between items-start mb-1"><h4 class="font-bold text-slate-800 dark:text-white">' + s.name + '</h4><span class="text-xs px-2 py-1 rounded bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-bold">' + (s.difficulty || '') + '</span></div><div class="flex gap-4 text-xs text-slate-500 dark:text-slate-400 mb-2"><span><i class="fas fa-route mr-1"></i> ' + (s.km || '') + ' km</span><span><i class="fas fa-calendar-day mr-1"></i> ' + (s.days || '') + ' days</span></div><p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">' + (s.desc || '') + '</p>';
                    card.onclick = function() {
                        map.flyTo([s.start.lat, s.start.lng], 13);
                        if (window.innerWidth < 768) toggleTrails();
                    };
                    sectionContainer.appendChild(card);
                });

                const getPoiStyle = (type) => {
                    const styles = { ANCIENT_SITE: ['fa-landmark','bg-amber-500'], CAMPING: ['fa-campground','bg-orange-500'], WATER: ['fa-tint','bg-blue-500'], VIEWPOINT: ['fa-mountain','bg-purple-500'], RESUPPLY: ['fa-shopping-bag','bg-green-500'] };
                    return styles[type] || ['fa-info','bg-emerald-500'];
                };
                (data.pois || []).forEach(poi => {
                    const [iconClass, color] = getPoiStyle(poi.type);
                    const icon = L.divIcon({
                        className: 'custom-marker',
                        html: '<div class="w-8 h-8 rounded-full ' + color + ' flex items-center justify-center text-white border-2 border-white shadow-lg"><i class="fas ' + iconClass + ' text-xs"></i></div>',
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    });
                    const m = L.marker([poi.lat, poi.lng], { icon: icon }).bindPopup('<div class="poi-popup-content p-1"><span class="poi-type-badge ' + color + ' bg-opacity-30">' + (poi.type || '').replace('_',' ') + '</span><h3 class="font-bold text-lg mb-1">' + (poi.name || '') + '</h3><p class="text-sm mb-2 opacity-80">' + (poi.desc || '') + '</p><a href="https://www.google.com/maps/search/?api=1&query=' + poi.lat + ',' + poi.lng + '" target="_blank" rel="noopener" class="block mt-3 text-center py-2 bg-slate-100 dark:bg-slate-700 rounded-lg text-xs font-bold hover:bg-emerald-500 hover:text-white transition-colors"><i class="fas fa-directions mr-1"></i> Google Maps</a></div>', { maxWidth: 280 });
                    trailLayers.push(m);
                    m.addTo(map);
                });

                try {
                    const overpassRes = await fetch('api/get_overpass_pois.php');
                    const overpassData = await overpassRes.json();
                    if (overpassData.status === 'success' && overpassData.pois) {
                        overpassData.pois.forEach(poi => {
                            const icon = L.divIcon({
                                className: 'custom-marker',
                                html: '<div class="w-7 h-7 rounded-full bg-cyan-500 flex items-center justify-center text-white border-2 border-white shadow-lg"><i class="fas fa-umbrella-beach text-[10px]"></i></div>',
                                iconSize: [28, 28],
                                iconAnchor: [14, 14]
                            });
                            const m = L.marker([poi.lat, poi.lng], { icon: icon }).bindPopup('<div class="poi-popup-content p-1"><span class="poi-type-badge bg-cyan-100 text-cyan-700">' + (poi.type || '') + '</span><h3 class="font-bold text-lg mb-1">' + (poi.name || '') + '</h3>' + (poi.desc ? '<p class="text-sm mb-2 opacity-80">' + poi.desc + '</p>' : '') + '<a href="https://www.google.com/maps/search/?api=1&query=' + poi.lat + ',' + poi.lng + '" target="_blank" rel="noopener" class="block mt-2 text-center py-2 bg-slate-100 dark:bg-slate-700 rounded-lg text-xs font-bold hover:bg-emerald-500 hover:text-white transition-colors"><i class="fas fa-directions mr-1"></i> Google Maps</a></div>', { maxWidth: 280 });
                            trailLayers.push(m);
                            m.addTo(map);
                        });
                    }
                } catch (e) {}

                (data.tracks || []).forEach(url => {
                    try {
                        new L.GPX(url, {
                            async: true,
                            polyline_options: { color: '#10b981', weight: 5, opacity: 0.8, dashArray: '4, 8' },
                            marker_options: { startIconUrl: null, endIconUrl: null, shadowUrl: null, wptIconUrls: { '': null } },
                            parseElements: ['track', 'route']
                        }).on('loaded', e => {
                            trailLayers.push(e.target);
                            e.target.addTo(map);
                        });
                    } catch (e) {}
                });
            } catch (err) {
                console.warn('Trail load error:', err);
            } finally {
                if (btn) btn.innerHTML = '<i class="fas fa-hiking text-lg"></i>';
            }
        }

        fetch('api/map_events.php')
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') return;
                allEvents = data.events || [];
                (data.pharmacies || []).forEach(p => { allPharmacies.push(p); renderPharmacy(p); });
                if (data.pharmacy) renderPharmacy(data.pharmacy);
                renderMarkers(allEvents);
                (data.tracks || []).forEach(t => renderTrack(t));
                if (new URLSearchParams(location.search).get('trails') === '1') toggleTrails();
                else setTimeout(loadLycianWay, 800);
            })
            .catch(() => setTimeout(loadLycianWay, 500));

        // Expose to global for onclick handlers
        window.geolocate = geolocate;
        window.closeDrawer = closeDrawer;
        window.toggleFilters = toggleFilters;
        window.toggleTrails = toggleTrails;
        window.filterMap = filterMap;

    })();
    </script>
</body>
</html>
