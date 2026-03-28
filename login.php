<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth_helper.php';
require_once 'includes/RateLimiter.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir (PWA/Facebook OAuth sonrası login'e düşmesin)
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: /');
    exit();
}

$error = '';
$error_en = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Lütfen tüm alanları doldurun.";
        $error_en = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND username NOT LIKE 'deleted_%'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['badge'] = $user['badge'];
            
            // Get user's preferred language from database
            $user_lang = $user['preferred_language'] ?? 'tr';
            $_SESSION['language'] = $user_lang;
            
            // Handle Remember Me
            if (isset($_POST['remember_me'])) {
                // Persistent login (30 days)
                $token = createRememberToken($pdo, $user['id']);
                setRememberCookie($token);
            }
            
            // Set language cookie
            setcookie('language', $user_lang, time() + (365 * 24 * 60 * 60), '/');
            
            if ($user['role'] == 'venue' || $user['role'] == 'admin') {
                $_SESSION['venue_name'] = $user['venue_name'];
                $_SESSION['full_name'] = $user['full_name'] ?? $user['venue_name'];
                $_SESSION['avatar'] = $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['venue_name']);
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $script_dir = dirname($_SERVER['SCRIPT_NAME']);
                if ($script_dir == '/') $script_dir = '';
                header("Location: " . $protocol . $host . $script_dir . "/index");
                exit();
            } else {
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['avatar'] = $user['avatar'];
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $script_dir = dirname($_SERVER['SCRIPT_NAME']);
                if ($script_dir == '/') $script_dir = '';
                header("Location: " . $protocol . $host . $script_dir . "/index");
                exit();
            }
        } else {
            // Rate limit only on failed attempts: 5 per 15 minutes
            if (!RateLimiter::check($pdo, 'login_fail', 5, 900)) {
                $error = "Çok fazla başarısız deneme. Lütfen 15 dakika sonra tekrar deneyin.";
                $error_en = "Too many failed attempts. Please try again in 15 minutes.";
            } else {
                $error = "Hatalı kullanıcı adı veya şifre.";
                $error_en = "Invalid username or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap / Login | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>
        /* Custom Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Glassmorphism Utilities */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .glass-input {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        /* Checkbox Customization */
        .glass-checkbox {
            appearance: none;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            width: 1.25em;
            height: 1.25em;
            display: inline-grid;
            place-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .glass-checkbox::before {
            content: "";
            width: 0.75em;
            height: 0.75em;
            transform: scale(0);
            transition: 120ms transform ease-in-out;
            box-shadow: inset 1em 1em white;
            transform-origin: center;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }
        .glass-checkbox:checked {
            background-color: #0055FF;
            border-color: #0055FF;
        }
        .glass-checkbox:checked::before {
            transform: scale(1);
        }

        /* Language Radio Customization */
        .glass-radio-option:checked + div {
            background: rgba(0, 85, 255, 0.4) !important;
            border-color: #0055FF !important;
        }
        
    </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4 bg-slate-900 relative overflow-hidden font-sans">

    <!-- Background Image with Overlay -->
    <div class="fixed inset-0 z-0">
        <img src="assets/kalkan/kalkan4.jpg" class="w-full h-full object-cover animate-pan-slow" alt="Kalkan Background">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] bg-gradient-to-tr from-slate-900 via-slate-900/50 to-transparent"></div>
    </div>
    
    <style>
        @keyframes pan-slow {
            0% { transform: scale(1.05); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1.05); }
        }
        .animate-pan-slow {
            animation: pan-slow 20s ease-in-out infinite;
        }
    </style>

    <!-- Main Container -->
    <div class="glass-card w-full max-w-md p-8 md:p-10 rounded-3xl relative z-10 animate-float border-t border-l border-white/20 border-b border-r border-black/10 my-10 mx-4">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-extrabold mb-3 text-white tracking-tight drop-shadow-lg">
                Hoşgeldiniz
                <span class="block text-xl font-medium text-blue-100/90 mt-1">Welcome Back</span>
            </h1>
            <p class="text-white/80 text-sm font-medium drop-shadow-md">
                Kalkan'ın kalbine giriş yapın.<br>
                <span class="text-white/60 font-normal">Log in to the heart of Kalkan.</span>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-white px-4 py-3 rounded-xl mb-6 text-sm flex items-center backdrop-blur-md shadow-lg">
                <?php echo heroicon('exclamation_circle', 'mr-2 w-5 h-5 text-red-100 flex-shrink-0'); ?>
                <span><?php echo $error; ?> / <?php echo $error_en; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div class="space-y-5">
                <div class="relative group">
                    <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                        Kullanıcı Adı / Username
                    </label>
                    <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-white/60 group-focus-within:text-white transition-colors">
                             <?php echo heroicon('user', 'w-5 h-5'); ?>
                        </div>
                        <input type="text" name="username" class="glass-input w-full rounded-2xl pl-12 pr-4 py-4 focus:outline-none placeholder-white/40 text-white text-base font-medium shadow-inner" placeholder="kullaniciadi" required style="background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255,255,255,0.3) !important;">
                    </div>
                </div>
                
                <div class="relative group">
                    <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                        Şifre / Password
                    </label>
                    <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-white/60 group-focus-within:text-white transition-colors">
                             <?php echo heroicon('lock', 'w-5 h-5'); ?>
                        </div>
                        <input type="password" name="password" class="glass-input w-full rounded-2xl pl-12 pr-4 py-4 focus:outline-none placeholder-white/40 text-white text-base font-medium shadow-inner" placeholder="••••••••" required style="background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255,255,255,0.3) !important;">
                    </div>
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between text-sm mt-2">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox" class="glass-checkbox text-blue-500 rounded focus:ring-0 focus:ring-offset-0 bg-white/20">
                    <label for="remember_me" class="ml-2 block text-white/90 text-sm font-medium cursor-pointer select-none hover:text-white transition-colors drop-shadow-sm">
                        Beni Hatırla
                    </label>
                </div>
                <a href="forgot_password" class="text-sm font-bold text-white hover:text-blue-100 transition-colors drop-shadow-md decoration-2 hover:underline opacity-90 hover:opacity-100">
                    Şifremi Unuttum?
                </a>
            </div>
            
            <button type="submit" class="group w-full bg-gradient-to-r from-[#0055FF] to-[#0033CC] text-white font-bold py-4 rounded-2xl shadow-[0_0_25px_rgba(0,85,255,0.5)] hover:shadow-[0_0_35px_rgba(0,85,255,0.7)] hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 flex items-center justify-center border border-white/20 mt-4 overflow-hidden relative">
                <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 rounded-2xl"></div>
                <?php echo heroicon('login', 'mr-2 w-6 h-6 text-white relative z-10'); ?>
                <span class="tracking-wide text-lg relative z-10">Giriş Yap / Login</span>
            </button>
        </form>

        <!-- Social Login Divider -->
        <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-white/20"></div>
            </div>
            <div class="relative flex justify-center text-[10px] uppercase tracking-widest">
                <span class="bg-transparent px-2 text-white/50 font-bold drop-shadow-md">veya / or</span>
            </div>
        </div>

        <!-- Social Login Buttons (Facebook first to avoid confusion) -->
        <div class="grid grid-cols-2 gap-3">
            <a href="/api/auth_facebook.php" class="flex items-center justify-center gap-2 bg-[#1877F2] text-white font-bold py-3.5 px-4 rounded-xl shadow-lg hover:bg-[#1558b0] hover:scale-[1.02] transition-all text-xs border border-white/20">
                <i class="fab fa-facebook-f text-lg"></i>
                Facebook
            </a>
            <a href="/api/auth_google.php" class="flex items-center justify-center gap-2 bg-white/90 backdrop-blur-sm text-slate-900 font-bold py-3.5 px-4 rounded-xl shadow-lg hover:bg-white hover:scale-[1.02] transition-all text-xs border border-white/50">
                 <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Google
            </a>
        </div>

        <p class="text-center mt-8 text-sm text-white/70 font-medium">
            Hesabın yok mu? <br>
            <a href="register" class="text-white font-extrabold hover:text-blue-200 hover:underline decoration-2 underline-offset-4 transition-all drop-shadow-md">Kayıt Ol / Register</a>
        </p>
        
        <!-- Legal Links -->
        <div class="flex flex-wrap justify-center gap-x-4 gap-y-2 mt-8 text-[10px] text-white/50 uppercase tracking-wider font-semibold">
            <a href="privacy" class="hover:text-white transition-colors">Privacy</a>
            <span>•</span>
            <a href="terms" class="hover:text-white transition-colors">Terms</a>
            <span>•</span>
            <a href="safety_standards.php" class="hover:text-white transition-colors">Safety</a>
            <span>•</span>
            <a href="kvkk" class="hover:text-white transition-colors">KVKK</a>
        </div>
    </div>
</body>
</html>
