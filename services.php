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
                <?php echo $lang == 'en' ? 'Everything your community needs in one place' : 'Kalkan\'da yaşam için ihtiyacınız olan her şey'; ?>
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
                <!-- Happy Hour / Nightlife -->
                <a href="happy_hour" class="group bg-white dark:bg-slate-800 p-4 md:p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-violet-300 dark:hover:border-violet-700 transition-all hover:scale-105">
                    <div class="w-12 h-12 md:w-14 md:h-14 bg-violet-50 dark:bg-violet-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <?php echo heroicon('music', 'text-2xl md:text-3xl text-violet-500'); ?>
                    </div>
                    <h3 class="font-bold text-sm md:text-base text-center text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Nightlife' : 'Gece Hayatı'; ?></h3>
                    <p class="text-[10px] md:text-xs text-slate-400 text-center mt-1"><?php echo $lang == 'en' ? 'Bars & Events' : 'Barlar & Etkinlikler'; ?></p>
                </a>
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
                NeighbourNet <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500">Snaps</span>
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
                           NeighbourNet <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-violet-400">Snaps</span>
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
                           <h3 class="text-2xl md:text-3xl font-black text-white mb-2">NeighbourNet <span class="text-pink-300">Snaps</span></h3>
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


