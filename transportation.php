<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';

$kalkan_to_kas = ["07:20", "07:50", "08:20", "08:45", "09:15", "09:50", "10:30", "11:10", "11:50", "12:30", "13:15", "13:55", "14:35", "15:15", "16:00", "16:45", "17:30", "18:35"];
$kas_to_kalkan = ["08:00", "09:00", "09:45", "10:20", "10:55", "11:30", "12:00", "12:40", "13:20", "14:00", "14:40", "15:20", "16:00", "16:40", "17:20", "18:00", "19:00", "20:15"];

// Get current time in Turkey
date_default_timezone_set('Europe/Istanbul');
$now = date('H:i');

function getNextBus($times, $now) {
    foreach ($times as $time) {
        if ($time > $now) return $time;
    }
    return null; // No more buses today
}

$next_kalkan = getNextBus($kalkan_to_kas, $now);
$next_kas = getNextBus($kas_to_kalkan, $now);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['transportation_hub']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .dark .glass { background: rgba(15, 23, 42, 0.7); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-6 pt-24">
        <!-- Hero Section -->
        <div class="relative rounded-[2.5rem] overflow-hidden mb-10 h-64 shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-violet-600/90 to-blue-500/90 mix-blend-multiply z-10"></div>
            <img src="assets/transport_hero.jpg" class="absolute inset-0 w-full h-full object-cover">
            <div class="relative z-20 h-full flex flex-col justify-center px-10">
                <h1 class="text-4xl md:text-5xl font-black text-white mb-2"><?php echo $t['transportation_hub']; ?></h1>
                <p class="text-white/80 text-lg font-medium max-w-lg"><?php echo $t['bus_schedules']; ?> & Local Travel Info</p>
                <div class="mt-4">
                     <a href="rides" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white px-4 py-2 rounded-xl transition-all font-bold text-sm border border-white/20">
                        <i class="fas fa-car"></i> Looking for a ride share? Click here
                    </a>
                </div>
            </div>
        </div>

        <!-- Bus Schedules Content -->
        <div id="content-bus" class="tab-content transition-all duration-300">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Kalkan to Kas -->
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-blue-50 mb-1"><?php echo $t['kalkan_kas_route']; ?></h3>
                            <p class="text-xs font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest"><?php echo $t['everyday']; ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-500 text-xl">
                            <i class="fas fa-bus"></i>
                        </div>
                    </div>

                    <?php if ($next_kalkan): ?>
                    <!-- Next Bus Card -->
                    <div class="bg-gradient-to-br from-violet-600 to-violet-700 rounded-3xl p-6 mb-8 text-white shadow-lg shadow-violet-500/20 relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fas fa-bus text-8xl"></i>
                        </div>
                        <p class="text-[10px] font-extrabold uppercase tracking-widest opacity-80 mb-1"><?php echo $t['next_bus']; ?></p>
                        <div class="text-4xl font-black mb-1"><?php echo $next_kalkan; ?></div>
                        <p class="text-xs font-medium opacity-70"><?php echo $t['kalkan_kas_route']; ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach ($kalkan_to_kas as $time): ?>
                            <div class="p-3 rounded-2xl text-center text-sm font-bold border <?php echo $time == $next_kalkan ? 'bg-violet-50 border-violet-200 text-violet-700 dark:bg-violet-900/30 dark:border-violet-500/30 dark:text-violet-300' : 'bg-slate-50 border-slate-100 text-slate-700 dark:bg-slate-900/50 dark:border-slate-700/50 dark:text-white hover:bg-white dark:hover:bg-slate-700 transition-colors'; ?>">
                                <?php echo $time; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Kas to Kalkan -->
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-blue-50 mb-1"><?php echo $t['kas_kalkan_route']; ?></h3>
                            <p class="text-xs font-bold text-pink-600 dark:text-pink-400 uppercase tracking-widest"><?php echo $t['everyday']; ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-pink-50 dark:bg-pink-900/20 flex items-center justify-center text-pink-500 text-xl">
                            <i class="fas fa-bus"></i>
                        </div>
                    </div>

                    <?php if ($next_kas): ?>
                    <!-- Next Bus Card -->
                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-3xl p-6 mb-8 text-white shadow-lg shadow-pink-500/20 relative overflow-hidden group">
                        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                            <i class="fas fa-bus text-8xl"></i>
                        </div>
                        <p class="text-[10px] font-extrabold uppercase tracking-widest opacity-80 mb-1"><?php echo $t['next_bus']; ?></p>
                        <div class="text-4xl font-black mb-1"><?php echo $next_kas; ?></div>
                        <p class="text-xs font-medium opacity-70"><?php echo $t['kas_kalkan_route']; ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach ($kas_to_kalkan as $time): ?>
                            <div class="p-3 rounded-2xl text-center text-sm font-bold border <?php echo $time == $next_kas ? 'bg-pink-50 border-pink-200 text-pink-600 dark:bg-pink-900/20 dark:border-pink-500/30' : 'bg-slate-50 border-slate-100 text-slate-500 dark:bg-slate-900/50 dark:border-slate-700/50 hover:bg-white dark:hover:bg-slate-700 transition-colors'; ?>">
                                <?php echo $time; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="mt-10 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-500/20 rounded-[2rem] p-8 flex flex-col md:flex-row items-center gap-8 group">
                <div class="w-20 h-20 rounded-[2rem] bg-white dark:bg-slate-800 shadow-xl flex items-center justify-center text-blue-500 text-3xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h4 class="text-xl font-bold mb-2"><?php echo $t['last_updated']; ?>: December 2025</h4>
                    <p class="text-slate-600 dark:text-slate-400">Dolmuş schedules change periodically. This information is provided as a guide based on the latest seasonal data.</p>
                </div>
            </div>
        </div>
        
    </main>

</body>
</html>
