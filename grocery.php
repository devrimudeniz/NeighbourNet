<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

$lang = $_SESSION['language'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Grocery Scout' : 'Market Ajanı'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-green-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 pt-32 max-w-lg">
        
        <div class="text-center mb-6">
            <div style="width:56px;height:56px;border-radius:16px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-shopping-basket" style="font-size:26px;color:#22c55e;"></i>
            </div>
            <h1 style="font-size:24px;font-weight:900;color:#0f172a;margin:0 0 6px;" class="dark:text-white">
                <?php echo $lang == 'en' ? 'Grocery Scout' : 'Market Ajanı'; ?>
            </h1>
            <p style="font-size:13px;color:#64748b;margin:0;line-height:1.5;">
                <?php echo $lang == 'en' 
                    ? 'Can\'t find your usual products in Turkey? Type what you need or snap a photo — AI finds the local equivalent.' 
                    : 'Alışık olduğunuz ürünleri Türkiye\'de bulamıyor musunuz? Yazın veya fotoğraf çekin — yapay zeka muadilini bulsun.'; ?>
            </p>
        </div>

        <!-- How It Works -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;margin-bottom:16px;" class="dark:bg-slate-800 dark:border-slate-700">
            <h3 style="font-size:13px;font-weight:800;color:#22c55e;margin:0 0 14px;text-transform:uppercase;letter-spacing:0.5px;">
                <?php echo $lang == 'en' ? 'How It Works' : 'Nasıl Çalışır?'; ?>
            </h3>
            <div style="display:flex;gap:8px;">
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #22c55e;">
                        <i class="fas fa-keyboard" style="font-size:15px;color:#22c55e;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'Type or Photo' : 'Yaz veya Çek'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'product name' : 'ürün adı'; ?></p>
                </div>
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #22c55e;">
                        <i class="fas fa-search" style="font-size:15px;color:#22c55e;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'AI Matches' : 'AI Eşleştirir'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'finds equivalent' : 'muadili bulur'; ?></p>
                </div>
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #22c55e;">
                        <i class="fas fa-shopping-cart" style="font-size:15px;color:#22c55e;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'Go Buy It' : 'Alışverişe'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'with confidence' : 'güvenle alın'; ?></p>
                </div>
            </div>
        </div>

        <!-- Example Scenario -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:16px;margin-bottom:20px;" class="dark:bg-slate-800/50 dark:border-slate-700">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <i class="fas fa-lightbulb" style="color:#f59e0b;font-size:14px;"></i>
                <span style="font-size:12px;font-weight:800;color:#0f172a;" class="dark:text-white"><?php echo $lang == 'en' ? 'Example' : 'Örnek Kullanım'; ?></span>
            </div>
            <div style="display:flex;gap:12px;align-items:flex-start;">
                <!-- User question -->
                <div style="flex:1;">
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px;margin-bottom:8px;">
                        <p style="font-size:11px;color:#166534;margin:0;font-weight:600;">
                            <i class="fas fa-user" style="margin-right:4px;font-size:9px;"></i>
                            <?php echo $lang == 'en' ? 'User asks:' : 'Kullanıcı soruyor:'; ?>
                        </p>
                        <p style="font-size:13px;color:#0f172a;margin:4px 0 0;font-weight:700;" class="dark:text-white">
                            <?php echo $lang == 'en' ? '"Where can I find Double Cream?"' : '"Double Cream bulamıyorum?"'; ?>
                        </p>
                    </div>
                    <div style="background:#fff;border:1px solid #bbf7d0;border-radius:10px;padding:10px;position:relative;" class="dark:bg-slate-700 dark:border-slate-600">
                        <div style="position:absolute;top:-6px;left:12px;background:#22c55e;color:#fff;font-size:8px;font-weight:800;padding:2px 8px;border-radius:4px;">AI</div>
                        <p style="font-size:11px;color:#334155;line-height:1.6;margin:4px 0 0;" class="dark:text-slate-200">
                            <?php echo $lang == 'en' 
                                ? '<b>Turkish Equivalent:</b> "Krema" (cream section)<br><b>Best match:</b> Sek Krema or Pınar Krema<br><b>Where to find:</b> Migros, BIM, A101 — dairy aisle<br><b>Tip:</b> For cooking, look for "Yemeklik Krema". For whipping, look for "Çırpılabilir Krema".'
                                : '<b>Türk Muadili:</b> "Krema" (süt ürünleri reyonu)<br><b>En yakın eşleşme:</b> Sek Krema veya Pınar Krema<br><b>Nerede bulunur:</b> Migros, BIM, A101 — süt reyonu<br><b>İpucu:</b> Yemek için "Yemeklik Krema", çırpmak için "Çırpılabilir Krema" arayın.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool Card -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border border-green-200 dark:border-green-900 mb-8">
            
            <form id="groceryForm" class="space-y-6">
                
                <!-- Text Input -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">
                        <i class="fas fa-search text-green-500 mr-1"></i> 
                        <?php echo $lang == 'en' ? 'What are you looking for?' : 'Ne arıyorsunuz?'; ?>
                    </label>
                    <div class="relative">
                        <input type="text" id="queryInput" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 pl-10 font-medium focus:outline-none focus:border-green-500 transition-colors" placeholder="<?php echo $lang == 'en' ? 'e.g. Double Cream, Self-raising flour...' : 'Örn. Double Cream...'; ?>">
                        <i class="fas fa-keyboard absolute left-4 top-4 text-slate-400"></i>
                    </div>
                </div>

                <div class="relative flex items-center justify-center">
                    <div class="border-t border-slate-100 dark:border-slate-700 w-full absolute"></div>
                    <span class="bg-white dark:bg-slate-800 px-3 text-xs text-slate-400 font-bold relative z-10">OR SCAN PHOTO</span>
                </div>

                <!-- Image Upload -->
                <div>
                    <div id="image-preview-container" class="hidden relative w-full h-48 bg-slate-100 dark:bg-slate-900 rounded-2xl overflow-hidden group mb-4">
                        <img id="image-preview" src="" class="w-full h-full object-contain">
                        <button type="button" onclick="resetImage()" class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full shadow-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <label for="file-upload" class="flex items-center justify-center w-full p-4 border-2 border-dashed border-green-300 dark:border-green-700 rounded-xl cursor-pointer bg-green-50 hover:bg-green-100 dark:bg-slate-900 dark:hover:bg-slate-800 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/40 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-camera text-green-600 dark:text-green-400"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-bold text-green-700 dark:text-green-400">Snap Product Photo</p>
                                <p class="text-[10px] text-green-600/70 dark:text-green-500/70">Analyze package/label</p>
                            </div>
                        </div>
                        <input id="file-upload" name="image" type="file" class="hidden" accept="image/*" capture="environment" onchange="handleImageSelect(event)" />
                    </label>
                </div>

                <!-- Action Button -->
                <button type="submit" id="submitBtn" class="w-full bg-green-500 hover:bg-green-600 text-white font-black py-4 rounded-xl shadow-lg shadow-green-500/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                    <i class="fas fa-search-dollar"></i>
                    <span><?php echo $lang == 'en' ? 'Ask Grocery Scout' : 'Market Ajanına Sor'; ?></span>
                </button>

            </form>
        </div>

        <!-- Result Card -->
        <div id="result-card" class="hidden bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border-2 border-green-200 dark:border-green-900 animate-fade-in-up">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                    <i class="fas fa-lightbulb text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white leading-tight">Scout's Advice</h3>
                    <p class="text-xs text-slate-400">Culinary Match</p>
                </div>
            </div>
            
            <div id="result-content" class="prose dark:prose-invert max-w-none text-sm leading-relaxed">
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
            <div class="w-16 h-16 border-4 border-green-500/30 border-t-green-500 rounded-full animate-spin mb-4"></div>
            <p class="text-white font-bold text-lg animate-pulse">Scouting Shelves...</p>
        </div>

    </main>

    <script>
    const form = document.getElementById('groceryForm');
    const queryInput = document.getElementById('queryInput');
    const fileUpload = document.getElementById('file-upload');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImage = document.getElementById('image-preview');
    const resultCard = document.getElementById('result-card');
    const resultContent = document.getElementById('result-content');
    const loadingOverlay = document.getElementById('loading-overlay');

    function handleImageSelect(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function resetImage() {
        fileUpload.value = '';
        previewContainer.classList.add('hidden');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const text = queryInput.value.trim();
        const hasFile = fileUpload.files.length > 0;

        if (!text && !hasFile) {
            alert('Please enter a product name OR upload a photo.');
            return;
        }

        const formData = new FormData();
        formData.append('text', text);
        if (hasFile) {
            formData.append('image', fileUpload.files[0]);
        }

        loadingOverlay.classList.remove('hidden');
        resultCard.classList.add('hidden');

        try {
            const response = await fetch('api/analyze_product.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            loadingOverlay.classList.add('hidden');

            if (data.success) {
                resultContent.innerHTML = data.analysis;
                resultCard.classList.remove('hidden');
                resultCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error(error);
            loadingOverlay.classList.add('hidden');
            alert('Something went wrong. Please try again.');
        }
    });
    </script>
</body>
</html>
