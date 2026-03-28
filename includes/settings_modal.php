<?php
/**
 * Settings Modal - Global (Theme, Language, Text Size, Quick Actions)
 * Included in header for logged-in users - available on all pages
 */
if (!isset($_SESSION['user_id'])) return;
?>
<script>
(function(){
    if (typeof window.openSettingsModal === 'function') return; // Already defined
    window.openSettingsModal = function() {
        const overlay = document.getElementById('settingsModalOverlay');
        const modal = document.getElementById('settingsModal');
        if (!overlay || !modal) return;
        overlay.classList.remove('hidden');
        modal.classList.remove('hidden');
        modal.offsetHeight;
        setTimeout(() => {
            overlay.classList.add('opacity-100');
            modal.style.opacity = '1';
            modal.style.transform = window.matchMedia('(min-width: 1024px)').matches ? 'translate(-50%, -50%)' : 'translateY(0)';
        }, 10);
        document.body.style.overflow = 'hidden';
    };
    window.closeSettingsModal = function() {
        const overlay = document.getElementById('settingsModalOverlay');
        const modal = document.getElementById('settingsModal');
        if (!overlay || !modal) return;
        overlay.classList.remove('opacity-100');
        modal.style.transform = window.matchMedia('(min-width: 1024px)').matches ? 'translate(-50%, -50%) scale(0.95)' : 'translateY(100%)';
        modal.style.opacity = window.matchMedia('(min-width: 1024px)').matches ? '0' : '1';
        document.body.style.overflow = '';
        setTimeout(() => {
            overlay.classList.add('hidden');
            modal.classList.add('hidden');
            modal.style.opacity = '';
            modal.style.transform = '';
        }, 300);
    };
    window.settingsSetLanguage = function(lang) {
        var q = new URLSearchParams(window.location.search);
        q.set('lang', lang);
        window.location.href = window.location.pathname + '?' + q.toString();
    };
    window.settingsSetTheme = function(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
        if (typeof updateSettingsThemeButtons === 'function') updateSettingsThemeButtons();
        document.cookie = "theme=" + theme + "; path=/; max-age=31536000";
        fetch('api/update_theme.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'theme=' + theme }).catch(function(){});
    };
    window.settingsSetTextScale = function(scale) {
        if (typeof window.applyTextScale === 'function') window.applyTextScale(scale);
        if (typeof updateSettingsTextScaleButtons === 'function') updateSettingsTextScaleButtons();
    };
    updateSettingsThemeButtons = function() {
        var isDark = document.documentElement.classList.contains('dark');
        var lb = document.getElementById('settings-theme-light-btn');
        var db = document.getElementById('settings-theme-dark-btn');
        if (lb) { lb.classList.toggle('ring-2', !isDark); lb.classList.toggle('ring-pink-500', !isDark); }
        if (db) { db.classList.toggle('ring-2', isDark); db.classList.toggle('ring-pink-500', isDark); }
    };
    updateSettingsTextScaleButtons = function() {
        var s = localStorage.getItem('textScale') || '100';
        ['100','112','125','140'].forEach(function(v){
            var btn = document.getElementById('settings-text-scale-' + v);
            if (btn) {
                var active = v === s;
                btn.classList.toggle('border-teal-500', active);
                btn.classList.toggle('ring-2', active);
                btn.classList.toggle('ring-teal-500', active);
                btn.classList.toggle('bg-teal-50', active);
                btn.classList.toggle('dark:bg-teal-900/20', active);
            }
        });
    };
    document.addEventListener('DOMContentLoaded', function(){
        if (typeof updateSettingsThemeButtons === 'function') updateSettingsThemeButtons();
        if (typeof updateSettingsTextScaleButtons === 'function') updateSettingsTextScaleButtons();
    });
})();
</script>
<!-- Settings Modal -->
<div id="settingsModalOverlay" class="fixed inset-0 bg-black/60 z-[100] hidden opacity-0 transition-opacity duration-300 backdrop-blur-sm" onclick="closeSettingsModal()"></div>
<div id="settingsModal" class="fixed inset-x-0 bottom-0 lg:inset-auto lg:top-1/2 lg:left-1/2 w-full lg:w-[500px] lg:max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-900 z-[110] shadow-2xl rounded-t-3xl lg:rounded-3xl transform translate-y-full transition-all duration-300 h-[85vh] max-h-[85vh] lg:h-auto lg:max-h-[90vh] border-t-4 lg:border-4 border-pink-500 hidden flex flex-col">
    <div class="lg:hidden flex justify-center pt-3 pb-1 shrink-0"><div class="w-12 h-1.5 bg-slate-300 dark:bg-slate-600 rounded-full"></div></div>
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 shrink-0">
        <h3 class="text-xl font-black flex items-center gap-2 text-slate-800 dark:text-white"><i class="fas fa-cog text-slate-500"></i> <?php echo $lang == 'en' ? 'Settings' : 'Ayarlar'; ?></h3>
        <button type="button" onclick="closeSettingsModal()" class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"><i class="fas fa-times text-red-500"></i></button>
    </div>
    <div class="p-5 overflow-y-auto overflow-x-hidden space-y-5 flex-1 min-h-0" style="max-height: calc(85vh - 130px); -webkit-overflow-scrolling: touch;">
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5">
            <h4 class="text-sm font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4 flex items-center gap-2"><?php echo heroicon('swatch', 'w-4 h-4'); ?> <?php echo $lang == 'en' ? 'Appearance' : 'Görünüm'; ?></h4>
            <div class="mb-4">
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 block"><?php echo $lang == 'en' ? 'Theme' : 'Tema'; ?></label>
                <div class="grid grid-cols-2 gap-3">
                    <button id="settings-theme-light-btn" onclick="settingsSetTheme('light')" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 hover:border-pink-300 transition-all"><?php echo heroicon('sun', 'w-8 h-8 text-amber-500'); ?><span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Light' : 'Açık'; ?></span></button>
                    <button id="settings-theme-dark-btn" onclick="settingsSetTheme('dark')" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-slate-800 dark:bg-slate-900 border-2 border-slate-600 dark:border-slate-500 hover:border-pink-500 transition-all"><?php echo heroicon('moon', 'w-8 h-8 text-indigo-400'); ?><span class="text-sm font-bold text-slate-300"><?php echo $lang == 'en' ? 'Dark' : 'Koyu'; ?></span></button>
                </div>
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 block flex items-center gap-2"><i class="fas fa-text-height text-teal-500"></i> <?php echo $lang == 'en' ? 'Text size' : 'Metin boyutu'; ?></label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="settingsSetTextScale('100')" id="settings-text-scale-100" class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-teal-400 transition-all"><span class="text-xs font-black text-slate-600 dark:text-slate-300">A</span><span class="text-[10px] font-bold text-slate-500"><?php echo $lang == 'en' ? 'Default' : 'Varsayılan'; ?></span></button>
                    <button type="button" onclick="settingsSetTextScale('112')" id="settings-text-scale-112" class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-teal-400 transition-all"><span class="text-sm font-black text-slate-600 dark:text-slate-300">A</span><span class="text-[10px] font-bold text-slate-500"><?php echo $lang == 'en' ? 'Large' : 'Büyük'; ?></span></button>
                    <button type="button" onclick="settingsSetTextScale('125')" id="settings-text-scale-125" class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-teal-400 transition-all"><span class="text-base font-black text-slate-600 dark:text-slate-300">A</span><span class="text-[10px] font-bold text-slate-500"><?php echo $lang == 'en' ? 'Larger' : 'Daha büyük'; ?></span></button>
                    <button type="button" onclick="settingsSetTextScale('140')" id="settings-text-scale-140" class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-600 hover:border-teal-400 transition-all"><span class="text-lg font-black text-slate-600 dark:text-slate-300">A</span><span class="text-[10px] font-bold text-slate-500"><?php echo $lang == 'en' ? 'Largest' : 'En büyük'; ?></span></button>
                </div>
            </div>
        </div>
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5">
            <h4 class="text-sm font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4 flex items-center gap-2"><?php echo heroicon('globe', 'w-4 h-4'); ?> <?php echo $lang == 'en' ? 'Language' : 'Dil'; ?></h4>
            <div class="grid grid-cols-2 gap-3">
                <button onclick="settingsSetLanguage('tr')" class="flex items-center justify-center gap-3 p-4 rounded-xl border-2 transition-all <?php echo $lang == 'tr' ? 'bg-pink-50 dark:bg-pink-900/20 border-pink-500 ring-2 ring-pink-500' : 'bg-white dark:bg-slate-700 border-slate-200 dark:border-slate-600 hover:border-pink-300'; ?>"><span class="text-2xl">🇹🇷</span><span class="text-sm font-bold text-slate-700 dark:text-slate-300">Türkçe</span></button>
                <button onclick="settingsSetLanguage('en')" class="flex items-center justify-center gap-3 p-4 rounded-xl border-2 transition-all <?php echo $lang == 'en' ? 'bg-pink-50 dark:bg-pink-900/20 border-pink-500 ring-2 ring-pink-500' : 'bg-white dark:bg-slate-700 border-slate-200 dark:border-slate-600 hover:border-pink-300'; ?>"><span class="text-2xl">🇬🇧</span><span class="text-sm font-bold text-slate-700 dark:text-slate-300">English</span></button>
            </div>
        </div>
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5">
            <h4 class="text-sm font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4 flex items-center gap-2"><?php echo heroicon('bolt', 'w-4 h-4'); ?> <?php echo $lang == 'en' ? 'Quick Actions' : 'Hızlı İşlemler'; ?></h4>
            <div class="space-y-2">
                <a href="edit_profile" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all"><?php echo heroicon('user', 'w-5 h-5 text-blue-500'); ?><span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Edit Profile' : 'Profili Düzenle'; ?></span><?php echo heroicon('chevron_right', 'w-4 h-4 text-slate-400 ml-auto'); ?></a>
                <a href="filters" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all"><?php echo heroicon('no_symbol', 'w-5 h-5 text-orange-500'); ?><span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Muted & Blocked' : 'Sessize Alınan ve Engellenenler'; ?></span><?php echo heroicon('chevron_right', 'w-4 h-4 text-slate-400 ml-auto'); ?></a>
                <a href="saved" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all"><?php echo heroicon('bookmark', 'w-5 h-5 text-yellow-500'); ?><span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Saved Items' : 'Kaydedilenler'; ?></span><?php echo heroicon('chevron_right', 'w-4 h-4 text-slate-400 ml-auto'); ?></a>
                <a href="notification_preferences" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-600 transition-all"><?php echo heroicon('bell', 'w-5 h-5 text-violet-500'); ?><span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo $lang == 'en' ? 'Notification preferences' : 'Bildirim tercihleri'; ?></span><?php echo heroicon('chevron_right', 'w-4 h-4 text-slate-400 ml-auto'); ?></a>
            </div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/10 rounded-2xl p-5 border border-red-100 dark:border-red-900/30">
            <h4 class="text-sm font-black uppercase tracking-wider text-red-500 dark:text-red-400 mb-4 flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> <?php echo $lang == 'en' ? 'Danger Zone' : 'Tehlikeli Bölge'; ?></h4>
            <a href="delete_account" class="flex items-center gap-3 p-3 rounded-xl bg-white dark:bg-slate-800 border border-red-200 dark:border-red-900/50 hover:bg-red-50 dark:hover:bg-red-900/30 transition-all group"><i class="fas fa-trash-alt text-red-500 group-hover:scale-110 transition-transform"></i><span class="text-sm font-bold text-red-600 dark:text-red-400"><?php echo $lang == 'en' ? 'Delete Account' : 'Hesabı Sil'; ?></span><i class="fas fa-chevron-right w-4 h-4 text-red-300 ml-auto"></i></a>
        </div>
        <a href="logout" class="flex items-center justify-center gap-2 w-full py-4 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-500 font-bold hover:bg-red-100 dark:hover:bg-red-900/40 transition-all border border-red-200 dark:border-red-800 shrink-0"><?php echo heroicon('logout', 'w-5 h-5'); ?> <?php echo $lang == 'en' ? 'Log Out' : 'Çıkış Yap'; ?></a>
        <div class="text-center text-xs text-slate-400 dark:text-slate-500 pt-2">Kalkan Social v2.0 • Made with ❤️ in Kalkan</div>
    </div>
</div>
