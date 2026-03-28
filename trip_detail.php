<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';

$trip_id = $_GET['id'] ?? 0;

// Fetch trip details
$stmt = $pdo->prepare("
    SELECT bt.*, u.full_name as captain_name, u.username as captain_username, u.avatar as captain_avatar, u.created_at as captain_since, u.phone as captain_phone,
           (SELECT AVG(rating) FROM trip_reviews WHERE trip_id = bt.id) as avg_rating,
           (SELECT COUNT(*) FROM trip_reviews WHERE trip_id = bt.id) as review_count
    FROM boat_trips bt
    JOIN users u ON bt.captain_id = u.id
    WHERE bt.id = ? AND bt.status = 'approved' AND bt.is_active = 1
");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header("Location: boat_trips");
    exit();
}

// Decode amenities
$amenities = json_decode($trip['amenities'] ?? '[]', true);

// Fetch reviews
$stmt = $pdo->prepare("
    SELECT tr.*, u.full_name, u.avatar
    FROM trip_reviews tr
    JOIN users u ON tr.user_id = u.id
    WHERE tr.trip_id = ?
    ORDER BY tr.created_at DESC
    LIMIT 5
");
$stmt->execute([$trip_id]);
$reviews = $stmt->fetchAll();

// Check permissions for Edit Button
$can_edit = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
    $u_stmt->execute([$uid]);
    $badge = $u_stmt->fetchColumn();
    
    if (in_array($badge, ['founder', 'moderator']) || $trip['captain_id'] == $uid) {
        $can_edit = true;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($trip['title']); ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <!-- Hero Image -->
    <div class="relative h-[60vh] md:h-[500px]">
        <img src="<?php echo htmlspecialchars($trip['cover_photo']); ?>" class="w-full h-full object-cover" loading="lazy">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/30 to-transparent"></div>
        <div class="absolute bottom-0 left-0 w-full p-6 md:p-12">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-start">
                    <span class="px-4 py-2 bg-cyan-500 text-white rounded-full text-xs font-black uppercase shadow-lg mb-4 inline-block">
                        <?php echo $t[$trip['category']] ?? $trip['category']; ?>
                    </span>
                    <?php if ($can_edit): ?>
                        <a href="edit_boat_trip?id=<?php echo $trip['id']; ?>" class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white rounded-xl font-bold transition-all border border-white/30 shadow-lg">
                            <i class="fas fa-edit mr-2"></i><?php echo $lang == 'en' ? 'Edit Trip' : 'Turu Düzenle'; ?>
                        </a>
                    <?php endif; ?>
                </div>
                <h1 class="text-3xl md:text-5xl font-black text-white mb-2"><?php echo htmlspecialchars($trip['title']); ?></h1>
                <div class="flex items-center text-white/80 gap-4 text-sm md:text-base">
                    <span><i class="fas fa-map-marker-alt text-cyan-400 mr-2"></i><?php echo htmlspecialchars($trip['departure_location']); ?></span>
                    <?php if ($trip['avg_rating']): ?>
                        <span><i class="fas fa-star text-amber-400 mr-2"></i><?php echo number_format($trip['avg_rating'], 1); ?> (<?php echo $trip['review_count']; ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <main class="max-w-7xl mx-auto px-6 -mt-10 relative z-10 grid grid-cols-1 lg:grid-cols-3 gap-8 mb-20">
        
        <!-- Left Column: Details -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Specs Navbar (Horizontal Scroll) -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-100 dark:border-slate-800 flex gap-8 overflow-x-auto">
                <div class="flex flex-col items-center min-w-[80px]">
                    <i class="fas fa-clock text-cyan-500 text-xl mb-1"></i>
                    <span class="text-xs text-slate-400 uppercase font-bold"><?php echo $t['duration']; ?></span>
                    <span class="font-bold"><?php echo $trip['duration_hours']; ?> <?php echo $t['hours']; ?></span>
                </div>
                <div class="flex flex-col items-center min-w-[80px]">
                    <i class="fas fa-users text-cyan-500 text-xl mb-1"></i>
                    <span class="text-xs text-slate-400 uppercase font-bold"><?php echo $t['max_capacity']; ?></span>
                    <span class="font-bold"><?php echo $trip['max_capacity']; ?></span>
                </div>
                <div class="flex flex-col items-center min-w-[80px]">
                    <i class="fas fa-ship text-cyan-500 text-xl mb-1"></i>
                    <span class="text-xs text-slate-400 uppercase font-bold"><?php echo $lang == 'en' ? 'Boat Type' : 'Tekne'; ?></span>
                    <span class="font-bold"><?php echo htmlspecialchars($trip['boat_type']); ?></span>
                </div>
                <div class="flex flex-col items-center min-w-[80px]">
                    <i class="fas fa-anchor text-cyan-500 text-xl mb-1"></i>
                    <span class="text-xs text-slate-400 uppercase font-bold"><?php echo $t['boat_name']; ?></span>
                    <span class="font-bold"><?php echo htmlspecialchars($trip['boat_name']); ?></span>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <h3 class="text-xl font-black mb-4 flex items-center gap-2">
                    <i class="fas fa-align-left text-cyan-500"></i> <?php echo $lang == 'en' ? 'About this trip' : 'Tur Hakkında'; ?>
                </h3>
                <div class="prose dark:prose-invert max-w-none text-slate-600 dark:text-slate-300">
                    <?php echo nl2br(htmlspecialchars($trip['description'])); ?>
                </div>
            </div>

            <!-- Amenities -->
            <?php if (!empty($amenities)): ?>
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="text-xl font-black mb-6 flex items-center gap-2">
                        <i class="fas fa-concierge-bell text-cyan-500"></i> <?php echo $t['amenities']; ?>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach($amenities as $item): ?>
                            <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl">
                                <?php if($item == 'wifi'): ?><i class="fas fa-wifi text-cyan-500"></i><?php endif; ?>
                                <?php if($item == 'lunch'): ?><i class="fas fa-utensils text-cyan-500"></i><?php endif; ?>
                                <?php if($item == 'drinks'): ?><i class="fas fa-cocktail text-cyan-500"></i><?php endif; ?>
                                <?php if($item == 'snorkeling'): ?><i class="fas fa-swimmer text-cyan-500"></i><?php endif; ?>
                                <?php if($item == 'fishing_gear'): ?><i class="fas fa-fish text-cyan-500"></i><?php endif; ?>
                                <?php if($item == 'bathroom'): ?><i class="fas fa-restroom text-cyan-500"></i><?php endif; ?>
                                <span class="font-bold text-sm capitalize"><?php echo str_replace('_', ' ', $item); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Captain Profile -->
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <h3 class="text-xl font-black mb-6 flex items-center gap-2">
                    <i class="fas fa-user-captain text-cyan-500"></i> <?php echo $t['captain']; ?>
                </h3>
                <div class="flex items-center gap-6 mb-6">
                    <img src="<?php echo htmlspecialchars($trip['captain_avatar']); ?>" class="w-24 h-24 rounded-[2rem] object-cover bg-slate-100" loading="lazy">
                    <div>
                        <h4 class="text-2xl font-black mb-1"><?php echo htmlspecialchars($trip['captain_name']); ?></h4>
                        <p class="text-sm font-bold text-slate-400 mb-2">@<?php echo htmlspecialchars($trip['captain_username']); ?></p>
                        <span class="px-3 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 rounded-full text-xs font-black uppercase">
                            <i class="fas fa-check-circle mr-1"></i> <?php echo $lang == 'en' ? 'Verified Captain' : 'Onaylı Kaptan'; ?>
                        </span>
                    </div>
                </div>

                <!-- Contact Buttons -->
                <?php if (!empty($trip['captain_phone'])): 
                    // Clean phone number for WhatsApp (remove spaces, dashes, etc.)
                    $clean_phone = preg_replace('/[^0-9+]/', '', $trip['captain_phone']);
                    // Remove leading + for WhatsApp format
                    $whatsapp_phone = ltrim($clean_phone, '+');
                    // Pre-filled message
                    $whatsapp_message = urlencode($lang == 'en' 
                        ? "Hi! I'm interested in your boat trip: " . $trip['title'] 
                        : "Merhaba! Tekne turunuzla ilgileniyorum: " . $trip['title']);
                ?>
                <div class="grid grid-cols-2 gap-4">
                    <a href="https://wa.me/<?php echo $whatsapp_phone; ?>?text=<?php echo $whatsapp_message; ?>" 
                       target="_blank"
                       class="flex items-center justify-center gap-3 bg-green-500 hover:bg-green-600 text-white px-6 py-4 rounded-2xl font-black shadow-lg shadow-green-500/30 transition-all hover:scale-105">
                        <i class="fab fa-whatsapp text-2xl"></i>
                        <span>WhatsApp</span>
                    </a>
                    <a href="tel:<?php echo htmlspecialchars($clean_phone); ?>" 
                       class="flex items-center justify-center gap-3 bg-blue-500 hover:bg-blue-600 text-white px-6 py-4 rounded-2xl font-black shadow-lg shadow-blue-500/30 transition-all hover:scale-105">
                        <i class="fas fa-phone-alt text-xl"></i>
                        <span><?php echo $lang == 'en' ? 'Call' : 'Ara'; ?></span>
                    </a>
                </div>
                <?php else: ?>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 text-center">
                    <p class="text-sm text-slate-400">
                        <i class="fas fa-info-circle mr-2"></i>
                        <?php echo $lang == 'en' ? 'Use the booking form to contact the captain.' : 'Kaptanla iletişime geçmek için rezervasyon formunu kullanın.'; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Right Column: Booking Card -->
        <div class="lg:col-span-1">
            <div class="sticky top-28">
                <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-2xl border border-slate-100 dark:border-slate-800 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex justify-between items-end mb-6 pb-6 border-b border-slate-100 dark:border-slate-700">
                            <div>
                                <p class="text-xs text-slate-400 font-bold uppercase"><?php echo $lang == 'en' ? 'Starting from' : 'Başlangıç Fiyatı'; ?></p>
                                <p class="text-3xl font-black text-slate-800 dark:text-white"><?php echo $trip['price_per_person']; ?> <span class="text-sm text-cyan-500"><?php echo $trip['currency']; ?></span></p>
                            </div>
                            <span class="text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded-full"><?php echo $t['per_person']; ?></span>
                        </div>

                        <?php if(isset($_SESSION['user_id'])): ?>
                            <form id="bookingForm" class="space-y-4">
                                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                
                                <div>
                                    <label class="block text-xs font-black uppercase text-slate-400 ml-2 mb-1"><?php echo $lang == 'en' ? 'Select Date' : 'Tarih Seçin'; ?></label>
                                    <input type="text" id="datePicker" name="booking_date" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold cursor-pointer" placeholder="<?php echo $lang == 'en' ? 'Select Date' : 'Tarih Seçin'; ?>">
                                </div>

                                <div>
                                    <label class="block text-xs font-black uppercase text-slate-400 ml-2 mb-1"><?php echo $t['num_guests']; ?></label>
                                    <input type="number" name="num_guests" min="1" max="<?php echo $trip['max_capacity']; ?>" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold" placeholder="1-<?php echo $trip['max_capacity']; ?>">
                                </div>

                                <div>
                                    <label class="block text-xs font-black uppercase text-slate-400 ml-2 mb-1"><?php echo $lang == 'en' ? 'Phone' : 'Telefon'; ?></label>
                                    <input type="tel" name="contact_phone" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold" placeholder="+90...">
                                </div>

                                <div>
                                    <label class="block text-xs font-black uppercase text-slate-400 ml-2 mb-1"><?php echo $t['special_requests']; ?></label>
                                    <textarea name="special_requests" rows="2" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-medium text-sm"></textarea>
                                </div>

                                <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-cyan-500/30 transition-all transform active:scale-95 text-lg block text-center mt-6">
                                    <?php echo $t['request_quote']; ?>
                                </button>
                                
                                <!-- Info Box -->
                                <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-100 dark:border-cyan-800 rounded-2xl p-4 mt-4">
                                    <div class="flex gap-3">
                                        <i class="fas fa-envelope text-cyan-500 mt-0.5"></i>
                                        <div>
                                            <p class="text-xs font-bold text-cyan-700 dark:text-cyan-400 mb-1">
                                                <?php echo $lang == 'en' ? 'How it works' : 'Nasıl Çalışır?'; ?>
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                                <?php echo $lang == 'en' 
                                                    ? 'Your reservation request will be sent to the captain via email. The captain will contact you as soon as possible to confirm availability and finalize the booking.' 
                                                    : 'Rezervasyon talebiniz kaptana e-posta olarak iletilecektir. Kaptan en kısa sürede sizinle iletişime geçerek müsaitlik durumunu onaylayacak ve rezervasyonu tamamlayacaktır.'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-6">
                                <p class="mb-4 text-slate-500"><?php echo $lang == 'en' ? 'Please login to request a booking.' : 'Rezervasyon yapmak için lütfen giriş yapın.'; ?></p>
                                <a href="login" class="block w-full bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black py-4 rounded-2xl transition-all">
                                    <?php echo $t['login']; ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Init DatePicker
        flatpickr("#datePicker", {
            minDate: "today",
            dateFormat: "Y-m-d",
            disable: [] 
            // In future, fetch blocked dates from trip_availability
        });

        // Booking Form Handler
        document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData(this);

            fetch('api/book_trip.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('<?php echo $lang == 'en' ? 'Booking request sent successfully! Check your inbox soon.' : 'Rezervasyon talebi başarıyla gönderildi! Yakında gelen kutunuzu kontrol edin.'; ?>');
                    this.reset();
                } else {
                    alert(data.message || 'Error');
                }
            })
            .catch(err => alert('Error occurred'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    </script>
</body>
</html>
