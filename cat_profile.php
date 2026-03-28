<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
session_start();

$cat_id = $_GET['id'] ?? null;
if (!$cat_id) { header('Location: catdex'); exit; }

// Fetch cat details
$stmt = $pdo->prepare("SELECT * FROM cats WHERE id = ?");
$stmt->execute([$cat_id]);
$cat = $stmt->fetch();

if (!$cat) { header('Location: catdex'); exit; }

// Check if collected
$is_collected = false;
$collection_data = null;
if (isset($_SESSION['user_id'])) {
    $c_stmt = $pdo->prepare("SELECT * FROM user_cat_collection WHERE user_id = ? AND cat_id = ?");
    $c_stmt->execute([$_SESSION['user_id'], $cat_id]);
    $collection_data = $c_stmt->fetch();
    $is_collected = (bool)$collection_data;
}

// Fetch community photos (approved only)
$gallery_stmt = $pdo->prepare("SELECT user_photo, found_at, u.username, u.avatar 
                               FROM user_cat_collection c 
                               JOIN users u ON c.user_id = u.id 
                               WHERE cat_id = ? AND user_photo IS NOT NULL AND c.status = 'approved'
                               ORDER BY found_at DESC LIMIT 10");
$gallery_stmt->execute([$cat_id]);
$gallery = $gallery_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cat['name']); ?> | Kalkan Catdex</title>
    <?php include 'includes/header_css.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
</head>
<body class="bg-amber-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 max-w-lg">
        
        <!-- Navbar -->
        <a href="catdex" class="absolute top-24 left-4 w-10 h-10 bg-white/50 backdrop-blur rounded-full flex items-center justify-center text-slate-800 shadow-sm z-50">
            <i class="fas fa-arrow-left"></i>
        </a>

        <!-- Cat Card -->
        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 dark:border-slate-700 relative">
            
            <!-- Hero Image -->
            <div class="h-80 w-full relative">
                <?php if($cat['master_photo']): ?>
                    <img src="<?php echo $cat['master_photo']; ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-700">
                        <i class="fas fa-cat text-8xl text-slate-300"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Rarity Badge -->
                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg
                    <?php echo $cat['rarity'] == 'legendary' ? 'text-amber-500' : ($cat['rarity'] == 'rare' ? 'text-blue-500' : 'text-slate-500'); ?>">
                    <?php echo $cat['rarity']; ?>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8 -mt-10 bg-white dark:bg-slate-800 rounded-t-[2.5rem] relative z-10">
                
                <!-- Status Badge -->
                <?php if($is_collected): ?>
                <div class="absolute -top-6 right-8 bg-green-500 text-white w-12 h-12 flex items-center justify-center rounded-full shadow-lg border-4 border-white dark:border-slate-800 text-xl animate-bounce">
                    <i class="fas fa-check"></i>
                </div>
                <?php endif; ?>

                <h1 class="text-4xl font-black text-slate-800 dark:text-white mb-2 leading-tight">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </h1>
                
                <div class="flex items-center gap-2 text-slate-500 font-bold text-sm mb-6">
                    <i class="fas fa-map-marker-alt text-amber-500"></i>
                    <?php echo htmlspecialchars($cat['location']); ?>
                </div>

                <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-3xl mb-8 font-medium leading-relaxed text-slate-600 dark:text-slate-300">
                    <?php echo nl2br(htmlspecialchars($cat['description'])); ?>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-2xl">
                        <div class="text-xs font-bold uppercase text-green-600 dark:text-green-400 mb-1">Likes</div>
                        <div class="font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($cat['likes']); ?></div>
                    </div>
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-2xl">
                        <div class="text-xs font-bold uppercase text-red-600 dark:text-red-400 mb-1">Dislikes</div>
                        <div class="font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($cat['dislikes']); ?></div>
                    </div>
                </div>

                <!-- Action Area -->
                <?php if(!$is_collected): ?>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="space-y-4">
                            <label class="block w-full py-5 rounded-3xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xl shadow-xl shadow-amber-500/30 transform active:scale-95 transition-all text-center cursor-pointer flex items-center justify-center gap-3">
                                <input type="file" id="proofPhoto" accept="image/*" capture="environment" class="hidden" onchange="uploadProof(this)">
                                <i class="fas fa-camera text-2xl"></i>
                                <?php echo $lang == 'en' ? 'Take Photo & Collect' : 'Fotoğraf Çek & Yakala'; ?>
                            </label>
                            <p class="text-center text-xs text-slate-400 font-bold">
                                <?php echo $lang == 'en' ? 'Proof photo required (Admin approved)' : 'Kanıt fotoğrafı zorunludur (Admin onaylı)'; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <a href="login" class="block w-full py-4 rounded-3xl bg-slate-900 text-white font-bold text-center">
                            <?php echo $lang == 'en' ? 'Login to Collect' : 'Yakalmak için Giriş Yap'; ?>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 bg-green-50 dark:bg-green-900/20 rounded-3xl border border-green-100 dark:border-green-800">
                        <p class="text-green-600 dark:text-green-400 font-black text-lg">
                            <?php echo $lang == 'en' ? 'Cat Collected!' : 'Kedi Yakalandı!'; ?>
                        </p>
                        <p class="text-slate-400 text-xs mt-1">
                            <?php echo date('d M Y - H:i', strtotime($collection_data['found_at'])); ?>
                        </p>
                        <div class="mt-2 text-xs font-bold uppercase tracking-wider <?php echo $collection_data['status'] == 'approved' ? 'text-green-500' : ($collection_data['status'] == 'rejected' ? 'text-red-500' : 'text-amber-500'); ?>">
                            <?php echo strtoupper($collection_data['status']); ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Community Sightings (Comment Style) -->
        <?php if(!empty($gallery)): ?>
        <div class="mt-8">
            <h3 class="font-black text-slate-800 dark:text-white text-xl mb-4 px-2">
                <?php echo $lang == 'en' ? 'Community Sightings' : 'Yakalananlar'; ?>
            </h3>
            <div class="space-y-3">
                <?php foreach($gallery as $g): ?>
                <div class="bg-white dark:bg-slate-800 p-3 rounded-2xl flex items-center justify-between shadow-sm border border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <?php echo renderAvatar($g['avatar'], ['size' => 'md', 'isBordered' => true]); ?>
                        <div>
                            <div class="font-bold text-slate-800 dark:text-white text-sm">
                                <?php echo htmlspecialchars($g['username']); ?>
                            </div>
                            <div class="text-xs text-amber-500 font-bold">
                                <i class="fas fa-trophy mr-1"></i>
                                <?php echo $lang == 'en' ? 'Congratulations!' : 'Tebrikler!'; ?>
                            </div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase"><?php echo date('d M Y', strtotime($g['found_at'])); ?></div>
                        </div>
                    </div>
                    <!-- Small Proof Thumbnail -->
                    <div onclick="openLightbox('<?php echo $g['user_photo']; ?>')" class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden cursor-zoom-in border border-slate-200 dark:border-slate-600">
                        <img src="<?php echo $g['user_photo']; ?>" class="w-full h-full object-cover">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Simple Lightbox -->
        <div id="lightbox" class="fixed inset-0 z-[100] bg-black/90 hidden flex items-center justify-center p-4" onclick="this.classList.add('hidden')">
             <img id="lightbox-img" src="" class="max-w-full max-h-full rounded-lg shadow-2xl">
        </div>
        
    </main>
    <script>
    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').classList.remove('hidden');
    }
    </script>

    </main>

    <script>
    const catId = <?php echo $cat_id; ?>;

    function collectCat() {
        const btn = document.getElementById('collectBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('api/collect_cat.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cat_id: catId})
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: { y: 0.6 }
                });
                btn.classList.replace('bg-amber-500', 'bg-green-500');
                btn.innerHTML = '<i class="fas fa-check"></i> <?php echo $lang == 'en' ? 'GOTCHA!' : 'YAKALANDI!'; ?>';
                setTimeout(() => location.reload(), 2000);
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    function uploadProof(input) {
        if(!input.files || !input.files[0]) return;
        
        const formData = new FormData();
        formData.append('cat_id', catId);
        formData.append('photo', input.files[0]);

        // UI Feedback - Show loading
        const labelText = input.parentElement.querySelector('div, p, i, span:not(.sr-only)'); // Try to find text element
        const originalContent = input.parentElement.innerHTML;
        input.parentElement.classList.add('opacity-50', 'pointer-events-none');
        
        // Safe toast function
        const showToast = (msg, type) => {
            if(typeof showGlobalToast === 'function') {
                showGlobalToast(msg, type);
            } else {
                console.log('Toast not found, using alert:', msg);
                if(type === 'error') alert(msg);
            }
        };

        showToast('<?php echo $lang == 'en' ? 'Uploading proof...' : 'Fotoğraf yükleniyor...'; ?>', 'info');

        fetch('api/collect_cat.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text()) // Use text() first to debug non-JSON responses
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Server response is not JSON:', text);
                throw new Error('Server Return Error: ' + text.substring(0, 100));
            }
        })
        .then(data => {
            if(data.status === 'success') {
                if(typeof confetti === 'function') confetti({ particleCount: 200 });
                showToast('<?php echo $lang == 'en' ? 'Proof Uploaded! Waiting for approval.' : 'Yüklendi! Onay bekleniyor.'; ?>', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                alert(data.message); // Always alert errors
                input.parentElement.classList.remove('opacity-50', 'pointer-events-none');
                input.value = ''; // Reset input
            }
        })
        .catch(err => {
            console.error(err);
            alert('Upload Error: ' + err.message);
            input.parentElement.classList.remove('opacity-50', 'pointer-events-none');
            input.value = '';
        });
    }
    </script>

</body>
</html>
