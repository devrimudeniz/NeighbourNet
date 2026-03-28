<?php
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';

// Create lost_items table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lost_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        category ENUM('keys','wallet','phone','bag','glasses','documents','jewelry','other') DEFAULT 'other',
        description TEXT,
        location VARCHAR(255) NOT NULL,
        photo_url VARCHAR(255),
        contact_phone VARCHAR(50),
        status ENUM('lost','found') DEFAULT 'lost',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (status),
        INDEX (category),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) { /* Table exists */ }

// Fetch items (active = lost, resolved = found)
$stmt = $pdo->query("SELECT li.*, u.username, u.full_name, u.avatar FROM lost_items li JOIN users u ON li.user_id = u.id ORDER BY li.status ASC, li.created_at DESC");
$items = $stmt->fetchAll();

// Check subscription
$is_subscribed = false;
if (isset($_SESSION['user_id'])) {
    try {
        $sub_stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND service = 'lost_found'");
        $sub_stmt->execute([$_SESSION['user_id']]);
        $is_subscribed = $sub_stmt->fetch() ? true : false;
    } catch (PDOException $e) { $is_subscribed = false; }
}

$cat_labels = [
    'keys' => $lang == 'en' ? 'Keys' : 'Anahtar',
    'wallet' => $lang == 'en' ? 'Wallet' : 'Cüzdan',
    'phone' => $lang == 'en' ? 'Phone' : 'Telefon',
    'bag' => $lang == 'en' ? 'Bag' : 'Çanta',
    'glasses' => $lang == 'en' ? 'Glasses' : 'Gözlük',
    'documents' => $lang == 'en' ? 'Documents' : 'Evrak',
    'jewelry' => $lang == 'en' ? 'Jewelry' : 'Takı',
    'other' => $t['other'] ?? ($lang == 'en' ? 'Other' : 'Diğer'),
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 max-w-4xl">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400 flex items-center gap-2">
                    <i class="fas fa-key"></i>
                    <?php echo $lang == 'en' ? 'Lost & Found' : 'Kayıp Eşya'; ?>
                </h1>
                <p class="text-slate-500 text-sm mt-1"><?php echo $lang == 'en' ? 'Share lost or found items: keys, wallet, phone, bag...' : 'Kayıp veya bulunan eşyaları paylaşın: anahtar, cüzdan, telefon, çanta...'; ?></p>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                <?php if(isset($_SESSION['user_id'])): ?>
                <button onclick="toggleSubscription('lost_found', this)" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 hover:text-amber-500 hover:border-amber-500 transition-all relative shrink-0" title="<?php echo $lang == 'en' ? 'Subscribe to Alerts' : 'Bildirimlere Abone Ol'; ?>">
                    <i class="<?php echo $is_subscribed ? 'fas' : 'far'; ?> fa-bell text-lg"></i>
                    <?php if($is_subscribed): ?>
                    <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white dark:border-slate-800"></span>
                    <?php endif; ?>
                </button>
                <a href="add_lost_item" class="bg-amber-500 text-white px-4 py-2.5 rounded-full font-bold shadow-lg shadow-amber-500/30 hover:bg-amber-600 transition-all text-sm flex items-center gap-2 shrink-0">
                    <i class="fas fa-plus"></i> <?php echo $lang == 'en' ? 'Report Item' : 'İlan Ver'; ?>
                </a>
                <?php else: ?>
                <a href="login?redirect=add_lost_item" class="bg-amber-500 text-white px-4 py-2.5 rounded-full font-bold shadow-lg shadow-amber-500/30 hover:bg-amber-600 transition-all text-sm flex items-center gap-2 shrink-0">
                    <i class="fas fa-plus"></i> <?php echo $lang == 'en' ? 'Report Item' : 'İlan Ver'; ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-6 shadow-sm border border-slate-100 dark:border-slate-700">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <select id="filter-status" onchange="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-2.5 text-sm font-bold outline-none">
                    <option value=""><?php echo $lang == 'en' ? 'All Status' : 'Tüm Durumlar'; ?></option>
                    <option value="lost"><?php echo $lang == 'en' ? 'Lost' : 'Kayıp'; ?></option>
                    <option value="found"><?php echo $lang == 'en' ? 'Found' : 'Bulundu'; ?></option>
                </select>
                <select id="filter-category" onchange="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-2.5 text-sm font-bold outline-none">
                    <option value=""><?php echo $lang == 'en' ? 'All Categories' : 'Tüm Kategoriler'; ?></option>
                    <?php foreach ($cat_labels as $k => $v): ?>
                    <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($v); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="filter-location" placeholder="<?php echo $lang == 'en' ? 'Location...' : 'Konum...'; ?>" oninput="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-2.5 text-sm font-bold outline-none col-span-2 sm:col-span-1">
                <button onclick="resetFilters()" class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl p-2.5 text-sm font-bold hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-undo"></i> <?php echo $lang == 'en' ? 'Reset' : 'Sıfırla'; ?>
                </button>
            </div>
        </div>

        <?php if(empty($items)): ?>
            <div class="text-center py-16 sm:py-20 opacity-80">
                <i class="fas fa-box-open text-6xl text-amber-400 mb-4"></i>
                <p class="font-bold text-lg"><?php echo $lang == 'en' ? 'No lost or found items yet' : 'Henüz kayıp eşya ilanı yok'; ?></p>
                <p class="text-sm text-slate-500 mt-1"><?php echo $lang == 'en' ? 'Be the first to report a lost or found item.' : 'Kayıp veya bulunan bir eşya bildirerek ilk siz olun.'; ?></p>
                <?php if(isset($_SESSION['user_id'])): ?>
                <a href="add_lost_item" class="inline-block mt-4 bg-amber-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-amber-600 transition-all">
                    <i class="fas fa-plus mr-2"></i> <?php echo $lang == 'en' ? 'Report Item' : 'İlan Ver'; ?>
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-4" id="items-container">
                <?php foreach($items as $it): ?>
                <div class="item-card bg-white dark:bg-slate-800 rounded-2xl sm:rounded-3xl p-4 shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-4 <?php echo $it['status'] == 'found' ? 'opacity-75' : ''; ?>" 
                     data-status="<?php echo htmlspecialchars($it['status']); ?>" 
                     data-category="<?php echo htmlspecialchars($it['category']); ?>" 
                     data-location="<?php echo strtolower(htmlspecialchars($it['location'])); ?>">
                    
                    <!-- Image -->
                    <div class="w-full sm:w-36 h-36 sm:h-36 flex-shrink-0 bg-slate-100 dark:bg-slate-700 rounded-xl overflow-hidden relative group">
                        <?php if(!empty($it['photo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($it['photo_url']); ?>" class="w-full h-full object-cover" loading="lazy" alt="">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-amber-400">
                                <i class="fas fa-box-open text-4xl"></i>
                            </div>
                        <?php endif; ?>
                        <?php if($it['status'] == 'found'): ?>
                            <div class="absolute inset-0 bg-emerald-500/80 flex items-center justify-center text-white font-bold text-sm backdrop-blur-sm">
                                <i class="fas fa-check-circle mr-1"></i> <?php echo $lang == 'en' ? 'Found' : 'Bulundu'; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-lg text-slate-900 dark:text-white"><?php echo htmlspecialchars($it['item_name']); ?></h3>
                                    <span class="text-xs px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full font-bold uppercase">
                                        <?php echo $cat_labels[$it['category']] ?? $it['category']; ?>
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-bold <?php echo $it['status'] == 'lost' ? 'bg-red-100 dark:bg-red-900/30 text-red-600' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600'; ?>">
                                        <?php echo $it['status'] == 'lost' ? ($lang == 'en' ? 'Lost' : 'Kayıp') : ($lang == 'en' ? 'Found' : 'Bulundu'); ?>
                                    </span>
                                </div>
                                <div class="text-amber-600 dark:text-amber-400 text-sm font-bold mt-1 flex items-center gap-1">
                                    <i class="fas fa-map-marker-alt text-xs"></i>
                                    <span><?php echo htmlspecialchars($it['location']); ?></span>
                                </div>
                            </div>
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $it['user_id'] && $it['status'] == 'lost'): ?>
                                <button onclick="markFound(<?php echo $it['id']; ?>)" class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-emerald-200 transition-colors shrink-0 self-start">
                                    <i class="fas fa-check mr-1"></i> <?php echo $lang == 'en' ? 'Mark Found' : 'Bulundu İşaretle'; ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($it['description'])): ?>
                        <p class="text-slate-600 dark:text-slate-300 text-sm mt-2 line-clamp-2"><?php echo htmlspecialchars($it['description']); ?></p>
                        <?php endif; ?>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <?php if(!empty($it['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($it['avatar']); ?>" class="w-5 h-5 rounded-full" loading="lazy" alt="">
                                <?php endif; ?>
                                <span>@<?php echo htmlspecialchars($it['username']); ?></span>
                                <span>&bull; <?php echo date('d.m H:i', strtotime($it['created_at'])); ?></span>
                            </div>
                            <?php if($it['status'] != 'found' && !empty($it['contact_phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($it['contact_phone']); ?>" class="text-emerald-500 font-bold text-sm bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition-colors flex items-center gap-1">
                                    <i class="fas fa-phone-alt"></i> <?php echo $t['call']; ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Share -->
                        <div class="mt-3 flex items-center gap-2 border-t border-slate-100 dark:border-slate-700 pt-3">
                            <?php 
                            $share_url = urlencode("https://kalkansocial.com/lost_found?id=" . $it['id']);
                            $share_text = urlencode(($it['status'] == 'lost' ? '🔑 Kayıp: ' : '✅ Bulundu: ') . $it['item_name'] . ' - ' . $it['location']);
                            ?>
                            <a href="https://wa.me/?text=<?php echo $share_text; ?>%20<?php echo $share_url; ?>" target="_blank" class="flex items-center gap-1.5 text-xs font-bold text-green-600 bg-green-50 dark:bg-green-900/20 px-3 py-1.5 rounded-lg hover:bg-green-100 transition-colors">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" class="flex items-center gap-1.5 text-xs font-bold text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Subscription Modal -->
    <div id="sub-success-modal" class="fixed inset-0 z-[60] flex items-center justify-center px-4 opacity-0 pointer-events-none transition-all duration-300">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeSubModal()"></div>
        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 max-w-sm w-full relative transform scale-90 transition-all duration-300 shadow-2xl border border-white/20">
            <div class="text-center">
                <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2"><?php echo $lang == 'en' ? 'Subscribed!' : 'Abone Olundu!'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                    <?php echo $lang == 'en' ? 'You will receive alerts for new lost & found items.' : 'Yeni kayıp eşya ilanları için bildirim alacaksınız.'; ?>
                </p>
                <button onclick="closeSubModal()" class="mt-8 w-full py-4 rounded-2xl bg-amber-500 text-white font-bold hover:bg-amber-600 transition-all">
                    <?php echo $lang == 'en' ? 'Understood' : 'Anlaşıldı'; ?>
                </button>
            </div>
        </div>
    </div>

    <script>
    function markFound(id) {
        if(!confirm('<?php echo $lang == 'en' ? 'Mark this item as found? It will be moved to the Found section.' : 'Bu eşyayı bulundu olarak işaretleyelim mi?'; ?>')) return;
        fetch('api/mark_lost_item_found.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                if(typeof showToast === 'function') showToast('<?php echo $lang == 'en' ? 'Item marked as found!' : 'Eşya bulundu olarak işaretlendi!'; ?>', 'success');
                location.reload();
            } else alert(data.message || 'Error');
        });
    }

    function applyFilters() {
        const statusFilter = document.getElementById('filter-status').value;
        const categoryFilter = document.getElementById('filter-category').value;
        const locationFilter = (document.getElementById('filter-location').value || '').toLowerCase();
        const cards = document.querySelectorAll('.item-card');
        let visibleCount = 0;
        cards.forEach(card => {
            const status = card.dataset.status || '';
            const category = card.dataset.category || '';
            const location = card.dataset.location || '';
            let show = true;
            if (statusFilter && status !== statusFilter) show = false;
            if (categoryFilter && category !== categoryFilter) show = false;
            if (locationFilter && !location.includes(locationFilter)) show = false;
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        const container = document.getElementById('items-container');
        let noResults = document.getElementById('no-filter-results');
        if (visibleCount === 0 && container) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.id = 'no-filter-results';
                noResults.className = 'text-center py-10 text-slate-400';
                noResults.innerHTML = '<i class="fas fa-filter text-3xl mb-2"></i><p class="font-bold"><?php echo $lang == "en" ? "No results match your filters" : "Filtrelerinize uygun sonuç yok"; ?></p>';
                container.appendChild(noResults);
            }
        } else if (noResults) noResults.remove();
    }

    function resetFilters() {
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-location').value = '';
        applyFilters();
    }

    function toggleSubscription(service, btn) {
        fetch('api/subscribe.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ service: service })
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                const icon = btn.querySelector('i');
                if(data.subscribed) {
                    icon.classList.remove('far'); icon.classList.add('fas');
                    if(!btn.querySelector('span')) {
                        const badge = document.createElement('span');
                        badge.className = 'absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white dark:border-slate-800';
                        btn.appendChild(badge);
                    }
                    document.getElementById('sub-success-modal').classList.remove('opacity-0','pointer-events-none');
                    document.getElementById('sub-success-modal').classList.add('opacity-100');
                } else {
                    icon.classList.remove('fas'); icon.classList.add('far');
                    const b = btn.querySelector('span'); if(b) b.remove();
                }
            } else if(data.message === 'Login required') location.href = 'login';
        });
    }

    function closeSubModal() {
        document.getElementById('sub-success-modal').classList.add('opacity-0','pointer-events-none');
        document.getElementById('sub-success-modal').classList.remove('opacity-100');
    }
    </script>
</body>
</html>
