<?php
require_once 'includes/lang.php';
// Set 403 header
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>
        .error-text-shadow {
            text-shadow: 2px 2px 0px rgba(0,0,0,0.1);
        }
        .shake-icon {
            animation: shake 0.5s ease-in-out infinite;
        }
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
        .pulse-ring {
            animation: pulse-ring 2s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex flex-col transition-colors">

    <main class="flex-1 container mx-auto px-6 flex flex-col items-center justify-center text-center py-20 relative overflow-hidden">
        
        <!-- Background Effects -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 left-10 w-72 h-72 bg-red-500/30 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-red-500/10 to-yellow-500/10 rounded-full blur-3xl"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10">
            <!-- Shield Icon -->
            <div class="relative mb-8">
                <div class="w-36 h-36 md:w-44 md:h-44 rounded-3xl flex items-center justify-center shadow-2xl pulse-ring" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 50%, #7f1d1d 100%);">
                    <i class="fas fa-shield-alt text-white text-6xl md:text-7xl shake-icon drop-shadow-lg"></i>
                </div>
                
                <!-- Lock Badge -->
                <div class="absolute -bottom-3 -right-3 bg-white dark:bg-slate-800 p-3 rounded-2xl shadow-xl border-4 border-red-500">
                    <i class="fas fa-lock text-2xl text-red-600"></i>
                </div>
                
                <!-- Warning Badge -->
                <div class="absolute -top-3 -left-3 bg-yellow-400 p-3 rounded-2xl shadow-xl">
                </div>
            </div>

            <!-- 403 Text -->
            <h1 class="text-8xl md:text-9xl font-black mb-4" style="background: linear-gradient(135deg, #ef4444, #f97316, #eab308); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 4px 30px rgba(239, 68, 68, 0.3);">
                403
            </h1>

            <!-- Message -->
            <h2 class="text-2xl md:text-4xl font-bold mb-4 text-slate-800 dark:text-white">
                🚫 Erişim Engellendi!
            </h2>
            
            <p class="text-lg text-slate-600 dark:text-slate-300 max-w-lg mb-6 leading-relaxed">
                Bu sayfaya erişim izniniz yok. Bu alan <span class="font-bold text-red-500">korumalı</span> ve sadece yetkili kullanıcılar tarafından erişilebilir.
            </p>

            <!-- Warning Box -->
            <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl p-1 mb-10 max-w-md mx-auto shadow-lg shadow-red-500/30">
                <div class="bg-white dark:bg-slate-900 rounded-xl p-4">
                    <div class="flex items-center gap-3 text-left">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-red-600 dark:text-red-400">Güvenlik Uyarısı</p>
                            <p class="text-xs text-slate-600 dark:text-slate-400">Bu erişim girişimi kaydedildi ve izleniyor.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto justify-center">
                <a href="feed" class="flex items-center justify-center gap-2 px-8 py-4 text-white rounded-2xl font-bold shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-1 hover:scale-105 transition-all" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                    <i class="fas fa-home text-lg"></i>
                    Ana Sayfaya Dön
                </a>
                
                <a href="login" class="flex items-center justify-center gap-2 px-8 py-4 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-2 border-slate-200 dark:border-slate-700 rounded-2xl font-bold hover:bg-slate-50 dark:hover:bg-slate-700 hover:-translate-y-1 transition-all">
                    <i class="fas fa-sign-in-alt text-lg"></i>
                    Giriş Yap
                </a>
            </div>

            <!-- Contact Info -->
            <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-800">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">
                    Bunun bir hata olduğunu düşünüyorsanız:
                </p>
                <a href="mailto:support@kalkansocial.com" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <i class="fas fa-envelope text-blue-500"></i>
                    <span class="font-medium">support@kalkansocial.com</span>
                </a>
            </div>
        </div>

    </main>

</body>
</html>
