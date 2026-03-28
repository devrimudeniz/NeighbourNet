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
    <title><?php echo $t['weather']; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .loc-btn.active {
            background: linear-gradient(to right, #2563eb, #7c3aed);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-violet-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen transition-colors duration-500">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-4xl">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-3 bg-blue-100 dark:bg-blue-900/30 px-6 py-3 rounded-full mb-4">
                <i class="fas fa-cloud-sun text-blue-600 dark:text-blue-400 text-2xl"></i>
                <span class="text-blue-600 dark:text-blue-400 font-black uppercase tracking-wider text-sm">
                    <?php echo $t['weather']; ?>
                </span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black mb-4 text-blue-700 dark:text-blue-400">
                <span id="display-location">Kalkan</span> <?php echo $lang == 'en' ? 'Weather' : 'Hava Durumu'; ?>
            </h1>
        </div>

        <!-- Location Selector -->
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            <?php
            $cities = [
                'kalkan' => 'Kalkan',
                'kas' => 'Kaş',
                'fethiye' => 'Fethiye',
                'dalaman' => 'Dalaman'
            ];
            foreach ($cities as $key => $name):
            ?>
            <button onclick="changeLocation('<?php echo $key; ?>')" id="btn-<?php echo $key; ?>" class="loc-btn px-6 py-2.5 rounded-2xl bg-white/50 dark:bg-slate-800/50 border border-white/30 dark:border-slate-700/50 font-bold text-sm transition-all hover:scale-105 <?php echo $key == 'kalkan' ? 'active' : ''; ?>">
                <?php echo $name; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Main Weather Card -->
        <div id="weather-card" class="glass-card rounded-[2.5rem] p-8 md:p-12 mb-8 text-center relative overflow-hidden hidden">
            <!-- Background Decorative Blobs -->
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-amber-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-blue-400/20 rounded-full blur-3xl"></div>

            <div class="relative z-10">
                <div id="main-icon" class="text-8xl md:text-9xl mb-6 weather-anim inline-block">
                    <i class="fas fa-sun text-amber-500"></i>
                </div>
                <div class="flex flex-col items-center">
                    <div class="text-7xl md:text-8xl font-black text-slate-900 dark:text-blue-50 mb-2">
                        <span id="temp-val">--</span><span class="text-4xl md:text-5xl align-top mt-4 inline-block">°C</span>
                    </div>
                    <p id="condition-text" class="text-xl md:text-2xl font-bold text-slate-600 dark:text-blue-300/90 capitalize mb-8">
                        Loading...
                    </p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="p-4 rounded-3xl bg-white/40 dark:bg-slate-800/40 border border-white/20 dark:border-slate-700/50">
                        <i class="fas fa-temperature-high text-red-500 mb-2"></i>
                        <span class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1"><?php echo $lang == 'en' ? 'Feels Like' : 'Hissedilen'; ?></span>
                        <span class="text-lg font-black text-slate-900 dark:text-white" id="feels-like">--°</span>
                    </div>
                    <div class="p-4 rounded-3xl bg-white/40 dark:bg-slate-800/40 border border-white/20 dark:border-slate-700/50">
                        <i class="fas fa-wind text-blue-400 mb-2"></i>
                        <span class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1"><?php echo $t['wind']; ?></span>
                        <span class="text-lg font-black text-slate-900 dark:text-white" id="wind-speed">-- km/h</span>
                    </div>
                    <div class="p-4 rounded-3xl bg-white/40 dark:bg-slate-800/40 border border-white/20 dark:border-slate-700/50">
                        <i class="fas fa-tint text-indigo-500 mb-2"></i>
                        <span class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1"><?php echo $t['humidity']; ?></span>
                        <span class="text-lg font-black text-slate-900 dark:text-white" id="humidity-val">--%</span>
                    </div>
                    <div class="p-4 rounded-3xl bg-white/40 dark:bg-slate-800/40 border border-white/20 dark:border-slate-700/50">
                        <i class="fas fa-water text-cyan-500 mb-2"></i>
                        <span class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1"><?php echo $t['sea_temp']; ?></span>
                        <span class="text-lg font-black text-blue-700 dark:text-blue-300" id="sea-temp-val">--°</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading" class="text-center py-20">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
            <p class="mt-4 text-slate-600 dark:text-slate-400"><?php echo $t['loading']; ?></p>
        </div>

        <!-- Info Section -->
        <div class="grid md:grid-cols-2 gap-8">
            <div class="glass-card rounded-3xl p-6 border border-white/20 dark:border-slate-800/50 shadow-xl">
                 <h3 class="text-lg font-black mb-4 flex items-center gap-2 dark:text-blue-100">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <?php echo $lang == 'en' ? 'About Weather Data' : 'Hava Durumu Bilgisi Hakkında'; ?>
                 </h3>
                 <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    <?php echo $lang == 'en' ? 'Weather data is updated every 30 minutes from OpenWeatherMap. Sea temperature is measured via Open-Meteo.' : 'Hava durumu verileri her 30 dakikada bir OpenWeatherMap üzerinden güncellenmektedir. Deniz suyu sıcaklığı Open-Meteo aracılığıyla ölçülmektedir.'; ?>
                 </p>
            </div>
            <div class="glass-card rounded-3xl p-6 border border-white/20 dark:border-slate-800/50 shadow-xl">
                 <h3 class="text-lg font-black mb-4 flex items-center gap-2 dark:text-blue-100">
                    <i class="fas fa-map-marker-alt text-pink-500"></i>
                    <?php echo $lang == 'en' ? 'Selected Location' : 'Seçili Lokasyon'; ?>
                 </h3>
                 <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    <?php echo $lang == 'en' ? 'You are currently viewing the live conditions for ' : 'Şu anda '; ?><span class="font-bold text-blue-600 dark:text-blue-400" id="info-location">Kalkan</span><?php echo $lang == 'en' ? '.' : ' için canlı hava durumunu görüntülüyorsunuz.'; ?>
                 </p>
            </div>
        </div>
    </main>

    <script>
        let currentLocation = 'kalkan';

        async function fetchWeather(location = 'kalkan') {
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('weather-card').classList.add('hidden');
            
            try {
                const response = await fetch(`/api/get_weather.php?location=${location}`);
                const result = await response.json();

                if (result.status === 'success' && result.data) {
                    const data = result.data;
                    document.getElementById('loading').classList.add('hidden');
                    document.getElementById('weather-card').classList.remove('hidden');

                    // Update values
                    document.getElementById('temp-val').textContent = data.temp;
                    document.getElementById('feels-like').textContent = data.feels_like + '°';
                    document.getElementById('wind-speed').textContent = data.wind_speed + ' km/h';
                    document.getElementById('humidity-val').textContent = data.humidity + '%';
                    document.getElementById('sea-temp-val').textContent = data.sea_temp ? data.sea_temp + '°' : '--';
                    document.getElementById('condition-text').textContent = data.description;
                    document.getElementById('display-location').textContent = data.location;
                    document.getElementById('info-location').textContent = data.location;

                    // Update Icon
                    const mainIcon = document.getElementById('main-icon');
                    const condition = data.main.toLowerCase();
                    let iconHtml = '<i class="fas fa-sun text-amber-500"></i>';

                    if (condition.includes('cloud')) {
                         iconHtml = '<i class="fas fa-cloud text-slate-400"></i>';
                    } else if (condition.includes('rain')) {
                         iconHtml = '<i class="fas fa-cloud-showers-heavy text-blue-500"></i>';
                    } else if (condition.includes('thunderstorm')) {
                         iconHtml = '<i class="fas fa-bolt text-yellow-500"></i>';
                    } else if (condition.includes('clear')) {
                         iconHtml = '<i class="fas fa-sun text-amber-500"></i>';
                    }

                    mainIcon.innerHTML = iconHtml;
                }
            } catch (error) {
                console.error('Weather fetch error:', error);
                document.getElementById('loading').innerHTML = '<p class="text-red-500 font-bold">Failed to load weather data.</p>';
            }
        }

        function changeLocation(location) {
            if (location === currentLocation) return;
            
            // UI Update
            document.querySelectorAll('.loc-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`btn-${location}`).classList.add('active');
            
            currentLocation = location;
            fetchWeather(location);
        }

        fetchWeather();
    </script>

</body>
</html>
