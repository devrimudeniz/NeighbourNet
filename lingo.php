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
    <title>Lingo | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 pt-32 max-w-lg">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-teal-500 to-emerald-500 mb-2">
                Lingo
            </h1>
            <p class="text-slate-500 dark:text-slate-400">
                Your pocket Turkish phrasebook & translator.
            </p>
        </div>

        <!-- Translator Card -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-xl border border-slate-200 dark:border-slate-700 mb-8">
            <h2 class="font-bold text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-language text-teal-500"></i>
                Instant Translator
            </h2>
            
            <form id="translateForm" class="space-y-4">
                <textarea id="translateInput" rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-4 font-medium focus:outline-none focus:border-teal-500 transition-colors resize-none" placeholder="Type English or Turkish..."></textarea>
                
                <div class="flex items-center justify-between">
                    <select id="targetLang" class="bg-slate-100 dark:bg-slate-700 rounded-lg px-3 py-2 text-sm font-bold">
                        <option value="tr" selected>To Turkish</option>
                        <option value="en">To English</option>
                    </select>
                    
                    <button type="submit" id="translateBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/30 active:scale-95">
                        Translate
                    </button>
                </div>
            </form>

            <div id="translateResult" class="mt-4 hidden p-4 bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-100 dark:border-teal-900/50">
                <p class="text-sm text-slate-400 mb-1 font-bold uppercase text-[10px]">Translation</p>
                <p id="resultText" class="text-lg font-bold text-slate-800 dark:text-white"></p>
            </div>
        </div>

        <!-- Common Phrases Categories -->
        <div class="space-y-6">
            
            <!-- Transportation -->
            <div>
                <h2 class="font-bold text-lg text-slate-800 dark:text-white mb-3 px-2 flex items-center gap-2">
                    <i class="fas fa-bus text-teal-500"></i> Transportation
                </h2>
                <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700">
                    <?php
                    $transport_phrases = [
                        ['tr' => 'Kaş otobüsü ne zaman?', 'en' => 'When is the Kaş bus?', 'pron' => 'Kash auto-bu-su nay za-man?'],
                        ['tr' => 'Dalaman havaalanı ne kadar?', 'en' => 'How much to Dalaman airport?', 'pron' => 'Da-la-man ha-va-a-la-ni nay ka-dar?'],
                        ['tr' => 'En yakın taksi durağı nerede?', 'en' => 'Where is the nearest taxi stand?', 'pron' => 'En ya-kin tak-si du-ra-uh nay-reh-deh?'],
                        ['tr' => 'Kalkan merkeze gider mi?', 'en' => 'Does this go to Kalkan center?', 'pron' => 'Kal-kan mer-ke-zeh gi-der me?'],
                        ['tr' => 'Burada inebilir miyim?', 'en' => 'Can I get off here?', 'pron' => 'Bu-ra-da in-eh-bi-lir me-yim?'],
                        ['tr' => 'Bilet nereden alınır?', 'en' => 'Where do I get a ticket?', 'pron' => 'Bi-let nay-reh-den a-li-nir?'],
                        ['tr' => 'Fethiye\'ye nasıl giderim?', 'en' => 'How do I get to Fethiye?', 'pron' => 'Fet-hi-ye-ye na-sil gi-de-rim?']
                    ];
                    foreach ($transport_phrases as $phrase) {
                        echo '<div class="p-4 flex items-center justify-between group cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" onclick="playAudio(\''.$phrase['tr'].'\')">
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white">'.$phrase['tr'].'</p>
                                <p class="text-xs text-teal-600 dark:text-teal-400 font-medium italic mb-0.5">'.$phrase['pron'].'</p>
                                <p class="text-xs text-slate-400">'.$phrase['en'].'</p>
                            </div>
                            <i class="fas fa-volume-up text-slate-300 group-hover:text-teal-500 transition-colors"></i>
                        </div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Shopping & Prices -->
            <div>
                <h2 class="font-bold text-lg text-slate-800 dark:text-white mb-3 px-2 flex items-center gap-2">
                    <i class="fas fa-tag text-teal-500"></i> Shopping & Prices
                </h2>
                <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700">
                    <?php
                    $shopping_phrases = [
                        ['tr' => 'Bu ne kadar?', 'en' => 'How much is this?', 'pron' => 'Bu nay ka-dar?'],
                        ['tr' => 'İndirim yapar mısınız?', 'en' => 'Can you give a discount?', 'pron' => 'In-di-rim ya-par mi-si-niz?'],
                        ['tr' => 'Kredi kartı geçiyor mu?', 'en' => 'Do you accept credit cards?', 'pron' => 'Kre-di kar-ti geh-chi-yor mu?'],
                        ['tr' => 'Çok pahalı', 'en' => 'Too expensive', 'pron' => 'Chok pa-ha-li'],
                        ['tr' => 'Sadece bakıyorum, teşekkürler', 'en' => 'Just looking, thanks', 'pron' => 'Sa-deh-jeh ba-ki-yo-rum, te-shek-kur-ler'],
                        ['tr' => 'Nakit alıyor musunuz?', 'en' => 'Do you take cash?', 'pron' => 'Na-kit a-li-yor mu-su-nuz?'],
                        ['tr' => 'Bu fiyat son mu?', 'en' => 'Is this the final price?', 'pron' => 'Bu fi-yat son mu?'],
                        ['tr' => 'Daha ucuz var mı?', 'en' => 'Do you have something cheaper?', 'pron' => 'Da-ha u-juz var mi?'],
                        ['tr' => 'İki tane alırsam indirim var mı?', 'en' => 'Any discount if I buy two?', 'pron' => 'I-ki ta-neh a-lir-sam in-di-rim var mi?']
                    ];
                    foreach ($shopping_phrases as $phrase) {
                        echo '<div class="p-4 flex items-center justify-between group cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" onclick="playAudio(\''.$phrase['tr'].'\')">
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white">'.$phrase['tr'].'</p>
                                <p class="text-xs text-teal-600 dark:text-teal-400 font-medium italic mb-0.5">'.$phrase['pron'].'</p>
                                <p class="text-xs text-slate-400">'.$phrase['en'].'</p>
                            </div>
                            <i class="fas fa-volume-up text-slate-300 group-hover:text-teal-500 transition-colors"></i>
                        </div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Dining -->
            <div>
                <h2 class="font-bold text-lg text-slate-800 dark:text-white mb-3 px-2 flex items-center gap-2">
                    <i class="fas fa-utensils text-teal-500"></i> Dining
                </h2>
                <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm border border-slate-100 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700">
                    <?php
                    $dining_phrases = [
                        ['tr' => 'Menü lütfen', 'en' => 'Menu please', 'pron' => 'Meh-nu lut-fen'],
                        ['tr' => 'Hesap lütfen', 'en' => 'Bill please', 'pron' => 'He-sap lut-fen'],
                        ['tr' => 'Su alabilir miyim?', 'en' => 'Can I have some water?', 'pron' => 'Su a-la-bi-lir me-yim?'],
                        ['tr' => 'Acısız olsun', 'en' => 'No spicy please', 'pron' => 'A-ji-siz ol-sun'],
                        ['tr' => 'Vejetaryen yemek var mı?', 'en' => 'Do you have vegetarian food?', 'pron' => 'Ve-je-tar-yen ye-mek var mi?'],
                        ['tr' => 'Bir masa boş mu?', 'en' => 'Is there a table free?', 'pron' => 'Bir ma-sa bosh mu?'],
                        ['tr' => 'Rezervasyon yaptırdım', 'en' => 'I have a reservation', 'pron' => 'Re-zer-vas-yon yap-tir-dim'],
                        ['tr' => 'Wi-Fi şifresi nedir?', 'en' => 'What is the Wi‑Fi password?', 'pron' => 'Vi-fay shif-re-si nay-dir?'],
                        ['tr' => 'Tavsiyeniz nedir?', 'en' => 'What do you recommend?', 'pron' => 'Tav-si-ye-niz nay-dir?'],
                        ['tr' => 'Alerjim var', 'en' => 'I have an allergy', 'pron' => 'A-ler-jim var']
                    ];
                    foreach ($dining_phrases as $phrase) {
                        echo '<div class="p-4 flex items-center justify-between group cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" onclick="playAudio(\''.$phrase['tr'].'\')">
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white">'.$phrase['tr'].'</p>
                                <p class="text-xs text-teal-600 dark:text-teal-400 font-medium italic mb-0.5">'.$phrase['pron'].'</p>
                                <p class="text-xs text-slate-400">'.$phrase['en'].'</p>
                            </div>
                            <i class="fas fa-volume-up text-slate-300 group-hover:text-teal-500 transition-colors"></i>
                        </div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Emergency -->
            <div>
                <h2 class="font-bold text-lg text-slate-800 dark:text-white mb-3 px-2 flex items-center gap-2">
                    <i class="fas fa-ambulance text-red-500"></i> Emergency
                </h2>
                <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm border border-red-100 dark:border-red-900/50 divide-y divide-slate-100 dark:divide-slate-700">
                    <?php
                    $emergency_phrases = [
                        ['tr' => 'İmdat!', 'en' => 'Help!', 'pron' => 'Im-dat!'],
                        ['tr' => 'Doktor lazım', 'en' => 'I need a doctor', 'pron' => 'Dok-tor la-zim'],
                        ['tr' => 'Polis çağırın', 'en' => 'Call the police', 'pron' => 'Po-lis cha-ir-in'],
                        ['tr' => 'Kayboldum', 'en' => 'I am lost', 'pron' => 'Kay-bol-dum'],
                        ['tr' => 'Hastane nerede?', 'en' => 'Where is the hospital?', 'pron' => 'Has-ta-neh nay-reh-deh?'],
                        ['tr' => '112 arayın', 'en' => 'Call 112', 'pron' => 'Yuz on iki a-ra-yin'],
                        ['tr' => 'İlaç lazım', 'en' => 'I need medicine', 'pron' => 'I-lach la-zim'],
                        ['tr' => 'Eczane nerede?', 'en' => 'Where is the pharmacy?', 'pron' => 'Ej-za-neh nay-reh-deh?'],
                        ['tr' => 'Birisi yardım etsin', 'en' => 'Someone help me', 'pron' => 'Bi-ri-si yar-dim et-sin']
                    ];
                    foreach ($emergency_phrases as $phrase) {
                        echo '<div class="p-4 flex items-center justify-between group cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" onclick="playAudio(\''.$phrase['tr'].'\')">
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white">'.$phrase['tr'].'</p>
                                <p class="text-xs text-red-500 dark:text-red-400 font-medium italic mb-0.5">'.$phrase['pron'].'</p>
                                <p class="text-xs text-slate-400">'.$phrase['en'].'</p>
                            </div>
                            <i class="fas fa-volume-up text-slate-300 group-hover:text-red-500 transition-colors"></i>
                        </div>';
                    }
                    ?>
                </div>
            </div>

    </main>

    <script>
    function playAudio(text) {
        // Simple TTS using browser API
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'tr-TR';
        window.speechSynthesis.speak(utterance);
    }

    document.getElementById('translateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = document.getElementById('translateInput').value;
        const lang = document.getElementById('targetLang').value;
        const btn = document.getElementById('translateBtn');
        const result = document.getElementById('translateResult');
        const resultText = document.getElementById('resultText');

        if (!text) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const response = await fetch('api/translate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text: text, target_lang: lang })
            });
            const data = await response.json();

            btn.disabled = false;
            btn.innerHTML = 'Translate';

            if (data.success) {
                resultText.textContent = data.translated_text;
                result.classList.remove('hidden');
            } else {
                alert('Translation failed.');
            }
        } catch (error) {
            console.error(error);
            btn.disabled = false;
            btn.innerHTML = 'Translate';
        }
    });
    </script>
</body>
</html>
