<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// Coming Soon - redirect to services
header('Location: services');
exit;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Kalkan Time Travel - Historical Photos' : 'Kalkan Zaman Yolculuğu - Tarihi Fotoğraflar'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        /* Before/After Slider Styles */
        .comparison-slider {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            touch-action: none;
            user-select: none;
        }
        
        .comparison-slider img {
            display: block;
            width: 100%;
            height: auto;
            pointer-events: none;
        }
        
        .comparison-slider .img-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 50%;
            overflow: hidden;
            z-index: 2;
        }
        
        .comparison-slider .img-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: auto;
            min-width: 100%;
            max-width: none;
        }
        
        .comparison-slider .slider-handle {
            position: absolute;
            top: 0;
            left: 50%;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #ec4899, #8b5cf6);
            z-index: 10;
            cursor: ew-resize;
            transform: translateX(-50%);
        }
        
        .comparison-slider .slider-handle::before,
        .comparison-slider .slider-handle::after {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border: 8px solid transparent;
        }
        
        .comparison-slider .slider-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #ec4899, #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(236, 72, 153, 0.4);
            cursor: ew-resize;
            z-index: 11;
        }
        
        .comparison-slider .slider-button i {
            color: white;
            font-size: 1.25rem;
        }
        
        .comparison-slider .label {
            position: absolute;
            bottom: 16px;
            padding: 8px 16px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            border-radius: 9999px;
            z-index: 5;
        }
        
        .comparison-slider .label-before {
            left: 16px;
        }
        
        .comparison-slider .label-after {
            right: 16px;
        }
        
        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .gallery-card {
            position: relative;
            border-radius: 1.5rem;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            transition: all 0.5s ease;
        }
        
        .gallery-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(236, 72, 153, 0.25);
        }
        
        .gallery-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }
        
        .gallery-card:hover img {
            transform: scale(1.1);
        }
        
        .gallery-card .card-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-card:hover .card-overlay {
            opacity: 1;
        }
        
        .gallery-card .card-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .gallery-card:hover .card-content {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Sepia filter for old photos */
        .sepia-overlay {
            filter: sepia(30%);
        }
        
        /* Lightbox */
        .lightbox {
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(0,0,0,0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .lightbox.active {
            opacity: 1;
            visibility: visible;
        }
        
        .lightbox img {
            max-width: 90%;
            max-height: 90vh;
            border-radius: 1rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        
        .lightbox-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .lightbox-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-28 max-w-6xl">
        
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 bg-gradient-to-r from-pink-500/10 to-violet-500/10 px-4 py-2 rounded-full mb-4">
                <i class="fas fa-clock-rotate-left text-pink-500"></i>
                <span class="text-sm font-bold text-pink-600 dark:text-pink-400">
                    <?php echo $lang == 'en' ? 'Historical Archive' : 'Tarihi Arşiv'; ?>
                </span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black mb-4 bg-gradient-to-r from-pink-500 to-violet-500 bg-clip-text text-transparent">
                <?php echo $lang == 'en' ? 'Kalkan Time Travel' : 'Kalkan Zaman Yolculuğu'; ?>
            </h1>
            <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                <?php echo $lang == 'en' 
                    ? 'Explore the transformation of Kalkan from the Greek village of Kalamaki to the charming coastal town it is today. Slide through time and witness history unfold.' 
                    : 'Kalkan\'ın eski Rum köyü Kalamaki\'den bugünkü büyüleyici sahil kasabasına dönüşümünü keşfedin. Zaman içinde kayarak tarihin akışına tanık olun.'; ?>
            </p>
        </div>

        <!-- Featured Before/After Slider -->
        <div class="mb-16">
            <h2 class="text-2xl font-black mb-6 flex items-center gap-3">
                <span class="w-2 h-8 bg-gradient-to-b from-pink-500 to-violet-500 rounded-full"></span>
                <?php echo $lang == 'en' ? 'Then & Now' : 'Dün ve Bugün'; ?>
            </h2>
            
            <div class="comparison-slider" id="slider1">
                <img src="assets/kalkan/kalkan2.jpg" alt="Kalkan Today" class="img-after">
                <div class="img-wrapper">
                    <img src="assets/kalkan/kalkan3.jpg" alt="Kalkan 1980s" class="img-before sepia-overlay">
                </div>
                <div class="slider-handle">
                    <div class="slider-button">
                        <i class="fas fa-arrows-left-right"></i>
                    </div>
                </div>
                <span class="label label-before"><?php echo $lang == 'en' ? '1980s' : '1980\'ler'; ?></span>
                <span class="label label-after"><?php echo $lang == 'en' ? 'Today' : 'Bugün'; ?></span>
            </div>
            
            <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-4 italic">
                <i class="fas fa-hand-pointer mr-1"></i>
                <?php echo $lang == 'en' ? 'Drag the slider to compare' : 'Karşılaştırmak için kaydırın'; ?>
            </p>
        </div>

        <!-- Historical Gallery -->
        <div class="mb-16">
            <h2 class="text-2xl font-black mb-6 flex items-center gap-3">
                <span class="w-2 h-8 bg-gradient-to-b from-amber-500 to-orange-500 rounded-full"></span>
                <?php echo $lang == 'en' ? 'Historical Photos' : 'Tarihi Fotoğraflar'; ?>
            </h2>
            
            <div class="gallery-grid">
                <?php
                $historical_images = [
                    ['file' => 'kalkan2.jpg', 'title_en' => 'Kalkan Harbor', 'title_tr' => 'Kalkan Limanı', 'year' => '1970s'],
                    ['file' => 'kalkan3.jpg', 'title_en' => 'Old Town View', 'title_tr' => 'Eski Şehir Manzarası', 'year' => '1980s'],
                    ['file' => 'kalkan4.jpg', 'title_en' => 'Traditional Houses', 'title_tr' => 'Geleneksel Evler', 'year' => '1960s'],
                    ['file' => 'kalkan5.jpg', 'title_en' => 'Village Life', 'title_tr' => 'Köy Hayatı', 'year' => '1975'],
                    ['file' => 'kalkan6.jpg', 'title_en' => 'Seaside', 'title_tr' => 'Sahil', 'year' => '1980s'],
                    ['file' => 'kalkan7.jpg', 'title_en' => 'Stone Streets', 'title_tr' => 'Taş Sokaklar', 'year' => '1970s'],
                    ['file' => 'kalkan8.jpg', 'title_en' => 'Historical View', 'title_tr' => 'Tarihi Görünüm', 'year' => '1965'],
                    ['file' => 'kalkan9.jpg', 'title_en' => 'Kalamaki Era', 'title_tr' => 'Kalamaki Dönemi', 'year' => '1950s'],
                    ['file' => 'kalkan10.jpg', 'title_en' => 'Old Kalkan', 'title_tr' => 'Eski Kalkan', 'year' => '1960s'],
                    ['file' => 'kalkan11.jpg', 'title_en' => 'Coastal Town', 'title_tr' => 'Sahil Kasabası', 'year' => '1970s'],
                    ['file' => 'kalkan12.jpg', 'title_en' => 'Village Square', 'title_tr' => 'Köy Meydanı', 'year' => '1975'],
                    ['file' => 'kalkan13.jpg', 'title_en' => 'Daily Life', 'title_tr' => 'Günlük Yaşam', 'year' => '1980s'],
                    ['file' => 'suleymanyılmazokuluacilis.jpg', 'title_en' => 'School Opening', 'title_tr' => 'Süleyman Yılmaz Okulu Açılışı', 'year' => '1960s'],
                ];
                
                foreach($historical_images as $img):
                    $title = $lang == 'en' ? $img['title_en'] : $img['title_tr'];
                ?>
                <div class="gallery-card" onclick="openLightbox('assets/kalkan/<?php echo $img['file']; ?>')">
                    <img src="assets/kalkan/<?php echo $img['file']; ?>" alt="<?php echo $title; ?>" class="sepia-overlay" loading="lazy">
                    <div class="card-overlay"></div>
                    <div class="card-content">
                        <span class="inline-block bg-amber-500/90 text-white text-xs font-bold px-2 py-1 rounded-full mb-2">
                            <?php echo $img['year']; ?>
                        </span>
                        <h3 class="text-white font-bold text-lg"><?php echo $title; ?></h3>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- About Kalkan History -->
        <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl p-8 border border-white/20 dark:border-slate-700/50 mb-16">
            <div class="flex flex-col md:flex-row gap-8 items-center">
                <div class="w-24 h-24 bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-orange-500/30">
                    <i class="fas fa-landmark text-white text-4xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black mb-3">
                        <?php echo $lang == 'en' ? 'The Story of Kalamaki' : 'Kalamaki\'nin Hikayesi'; ?>
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                        <?php echo $lang == 'en' 
                            ? 'Kalkan was originally a Greek fishing village called Kalamaki until the population exchange between Greece and Turkey in 1923. The village was resettled by Turkish families from the islands of Rhodes and Crete. The name Kalkan means "shield" in Turkish, possibly referring to the shape of the bay. The charming Greek architecture, with its white-washed stone houses and narrow cobblestone streets, still defines the character of the old town today.'
                            : 'Kalkan, 1923\'teki Türkiye-Yunanistan nüfus mübadelesine kadar Kalamaki adında bir Rum balıkçı köyüydü. Köy, Rodos ve Girit adalarından gelen Türk aileleri tarafından yeniden yerleşildi. Kalkan adı Türkçe\'de "kalkan" anlamına gelir, muhtemelen koydun şeklinden geliyor. Beyaz badanalı taş evleri ve dar arnavut kaldırımlı sokaklarıyla büyüleyici Yunan mimarisi, bugün hâlâ eski şehrin karakterini tanımlıyor.'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="text-center mb-16">
            <div class="bg-gradient-to-r from-pink-500/10 to-violet-500/10 rounded-3xl p-8 border border-pink-500/20">
                <i class="fas fa-camera-retro text-5xl text-pink-500 mb-4"></i>
                <h3 class="text-2xl font-black mb-3">
                    <?php echo $lang == 'en' ? 'Have Old Photos?' : 'Eski Fotoğraflarınız Var mı?'; ?>
                </h3>
                <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-md mx-auto">
                    <?php echo $lang == 'en' 
                        ? 'Help preserve Kalkan\'s history! Share your vintage photos of Kalkan with our community.'
                        : 'Kalkan\'ın tarihini korumaya yardımcı olun! Eski Kalkan fotoğraflarınızı topluluğumuzla paylaşın.'; ?>
                </p>
                <a href="messages" class="inline-flex items-center gap-2 bg-gradient-to-r from-pink-500 to-violet-500 text-white px-8 py-4 rounded-2xl font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                    <i class="fas fa-share"></i>
                    <?php echo $lang == 'en' ? 'Share Your Photos' : 'Fotoğraflarınızı Paylaşın'; ?>
                </a>
            </div>
        </div>

    </main>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">
            <i class="fas fa-times"></i>
        </button>
        <img id="lightbox-img" src="" alt="Full size image">
    </div>

    <script>
    // Before/After Slider Logic
    document.querySelectorAll('.comparison-slider').forEach(slider => {
        const handle = slider.querySelector('.slider-handle');
        const imgWrapper = slider.querySelector('.img-wrapper');
        let isDragging = false;

        function updateSlider(x) {
            const rect = slider.getBoundingClientRect();
            let percentage = ((x - rect.left) / rect.width) * 100;
            percentage = Math.max(0, Math.min(100, percentage));
            
            handle.style.left = percentage + '%';
            imgWrapper.style.width = percentage + '%';
        }

        // Mouse Events
        handle.addEventListener('mousedown', (e) => {
            isDragging = true;
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                updateSlider(e.clientX);
            }
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        // Touch Events for Mobile
        handle.addEventListener('touchstart', (e) => {
            isDragging = true;
            e.preventDefault();
        });

        document.addEventListener('touchmove', (e) => {
            if (isDragging && e.touches[0]) {
                updateSlider(e.touches[0].clientX);
            }
        });

        document.addEventListener('touchend', () => {
            isDragging = false;
        });

        // Click anywhere on slider to jump
        slider.addEventListener('click', (e) => {
            if (e.target !== handle && !handle.contains(e.target)) {
                updateSlider(e.clientX);
            }
        });
    });

    // Lightbox Functions
    function openLightbox(src) {
        const lightbox = document.getElementById('lightbox');
        const img = document.getElementById('lightbox-img');
        img.src = src;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        const lightbox = document.getElementById('lightbox');
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });
    </script>

</body>
</html>
