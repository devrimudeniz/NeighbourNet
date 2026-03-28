<?php
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login?redirect=add_lost_item");
    exit();
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
    <title><?php echo $lang == 'en' ? 'Report Lost/Found Item' : 'Kayıp/Bulunan Eşya Bildir'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen">

    <div class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md fixed top-0 w-full z-10 border-b border-slate-200 dark:border-slate-800 flex items-center px-4">
        <a href="lost_found" class="text-2xl mr-4"><i class="fas fa-arrow-left"></i></a>
        <h1 class="font-bold text-lg truncate"><?php echo $lang == 'en' ? 'Report Lost/Found Item' : 'Kayıp/Bulunan Eşya Bildir'; ?></h1>
    </div>

    <main class="container mx-auto px-4 pt-24 max-w-lg pb-24">
        
        <form id="lostItemForm" class="space-y-6">
            
            <!-- Photo -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['photo']; ?></label>
                <label class="w-full h-40 sm:h-48 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-amber-500 transition-colors bg-white dark:bg-slate-800 overflow-hidden" id="dropzone">
                    <div id="upload-placeholder" class="text-center text-slate-400">
                        <i class="fas fa-camera text-3xl mb-2"></i>
                        <p class="text-sm font-bold"><?php echo $lang == 'en' ? 'Add Photo' : 'Fotoğraf Ekle'; ?></p>
                        <p class="text-xs opacity-60 mt-1"><?php echo $lang == 'en' ? 'Optional but helpful' : 'İsteğe bağlı ama faydalı'; ?></p>
                    </div>
                    <input type="file" name="photo" id="photoInput" class="hidden" accept="image/*">
                </label>
                <div id="preview-wrap" class="mt-2 hidden">
                    <img id="preview-img" src="" class="w-24 h-24 object-cover rounded-xl border border-slate-200 dark:border-slate-600" alt="">
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Status' : 'Durum'; ?></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/20 transition-all">
                        <input type="radio" name="status" value="lost" class="accent-amber-500" checked>
                        <span class="font-bold text-sm"><i class="fas fa-search text-amber-500 mr-1"></i> <?php echo $lang == 'en' ? 'Lost' : 'Kayıp'; ?></span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 transition-all">
                        <input type="radio" name="status" value="found" class="accent-emerald-500">
                        <span class="font-bold text-sm"><i class="fas fa-hand-holding text-emerald-500 mr-1"></i> <?php echo $lang == 'en' ? 'Found' : 'Bulundu'; ?></span>
                    </label>
                </div>
            </div>

            <!-- Item Name & Category -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Item Name' : 'Eşya Adı'; ?> <span class="text-red-500">*</span></label>
                    <input type="text" name="item_name" required class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-amber-500 outline-none" placeholder="<?php echo $lang == 'en' ? 'e.g. Blue wallet' : 'ör. Mavi cüzdan'; ?>">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                    <select name="category" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 outline-none">
                        <?php foreach ($cat_labels as $k => $v): ?>
                        <option value="<?php echo $k; ?>"><?php echo htmlspecialchars($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['location']; ?> <span class="text-red-500">*</span></label>
                <div class="relative">
                    <i class="fas fa-map-marker-alt absolute left-4 top-3.5 text-amber-500"></i>
                    <input type="text" name="location" required class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 pl-10 focus:border-amber-500 outline-none" placeholder="<?php echo $lang == 'en' ? 'Where was it lost/found?' : 'Nerede kaybedildi/bulundu?'; ?>">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['description']; ?></label>
                <textarea name="description" rows="3" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-amber-500 outline-none" placeholder="<?php echo $lang == 'en' ? 'Describe the item (color, brand, distinctive features...)' : 'Eşyayı tarif edin (renk, marka, belirgin özellikler...)'; ?>"></textarea>
            </div>

            <!-- Contact -->
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo $t['contact_number']; ?></label>
                <input type="tel" name="contact_phone" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:border-amber-500 outline-none" placeholder="<?php echo $t['contact_placeholder']; ?>">
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-amber-500 text-white py-4 rounded-xl font-bold shadow-lg shadow-amber-500/30 hover:bg-amber-600 transition-all flex justify-center items-center gap-2">
                <i class="fas fa-paper-plane"></i> <?php echo $lang == 'en' ? 'Publish' : 'Yayınla'; ?>
            </button>

        </form>

    </main>

    <script>
    // Photo preview
    document.getElementById('photoInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const wrap = document.getElementById('preview-wrap');
        const img = document.getElementById('preview-img');
        const placeholder = document.getElementById('upload-placeholder');
        if (file) {
            const r = new FileReader();
            r.onload = function() { img.src = r.result; wrap.classList.remove('hidden'); };
            r.readAsDataURL(file);
        } else {
            wrap.classList.add('hidden');
        }
    });

    document.getElementById('lostItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $lang == "en" ? "Publishing..." : "Yayınlanıyor..."; ?>';

        const formData = new FormData(this);
        fetch('api/add_lost_item.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                if (typeof showToast === 'function') showToast('<?php echo $lang == "en" ? "Item published!" : "İlan yayınlandı!"; ?>', 'success');
                window.location.href = 'lost_found';
            } else {
                alert(data.message || 'Error');
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        })
        .catch(() => {
            alert('<?php echo $t["error"] ?? "Error"; ?>');
            btn.disabled = false;
            btn.innerHTML = orig;
        });
    });
    </script>
</body>
</html>
