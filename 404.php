<?php
require_once 'includes/lang.php';
// Set 404 header
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>
        .error-text-shadow {
            text-shadow: 2px 2px 0px rgba(0,0,0,0.1);
        }
        .bouncing-map {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex flex-col transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="flex-1 container mx-auto px-6 flex flex-col items-center justify-center text-center py-20">
        
        <!-- Illustration -->
        <div class="relative mb-10">
            <!-- Background Blob -->
            <div class="absolute inset-0 bg-gradient-to-tr from-pink-500/20 to-violet-500/20 rounded-full blur-3xl transform scale-150"></div>
            
            <!-- 404 Text -->
            <h1 class="relative text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500 error-text-shadow z-10">
                404
            </h1>
            
            <!-- Floating Icon -->
            <div class="absolute -top-10 -right-10 bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-xl rotate-12 bouncing-map z-20 hidden md:block">
                <i class="fas fa-map-marked-alt text-4xl text-pink-500"></i>
            </div>
        </div>

        <!-- Message -->
        <h2 class="text-3xl md:text-4xl font-bold mb-4 text-slate-800 dark:text-white">
            Oops! You seem to be lost.
        </h2>
        
        <p class="text-lg text-slate-500 dark:text-slate-400 max-w-lg mb-10 leading-relaxed">
            The page you are looking for might have been moved, deleted, or perhaps it got lost on the winding roads of Kalkan.
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
            <a href="feed" class="flex items-center justify-center gap-2 px-8 py-3 bg-gradient-to-r from-pink-500 to-violet-500 text-white rounded-xl font-bold shadow-lg shadow-pink-500/30 hover:shadow-pink-500/50 hover:-translate-y-1 transition-all">
                <i class="fas fa-home"></i>
                Back to Home
            </a>
            
            <a href="events" class="flex items-center justify-center gap-2 px-8 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-all">
                <i class="fas fa-calendar-alt"></i>
                Browse Events
            </a>
        </div>

        <!-- Help Links -->
        <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-800">
            <p class="text-sm text-slate-400 mb-4">
                Or try these popular pages:
            </p>
            <div class="flex flex-wrap justify-center gap-6 text-sm font-medium text-slate-500 dark:text-slate-400">
                <a href="news" class="hover:text-pink-500 transition-colors">
                    News
                </a>
                <a href="groups" class="hover:text-pink-500 transition-colors">
                    Groups
                </a>
                <a href="directory" class="hover:text-pink-500 transition-colors">
                    Directory
                </a>
                <a href="contact" class="hover:text-pink-500 transition-colors">
                    Contact Us
                </a>
            </div>
        </div>

    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200 dark:border-slate-800 z-50 md:hidden safe-area-inset-bottom">
        <div class="flex justify-around items-center h-16">
            <a href="feed" class="flex flex-col items-center justify-center text-slate-400 hover:text-pink-500 transition-colors py-2 px-4">
                <i class="fas fa-home text-xl"></i>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="events" class="flex flex-col items-center justify-center text-slate-400 hover:text-pink-500 transition-colors py-2 px-4">
                <i class="fas fa-calendar text-xl"></i>
                <span class="text-[10px] font-bold mt-1">Events</span>
            </a>
            <a href="news" class="flex flex-col items-center justify-center text-slate-400 hover:text-pink-500 transition-colors py-2 px-4">
                <i class="fas fa-newspaper text-xl"></i>
                <span class="text-[10px] font-bold mt-1">News</span>
            </a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'profile' : 'login'; ?>" class="flex flex-col items-center justify-center text-slate-400 hover:text-pink-500 transition-colors py-2 px-4">
                <i class="fas fa-user text-xl"></i>
                <span class="text-[10px] font-bold mt-1">Profile</span>
            </a>
        </div>
    </nav>

</body>
</html>
