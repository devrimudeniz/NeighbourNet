<?php
require_once 'includes/bootstrap.php';

// Fetch available rides
$r_stmt = $pdo->prepare("SELECT r.*, u.username, u.full_name, u.avatar, u.phone 
                        FROM rides r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.ride_date >= CURDATE() AND r.status = 'active'
                        ORDER BY r.ride_date ASC, r.ride_time ASC");
$r_stmt->execute();
$r_stmt->execute();
$rides = $r_stmt->fetchAll();

// Check if user is subscribed
$is_subscribed = false;
if (isset($_SESSION['user_id'])) {
    try {
        $sub_stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND service = 'rides'");
        $sub_stmt->execute([$_SESSION['user_id']]);
        $is_subscribed = $sub_stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        // Table likely doesn't exist yet, ignore error
        $is_subscribed = false;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['ride_sharing']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .dark .glass { background: rgba(15, 23, 42, 0.7); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-6 pt-24">
        <!-- Hero Section -->
        <div class="relative rounded-[2.5rem] overflow-hidden mb-10 h-64 shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-pink-500 to-violet-600 mix-blend-multiply z-10"></div>
            <!-- Using a car/road related image if available, or fallback to transport hero for now -->
            <img src="assets/transport_hero.jpg" class="absolute inset-0 w-full h-full object-cover">
            <div class="relative z-20 h-full flex flex-col justify-center px-10">
                <h1 class="text-4xl md:text-5xl font-black text-white mb-2"><?php echo $t['ride_sharing']; ?></h1>
                <p class="text-white/80 text-lg font-medium max-w-lg">Share a ride, save money, make friends.</p>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <button onclick="toggleSubscription('rides', this)" class="absolute top-10 right-10 w-12 h-12 rounded-full backdrop-blur-md bg-white/20 border border-white/30 flex items-center justify-center text-white hover:bg-white hover:text-pink-600 transition-all group-hover:scale-110" title="Subscribe to Notifications">
                    <i class="<?php echo $is_subscribed ? 'fas' : 'far'; ?> fa-bell text-xl"></i>
                    <?php if($is_subscribed): ?>
                    <span class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full border-2 border-white/20"></span>
                    <?php endif; ?>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Offer Ride CTA -->
        <div class="bg-gradient-to-r from-pink-500 to-violet-600 rounded-[2rem] p-10 mb-10 text-white shadow-2xl relative overflow-hidden group">
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="text-center md:text-left">
                    <h2 class="text-3xl font-black mb-2"><?php echo $t['offer_ride']; ?></h2>
                    <p class="text-white/80 font-medium">Have extra seats in your car? Share the cost and make new friends!</p>
                </div>
                <button onclick="toggleRideForm()" class="px-10 py-5 bg-white text-violet-600 rounded-2xl font-black text-lg shadow-xl hover:scale-105 transition-all">
                    <?php echo $t['post_ride']; ?>
                </button>
            </div>
            <div class="absolute -right-10 -bottom-10 opacity-10 group-hover:scale-110 transition-transform">
                <i class="fas fa-car-side text-[15rem]"></i>
            </div>
        </div>

        <!-- Ride Form (Hidden) -->
        <div id="ride-form" class="hidden bg-white dark:bg-slate-800 rounded-[2rem] p-8 mb-10 shadow-2xl border border-slate-100 dark:border-slate-800 scale-95 opacity-0 transition-all duration-300">
            <form id="postRideForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="action" value="create">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-2"><?php echo $t['from']; ?></label>
                    <input type="text" name="origin" required placeholder="Ex: Kalkan Center" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-2"><?php echo $t['to']; ?></label>
                    <input type="text" name="destination" required placeholder="Ex: Dalaman Airport" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-2"><?php echo $lang == 'en' ? 'Date' : 'Tarih'; ?></label>
                    <input type="date" name="ride_date" required class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-2"><?php echo $lang == 'en' ? 'Time' : 'Saat'; ?></label>
                    <input type="time" name="ride_time" required class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-2"><?php echo $t['seats']; ?></label>
                    <input type="number" name="seats" value="1" min="1" max="8" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-2"><?php echo $t['price']; ?> (Optional)</label>
                    <input type="text" name="price" placeholder="Ex: 500 TL" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all">
                </div>
                <div class="md:col-span-2 space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase ml-2"><?php echo $t['ride_note']; ?></label>
                    <textarea name="note" rows="3" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-4 mt-2">
                    <button type="button" onclick="toggleRideForm()" class="px-8 py-4 text-slate-400 font-bold hover:text-slate-600 transition-all"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="px-10 py-4 bg-violet-600 text-white rounded-2xl font-black shadow-lg shadow-violet-500/25 hover:bg-violet-700 transition-all"><?php echo $t['post_ride']; ?></button>
                </div>
            </form>
        </div>

        <!-- Rides List -->
        <h3 class="text-2xl font-black mb-6 flex items-center gap-3">
             <span class="w-2 h-8 bg-pink-500 rounded-full"></span>
             <?php echo $lang == 'en' ? 'Available Rides' : 'Mevcut Yolculuklar'; ?>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($rides as $ride): ?>
            <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:border-violet-500/30 transition-all group" data-ride-id="<?php echo $ride['id']; ?>">
                <div class="flex items-center gap-4 mb-6">
                    <img src="<?php echo $ride['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($ride['username']); ?>" class="w-14 h-14 rounded-2x object-cover shadow-lg ring-2 ring-slate-50 dark:ring-slate-700">
                    <div class="flex-1">
                        <h4 class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($ride['full_name'] ?? $ride['username']); ?></h4>
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                            <i class="far fa-star text-yellow-500"></i>
                            <span>Local Member</span>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $ride['user_id']): ?>
                        <button onclick="deleteRide(<?php echo $ride['id']; ?>, this)" class="text-slate-300 hover:text-red-500 transition-colors p-2">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3 mb-6 p-4 bg-slate-50 dark:bg-slate-900 rounded-3xl relative">
                    <div class="flex-1 text-center">
                        <p class="text-[10px] uppercase font-black text-slate-500 dark:text-slate-400 mb-1"><?php echo $t['from']; ?></p>
                        <p class="font-bold text-sm truncate text-slate-900 dark:text-white"><?php echo htmlspecialchars($ride['origin']); ?></p>
                    </div>
                    <div class="text-violet-500">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="flex-1 text-center">
                        <p class="text-[10px] uppercase font-black text-slate-500 dark:text-slate-400 mb-1"><?php echo $t['to']; ?></p>
                        <p class="font-bold text-sm truncate text-slate-900 dark:text-white"><?php echo htmlspecialchars($ride['destination']); ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="flex items-center gap-3 text-sm font-bold text-slate-700 dark:text-slate-200">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-500">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                        <?php echo date('d M', strtotime($ride['ride_date'])); ?>
                    </div>
                    <div class="flex items-center gap-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-500">
                            <i class="far fa-clock"></i>
                        </div>
                        <?php echo date('H:i', strtotime($ride['ride_time'])); ?>
                    </div>
                    <div class="flex items-center gap-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-500">
                            <i class="fas fa-chair"></i>
                        </div>
                        <?php echo $ride['seats']; ?> <?php echo $t['seats']; ?>
                    </div>
                    <div class="flex items-center gap-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-500">
                            <i class="fas fa-tag"></i>
                        </div>
                        <?php echo $ride['price'] ? htmlspecialchars($ride['price']) : $t['free']; ?>
                    </div>
                </div>

                <?php if($ride['note']): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6 italic">"<?php echo htmlspecialchars($ride['note']); ?>"</p>
                <?php endif; ?>

                <div class="flex gap-2">
                    <a href="messages?chat_with=<?php echo $ride['user_id']; ?>" class="flex-1 py-4 rounded-2xl bg-violet-50 dark:bg-violet-900/10 text-violet-600 dark:text-violet-400 font-black text-center text-sm border border-violet-100 dark:border-violet-500/10 hover:bg-violet-600 hover:text-white transition-all">
                        <i class="far fa-comment-dots mr-2"></i><?php echo $t['contact_seller']; ?>
                    </a>
                    <?php if($ride['phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars($ride['phone']); ?>" class="w-14 h-14 rounded-2xl bg-green-50 dark:bg-green-900/10 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-500/10 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all shadow-sm">
                        <i class="fas fa-phone"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($rides)): ?>
                <div class="md:col-span-2 text-center py-20 opacity-30">
                    <i class="fas fa-car-side text-6xl mb-4"></i>
                    <p class="text-xl font-bold"><?php echo $t['no_rides']; ?></p>
                </div>
            <?php endif; ?>
        </div>
        
    </main>

    <!-- Subscription Success Modal -->
    <div id="sub-success-modal" class="fixed inset-0 z-[60] flex items-center justify-center px-4 opacity-0 pointer-events-none transition-all duration-300">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeSubModal()"></div>
        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 max-w-sm w-full relative transform scale-90 transition-all duration-300 shadow-2xl border border-white/20">
            <div class="text-center">
                <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2"><?php echo $lang == 'en' ? 'Subscribed!' : 'Abone Olundu!'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                    <?php echo $lang == 'en' ? 'You will now receive notifications when new rides are posted.' : 'Harika! Bundan sonra yeni bir yolculuk ilanı paylaşıldığında anında bildirim alacaksınız.'; ?>
                </p>
                
                <button onclick="closeSubModal()" class="mt-8 w-full py-4 rounded-2xl bg-emerald-500 text-white font-bold hover:bg-emerald-600 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-emerald-500/25">
                    <?php echo $lang == 'en' ? 'Awesome' : 'Tamamdır'; ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleRideForm() {
            const form = document.getElementById('ride-form');
            if(form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                setTimeout(() => {
                    form.classList.remove('scale-95', 'opacity-0');
                    form.classList.add('scale-100', 'opacity-100');
                }, 10);
            } else {
                form.classList.add('scale-95', 'opacity-0');
                setTimeout(() => form.classList.add('hidden'), 300);
            }
        }

        document.getElementById('postRideForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('api/rides.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('<?php echo $t['ride_success']; ?>');
                    location.reload();
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<?php echo $t['post_ride']; ?>';
                }
            });
        });

        function deleteRide(id, btn) {
            if(!confirm('<?php echo $t['confirm']; ?>')) return;
            
            const params = new URLSearchParams();
            params.append('action', 'delete');
            params.append('ride_id', id);

            fetch('api/rides.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    const card = btn ? btn.closest('[data-ride-id]') : document.querySelector(`[data-ride-id="${id}"]`);
                    if (card) {
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => card.remove(), 300);
                    }
                } else {
                    alert(data.message);
                }
            });
        }

        function toggleSubscription(service, btn) {
            const icon = btn.querySelector('i');
            
            fetch('api/subscribe.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ service: service })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    if(data.subscribed) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        // Add badge
                        if(!btn.querySelector('span')) {
                            const badge = document.createElement('span');
                            badge.className = 'absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full border-2 border-white/20';
                            btn.appendChild(badge);
                        }
                        // Show Success Modal
                        openSubModal();
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        // Remove badge
                        const badge = btn.querySelector('span');
                        if(badge) badge.remove();
                    }
                } else {
                    if(data.message === 'Login required') location.href = 'login.php';
                    else alert(data.message);
                }
            });
        }

        function openSubModal() {
            const modal = document.getElementById('sub-success-modal');
            const content = modal.querySelector('div.relative');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            setTimeout(() => {
                content.classList.remove('scale-90');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeSubModal() {
            const modal = document.getElementById('sub-success-modal');
            const content = modal.querySelector('div.relative');
            content.classList.remove('scale-100');
            content.classList.add('scale-90');
            setTimeout(() => {
                modal.classList.add('opacity-0', 'pointer-events-none');
            }, 300);
        }
    </script>
</body>
</html>
