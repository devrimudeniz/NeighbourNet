<?php
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';
// $t['pati_safe'] handled in lang
// $t['report_lost'] handled in lang

// Fetch Active Alerts
$stmt = $pdo->query("SELECT lp.*, u.username, u.full_name, u.avatar FROM lost_pets lp JOIN users u ON lp.user_id = u.id ORDER BY lp.status ASC, lp.created_at DESC");
$pets = $stmt->fetchAll();

// Fetch additional photos
$pet_ids = array_column($pets, 'id');
$pet_photos = [];
if (!empty($pet_ids)) {
    try {
        $placeholders = implode(',', array_fill(0, count($pet_ids), '?'));
        $p_stmt = $pdo->prepare("SELECT * FROM lost_pet_photos WHERE lost_pet_id IN ($placeholders)");
        $p_stmt->execute($pet_ids);
        $all_photos = $p_stmt->fetchAll();
        foreach ($all_photos as $ph) {
            $pet_photos[$ph['lost_pet_id']][] = $ph['photo_url'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet on server. Let's create it and migrate.
        if ($e->getCode() == '42S02') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS lost_pet_photos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lost_pet_id INT NOT NULL,
                photo_url VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (lost_pet_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Migrate existing photos from lost_pets.photo_url
            $pdo->exec("INSERT IGNORE INTO lost_pet_photos (lost_pet_id, photo_url) 
                        SELECT id, photo_url FROM lost_pets 
                        WHERE photo_url IS NOT NULL 
                        AND id NOT IN (SELECT lost_pet_id FROM lost_pet_photos)");
            
            // Re-run the logic after creation
            $p_stmt = $pdo->prepare("SELECT * FROM lost_pet_photos WHERE lost_pet_id IN ($placeholders)");
            $p_stmt->execute($pet_ids);
            $all_photos = $p_stmt->fetchAll();
            foreach ($all_photos as $ph) {
                $pet_photos[$ph['lost_pet_id']][] = $ph['photo_url'];
            }
        } else {
            throw $e;
        }
    }
}

// Check if user is subscribed
$is_subscribed = false;
if (isset($_SESSION['user_id'])) {
    try {
        $sub_stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND service = 'pati_safe'");
        $sub_stmt->execute([$_SESSION['user_id']]);
        $is_subscribed = $sub_stmt->fetch() ? true : false;
    } catch (PDOException $e) { $is_subscribed = false; }
}
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
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-orange-600 dark:text-orange-400"><?php echo $t['pati_safe']; ?></h1>
                <p class="text-slate-500 text-sm"><?php echo $lang == 'en' ? 'Let\'s find our lost friends together.' : 'Kayıp dostlarımızı birlikte bulalım.'; ?></p>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if(isset($_SESSION['user_id'])): ?>
                <button onclick="toggleSubscription('pati_safe', this)" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 hover:text-orange-500 hover:border-orange-500 transition-all relative" title="Subscribe to Alerts">
                    <i class="<?php echo $is_subscribed ? 'fas' : 'far'; ?> fa-bell text-lg"></i>
                    <?php if($is_subscribed): ?>
                    <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white dark:border-slate-800"></span>
                    <?php endif; ?>
                </button>
                <?php endif; ?>
                
                <a href="add_lost_pet" class="bg-red-500 text-white px-4 py-2 rounded-full font-bold shadow-lg shadow-red-500/30 hover:bg-red-600 transition-all text-sm">
                    <i class="fas fa-bullhorn mr-2"></i> <?php echo $t['report_lost_pet']; ?>
                </a>
            </div>
        </div>

        <!-- Filtering Panel -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 mb-6 shadow-sm border border-slate-100 dark:border-slate-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <!-- Status Filter -->
                <select id="filter-status" onchange="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-2.5 text-sm font-bold outline-none">
                    <option value=""><?php echo $lang == 'en' ? 'All Status' : 'Tüm Durumlar'; ?></option>
                    <option value="lost"><?php echo $lang == 'en' ? 'Lost' : 'Kayıp'; ?></option>
                    <option value="adoption"><?php echo $lang == 'en' ? 'Adoption' : 'Sahiplendirme'; ?></option>
                    <option value="found"><?php echo $lang == 'en' ? 'Found' : 'Bulundu'; ?></option>
                </select>
                
                <!-- Type Filter -->
                <select id="filter-type" onchange="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-2.5 text-sm font-bold outline-none">
                    <option value=""><?php echo $lang == 'en' ? 'All Types' : 'Tüm Türler'; ?></option>
                    <option value="cat"><?php echo $t['cat']; ?></option>
                    <option value="dog"><?php echo $t['dog']; ?></option>
                    <option value="bird"><?php echo $t['bird']; ?></option>
                    <option value="other"><?php echo $t['other']; ?></option>
                </select>
                
                <!-- Location Filter -->
                <select id="filter-location" onchange="applyFilters()" class="bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl p-2.5 text-sm font-bold outline-none">
                    <option value=""><?php echo $lang == 'en' ? 'All Locations' : 'Tüm Konumlar'; ?></option>
                    <option value="kalkan">Kalkan Merkez</option>
                    <option value="kalamar">Kalamar</option>
                    <option value="kiziltas">Kızıltaş</option>
                    <option value="bezirgan">Bezirgan</option>
                    <option value="islamlar">İslamlar</option>
                </select>
                
                <!-- Reset Button -->
                <button onclick="resetFilters()" class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl p-2.5 text-sm font-bold hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-undo"></i> <?php echo $lang == 'en' ? 'Reset' : 'Sıfırla'; ?>
                </button>
            </div>
        </div>

        <?php if(empty($pets)): ?>
            <div class="text-center py-20 opacity-50">
                <i class="fas fa-paw text-6xl mb-4"></i>
                <p class="font-bold"><?php echo $t['no_lost_pets']; ?></p>
                <p class="text-sm"><?php echo $t['hope_none']; ?></p>
            </div>
        <?php else: ?>
            <div class="space-y-4" id="pets-container">
                <?php foreach($pets as $pet): 
                    $pet_status_type = $pet['status_type'] ?? 'lost';
                    if ($pet['status'] == 'found') $pet_status_type = 'found';
                ?>
                <div class="pet-card bg-white dark:bg-slate-800 rounded-3xl p-4 shadow-sm border border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-4 <?php echo $pet['status'] == 'found' ? 'opacity-75 grayscale' : ''; ?>" 
                     data-status="<?php echo $pet_status_type; ?>" 
                     data-type="<?php echo $pet['pet_type']; ?>" 
                     data-location="<?php echo strtolower(htmlspecialchars($pet['location'])); ?>">
                    
                    <!-- Image -->
                    <div class="w-32 h-32 flex-shrink-0 bg-slate-100 dark:bg-slate-700 rounded-xl overflow-hidden relative cursor-pointer group" 
                         onclick="openGallery(<?php echo $pet['id']; ?>, <?php echo htmlspecialchars(json_encode($pet_photos[$pet['id']] ?? [$pet['photo_url']])); ?>)">
                        <?php if($pet['photo_url']): ?>
                            <img src="<?php echo htmlspecialchars($pet['photo_url']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-400">
                                <i class="fas fa-camera text-2xl"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(count($pet_photos[$pet['id']] ?? []) > 1): ?>
                            <div class="absolute bottom-2 right-2 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded-full backdrop-blur-sm">
                                <i class="fas fa-images mr-1"></i> <?php echo count($pet_photos[$pet['id']]); ?>
                            </div>
                        <?php endif; ?>

                        <?php if($pet['status'] == 'found'): ?>
                            <div class="absolute inset-0 bg-green-500/80 flex items-center justify-center text-white font-bold text-lg backdrop-blur-sm">
                                <i class="fas fa-check-circle mr-2"></i> <?php echo strtoupper($t['mark_found']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-xl flex items-center gap-2 text-slate-900 dark:text-orange-50">
                                    <?php echo htmlspecialchars($pet['pet_name']); ?>
                                    <span class="text-xs px-2 py-0.5 bg-slate-100 dark:bg-slate-700/50 rounded-full text-slate-600 dark:text-slate-400 uppercase">
                                        <?php echo $t[$pet['pet_type']] ?? $pet['pet_type']; ?>
                                    </span>
                                </h3>
                                <div class="text-red-600 dark:text-red-400 text-sm font-bold mt-1">
                                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($pet['location']); ?>
                                </div>
                            </div>
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $pet['user_id']): ?>
                                <div class="flex items-center gap-2">
                                    <?php if($pet['status'] != 'found'): ?>
                                    <a href="edit_lost_pet?id=<?php echo (int)$pet['id']; ?>" class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-3 py-1 rounded-full text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                        <i class="fas fa-pen mr-1"></i> <?php echo $lang == 'en' ? 'Edit' : 'Düzenle'; ?>
                                    </a>
                                    <button onclick="markFound(<?php echo $pet['id']; ?>)" class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-3 py-1 rounded-full text-xs font-bold hover:bg-green-200 transition-colors">
                                        <i class="fas fa-check mr-1"></i> <?php echo $t['mark_found']; ?>
                                    </button>
                                    <?php else: ?>
                                    <a href="edit_lost_pet?id=<?php echo (int)$pet['id']; ?>" class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-3 py-1 rounded-full text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                        <i class="fas fa-pen mr-1"></i> <?php echo $lang == 'en' ? 'Edit' : 'Düzenle'; ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <p class="text-slate-600 dark:text-slate-200 text-sm mt-3 line-clamp-2">
                            <?php echo htmlspecialchars($pet['description']); ?>
                        </p>

                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <img src="<?php echo $pet['avatar']; ?>" class="w-5 h-5 rounded-full" loading="lazy">
                                <span>@<?php echo htmlspecialchars($pet['username']); ?></span>
                                <span>&bull; <?php echo date('d.m H:i', strtotime($pet['created_at'])); ?></span>
                            </div>
                            <?php if($pet['status'] != 'found' && $pet['contact_phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($pet['contact_phone']); ?>" class="text-green-500 font-bold text-sm bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-lg">
                                    <i class="fas fa-phone-alt"></i> <?php echo $t['call']; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Social Sharing Buttons -->
                        <div class="mt-3 flex items-center gap-2 border-t border-slate-100 dark:border-slate-700 pt-3">
                            <?php 
                            $share_url = urlencode("https://kalkansocial.com/pati_safe?id=" . $pet['id']);
                            $share_text = urlencode(($pet_status_type == 'adoption' ? '💖 Sahiplendirme: ' : '🚨 Kayıp: ') . $pet['pet_name'] . ' - ' . $pet['location']);
                            ?>
                            <a href="https://wa.me/?text=<?php echo $share_text; ?>%20<?php echo $share_url; ?>" target="_blank" class="flex items-center gap-1.5 text-xs font-bold text-green-600 bg-green-50 dark:bg-green-900/20 px-3 py-1.5 rounded-lg hover:bg-green-100 transition-colors">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" class="flex items-center gap-1.5 text-xs font-bold text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                            <button onclick="openPosterModal('<?php echo htmlspecialchars(addslashes($pet['pet_name'])); ?>', '<?php echo htmlspecialchars(addslashes($pet['pet_type'])); ?>', '<?php echo htmlspecialchars(addslashes($pet['location'])); ?>', '<?php echo htmlspecialchars($pet['photo_url']); ?>', '<?php echo htmlspecialchars($pet['contact_phone']); ?>', '<?php echo $pet_status_type; ?>')" class="flex items-center gap-1.5 text-xs font-bold text-purple-600 bg-purple-50 dark:bg-purple-900/20 px-3 py-1.5 rounded-lg hover:bg-purple-100 transition-colors">
                                <i class="fas fa-portrait"></i> Poster
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Subscription Success Modal -->
    <div id="sub-success-modal" class="fixed inset-0 z-[60] flex items-center justify-center px-4 opacity-0 pointer-events-none transition-all duration-300">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeSubModal()"></div>
        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 max-w-sm w-full relative transform scale-90 transition-all duration-300 shadow-2xl border border-white/20">
            <div class="text-center">
                <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2"><?php echo $lang == 'en' ? 'Subscribed!' : 'Abone Olundu!'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                    <?php echo $lang == 'en' ? 'You will now receive alerts for lost pets immediately.' : 'Harika! Kayıp can dostlarımız için bir ilan açıldığında anında bildirim alacaksınız.'; ?>
                </p>
                
                <button onclick="closeSubModal()" class="mt-8 w-full py-4 rounded-2xl bg-orange-500 text-white font-bold hover:bg-orange-600 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-orange-500/25">
                    <?php echo $lang == 'en' ? 'Understood' : 'Anlaşıldı'; ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Gallery Modal -->
    <div id="galleryModal" class="fixed inset-0 z-[100] bg-black/95 backdrop-blur-sm hidden flex flex-col items-center justify-center p-4">
        <button onclick="closeGallery()" class="absolute top-6 right-6 text-white text-3xl hover:scale-110 transition-transform">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="relative w-full max-w-4xl aspect-[4/3] flex items-center justify-center">
            <img id="galleryImage" src="" class="max-w-full max-h-full object-contain rounded-xl shadow-2xl">
            
            <!-- Navigation -->
            <button onclick="prevImage()" id="prevBtn" class="absolute left-0 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 text-white p-4 rounded-r-2xl transition-all">
                <i class="fas fa-chevron-left text-2xl"></i>
            </button>
            <button onclick="nextImage()" id="nextBtn" class="absolute right-0 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 text-white p-4 rounded-l-2xl transition-all">
                <i class="fas fa-chevron-right text-2xl"></i>
            </button>
        </div>
        
        <!-- Thumbnails / Counter -->
        <div class="mt-8 flex gap-2 overflow-x-auto pb-4 max-w-full px-4" id="galleryThumbs"></div>
        <p class="text-white/60 text-sm mt-2" id="galleryCounter"></p>
    </div>
    
    <!-- HTML2Canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
    let currentPhotos = [];
    let currentIndex = 0;

    function openGallery(id, photos) {
        currentPhotos = photos;
        currentIndex = 0;
        document.getElementById('galleryModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        updateGallery();
    }

    function updateGallery() {
        const img = document.getElementById('galleryImage');
        const counter = document.getElementById('galleryCounter');
        const thumbs = document.getElementById('galleryThumbs');
        
        img.src = currentPhotos[currentIndex];
        counter.innerText = `${currentIndex + 1} / ${currentPhotos.length}`;
        
        // Update nav buttons
        document.getElementById('prevBtn').style.display = currentPhotos.length > 1 ? 'block' : 'none';
        document.getElementById('nextBtn').style.display = currentPhotos.length > 1 ? 'block' : 'none';

        // Update Thumbnails
        thumbs.innerHTML = '';
        currentPhotos.forEach((photo, idx) => {
            const thumb = document.createElement('img');
            thumb.src = photo;
            thumb.className = `w-16 h-16 object-cover rounded-lg cursor-pointer transition-all border-2 ${idx === currentIndex ? 'border-red-500 scale-110' : 'border-transparent opacity-50 hover:opacity-100'}`;
            thumb.onclick = () => { currentIndex = idx; updateGallery(); };
            thumbs.appendChild(thumb);
        });
    }

    function nextImage() {
        currentIndex = (currentIndex + 1) % currentPhotos.length;
        updateGallery();
    }

    function prevImage() {
        currentIndex = (currentIndex - 1 + currentPhotos.length) % currentPhotos.length;
        updateGallery();
    }

    function closeGallery() {
        document.getElementById('galleryModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (document.getElementById('galleryModal').classList.contains('hidden')) return;
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'Escape') closeGallery();
    });

    function markFound(id) {
        if(!confirm('<?php echo $t['found_confirm']; ?>')) return;
        
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('api/resolve_alert.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                showToast('<?php echo $t['found_success']; ?>', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(data.message, 'error');
            }
        });
    }

    // Filtering Functions
    function applyFilters() {
        const statusFilter = document.getElementById('filter-status').value;
        const typeFilter = document.getElementById('filter-type').value;
        const locationFilter = document.getElementById('filter-location').value;
        
        const cards = document.querySelectorAll('.pet-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const status = card.dataset.status || '';
            const type = card.dataset.type || '';
            const location = (card.dataset.location || '').toLowerCase();
            
            let show = true;
            
            if (statusFilter && status !== statusFilter) show = false;
            if (typeFilter && type !== typeFilter) show = false;
            if (locationFilter && !location.includes(locationFilter.toLowerCase())) show = false;
            
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // Show/hide no results message
        const container = document.getElementById('pets-container');
        let noResults = document.getElementById('no-filter-results');
        
        if (visibleCount === 0) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.id = 'no-filter-results';
                noResults.className = 'text-center py-10 text-slate-400';
                noResults.innerHTML = '<i class="fas fa-filter text-3xl mb-2"></i><p class="font-bold"><?php echo $lang == "en" ? "No results match your filters" : "Filtrelerinize uygun sonuç yok"; ?></p>';
                container.appendChild(noResults);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }
    
    function resetFilters() {
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-type').value = '';
        document.getElementById('filter-location').value = '';
        applyFilters();
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
                        badge.className = 'absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white dark:border-slate-800';
                        btn.appendChild(badge);
                    }
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

    // --- Poster Generator Logic ---
    function openPosterModal(name, type, location, imgUrl, phone, status) {
        const modal = document.getElementById('posterModal');
        const poster = document.getElementById('poster-canvas');
        
        // Populate Data
        document.getElementById('poster-name').innerText = name.toUpperCase();
        document.getElementById('poster-type').innerText = type.toUpperCase();
        document.getElementById('poster-location').innerText = location.toUpperCase();
        document.getElementById('poster-image').src = imgUrl; // Need proxy or CORS handling for external images preferably
        
        // Handle Status (Red for Lost, Green/Blue for Adoption)
        const header = document.getElementById('poster-header');
        const mainContainer = document.getElementById('poster-main');
        
        if (status === 'adoption') {
            header.innerText = 'SAHİPLENDİRME / ADOPT ME';
            header.className = 'w-full bg-green-500 text-white font-black text-3xl py-4 mb-6 shadow-lg tracking-widest';
            mainContainer.className = 'bg-white p-8 relative w-[400px] h-[700px] flex flex-col items-center text-center border-[12px] border-green-500 shadow-2xl';
        } else {
            header.innerText = 'KAYIP / LOST';
            header.className = 'w-full bg-red-600 text-white font-black text-4xl py-4 mb-6 shadow-lg tracking-widest';
            mainContainer.className = 'bg-white p-8 relative w-[400px] h-[700px] flex flex-col items-center text-center border-[12px] border-red-600 shadow-2xl';
        }

        const phoneContainer = document.getElementById('poster-phone-container');
        if (phone && phone !== 'null' && phone !== '') {
            document.getElementById('poster-phone').innerText = phone;
            phoneContainer.style.display = 'block';
        } else {
            phoneContainer.style.display = 'none';
        }

        modal.classList.remove('hidden');
    }

    function closePosterModal() {
        document.getElementById('posterModal').classList.add('hidden');
    }

    function downloadPoster() {
        const poster = document.getElementById('poster-main');
        const btn = document.getElementById('downloadPosterBtn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';
        btn.disabled = true;

        html2canvas(poster, {
            scale: 2, // High resolution
            useCORS: true, // Attempt to load external images
            backgroundColor: '#ffffff'
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'kalkansocial-poster-' + Date.now() + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            
            btn.innerHTML = '✅ Saved!';
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        }).catch(err => {
            console.error(err);
            alert('Error generating poster. Please try again.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
    </script>
    
    <!-- Poster Generator Modal -->
    <div id="posterModal" class="fixed inset-0 z-[200] bg-black/95 backdrop-blur-md hidden flex flex-col items-center justify-center p-4 overflow-y-auto">
        
        <div class="flex items-center gap-4 mb-4">
            <h3 class="text-white font-bold text-xl">Poster Preview</h3>
            <button onclick="closePosterModal()" class="w-8 h-8 rounded-full bg-white/20 text-white flex items-center justify-center hover:bg-white/40">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- The Poster (Canvas Source) -->
        <div class="scale-[0.5] sm:scale-[0.6] md:scale-75 lg:scale-90 origin-top shadow-2xl">
            <div id="poster-main" class="bg-white p-8 relative w-[400px] h-[700px] flex flex-col items-center text-center border-[12px] border-red-600 shadow-2xl">
                
                <!-- Header -->
                <div id="poster-header" class="w-full bg-red-600 text-white font-black text-4xl py-4 mb-6 shadow-lg tracking-widest">
                    KAYIP / LOST
                </div>

                <!-- Image -->
                <div class="relative w-64 h-64 mb-6 group">
                     <div class="absolute -inset-1 bg-gradient-to-r from-slate-200 to-slate-300 rounded-full blur opacity-50"></div>
                    <img id="poster-image" src="" crossorigin="anonymous" class="relative w-64 h-64 object-cover rounded-full border-4 border-slate-900 shadow-2xl">
                </div>
                
                <!-- Info -->
                <h2 id="poster-name" class="text-5xl font-black text-slate-900 mb-2 leading-none tracking-tight"></h2>
                
                <div class="flex items-center gap-2 mb-8">
                     <span id="poster-type" class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-black text-lg uppercase tracking-wider"></span>
                     <span class="text-slate-300 text-2xl">•</span>
                     <span id="poster-location" class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-black text-lg uppercase tracking-wider"></span>
                </div>
                
                <!-- Footer area -->
                <div class="mt-auto w-full">
                    <div id="poster-phone-container">
                        <div class="text-red-500 font-extrabold text-sm mb-1 uppercase tracking-widest px-4">Görenler Lütfen Arasın / Please Call</div>
                        <div id="poster-phone" class="text-4xl font-black text-white bg-slate-900 py-4 rounded-2xl mb-6 mx-2 shadow-xl tracking-wider">
                            <!-- Phone -->
                        </div>
                    </div>

                    <div class="flex flex-col items-center justify-center border-t-2 border-slate-100 pt-4 pb-2">
                        <div class="flex items-center gap-2 text-slate-400 font-bold text-lg">
                            <i class="fas fa-paw text-orange-500 text-xl"></i> 
                            <span class="tracking-tight">www.kalkansocial.com</span>
                        </div>
                        <div class="text-[10px] text-slate-300 font-bold mt-1 uppercase tracking-widest">Kalkan Community Support</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Actions -->
        <div class="fixed bottom-4 md:bottom-8 z-[210] w-full px-4 md:w-auto md:px-0">
             <button onclick="downloadPoster()" id="downloadPosterBtn" class="w-full md:w-auto bg-green-500 text-white px-6 py-3 md:px-8 md:py-4 rounded-xl md:rounded-full font-black text-base md:text-lg shadow-xl shadow-green-500/40 active:scale-95 md:hover:scale-110 transition-transform flex items-center justify-center gap-2 md:gap-3">
                <i class="fas fa-download"></i> 
                <span class="whitespace-nowrap"><?php echo $lang == 'en' ? 'Save to Gallery' : 'Galeriye Kaydet'; ?></span>
            </button>
        </div>

    </div>

</body>
</html>
