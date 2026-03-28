<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
    <style>
        .aid-card {
            transition: all 0.3s ease;
        }
        .aid-card:hover {
            transform: translateY(-4px);
        }
        .aid-icon {
            width: 64px;
            height: 64px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }
        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .emergency-banner {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            animation: pulse-glow 2s infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(220, 38, 38, 0.4); }
            50% { box-shadow: 0 0 40px rgba(220, 38, 38, 0.6); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-28 max-w-4xl">
        
        <!-- Hero Section -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 bg-red-500/10 px-4 py-2 rounded-full mb-4">
                <i class="fas fa-kit-medical text-red-500"></i>
                <span class="text-sm font-bold text-red-600 dark:text-red-400">
                    <?php echo $lang == 'en' ? 'Tourist Safety Guide' : 'Turist Güvenlik Rehberi'; ?>
                </span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black mb-4 text-red-600 dark:text-red-400">
                <?php echo $lang == 'en' ? 'First Aid Kit' : 'İlk Yardım Çantası'; ?>
            </h1>
            <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                <?php echo $lang == 'en' 
                    ? 'Quick guide for common injuries and health issues in Kalkan. Stay safe and enjoy your holiday!' 
                    : 'Kalkan\'da sık karşılaşılan yaralanmalar ve sağlık sorunları için hızlı rehber. Güvende kalın ve tatilinizin keyfini çıkarın!'; ?>
            </p>
        </div>

        <!-- Emergency Banner -->
        <div class="emergency-banner rounded-2xl p-6 mb-10 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-phone-volume text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-xl"><?php echo $lang == 'en' ? 'Emergency Numbers' : 'Acil Numaralar'; ?></h3>
                        <p class="text-white/80 text-sm"><?php echo $lang == 'en' ? 'Save these numbers!' : 'Bu numaraları kaydedin!'; ?></p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="tel:112" class="bg-white/20 hover:bg-white/30 px-5 py-3 rounded-xl font-bold flex items-center gap-2 transition-colors">
                        <i class="fas fa-phone-volume"></i> 112 - <?php echo $lang == 'en' ? 'All Emergencies' : 'Tüm Acil Durumlar'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- First Aid Cards -->
        <div class="space-y-6 mb-10">
            
            <!-- Sea Urchin Card -->
            <div class="aid-card bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700">
                <div class="flex gap-4 mb-5">
                    <div class="aid-icon bg-blue-500/10 text-blue-500">
                        🦔
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-red-50 mb-1">
                            <?php echo $lang == 'en' ? 'Sea Urchin Sting' : 'Deniz Kestanesi Battı'; ?>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Common when swimming near rocks' : 'Kayalıklarda yüzerken sık rastlanır'; ?>
                        </p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-blue-500 text-white">1</div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-blue-50"><?php echo $lang == 'en' ? 'Don\'t panic!' : 'Panik yapmayın!'; ?></p>
                            <p class="text-sm text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Sea urchin stings are painful but rarely dangerous' : 'Deniz kestanesi batması acı verir ama nadiren tehlikelidir'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-blue-500 text-white">2</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Apply olive oil' : 'Zeytinyağı sürün'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Generously apply olive oil to the affected area. It softens the spines.' : 'Etkilenen bölgeye bol miktarda zeytinyağı sürün. Dikenleri yumuşatır.'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-blue-500 text-white">3</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Soak in hot water' : 'Sıcak suda bekletin'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Soak in hot (not boiling) water for 30-90 minutes. Heat breaks down the toxins.' : '30-90 dakika sıcak (kaynar değil) suda bekletin. Isı toksinleri parçalar.'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-blue-500 text-white">4</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Remove visible spines' : 'Görünür dikenleri çıkarın'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Use tweezers to carefully remove any visible spines. Don\'t dig!' : 'Görünür dikenleri cımbızla dikkatlice çıkarın. Kazımayın!'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <p class="text-sm text-amber-700 dark:text-amber-400 flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <?php echo $lang == 'en' 
                            ? 'Seek medical help if you experience severe pain, infection signs, or difficulty breathing.' 
                            : 'Şiddetli ağrı, enfeksiyon belirtileri veya nefes almada güçlük yaşarsanız tıbbi yardım alın.'; ?>
                    </p>
                </div>
            </div>

            <!-- Bee Sting Card -->
            <div class="aid-card bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700">
                <div class="flex gap-4 mb-5">
                    <div class="aid-icon bg-yellow-500/10 text-yellow-500">
                        🐝
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white mb-1">
                            <?php echo $lang == 'en' ? 'Bee or Wasp Sting' : 'Arı Sokması'; ?>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Common near restaurants and gardens' : 'Restoranlarda ve bahçelerde sık rastlanır'; ?>
                        </p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-yellow-500 text-white">1</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Remove the stinger' : 'İğneyi çıkarın'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Scrape it off with a credit card edge. Don\'t squeeze!' : 'Kredi kartı kenarıyla kazıyarak çıkarın. Sıkmayın!'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-yellow-500 text-white">2</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Clean the area' : 'Bölgeyi temizleyin'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Wash with soap and water' : 'Sabun ve suyla yıkayın'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-yellow-500 text-white">3</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Apply cold compress' : 'Soğuk kompres uygulayın'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Ice wrapped in cloth for 10-15 minutes to reduce swelling' : 'Şişliği azaltmak için beze sarılı buz 10-15 dakika uygulayın'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-yellow-500 text-white">4</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Take antihistamine if needed' : 'Gerekirse antihistaminik alın'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Available at local pharmacies (Eczane)' : 'Yerel eczanelerde bulabilirsiniz'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
                    <p class="text-sm text-red-700 dark:text-red-400 flex items-start gap-2">
                        <i class="fas fa-exclamation-circle mt-0.5"></i>
                        <strong><?php echo $lang == 'en' ? 'ALLERGIC REACTION WARNING:' : 'ALERJİK REAKSİYON UYARISI:'; ?></strong>
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                        <?php echo $lang == 'en' 
                            ? 'Call 112 immediately if experiencing: difficulty breathing, swelling of face/throat, dizziness, or rapid heartbeat.' 
                            : 'Nefes almada güçlük, yüz/boğaz şişmesi, baş dönmesi veya hızlı kalp atışı yaşarsanız hemen 112\'yi arayın.'; ?>
                    </p>
                </div>
            </div>

            <!-- Sunstroke Card -->
            <div class="aid-card bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700">
                <div class="flex gap-4 mb-5">
                    <div class="aid-icon bg-orange-500/10 text-orange-500">
                        ☀️
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white mb-1">
                            <?php echo $lang == 'en' ? 'Sunstroke / Heat Exhaustion' : 'Güneş Çarpması'; ?>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Very common in July-August' : 'Temmuz-Ağustos aylarında çok sık'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                        <h4 class="font-bold text-orange-700 dark:text-orange-400 mb-2 flex items-center gap-2">
                            <i class="fas fa-list-check"></i> <?php echo $lang == 'en' ? 'Symptoms' : 'Belirtiler'; ?>
                        </h4>
                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                            <li>• <?php echo $lang == 'en' ? 'Headache, dizziness' : 'Baş ağrısı, baş dönmesi'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Nausea, vomiting' : 'Mide bulantısı, kusma'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Hot, red skin' : 'Sıcak, kızarık cilt'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Rapid pulse' : 'Hızlı nabız'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Confusion' : 'Bilinç bulanıklığı'; ?></li>
                        </ul>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <h4 class="font-bold text-green-700 dark:text-green-400 mb-2 flex items-center gap-2">
                            <i class="fas fa-heart-pulse"></i> <?php echo $lang == 'en' ? 'What to Do' : 'Ne Yapmalı'; ?>
                        </h4>
                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                            <li>• <?php echo $lang == 'en' ? 'Move to shade/cool area' : 'Gölgeye/serin yere gidin'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Drink cool water slowly' : 'Yavaşça serin su için'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Apply cool cloths to neck/forehead' : 'Boyun/alna soğuk bez uygulayın'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Rest and avoid activity' : 'Dinlenin, aktiviteden kaçının'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Call 112 if severe' : 'Ciddi ise 112\'yi arayın'; ?></li>
                        </ul>
                    </div>
                </div>
                
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-700 dark:text-blue-400 flex items-start gap-2">
                        <i class="fas fa-lightbulb mt-0.5"></i>
                        <strong><?php echo $lang == 'en' ? 'Prevention:' : 'Önleme:'; ?></strong>
                        <?php echo $lang == 'en' 
                            ? 'Wear a hat, use SPF 50+, drink 2-3L water daily, avoid midday sun (12-16:00).' 
                            : 'Şapka takın, SPF 50+ kullanın, günde 2-3L su için, öğle güneşinden kaçının (12-16:00).'; ?>
                    </p>
                </div>
            </div>

            <!-- Jellyfish Card -->
            <div class="aid-card bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700">
                <div class="flex gap-4 mb-5">
                    <div class="aid-icon bg-purple-500/10 text-purple-500">
                        🪼
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white mb-1">
                            <?php echo $lang == 'en' ? 'Jellyfish Sting' : 'Denizanası Sokması'; ?>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Occasional in late summer' : 'Yaz sonunda zaman zaman görülür'; ?>
                        </p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-purple-500 text-white">1</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Rinse with seawater' : 'Deniz suyu ile yıkayın'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Do NOT use fresh water - it activates more stinging cells' : 'Tatlı su KULLANMAYIN - daha fazla iğne hücresini aktive eder'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-purple-500 text-white">2</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Remove tentacles carefully' : 'Dokunaçları dikkatlice çıkarın'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Use tweezers or credit card edge, never bare hands' : 'Cımbız veya kredi kartı kenarı kullanın, asla çıplak elle değil'; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <div class="step-number bg-purple-500 text-white">3</div>
                        <div>
                            <p class="font-bold text-slate-700 dark:text-slate-200"><?php echo $lang == 'en' ? 'Apply vinegar or ice' : 'Sirke veya buz uygulayın'; ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Helps neutralize toxins and reduce pain' : 'Toksinleri nötralize etmeye ve ağrıyı azaltmaya yardımcı olur'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mosquito Bites Card -->
            <div class="aid-card bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700">
                <div class="flex gap-4 mb-5">
                    <div class="aid-icon bg-slate-500/10 text-slate-600 dark:text-slate-400">
                        🦟
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white mb-1">
                            <?php echo $lang == 'en' ? 'Mosquito Bites' : 'Sivrisinek Isırığı'; ?>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Common in evenings near water' : 'Akşamları su yakınında sık görülür'; ?>
                        </p>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                        <h4 class="font-bold text-slate-700 dark:text-slate-200 mb-2"><?php echo $lang == 'en' ? 'Treatment' : 'Tedavi'; ?></h4>
                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                            <li>• <?php echo $lang == 'en' ? 'Wash with soap and water' : 'Sabun ve suyla yıkayın'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Apply anti-itch cream' : 'Kaşıntı giderici krem sürün'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Avoid scratching' : 'Kaşımaktan kaçının'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Ice for swelling' : 'Şişlik için buz'; ?></li>
                        </ul>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <h4 class="font-bold text-green-700 dark:text-green-400 mb-2"><?php echo $lang == 'en' ? 'Prevention' : 'Önleme'; ?></h4>
                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                            <li>• <?php echo $lang == 'en' ? 'Use insect repellent' : 'Böcek kovucu kullanın'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Wear long sleeves at dusk' : 'Alacakaranlıkta uzun kollu giyin'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Use plug-in repellers' : 'Elektrikli kovucu kullanın'; ?></li>
                        </ul>
                    </div>
                </div>
            </div>



        </div>


        <!-- Support Services Section -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700 mb-10">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-indigo-500/10 rounded-xl flex items-center justify-center">
                    <i class="fas fa-life-ring text-indigo-500 text-xl"></i>
                </div>
                <h2 class="text-xl font-black text-slate-800 dark:text-white">
                    <?php echo $lang == 'en' ? 'Official Support Services' : 'Resmi Destek Hizmetleri'; ?>
                </h2>
            </div>
            
            <div class="space-y-4">
                <!-- YIMER 157 Support -->
                <!-- YIMER 157 Support -->
                <div class="flex items-start gap-4 p-4 bg-red-50 dark:bg-slate-800 rounded-xl border border-red-100 dark:border-red-500/50 relative overflow-hidden group">
                    <!-- Glow Effect -->
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-red-500/20 blur-xl rounded-full pointer-events-none group-hover:bg-red-500/30 transition-all"></div>
                    
                    <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center text-white flex-shrink-0 animate-pulse relative z-10">
                        <i class="fas fa-phone-volume"></i>
                    </div>
                    <div class="flex-1 relative z-10">
                        <h3 class="font-bold text-slate-800 dark:text-white">
                            <?php echo $lang == 'en' ? 'YIMER 157 - Foreigner Center' : 'YİMER 157 - Yabancı İletişim'; ?>
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-300">
                            <?php echo $lang == 'en' ? '24/7 Support: Visa, residence, emergency' : '7/24 Destek: Vize, ikamet, acil durum'; ?>
                        </p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1 font-bold">
                            <i class="fas fa-language mr-1"></i>
                            <?php echo $lang == 'en' ? 'English, German, Russian, Arabic' : 'İngilizce, Almanca, Rusça, Arapça'; ?>
                        </p>
                    </div>
                    <a href="tel:157" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs transition-colors flex flex-col items-center text-center gap-1 min-w-[80px] relative z-10">
                        <i class="fas fa-phone mb-1"></i>
                        <span>157</span>
                    </a>
                </div>
                <!-- British Consulate Support -->
                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                        <i class="fas fa-passport"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800 dark:text-white">
                            <?php echo $lang == 'en' ? 'Consulate Support' : 'İngiliz Konsolosluğu Yardım'; ?>
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Lost passport or legal issues' : 'Pasaport kaybı veya yasal sorunlar'; ?>
                        </p>
                        <div class="text-xs text-slate-600 dark:text-slate-300 mt-2 space-y-1">
                            <p><strong>Fethiye:</strong> Atatürk Cad. Likya İş Merk. No:202</p>
                            <p><strong>Antalya:</strong> Gürsu Mah. 324. Sok. No:6</p>
                        </div>
                    </div>
                    <a href="tel:+902526146302" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition-colors flex flex-col items-center text-center gap-1 min-w-[80px]">
                        <i class="fas fa-phone-alt mb-1"></i>
                        <span><?php echo $lang == 'en' ? 'Fethiye' : 'Fethiye'; ?></span>
                    </a>
                </div>
                
                <!-- City Support -->
                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                        <i class="fas fa-city"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800 dark:text-white">
                            <?php echo $lang == 'en' ? 'City Support' : 'Belediye Şikayet Hattı'; ?>
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Garbage, lights, or stray animals' : 'Çöp, aydınlatma veya sokak hayvanları'; ?>
                        </p>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                            <i class="fas fa-info-circle text-orange-500 mr-1"></i>
                            <?php echo $lang == 'en' ? 'Kaş Municipality White Desk' : 'Kaş Belediyesi Beyaz Masa'; ?>
                        </p>
                    </div>
                    <a href="tel:4440711" class="px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl text-xs transition-colors flex flex-col items-center text-center gap-1 min-w-[80px]">
                        <i class="fas fa-headset mb-1"></i>
                        <span><?php echo $lang == 'en' ? 'Call Now' : 'Hemen Ara'; ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Health Centers Section -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700 mb-10">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                    <i class="fas fa-hospital text-emerald-500 text-xl"></i>
                </div>
                <h2 class="text-xl font-black text-slate-800 dark:text-white">
                    <?php echo $lang == 'en' ? 'Nearby Health Facilities' : 'Yakın Sağlık Tesisleri'; ?>
                </h2>
            </div>
            
            <div class="space-y-4">
                <!-- Kalkan Health Center -->
                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800 dark:text-white">Kalkan Sağlık Ocağı</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Kalkan Health Center</p>
                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                            <i class="fas fa-location-dot text-pink-500 mr-1"></i>
                            Kalkan Mahallesi, Antalya
                        </p>
                    </div>
                    <a href="https://maps.google.com/?q=Kalkan+Sağlık+Ocağı" target="_blank" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl text-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo $lang == 'en' ? 'Map' : 'Harita'; ?>
                    </a>
                </div>
                
                <!-- Kaş State Hospital -->
                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800 dark:text-white">Kaş Devlet Hastanesi</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Kaş State Hospital (25 km)</p>
                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                            <i class="fas fa-phone text-pink-500 mr-1"></i>
                            0242 836 10 51
                        </p>
                    </div>
                    <a href="https://maps.google.com/?q=Kaş+Devlet+Hastanesi" target="_blank" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-bold rounded-xl text-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo $lang == 'en' ? 'Map' : 'Harita'; ?>
                    </a>
                </div>
                
                <!-- Pharmacy -->
                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                        <i class="fas fa-prescription-bottle-medical"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800 dark:text-white"><?php echo $lang == 'en' ? 'Pharmacy on Duty' : 'Nöbetçi Eczane'; ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Find the pharmacy open now' : 'Şu an açık olan eczaneyi bulun'; ?></p>
                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                            <i class="fas fa-clock text-pink-500 mr-1"></i>
                            <?php echo $lang == 'en' ? '24/7 duty rotation' : '7/24 nöbet sistemi'; ?>
                        </p>
                    </div>
                    <a href="duty_pharmacy" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl text-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-pills"></i>
                        <?php echo $lang == 'en' ? 'View' : 'Görüntüle'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Tips Section -->
        <div class="bg-gradient-to-r from-pink-500/10 to-violet-500/10 rounded-3xl p-6 border border-pink-500/20 mb-10">
            <h3 class="font-black text-xl mb-4 flex items-center gap-2">
                <i class="fas fa-lightbulb text-pink-500"></i>
                <?php echo $lang == 'en' ? 'General Safety Tips' : 'Genel Güvenlik İpuçları'; ?>
            </h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' ? 'Wear water shoes when swimming near rocks' : 'Kayalıklarda yüzerken deniz ayakkabısı giyin'; ?>
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' ? 'Keep food covered to avoid wasps' : 'Arılardan korunmak için yiyecekleri kapalı tutun'; ?>
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' ? 'Stay hydrated - carry a water bottle' : 'Su şişesi taşıyın - susuz kalmayın'; ?>
                    </p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' ? 'Use reef-safe sunscreen (SPF 50+)' : 'Mercan dostu güneş kremi kullanın (SPF 50+)'; ?>
                    </p>
                </div>
            </div>
        </div>

    </main>

</body>
</html>
