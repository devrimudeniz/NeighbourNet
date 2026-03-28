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
    <title><?php echo $lang == 'en' ? 'Help & FAQ - Kalkan Social' : 'Yardım & SSS - Kalkan Social'; ?></title>
    <meta name="description" content="<?php echo $lang == 'en' ? 'Frequently asked questions about Kalkan Social - Learn about our mission, services, and how to use the platform' : 'Kalkan Social hakkında sıkça sorulan sorular - Misyonumuz, hizmetlerimiz ve platformu nasıl kullanacağınız hakkında bilgi edinin'; ?>">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32 transition-colors duration-300 overflow-x-hidden">

    <!-- Premium Background -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 md:pt-28 max-w-4xl">
        
        <!-- Page Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-3xl mb-4 shadow-lg">
                <i class="fas fa-question-circle text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl md:text-5xl font-black bg-clip-text text-transparent bg-gradient-to-r from-emerald-500 to-teal-600 mb-3">
                <?php echo $lang == 'en' ? 'Help & FAQ' : 'Yardım & SSS'; ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base max-w-2xl mx-auto">
                <?php echo $lang == 'en' ? 'Everything you need to know about Kalkan Social' : 'Kalkan Social hakkında bilmeniz gereken her şey'; ?>
            </p>
        </div>

        <!-- Mission Statement Card -->
        <section class="mb-12">
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 p-6 md:p-8 rounded-3xl shadow-lg border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-heart text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white mb-3">
                            <?php echo $lang == 'en' ? 'Our Mission' : 'Misyonumuz'; ?>
                        </h2>
                        <p class="text-slate-600 dark:text-slate-300 text-sm md:text-base leading-relaxed mb-4">
                            <?php if($lang == 'en'): ?>
                                Kalkan Social is more than just a platform—it's the digital heart of our beloved Kalkan community. We're here to connect neighbors, preserve local culture, support small businesses, and make everyday life easier for everyone who calls Kalkan home. Whether you're a lifelong resident or a newcomer, we're building a space where everyone belongs.
                            <?php else: ?>
                                Kalkan Social, sadece bir platform değil—sevgili Kalkan topluluğumuzun dijital kalbidir. Komşuları birbirine bağlamak, yerel kültürü korumak, küçük işletmeleri desteklemek ve Kalkan'ı evim diyenlerin günlük yaşamını kolaylaştırmak için buradayız. İster uzun süredir burada yaşıyor olun, ister yeni gelen biri olun, herkesin ait olduğu bir alan inşa ediyoruz.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Core Values -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/60 dark:bg-slate-800/60 p-4 rounded-2xl">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-users text-emerald-500"></i>
                            <h3 class="font-bold text-slate-800 dark:text-white text-sm">
                                <?php echo $lang == 'en' ? 'Community First' : 'Topluluk Öncelikli'; ?>
                            </h3>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Building meaningful connections between neighbors' : 'Komşular arasında anlamlı bağlantılar kurmak'; ?>
                        </p>
                    </div>
                    
                    <div class="bg-white/60 dark:bg-slate-800/60 p-4 rounded-2xl">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-globe text-teal-500"></i>
                            <h3 class="font-bold text-slate-800 dark:text-white text-sm">
                                <?php echo $lang == 'en' ? 'Local Culture' : 'Yerel Kültür'; ?>
                            </h3>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Preserving and celebrating Kalkan\'s unique heritage' : 'Kalkan\'ın benzersiz mirasını korumak ve kutlamak'; ?>
                        </p>
                    </div>
                    
                    <div class="bg-white/60 dark:bg-slate-800/60 p-4 rounded-2xl">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-lightbulb text-amber-500"></i>
                            <h3 class="font-bold text-slate-800 dark:text-white text-sm">
                                <?php echo $lang == 'en' ? 'Innovation' : 'Yenilikçilik'; ?>
                            </h3>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Using technology to solve everyday challenges' : 'Günlük zorlukları çözmek için teknolojiyi kullanmak'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Categories -->
        <section class="mb-12">
            <h2 class="text-2xl font-black text-slate-800 dark:text-white mb-6">
                <?php echo $lang == 'en' ? 'Frequently Asked Questions' : 'Sıkça Sorulan Sorular'; ?>
            </h2>

            <!-- General Questions -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <?php echo $lang == 'en' ? 'General Questions' : 'Genel Sorular'; ?>
                </h3>
                <div class="space-y-3">
                    <!-- FAQ 1 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(1)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'What is Kalkan Social?' : 'Kalkan Social nedir?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-1"></i>
                        </button>
                        <div id="faq-content-1" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Kalkan Social is a comprehensive community platform designed specifically for Kalkan residents and visitors. We bring together social networking, local services, business directory, events, news, and AI-powered tools—all in one place to make life in Kalkan easier and more connected.
                            <?php else: ?>
                                Kalkan Social, özellikle Kalkan sakinleri ve ziyaretçileri için tasarlanmış kapsamlı bir topluluk platformudur. Sosyal ağ, yerel hizmetler, işletme rehberi, etkinlikler, haberler ve yapay zeka destekli araçları tek bir yerde bir araya getirerek Kalkan'daki yaşamı daha kolay ve bağlantılı hale getiriyoruz.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(2)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'Is Kalkan Social free to use?' : 'Kalkan Social kullanımı ücretsiz mi?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-2"></i>
                        </button>
                        <div id="faq-content-2" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Yes! Kalkan Social is completely free for all community members. Our mission is to serve the Kalkan community, and we believe everyone should have access to these essential services and connections.
                            <?php else: ?>
                                Evet! Kalkan Social tüm topluluk üyeleri için tamamen ücretsizdir. Misyonumuz Kalkan topluluğuna hizmet etmektir ve herkesin bu temel hizmetlere ve bağlantılara erişimi olması gerektiğine inanıyoruz.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(3)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'Who can join Kalkan Social?' : 'Kalkan Social\'e kimler katılabilir?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-3"></i>
                        </button>
                        <div id="faq-content-3" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Anyone connected to Kalkan! Whether you're a permanent resident, seasonal visitor, business owner, or someone planning to visit—everyone is welcome. We support both Turkish and English languages to serve our diverse community.
                            <?php else: ?>
                                Kalkan ile bağlantılı herkes! İster daimi sakin, ister mevsimlik ziyaretçi, ister işletme sahibi, ister ziyaret etmeyi planlayan biri olun—herkes hoş geldiniz. Çeşitli topluluğumuza hizmet etmek için hem Türkçe hem de İngilizce dillerini destekliyoruz.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account & Features -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                    <i class="fas fa-user-circle text-violet-500"></i>
                    <?php echo $lang == 'en' ? 'Account & Features' : 'Hesap & Özellikler'; ?>
                </h3>
                <div class="space-y-3">
                    <!-- FAQ 4 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(4)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'How do I create an account?' : 'Nasıl hesap oluşturabilirim?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-4"></i>
                        </button>
                        <div id="faq-content-4" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Click "Login" in the top right corner, then select "Sign Up". You can register with your email or use Google Sign-In for quick access. Fill in your details, verify your email, and you're ready to go!
                            <?php else: ?>
                                Sağ üst köşedeki "Giriş"e tıklayın, ardından "Kayıt Ol"u seçin. E-postanızla kayıt olabilir veya hızlı erişim için Google ile Giriş'i kullanabilirsiniz. Bilgilerinizi doldurun, e-postanızı doğrulayın ve hazırsınız!
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 5 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(5)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'What are the AI tools for?' : 'Yapay zeka araçları ne için?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-5"></i>
                        </button>
                        <div id="faq-content-5" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Our AI tools help bridge language and cultural gaps. They can translate menus, identify medications, analyze bills and documents, match grocery products, and provide cultural insights—making daily life easier for both Turkish speakers and international residents.
                            <?php else: ?>
                                Yapay zeka araçlarımız dil ve kültür farklarını aşmaya yardımcı olur. Menüleri çevirebilir, ilaçları tanımlayabilir, faturaları ve belgeleri analiz edebilir, market ürünlerini eşleştirebilir ve kültürel içgörüler sağlayabilir—hem Türkçe konuşanlar hem de uluslararası sakinler için günlük yaşamı kolaylaştırır.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 6 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(6)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'How do I change my language preference?' : 'Dil tercihimi nasıl değiştirebilirim?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-6"></i>
                        </button>
                        <div id="faq-content-6" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Click on your profile picture, go to Settings, and select your preferred language (Turkish or English). The entire platform will switch to your chosen language instantly.
                            <?php else: ?>
                                Profil resminize tıklayın, Ayarlar'a gidin ve tercih ettiğiniz dili seçin (Türkçe veya İngilizce). Tüm platform anında seçtiğiniz dile geçecektir.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business & Services -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                    <i class="fas fa-briefcase text-teal-500"></i>
                    <?php echo $lang == 'en' ? 'Business & Services' : 'İşletme & Hizmetler'; ?>
                </h3>
                <div class="space-y-3">
                    <!-- FAQ 7 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(7)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'How can I add my business to the directory?' : 'İşletmemi rehbere nasıl ekleyebilirim?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-7"></i>
                        </button>
                        <div id="faq-content-7" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Simply create an account, go to the Directory section, and click "Add Business". Fill in your business details, upload photos, and submit for review. Our team will verify and publish your listing within 24-48 hours.
                            <?php else: ?>
                                Bir hesap oluşturun, Rehber bölümüne gidin ve "İşletme Ekle"ye tıklayın. İşletme bilgilerinizi doldurun, fotoğraflar yükleyin ve inceleme için gönderin. Ekibimiz 24-48 saat içinde listenizi doğrulayıp yayınlayacaktır.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 8 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(8)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'How do I post a job listing?' : 'İş ilanı nasıl yayınlarım?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-8"></i>
                        </button>
                        <div id="faq-content-8" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Navigate to the Jobs section and click "Post a Job". Fill in the job details, requirements, and contact information. Your listing will be reviewed and published within 24 hours.
                            <?php else: ?>
                                İş İlanları bölümüne gidin ve "İlan Yayınla"ya tıklayın. İş detaylarını, gereksinimleri ve iletişim bilgilerini doldurun. İlanınız 24 saat içinde incelenip yayınlanacaktır.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 9 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(9)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'Can I advertise my property for rent or sale?' : 'Kiralık veya satılık mülkümü ilan edebilir miyim?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-9"></i>
                        </button>
                        <div id="faq-content-9" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Yes! Visit the Property Hub section and click "Add Property". Upload photos, add details, pricing, and contact information. Your property listing will be visible to the entire community after verification.
                            <?php else: ?>
                                Evet! Emlak Merkezi bölümünü ziyaret edin ve "Mülk Ekle"ye tıklayın. Fotoğraflar yükleyin, detayları, fiyatlandırmayı ve iletişim bilgilerini ekleyin. Mülk ilanınız doğrulamadan sonra tüm topluluk tarafından görülebilir olacaktır.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support & Contact -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                    <i class="fas fa-headset text-pink-500"></i>
                    <?php echo $lang == 'en' ? 'Support & Contact' : 'Destek & İletişim'; ?>
                </h3>
                <div class="space-y-3">
                    <!-- FAQ 10 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(10)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'How do I report an issue or suggest a feature?' : 'Bir sorunu nasıl bildirebilirim veya özellik önerisinde bulunabilirim?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-10"></i>
                        </button>
                        <div id="faq-content-10" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                We love hearing from our community! Visit our Contact page or send us a message directly through the platform. We review all feedback and continuously improve based on your suggestions.
                            <?php else: ?>
                                Topluluğumuzdan haber almayı seviyoruz! İletişim sayfamızı ziyaret edin veya platform üzerinden bize doğrudan mesaj gönderin. Tüm geri bildirimleri inceliyoruz ve önerilerinize göre sürekli gelişiyoruz.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 11 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(11)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'How can I report inappropriate content?' : 'Uygunsuz içeriği nasıl bildirebilirim?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-11"></i>
                        </button>
                        <div id="faq-content-11" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Click the three dots (•••) on any post or comment and select "Report". Choose the reason for reporting and submit. Our moderation team reviews all reports within 24 hours.
                            <?php else: ?>
                                Herhangi bir gönderi veya yorumdaki üç noktaya (•••) tıklayın ve "Bildir"i seçin. Bildirme nedenini seçin ve gönderin. Moderasyon ekibimiz tüm bildirimleri 24 saat içinde inceler.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- FAQ 12 -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <button onclick="toggleFAQ(12)" class="w-full p-4 md:p-5 flex items-center justify-between text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <h4 class="font-bold text-slate-800 dark:text-white text-sm md:text-base">
                                <?php echo $lang == 'en' ? 'Is my personal information safe?' : 'Kişisel bilgilerim güvende mi?'; ?>
                            </h4>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform duration-300" id="faq-icon-12"></i>
                        </button>
                        <div id="faq-content-12" class="hidden px-4 md:px-5 pb-4 md:pb-5 text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                            <?php if($lang == 'en'): ?>
                                Absolutely! We take privacy seriously. Your data is encrypted, never sold to third parties, and you have full control over what information is visible to others. Read our Privacy Policy for complete details.
                            <?php else: ?>
                                Kesinlikle! Gizliliği ciddiye alıyoruz. Verileriniz şifrelenir, asla üçüncü taraflara satılmaz ve hangi bilgilerin başkaları tarafından görülebileceği konusunda tam kontrole sahipsiniz. Tam detaylar için Gizlilik Politikamızı okuyun.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Legal & Policies Section -->
        <section class="mb-12">
            <h2 class="text-2xl font-black text-slate-800 dark:text-white mb-6">
                <?php echo $lang == 'en' ? 'Legal & Policies' : 'Yasal & Politikalar'; ?>
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Privacy Policy -->
                <a href="privacy.php" class="group bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-blue-300 dark:hover:border-blue-700 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <i class="fas fa-shield-alt text-blue-500 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-800 dark:text-white mb-1">
                                <?php echo $lang == 'en' ? 'Privacy Policy' : 'Gizlilik Politikası'; ?>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?php echo $lang == 'en' ? 'How we protect your personal data' : 'Kişisel verilerinizi nasıl koruyoruz'; ?>
                            </p>
                        </div>
                        <i class="fas fa-arrow-right text-slate-300 dark:text-slate-600 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"></i>
                    </div>
                </a>

                <!-- KVKK -->
                <a href="kvkk.php" class="group bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-purple-300 dark:hover:border-purple-700 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-shield text-purple-500 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-800 dark:text-white mb-1">
                                <?php echo $lang == 'en' ? 'KVKK / GDPR' : 'KVKK Aydınlatma Metni'; ?>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?php echo $lang == 'en' ? 'Personal data protection law' : 'Kişisel verilerin korunması kanunu'; ?>
                            </p>
                        </div>
                        <i class="fas fa-arrow-right text-slate-300 dark:text-slate-600 group-hover:text-purple-500 group-hover:translate-x-1 transition-all"></i>
                    </div>
                </a>

                <!-- Terms of Service -->
                <a href="terms.php" class="group bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-emerald-300 dark:hover:border-emerald-700 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <i class="fas fa-file-contract text-emerald-500 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-800 dark:text-white mb-1">
                                <?php echo $lang == 'en' ? 'Terms of Service' : 'Kullanım Şartları'; ?>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?php echo $lang == 'en' ? 'Rules and guidelines for using our platform' : 'Platformumuzu kullanma kuralları'; ?>
                            </p>
                        </div>
                        <i class="fas fa-arrow-right text-slate-300 dark:text-slate-600 group-hover:text-emerald-500 group-hover:translate-x-1 transition-all"></i>
                    </div>
                </a>

                <!-- Safety Standards -->
                <a href="safety_standards.php" class="group bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl hover:border-orange-300 dark:hover:border-orange-700 transition-all hover:scale-[1.02]">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-orange-50 dark:bg-orange-900/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                            <i class="fas fa-check-circle text-orange-500 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-slate-800 dark:text-white mb-1">
                                <?php echo $lang == 'en' ? 'Safety Standards' : 'Güvenlik Standartları'; ?>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?php echo $lang == 'en' ? 'Community guidelines and safety measures' : 'Topluluk kuralları ve güvenlik önlemleri'; ?>
                            </p>
                        </div>
                        <i class="fas fa-arrow-right text-slate-300 dark:text-slate-600 group-hover:text-orange-500 group-hover:translate-x-1 transition-all"></i>
                    </div>
                </a>
            </div>
        </section>

        <!-- Need More Help Card -->
        <section class="mb-12">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-8 rounded-3xl shadow-2xl text-center">
                <i class="fas fa-life-ring text-white text-4xl mb-4"></i>
                <h2 class="text-2xl md:text-3xl font-black text-white mb-3">
                    <?php echo $lang == 'en' ? 'Still Need Help?' : 'Hala Yardıma mı İhtiyacınız Var?'; ?>
                </h2>
                <p class="text-blue-100 text-sm md:text-base mb-6 max-w-xl mx-auto">
                    <?php echo $lang == 'en' ? 'Our support team is here for you. We typically respond within 24 hours.' : 'Destek ekibimiz sizin için burada. Genellikle 24 saat içinde yanıt veriyoruz.'; ?>
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="contact.php" class="inline-flex items-center justify-center gap-2 bg-white text-blue-600 px-6 py-3 rounded-xl font-bold hover:scale-105 transition-transform shadow-lg">
                        <i class="fas fa-envelope"></i>
                        <?php echo $lang == 'en' ? 'Contact Support' : 'Destek Ekibiyle İletişime Geç'; ?>
                    </a>
                    <a href="services" class="inline-flex items-center justify-center gap-2 bg-white/20 backdrop-blur border-2 border-white/30 text-white px-6 py-3 rounded-xl font-bold hover:bg-white/30 transition-all">
                        <i class="fas fa-arrow-left"></i>
                        <?php echo $lang == 'en' ? 'Back to Services' : 'Hizmetlere Dön'; ?>
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- Sticky Footer Spacer for Mobile Nav -->
    <div class="h-20 md:hidden"></div>

    <script>
    function toggleFAQ(id) {
        const content = document.getElementById(`faq-content-${id}`);
        const icon = document.getElementById(`faq-icon-${id}`);
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }
    </script>

</body>
</html>
