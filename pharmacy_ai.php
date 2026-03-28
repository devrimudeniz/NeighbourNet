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
    <title><?php echo $lang == 'en' ? 'Pharmacy Pal' : 'Eczane Yoldaşı'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-blue-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 pt-32 max-w-lg">
        
        <div class="text-center mb-6">
            <div style="width:56px;height:56px;border-radius:16px;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-prescription-bottle-alt" style="font-size:26px;color:#3b82f6;"></i>
            </div>
            <h1 style="font-size:24px;font-weight:900;color:#0f172a;margin:0 0 6px;" class="dark:text-white">
                <?php echo $lang == 'en' ? 'Pharmacy Pal' : 'Eczane Yoldaşı'; ?>
            </h1>
            <p style="font-size:13px;color:#64748b;margin:0;line-height:1.5;">
                <?php echo $lang == 'en' 
                    ? 'Need medicine but don\'t know the Turkish brand? Describe your symptoms or snap a medicine box — AI finds the equivalent.' 
                    : 'İlaç lazım ama Türk markasını bilmiyor musunuz? Şikayetinizi anlatın veya kutuyu fotoğraflayın — yapay zeka muadilini bulsun.'; ?>
            </p>
        </div>

        <!-- How It Works -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;margin-bottom:16px;" class="dark:bg-slate-800 dark:border-slate-700">
            <h3 style="font-size:13px;font-weight:800;color:#3b82f6;margin:0 0 14px;text-transform:uppercase;letter-spacing:0.5px;">
                <?php echo $lang == 'en' ? 'How It Works' : 'Nasıl Çalışır?'; ?>
            </h3>
            <div style="display:flex;gap:8px;">
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #3b82f6;">
                        <i class="fas fa-camera" style="font-size:15px;color:#3b82f6;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'Photo or Type' : 'Çek veya Yaz'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'medicine box' : 'ilaç kutusu'; ?></p>
                </div>
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #3b82f6;">
                        <i class="fas fa-pills" style="font-size:15px;color:#3b82f6;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'AI Identifies' : 'AI Tanımlar'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'finds equivalent' : 'muadilini bulur'; ?></p>
                </div>
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #3b82f6;">
                        <i class="fas fa-clinic-medical" style="font-size:15px;color:#3b82f6;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'Go to Pharmacy' : 'Eczaneye Git'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'with the name' : 'isim ile'; ?></p>
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
                <!-- Mock medicine box -->
                <div style="width:70px;flex-shrink:0;">
                    <div style="background:#eff6ff;border:2px solid #bfdbfe;border-radius:10px;padding:8px 6px;text-align:center;">
                        <i class="fas fa-box" style="font-size:18px;color:#3b82f6;margin-bottom:4px;display:block;"></i>
                        <p style="font-size:7px;font-weight:800;color:#1e40af;margin:0;">OMEPRAZOLE</p>
                        <p style="font-size:7px;color:#3b82f6;margin:0;">20mg</p>
                        <div style="height:1px;background:#bfdbfe;margin:4px 0;"></div>
                        <p style="font-size:6px;color:#64748b;margin:0;">UK Brand</p>
                    </div>
                    <p style="font-size:9px;color:#94a3b8;text-align:center;margin:4px 0 0;"><?php echo $lang == 'en' ? 'Your medicine' : 'İlacınız'; ?></p>
                </div>
                <div style="flex:1;">
                    <div style="background:#fff;border:1px solid #bfdbfe;border-radius:10px;padding:10px;position:relative;" class="dark:bg-slate-700 dark:border-slate-600">
                        <div style="position:absolute;top:-6px;left:12px;background:#3b82f6;color:#fff;font-size:8px;font-weight:800;padding:2px 8px;border-radius:4px;">AI</div>
                        <p style="font-size:11px;color:#334155;line-height:1.6;margin:4px 0 0;" class="dark:text-slate-200">
                            <?php echo $lang == 'en' 
                                ? '<b>Omeprazole 20mg</b> — Stomach acid reducer<br><b>Turkish equivalent:</b> "Omeprol 20mg" or "Losec 20mg"<br><b>At pharmacy say:</b> "Omeprol yirmi miligram"<br><b>Available:</b> Over the counter, ~₺45-80<br><b>Note:</b> Same active ingredient (omeprazole). Take before breakfast.'
                                : '<b>Omeprazole 20mg</b> — Mide asidi düzenleyici<br><b>Türk muadili:</b> "Omeprol 20mg" veya "Losec 20mg"<br><b>Eczanede:</b> "Omeprol yirmi miligram istiyorum" deyin<br><b>Fiyat:</b> Reçetesiz, ~₺45-80<br><b>Not:</b> Aynı etken madde. Kahvaltıdan önce alın.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool Card -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border border-blue-100 dark:border-blue-900 mb-8">
            
            <form id="pharmaForm" class="space-y-6">

                <!-- Image Upload -->
                <div>
                    <div id="image-preview-container" class="hidden relative w-full h-64 bg-slate-100 dark:bg-slate-900 rounded-2xl overflow-hidden group mb-4">
                        <img id="image-preview" src="" class="w-full h-full object-contain">
                        <button type="button" onclick="resetImage()" class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full shadow-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <label for="file-upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-blue-300 dark:border-blue-700 rounded-3xl cursor-pointer bg-blue-50 hover:bg-blue-100 dark:bg-slate-900 dark:hover:bg-slate-800 transition-all">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-camera text-4xl text-blue-500 mb-3"></i>
                            <p class="mb-2 text-sm text-blue-600 dark:text-blue-400 font-bold">
                                <?php echo $lang == 'en' ? 'Photo of Medicine Box' : 'İlaç Fotosu Çek'; ?>
                            </p>
                            <p class="text-xs text-slate-400">Make sure the text is clear</p>
                        </div>
                        <input id="file-upload" name="image" type="file" class="hidden" accept="image/*" capture="environment" onchange="handleImageSelect(event)" />
                    </label>
                </div>

                <!-- Action Button -->
                <button type="submit" id="submitBtn" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-black py-4 rounded-xl shadow-lg shadow-blue-500/30 transition-all active:scale-95 flex items-center justify-center gap-2 hidden">
                    <i class="fas fa-capsules"></i>
                    <span><?php echo $lang == 'en' ? 'Identify Medicine' : 'İlacı Tanımla'; ?></span>
                </button>

            </form>
        </div>

        <!-- Result Card -->
        <div id="result-card" class="hidden bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border-2 border-blue-200 dark:border-blue-800 animate-fade-in-up">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                <i class="fas fa-user-md text-blue-500 text-xl"></i>
                <h3 class="font-bold text-slate-800 dark:text-white">Analysis Result</h3>
            </div>
            
            <div id="result-content" class="prose dark:prose-invert max-w-none text-sm leading-relaxed">
            </div>

            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-900/50 flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-red-500 mt-1"></i>
                <p class="text-xs text-red-800 dark:text-red-300">
                    <?php echo $lang == 'en' ? 'AI can make mistakes. Always consult a doctor or pharmacist.' : 'Yapay zeka hata yapabilir. Her zaman doktora danışın.'; ?>
                </p>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
            <div class="w-16 h-16 border-4 border-blue-500/30 border-t-blue-500 rounded-full animate-spin mb-4"></div>
            <p class="text-white font-bold text-lg animate-pulse">Consulting AI Pharmacist...</p>
        </div>

    </main>

    <script>
    const form = document.getElementById('pharmaForm');
    const fileUpload = document.getElementById('file-upload');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImage = document.getElementById('image-preview');
    const submitBtn = document.getElementById('submitBtn');
    const resultCard = document.getElementById('result-card');
    const resultContent = document.getElementById('result-content');
    const loadingOverlay = document.getElementById('loading-overlay');
    const uploadLabel = document.querySelector('label[for="file-upload"]');

    function handleImageSelect(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
                uploadLabel.classList.add('hidden');
                submitBtn.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function resetImage() {
        fileUpload.value = '';
        previewContainer.classList.add('hidden');
        uploadLabel.classList.remove('hidden');
        submitBtn.classList.add('hidden');
        resultCard.classList.add('hidden');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (fileUpload.files.length === 0) return;

        const formData = new FormData();
        formData.append('image', fileUpload.files[0]);

        loadingOverlay.classList.remove('hidden');

        try {
            const response = await fetch('api/analyze_medicine.php', {
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
