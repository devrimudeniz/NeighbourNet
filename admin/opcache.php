<?php
/**
 * OPcache Status Dashboard
 * Premium admin interface for monitoring PHP OPcache performance
 */

require_once '../includes/db.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";
require_once "../includes/icon_helper.php";

// Check if OPcache is available
$opcache_available = function_exists('opcache_get_status');
$opcache_status = null;
$opcache_config = null;

if ($opcache_available) {
    $opcache_status = @opcache_get_status(false);
    $opcache_config = @opcache_get_configuration();
}

// Handle cache actions
if (isset($_POST['action']) && $opcache_available) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'reset':
            $result = opcache_reset();
            echo json_encode(['success' => $result, 'message' => $result ? 'Cache cleared successfully' : 'Failed to clear cache']);
            exit;
            
        case 'invalidate':
            if (isset($_POST['file'])) {
                $result = opcache_invalidate($_POST['file'], true);
                echo json_encode(['success' => $result, 'message' => $result ? 'File invalidated' : 'Failed to invalidate']);
            }
            exit;
            
        case 'refresh':
            $status = opcache_get_status(false);
            echo json_encode(['success' => true, 'status' => $status]);
            exit;
    }
}

// Calculate stats
$memory_usage = 0;
$hit_rate = 0;
$cached_files = 0;
$memory_used = 0;
$memory_free = 0;
$memory_wasted = 0;

if ($opcache_status && isset($opcache_status['memory_usage'])) {
    $mem = $opcache_status['memory_usage'];
    $memory_used = $mem['used_memory'];
    $memory_free = $mem['free_memory'];
    $memory_wasted = $mem['wasted_memory'];
    $memory_total = $memory_used + $memory_free + $memory_wasted;
    $memory_usage = $memory_total > 0 ? round(($memory_used / $memory_total) * 100, 1) : 0;
    
    if (isset($opcache_status['opcache_statistics'])) {
        $stats = $opcache_status['opcache_statistics'];
        $hits = $stats['hits'] ?? 0;
        $misses = $stats['misses'] ?? 0;
        $total_requests = $hits + $misses;
        $hit_rate = $total_requests > 0 ? round(($hits / $total_requests) * 100, 2) : 0;
        $cached_files = $stats['num_cached_scripts'] ?? 0;
    }
}

// Format bytes helper
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPcache Status | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring circle {
            transition: stroke-dashoffset 0.5s ease-in-out;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
            50% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.6); }
        }
        .glow-success { animation: pulse-glow 2s infinite; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white min-h-screen transition-colors duration-300 overflow-x-hidden">

    <?php include "includes/sidebar.php"; ?>

    <!-- Main Content - Responsive -->
    <main class="lg:ml-72 flex-1 p-4 sm:p-6 lg:p-8 xl:p-10 pt-20 lg:pt-10 min-w-0 w-full max-w-full overflow-x-hidden">
        
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
            <div>
                <h1 class="text-3xl lg:text-4xl font-black tracking-tight mb-2">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-emerald-500 to-cyan-500">
                        <i class="fas fa-bolt mr-2"></i>OPcache Status
                    </span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400">PHP opcode cache performance monitoring</p>
            </div>
            
            <?php if ($opcache_available && $opcache_status): ?>
            <div class="flex items-center gap-3">
                <button onclick="refreshStatus()" class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center gap-2">
                    <i class="fas fa-sync-alt" id="refresh-icon"></i> Refresh
                </button>
                <button onclick="confirmReset()" class="px-6 py-3 rounded-2xl bg-gradient-to-r from-red-500 to-orange-500 text-white font-bold shadow-lg shadow-red-500/30 hover:shadow-red-500/50 transition-all flex items-center gap-2">
                    <i class="fas fa-trash-alt"></i> Clear Cache
                </button>
            </div>
            <?php endif; ?>
        </header>

        <?php if (!$opcache_available): ?>
        <!-- OPcache Not Available -->
        <div class="glass-card p-12 rounded-[3rem] text-center">
            <div class="w-24 h-24 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl"></i>
            </div>
            <h2 class="text-2xl font-black mb-4 text-red-500">OPcache Not Available</h2>
            <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-6">
                PHP OPcache extension is not enabled on this server. Contact your hosting provider or enable it in php.ini.
            </p>
            <code class="block bg-slate-100 dark:bg-slate-800 p-4 rounded-xl text-sm font-mono">
                zend_extension=opcache<br>
                opcache.enable=1
            </code>
        </div>
        
        <?php elseif (!$opcache_status): ?>
        <!-- OPcache Disabled -->
        <div class="glass-card p-12 rounded-[3rem] text-center">
            <div class="w-24 h-24 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-pause-circle text-yellow-500 text-4xl"></i>
            </div>
            <h2 class="text-2xl font-black mb-4 text-yellow-500">OPcache Disabled</h2>
            <p class="text-slate-500 dark:text-slate-400">OPcache is installed but currently disabled.</p>
        </div>
        
        <?php else: ?>
        
        <!-- Status Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            
            <!-- Status -->
            <div class="glass-card p-6 rounded-[2.5rem] shadow-xl <?php echo $opcache_status['opcache_enabled'] ? 'glow-success' : ''; ?>">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br <?php echo $opcache_status['opcache_enabled'] ? 'from-emerald-500 to-teal-400' : 'from-red-500 to-orange-400'; ?> rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="fas <?php echo $opcache_status['opcache_enabled'] ? 'fa-check' : 'fa-times'; ?> text-xl"></i>
                    </div>
                    <span class="px-3 py-1 <?php echo $opcache_status['opcache_enabled'] ? 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400'; ?> rounded-full text-[10px] font-black">
                        <?php echo $opcache_status['opcache_enabled'] ? 'ACTIVE' : 'DISABLED'; ?>
                    </span>
                </div>
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1">Status</h3>
                <span class="text-2xl font-black"><?php echo $opcache_status['opcache_enabled'] ? 'Enabled' : 'Disabled'; ?></span>
            </div>

            <!-- Memory Usage -->
            <div class="glass-card p-6 rounded-[2.5rem] shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-400 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                        <i class="fas fa-memory text-xl"></i>
                    </div>
                    <div class="relative w-12 h-12">
                        <svg class="progress-ring w-full h-full" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#3b82f6" stroke-width="3" 
                                    stroke-dasharray="100" stroke-dashoffset="<?php echo 100 - $memory_usage; ?>" stroke-linecap="round"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-blue-500"><?php echo $memory_usage; ?>%</span>
                    </div>
                </div>
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1">Memory Used</h3>
                <span class="text-2xl font-black" id="memory-used"><?php echo formatBytes($memory_used); ?></span>
            </div>

            <!-- Hit Rate -->
            <div class="glass-card p-6 rounded-[2.5rem] shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-400 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-violet-500/30">
                        <i class="fas fa-bullseye text-xl"></i>
                    </div>
                    <div class="relative w-12 h-12">
                        <svg class="progress-ring w-full h-full" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#8b5cf6" stroke-width="3" 
                                    stroke-dasharray="100" stroke-dashoffset="<?php echo 100 - $hit_rate; ?>" stroke-linecap="round"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-violet-500"><?php echo $hit_rate; ?>%</span>
                    </div>
                </div>
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1">Hit Rate</h3>
                <span class="text-2xl font-black" id="hit-rate"><?php echo $hit_rate; ?>%</span>
            </div>

            <!-- Cached Files -->
            <div class="glass-card p-6 rounded-[2.5rem] shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-400 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-pink-500/30">
                        <i class="fas fa-file-code text-xl"></i>
                    </div>
                </div>
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-1">Cached Files</h3>
                <span class="text-2xl font-black" id="cached-files"><?php echo number_format($cached_files); ?></span>
            </div>
        </div>

        <!-- Detailed Stats -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8 mb-8 sm:mb-10">
            
            <!-- Memory Breakdown -->
            <div class="glass-card p-8 rounded-[3rem] shadow-xl">
                <h3 class="text-xl font-black mb-6 flex items-center gap-3">
                    <i class="fas fa-chart-pie text-blue-500"></i> Memory Breakdown
                </h3>
                <div class="space-y-6">
                    <!-- Used Memory -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-bold text-slate-500">Used Memory</span>
                            <span class="text-sm font-black text-blue-500"><?php echo formatBytes($memory_used); ?></span>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full" style="width: <?php echo $memory_usage; ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Free Memory -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-bold text-slate-500">Free Memory</span>
                            <span class="text-sm font-black text-emerald-500"><?php echo formatBytes($memory_free); ?></span>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <?php $free_pct = ($memory_used + $memory_free + $memory_wasted) > 0 ? ($memory_free / ($memory_used + $memory_free + $memory_wasted)) * 100 : 0; ?>
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full" style="width: <?php echo $free_pct; ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Wasted Memory -->
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-bold text-slate-500">Wasted Memory</span>
                            <span class="text-sm font-black text-orange-500"><?php echo formatBytes($memory_wasted); ?></span>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <?php $wasted_pct = ($memory_used + $memory_free + $memory_wasted) > 0 ? ($memory_wasted / ($memory_used + $memory_free + $memory_wasted)) * 100 : 0; ?>
                            <div class="h-full bg-gradient-to-r from-orange-500 to-red-500 rounded-full" style="width: <?php echo $wasted_pct; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="glass-card p-8 rounded-[3rem] shadow-xl">
                <h3 class="text-xl font-black mb-6 flex items-center gap-3">
                    <i class="fas fa-chart-bar text-violet-500"></i> Cache Statistics
                </h3>
                <?php 
                $stats = $opcache_status['opcache_statistics'] ?? [];
                ?>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Cache Hits</p>
                        <p class="text-xl font-black text-emerald-500"><?php echo number_format($stats['hits'] ?? 0); ?></p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Cache Misses</p>
                        <p class="text-xl font-black text-red-500"><?php echo number_format($stats['misses'] ?? 0); ?></p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Restarts</p>
                        <p class="text-xl font-black text-orange-500"><?php echo number_format(($stats['oom_restarts'] ?? 0) + ($stats['hash_restarts'] ?? 0) + ($stats['manual_restarts'] ?? 0)); ?></p>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Max Cached Keys</p>
                        <p class="text-xl font-black text-blue-500"><?php echo number_format($stats['max_cached_keys'] ?? 0); ?></p>
                    </div>
                </div>
                
                <?php if (isset($stats['start_time'])): ?>
                <div class="mt-6 p-4 bg-violet-50 dark:bg-violet-900/20 rounded-2xl border border-violet-100 dark:border-violet-800">
                    <p class="text-[10px] font-black text-violet-400 uppercase mb-1">Cache Started</p>
                    <p class="text-sm font-bold text-violet-600 dark:text-violet-300"><?php echo date('d M Y, H:i:s', $stats['start_time']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configuration -->
        <div class="glass-card p-8 rounded-[3rem] shadow-xl">
            <h3 class="text-xl font-black mb-6 flex items-center gap-3">
                <i class="fas fa-cog text-slate-500"></i> Configuration
            </h3>
            <?php if ($opcache_config && isset($opcache_config['directives'])): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 min-w-0">
                <?php 
                $important_directives = [
                    'opcache.enable' => 'Enable',
                    'opcache.enable_cli' => 'CLI Enable',
                    'opcache.memory_consumption' => 'Memory (MB)',
                    'opcache.max_accelerated_files' => 'Max Files',
                    'opcache.validate_timestamps' => 'Validate Timestamps',
                    'opcache.revalidate_freq' => 'Revalidate Freq (s)',
                    'opcache.save_comments' => 'Save Comments',
                    'opcache.fast_shutdown' => 'Fast Shutdown',
                    'opcache.jit' => 'JIT',
                    'opcache.jit_buffer_size' => 'JIT Buffer',
                ];
                
                foreach ($important_directives as $key => $label):
                    $value = $opcache_config['directives'][$key] ?? 'N/A';
                    if (is_bool($value)) $value = $value ? 'Yes' : 'No';
                ?>
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                    <span class="text-sm font-bold text-slate-500"><?php echo $label; ?></span>
                    <span class="text-sm font-black <?php echo ($value === 'Yes' || $value === true || $value == 1) ? 'text-emerald-500' : 'text-slate-700 dark:text-slate-300'; ?>">
                        <?php echo is_numeric($value) ? number_format($value) : $value; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
    </main>

    <script>
    function confirmReset() {
        if (!confirm('Are you sure you want to clear the OPcache? This will cause a temporary performance decrease while the cache rebuilds.')) {
            return;
        }
        
        fetch('opcache.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=reset'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error occurred');
        });
    }

    function refreshStatus() {
        const icon = document.getElementById('refresh-icon');
        icon.classList.add('fa-spin');
        
        fetch('opcache.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=refresh'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .finally(() => {
            icon.classList.remove('fa-spin');
        });
    }
    </script>
</body>
</html>
