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
    <title><?php echo $lang == 'en' ? 'Paperwork Helper' : 'Evrak Asistanı'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 pt-32 max-w-lg">
        
        <div class="text-center mb-6">
            <div style="width:56px;height:56px;border-radius:16px;background:#eef2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-file-contract" style="font-size:26px;color:#6366f1;"></i>
            </div>
            <h1 style="font-size:24px;font-weight:900;color:#0f172a;margin:0 0 6px;" class="dark:text-white">
                <?php echo $lang == 'en' ? 'Paperwork Helper' : 'Evrak Asistanı'; ?>
            </h1>
            <p style="font-size:13px;color:#64748b;margin:0;line-height:1.5;">
                <?php echo $lang == 'en' 
                    ? 'Snap a photo of any Turkish bill, letter, or document. AI reads and explains it in plain language.' 
                    : 'Fatura, resmi yazı veya herhangi bir belgenin fotoğrafını çekin. Yapay zeka okuyup sade bir dille açıklasın.'; ?>
            </p>
        </div>

        <!-- How It Works -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;margin-bottom:16px;" class="dark:bg-slate-800 dark:border-slate-700">
            <h3 style="font-size:13px;font-weight:800;color:#6366f1;margin:0 0 14px;text-transform:uppercase;letter-spacing:0.5px;">
                <?php echo $lang == 'en' ? 'How It Works' : 'Nasıl Çalışır?'; ?>
            </h3>
            <div style="display:flex;gap:8px;">
                <!-- Step 1 -->
                <div style="flex:1;text-align:center;position:relative;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #6366f1;">
                        <i class="fas fa-camera" style="font-size:16px;color:#6366f1;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'Take Photo' : 'Fotoğraf Çek'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'of bill or letter' : 'fatura veya yazı'; ?></p>
                </div>
                <!-- Arrow -->
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <!-- Step 2 -->
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #6366f1;">
                        <i class="fas fa-robot" style="font-size:16px;color:#6366f1;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'AI Reads It' : 'AI Okur'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'understands content' : 'içeriği anlar'; ?></p>
                </div>
                <!-- Arrow -->
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <!-- Step 3 -->
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #6366f1;">
                        <i class="fas fa-check" style="font-size:16px;color:#6366f1;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'You Understand' : 'Siz Anlarsınız'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'plain explanation' : 'sade açıklama'; ?></p>
                </div>
            </div>
        </div>

        <!-- Example Scenario -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:16px;margin-bottom:20px;" class="dark:bg-slate-800/50 dark:border-slate-700">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <i class="fas fa-lightbulb" style="color:#f59e0b;font-size:14px;"></i>
                <span style="font-size:12px;font-weight:800;color:#0f172a;" class="dark:text-white"><?php echo $lang == 'en' ? 'Example' : 'Örnek Kullanım'; ?></span>
            </div>
            <!-- Mock phone frame with document -->
            <div style="display:flex;gap:12px;align-items:flex-start;">
                <div style="width:70px;flex-shrink:0;">
                    <div style="background:#fff;border:2px solid #e2e8f0;border-radius:10px;padding:8px;text-align:center;">
                        <div style="background:#eef2ff;border-radius:6px;padding:8px 4px;margin-bottom:4px;">
                            <i class="fas fa-file-invoice" style="font-size:20px;color:#6366f1;"></i>
                        </div>
                        <div style="height:3px;background:#e2e8f0;border-radius:2px;margin:3px 0;"></div>
                        <div style="height:3px;background:#e2e8f0;border-radius:2px;margin:3px 0;width:80%;"></div>
                        <div style="height:3px;background:#e2e8f0;border-radius:2px;margin:3px 0;width:60%;"></div>
                        <div style="font-size:8px;color:#6366f1;font-weight:800;margin-top:4px;">₺247.50</div>
                    </div>
                    <p style="font-size:9px;color:#94a3b8;text-align:center;margin:4px 0 0;"><?php echo $lang == 'en' ? 'Water bill' : 'Su faturası'; ?></p>
                </div>
                <div style="flex:1;">
                    <div style="background:#fff;border:1px solid #c7d2fe;border-radius:10px;padding:10px;position:relative;" class="dark:bg-slate-700 dark:border-slate-600">
                        <div style="position:absolute;top:-6px;left:12px;background:#6366f1;color:#fff;font-size:8px;font-weight:800;padding:2px 8px;border-radius:4px;">AI</div>
                        <p style="font-size:11px;color:#334155;line-height:1.6;margin:4px 0 0;" class="dark:text-slate-200">
                            <?php echo $lang == 'en' 
                                ? '<b>Water Bill - January 2025</b><br>Amount: ₺247.50<br>Due date: Feb 15<br>This is your monthly water bill. The amount is <b>normal</b> for a household. Pay before the due date to avoid late fees.'
                                : '<b>Su Faturası - Ocak 2025</b><br>Tutar: ₺247,50<br>Son ödeme: 15 Şubat<br>Bu aylık su faturanızdır. Tutar bir ev için <b>normal</b> seviyededir. Gecikme cezası ödememek için son tarihe kadar ödeyin.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Card -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border border-slate-200 dark:border-slate-700 mb-8">
            
            <form id="uploadForm" class="space-y-4">
                
                <!-- Image Preview -->
                <div id="image-preview-container" class="hidden relative w-full h-64 bg-slate-100 dark:bg-slate-900 rounded-2xl overflow-hidden group">
                    <img id="image-preview" src="" class="w-full h-full object-contain">
                    <button type="button" onclick="resetForm()" class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full shadow-lg transform scale-0 group-hover:scale-100 transition-transform">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Upload Button -->
                <div id="upload-area">
                    <label for="file-upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-indigo-300 dark:border-indigo-700 rounded-3xl cursor-pointer bg-indigo-50 hover:bg-indigo-100 dark:bg-slate-900 dark:hover:bg-slate-800 transition-all">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-camera text-4xl text-indigo-500 mb-3"></i>
                            <p class="mb-2 text-sm text-indigo-600 dark:text-indigo-400 font-bold">
                                <?php echo $lang == 'en' ? 'Take Photo or Upload' : 'Fotoğraf Çek veya Yükle'; ?>
                            </p>
                            <p class="text-xs text-slate-400">JPG, PNG, WEBP</p>
                        </div>
                        <input id="file-upload" name="image" type="file" class="hidden" accept="image/*" capture="environment" onchange="handlePreviewImage(event)" />
                    </label>
                </div>

                <!-- Analyze Button -->
                <button type="submit" id="analyzeBtn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95 flex items-center justify-center gap-2 hidden">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <span><?php echo $lang == 'en' ? 'Analyze Document' : 'Belgeyi Analiz Et'; ?></span>
                </button>

            </form>
        </div>

        <!-- Result Card -->
        <div id="result-card" class="hidden bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border border-green-200 dark:border-green-900 animate-fade-in-up">
            <div class="flex items-center gap-3 mb-4 border-b border-slate-100 dark:border-slate-700 pb-3">
                <i class="fas fa-robot text-indigo-500 text-xl"></i>
                <h3 class="font-bold text-slate-800 dark:text-white">
                    <?php echo $lang == 'en' ? 'Analysis Result' : 'Analiz Sonucu'; ?>
                </h3>
            </div>
            <div id="result-content" class="prose dark:prose-invert max-w-none text-sm leading-relaxed">
                <!-- AI Output will go here -->
            </div>
            
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                <p class="text-xs text-slate-400 text-center">
                    <i class="fas fa-shield-alt mr-1"></i> 
                    <?php echo $lang == 'en' ? 'AI generated. Always double check important dates.' : 'Yapay zeka tarafından oluşturuldu. Önemli tarihleri kontrol edin.'; ?>
                </p>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
            <div class="w-20 h-20 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin mb-4"></div>
            <p class="text-white font-bold text-lg animate-pulse">
                <?php echo $lang == 'en' ? 'Analyzing Document...' : 'Belge Analiz Ediliyor...'; ?>
            </p>
            <p class="text-slate-400 text-sm mt-2">
                <?php echo $lang == 'en' ? 'Reading Turkish text...' : 'Türkçe metin okunuyor...'; ?>
            </p>
        </div>

    </main>

    <script>
    const uploadInput = document.getElementById('file-upload');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImage = document.getElementById('image-preview');
    const uploadArea = document.getElementById('upload-area');
    const analyzeBtn = document.getElementById('analyzeBtn');
    const resultCard = document.getElementById('result-card');
    const resultContent = document.getElementById('result-content');
    const loadingOverlay = document.getElementById('loading-overlay');

    function handlePreviewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
                uploadArea.classList.add('hidden');
                analyzeBtn.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function resetForm() {
        uploadInput.value = '';
        previewContainer.classList.add('hidden');
        uploadArea.classList.remove('hidden');
        analyzeBtn.classList.add('hidden');
        resultCard.classList.add('hidden');
    }

    document.getElementById('uploadForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!uploadInput.files[0]) return;

        const formData = new FormData();
        formData.append('image', uploadInput.files[0]);

        loadingOverlay.classList.remove('hidden');
        
        try {
            const response = await fetch('api/analyze_document.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            loadingOverlay.classList.add('hidden');

            if (data.success) {
                resultContent.innerHTML = data.analysis;
                resultCard.classList.remove('hidden');
                // Scroll to result
                resultCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }

        } catch (error) {
            console.error(error);
            loadingOverlay.classList.add('hidden');
            alert('Connection failed. Please try again.');
        }
    });
    </script>
</body>
</html>
