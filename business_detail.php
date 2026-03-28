<?php
require_once 'includes/bootstrap.php';

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get business details
$stmt = $pdo->prepare("
    SELECT bl.*, u.username as owner_username, u.full_name as owner_name
    FROM business_listings bl
    JOIN users u ON bl.owner_id = u.id
    WHERE bl.id = ?
");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

// Check if business has QR menu (subdomain)
$has_menu = false;
$menu_url = '';
if ($business && !empty($business['subdomain']) && $business['subdomain_status'] === 'approved') {
    $has_menu = true;
    $menu_url = 'https://' . $business['subdomain'] . '.kalkansocial.com';
}

if (!$business) {
    header('Location: directory');
    exit();
}

// Get reviews
$reviews_stmt = $pdo->prepare("
    SELECT br.*, u.username, u.full_name, u.avatar
    FROM business_reviews br
    JOIN users u ON br.user_id = u.id
    WHERE br.business_id = ?
    ORDER BY br.created_at DESC
");
$reviews_stmt->execute([$business_id]);
$reviews = $reviews_stmt->fetchAll();

// Get photos
$photos_stmt = $pdo->prepare("
    SELECT bp.*, u.username
    FROM business_photos bp
    JOIN users u ON bp.user_id = u.id
    WHERE bp.business_id = ?
    ORDER BY bp.created_at DESC
    LIMIT 12
");
$photos_stmt->execute([$business_id]);
$photos = $photos_stmt->fetchAll();

// Get menu photos
$menu_stmt = $pdo->prepare("SELECT * FROM business_menu_images WHERE business_id = ? ORDER BY created_at ASC");
$menu_stmt->execute([$business_id]);
$menu_photos = $menu_stmt->fetchAll();

// Check if user has favorited
$is_favorited = false;
if (isset($_SESSION['user_id'])) {
    $fav_stmt = $pdo->prepare("SELECT id FROM business_favorites WHERE business_id = ? AND user_id = ?");
    $fav_stmt->execute([$business_id, $_SESSION['user_id']]);
    $is_favorited = $fav_stmt->fetch() ? true : false;
}

// Check if user has reviewed
$user_review = null;
if (isset($_SESSION['user_id'])) {
    $rev_stmt = $pdo->prepare("SELECT * FROM business_reviews WHERE business_id = ? AND user_id = ?");
    $rev_stmt->execute([$business_id, $_SESSION['user_id']]);
    $user_review = $rev_stmt->fetch();
}

// Check if current user is business owner (can reply to reviews)
$is_business_owner = isset($_SESSION['user_id']) && $business['owner_id'] == $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business['name']); ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Force light mode
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    </script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-5xl">
    <main class="container mx-auto px-6 pt-32 pb-20 max-w-5xl">
        <!-- Gallery Slider (Swiper) -->
        <?php 
        // Get official gallery photos (owner's photos)
        $gallery_stmt = $pdo->prepare("SELECT photo_url FROM business_photos WHERE business_id = ? AND user_id = ? ORDER BY created_at DESC");
        $gallery_stmt->execute([$business_id, $business['owner_id']]);
        $gallery_photos = $gallery_stmt->fetchAll(PDO::FETCH_COLUMN);

        $slider_photos = [];
        if ($business['cover_photo']) {
            $slider_photos[] = $business['cover_photo'];
        }
        $slider_photos = array_merge($slider_photos, $gallery_photos);
        ?>

        <div class="rounded-2xl overflow-hidden mb-8 relative shadow-2xl">
            <!-- Swiper -->
            <div class="swiper mySwiper h-96">
                <div class="swiper-wrapper">
                    <?php if (count($slider_photos) > 0): ?>
                        <?php foreach($slider_photos as $photo_url): ?>
                        <div class="swiper-slide">
                            <div class="h-full w-full bg-slate-900">
                                <img src="<?php echo $photo_url; ?>" class="w-full h-full object-cover opacity-90" loading="lazy">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="swiper-slide">
                            <div class="w-full h-full flex items-center justify-center text-white text-9xl bg-gradient-to-br from-pink-500 to-violet-500">
                                <?php 
                                $icons = ['restaurant' => '🍽️', 'bar' => '🍹', 'hotel' => '🏨', 'cafe' => '☕', 'activity' => '🎯', 'shop' => '🛍️', 'service' => '🔧', 'health' => '🏥', 'nomad' => '💻'];
                                echo $icons[$business['category']] ?? '🏪';
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($slider_photos) > 1): ?>
                <div class="swiper-button-next text-white/80 hover:text-white"></div>
                <div class="swiper-button-prev text-white/80 hover:text-white"></div>
                <div class="swiper-pagination"></div>
                <?php endif; ?>
            </div>
            
            <!-- Claimed Badge Overlay -->
            <?php if ($business['is_claimed']): ?>
            <div class="absolute top-4 right-4 z-10 bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1 shadow-lg">
                <i class="fas fa-check-circle"></i> <?php echo $lang == 'en' ? 'Claimed' : 'Onaylı'; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
            var swiper = new Swiper(".mySwiper", {
                loop: <?php echo count($slider_photos) > 1 ? 'true' : 'false'; ?>,
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                    dynamicBullets: true,
                },
                autoplay: {
                    delay: 3500,
                    disableOnInteraction: false,
                },
            });
        </script>

        <!-- Business Info -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 mb-6 border border-white/20 dark:border-slate-800/50">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-4xl font-extrabold"><?php echo htmlspecialchars($business['name']); ?></h1>
                        <?php 
                        $can_edit = isset($_SESSION['user_id']) && (
                            $business['owner_id'] == $_SESSION['user_id'] || 
                            (isset($_SESSION['badge']) && in_array($_SESSION['badge'], ['founder', 'moderator']))
                        );
                        if ($can_edit): ?>
                        <a href="edit_business?id=<?php echo $business_id; ?>" 
                           style="background: #3b82f6; color: white; padding: 6px 12px; border-radius: 8px; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                            <i class="fas fa-edit"></i> <?php echo $lang == 'en' ? 'Edit' : 'Düzenle'; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Categories Badges -->
                    <?php 
                    $curr_cats_stmt = $pdo->prepare("SELECT category FROM business_categories WHERE business_id = ?");
                    $curr_cats_stmt->execute([$business_id]);
                    $curr_cats = $curr_cats_stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (empty($curr_cats)) {
                        $curr_cats = [$business['category']]; // Fallback
                    }
                    ?>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <?php foreach($curr_cats as $cat): 
                            $cat_name = $t[$cat] ?? $cat;
                            if ($cat == 'nomad') $cat_name = '💻 ' . $t['nomad'];
                            // Simple color mapping
                            $colors = [
                                'restaurant' => 'bg-orange-100 text-orange-600',
                                'bar' => 'bg-purple-100 text-purple-600',
                                'hotel' => 'bg-blue-100 text-blue-600',
                                'nomad' => 'bg-cyan-100 text-cyan-700',
                                'cafe' => 'bg-yellow-100 text-yellow-700',
                                'default' => 'bg-slate-100 text-slate-600'
                            ];
                            $cls = $colors[$cat] ?? $colors['default'];
                        ?>
                        <span class="<?php echo $cls; ?> px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            <?php echo $cat_name; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($business['total_reviews'] > 0): ?>
                <div class="text-center">
                    <div class="text-5xl font-extrabold text-yellow-500"><?php echo number_format($business['avg_rating'], 1); ?></div>
                    <div class="text-yellow-500">
                        <?php for($i = 0; $i < 5; $i++): ?>
                            <i class="<?php echo $i < round($business['avg_rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-1"><?php echo $business['total_reviews']; ?> <?php echo $t['reviews']; ?></p>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($business['description']): ?>
            <p class="text-slate-700 dark:text-slate-300 mb-6"><?php echo nl2br(htmlspecialchars($business['description'])); ?></p>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <?php if ($business['address']): ?>
                <div class="flex items-center gap-3">
                    <i class="fas fa-map-marker-alt text-pink-500"></i>
                    <span><?php echo htmlspecialchars($business['address']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($business['phone']): ?>
                <div class="flex items-center gap-3">
                    <i class="fas fa-phone text-pink-500"></i>
                    <a href="tel:<?php echo $business['phone']; ?>" class="hover:text-pink-500"><?php echo htmlspecialchars($business['phone']); ?></a>
                </div>
                <?php endif; ?>
                
                <?php if ($business['website']): ?>
                <div class="flex items-center gap-3">
                    <i class="fas fa-globe text-pink-500"></i>
                    <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank" class="hover:text-pink-500"><?php echo htmlspecialchars($business['website']); ?></a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Contact Buttons -->
            <?php if ($business['phone'] || $has_menu): ?>
            <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                <?php if ($business['phone']): ?>
                <a href="#reservationForm" class="flex-1 min-w-[140px] text-white text-center py-3 px-4 rounded-xl font-black hover:shadow-lg transition-all flex items-center justify-center gap-2" style="background:#7c3aed;">
                    <i class="fas fa-calendar-check"></i> <?php echo $lang == 'en' ? 'Reservation' : 'Rezervasyon'; ?>
                </a>
                <a href="tel:<?php echo htmlspecialchars($business['phone']); ?>" 
                   class="flex-1 min-w-[140px] bg-gradient-to-r from-emerald-500 to-green-500 text-white text-center py-3 px-4 rounded-xl font-black hover:shadow-lg hover:shadow-green-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-phone"></i> <?php echo $lang == 'en' ? 'Call Now' : 'Hemen Ara'; ?>
                </a>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $business['phone']); ?>?text=Hello%2C%20I%20saw%20your%20business%20on%20Kalkan%20Social" 
                   target="_blank"
                   class="flex-1 min-w-[140px] bg-gradient-to-r from-green-500 to-emerald-600 text-white text-center py-3 px-4 rounded-xl font-black hover:shadow-lg hover:shadow-green-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fab fa-whatsapp text-lg"></i> WhatsApp
                </a>
                <?php endif; ?>
                
                <?php if ($has_menu): ?>
                <a href="<?php echo $menu_url; ?>" 
                   target="_blank"
                   class="flex-1 min-w-[140px] bg-gradient-to-r from-orange-500 to-red-500 text-white text-center py-3 px-4 rounded-xl font-black hover:shadow-lg hover:shadow-orange-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-utensils"></i> <?php echo $lang == 'en' ? 'View Menu' : 'Menüyü Gör'; ?>
                </a>
                <?php endif; ?>
                
                <?php if ($business['address']): ?>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($business['address'] . ', Kalkan, Antalya'); ?>" 
                   target="_blank"
                   class="flex-1 min-w-[140px] bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-center py-3 px-4 rounded-xl font-black hover:shadow-lg hover:shadow-blue-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-directions"></i> <?php echo $lang == 'en' ? 'Get Directions' : 'Yol Tarifi'; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php
            $business_share_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com') . '/business_detail?id=' . $business_id;
            ?>
            <div class="flex flex-wrap gap-3 mt-4">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($business_share_url); ?>" 
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-[#1877F2] hover:bg-[#166FE5] text-white transition-all">
                    <i class="fab fa-facebook-f"></i> <?php echo $lang == 'en' ? 'Share on Facebook' : 'Facebook\'ta Paylaş'; ?>
                </a>
            </div>
            
            <!-- Operating Hours Display -->
            <?php 
            if (!empty($business['opening_hours'])):
                $hours = json_decode($business['opening_hours'], true);
                if ($hours):
                    $days_tr = ['monday'=>'Pazartesi','tuesday'=>'Salı','wednesday'=>'Çarşamba','thursday'=>'Perşembe','friday'=>'Cuma','saturday'=>'Cumartesi','sunday'=>'Pazar'];
                    $days_en = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
                    $day_names = $lang == 'en' ? $days_en : $days_tr;
                    
                    // Check if 24/7
                    $is_24_7 = ($hours['is_24_7'] ?? false);
                    
                    // Check if currently open
                    $today = strtolower(date('l'));
                    $current_time = date('H:i');
                    $today_hours = $hours[$today] ?? null;
                    $is_open = $is_24_7; // 24/7 means always open
                    if (!$is_24_7 && $today_hours && !($today_hours['closed'] ?? false)) {
                        $is_open = ($current_time >= $today_hours['open'] && $current_time <= $today_hours['close']);
                    }
            ?>
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <h3 style="font-size: 18px; font-weight: bold; margin: 0;">
                        <i class="fas fa-clock" style="color: #ec4899; margin-right: 8px;"></i>
                        <?php echo $lang == 'en' ? 'Operating Hours' : 'Çalışma Saatleri'; ?>
                    </h3>
                    <?php if ($is_24_7): ?>
                    <span style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                        🌙 <?php echo $lang == 'en' ? 'Open 24/7' : '7/24 Açık'; ?>
                    </span>
                    <?php elseif ($is_open): ?>
                    <span style="background: #22c55e; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                        ● <?php echo $lang == 'en' ? 'Open Now' : 'Şimdi Açık'; ?>
                    </span>
                    <?php else: ?>
                    <span style="background: #ef4444; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                        ● <?php echo $lang == 'en' ? 'Closed' : 'Kapalı'; ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 14px;">
                    <?php foreach ($day_names as $day_key => $day_label): 
                        $day_data = $hours[$day_key] ?? null;
                        $is_today = ($day_key == $today);
                    ?>
                    <div style="display: flex; justify-content: space-between; padding: 8px 12px; background: <?php echo $is_today ? '#fce7f3' : '#f8fafc'; ?>; border-radius: 6px; <?php echo $is_today ? 'font-weight: bold;' : ''; ?>">
                        <span><?php echo $day_label; ?></span>
                        <?php if ($is_24_7): ?>
                        <span style="color: #16a34a;"><?php echo $lang == 'en' ? 'Open 24/7' : '7/24 Açık'; ?></span>
                        <?php elseif ($day_data && ($day_data['closed'] ?? false)): ?>
                        <span style="color: #ef4444;"><?php echo $lang == 'en' ? 'Closed' : 'Kapalı'; ?></span>
                        <?php elseif ($day_data): ?>
                        <span style="color: #16a34a;"><?php echo $day_data['open']; ?> - <?php echo $day_data['close']; ?></span>
                        <?php else: ?>
                        <span style="color: #94a3b8;">-</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; endif; ?>
        </div>

            <!-- Reservation Assistant -->
            <?php if ($business['phone']): ?>
            <div class="mt-8 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border border-green-100 dark:border-green-800/30 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <i class="fab fa-whatsapp text-9xl text-green-600"></i>
                </div>
                
                <h3 class="text-lg font-black text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-4 relative z-10">
                    <i class="fab fa-whatsapp text-green-500 text-2xl"></i>
                    <?php echo $lang == 'en' ? 'Reservation Assistant' : 'Rezervasyon Asistanı'; ?>
                </h3>
                
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-4 relative z-10 max-w-lg">
                    <?php echo $lang == 'en' 
                        ? 'Book a table easily! Fill out the form below and we will create a WhatsApp message for you to send to the venue.' 
                        : 'Kolayca masa ayırtın! Aşağıdaki formu doldurun, mekana göndermeniz için otomatik WhatsApp mesajı oluşturalım.'; ?>
                </p>

                <form id="reservationForm" class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-4" onsubmit="sendReservation(event)">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Your Name' : 'Adınız'; ?></label>
                        <input type="text" id="resName" class="w-full rounded-lg border-none bg-white/80 dark:bg-slate-800/80 p-2.5 text-sm font-bold shadow-sm focus:ring-2 focus:ring-green-500" 
                               value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Guests' : 'Kişi Sayısı'; ?></label>
                        <select id="resGuests" class="w-full rounded-lg border-none bg-white/80 dark:bg-slate-800/80 p-2.5 text-sm font-bold shadow-sm focus:ring-2 focus:ring-green-500">
                            <?php for($i=1; $i<=10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == 2 ? 'selected' : ''; ?>><?php echo $i; ?> <?php echo $lang == 'en' ? 'People' : 'Kişi'; ?></option>
                            <?php endfor; ?>
                            <option value="10+">10+</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Date' : 'Tarih'; ?></label>
                        <input type="date" id="resDate" class="w-full rounded-lg border-none bg-white/80 dark:bg-slate-800/80 p-2.5 text-sm font-bold shadow-sm focus:ring-2 focus:ring-green-500" 
                               value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?php echo $lang == 'en' ? 'Time' : 'Saat'; ?></label>
                        <input type="time" id="resTime" class="w-full rounded-lg border-none bg-white/80 dark:bg-slate-800/80 p-2.5 text-sm font-bold shadow-sm focus:ring-2 focus:ring-green-500" 
                               value="<?php echo date('H:00', strtotime('+1 hour')); ?>" required>
                    </div>

                    <div class="md:col-span-2 mt-2">
                        <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-black py-3 rounded-xl shadow-lg shadow-green-500/30 transition-all flex items-center justify-center gap-2 transform active:scale-95">
                            <i class="fab fa-whatsapp text-xl"></i>
                            <?php echo $lang == 'en' ? 'Send Request via WhatsApp' : 'WhatsApp ile Gönder'; ?>
                        </button>
                        <p class="text-[10px] text-center text-slate-400 mt-2">
                            <?php echo $lang == 'en' ? 'This will create a pre-filled message for you.' : 'Bu işlem sizin için hazır bir mesaj oluşturacaktır.'; ?>
                        </p>
                    </div>
                </form>
            </div>
            
            <script>
            function sendReservation(e) {
                e.preventDefault();
                
                const name = document.getElementById('resName').value;
                const guests = document.getElementById('resGuests').value;
                const date = document.getElementById('resDate').value;
                const time = document.getElementById('resTime').value;
                const businessName = "<?php echo htmlspecialchars($business['name']); ?>";
                const phone = "<?php echo preg_replace('/[^0-9]/', '', $business['phone']); ?>";
                
                // Format Date
                const dateObj = new Date(date);
                const dateStr = dateObj.toLocaleDateString('<?php echo $lang == 'en' ? 'en-US' : 'tr-TR'; ?>', { weekday: 'short', month: 'short', day: 'numeric' });

                let message = "";
                
                <?php if ($lang == 'en'): ?>
                message = `👋 [KalkanSocial] Reservation Request\n\nHello ${businessName}, I would like to reserve a table.\n\n👤 Name: ${name}\n👥 Guests: ${guests}\n📅 Date: ${dateStr}\n🕒 Time: ${time}\n\nIs this available?`;
                <?php else: ?>
                message = `👋 [KalkanSocial] Rezervasyon Talebi\n\nMerhaba ${businessName}, bir masa ayırtmak istiyorum.\n\n👤 İsim: ${name}\n👥 Kişi: ${guests}\n📅 Tarih: ${dateStr}\n🕒 Saat: ${time}\n\nMüsait misiniz?`;
                <?php endif; ?>

                const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
                window.open(url, '_blank');
            }
            </script>
            <?php endif; ?>

        </div>

        <!-- Menu Section (if exists) -->
        <?php if (count($menu_photos) > 0): ?>
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-utensils text-pink-500"></i>
                <?php echo $lang == 'en' ? 'Menu' : 'Menü'; ?>
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($menu_photos as $menu): ?>
                <div class="aspect-[3/4] rounded-xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 relative group">
                    <img src="<?php echo $menu['image_path']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 cursor-pointer" loading="lazy" onclick="openLightbox(this.src)">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors pointer-events-none"></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Simple Lightbox Script -->
            <script>
                function openLightbox(src) {
                    const lightbox = document.createElement('div');
                    lightbox.className = 'fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4 cursor-zoom-out';
                    lightbox.onclick = () => lightbox.remove();
                    
                    const img = document.createElement('img');
                    img.src = src;
                    img.className = 'max-w-full max-h-full rounded-lg shadow-2xl';
                    
                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    closeBtn.className = 'absolute top-4 right-4 text-white text-3xl hover:text-pink-500 transition-colors';
                    
                    lightbox.appendChild(img);
                    lightbox.appendChild(closeBtn);
                    document.body.appendChild(lightbox);
                }
            </script>
        </div>
        <?php endif; ?>

        <!-- Photos -->
        <?php if (count($photos) > 0): ?>
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4"><?php echo $lang == 'en' ? 'Photos' : 'Fotoğraflar'; ?></h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($photos as $photo): ?>
                <div class="aspect-square rounded-xl overflow-hidden">
                    <img src="<?php echo $photo['photo_url']; ?>" class="w-full h-full object-cover hover:scale-110 transition-transform" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Write Review Section -->
        <?php if (isset($_SESSION['user_id']) && !$user_review): ?>
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 mb-6 border border-white/20 dark:border-slate-800/50">
            <h2 class="text-2xl font-bold mb-4"><?php echo $t['write_review']; ?></h2>
            <form id="reviewForm">
                <div class="mb-4">
                    <label class="block font-bold mb-2"><?php echo $t['rating']; ?></label>
                    <div class="flex gap-2">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                        <button type="button" onclick="setRating(<?php echo $i; ?>)" class="star-btn text-3xl text-slate-300 hover:text-yellow-500 transition-colors">
                            <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                        </button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="rating" name="rating" value="0">
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2"><?php echo $lang == 'en' ? 'Your Review' : 'Yorumunuz'; ?></label>
                    <textarea name="comment" rows="4" class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border-none focus:outline-none focus:ring-2 focus:ring-pink-500"></textarea>
                </div>
                <button type="submit" class="bg-gradient-to-r from-pink-500 to-violet-500 text-white px-6 py-3 rounded-xl font-bold hover:shadow-lg">
                    <?php echo $lang == 'en' ? 'Submit Review' : 'Yorum Gönder'; ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Reviews -->
        <div>
            <h2 class="text-2xl font-bold mb-4"><?php echo $t['reviews']; ?> (<?php echo count($reviews); ?>)</h2>
            <?php if (count($reviews) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-slate-800/50">
                        <div class="flex items-start gap-4">
                            <img src="<?php echo $review['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($review['full_name']); ?>" class="w-12 h-12 rounded-full" loading="lazy">
                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-bold"><?php echo htmlspecialchars($review['full_name']); ?></h3>
                                        <p class="text-xs text-slate-500"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="text-yellow-500">
                                            <?php for($i = 0; $i < 5; $i++): ?>
                                                <i class="<?php echo $i < $review['rating'] ? 'fas' : 'far'; ?> fa-star text-sm"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if ($review['comment']): ?>
                                        <button onclick="toggleTranslationReview(<?php echo $review['id']; ?>)" id="trans-btn-review-<?php echo $review['id']; ?>" class="text-slate-400 hover:text-pink-500 transition-colors" title="<?php echo $lang == 'en' ? 'See Translation' : 'Çeviriyi Gör'; ?>">
                                            <i class="fas fa-language text-sm"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($review['comment']): ?>
                                <div class="relative group/rev">
                                    <p id="review-original-<?php echo $review['id']; ?>" class="text-slate-700 dark:text-slate-300 transition-all duration-300"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <p id="review-translated-<?php echo $review['id']; ?>" class="hidden text-slate-700 dark:text-slate-300 italic border-l-2 border-pink-500 pl-3 mt-2 transition-all duration-300"></p>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($review['business_reply'])): ?>
                                <div class="mt-4 pl-4 border-l-2 border-pink-300 dark:border-pink-600 bg-pink-50/50 dark:bg-slate-800/50 rounded-r-xl py-3 px-4">
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold bg-pink-500/20 text-pink-600 dark:text-pink-400">
                                                <i class="fas fa-store"></i> <?php echo $t['business_owner']; ?>
                                            </span>
                                            <?php if (!empty($review['reply_at'])): ?>
                                            <span class="text-xs text-slate-500"><?php echo date('M d, Y', strtotime($review['reply_at'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <button onclick="toggleTranslationReply(<?php echo $review['id']; ?>)" id="trans-btn-reply-<?php echo $review['id']; ?>" class="text-slate-400 hover:text-pink-500 transition-colors" title="<?php echo $lang == 'en' ? 'See Translation' : 'Çeviriyi Gör'; ?>">
                                            <i class="fas fa-language text-xs"></i>
                                        </button>
                                    </div>
                                    <p id="reply-original-<?php echo $review['id']; ?>" class="text-slate-700 dark:text-slate-300 text-sm transition-all duration-300"><?php echo nl2br(htmlspecialchars($review['business_reply'])); ?></p>
                                    <p id="reply-translated-<?php echo $review['id']; ?>" class="hidden text-slate-700 dark:text-slate-300 text-sm italic border-l-2 border-pink-500 pl-2 mt-1 transition-all duration-300"></p>
                                </div>
                                <?php elseif ($is_business_owner): ?>
                                <div class="mt-4" id="reply-form-<?php echo $review['id']; ?>">
                                    <form class="reply-form" data-review-id="<?php echo $review['id']; ?>">
                                        <textarea name="reply" rows="2" placeholder="<?php echo htmlspecialchars($lang == 'en' ? 'Write your reply...' : 'Cevabınızı yazın...'); ?>" class="w-full px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 resize-none"></textarea>
                                        <button type="submit" class="mt-2 px-4 py-2 bg-gradient-to-r from-pink-500 to-violet-500 text-white rounded-xl text-sm font-bold hover:shadow-lg transition-shadow">
                                            <i class="fas fa-reply mr-1"></i> <?php echo $t['send_reply']; ?>
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
            <p class="text-slate-500 text-center py-8"><?php echo $lang == 'en' ? 'No reviews yet. Be the first!' : 'Henüz yorum yok. İlk sen ol!'; ?></p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        let selectedRating = 0;

        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('rating').value = rating;
            
            document.querySelectorAll('.star-btn i').forEach((star, index) => {
                if (index < rating) {
                    star.classList.remove('far');
                    star.classList.add('fas', 'text-yellow-500');
                } else {
                    star.classList.remove('fas', 'text-yellow-500');
                    star.classList.add('far');
                }
            });
        }

        document.getElementById('reviewForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (selectedRating === 0) {
                alert('<?php echo $lang == "en" ? "Please select a rating" : "Lütfen puan seçin"; ?>');
                return;
            }

            const formData = new FormData(e.target);
            formData.append('business_id', <?php echo $business_id; ?>);

            const response = await fetch('api/review_business.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error');
            }
        });

        async function toggleFavorite() {
            const formData = new FormData();
            formData.append('business_id', <?php echo $business_id; ?>);

            const response = await fetch('api/favorite_business.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            }
        }

        async function toggleTranslationReview(reviewId) {
            const originalP = document.getElementById('review-original-' + reviewId);
            const translatedP = document.getElementById('review-translated-' + reviewId);
            const btn = document.getElementById('trans-btn-review-' + reviewId);
            const currentLang = '<?php echo $lang; ?>';
            const isShowingOriginal = !originalP.classList.contains('hidden');
            if (isShowingOriginal) {
                if (!translatedP.innerHTML.trim()) {
                    const oldIcon = btn?.querySelector('i');
                    if (oldIcon) oldIcon.className = 'fas fa-circle-notch fa-spin text-sm';
                    try {
                        const formData = new FormData();
                        formData.append('text', originalP.innerText.trim());
                        formData.append('target_lang', currentLang);
                        const res = await fetch('api/translate.php', { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.success) translatedP.innerText = data.translated_text;
                        else { alert(data.message || data.error || 'Error'); if (oldIcon) oldIcon.className = 'fas fa-language text-sm'; return; }
                    } catch (e) { alert('<?php echo $lang == "en" ? "Translation error" : "Çeviri hatası"; ?>'); if (oldIcon) oldIcon.className = 'fas fa-language text-sm'; return; }
                    if (oldIcon) oldIcon.className = 'fas fa-language text-sm';
                }
                originalP.style.opacity = '0';
                setTimeout(() => { originalP.classList.add('hidden'); translatedP.classList.remove('hidden'); void translatedP.offsetWidth; translatedP.style.opacity = '1'; }, 300);
                if (btn) btn.title = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
            } else {
                translatedP.style.opacity = '0';
                setTimeout(() => { translatedP.classList.add('hidden'); originalP.classList.remove('hidden'); void originalP.offsetWidth; originalP.style.opacity = '1'; }, 300);
                if (btn) btn.title = currentLang === 'en' ? 'See Translation' : 'Çeviriyi Gör';
            }
        }
        async function toggleTranslationReply(reviewId) {
            const originalP = document.getElementById('reply-original-' + reviewId);
            const translatedP = document.getElementById('reply-translated-' + reviewId);
            const btn = document.getElementById('trans-btn-reply-' + reviewId);
            const currentLang = '<?php echo $lang; ?>';
            const isShowingOriginal = !originalP.classList.contains('hidden');
            if (isShowingOriginal) {
                if (!translatedP.innerHTML.trim()) {
                    const oldIcon = btn?.querySelector('i');
                    if (oldIcon) oldIcon.className = 'fas fa-circle-notch fa-spin text-xs';
                    try {
                        const formData = new FormData();
                        formData.append('text', originalP.innerText.trim());
                        formData.append('target_lang', currentLang);
                        const res = await fetch('api/translate.php', { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.success) translatedP.innerText = data.translated_text;
                        else { alert(data.message || data.error || 'Error'); if (oldIcon) oldIcon.className = 'fas fa-language text-xs'; return; }
                    } catch (e) { alert('<?php echo $lang == "en" ? "Translation error" : "Çeviri hatası"; ?>'); if (oldIcon) oldIcon.className = 'fas fa-language text-xs'; return; }
                    if (oldIcon) oldIcon.className = 'fas fa-language text-xs';
                }
                originalP.style.opacity = '0';
                setTimeout(() => { originalP.classList.add('hidden'); translatedP.classList.remove('hidden'); void translatedP.offsetWidth; translatedP.style.opacity = '1'; }, 300);
                if (btn) btn.title = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
            } else {
                translatedP.style.opacity = '0';
                setTimeout(() => { translatedP.classList.add('hidden'); originalP.classList.remove('hidden'); void originalP.offsetWidth; originalP.style.opacity = '1'; }, 300);
                if (btn) btn.title = currentLang === 'en' ? 'See Translation' : 'Çeviriyi Gör';
            }
        }

        document.querySelectorAll('.reply-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const reviewId = form.dataset.reviewId;
                const textarea = form.querySelector('textarea[name="reply"]');
                const reply = textarea?.value?.trim();
                if (!reply) {
                    alert('<?php echo $lang == "en" ? "Please enter a reply" : "Lütfen cevap yazın"; ?>');
                    return;
                }
                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> <?php echo $lang == "en" ? "Sending..." : "Gönderiliyor..."; ?>';
                const formData = new FormData();
                formData.append('review_id', reviewId);
                formData.append('reply', reply);
                try {
                    const res = await fetch('api/reply_review.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-reply mr-1"></i> <?php echo addslashes($t['send_reply']); ?>';
                    }
                } catch (err) {
                    alert('<?php echo $lang == "en" ? "Error" : "Hata"; ?>');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-reply mr-1"></i> <?php echo addslashes($t['send_reply']); ?>';
                }
            });
        });
    </script>
    <script>
        // Force light mode - runs after all other scripts
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        
        // Define toggleTheme function (disabled for this page - always light)
        function toggleTheme() {
            // Do nothing - this page is light mode only
            alert('Bu sayfa sadece açık tema destekler.');
        }
    </script>
</body>
</html>
