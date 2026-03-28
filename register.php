<?php
session_start();
require_once 'includes/db.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: /');
    exit();
}

$error = '';
$error_en = '';
$success = '';
$success_en = '';

// Ensure email_verified column exists
try {
    $pdo->query("SELECT email_verified FROM users LIMIT 1");
} catch (PDOException $e) {
    try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 1"); } catch (Exception $x) {}
}

// Rate limiting for registration (3 per 15 minutes)
require_once 'includes/RateLimiter.php';

// Single-step registration (no verification code required)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    if (!RateLimiter::check($pdo, 'register', 3, 900)) {
        $error = 'Çok fazla kayıt denemesi. Lütfen 15 dakika sonra tekrar deneyin.';
        $error_en = 'Too many registration attempts. Please try again in 15 minutes.';
    } else {
    $email = trim($_POST['email'] ?? '');
    $account_type = $_POST['account_type'] ?? 'standard';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $selected_lang = $_POST['language'] ?? 'tr';
    
    $business_name = $account_type == 'business' ? trim($_POST['business_name'] ?? '') : '';
    $phone = $account_type == 'business' ? trim($_POST['phone'] ?? '') : '';
    $gender = $_POST['gender'] ?? 'unspecified';
    $birth_date = $_POST['birth_date'] ?? null;
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz e-posta adresi';
        $error_en = 'Invalid email address';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor';
        $error_en = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalı';
        $error_en = 'Password must be at least 6 characters';
    } elseif ($account_type == 'business' && (!$business_name || !$phone)) {
        $error = 'İşletme adı ve telefon zorunludur';
        $error_en = 'Business name and phone are required';
    } else {
        $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->execute([$email]);
        if ($check_email->fetch()) {
            $error = 'Bu e-posta zaten kullanılıyor';
            $error_en = 'This email is already in use';
        } else {
            $check_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->execute([$username]);
            if ($check_user->fetch()) {
                $error = 'Bu kullanıcı adı zaten kullanılıyor';
                $error_en = 'This username is already taken';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = $account_type == 'business' ? 'venue' : 'standard';
                $badge = $account_type == 'business' ? 'verified_business' : '';
                $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&background=random';
                
                $insert_ok = false;
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, badge, venue_name, avatar, preferred_language, gender, birth_date, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                    $insert_ok = $stmt->execute([$username, $hashed_password, $full_name, $email, $role, $badge, $business_name, $avatar, $selected_lang, $gender, $birth_date]);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'email_verified') !== false) {
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, badge, venue_name, avatar, preferred_language, gender, birth_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $insert_ok = $stmt->execute([$username, $hashed_password, $full_name, $email, $role, $badge, $business_name, $avatar, $selected_lang, $gender, $birth_date]);
                    } else throw $e;
                }
                if ($insert_ok) {
                    
                    $new_user_id = $pdo->lastInsertId();
                    
                    if ($new_user_id == 0 || empty($new_user_id)) {
                        $id_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
                        $id_stmt->execute([$username, $email]);
                        $user_data = $id_stmt->fetch();
                        $new_user_id = $user_data['id'];
                    }
                    
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = $role;
                    $_SESSION['badge'] = $badge;
                    $_SESSION['avatar'] = $avatar;
                    $_SESSION['language'] = $selected_lang;
                    $_SESSION['show_welcome_tour'] = true; // Show welcome tour for new users
                    
                    setcookie('language', $selected_lang, time() + (365 * 24 * 60 * 60), '/');
                    
                    unset($_SESSION['reg_email']);
                    header('Location: index?registered=1');
                    exit();
                } else {
                    $error = 'Kayıt başarısız. Tekrar deneyin.';
                    $error_en = 'Registration failed. Please try again.';
                }
            }
        }
    }
} // end rate limit else
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol / Register | Kalkan Social</title>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol / Register | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>
        @keyframes pan-slow {
            0% { transform: scale(1.05); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1.05); }
        }
        .animate-pan-slow {
            animation: pan-slow 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .glass-input {
            background: rgba(255, 255, 255, 0.15) !important; 
            border: 1px solid rgba(255,255,255,0.3) !important;
            color: white !important;
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        /* Select styling is tricky with glass, keep simple */
        select.glass-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        option { background-color: #1e293b; color: white; }

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

    <div class="glass-card w-full max-w-lg p-8 rounded-3xl relative z-10 animate-float border-t border-l border-white/20 border-b border-r border-black/10 my-10 mx-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-white mb-2 drop-shadow-md tracking-tight">
                Kalkan Social
            </h1>
            <p class="text-white/70 text-sm font-medium">
                Topluluğumuza katıl / Join our community
            </p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-white px-4 py-3 rounded-xl mb-6 text-sm flex items-center backdrop-blur-md shadow-lg">
            <?php echo heroicon('exclamation_circle', 'mr-2 w-5 h-5 text-red-100 flex-shrink-0'); ?>
            <span><?php echo $error; ?> / <?php echo $error_en; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-500/20 border border-green-500/50 text-white px-4 py-3 rounded-xl mb-6 text-sm flex items-center backdrop-blur-md shadow-lg">
            <?php echo heroicon('check_circle', 'mr-2 w-5 h-5 text-green-100 flex-shrink-0'); ?>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>

        <!-- Social Register Buttons (Facebook first) -->
        <div class="grid grid-cols-2 gap-3 mb-6">
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

        <!-- Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-white/20"></div>
            </div>
            <div class="relative flex justify-center text-[10px] uppercase tracking-widest">
                <span class="bg-transparent px-2 text-white/50 font-bold drop-shadow-md">veya e-posta ile / or with email</span>
            </div>
        </div>

        <!-- Single-step Registration Form -->
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">E-posta Adresi / Email Address</label>
                <div class="relative">
                    <span class="absolute left-4 top-4 text-white/60 pointer-events-none"><?php echo heroicon('envelope', 'w-5 h-5'); ?></span>
                    <input type="email" name="email" required class="glass-input w-full rounded-2xl pl-12 pr-4 py-4 focus:outline-none placeholder-white/40 text-white text-base font-medium" placeholder="email@ornek.com">
                </div>
            </div>

            <!-- Language Selection -->
            <div class="pt-2">
                 <label class="block text-[10px] uppercase tracking-[0.2em] text-white/60 mb-3 text-center font-bold">
                    Dil Seçimi / Select Language
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer group relative">
                        <input type="radio" name="language" value="tr" checked class="glass-radio-option peer hidden">
                        <div class="border border-white/20 bg-white/10 peer-checked:bg-blue-600/40 peer-checked:border-blue-400/50 rounded-xl p-3 text-center transition-all hover:bg-white/20 flex items-center justify-center gap-2 backdrop-blur-sm">
                            <span class="text-xl filter drop-shadow">🇹🇷</span>
                            <span class="font-bold text-sm text-white sticky z-10">Türkçe</span>
                        </div>
                    </label>
                    <label class="cursor-pointer group relative">
                        <input type="radio" name="language" value="en" class="glass-radio-option peer hidden">
                        <div class="border border-white/20 bg-white/10 peer-checked:bg-blue-600/40 peer-checked:border-blue-400/50 rounded-xl p-3 text-center transition-all hover:bg-white/20 flex items-center justify-center gap-2 backdrop-blur-sm">
                            <span class="text-xl filter drop-shadow">🇬🇧</span>
                            <span class="font-bold text-sm text-white sticky z-10">English</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Account Type Selection -->
            <div>
                <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                    Hesap Tipi / Account Type
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="account_type" value="standard" checked class="peer hidden">
                        <div class="glass-input border border-white/20 peer-checked:bg-blue-600/40 peer-checked:border-blue-400/50 rounded-xl p-4 text-center transition-all flex flex-col items-center">
                            <?php echo heroicon('user', 'text-2xl mb-2 text-white w-8 h-8'); ?>
                            <p class="font-bold text-sm text-white">Standart</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="account_type" value="business" class="peer hidden">
                        <div class="glass-input border border-white/20 peer-checked:bg-blue-600/40 peer-checked:border-blue-400/50 rounded-xl p-4 text-center transition-all flex flex-col items-center">
                            <?php echo heroicon('briefcase', 'text-2xl mb-2 text-white w-8 h-8'); ?>
                            <p class="font-bold text-sm text-white">İşletme</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Standard Fields -->
            <div>
                <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                    Ad Soyad / Full Name
                </label>
                <input type="text" name="full_name" required class="glass-input w-full rounded-2xl px-4 py-3 focus:outline-none placeholder-white/40 font-medium">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                   <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                        Cinsiyet
                    </label>
                    <select name="gender" class="glass-input w-full rounded-2xl px-4 py-3 focus:outline-none">
                        <option value="unspecified">Seçiniz</option>
                        <option value="male">Erkek</option>
                        <option value="female">Kadın</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                        Doğum Tarihi
                    </label>
                    <input type="date" name="birth_date" class="glass-input w-full rounded-2xl px-4 py-3 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                    Kullanıcı Adı / Username
                </label>
                <input type="text" name="username" required class="glass-input w-full rounded-2xl px-4 py-3 focus:outline-none placeholder-white/40 font-medium">
            </div>

            <!-- Business Fields (conditional) -->
            <div id="business-fields" style="display: none;">
                <div class="bg-blue-900/30 border border-blue-500/30 rounded-xl p-4 space-y-4 backdrop-blur-sm">
                    <p class="text-xs font-bold text-blue-200 uppercase tracking-wider">
                        İşletme Bilgileri / Business Information
                    </p>
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-blue-100 mb-2">
                            İşletme Adı / Business Name *
                        </label>
                        <input type="text" name="business_name" class="glass-input w-full rounded-xl px-4 py-3 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-blue-100 mb-2">
                            Telefon / Phone *
                        </label>
                        <input type="tel" name="phone" class="glass-input w-full rounded-xl px-4 py-3 focus:outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                    Şifre / Password
                </label>
                <input type="password" name="password" required minlength="6" class="glass-input w-full rounded-2xl px-4 py-3 focus:outline-none placeholder-white/40">
            </div>

            <div>
                <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                    Şifre Tekrar / Confirm
                </label>
                <input type="password" name="password_confirm" required minlength="6" class="glass-input w-full rounded-2xl px-4 py-3 focus:outline-none placeholder-white/40">
            </div>

            <button type="submit" name="register" class="w-full bg-gradient-to-r from-[#0055FF] to-[#0033CC] hover:shadow-[0_0_35px_rgba(0,85,255,0.7)] text-white font-bold py-4 rounded-xl shadow-[0_0_25px_rgba(0,85,255,0.5)] transition-all flex items-center justify-center border border-white/20 mt-4">
                <?php echo heroicon('user_plus', 'mr-2 w-5 h-5'); ?>
                Kaydı Tamamla / Register
            </button>
        </form>

        <p class="text-center mt-8 text-sm text-white/70">
            Zaten hesabın var mı? <br>
            <a href="login" class="text-blue-200 font-bold hover:text-white hover:underline transition-colors drop-shadow-sm">Giriş Yap / Login</a>
        </p>

        <!-- Legal Links -->
        <div class="flex flex-wrap justify-center gap-x-4 gap-y-2 mt-8 text-[10px] text-white/40 uppercase tracking-wider font-semibold">
            <a href="privacy" class="hover:text-white transition-colors">Privacy</a>
            <span>•</span>
            <a href="terms" class="hover:text-white transition-colors">Terms</a>
            <span>•</span>
            <a href="safety_standards.php" class="hover:text-white transition-colors">Safety</a>
            <span>•</span>
            <a href="kvkk" class="hover:text-white transition-colors">KVKK</a>
        </div>
    </div>

    <script>
        // Show/hide business fields
        document.querySelectorAll('input[name="account_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const businessFields = document.getElementById('business-fields');
                const businessInputs = businessFields.querySelectorAll('input');
                
                if (this.value === 'business') {
                    businessFields.style.display = 'block';
                    businessInputs.forEach(input => input.required = true);
                } else {
                    businessFields.style.display = 'none';
                    businessInputs.forEach(input => input.required = false);
                }
            });
        });
    </script>
</body>
</html>
