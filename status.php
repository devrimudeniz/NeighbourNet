<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// $lang is already set by lang.php

// Neighborhood Map (Key => Display Name Key)
$neighborhoods = [
    'Kalamar' => 'kalamar',
    'Ortaalan' => 'ortaalan',
    'Kordere' => 'kordere',
    'Old Town' => 'old_town',
    'Kiziltas' => 'kiziltas',
    'Islamlar' => 'islamlar',
    'Uzumlu' => 'uzumlu'
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['utilities_status']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        .status-dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; }
        .status-green { background-color: #10b981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
        .status-yellow { background-color: #f59e0b; box-shadow: 0 0 10px rgba(245, 158, 11, 0.4); }
        .status-red { background-color: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 md:px-6 pt-24 pb-12 max-w-4xl">
        
        <!-- Header -->
        <div class="mb-10 text-center md:text-left flex flex-col md:flex-row justify-between items-end gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-black mb-2 text-slate-900 dark:text-white flex items-center gap-3 justify-center md:justify-start">
                    <?php echo heroicon('bolt', 'text-amber-500'); ?>
                    <?php echo $t['utilities_status']; ?>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-lg">
                    <?php echo $t['utilities_desc']; ?>
                </p>
            </div>
            
            <button onclick="openReportModal()" class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-3 rounded-xl font-bold hover:shadow-lg transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $t['report_issue']; ?>
            </button>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4 mb-8 flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-500 mt-1"></i>
            <div>
                <p class="text-sm text-blue-800 dark:text-blue-200 font-bold"><?php echo $t['community_driven']; ?></p>
                <div class="flex gap-4 mt-2 text-xs text-blue-600 dark:text-blue-300">
                    <span class="flex items-center gap-1"><span class="status-dot status-green"></span> <?php echo $t['status_normal']; ?></span>
                    <span class="flex items-center gap-1"><span class="status-dot status-yellow"></span> <?php echo $t['status_warning']; ?></span>
                    <span class="flex items-center gap-1"><span class="status-dot status-red"></span> <?php echo $t['status_critical']; ?></span>
                </div>
            </div>
        </div>

        <!-- Status Grid -->
        <div id="status-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading Skeleton -->
            <?php foreach ($neighborhoods as $key => $langKey): ?>
                <div class="animate-pulse bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 h-40"></div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- Report Modal -->
    <div id="report-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeReportModal()"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl border border-slate-200 dark:border-slate-700 transition-all scale-95 opacity-0" id="modal-content">
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $t['report_outage']; ?></h3>
                <button onclick="closeReportModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 hover:text-red-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="report-form" onsubmit="submitReport(event)">
                <div class="space-y-4">
                    <!-- Neighborhood -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2"><?php echo $t['select_neighborhood']; ?></label>
                        <div class="grid grid-cols-2 gap-2" id="neighborhood-grid">
                            <?php foreach ($neighborhoods as $key => $langKey): ?>
                                <label class="cursor-pointer group relative">
                                    <input type="radio" name="neighborhood" value="<?php echo $key; ?>" class="sr-only" onchange="updateSelection('neighborhood', this)">
                                    <div class="select-btn border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm font-bold text-center text-slate-700 dark:text-slate-300 transition-all hover:bg-slate-50 dark:hover:bg-slate-700 group-hover:border-slate-300 dark:group-hover:border-slate-600">
                                        <?php echo $t[$langKey]; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2"><?php echo $t['outage_type']; ?></label>
                        <div class="flex gap-4" id="type-grid">
                            <label class="flex-1 cursor-pointer group relative">
                                <input type="radio" name="type" value="water" class="sr-only" onchange="updateSelection('type', this)">
                                <div class="select-btn border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center text-slate-700 dark:text-slate-300 transition-all group-hover:border-sky-200 dark:group-hover:border-sky-900">
                                    <i class="fas fa-water text-2xl mb-1 block group-hover:text-sky-500 transition-colors"></i>
                                    <span class="font-bold"><?php echo $t['water']; ?></span>
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer group relative">
                                <input type="radio" name="type" value="electricity" class="sr-only" onchange="updateSelection('type', this)">
                                <div class="select-btn border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center text-slate-700 dark:text-slate-300 transition-all group-hover:border-amber-200 dark:group-hover:border-amber-900">
                                    <i class="fas fa-bolt text-2xl mb-1 block group-hover:text-amber-500 transition-colors"></i>
                                    <span class="font-bold"><?php echo $t['electricity']; ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-red-500 hover:bg-red-600 text-white rounded-xl font-bold shadow-lg shadow-red-500/30 transition-all mt-6 active:scale-95">
                        <?php echo $t['report_issue']; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const t = <?php echo json_encode([
        'water' => $t['water'],
        'electricity' => $t['electricity'],
        'status_normal' => $t['status_normal'],
        'status_warning' => $t['status_warning'],
        'status_critical' => $t['status_critical'],
        'recent_reports' => $t['recent_reports'],
        'reported_by' => $t['reported_by'],
        'report_submitted' => $t['report_submitted'],
        'neighborhoods' => array_map(function($k) use ($t) { return $t[$k]; }, $neighborhoods)
    ]); ?>;
    
    // Neighborhood Translations Map
    const nbMap = <?php echo json_encode($neighborhoods); ?>;

    async function fetchStatus() {
        const grid = document.getElementById('status-grid');
        try {
            const response = await fetch('api/status_api.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                renderGrid(data.data);
            }
        } catch (e) {
            console.error('Fetch error:', e);
        }
    }

    function renderGrid(data) {
        const grid = document.getElementById('status-grid');
        grid.innerHTML = '';
        
        // Loop through defined neighborhoods to maintain order
        for (const [key, langKey] of Object.entries(nbMap)) {
            const stats = data[key] || { water: { count: 0, reporters: '' }, electricity: { count: 0, reporters: '' } };
            const displayName = t.neighborhoods[langKey] || key;
            
            // Build utility rows
            const waterStatus = getStatus(stats.water.count);
            const elecStatus = getStatus(stats.electricity.count);

            // Collect reporters
            let reporters = [];
            if (stats.water.reporters) reporters.push(stats.water.reporters);
            if (stats.electricity.reporters) reporters.push(stats.electricity.reporters);
            let uniqueReporters = [...new Set(reporters.join(', ').split(', '))].filter(Boolean).join(', ');

            const card = `
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all">
                    <h3 class="font-black text-lg text-slate-800 dark:text-white mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">
                        ${displayName}
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Water -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/20 flex items-center justify-center text-sky-500">
                                    <i class="fas fa-water"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">${t.water}</span>
                                    <span class="text-sm font-bold ${waterStatus.colorText}">${waterStatus.label}</span>
                                </div>
                            </div>
                            <div class="${waterStatus.dotClass}"></div>
                        </div>
                        
                        <!-- Electricity -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-500">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">${t.electricity}</span>
                                    <span class="text-sm font-bold ${elecStatus.colorText}">${elecStatus.label}</span>
                                </div>
                            </div>
                            <div class="${elecStatus.dotClass}"></div>
                        </div>
                    </div>

                    ${(stats.water.count > 0 || stats.electricity.count > 0) ? `
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 text-xs text-slate-400 text-center">
                            <span class="font-bold text-slate-500">${t.reported_by}:</span> <span class="text-slate-600 dark:text-slate-300">${uniqueReporters}</span>
                        </div>
                    ` : ''}
                </div>
            `;
            grid.innerHTML += card;
        }
    }

    function getStatus(count) {
        if (count >= 3) return { label: t.status_critical, colorText: 'text-red-500', dotClass: 'status-dot status-red' };
        if (count > 0) return { label: t.status_warning, colorText: 'text-amber-500', dotClass: 'status-dot status-yellow' };
        return { label: t.status_normal, colorText: 'text-emerald-500', dotClass: 'status-dot status-green' };
    }

    // Modal Logic
    const modal = document.getElementById('report-modal');
    const modalContent = document.getElementById('modal-content');

    function openReportModal() {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeReportModal() {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Submit Logic
    async function submitReport(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        if (!formData.get('neighborhood') || !formData.get('type')) {
            alert('Please select neighborhood and type.');
            return;
        }

        try {
            const res = await fetch('api/status_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.status === 'success') {
                alert(t.report_submitted);
                closeReportModal();
                fetchStatus(); // Refresh grid
                e.target.reset();
                resetSelection(); // Reset UI
            } else {
                alert('Error: ' + result.message);
            }
        } catch (err) {
            console.error(err);
            alert('Connection failed');
        }
    }

    // UI Selection Logic
    function updateSelection(group, input) {
        const containerId = group === 'neighborhood' ? 'neighborhood-grid' : 'type-grid';
        const container = document.getElementById(containerId);
        
        // Reset all in group
        container.querySelectorAll('.select-btn').forEach(div => {
            // Remove active classes
            div.classList.remove('bg-slate-900', 'text-white', 'dark:bg-white', 'dark:text-slate-900', 'border-sky-500', 'bg-sky-50', 'text-sky-600', 'border-amber-500', 'bg-amber-50', 'text-amber-600', 'ring-2', 'ring-offset-2', 'dark:ring-offset-slate-800');
            
            // Add default classes
            div.classList.add('border-slate-200', 'dark:border-slate-700', 'text-slate-700', 'dark:text-slate-300');
            
            // Re-add hidden icons color reset if needed (for type)
            if (group === 'type') {
                 const icon = div.querySelector('i');
                 if(icon) icon.className = icon.className.replace('text-sky-500', '').replace('text-amber-500', '');
            }
        });

        // Activate current
        const activeDiv = input.nextElementSibling;
        activeDiv.classList.remove('border-slate-200', 'dark:border-slate-700', 'text-slate-700', 'dark:text-slate-300');

        if (group === 'neighborhood') {
            activeDiv.classList.add('bg-slate-900', 'text-white', 'dark:bg-white', 'dark:text-slate-900', 'ring-2', 'ring-slate-900', 'dark:ring-white', 'ring-offset-2', 'dark:ring-offset-slate-800');
        } else if (group === 'type') {
             if (input.value === 'water') {
                 activeDiv.classList.add('border-sky-500', 'bg-sky-50', 'text-sky-600', 'ring-2', 'ring-sky-500', 'ring-offset-2', 'dark:ring-offset-slate-800');
                 activeDiv.querySelector('i').classList.add('text-sky-500');
             } else {
                 activeDiv.classList.add('border-amber-500', 'bg-amber-50', 'text-amber-600', 'ring-2', 'ring-amber-500', 'ring-offset-2', 'dark:ring-offset-slate-800');
                 activeDiv.querySelector('i').classList.add('text-amber-500');
             }
        }
    }

    function resetSelection() {
        document.querySelectorAll('.select-btn').forEach(div => {
             // Basic reset to default state
            div.className = 'select-btn border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm font-bold text-center text-slate-700 dark:text-slate-300 transition-all hover:bg-slate-50 dark:hover:bg-slate-700 group-hover:border-slate-300 dark:group-hover:border-slate-600';
            // For type buttons, we need different padding/structure, purely resetting classes is risky if we wipe structure classes.
            // Simplified: Just trigger click on null or reload page? No.
            // Let's just create a cleaner reset loop.
            
            // Actually, simpler to just remove the active classes.
             div.classList.remove('bg-slate-900', 'text-white', 'dark:bg-white', 'dark:text-slate-900', 'border-sky-500', 'bg-sky-50', 'text-sky-600', 'border-amber-500', 'bg-amber-50', 'text-amber-600', 'ring-2', 'ring-offset-2', 'dark:ring-offset-slate-800');
             div.classList.add('border-slate-200', 'dark:border-slate-700');
             
             // Reset icon colors
             const icon = div.querySelector('i');
             if(icon) {
                 icon.classList.remove('text-sky-500', 'text-amber-500');
             }
        });
    }

    // Init
    document.addEventListener('DOMContentLoaded', fetchStatus);
    </script>

</body>
</html>
