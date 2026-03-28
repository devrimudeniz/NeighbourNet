<?php
require_once 'includes/bootstrap.php';

// All activities as structured data
$activities = [
    // KALKAN
    ['id'=>'k1','name'=>'Rooftop Dining','name_tr'=>'Teras Restoranları','loc'=>'kalkan','cat'=>'food','emoji'=>'🌅','tip'=>'Book sunset dinner','tip_tr'=>'Gün batımında rezervasyon yapın','desc'=>'Kalkan is famous for its rooftop restaurants. Watch the bay turn from gold to twinkling night lights.','desc_tr'=>'Kalkan teraslarıyla meşhurdur. Körfezin altından parlayan gece ışıklarına dönüşünü izleyin.','map'=>'Kalkan+Old+Town+Restaurants'],
    ['id'=>'k2','name'=>'Kaputaş Beach','name_tr'=>'Kaputaş Plajı','loc'=>'kalkan','cat'=>'beach','emoji'=>'🌊','tip'=>'Go before 10 AM','tip_tr'=>'Sabah 10\'dan önce gidin','desc'=>'187 stairs down to paradise. The turquoise color is unreal! Go early to find parking.','desc_tr'=>'187 basamağın altında cennet. Turkuaz rengi gerçek dışı! Park yeri bulmak için erken gidin.','map'=>'Kaputas+Beach'],
    ['id'=>'k3','name'=>'Beach Clubs','name_tr'=>'Beach Club\'lar','loc'=>'kalkan','cat'=>'beach','emoji'=>'🏖️','tip'=>'Platform style, no sand','tip_tr'=>'Platform tarzı, kum yok','desc'=>'Crystal clear water, waiter service to your sunbed, and pure relaxation by the sea.','desc_tr'=>'Kristal berraklığında su, şezlongunuza servis ve deniz kenarında saf rahatlama.','map'=>'Kalkan+Beach+Club'],
    ['id'=>'k4','name'=>'Boat Trip','name_tr'=>'Tekne Turu','loc'=>'kalkan','cat'=>'sea','emoji'=>'⛵','tip'=>'Lunch included','tip_tr'=>'Öğle yemeği dahil','desc'=>'Visit Snake Island, Mouse Island, and hidden coves. A classic Kalkan day out.','desc_tr'=>'Yılan Adası, Fare Adası ve gizli koyları ziyaret edin. Klasik bir Kalkan günü.','map'=>'Kalkan+Marina'],
    ['id'=>'k5','name'=>'Old Town','name_tr'=>'Eski Şehir','loc'=>'kalkan','cat'=>'shopping','emoji'=>'🛍️','tip'=>'Open until late','tip_tr'=>'Gece geç saatlere kadar açık','desc'=>'Get lost in narrow streets. Quality boutiques, handmade ceramics, and spices.','desc_tr'=>'Dar sokaklarda kaybolun. Kaliteli butikler, el yapımı seramikler ve baharatlar.','map'=>'Kalkan+Old+Town'],
    ['id'=>'k6','name'=>'Patara Beach','name_tr'=>'Patara Plajı','loc'=>'kalkan','cat'=>'beach','emoji'=>'🏛️','tip'=>'18km longest beach','tip_tr'=>'18km en uzun plaj','desc'=>'Turkey\'s longest beach. Birthplace of St. Nicholas. Wild, sandy, home to Caretta Caretta turtles.','desc_tr'=>'Türkiye\'nin en uzun plajı. St. Nicholas\'ın doğum yeri. Caretta Caretta yuvası.','map'=>'Patara+Beach'],
    ['id'=>'k7','name'=>'İslamlar Village','name_tr'=>'İslamlar Köyü','loc'=>'kalkan','cat'=>'food','emoji'=>'🌲','tip'=>'15 min drive, cool air','tip_tr'=>'15 dk mesafe, serin hava','desc'=>'Escape the heat! Fresh trout farms, cooler mountain air, and traditional Turkish breakfasts.','desc_tr'=>'Sıcaktan kaçış! Taze alabalık, serin dağ havası ve geleneksel kahvaltılar.','map'=>'Islamlar+Kalkan'],
    ['id'=>'k8','name'=>'Thursday Market','name_tr'=>'Perşembe Pazarı','loc'=>'kalkan','cat'=>'shopping','emoji'=>'🥬','tip'=>'Every Thursday','tip_tr'=>'Her Perşembe','desc'=>'Fresh produce, textiles, spices. The social event of the week. Practice your bargaining!','desc_tr'=>'Taze ürünler, tekstil, baharat. Haftanın sosyal etkinliği. Pazarlık yapın!','map'=>'Kalkan+Market'],
    ['id'=>'k9','name'=>'Xanthos','name_tr'=>'Xanthos Antik Kenti','loc'=>'kalkan','cat'=>'history','emoji'=>'⚱️','tip'=>'UNESCO Heritage','tip_tr'=>'UNESCO Mirası','desc'=>'Capital of ancient Lycia. Roman amphitheater and pillar tombs. UNESCO World Heritage Site.','desc_tr'=>'Antik Likya\'nın başkenti. Roma amfitiyatrosu ve sütun mezarları. UNESCO Dünya Mirası.','map'=>'Xanthos+Ancient+City'],
    ['id'=>'k10','name'=>'Kalamar Bay','name_tr'=>'Kalamar Koyu','loc'=>'kalkan','cat'=>'beach','emoji'=>'🤿','tip'=>'Great snorkeling','tip_tr'=>'Harika şnorkel','desc'=>'Quieter than the town beaches. Perfect for snorkeling and families. Calm, clear water.','desc_tr'=>'Kasaba plajlarından daha sakin. Şnorkel ve aileler için mükemmel.','map'=>'Kalamar+Bay+Kalkan'],
    ['id'=>'k11','name'=>'Kalkan Marina','name_tr'=>'Kalkan Marina','loc'=>'kalkan','cat'=>'sea','emoji'=>'⚓','tip'=>'Sunset spot','tip_tr'=>'Gün batımı noktası','desc'=>'Walk along the waterfront. Watch the yachts. Great cafes and sunset spots.','desc_tr'=>'Sahil boyunca yürüyün. Yatları izleyin. Harika kafeler ve gün batımı noktaları.','map'=>'Kalkan+Marina'],
    ['id'=>'k12','name'=>'Turkish Bath','name_tr'=>'Hamam','loc'=>'kalkan','cat'=>'activity','emoji'=>'🧖','tip'=>'Very relaxing','tip_tr'=>'Çok rahatlatıcı','desc'=>'Traditional steam bath and massage. Perfect after a long day of exploring.','desc_tr'=>'Geleneksel buhar banyosu ve masajı. Uzun bir geziden sonra mükemmel.','map'=>'Kalkan+Hamam'],
    ['id'=>'k13','name'=>'Bezirgan Hike','name_tr'=>'Bezirgan Yürüyüşü','loc'=>'kalkan','cat'=>'nature','emoji'=>'🥾','tip'=>'Panoramic views','tip_tr'=>'Panoramik manzara','desc'=>'Picturesque mountain village trail. Ancient ruins along the way. Stunning panoramic bay views.','desc_tr'=>'Pitoresk dağ köyü yolu. Yol boyunca antik kalıntılar. Muhteşem panoramik manzara.','map'=>'Bezirgan+Kalkan'],
    ['id'=>'k14','name'=>'Letoon','name_tr'=>'Letoon Tapınağı','loc'=>'kalkan','cat'=>'history','emoji'=>'🏺','tip'=>'Free entry!','tip_tr'=>'Giriş ücretsiz!','desc'=>'Ancient religious center dedicated to Leto. Three beautiful temples and mosaic floors.','desc_tr'=>'Leto\'ya adanmış antik dini merkez. Üç muhteşem tapınak ve mozaik zeminler.','map'=>'Letoon+Antalya'],

    // KAŞ
    ['id'=>'s1','name'=>'Scuba Diving','name_tr'=>'Tüplü Dalış','loc'=>'kas','cat'=>'sea','emoji'=>'🤿','tip'=>'Turkey\'s dive capital','tip_tr'=>'Türkiye\'nin dalış başkenti','desc'=>'See turtles, sunken planes, and ancient amphoras. Try a "Discovery Dive" even as a beginner.','desc_tr'=>'Kaplumbağaları, batık uçakları ve antik amforaları görün. Yeni başlayanlar için deneme dalışı.','map'=>'Kas+Diving+Center'],
    ['id'=>'s2','name'=>'Jazz & Nightlife','name_tr'=>'Caz & Gece Hayatı','loc'=>'kas','cat'=>'nightlife','emoji'=>'🎷','tip'=>'Echo Bar is legendary','tip_tr'=>'Echo Bar efsanedir','desc'=>'Nightlife here is live music. Echo Bar and Mavi Bar are legendary. Grab a beer and sit in the square.','desc_tr'=>'Burada gece hayatı canlı müziktir. Echo Bar ve Mavi Bar efsanedir. Meydanda oturun.','map'=>'Kas+Bars'],
    ['id'=>'s3','name'=>'Uzun Çarşı','name_tr'=>'Uzun Çarşı','loc'=>'kas','cat'=>'shopping','emoji'=>'🏮','tip'=>'Most photogenic street','tip_tr'=>'En fotojenik sokak','desc'=>'Turkey\'s most photogenic street. Bougainvillea-covered old wooden houses. Perfect for evening strolls.','desc_tr'=>'Türkiye\'nin en fotojenik sokağı. Begonvillerle kaplı eski ahşap evler. Akşam yürüyüşleri için.','map'=>'Uzun+Carsi+Kas'],
    ['id'=>'s4','name'=>'Antiphellos Theater','name_tr'=>'Antiphellos Tiyatrosu','loc'=>'kas','cat'=>'history','emoji'=>'🏛️','tip'=>'Free, sunset views','tip_tr'=>'Ücretsiz, gün batımı','desc'=>'Ancient Greek theater overlooking the sea. Go at sunset. Free, historic, breathtaking.','desc_tr'=>'Denize nazır antik Yunan tiyatrosu. Gün batımında gidin. Bedava, tarihi, nefes kesici.','map'=>'Antiphellos+Theater+Kas'],
    ['id'=>'s5','name'=>'Big Pebble Beach','name_tr'=>'Büyük Çakıl Plajı','loc'=>'kas','cat'=>'beach','emoji'=>'🌊','tip'=>'Cold freshwater springs','tip_tr'=>'Soğuk tatlı su kaynakları','desc'=>'Cold freshwater springs mix with the sea. Refreshing! Great restaurants right on the pebbles.','desc_tr'=>'Soğuk tatlı su kaynakları denizle karışır. Serinletici ve taşların üzerinde harika restoranlar.','map'=>'Buyuk+Cakil+Plaji+Kas'],
    ['id'=>'s6','name'=>'Meis Island','name_tr'=>'Meis Adası','loc'=>'kas','cat'=>'sea','emoji'=>'🇬🇷','tip'=>'20 min ferry to Greece','tip_tr'=>'20 dk feribot Yunanistan','desc'=>'Greece by ferry! Bring your passport. Duty-free shopping, pork gyros, and a totally different vibe.','desc_tr'=>'Yunanistan\'a 20 dk feribot! Pasaportunuzu alın. Duty-free alışveriş ve farklı atmosfer.','map'=>'Meis+Island+Kastellorizo'],
    ['id'=>'s7','name'=>'Kekova Sunken City','name_tr'=>'Kekova Batık Şehir','loc'=>'kas','cat'=>'history','emoji'=>'🏺','tip'=>'See ruins underwater','tip_tr'=>'Su altında kalıntılar','desc'=>'Boat tour over sunken Lycian ruins. See ancient stairs and walls through crystal clear water.','desc_tr'=>'Batık Likya kalıntılarına tekne turu. Berrak suda antik merdivenler ve duvarları görün.','map'=>'Kekova+Sunken+City'],
    ['id'=>'s8','name'=>'Simena Castle','name_tr'=>'Simena Kalesi','loc'=>'kas','cat'=>'history','emoji'=>'🏰','tip'=>'Boat access only','tip_tr'=>'Sadece tekneyle','desc'=>'Medieval castle reachable only by boat. Stunning views of the coastline. Tiny village with no roads.','desc_tr'=>'Sadece tekneyle ulaşılan Ortaçağ kalesi. Muhteşem kıyı manzarası.','map'=>'Simena+Castle'],
    ['id'=>'s9','name'=>'Lycian Way','name_tr'=>'Likya Yolu','loc'=>'kas','cat'=>'nature','emoji'=>'🥾','tip'=>'World-famous 540km trail','tip_tr'=>'Dünyaca ünlü 540km parkur','desc'=>'World-famous 540km trail passes through Kaş. Do day sections for coastal views and ancient ruins.','desc_tr'=>'Dünyaca ünlü 540km parkur Kaş\'tan geçer. Günlük bölümler yapın.','map'=>'Lycian+Way+Kas'],
    ['id'=>'s10','name'=>'Liman Square','name_tr'=>'Liman Meydanı','loc'=>'kas','cat'=>'food','emoji'=>'☕','tip'=>'Heart of Kaş','tip_tr'=>'Kaş\'ın kalbi','desc'=>'The heart of Kaş. Watch the boats, sip Turkish tea, people-watch. Perfect atmosphere any time.','desc_tr'=>'Kaş\'ın kalbi. Tekneleri izleyin, Türk çayı için. Her saatte mükemmel atmosfer.','map'=>'Liman+Square+Kas'],
    ['id'=>'s11','name'=>'Friday Market','name_tr'=>'Cuma Pazarı','loc'=>'kas','cat'=>'shopping','emoji'=>'🍅','tip'=>'Every Friday','tip_tr'=>'Her Cuma','desc'=>'Fresh produce, local cheese, olives, and handmade goods. Very authentic experience.','desc_tr'=>'Taze ürünler, yerel peynir, zeytin ve el yapımı ürünler. Çok otantik deneyim.','map'=>'Kas+Market'],
    ['id'=>'s12','name'=>'Snorkeling Tours','name_tr'=>'Şnorkel Turları','loc'=>'kas','cat'=>'sea','emoji'=>'🐠','tip'=>'Full day boat tour','tip_tr'=>'Tam gün tekne turu','desc'=>'Hidden coves and caves with snorkeling stops. Full-day boat tours even for non-divers.','desc_tr'=>'Gizli koylar ve mağaralarda şnorkel durakları. Tam günlük tekne turları.','map'=>'Kas+Snorkeling+Tour'],

    // FETHİYE
    ['id'=>'f1','name'=>'Paragliding','name_tr'=>'Yamaç Paraşütü','loc'=>'fethiye','cat'=>'activity','emoji'=>'🪂','tip'=>'World-famous Babadağ','tip_tr'=>'Dünyaca ünlü Babadağ','desc'=>'Jump from 2000m above Ölüdeniz. One of the best places in the world to fly. Bucket list!','desc_tr'=>'Ölüdeniz üzerinde 2000m\'den atlayın. Dünyada uçmak için en iyi yerlerden biri!','map'=>'Babadag+Paragliding+Fethiye'],
    ['id'=>'f2','name'=>'Ölüdeniz Lagoon','name_tr'=>'Ölüdeniz Lagünü','loc'=>'fethiye','cat'=>'beach','emoji'=>'💧','tip'=>'Iconic postcard view','tip_tr'=>'İkonik kartpostal','desc'=>'The iconic turquoise lagoon. Protected nature reserve. Perfect for swimming and sunbathing.','desc_tr'=>'İkonik turkuaz lagün. Korunan doğa rezervi. Yüzme ve güneşlenme için mükemmel.','map'=>'Oludeniz+Blue+Lagoon'],
    ['id'=>'f3','name'=>'Kayaköy Ghost Village','name_tr'=>'Kayaköy Hayalet Köy','loc'=>'fethiye','cat'=>'history','emoji'=>'👻','tip'=>'500+ abandoned houses','tip_tr'=>'500+ terk edilmiş ev','desc'=>'Abandoned Greek village. Eerie, beautiful, historic. 500+ ruined houses. Great for sunset walks.','desc_tr'=>'Terk edilmiş Rum köyü. Ürkütücü, güzel, tarihi. 500+ harabe ev.','map'=>'Kayakoy+Ghost+Village'],
    ['id'=>'f4','name'=>'Saklıkent Gorge','name_tr'=>'Saklıkent Kanyonu','loc'=>'fethiye','cat'=>'nature','emoji'=>'🏔️','tip'=>'Deepest canyon in Turkey','tip_tr'=>'Türkiye\'nin en derin kanyonu','desc'=>'Turkey\'s deepest canyon. Walk through ice-cold water. Eat trout by the river. Real adventure.','desc_tr'=>'Türkiye\'nin en derin kanyonu. Buz gibi suda yürüyün. Nehir kenarında alabalık yiyin.','map'=>'Saklikent+Gorge'],
    ['id'=>'f5','name'=>'Butterfly Valley','name_tr'=>'Kelebekler Vadisi','loc'=>'fethiye','cat'=>'nature','emoji'=>'🦋','tip'=>'Boat access only','tip_tr'=>'Sadece tekneyle','desc'=>'Reachable only by boat. Waterfalls and butterflies. Simple, raw nature. Hippie paradise.','desc_tr'=>'Sadece tekneyle ulaşılır. Şelaleler ve kelebekler. Basit, ham doğa.','map'=>'Butterfly+Valley+Fethiye'],
    ['id'=>'f6','name'=>'Fish Market','name_tr'=>'Balık Pazarı','loc'=>'fethiye','cat'=>'food','emoji'=>'🐟','tip'=>'Pick fish, any restaurant cooks it','tip_tr'=>'Balığı seç, restoran pişirsin','desc'=>'Buy fresh fish from the stalls, any restaurant will cook it for you. Lively atmosphere and tradition.','desc_tr'=>'Tezgahlardan taze balık alın, herhangi bir restoran pişirsin. Canlı atmosfer.','map'=>'Fethiye+Fish+Market'],
    ['id'=>'f7','name'=>'Amyntas Rock Tombs','name_tr'=>'Amyntas Kaya Mezarları','loc'=>'fethiye','cat'=>'history','emoji'=>'🏛️','tip'=>'Sunset views unmatched','tip_tr'=>'Gün batımı manzarası','desc'=>'Carved into the mountain. Stairs are tiring but sunset views over Fethiye are unmatched.','desc_tr'=>'Dağa oyulmuş. Merdivenler yorucu ama Fethiye üzerindeki gün batımı manzarası rakipsiz.','map'=>'Amyntas+Rock+Tombs'],
    ['id'=>'f8','name'=>'12 Islands Tour','name_tr'=>'12 Adalar Turu','loc'=>'fethiye','cat'=>'sea','emoji'=>'🛥️','tip'=>'Full day, lunch included','tip_tr'=>'Tam gün, öğle yemeği dahil','desc'=>'Full-day boat tour visiting coves and islands. Swimming stops and lunch included. Classic Fethiye.','desc_tr'=>'Tam günlük tekne turu. Yüzme durakları, öğle yemeği dahil. Klasik Fethiye.','map'=>'Fethiye+12+Islands+Tour'],
    ['id'=>'f9','name'=>'Paspatur Old Town','name_tr'=>'Paspatur Çarşısı','loc'=>'fethiye','cat'=>'shopping','emoji'=>'🧿','tip'=>'Turkish delight, carpets','tip_tr'=>'Lokum, halı, mücevher','desc'=>'Charming bazaar with narrow streets. Turkish delight, carpets, jewelry. Atmospheric evening strolls.','desc_tr'=>'Dar sokakları olan büyüleyici çarşı. Lokum, halı, mücevher. Akşam gezintileri.','map'=>'Paspatur+Fethiye'],
    ['id'=>'f10','name'=>'Çalış Beach','name_tr'=>'Çalış Plajı','loc'=>'fethiye','cat'=>'beach','emoji'=>'🌅','tip'=>'Best sunset spot','tip_tr'=>'En iyi gün batımı noktası','desc'=>'Long sandy beach perfect for sunset. Beach bars and restaurants. Calmer than Ölüdeniz.','desc_tr'=>'Gün batımı için mükemmel uzun kumsal. Beach barlar. Ölüdeniz\'den daha sakin.','map'=>'Calis+Beach+Fethiye'],
    ['id'=>'f11','name'=>'Dalyan & İztuzu','name_tr'=>'Dalyan & İztuzu','loc'=>'fethiye','cat'=>'nature','emoji'=>'🚤','tip'=>'River boat + turtle beach','tip_tr'=>'Nehir turu + kaplumbağa plajı','desc'=>'River boat to turtle beach. See Lycian tombs from the river. Mud bath on the way!','desc_tr'=>'Nehirden kaplumbağa plajına tekne turu. Likya mezarlarını görün. Çamur banyosu!','map'=>'Dalyan+Iztuzu'],
    ['id'=>'f12','name'=>'Tuesday Market','name_tr'=>'Salı Pazarı','loc'=>'fethiye','cat'=>'shopping','emoji'=>'🍊','tip'=>'Huge, come early','tip_tr'=>'Dev pazar, erken gelin','desc'=>'Massive weekly market. Fresh produce, spices, textiles, everything! Come early and bring cash.','desc_tr'=>'Devasa haftalık pazar. Taze ürünler, baharatlar, tekstil, her şey! Erken gelin.','map'=>'Fethiye+Tuesday+Market'],
];

$categories = [
    'all'      => ['label_en'=>'All',        'label_tr'=>'Tümü',        'icon'=>'fa-compass',        'color'=>'#64748b','bg'=>'#f1f5f9','bg_dark'=>'#1e293b'],
    'beach'    => ['label_en'=>'Beaches',    'label_tr'=>'Plajlar',     'icon'=>'fa-umbrella-beach',  'color'=>'#0ea5e9','bg'=>'#f0f9ff','bg_dark'=>'#0c4a6e'],
    'sea'      => ['label_en'=>'Boat & Sea', 'label_tr'=>'Deniz & Tekne','icon'=>'fa-ship',           'color'=>'#2563eb','bg'=>'#eff6ff','bg_dark'=>'#1e3a5f'],
    'food'     => ['label_en'=>'Food',       'label_tr'=>'Yeme & İçme', 'icon'=>'fa-utensils',        'color'=>'#f97316','bg'=>'#fff7ed','bg_dark'=>'#431407'],
    'history'  => ['label_en'=>'History',    'label_tr'=>'Tarih',       'icon'=>'fa-landmark',        'color'=>'#a16207','bg'=>'#fefce8','bg_dark'=>'#422006'],
    'nature'   => ['label_en'=>'Nature',     'label_tr'=>'Doğa',        'icon'=>'fa-leaf',            'color'=>'#16a34a','bg'=>'#f0fdf4','bg_dark'=>'#052e16'],
    'shopping' => ['label_en'=>'Shopping',   'label_tr'=>'Alışveriş',   'icon'=>'fa-shopping-bag',    'color'=>'#e11d48','bg'=>'#fff1f2','bg_dark'=>'#4c0519'],
    'activity' => ['label_en'=>'Activities', 'label_tr'=>'Aktiviteler', 'icon'=>'fa-hiking',          'color'=>'#7c3aed','bg'=>'#f5f3ff','bg_dark'=>'#2e1065'],
    'nightlife'=> ['label_en'=>'Nightlife',  'label_tr'=>'Gece Hayatı', 'icon'=>'fa-music',           'color'=>'#c026d3','bg'=>'#fdf4ff','bg_dark'=>'#4a044e'],
];

// Dark mode detection
$is_dark = (defined('CURRENT_THEME') && CURRENT_THEME == 'dark');

$locations = [
    'all'     => ['label'=>$lang=='en'?'All':'Tümü'],
    'kalkan'  => ['label'=>'Kalkan'],
    'kas'     => ['label'=>'Kaş'],
    'fethiye' => ['label'=>'Fethiye'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'What To Do' : 'Ne Yapmalı'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
        .act-card { transition: transform 0.15s, box-shadow 0.15s; cursor: pointer; }
        .act-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.07); }
        .act-card:active { transform: scale(0.98); }
        .act-card .act-detail { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .act-card.open .act-detail { max-height: 300px; }
        .act-card.open .act-chevron { transform: rotate(180deg); }
        .expat-card .expat-detail { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .expat-card.exp-open .expat-detail { max-height: 400px; }
        .expat-card.exp-open .expat-chevron { transform: rotate(180deg); }
        .expat-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .loc-tab, .cat-chip { transition: all 0.15s; }
        .loc-tab:hover, .cat-chip:hover { opacity: 0.85; }
        .loc-tab.active { background: #0f172a !important; color: #fff !important; }
        .dark .loc-tab.active { background: #e2e8f0 !important; color: #0f172a !important; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <div class="max-w-2xl mx-auto px-4 pt-24 pb-8">

        <!-- Header -->
        <div style="margin-bottom:20px;">
            <h1 style="font-size:24px;font-weight:900;margin:0;color:<?php echo $is_dark ? '#f1f5f9' : '#0f172a'; ?>;"><?php echo $lang == 'en' ? 'What To Do' : 'Ne Yapmalı?'; ?></h1>
            <p style="font-size:13px;color:#94a3b8;margin:4px 0 0;"><?php echo count($activities); ?> <?php echo $lang == 'en' ? 'things to discover around Kalkan' : 'keşfedilecek yer ve aktivite'; ?></p>
        </div>

        <!-- Location Tabs -->
        <div style="display:flex;gap:4px;margin-bottom:14px;background:<?php echo $is_dark ? '#1e293b' : '#f1f5f9'; ?>;border-radius:12px;padding:4px;">
            <?php foreach($locations as $lk => $lv): ?>
            <button onclick="setLocation('<?php echo $lk; ?>')" class="loc-tab <?php echo $lk==='all'?'active':''; ?>" data-loc="<?php echo $lk; ?>"
                style="flex:1;padding:9px;border-radius:10px;border:none;font-size:13px;font-weight:700;cursor:pointer;text-align:center;color:<?php echo $is_dark ? '#cbd5e1' : 'inherit'; ?>;background:transparent;">
                <?php echo $lv['label']; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Category Chips -->
        <div style="display:flex;gap:6px;margin-bottom:20px;overflow-x:auto;padding-bottom:2px;" class="hide-scrollbar">
            <?php foreach($categories as $ck => $cv): 
                $is_active = ($ck === 'all');
                $chip_bg = $is_active ? ($is_dark ? $cv['bg_dark'] : $cv['bg']) : 'transparent';
            ?>
            <button onclick="setCategory('<?php echo $ck; ?>')" class="cat-chip" data-cat="<?php echo $ck; ?>" data-color="<?php echo $cv['color']; ?>" data-bg="<?php echo $is_dark ? $cv['bg_dark'] : $cv['bg']; ?>"
                style="padding:7px 13px;border-radius:10px;font-size:12px;font-weight:<?php echo $is_active ? '800' : '600'; ?>;border:none;cursor:pointer;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:5px;<?php echo $is_active ? 'background:'.$chip_bg.';color:'.$cv['color'].';' : 'background:transparent;color:'.($is_dark ? '#94a3b8' : '#64748b').';'; ?>">
                <i class="fas <?php echo $cv['icon']; ?>" style="font-size:10px;"></i>
                <?php echo $lang == 'en' ? $cv['label_en'] : $cv['label_tr']; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Results count -->
        <div id="results-count" style="font-size:11px;color:#94a3b8;font-weight:700;margin-bottom:12px;padding-left:2px;"></div>

        <!-- Activities Grid -->
        <div id="activities-list" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <?php foreach($activities as $a): 
                $name = $lang == 'en' ? $a['name'] : $a['name_tr'];
                $tip = $lang == 'en' ? $a['tip'] : $a['tip_tr'];
                $desc = $lang == 'en' ? $a['desc'] : $a['desc_tr'];
                $loc_label = $locations[$a['loc']]['label'] ?? $a['loc'];
                $cat_info = $categories[$a['cat']] ?? $categories['activity'];
                $cat_label = $lang == 'en' ? $cat_info['label_en'] : $cat_info['label_tr'];
            ?>
            <?php 
                $card_bg = $is_dark ? $cat_info['bg_dark'] : $cat_info['bg'];
                $card_border = $is_dark ? 'rgba(255,255,255,0.08)' : $cat_info['bg'];
                $card_title_color = $is_dark ? '#f1f5f9' : '#0f172a';
                $card_desc_color = $is_dark ? '#cbd5e1' : '#475569';
                $card_detail_border = $is_dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
                $dir_btn_bg = $is_dark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.06)';
                $dir_btn_color = $is_dark ? '#e2e8f0' : '#334155';
            ?>
            <div class="act-card" data-loc="<?php echo $a['loc']; ?>" data-cat="<?php echo $a['cat']; ?>" onclick="toggleCard(this)">
                <div style="background:<?php echo $card_bg; ?>;border:1px solid <?php echo $card_border; ?>;border-radius:16px;padding:16px;height:100%;position:relative;overflow:hidden;">
                    
                    <!-- Top: Emoji + Category -->
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;">
                        <div style="font-size:32px;line-height:1;"><?php echo $a['emoji']; ?></div>
                        <i class="fas fa-chevron-down act-chevron" style="font-size:9px;color:<?php echo $cat_info['color']; ?>;opacity:0.4;transition:transform 0.2s;margin-top:6px;"></i>
                    </div>

                    <!-- Name -->
                    <h3 style="font-size:14px;font-weight:800;color:<?php echo $card_title_color; ?>;margin:0 0 4px;line-height:1.3;"><?php echo htmlspecialchars($name); ?></h3>
                    
                    <!-- Tip -->
                    <p style="font-size:11px;color:<?php echo $cat_info['color']; ?>;font-weight:600;margin:0 0 4px;opacity:0.8;"><?php echo htmlspecialchars($tip); ?></p>

                    <!-- Location badge -->
                    <div style="display:flex;align-items:center;gap:4px;margin-top:6px;">
                        <span style="font-size:10px;font-weight:700;color:#94a3b8;"><i class="fas fa-map-pin" style="margin-right:2px;font-size:8px;"></i><?php echo $loc_label; ?></span>
                        <span style="padding:2px 6px;border-radius:5px;font-size:9px;font-weight:700;background:<?php echo $cat_info['color']; ?>;color:#fff;"><?php echo $cat_label; ?></span>
                    </div>

                    <!-- Expandable Detail -->
                    <div class="act-detail">
                        <div style="margin-top:12px;padding-top:10px;border-top:1px solid <?php echo $card_detail_border; ?>;">
                            <p style="font-size:12px;color:<?php echo $card_desc_color; ?>;line-height:1.6;margin:0 0 10px;"><?php echo htmlspecialchars($desc); ?></p>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="https://maps.google.com/?q=<?php echo $a['map']; ?>" target="_blank" onclick="event.stopPropagation()" style="padding:7px 12px;border-radius:8px;background:<?php echo $cat_info['color']; ?>;color:#fff;font-size:11px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="fas fa-map-marker-alt" style="font-size:9px;"></i> <?php echo $lang == 'en' ? 'Map' : 'Harita'; ?>
                                </a>
                                <a href="https://maps.google.com/maps/dir/?api=1&destination=<?php echo $a['map']; ?>" target="_blank" onclick="event.stopPropagation()" style="padding:7px 12px;border-radius:8px;background:<?php echo $dir_btn_bg; ?>;color:<?php echo $dir_btn_color; ?>;font-size:11px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="fas fa-directions" style="font-size:9px;"></i> <?php echo $lang == 'en' ? 'Directions' : 'Yol Tarifi'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- No results -->
        <div id="no-results" style="display:none;text-align:center;padding:40px 20px;">
            <div style="font-size:32px;margin-bottom:8px;">🔍</div>
            <p style="font-size:14px;color:#94a3b8;font-weight:600;"><?php echo $lang == 'en' ? 'No activities in this combination.' : 'Bu kombinasyonda aktivite yok.'; ?></p>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- EXPAT ESSENTIALS SECTION -->
        <!-- ═══════════════════════════════════════════ -->
        <?php
        $expat_items = [
            [
                'icon' => 'fa-passport',
                'color' => '#2563eb',
                'bg' => $is_dark ? '#1e3a5f' : '#eff6ff',
                'title_en' => 'Residence Permit',
                'title_tr' => 'İkamet İzni',
                'desc_en' => 'Apply online at e-ikamet.goc.gov.tr. You\'ll need: passport, health insurance, proof of address, biometric photos (50x60mm), and bank statement. Appointment at Antalya Migration Office. Process takes 30-60 days. Cost: ~€50-100.',
                'desc_tr' => 'e-ikamet.goc.gov.tr üzerinden başvurun. Gerekli: pasaport, sağlık sigortası, adres belgesi, biyometrik fotoğraf (50x60mm), banka hesap özeti. Antalya Göç İdaresi\'nde randevu. Süreç 30-60 gün. Ücret: ~€50-100.',
                'tip_en' => 'Apply 60 days before your visa expires',
                'tip_tr' => 'Vizeniz bitmeden 60 gün önce başvurun',
                'link' => 'https://e-ikamet.goc.gov.tr',
                'link_label' => 'e-İkamet Portal',
            ],
            [
                'icon' => 'fa-university',
                'color' => '#16a34a',
                'bg' => $is_dark ? '#052e16' : '#f0fdf4',
                'title_en' => 'Bank Account',
                'title_tr' => 'Banka Hesabı',
                'desc_en' => 'You need a Turkish Tax Number (Vergi Numarası) first — get it free from any Tax Office with your passport. Then visit any bank branch (Ziraat, İş Bankası, Garanti recommended). Bring: passport, tax number, Turkish phone number, proof of address.',
                'desc_tr' => 'Önce Vergi Numarası almanız gerekir — herhangi bir Vergi Dairesi\'nden pasaportla ücretsiz alınır. Ardından bir banka şubesine gidin (Ziraat, İş Bankası, Garanti önerilir). Gerekli: pasaport, vergi numarası, TR telefon, adres belgesi.',
                'tip_en' => 'Ziraat Bank is the easiest for foreigners',
                'tip_tr' => 'Ziraat Bankası yabancılar için en kolay',
                'link' => '',
                'link_label' => '',
            ],
            [
                'icon' => 'fa-heartbeat',
                'color' => '#e11d48',
                'bg' => $is_dark ? '#4c0519' : '#fff1f2',
                'title_en' => 'Health Insurance',
                'title_tr' => 'Sağlık Sigortası',
                'desc_en' => 'Required for residence permit. Options: (1) Private insurance (~€200-500/year, accepted for ikamet), (2) SGK state insurance (~€50/month after 1 year residency). Private clinics in Kalkan for basics; hospitals in Fethiye/Antalya for serious cases.',
                'desc_tr' => 'İkamet izni için gerekli. Seçenekler: (1) Özel sigorta (~€200-500/yıl, ikamet için kabul edilir), (2) SGK devlet sigortası (~€50/ay, 1 yıl ikamet sonrası). Kalkan\'da temel sağlık için özel klinikler; ciddi durumlar için Fethiye/Antalya hastaneleri.',
                'tip_en' => 'Get insurance BEFORE applying for ikamet',
                'tip_tr' => 'İkamet başvurusundan ÖNCE sigorta yaptırın',
                'link' => '',
                'link_label' => '',
            ],
            [
                'icon' => 'fa-file-invoice',
                'color' => '#7c3aed',
                'bg' => $is_dark ? '#2e1065' : '#f5f3ff',
                'title_en' => 'Tax Number (Vergi No)',
                'title_tr' => 'Vergi Numarası',
                'desc_en' => 'Free and takes 10 minutes. Go to any Tax Office (Vergi Dairesi) with your passport. You\'ll get a number on the spot. Needed for: bank account, buying property, car registration, phone registration, and most official processes.',
                'desc_tr' => 'Ücretsiz ve 10 dakika sürer. Herhangi bir Vergi Dairesi\'ne pasaportunuzla gidin. Numara anında verilir. Gerekli: banka hesabı, mülk satın alma, araç tescili, telefon kaydı ve çoğu resmi işlem.',
                'tip_en' => 'Nearest tax office is in Kaş center',
                'tip_tr' => 'En yakın vergi dairesi Kaş merkezde',
                'link' => '',
                'link_label' => '',
            ],
            [
                'icon' => 'fa-car',
                'color' => '#f97316',
                'bg' => $is_dark ? '#431407' : '#fff7ed',
                'title_en' => 'Driving in Turkey',
                'title_tr' => 'Türkiye\'de Araç Kullanma',
                'desc_en' => 'Your home license + IDP (International Driving Permit) works for 6 months. After that, convert to Turkish license at Nüfus Müdürlüğü. You\'ll need: medical report, biometric photos, notarized translation of your license. Speed limits: 50 city / 90 highway / 120 motorway.',
                'desc_tr' => 'Kendi ehliyetiniz + Uluslararası Sürücü Belgesi (IDP) 6 ay geçerli. Sonra Nüfus Müdürlüğü\'nde Türk ehliyetine dönüştürün. Gerekli: sağlık raporu, biyometrik fotoğraf, noter onaylı ehliyet tercümesi. Hız limitleri: 50 şehir / 90 karayolu / 120 otoyol.',
                'tip_en' => 'Get an IDP before you arrive',
                'tip_tr' => 'Gelmeden önce IDP alın',
                'link' => '',
                'link_label' => '',
            ],
            [
                'icon' => 'fa-bolt',
                'color' => '#eab308',
                'bg' => $is_dark ? '#422006' : '#fefce8',
                'title_en' => 'Utilities Setup',
                'title_tr' => 'Altyapı Kurulumu',
                'desc_en' => 'Water: Apply at Kalkan Belediyesi. Electric: CK Enerji office (Fethiye or online). Internet: Türk Telekom, Superonline, or Vodafone — fiber available in central Kalkan. Bring: passport, rental contract (or tapü), tax number. Bills can be auto-paid via bank.',
                'desc_tr' => 'Su: Kalkan Belediyesi\'ne başvurun. Elektrik: CK Enerji ofisi (Fethiye veya online). İnternet: Türk Telekom, Superonline veya Vodafone — merkez Kalkan\'da fiber mevcut. Gerekli: pasaport, kira sözleşmesi (veya tapü), vergi numarası. Faturalar otomatik ödeme yapılabilir.',
                'tip_en' => 'Türk Telekom has best coverage in Kalkan',
                'tip_tr' => 'Kalkan\'da en iyi kapsama Türk Telekom\'da',
                'link' => '',
                'link_label' => '',
            ],
            [
                'icon' => 'fa-mobile-alt',
                'color' => '#0ea5e9',
                'bg' => $is_dark ? '#0c4a6e' : '#f0f9ff',
                'title_en' => 'Turkish SIM Card',
                'title_tr' => 'Türk SIM Kartı',
                'desc_en' => 'Buy a prepaid SIM at any Turkcell, Vodafone, or Türk Telekom shop with your passport. ~₺200-300 for starter pack. Foreign phones must be registered within 120 days (IMEI registration at tax office, ~€50). After registration, your phone works permanently.',
                'desc_tr' => 'Herhangi bir Turkcell, Vodafone veya Türk Telekom mağazasından pasaportla alın. Başlangıç paketi ~₺200-300. Yabancı telefonlar 120 gün içinde kayıt edilmeli (vergi dairesinde IMEI kaydı, ~€50). Kayıt sonrası telefonunuz süresiz çalışır.',
                'tip_en' => 'Turkcell has best rural coverage',
                'tip_tr' => 'Kırsal alanda en iyi kapsama Turkcell\'de',
                'link' => '',
                'link_label' => '',
            ],
            [
                'icon' => 'fa-gavel',
                'color' => '#64748b',
                'bg' => $is_dark ? '#1e293b' : '#f1f5f9',
                'title_en' => 'Notary & Legal',
                'title_tr' => 'Noter & Hukuki İşlemler',
                'desc_en' => 'Nearest notary (Noter) is in Kaş. For property: mandatory use of notary + tapü office. Power of Attorney (Vekalet) for someone to act on your behalf. Apostille: get documents apostilled in your home country first. Sworn translators available in Fethiye/Antalya.',
                'desc_tr' => 'En yakın noter Kaş\'ta. Mülk için: noter + tapü dairesi zorunlu. Vekalet: birinin sizin adınıza işlem yapması için. Apostil: belgeleri kendi ülkenizde apostil ettirin. Fethiye/Antalya\'da yeminli tercümanlar mevcut.',
                'tip_en' => 'Always use a sworn translator for legal docs',
                'tip_tr' => 'Hukuki belgeler için daima yeminli tercüman kullanın',
                'link' => '',
                'link_label' => '',
            ],
        ];
        ?>

        <div style="margin-top:32px;padding-top:28px;border-top:2px solid <?php echo $is_dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)'; ?>;">
            <!-- Section Header -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-suitcase-rolling" style="color:#fff;font-size:16px;"></i>
                </div>
                <div>
                    <h2 style="font-size:20px;font-weight:900;margin:0;color:<?php echo $is_dark ? '#f1f5f9' : '#0f172a'; ?>;"><?php echo $lang == 'en' ? 'Expat Essentials' : 'Göçmen Rehberi'; ?></h2>
                    <p style="font-size:12px;color:#94a3b8;margin:2px 0 0;"><?php echo $lang == 'en' ? 'Everything you need to settle in Kalkan' : 'Kalkan\'a yerleşmek için bilmeniz gereken her şey'; ?></p>
                </div>
            </div>

            <div style="display:grid;gap:12px;margin-top:16px;">
                <?php foreach($expat_items as $ei): 
                    $ei_title = $lang == 'en' ? $ei['title_en'] : $ei['title_tr'];
                    $ei_desc = $lang == 'en' ? $ei['desc_en'] : $ei['desc_tr'];
                    $ei_tip = $lang == 'en' ? $ei['tip_en'] : $ei['tip_tr'];
                ?>
                <div class="expat-card" onclick="this.classList.toggle('exp-open')" style="background:<?php echo $ei['bg']; ?>;border:1px solid <?php echo $is_dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.04)'; ?>;border-radius:14px;padding:16px;cursor:pointer;transition:all 0.15s;">
                    
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;border-radius:10px;background:<?php echo $ei['color']; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas <?php echo $ei['icon']; ?>" style="color:#fff;font-size:16px;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <h3 style="font-size:15px;font-weight:800;color:<?php echo $is_dark ? '#f1f5f9' : '#0f172a'; ?>;margin:0;"><?php echo $ei_title; ?></h3>
                            <p style="font-size:11px;color:<?php echo $ei['color']; ?>;font-weight:600;margin:2px 0 0;">💡 <?php echo $ei_tip; ?></p>
                        </div>
                        <i class="fas fa-chevron-down expat-chevron" style="font-size:10px;color:<?php echo $is_dark ? '#64748b' : '#94a3b8'; ?>;transition:transform 0.2s;flex-shrink:0;"></i>
                    </div>

                    <div class="expat-detail">
                        <div style="margin-top:14px;padding-top:12px;border-top:1px solid <?php echo $is_dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)'; ?>;">
                            <p style="font-size:13px;color:<?php echo $is_dark ? '#cbd5e1' : '#475569'; ?>;line-height:1.7;margin:0;"><?php echo $ei_desc; ?></p>
                            <?php if(!empty($ei['link'])): ?>
                            <a href="<?php echo $ei['link']; ?>" target="_blank" onclick="event.stopPropagation()" style="display:inline-flex;align-items:center;gap:5px;margin-top:10px;padding:8px 14px;border-radius:8px;background:<?php echo $ei['color']; ?>;color:#fff;font-size:12px;font-weight:700;text-decoration:none;">
                                <i class="fas fa-external-link-alt" style="font-size:9px;"></i> <?php echo $ei['link_label']; ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <script>
    var currentLoc = 'all';
    var currentCat = 'all';

    function setLocation(loc) {
        currentLoc = loc;
        document.querySelectorAll('.loc-tab').forEach(function(t) {
            t.classList.toggle('active', t.dataset.loc === loc);
        });
        applyFilters();
    }

    function setCategory(cat) {
        currentCat = cat;
        var inactiveColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
        document.querySelectorAll('.cat-chip').forEach(function(c) {
            if (c.dataset.cat === cat) {
                c.style.background = c.dataset.bg;
                c.style.color = c.dataset.color;
                c.style.fontWeight = '800';
            } else {
                c.style.background = 'transparent';
                c.style.color = inactiveColor;
                c.style.fontWeight = '600';
            }
        });
        applyFilters();
    }

    function applyFilters() {
        var cards = document.querySelectorAll('.act-card');
        var visible = 0;
        cards.forEach(function(card) {
            var matchLoc = (currentLoc === 'all' || card.dataset.loc === currentLoc);
            var matchCat = (currentCat === 'all' || card.dataset.cat === currentCat);
            if (matchLoc && matchCat) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
                card.classList.remove('open');
            }
        });
        document.getElementById('results-count').textContent = visible + ' <?php echo $lang == "en" ? "places to discover" : "keşfedilecek yer"; ?>';
        document.getElementById('no-results').style.display = visible === 0 ? '' : 'none';
    }

    function toggleCard(el) {
        var wasOpen = el.classList.contains('open');
        document.querySelectorAll('.act-card.open').forEach(function(c) { c.classList.remove('open'); });
        if (!wasOpen) el.classList.add('open');
    }

    applyFilters();
    </script>
</body>
</html>
