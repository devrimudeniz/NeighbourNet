<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
require_once 'includes/ui_components.php';

// $lang is already set by lang.php
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32 transition-colors duration-300 overflow-x-hidden">

    <!-- Premium Background -->
    <!-- Light Mode -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <!-- Dark Mode -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 md:pt-28 max-w-5xl">
        
        <!-- Page Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-black bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-600">
                <?php echo $lang == 'en' ? 'All Services' : 'Tüm Hizmetler'; ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm md:text-base">
                <?php echo $lang == 'en' ? 'Everything you need for life in Kalkan' : 'Kalkan\'da yaşam için ihtiyacınız olan her şey'; ?>
            </p>
        </div>

        <!-- Essential Services Section -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <?php echo heroicon('star', 'w-5 h-5 text-amber-500'); ?>
                <?php echo $lang == 'en' ? 'Essential Services' : 'Önemli Hizmetler'; ?>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 md:gap-4">

                <!-- Guidebook -->
                <a href="guidebook.php" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-violet-300 dark:hover:border-violet-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-violet-50 dark:bg-violet-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-book-reader text-2xl md:text-3xl text-violet-500"></i>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Local Guidebook' : 'Yerel Rehber'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Expert Advice' : 'Uzman Tavsiyeleri'; ?></p>
                </a>
                
                <!-- Community Support -->
                <a href="community_support" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-green-300 dark:hover:border-green-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-green-50 dark:bg-green-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-hand-holding-heart text-2xl md:text-3xl text-green-500"></i>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Community Support' : 'Askıda İyilik'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Share & Care' : 'Paylaş & Destek Ol'; ?></p>
                </a>
                
                <!-- Duty Pharmacy -->
                <a href="duty_pharmacy" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-red-300 dark:hover:border-red-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('pills', 'text-2xl md:text-3xl text-red-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Duty Pharmacy' : 'Nöbetçi Eczane'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? '24/7 Service' : '24 Saat Açık'; ?></p>
                </a>

                <!-- First Aid -->
                <a href="first_aid" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-pink-300 dark:hover:border-pink-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-pink-50 dark:bg-pink-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('heart', 'text-2xl md:text-3xl text-pink-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'First Aid' : 'İlk Yardım'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Emergency Info' : 'Acil Bilgiler'; ?></p>
                </a>

                <!-- Weather -->
                <a href="weather" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-amber-300 dark:hover:border-amber-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('sun', 'text-2xl md:text-3xl text-amber-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['weather']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Local Forecast' : 'Yerel Tahmin'; ?></p>
                </a>

                <!-- Pati Safe -->
                <a href="pati_safe" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-pink-300 dark:hover:border-pink-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-pink-50 dark:bg-pink-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('paw', 'text-2xl md:text-3xl text-pink-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['pati_safe']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Lost & Found Pets' : 'Kayıp Hayvanlar'; ?></p>
                </a>

                <!-- Lost & Found (Items) -->
                <a href="lost_found" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-amber-300 dark:hover:border-amber-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-key text-2xl md:text-3xl text-amber-500"></i>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Lost & Found' : 'Kayıp Eşya'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Keys, Wallet, Phone...' : 'Anahtar, Cüzdan, Telefon...'; ?></p>
                </a>

                <!-- Pet Sitting -->
                <a href="pet_sitting" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-pink-400 dark:hover:border-pink-600 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-pink-50 dark:bg-pink-900/10 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-hand-holding-heart text-2xl md:text-3xl text-pink-500"></i>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['pet_sitting']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Verified Sitters' : 'Onaylı Bakıcılar'; ?></p>
                </a>
                
            </div>
        </section>

        <!-- Business & Commerce Section -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <?php echo heroicon('store', 'w-5 h-5 text-teal-500'); ?>
                <?php echo $lang == 'en' ? 'Business & Commerce' : 'İş & Ticaret'; ?>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 md:gap-4">
                
                <!-- Directory -->
                <a href="directory" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-teal-300 dark:hover:border-teal-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-teal-50 dark:bg-teal-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('store', 'text-2xl md:text-3xl text-teal-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['directory']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Local Businesses' : 'Yerel İşletmeler'; ?></p>
                </a>

                <!-- Marketplace -->
                <a href="marketplace" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-red-300 dark:hover:border-red-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('marketplace', 'text-2xl md:text-3xl text-red-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['marketplace']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Buy & Sell' : 'Al & Sat'; ?></p>
                </a>

                <!-- Jobs -->
                <a href="jobs" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-indigo-300 dark:hover:border-indigo-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('briefcase', 'text-2xl md:text-3xl text-indigo-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['jobs']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Job Listings' : 'İş İlanları'; ?></p>
                </a>

                <!-- Properties -->
                <a href="properties" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-emerald-300 dark:hover:border-emerald-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('home', 'text-2xl md:text-3xl text-emerald-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['property_hub']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Rent & Buy' : 'Kirala & Satın Al'; ?></p>
                </a>

                <!-- Utilities Status -->
                <a href="status" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-amber-300 dark:hover:border-amber-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('bolt', 'text-2xl md:text-3xl text-amber-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['utilities_status']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Water & Electric' : 'Su & Elektrik'; ?></p>
                </a>
                
            </div>
        </section>

        <!-- Transportation Section -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <?php echo heroicon('car', 'w-5 h-5 text-pink-500'); ?>
                <?php echo $lang == 'en' ? 'Transportation' : 'Ulaşım'; ?>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 md:gap-4">
                
                <!-- Transportation Hub -->
                <a href="transportation" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-amber-300 dark:hover:border-amber-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('transportation', 'text-2xl md:text-3xl text-amber-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['transportation_hub']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Bus & Taxi' : 'Otobüs & Taksi'; ?></p>
                </a>
                
                <!-- Flight Prices -->
                <a href="flights" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-slate-300 dark:hover:border-slate-600 transition-all opacity-75 hover:opacity-100">
                    <div class="relative w-12 h-12 md:w-14 md:h-14 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <?php echo heroicon('paper_plane', 'text-2xl md:text-3xl text-slate-400'); ?>
                        <span class="absolute -top-2 -right-2 bg-amber-500 text-white text-[9px] px-2 py-0.5 rounded-full font-bold shadow-sm">SOON</span>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['flight_prices']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Coming Soon' : 'Çok Yakında'; ?></p>
                </a>

                <!-- Ride Sharing -->
                <a href="rides" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-pink-300 dark:hover:border-pink-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-pink-50 dark:bg-pink-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('car', 'text-2xl md:text-3xl text-pink-600'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['ride_sharing']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Share a Ride' : 'Araba Paylaş'; ?></p>
                </a>

                <!-- Boat Trips -->
                <a href="boat_trips" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-cyan-300 dark:hover:border-cyan-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-cyan-50 dark:bg-cyan-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('ship', 'text-2xl md:text-3xl text-cyan-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Boat Trips' : 'Tekne Turları'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Daily Tours' : 'Günlük Turlar'; ?></p>
                </a>
                
            </div>
        </section>

        <!-- Explore & Activities Section -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <?php echo heroicon('compass', 'w-5 h-5 text-violet-500'); ?>
                <?php echo $lang == 'en' ? 'Explore & Activities' : 'Keşfet & Aktiviteler'; ?>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 md:gap-4">
                
                <!-- Trail Mate -->
                <a href="trail_mate" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-amber-300 dark:hover:border-amber-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('hiking', 'text-2xl md:text-3xl text-amber-600'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['trail_mate']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Hiking Trails' : 'Yürüyüş Parkurları'; ?></p>
                </a>

                <!-- Happy Hour / Nightlife -->
                <a href="happy_hour" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-violet-300 dark:hover:border-violet-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-violet-50 dark:bg-violet-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('music', 'text-2xl md:text-3xl text-violet-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Nightlife' : 'Gece Hayatı'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Bars & Events' : 'Barlar & Etkinlikler'; ?></p>
                </a>

                <!-- Time Travel (Coming Soon) -->
                <div class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 opacity-90">
                    <div class="relative w-12 h-12 md:w-14 md:h-14 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <?php echo heroicon('clock', 'text-2xl md:text-3xl text-amber-500'); ?>
                        <span class="absolute -top-2 -right-2 bg-amber-500 text-white text-[9px] px-2 py-0.5 rounded-full font-bold shadow-sm">SOON</span>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Time Travel' : 'Zaman Yolculuğu'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Coming Soon' : 'Çok Yakında'; ?></p>
                </div>

                <!-- What to Do Guide -->
                <a href="what_to_do.php" class="group bg-gradient-to-br from-blue-500 to-cyan-500 p-4 md:p-6 rounded-2xl shadow-lg border border-blue-400 hover:shadow-xl hover:scale-105 transition-all">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-map-marked-alt text-2xl md:text-3xl text-white"></i>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-white"><?php echo $lang == 'en' ? 'Travel Guide' : 'Gezi Rehberi'; ?></h3>
                    <p class="text-[10px] md:text-xs text-blue-100 text-center mt-1 font-medium"><?php echo $lang == 'en' ? 'Kalkan, Kaş, Fethiye' : 'Kalkan, Kaş, Fethiye'; ?></p>
                </a>

                <!-- Events -->
                <a href="events" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-violet-300 dark:hover:border-violet-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-violet-50 dark:bg-violet-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('calendar', 'text-2xl md:text-3xl text-violet-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['events']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'What\'s On' : 'Etkinlikler'; ?></p>
                </a>
                

                
            </div>
        </section>

        <!-- Community Contests & Fun -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <i class="fas fa-trophy w-5 h-5 text-yellow-500"></i>
                Kalkan <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500">Snaps</span>
            </h2>
            
            <!-- Photo Contest Promo Card -->
            <?php
            $latest_winner = false;
            try {
                $latest_winner_stmt = $pdo->query("
                    SELECT cw.*, s.image_path, u.username, u.full_name, u.avatar
                    FROM contest_winners cw
                    JOIN contest_submissions s ON cw.submission_id = s.id
                    JOIN users u ON cw.user_id = u.id
                    ORDER BY cw.week_of DESC
                    LIMIT 1
                ");
                $latest_winner = $latest_winner_stmt->fetch();
            } catch (Exception $e) {}
            ?>
            <div class="relative rounded-3xl overflow-hidden shadow-2xl group cursor-pointer" onclick="location.href='photo_contest.php'">
               <?php if($latest_winner): ?>
                   <div class="aspect-[21/9] md:aspect-[3/1]">
                       <img src="<?php echo htmlspecialchars($latest_winner['image_path']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                   </div>
                   <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent flex flex-col justify-center px-6 md:px-10">
                       <div class="inline-flex items-center gap-2 bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider mb-2 w-max shadow-lg animate-pulse">
                           <i class="fas fa-trophy"></i> <?php echo $lang == 'en' ? 'Photo of the Week' : 'Haftanın Fotoğrafı'; ?>
                       </div>
                       <h3 class="text-2xl md:text-3xl font-black text-white mb-2 truncate max-w-lg shadow-black drop-shadow-md">
                           Kalkan <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-violet-400">Snaps</span>
                       </h3>
                       <div class="flex items-center gap-3 mt-2">
                            <img src="<?php echo htmlspecialchars($latest_winner['avatar']); ?>" class="w-10 h-10 rounded-full border-2 border-white shadow-md">
                            <div>
                                <p class="text-white font-bold leading-tight"><?php echo htmlspecialchars($latest_winner['full_name']); ?></p>
                                <p class="text-white/60 text-xs font-medium">@<?php echo htmlspecialchars($latest_winner['username']); ?></p>
                            </div>
                       </div>
                   </div>
                   <div class="absolute bottom-6 right-6 md:right-10 hidden md:block">
                       <span class="bg-white/20 backdrop-blur border border-white/30 text-white px-5 py-2.5 rounded-full font-bold text-sm hover:bg-white/30 transition-all flex items-center gap-2">
                           <?php echo $lang == 'en' ? 'View Gallery' : 'Galeriyi Gör'; ?> <i class="fas fa-arrow-right"></i>
                       </span>
                   </div>
               <?php else: ?>
                    <div class="w-full bg-blue-600 p-8 flex items-center justify-between" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                       <div>
                           <div class="inline-flex items-center gap-2 bg-white/20 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-3 border border-white/20">
                               📸 <?php echo $lang == 'en' ? 'New Feature' : 'Yeni Özellik'; ?>
                           </div>
                           <h3 class="text-2xl md:text-3xl font-black text-white mb-2">Kalkan <span class="text-pink-300">Snaps</span></h3>
                           <p class="text-white/80 max-w-md text-sm md:text-base">
                               <?php echo $lang == 'en' ? 'Share your best photos of Kalkan. Win badges and fame!' : 'En güzel Kalkan fotoğraflarını paylaş. Rozet ve şöhret kazan!'; ?>
                           </p>
                       </div>
                       <div class="hidden md:block">
                           <span class="bg-white text-indigo-600 px-6 py-3 rounded-full font-black hover:scale-105 transition-transform shadow-xl">
                               <?php echo $lang == 'en' ? 'Join Contest' : 'Yarışmaya Katıl'; ?>
                           </span>
                       </div>
                    </div>
               <?php endif; ?>
            </div>
        </section>

        <!-- Learn & Connect Section -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <?php echo heroicon('language', 'w-5 h-5 text-indigo-500'); ?>
                <?php echo $lang == 'en' ? 'Learn & Connect' : 'Öğren & Bağlan'; ?>
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 md:gap-4">
                
                <!-- Lingo -->
                <a href="lingo" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-indigo-300 dark:hover:border-indigo-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('language', 'text-2xl md:text-3xl text-indigo-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Lingo Cards' : 'Pratik Sözlük'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Learn Turkish' : 'Dil Öğren'; ?></p>
                </a>

                <!-- Members -->
                <a href="members" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-blue-300 dark:hover:border-blue-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('group', 'text-2xl md:text-3xl text-blue-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['members']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Community' : 'Topluluk'; ?></p>
                </a>

                <!-- Groups -->
                <a href="groups" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-emerald-300 dark:hover:border-emerald-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('users', 'text-2xl md:text-3xl text-emerald-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $t['groups']; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Join Groups' : 'Gruplara Katıl'; ?></p>
                </a>

                <!-- News -->
                <a href="news" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-blue-300 dark:hover:border-blue-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-newspaper text-xl md:text-2xl text-blue-500"></i>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'News' : 'Haberler'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Local News' : 'Yerel Haberler'; ?></p>
                </a>
                
            </div>
        </section>

            </div>
        </section>

        <!-- AI Tools Section -->
        <?php
        $isDark = defined('CURRENT_THEME') && CURRENT_THEME == 'dark';
        $ait_card_bg = $isDark ? '#1e293b' : '#ffffff';
        $ait_card_border = $isDark ? '#334155' : '#e2e8f0';
        $ait_title_color = $isDark ? '#f1f5f9' : '#0f172a';
        $ait_desc_color = $isDark ? '#94a3b8' : '#475569';
        $ait_quote_color = $isDark ? '#64748b' : '#64748b';
        $ait_chevron_color = $isDark ? '#94a3b8' : '#94a3b8';
        $ait_badge_bg = $isDark ? 'rgba(99,102,241,0.15)' : '#eef2ff';
        $ait_icon_bg_indigo = $isDark ? 'rgba(99,102,241,0.15)' : '#eef2ff';
        $ait_icon_bg_green = $isDark ? 'rgba(34,197,94,0.15)' : '#f0fdf4';
        $ait_icon_bg_amber = $isDark ? 'rgba(245,158,11,0.15)' : '#fffbeb';
        $ait_icon_bg_blue = $isDark ? 'rgba(59,130,246,0.15)' : '#eff6ff';
        $ait_icon_bg_rose = $isDark ? 'rgba(225,29,72,0.15)' : '#fff1f2';
        ?>
        <section class="mb-8">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <h2 style="font-size:18px;font-weight:800;color:<?php echo $ait_title_color; ?>;margin:0;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-robot" style="color:#6366f1;font-size:16px;"></i>
                    <?php echo $lang == 'en' ? 'AI Tools' : 'Yapay Zeka Araçları'; ?>
                </h2>
                <span style="padding:3px 10px;border-radius:6px;font-size:10px;font-weight:700;background:<?php echo $ait_badge_bg; ?>;color:#6366f1;">
                    <?php echo $lang == 'en' ? 'FREE' : 'ÜCRETSİZ'; ?>
                </span>
            </div>
            <p style="font-size:12px;color:#94a3b8;margin:0 0 14px;line-height:1.5;">
                <?php echo $lang == 'en' 
                    ? 'Smart assistants powered by AI. Just ask a question or upload a photo — they do the hard work for you!'
                    : 'Yapay zeka destekli akıllı asistanlar. Soru sorun veya fotoğraf yükleyin — zor işi onlar yapsın!'; ?>
            </p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                
                <!-- Paperwork Helper -->
                <a href="paperwork.php" style="display:flex;align-items:center;gap:14px;padding:14px;background:<?php echo $ait_card_bg; ?>;border:1px solid <?php echo $ait_card_border; ?>;border-radius:14px;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?php echo $ait_icon_bg_indigo; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-file-contract" style="font-size:20px;color:#6366f1;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:14px;font-weight:800;margin:0 0 2px;color:<?php echo $ait_title_color; ?>;"><?php echo $lang == 'en' ? 'Paperwork Helper' : 'Evrak Asistanı'; ?></h3>
                        <p style="font-size:12px;color:<?php echo $ait_desc_color; ?>;margin:0;line-height:1.4;">
                            <?php echo $lang == 'en' 
                                ? 'Upload a bill, contract or official document — AI reads it and explains what it says in plain English.'
                                : 'Fatura, sözleşme veya resmi belgeyi yükleyin — yapay zeka okuyup size sade bir dille açıklasın.'; ?>
                        </p>
                        <p style="font-size:11px;color:<?php echo $ait_quote_color; ?>;margin:4px 0 0;font-style:italic;">
                            <?php echo $lang == 'en' 
                                ? '"I got a water bill in Turkish, is this normal?"'
                                : '"Türkçe su faturası geldi, normal mi bu tutar?"'; ?>
                        </p>
                    </div>
                    <i class="fas fa-chevron-right" style="color:<?php echo $ait_chevron_color; ?>;font-size:12px;flex-shrink:0;"></i>
                </a>

                <!-- Grocery Scout -->
                <a href="grocery.php" style="display:flex;align-items:center;gap:14px;padding:14px;background:<?php echo $ait_card_bg; ?>;border:1px solid <?php echo $ait_card_border; ?>;border-radius:14px;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?php echo $ait_icon_bg_green; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-shopping-basket" style="font-size:20px;color:#22c55e;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:14px;font-weight:800;margin:0 0 2px;color:<?php echo $ait_title_color; ?>;"><?php echo $lang == 'en' ? 'Grocery Scout' : 'Market Ajanı'; ?></h3>
                        <p style="font-size:12px;color:<?php echo $ait_desc_color; ?>;margin:0;line-height:1.4;">
                            <?php echo $lang == 'en' 
                                ? 'Can\'t find your favorite brand? Tell AI what you need and it finds the Turkish equivalent.'
                                : 'Aradığınız markayı bulamıyor musunuz? Yapay zekaya söyleyin, Türk muadilini bulsun.'; ?>
                        </p>
                        <p style="font-size:11px;color:<?php echo $ait_quote_color; ?>;margin:4px 0 0;font-style:italic;">
                            <?php echo $lang == 'en' 
                                ? '"What\'s the Turkish version of Bisto gravy granules?"'
                                : '"İngiltere\'deki Hovis ekmeğinin Türkiye\'deki karşılığı ne?"'; ?>
                        </p>
                    </div>
                    <i class="fas fa-chevron-right" style="color:<?php echo $ait_chevron_color; ?>;font-size:12px;flex-shrink:0;"></i>
                </a>

                <!-- Menu Master -->
                <a href="menu_decoder.php" style="display:flex;align-items:center;gap:14px;padding:14px;background:<?php echo $ait_card_bg; ?>;border:1px solid <?php echo $ait_card_border; ?>;border-radius:14px;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?php echo $ait_icon_bg_amber; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-utensils" style="font-size:20px;color:#f59e0b;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:14px;font-weight:800;margin:0 0 2px;color:<?php echo $ait_title_color; ?>;"><?php echo $lang == 'en' ? 'Menu Master' : 'Menü Gurmesi'; ?></h3>
                        <p style="font-size:12px;color:<?php echo $ait_desc_color; ?>;margin:0;line-height:1.4;">
                            <?php echo $lang == 'en' 
                                ? 'Photo a Turkish menu and AI translates every dish, explains ingredients and suggests what to try.'
                                : 'Türkçe menüyü fotoğraflayın, yapay zeka her yemeği çevirsin, içindekileri ve önerilerini söylesin.'; ?>
                        </p>
                        <p style="font-size:11px;color:<?php echo $ait_quote_color; ?>;margin:4px 0 0;font-style:italic;">
                            <?php echo $lang == 'en' 
                                ? '"What is Hünkar Beğendi? Is it spicy?"'
                                : '"Menüde yazan şeyleri anlamıyorum, fotoğrafını çektim"'; ?>
                        </p>
                    </div>
                    <i class="fas fa-chevron-right" style="color:<?php echo $ait_chevron_color; ?>;font-size:12px;flex-shrink:0;"></i>
                </a>

                <!-- Pharmacy Pal -->
                <a href="pharmacy_ai.php" style="display:flex;align-items:center;gap:14px;padding:14px;background:<?php echo $ait_card_bg; ?>;border:1px solid <?php echo $ait_card_border; ?>;border-radius:14px;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?php echo $ait_icon_bg_blue; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-prescription-bottle-alt" style="font-size:20px;color:#3b82f6;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:14px;font-weight:800;margin:0 0 2px;color:<?php echo $ait_title_color; ?>;"><?php echo $lang == 'en' ? 'Pharmacy Pal' : 'Eczane Yoldaşı'; ?></h3>
                        <p style="font-size:12px;color:<?php echo $ait_desc_color; ?>;margin:0;line-height:1.4;">
                            <?php echo $lang == 'en' 
                                ? 'Need medicine? Describe symptoms or upload a medicine box photo — AI finds the Turkish equivalent.'
                                : 'İlaç mı lazım? Şikayetinizi anlatın veya ilaç kutusunu fotoğraflayın — Türk muadilini bulsun.'; ?>
                        </p>
                        <p style="font-size:11px;color:<?php echo $ait_quote_color; ?>;margin:4px 0 0;font-style:italic;">
                            <?php echo $lang == 'en' 
                                ? '"I take Omeprazole 20mg in the UK, what do I ask for here?"'
                                : '"İngiltere\'de Paracetamol alıyorum, burada hangisini almalıyım?"'; ?>
                        </p>
                    </div>
                    <i class="fas fa-chevron-right" style="color:<?php echo $ait_chevron_color; ?>;font-size:12px;flex-shrink:0;"></i>
                </a>

                <!-- Culture Lens -->
                <a href="culture_lens.php" style="display:flex;align-items:center;gap:14px;padding:14px;background:<?php echo $ait_card_bg; ?>;border:1px solid <?php echo $ait_card_border; ?>;border-radius:14px;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?php echo $ait_icon_bg_rose; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-landmark" style="font-size:20px;color:#e11d48;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <h3 style="font-size:14px;font-weight:800;margin:0 0 2px;color:<?php echo $ait_title_color; ?>;"><?php echo $lang == 'en' ? 'Culture Lens' : 'Kültür Rehberi'; ?></h3>
                        <p style="font-size:12px;color:<?php echo $ait_desc_color; ?>;margin:0;line-height:1.4;">
                            <?php echo $lang == 'en' 
                                ? 'Photo any historical site, ruin or artifact — AI tells you its story, age and significance.'
                                : 'Tarihi yeri, kalıntıyı veya eseri fotoğraflayın — yapay zeka hikayesini, yaşını ve önemini anlatsın.'; ?>
                        </p>
                        <p style="font-size:11px;color:<?php echo $ait_quote_color; ?>;margin:4px 0 0;font-style:italic;">
                            <?php echo $lang == 'en' 
                                ? '"What are these rock tombs I see on the cliff?"'
                                : '"Yol kenarında gördüğüm bu kaya mezarları ne?"'; ?>
                        </p>
                    </div>
                    <i class="fas fa-chevron-right" style="color:<?php echo $ait_chevron_color; ?>;font-size:12px;flex-shrink:0;"></i>
                </a>

            </div>
        </section>

        <!-- Contact Section -->
        <section class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-4 flex items-center gap-2">
                <?php echo heroicon('chat-bubble-left-right', 'w-5 h-5 text-blue-500'); ?>
                <?php echo $lang == 'en' ? 'Contact & Support' : 'İletişim & Destek'; ?>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <!-- Contact Us -->
                <a href="contact.php" class="group bg-gradient-to-r from-blue-500 to-indigo-600 p-4 md:p-6 rounded-2xl shadow-lg border border-blue-400 hover:shadow-xl hover:scale-[1.02] transition-all flex items-center gap-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                        <i class="fas fa-envelope text-white text-xl md:text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-white"><?php echo $lang == 'en' ? 'Contact Us' : 'Bize Ulaşın'; ?></h3>
                        <p class="text-xs md:text-sm text-blue-100 mt-1"><?php echo $lang == 'en' ? 'Get in touch with us' : 'Bizimle iletişime geçin'; ?></p>
                    </div>
                    <i class="fas fa-arrow-right text-white/50 ml-auto group-hover:translate-x-1 transition-transform"></i>
                </a>

                <!-- FAQ -->
                <a href="faq.php" class="group bg-gradient-to-r from-emerald-500 to-teal-600 p-4 md:p-6 rounded-2xl shadow-lg border border-emerald-400 hover:shadow-xl hover:scale-[1.02] transition-all flex items-center gap-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                        <i class="fas fa-question-circle text-white text-xl md:text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-white"><?php echo $lang == 'en' ? 'Help & FAQ' : 'Yardım & SSS'; ?></h3>
                        <p class="text-xs md:text-sm text-emerald-100 mt-1"><?php echo $lang == 'en' ? 'Answers to common questions' : 'Sık sorulan sorular'; ?></p>
                    </div>
                    <i class="fas fa-arrow-right text-white/50 ml-auto group-hover:translate-x-1 transition-transform"></i>
                </a>

                <!-- Changelog -->
                <a href="changelog.php" class="group bg-gradient-to-r from-orange-500 to-amber-600 p-4 md:p-6 rounded-2xl shadow-lg border border-orange-400 hover:shadow-xl hover:scale-[1.02] transition-all flex items-center gap-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:rotate-180 transition-transform duration-700">
                        <i class="fas fa-sync text-white text-xl md:text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-white"><?php echo $lang == 'en' ? 'Platform Updates' : 'Geliştirme Günlüğü'; ?></h3>
                        <p class="text-xs md:text-sm text-orange-100 mt-1"><?php echo $lang == 'en' ? 'See what\'s new' : 'Yenilikleri gör'; ?></p>
                    </div>
                    <i class="fas fa-arrow-right text-white/50 ml-auto group-hover:translate-x-1 transition-transform"></i>
                </a>

                <!-- Become Expert -->
                <a href="expert_application.php" class="group bg-gradient-to-r from-violet-600 to-purple-600 p-4 md:p-6 rounded-2xl shadow-lg border border-violet-500 hover:shadow-xl hover:scale-[1.02] transition-all flex items-center gap-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                        <i class="fas fa-certificate text-white text-xl md:text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-white"><?php echo $lang == 'en' ? 'Become a Local Expert' : 'Yerel Uzman Olun'; ?></h3>
                        <p class="text-xs md:text-sm text-violet-100 mt-1"><?php echo $lang == 'en' ? 'Verified Experts Badge Program' : 'Doğrulanmış Uzman Rozet Programı'; ?></p>
                    </div>
                    <div class="ml-auto bg-white/20 px-3 py-1 rounded-full text-xs font-bold text-white group-hover:bg-white group-hover:text-violet-600 transition-colors">
                        <?php echo $lang == 'en' ? 'Apply Now' : 'Başvur'; ?>
                    </div>
                </a>
            </div>
        </section>

    </main>

    <!-- Sticky Footer Spacer for Mobile Nav -->
    <div class="h-20 md:hidden"></div>

</body>
</html>
