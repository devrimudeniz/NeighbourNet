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
    <title><?php echo $lang == 'en' ? 'Culture Lens' : 'Kültür Rehberi'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-indigo-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 pt-32 max-w-lg">
        
        <div class="text-center mb-6">
            <div style="width:56px;height:56px;border-radius:16px;background:#fdf2f8;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-landmark" style="font-size:26px;color:#e11d48;"></i>
            </div>
            <h1 style="font-size:24px;font-weight:900;color:#0f172a;margin:0 0 6px;" class="dark:text-white">
                <?php echo $lang == 'en' ? 'Culture Lens' : 'Kültür Rehberi'; ?>
            </h1>
            <p style="font-size:13px;color:#64748b;margin:0;line-height:1.5;">
                <?php echo $lang == 'en' 
                    ? 'See something interesting? A ruin, a tomb, a symbol? Take a photo and AI tells you its history and story.' 
                    : 'İlginç bir şey mi gördünüz? Kalıntı, mezar, sembol? Fotoğrafını çekin, yapay zeka tarihini ve hikayesini anlatsın.'; ?>
            </p>
        </div>

        <!-- How It Works -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;margin-bottom:16px;" class="dark:bg-slate-800 dark:border-slate-700">
            <h3 style="font-size:13px;font-weight:800;color:#e11d48;margin:0 0 14px;text-transform:uppercase;letter-spacing:0.5px;">
                <?php echo $lang == 'en' ? 'How It Works' : 'Nasıl Çalışır?'; ?>
            </h3>
            <div style="display:flex;gap:8px;">
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#fdf2f8;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #e11d48;">
                        <i class="fas fa-camera" style="font-size:15px;color:#e11d48;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'Snap It' : 'Fotoğrafla'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'ruin or landmark' : 'kalıntı veya yer'; ?></p>
                </div>
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#fdf2f8;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #e11d48;">
                        <i class="fas fa-brain" style="font-size:15px;color:#e11d48;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'AI Recognizes' : 'AI Tanır'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'identifies place' : 'yeri tanımlar'; ?></p>
                </div>
                <div style="display:flex;align-items:center;padding-bottom:20px;"><i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:10px;"></i></div>
                <div style="flex:1;text-align:center;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#fdf2f8;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid #e11d48;">
                        <i class="fas fa-book-open" style="font-size:15px;color:#e11d48;"></i>
                    </div>
                    <p style="font-size:11px;font-weight:700;color:#0f172a;margin:0 0 2px;" class="dark:text-white"><?php echo $lang == 'en' ? 'Learn Story' : 'Hikayeyi Öğren'; ?></p>
                    <p style="font-size:10px;color:#94a3b8;margin:0;"><?php echo $lang == 'en' ? 'history & facts' : 'tarih ve bilgi'; ?></p>
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
                <!-- Mock ruin/landmark -->
                <div style="width:70px;flex-shrink:0;">
                    <div style="background:#fdf2f8;border:2px solid #fecdd3;border-radius:10px;padding:8px 6px;text-align:center;">
                        <div style="position:relative;margin-bottom:4px;">
                            <i class="fas fa-mountain" style="font-size:10px;color:#e11d48;position:absolute;top:0;left:50%;transform:translateX(-50%);"></i>
                            <i class="fas fa-archway" style="font-size:22px;color:#e11d48;margin-top:6px;display:block;"></i>
                        </div>
                        <div style="display:flex;justify-content:center;gap:2px;margin-top:2px;">
                            <div style="width:6px;height:10px;background:#fecdd3;border-radius:2px;"></div>
                            <div style="width:6px;height:14px;background:#fecdd3;border-radius:2px;"></div>
                            <div style="width:6px;height:8px;background:#fecdd3;border-radius:2px;"></div>
                        </div>
                    </div>
                    <p style="font-size:9px;color:#94a3b8;text-align:center;margin:4px 0 0;"><?php echo $lang == 'en' ? 'Rock tombs' : 'Kaya mezarları'; ?></p>
                </div>
                <div style="flex:1;">
                    <div style="background:#fff;border:1px solid #fecdd3;border-radius:10px;padding:10px;position:relative;" class="dark:bg-slate-700 dark:border-slate-600">
                        <div style="position:absolute;top:-6px;left:12px;background:#e11d48;color:#fff;font-size:8px;font-weight:800;padding:2px 8px;border-radius:4px;">AI</div>
                        <p style="font-size:11px;color:#334155;line-height:1.6;margin:4px 0 0;" class="dark:text-slate-200">
                            <?php echo $lang == 'en' 
                                ? '<b>Lycian Rock Tombs</b><br><b>Age:</b> ~2,400 years old (4th century BC)<br><b>Civilization:</b> Ancient Lycia<br><b>What are they:</b> Carved directly into cliff faces, these tombs were built for important Lycian citizens. The Lycians believed the dead should be close to the sky for winged spirits to carry them to the afterlife.<br><b>Fun fact:</b> The temple-style facades mimic real buildings of that era.'
                                : '<b>Likya Kaya Mezarları</b><br><b>Yaş:</b> ~2.400 yıl (MÖ 4. yüzyıl)<br><b>Medeniyet:</b> Antik Likya<br><b>Nedir:</b> Kayalara oyulmuş bu mezarlar, önemli Likya vatandaşları için yapılmıştır. Likyalılar ölülerin kanatlı ruhların onları öte dünyaya taşıması için gökyüzüne yakın olması gerektiğine inanırdı.<br><b>İlginç bilgi:</b> Tapınak tarzı cepheleri, o dönemin gerçek binalarını taklit eder.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tool Card -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border border-indigo-100 dark:border-indigo-900 mb-8">
            
            <form id="cultureForm" class="space-y-6">

                <!-- Image Upload -->
                <div>
                    <div id="image-preview-container" class="hidden relative w-full h-64 bg-slate-100 dark:bg-slate-900 rounded-2xl overflow-hidden group mb-4">
                        <img id="image-preview" src="" class="w-full h-full object-contain">
                        <button type="button" onclick="resetImage()" class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full shadow-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <label for="file-upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-indigo-300 dark:border-indigo-600 rounded-3xl cursor-pointer bg-indigo-50 hover:bg-indigo-100 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-indigo-600 transition-all">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-camera text-4xl text-indigo-600 dark:text-indigo-400 mb-3"></i>
                            <p class="mb-2 text-sm font-bold text-indigo-700 dark:text-indigo-300">
                                <?php echo $lang == 'en' ? 'Snap Photo' : 'Fotoğraf Çek'; ?>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Landmarks, objects, symbols</p>
                        </div>
                        <input id="file-upload" name="image" type="file" class="hidden" accept="image/*" capture="environment" onchange="handleImageSelect(event)" />
                    </label>
                </div>

                <!-- Action Button -->
                <button type="submit" id="submitBtn" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-black py-4 rounded-xl shadow-lg shadow-indigo-500/30 transition-all active:scale-95 flex items-center justify-center gap-2 hidden">
                    <i class="fas fa-book-open"></i>
                    <span><?php echo $lang == 'en' ? 'Learn History' : 'Tarihini Öğren'; ?></span>
                </button>

            </form>
        </div>

        <!-- Result Card -->
        <div id="result-card" class="hidden bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border-2 border-indigo-200 dark:border-indigo-800 animate-fade-in-up">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                <i class="fas fa-history text-indigo-500 text-xl"></i>
                <h3 class="font-bold text-slate-800 dark:text-white">Cultural Insight</h3>
            </div>
            
            <div id="result-content" class="prose dark:prose-invert max-w-none text-sm leading-relaxed">
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden flex flex-col items-center justify-center">
            <div class="w-16 h-16 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin mb-4"></div>
            <p class="text-white font-bold text-lg animate-pulse">Consulting Historians...</p>
        </div>

    </main>

    <script>
    const form = document.getElementById('cultureForm');
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
            const response = await fetch('api/analyze_culture.php', {
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
