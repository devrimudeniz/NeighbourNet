<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// $lang is already set by lang.php

// Photo Spots Data (Static for now)
$spots = [
    [
        'title_en' => 'Kaleiçi (Old Town) - Antalya',
        'title_tr' => 'Kaleiçi (Eski Şehir) - Antalya',
        'desc_en' => 'Historic narrow streets, Hadrian\'s Gate, and sea views. Perfect for nostalgic and architectural shots.',
        'desc_tr' => 'Tarihi dar sokaklar, Hadrian Kapısı ve deniz manzarası. Nostaljik ve mimari fotoğraflar için mükemmel.',
        'image' => 'assets/photos/antalyakaleici.jpg',
        'location' => 'Antalya, Merkez'
    ],
    [
        'title_en' => 'Kaputaş Beach',
        'title_tr' => 'Kaputaş Plajı',
        'desc_en' => 'Famous for its turquoise waves and golden sand. A must-visit spot for drone shots and beach portraits.',
        'desc_tr' => 'Turkuaz dalgaları ve altın rengi kumsalıyla ünlü. Drone çekimleri ve plaj portreleri için vazgeçilmez bir nokta.',
        'image' => 'assets/photos/kaputas.jpg',
        'location' => 'Kaş - Kalkan Yolu'
    ],
    [
        'title_en' => 'Ölüdeniz & Butterfly Valley',
        'title_tr' => 'Ölüdeniz & Kelebekler Vadisi',
        'desc_en' => 'The Blue Lagoon offers crystal clear waters. Paragliding shots here are iconic.',
        'desc_tr' => 'Mavi Lagün kristal berraklığında sular sunar. Burada yamaç paraşütü fotoğrafları ikoniktir.',
        'image' => 'assets/photos/kelebekler.jpg',
        'location' => 'Fethiye, Muğla'
    ],
    [
        'title_en' => 'Salda Lake',
        'title_tr' => 'Salda Gölü',
        'desc_en' => 'Known as the "Turkish Maldives" with its white sands and turquoise waters. Minimalist and surreal photos.',
        'desc_tr' => '"Türkiye\'nin Maldivleri" olarak bilinen beyaz kumsalları ve turkuaz suları. Minimalist ve sürreal fotoğraflar.',
        'image' => 'https://images.unsplash.com/photo-1629833728636-2330a84d47c2?q=80&w=2070&auto=format&fit=crop', // Keep Unsplash for now
        'location' => 'Burdur (Close to Antalya)'
    ],
    [
        'title_en' => 'Land of Legends',
        'title_tr' => 'The Land of Legends',
        'desc_en' => 'A Disneyland-like theme park with a stunning castle and light shows. Great for vibrant night photography.',
        'desc_tr' => 'Etkileyici şatosu ve ışık şovlarıyla Disneyland benzeri bir tema parkı. Canlı gece fotoğrafları için harika.',
        'image' => 'assets/photos/landoflegends.jpg',
        'location' => 'Belek, Antalya'
    ],
     [
        'title_en' => 'Saklıkent Canyon',
        'title_tr' => 'Saklıkent Kanyonu',
        'desc_en' => 'One of the deepest canyons in the world. Dramatic cliffs and icy blue water.',
        'desc_tr' => 'Dünyanın en derin kanyonlarından biri. Dramatik kayalıklar ve buz gibi mavi sular.',
        'image' => 'assets/photos/saklikent.jpg',
        'location' => 'Fethiye - Kaş Sınırı'
    ],
    [
        'title_en' => 'Bodrum Castle & Marina',
        'title_tr' => 'Bodrum Kalesi & Marina',
        'desc_en' => 'Medieval castle views mixed with luxury yachts. The sunset here is magical.',
        'desc_tr' => 'Ortaçağ kalesi ve lüks yat manzaraları. Burada gün batımı büyüleyicidir.',
        'image' => 'https://images.unsplash.com/photo-1598345710609-b68cba569b9e?q=80&w=2070&auto=format&fit=crop', // Keep Unsplash for now
        'location' => 'Bodrum, Muğla'
    ],
    [
        'title_en' => 'Myra Ancient City',
        'title_tr' => 'Myra Antik Kenti',
        'desc_en' => 'Lycian rock tombs carved into the cliffs. A unique historical background for portraits.',
        'desc_tr' => 'Kayalıklara oyulmuş Likya kaya mezarları. Portreler için eşsiz bir tarihi arka plan.',
        'image' => 'https://images.unsplash.com/photo-1604928151249-144426b38c03?q=80&w=2129&auto=format&fit=crop', // Keep Unsplash for now
        'location' => 'Demre, Antalya'
    ],
    [
        'title_en' => 'Patara Sand Dunes',
        'title_tr' => 'Patara Kum Tepeleri',
        'desc_en' => 'Endless sand dunes that look like a desert, especially at sunset. Very cinematic.',
        'desc_tr' => 'Özellikle gün batımında çölü andıran uçsuz bucaksız kum tepeleri. Çok sinematik.',
        'image' => 'assets/photos/patara.jpg',
        'location' => 'Kaş, Antalya'
    ]
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Photo Spots | Kalkan Social' : 'Fotoğraf Noktaları | Kalkan Social'; ?></title>
    <meta name="description" content="<?php echo $lang == 'en' ? 'Best Instagram photo spots in Antalya & Muğla' : 'Antalya ve Muğla\'daki en iyi Instagram fotoğraf noktaları'; ?>">
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-6 pt-32 pb-12">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-pink-500 to-violet-500 mb-6 shadow-lg shadow-pink-500/30">
                <i class="fas fa-camera text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl md:text-5xl font-black mb-4 bg-clip-text text-transparent bg-gradient-to-r from-pink-600 to-violet-600 p-2">
                <?php echo $lang == 'en' ? 'Best Photo Spots' : 'En İyi Fotoğraf Noktaları'; ?>
            </h1>
            <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                <?php echo $lang == 'en' ? 'Discover the most instagrammable places in Antalya and Muğla. Perfect views for your feed.' : 'Antalya ve Muğla\'nın en fotojenik noktalarını keşfedin. Profiliniz için mükemmel manzaralar.'; ?>
            </p>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($spots as $index => $spot): ?>
            <div class="group bg-white dark:bg-slate-800 rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-700 shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                
                <!-- Image -->
                <div class="h-72 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent z-10 opacity-60 group-hover:opacity-40 transition-opacity duration-500"></div>
                    <img src="<?php echo $spot['image']; ?>" alt="<?php echo $lang == 'en' ? $spot['title_en'] : $spot['title_tr']; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                    
                    <div class="absolute top-4 right-4 z-20">
                        <span class="bg-white/20 backdrop-blur-md text-white px-3 py-1 rounded-full text-xs font-bold border border-white/20 uppercase tracking-widest flex items-center gap-1">
                            <i class="fas fa-map-marker-alt"></i> <?php echo $spot['location']; ?>
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-8 relative">
                    <!-- Decor -->
                    <div class="absolute -top-6 right-8 w-12 h-12 bg-pink-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-pink-500/30 transform rotate-12 group-hover:rotate-0 transition-all duration-300 z-20">
                        <i class="fas fa-camera text-lg"></i>
                    </div>

                    <h3 class="text-2xl font-bold mb-3 text-slate-800 dark:text-white leading-tight group-hover:text-pink-500 transition-colors">
                        <?php echo $lang == 'en' ? $spot['title_en'] : $spot['title_tr']; ?>
                    </h3>
                    
                    <p class="text-slate-500 dark:text-slate-400 leading-relaxed mb-6">
                        <?php echo $lang == 'en' ? $spot['desc_en'] : $spot['desc_tr']; ?>
                    </p>

                    <div class="pt-6 border-t border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                            #KalkanSocial
                        </span>
                        <button onclick="window.open('https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($spot['location'] . ' ' . $spot['title_en']); ?>', '_blank')" class="text-sm font-bold text-pink-500 hover:text-pink-600 transition-colors flex items-center gap-2">
                            <?php echo $lang == 'en' ? 'View on Map' : 'Haritada Gör'; ?> <i class="fas fa-location-arrow"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

</body>
</html>
