<?php
// Bottom Navigation Component
// Mobile-first navigation bar fixed to the bottom
?>
<nav class="fixed bottom-0 left-0 w-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-lg border-t border-slate-200 dark:border-slate-800 pb-safe z-50 md:hidden block">
    <div class="flex justify-around items-center h-16">
        <a href="feed" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'feed.php' ? 'text-slate-900 dark:text-white font-bold' : ''; ?>">
            <i class="fas fa-home text-xl mb-1"></i>
            <span class="text-[10px] font-medium"><?php echo $lang == 'en' ? 'Home' : 'Akış'; ?></span>
        </a>
        
        <a href="directory" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'directory.php' ? 'text-slate-900 dark:text-white font-bold' : ''; ?>">
            <i class="fas fa-search text-xl mb-1"></i>
            <span class="text-[10px] font-medium"><?php echo $lang == 'en' ? 'Discover' : 'Keşfet'; ?></span>
        </a>

        <div class="relative -top-7">
            <a href="create_post_page.php" style="background: linear-gradient(135deg, #2563eb 0%, #1e3a5f 100%);" class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-900/50 hover:shadow-blue-800/60 hover:scale-110 active:scale-95 transition-all animate-pulse-glow">
                <i class="fas fa-plus text-2xl"></i>
            </a>
        </div>
        
        <a href="marketplace" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'marketplace.php' ? 'text-slate-900 dark:text-white font-bold' : ''; ?>">
            <i class="fas fa-store text-xl mb-1"></i>
            <span class="text-[10px] font-medium"><?php echo $lang == 'en' ? 'Market' : 'Pazar'; ?></span>
        </a>
        
        <a href="profile?uid=<?php echo $_SESSION['user_id'] ?? 0; ?>" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-slate-900 dark:text-white font-bold' : ''; ?>">
            <img src="<?php echo $_SESSION['avatar'] ?? 'assets/img/default-avatar.png'; ?>" class="w-6 h-6 rounded-full object-cover border border-slate-300 dark:border-slate-600">
            <span class="text-[10px] font-medium mt-1"><?php echo $lang == 'en' ? 'Me' : 'Ben'; ?></span>
        </a>
    </div>
</nav>
<style>
/* Safe area for iPhone X+ */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}

/* Glowing pulse animation */
@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.5);
    }
    50% {
        box-shadow: 0 10px 35px -5px rgba(37, 99, 235, 0.7), 0 0 20px rgba(59, 130, 246, 0.4);
    }
}
.animate-pulse-glow {
    animation: pulse-glow 2s ease-in-out infinite;
}
</style>
