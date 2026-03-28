<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// $lang is already set by lang.php

// Fetch Active Sitters
try {
    $stmt = $pdo->prepare("
        SELECT ps.*, u.username, u.full_name, u.avatar 
        FROM pet_sitters ps
        JOIN users u ON ps.user_id = u.id 
        WHERE ps.status = 'active' 
        ORDER BY ps.created_at DESC
    ");
    $stmt->execute();
    $sitters = $stmt->fetchAll();
} catch (PDOException $e) {
    $sitters = [];
}

// Check if current user is a sitter
$is_sitter = false;
$my_sitter_data = null;
if (isset($_SESSION['user_id'])) {
    $check_stmt = $pdo->prepare("SELECT * FROM pet_sitters WHERE user_id = ?");
    $check_stmt->execute([$_SESSION['user_id']]);
    $my_sitter_data = $check_stmt->fetch();
    $is_sitter = (bool)$my_sitter_data;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['pet_sitting']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <!-- Modal for Sitter Application -->
    <div id="sitter-modal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="toggleSitterModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-black text-pink-600 dark:text-pink-400"><?php echo $t['sitter_application']; ?></h2>
                    <button onclick="toggleSitterModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="sitter-form" onsubmit="saveSitter(event)">
                    <div class="space-y-4">
                        <!-- Experience & Rate Row -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1"><?php echo $t['experience_years']; ?></label>
                                <input type="number" name="experience_years" value="<?php echo $my_sitter_data['experience_years'] ?? '0'; ?>" min="0" class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1"><?php echo $t['daily_rate_from']; ?></label>
                                <input type="text" name="daily_rate" value="<?php echo htmlspecialchars($my_sitter_data['daily_rate'] ?? ''); ?>" placeholder="₺100 / <?php echo $lang == 'en' ? 'day' : 'gün'; ?>" class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-500 outline-none transition-all">
                            </div>
                        </div>
                        
                        <!-- Phone / WhatsApp -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">WhatsApp / <?php echo $t['phone']; ?></label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($my_sitter_data['phone'] ?? ''); ?>" placeholder="+90 5XX XXX XX XX" class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-500 outline-none transition-all">
                        </div>

                        <!-- Bio -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1"><?php echo $t['bio']; ?></label>
                            <textarea name="bio" rows="3" placeholder="<?php echo $lang == 'en' ? 'Tell pet owners about yourself...' : 'Evcil hayvan sahiplerine kendinizden bahsedin...'; ?>" class="w-full bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-500 outline-none transition-all"><?php echo htmlspecialchars($my_sitter_data['bio'] ?? ''); ?></textarea>
                        </div>

                        <!-- Pet Types -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2"><?php echo $t['accepted_pets']; ?></label>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                $pets = ['cat', 'dog', 'bird', 'other'];
                                $my_pets = explode(',', $my_sitter_data['pet_types'] ?? '');
                                foreach($pets as $p): ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="pet_types[]" value="<?php echo $p; ?>" class="peer hidden" <?php echo in_array($p, $my_pets) ? 'checked' : ''; ?>>
                                    <div class="px-4 py-2 rounded-full border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all peer-checked:bg-pink-500 peer-checked:text-white peer-checked:border-pink-500">
                                        <?php echo $t[$p]; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Services -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2"><?php echo $t['services_offered']; ?></label>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                $srvs = ['boarding', 'walking', 'visiting'];
                                $my_srvs = explode(',', $my_sitter_data['services'] ?? '');
                                foreach($srvs as $s): ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="services[]" value="<?php echo $s; ?>" class="peer hidden" <?php echo in_array($s, $my_srvs) ? 'checked' : ''; ?>>
                                    <div class="px-4 py-2 rounded-full border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all peer-checked:bg-blue-500 peer-checked:text-white peer-checked:border-blue-500">
                                        <?php echo $t['sitting_'.$s]; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-8 bg-pink-500 text-white py-4 rounded-2xl font-black shadow-lg shadow-pink-500/30 hover:bg-pink-600 active:scale-95 transition-all">
                        <?php echo $lang == 'en' ? 'Save Profile' : 'Profilini Kaydet'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 pt-24 max-w-4xl">
        
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-pink-600 dark:text-pink-400"><?php echo $t['pet_sitting']; ?></h1>
                <p class="text-slate-500 text-sm md:text-base font-medium"><?php echo $t['pet_sitting_desc']; ?></p>
            </div>
            
            <?php if(isset($_SESSION['user_id'])): ?>
            <button onclick="toggleSitterModal()" class="bg-gradient-to-r from-pink-500 to-rose-500 text-white px-6 py-3 rounded-2xl font-black shadow-xl shadow-pink-500/30 hover:scale-105 active:scale-95 transition-all text-sm">
                <i class="fas fa-paw mr-2"></i> <?php echo $is_sitter ? ($lang == 'en' ? 'Edit Profile' : 'Profili Düzenle') : $t['become_sitter']; ?>
            </button>
            <?php endif; ?>
        </div>

        <!-- Filtering Panel -->
        <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-5 mb-10 shadow-sm border border-slate-100 dark:border-slate-700">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <!-- Pet Type -->
                <select id="filter-pet" onchange="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-3 text-sm font-bold outline-none">
                    <option value=""><?php echo $lang == 'en' ? 'All Pets' : 'Tüm Hayvanlar'; ?></option>
                    <option value="cat"><?php echo $t['cat']; ?></option>
                    <option value="dog"><?php echo $t['dog']; ?></option>
                    <option value="bird"><?php echo $t['bird']; ?></option>
                </select>
                
                <!-- Service Type -->
                <select id="filter-service" onchange="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-3 text-sm font-bold outline-none">
                    <option value=""><?php echo $lang == 'en' ? 'All Services' : 'Tüm Hizmetler'; ?></option>
                    <option value="boarding"><?php echo $t['sitting_boarding']; ?></option>
                    <option value="walking"><?php echo $t['sitting_walking']; ?></option>
                    <option value="visiting"><?php echo $t['sitting_visiting']; ?></option>
                </select>
                
                <!-- Reset Column on Desktop -->
                <button onclick="resetFilters()" class="col-span-2 md:col-span-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl p-3 text-sm font-bold hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-undo"></i> <?php echo $lang == 'en' ? 'Reset' : 'Filtreleri Sıfırla'; ?>
                </button>
            </div>
        </div>

        <?php if(empty($sitters)): ?>
            <div class="text-center py-20 opacity-40">
                <i class="fas fa-paw text-7xl mb-6"></i>
                <p class="text-xl font-black text-slate-400"><?php echo $t['no_sitters']; ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="sitters-container">
                <?php foreach($sitters as $sitter): ?>
                <div class="sitter-card bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-xl transition-all group" 
                     data-pets="<?php echo htmlspecialchars($sitter['pet_types']); ?>" 
                     data-services="<?php echo htmlspecialchars($sitter['services']); ?>">
                    
                    <div class="flex gap-4 items-start mb-4">
                        <img src="<?php echo htmlspecialchars($sitter['avatar']); ?>" class="w-16 h-16 rounded-2xl object-cover shadow-md group-hover:scale-105 transition-transform">
                        <div class="flex-1">
                            <h3 class="font-black text-lg text-slate-900 dark:text-white leading-tight">
                                <?php echo htmlspecialchars($sitter['full_name']); ?>
                            </h3>
                            <p class="text-pink-500 font-black text-xs uppercase tracking-wider mt-1">
                                <i class="fas fa-award mr-1"></i> <?php echo $sitter['experience_years']; ?> <?php echo $t['experience_years']; ?>
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-0.5 rounded-lg text-xs font-black">
                                     <?php echo htmlspecialchars($sitter['daily_rate']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="text-slate-500 dark:text-slate-400 text-sm line-clamp-2 mb-4 italic leading-relaxed">
                        "<?php echo htmlspecialchars($sitter['bio']); ?>"
                    </p>

                    <div class="flex flex-wrap gap-2 mb-6">
                        <?php 
                        $spets = explode(',', $sitter['pet_types']);
                        foreach($spets as $sp): if(empty($sp)) continue; ?>
                        <span class="bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 px-2 py-1 rounded-full text-[10px] font-bold">
                            <?php echo $t[$sp] ?? $sp; ?>
                        </span>
                        <?php endforeach; ?>
                        
                        <?php 
                        $ssrvs = explode(',', $sitter['services']);
                        foreach($ssrvs as $ss): if(empty($ss)) continue; ?>
                        <span class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-full text-[10px] font-bold">
                            <?php echo $t['sitting_'.$ss] ?? $ss; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex gap-3">
                         <a href="messages.php?user_id=<?php echo $sitter['user_id']; ?>" class="flex-1 bg-slate-900 dark:bg-white text-white dark:text-slate-900 py-3 rounded-xl font-black text-center text-sm shadow-lg hover:translate-y-[-2px] transition-all">
                             <?php echo $t['send_message']; ?>
                         </a>
                         <?php if(!empty($sitter['phone'])): ?>
                         <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $sitter['phone']); ?>" target="_blank" class="w-12 h-12 flex items-center justify-center bg-green-500 text-white rounded-xl hover:bg-green-600 transition-all shadow-lg shadow-green-500/20">
                             <i class="fab fa-whatsapp text-xl"></i>
                         </a>
                         <?php endif; ?>
                         <a href="profile.php?username=<?php echo $sitter['username']; ?>" class="w-12 h-12 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200 transition-all text-slate-500">
                             <i class="fas fa-user"></i>
                         </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <script>
    function toggleSitterModal() {
        const modal = document.getElementById('sitter-modal');
        modal.classList.toggle('hidden');
        document.body.style.overflow = modal.classList.contains('hidden') ? '' : 'hidden';
    }

    function saveSitter(e) {
        e.preventDefault();
        const form = document.getElementById('sitter-form');
        const formData = new FormData(form);
        
        fetch('api/save_sitter.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message, 'error');
            }
        });
    }

    function applyFilters() {
        const petFilter = document.getElementById('filter-pet').value;
        const serviceFilter = document.getElementById('filter-service').value;
        const cards = document.querySelectorAll('.sitter-card');
        
        cards.forEach(card => {
            const pets = card.dataset.pets;
            const services = card.dataset.services;
            
            let show = true;
            if(petFilter && !pets.includes(petFilter)) show = false;
            if(serviceFilter && !services.includes(serviceFilter)) show = false;
            
            card.style.display = show ? 'block' : 'none';
        });
    }

    function resetFilters() {
        document.getElementById('filter-pet').value = '';
        document.getElementById('filter-service').value = '';
        applyFilters();
    }
    </script>

</body>
</html>
