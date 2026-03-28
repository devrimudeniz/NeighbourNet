<?php
/**
 * Welcome Tour Component
 * Shown ONLY after successful registration (new users)
 */

// Clear the flag so tour only shows once (check is done in index.php)
unset($_SESSION['show_welcome_tour']);

$user_name = !empty($_SESSION['full_name']) ? trim(explode(' ', $_SESSION['full_name'])[0]) : '';

$welcome_title_tr = $user_name ? $user_name . ', Kalkan Social\'e hoş geldin!' : 'Kalkan Social\'e hoş geldin!';
$welcome_title_en = $user_name ? 'Welcome to Kalkan Social, ' . $user_name . '!' : 'Welcome to Kalkan Social!';
$subtitle_tr = 'Kaydın başarıyla tamamlandı. Platformu keşfetmeye hazır mısın?';
$subtitle_en = 'Your registration is complete. Ready to explore?';

$step1_title = 'Anlarını Paylaş / Share Moments';
$step1_desc = 'Fotoğraf, video ve güncellemeler paylaş. Kalkan deneyimini toplulukla paylaş.';
$step1_desc_en = 'Post photos, videos and updates. Share your Kalkan experience with the community.';
$step2_title = 'Etkinlikleri Keşfet / Discover Events';
$step2_desc = 'Kalkan\'daki canlı müzik, parti ve sosyal etkinliklerden haberdar ol.';
$step2_desc_en = 'Find live music, parties and social events happening in Kalkan.';
$step3_title = 'Toplulukla Bağlan / Connect';
$step3_desc = 'Yerellere ve ziyaretçilere takip at, yeni insanlarla tanış.';
$step3_desc_en = 'Follow locals and visitors, meet new people.';
$step4_title = 'İşletmeleri Keşfet / Explore';
$step4_desc = 'Restoranlar, oteller ve hizmetleri keşfet. QR menüler, rezervasyonlar ve daha fazlası.';
$step4_desc_en = 'Discover restaurants, hotels and services. QR menus, reservations and more.';
$next_btn = 'İleri / Next';
$prev_btn = 'Geri / Back';
$start_btn = 'Başla! / Get Started!';
$skip_btn = 'Atla / Skip';

// Use the already-set $lang from lang.php; fallback to 'en' for guests
if (!isset($lang)) {
    $lang = $_SESSION['language'] ?? 'en';
}
$is_en = ($lang === 'en');
?>

<!-- Welcome Tour Modal (shown only after successful registration) -->
<div id="welcome-tour-modal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all border border-white/20">
        <!-- Header with confetti -->
        <div class="bg-gradient-to-r from-pink-500 via-purple-500 to-violet-500 p-6 text-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-20">
                <div class="confetti-piece" style="left: 10%; animation-delay: 0s;"></div>
                <div class="confetti-piece" style="left: 30%; animation-delay: 0.3s;"></div>
                <div class="confetti-piece" style="left: 50%; animation-delay: 0.6s;"></div>
                <div class="confetti-piece" style="left: 70%; animation-delay: 0.9s;"></div>
                <div class="confetti-piece" style="left: 90%; animation-delay: 1.2s;"></div>
            </div>
            <button type="button" id="tour-skip" class="absolute top-3 right-3 text-white/80 hover:text-white text-sm font-medium z-20"><?php echo htmlspecialchars($skip_btn); ?></button>
            <h2 id="tour-title" class="text-xl sm:text-2xl font-bold text-white relative z-10">
                <?php echo htmlspecialchars($is_en ? $welcome_title_en : $welcome_title_tr); ?>
            </h2>
            <p class="text-white/90 text-sm mt-2 relative z-10"><?php echo htmlspecialchars($is_en ? $subtitle_en : $subtitle_tr); ?></p>
        </div>
        
        <!-- Tour Steps Content -->
        <div id="tour-content" class="p-6">
            <!-- Language Selection Step (New Step 0) -->
            <div id="tour-step-0" class="tour-step active">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-full">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-center text-slate-800 dark:text-white mb-4">Choose Your Language / Dilinizi Seçin</h3>
                <p class="text-slate-600 dark:text-slate-300 text-center mb-6 text-sm">Select your preferred language / Tercih ettiğiniz dili seçin</p>
                
                <div class="flex flex-col gap-3">
                    <button onclick="selectLanguage('tr', event)" class="lang-option flex items-center gap-4 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all bg-white dark:bg-slate-800 hover:shadow-lg">
                        <span class="text-3xl">🇹🇷</span>
                        <div class="text-left flex-1">
                            <div class="font-bold text-slate-800 dark:text-white">Türkçe</div>
                            <div class="text-xs text-slate-500">Turkish</div>
                        </div>
                        <i class="fas fa-check text-blue-500 opacity-0 lang-check-tr"></i>
                    </button>
                    
                    <button onclick="selectLanguage('en', event)" class="lang-option flex items-center gap-4 p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all bg-white dark:bg-slate-800 hover:shadow-lg">
                        <span class="text-3xl">🇬🇧</span>
                        <div class="text-left flex-1">
                            <div class="font-bold text-slate-800 dark:text-white">English</div>
                            <div class="text-xs text-slate-500">İngilizce</div>
                        </div>
                        <i class="fas fa-check text-blue-500 opacity-0 lang-check-en"></i>
                    </button>
                </div>
            </div>
            
            <div id="tour-step-1" class="tour-step hidden">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-pink-100 dark:bg-pink-900/30 rounded-full">
                    <svg class="w-8 h-8 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-slate-800 dark:text-white mb-2"><?php echo htmlspecialchars($step1_title); ?></h3>
                <p class="text-slate-600 dark:text-slate-300 text-center text-sm"><?php echo htmlspecialchars($is_en ? $step1_desc_en : $step1_desc); ?></p>
            </div>
            
            <div id="tour-step-2" class="tour-step hidden">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                    <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-slate-800 dark:text-white mb-2"><?php echo htmlspecialchars($step2_title); ?></h3>
                <p class="text-slate-600 dark:text-slate-300 text-center text-sm"><?php echo htmlspecialchars($is_en ? $step2_desc_en : $step2_desc); ?></p>
            </div>
            
            <div id="tour-step-3" class="tour-step hidden">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-slate-800 dark:text-white mb-2"><?php echo htmlspecialchars($step3_title); ?></h3>
                <p class="text-slate-600 dark:text-slate-300 text-center text-sm"><?php echo htmlspecialchars($is_en ? $step3_desc_en : $step3_desc); ?></p>
            </div>
            
            <div id="tour-step-4" class="tour-step hidden">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-violet-100 dark:bg-violet-900/30 rounded-full">
                    <svg class="w-8 h-8 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-center text-slate-800 dark:text-white mb-2"><?php echo htmlspecialchars($step4_title); ?></h3>
                <p class="text-slate-600 dark:text-slate-300 text-center text-sm"><?php echo htmlspecialchars($is_en ? $step4_desc_en : $step4_desc); ?></p>
            </div>
        </div>
        
        <!-- Progress Dots -->
        <div class="flex justify-center gap-2 pb-4">
            <div class="tour-dot active w-2 h-2 rounded-full bg-pink-500 transition-all"></div>
            <div class="tour-dot w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600 transition-all"></div>
            <div class="tour-dot w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600 transition-all"></div>
            <div class="tour-dot w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600 transition-all"></div>
            <div class="tour-dot w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600 transition-all"></div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="flex justify-between items-center px-6 pb-6">
            <button id="tour-prev" class="hidden px-4 py-2 text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                ← <?php echo htmlspecialchars($prev_btn); ?>
            </button>
            <div class="flex-1"></div>
            <button id="tour-next" class="px-6 py-2 bg-gradient-to-r from-pink-500 to-violet-500 text-white font-semibold rounded-full hover:shadow-lg transform hover:scale-105 transition-all">
                <?php echo htmlspecialchars($next_btn); ?> →
            </button>
            <button id="tour-finish" class="hidden px-6 py-2 bg-gradient-to-r from-pink-500 to-violet-500 text-white font-semibold rounded-full hover:shadow-lg transform hover:scale-105 transition-all">
                <?php echo htmlspecialchars($start_btn); ?> 🎉
            </button>
        </div>
    </div>
</div>

<style>
.tour-step {
    animation: fadeIn 0.3s ease-out;
}

.tour-step.hidden {
    display: none;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.confetti-piece {
    position: absolute;
    width: 10px;
    height: 10px;
    background: white;
    animation: confetti-fall 3s ease-in-out infinite;
}

@keyframes confetti-fall {
    0% { transform: translateY(-20px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(100px) rotate(360deg); opacity: 0; }
}

.tour-dot.active {
    width: 24px;
    background: linear-gradient(to right, #ec4899, #8b5cf6);
}
</style>

<script>
(function() {
    var currentStep = 0;
    var totalSteps = 5;
    var selectedLanguage = '<?php echo $lang; ?>';
    
    var modal = document.getElementById('welcome-tour-modal');
    
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    
    var prevBtn = document.getElementById('tour-prev');
    var nextBtn = document.getElementById('tour-next');
    var finishBtn = document.getElementById('tour-finish');
    var skipBtn = document.getElementById('tour-skip');
    var dots = document.querySelectorAll('.tour-dot');
    
    function closeTour() {
        if (modal) {
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.95)';
            modal.style.transition = 'all 0.3s ease';
            setTimeout(function() { modal.remove(); }, 300);
        }
    }
    
    window.selectLanguage = function(lang, ev) {
        selectedLanguage = lang;
        var tr = document.querySelector('.lang-check-tr');
        var en = document.querySelector('.lang-check-en');
        if (tr) tr.style.opacity = lang === 'tr' ? '1' : '0';
        if (en) en.style.opacity = lang === 'en' ? '1' : '0';
        document.querySelectorAll('.lang-option').forEach(function(btn) { btn.style.borderColor = ''; });
        var opt = ev && ev.target ? ev.target.closest('.lang-option') : null;
        if (opt) opt.style.borderColor = '#3b82f6';
        document.cookie = 'language=' + lang + '; path=/; max-age=' + (365*24*60*60);
        setTimeout(function() {
            if (currentStep < totalSteps - 1) {
                currentStep++;
                showStep(currentStep);
            }
        }, 500);
    };
    
    function showStep(step) {
        currentStep = step;
        for (var i = 0; i < totalSteps; i++) {
            var stepEl = document.getElementById('tour-step-' + i);
            if (stepEl) {
                stepEl.classList.add('hidden');
                stepEl.classList.remove('active');
            }
        }
        var currentStepEl = document.getElementById('tour-step-' + step);
        if (currentStepEl) {
            currentStepEl.classList.remove('hidden');
            currentStepEl.classList.add('active');
        }
        dots.forEach(function(dot, index) {
            dot.classList.toggle('active', index === step);
        });
        if (prevBtn) prevBtn.classList.toggle('hidden', step <= 1);
        if (nextBtn) nextBtn.classList.toggle('hidden', step === 0 || step === totalSteps - 1);
        if (finishBtn) finishBtn.classList.toggle('hidden', step !== totalSteps - 1);
    }
    
    if (prevBtn) prevBtn.addEventListener('click', function() {
        if (currentStep > 1) { currentStep--; showStep(currentStep); }
    });
    
    if (nextBtn) nextBtn.addEventListener('click', function() {
        if (currentStep < totalSteps - 1) { currentStep++; showStep(currentStep); }
    });
    
    if (finishBtn) finishBtn.addEventListener('click', closeTour);
    
    if (skipBtn) skipBtn.addEventListener('click', closeTour);
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeTour();
        });
    }
    
    showStep(0);
})();
</script>
