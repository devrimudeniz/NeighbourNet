<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// $lang is already set by lang.php

// Services Configuration
$services = [
    [
        'id' => 'private_chef',
        'icon' => 'user-group',
        'title_key' => 'private_chef',
        'desc_key' => 'private_chef_desc',
        'image' => 'assets/photos/chef.jpg', // Placeholder or generic
        'color' => 'amber'
    ],
    [
        'id' => 'house_cleaning',
        'icon' => 'sparkles',
        'title_key' => 'house_cleaning',
        'desc_key' => 'house_cleaning_desc',
        'image' => 'assets/photos/cleaning.jpg',
        'color' => 'sky'
    ],
    [
        'id' => 'pool_cleaning',
        'icon' => 'water', // using general terms for now, heroicon will verify
        'title_key' => 'pool_cleaning',
        'desc_key' => 'pool_cleaning_desc',
        'image' => 'assets/photos/pool.jpg',
        'color' => 'cyan'
    ],
    [
        'id' => 'bbq_supplies',
        'icon' => 'fire',
        'title_key' => 'bbq_supplies',
        'desc_key' => 'bbq_supplies_desc',
        'image' => 'assets/photos/bbq.jpg',
        'color' => 'red'
    ]
];

// Determine WhatsApp Number (Admin)
$adminPhone = '905555555555'; // Replace with real admin number if known, or generic placeholder
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['villa_services']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 md:px-6 pt-24 pb-12 max-w-6xl">
        
        <!-- Header Section -->
        <div class="mb-12 text-center">
            <h1 class="text-4xl md:text-5xl font-black mb-4 text-slate-900 dark:text-white tracking-tight">
                <?php echo $t['villa_services']; ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-lg max-w-2xl mx-auto">
                <?php echo $t['villa_services_desc']; ?>
            </p>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
            <?php foreach ($services as $svc): ?>
                <?php 
                    // Construct WhatsApp Message
                    $svcTitle = $t[$svc['title_key']] ?? $svc['title_key'];
                    $msg = $lang == 'en' 
                        ? "Hello, I am interested in {$svcTitle} service for my villa." 
                        : "Merhaba, villam için {$svcTitle} hizmeti hakkında bilgi almak istiyorum.";
                    $waLink = "https://wa.me/{$adminPhone}?text=" . urlencode($msg);
                ?>
                <div class="group relative bg-white dark:bg-slate-800 rounded-3xl overflow-hidden shadow-lg border border-slate-100 dark:border-slate-700 hover:shadow-2xl transition-all duration-500 hover:-translate-y-1">
                    
                    <!-- Color Accent -->
                    <div class="absolute top-0 left-0 w-full h-2 bg-<?php echo $svc['color']; ?>-500"></div>

                    <div class="p-8">
                        <div class="flex items-start justify-between mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-<?php echo $svc['color']; ?>-50 dark:bg-<?php echo $svc['color']; ?>-900/20 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <!-- Icon Placeholder - ensuring fallback -->
                                <i class="fas fa-<?php 
                                    echo match($svc['id']) {
                                        'private_chef' => 'utensils',
                                        'house_cleaning' => 'sparkles', // fontawesome doesn't store house-cleaning maybe
                                        'pool_cleaning' => 'water',
                                        'bbq_supplies' => 'fire', 
                                        default => 'star'
                                    }; 
                                ?> text-2xl text-<?php echo $svc['color']; ?>-500"></i>
                            </div>
                            <span class="bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                                <?php echo $lang == 'en' ? 'Service' : 'Hizmet'; ?>
                            </span>
                        </div>

                        <h3 class="text-2xl font-bold mb-3 text-slate-900 dark:text-white group-hover:text-<?php echo $svc['color']; ?>-600 dark:group-hover:text-<?php echo $svc['color']; ?>-400 transition-colors">
                            <?php echo $t[$svc['title_key']]; ?>
                        </h3>
                        
                        <p class="text-slate-500 dark:text-slate-400 mb-8 leading-relaxed h-12">
                            <?php echo $t[$svc['desc_key']]; ?>
                        </p>

                        <a href="<?php echo $waLink; ?>" target="_blank" class="flex items-center justify-center gap-3 w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl font-bold hover:opacity-90 transition-opacity">
                            <i class="fab fa-whatsapp text-xl"></i>
                            <?php echo $t['request_service']; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Info Card -->
        <div class="mt-12 bg-sky-50 dark:bg-sky-900/20 rounded-3xl p-8 md:p-12 text-center border border-sky-100 dark:border-sky-800">
            <h3 class="text-xl font-bold text-sky-900 dark:text-sky-100 mb-2">
                <?php echo $t['whatsapp_concierge']; ?>
            </h3>
            <p class="text-sky-700 dark:text-sky-300 mb-6 max-w-xl mx-auto">
                <?php echo $lang == 'en' 
                    ? 'Our team is ready to assist with all your villa needs via WhatsApp. Fast, local, and reliable.' 
                    : 'Ekibimiz tüm villa ihtiyaçlarınız için WhatsApp üzerinden size yardımcı olmaya hazır. Hızlı, yerel ve güvenilir.'; ?>
            </p>
            <a href="https://wa.me/<?php echo $adminPhone; ?>" class="inline-flex items-center gap-2 text-sky-600 dark:text-sky-400 font-bold hover:underline">
                <i class="fab fa-whatsapp"></i> +90 555 555 55 55
            </a>
        </div>

    </main>

</body>
</html>
