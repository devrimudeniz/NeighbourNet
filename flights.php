<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// $lang is already set by lang.php
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Flight Prices - Coming Soon' : 'Uçak Bileti Fiyatları - Çok Yakında'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 pt-32 max-w-lg text-center">
        
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-10 shadow-xl border border-slate-200 dark:border-slate-700 animate-fade-in-up">
            <div class="w-24 h-24 bg-blue-50 dark:bg-blue-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-plane-departure text-4xl text-blue-500"></i>
            </div>
            
            <h1 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-blue-500 to-cyan-500 mb-4">
                <?php echo $lang == 'en' ? 'Coming Soon' : 'Çok Yakında'; ?>
            </h1>
            
            <p class="text-slate-500 dark:text-slate-400 mb-8 leading-relaxed">
                <?php echo $lang == 'en' 
                    ? 'We are working hard to bring you the best flight prices from the UK to Dalaman. Stay tuned for this feature!' 
                    : 'İngiltere\'den Dalaman\'a en uygun uçak bileti fiyatlarını size sunmak için çalışıyoruz. Bu özellik çok yakında burada olacak!'; ?>
            </p>

            <a href="index.php" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-slate-700 text-white font-bold rounded-xl hover:scale-105 transition-transform">
                <i class="fas fa-home"></i>
                <span><?php echo $lang == 'en' ? 'Return Home' : 'Ana Sayfaya Dön'; ?></span>
            </a>
        </div>

    </main>

</body>
</html>
