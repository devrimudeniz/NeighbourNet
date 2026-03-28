<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/RateLimiter.php';
session_start();

$msg = '';
$msg_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Rate limit: 3 requests per hour (prevent email spam/abuse)
    if (!RateLimiter::check($pdo, 'forgot_pw', 3, 3600)) {
        $msg = "Çok fazla istek. Lütfen 1 saat sonra tekrar deneyin. / Too many requests. Please try again in 1 hour.";
        $msg_type = "error";
    } else {
    $email = trim($_POST['email']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Geçersiz e-posta adresi. / Invalid email address.";
        $msg_type = "error";
    } else {
        // Check user (Trim DB email just in case)
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE TRIM(email) = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate Token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save to DB
            $upd = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $upd->execute([$token, $expiry, $user['id']]);
            
            // Send Email (SMTP)
            require_once 'includes/mail_sender.php';
            
            $reset_link = "https://kalkansocial.com/reset_password?token=" . $token;
            $subject = "Kalkan Social - Şifre Sıfırlama";
            
            // HTML Email Template
            $message_body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
                    .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #0055FF 0%, #0033CC 100%); padding: 30px; text-align: center; }
                    .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 1px; }
                    .content { padding: 40px 30px; color: #334155; line-height: 1.6; }
                    .greeting { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 20px; }
                    .message-text { margin-bottom: 30px; }
                    .button-container { text-align: center; margin: 30px 0; }
                    .button { background: linear-gradient(135deg, #0055FF 0%, #0033CC 100%); color: #ffffff !important; padding: 14px 32px; text-decoration: none; border-radius: 50px; font-weight: 600; display: inline-block; box-shadow: 0 4px 15px rgba(0, 85, 255, 0.3); }
                    .footer { background-color: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
                    .link-text { color: #0055FF; word-break: break-all; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="header">
                        <h1>KALKAN SOCIAL</h1>
                    </div>
                    <div class="content">
                        <div class="greeting">Merhaba ' . htmlspecialchars($user['username']) . ',</div>
                        <div class="message-text">
                            Kalkan Social hesabınız için bir şifre sıfırlama talebi aldık. Hesabınıza yeniden erişim sağlamak için aşağıdaki butona tıklayarak yeni şifrenizi belirleyebilirsiniz.
                        </div>
                        
                        <div class="button-container">
                            <a href="' . $reset_link . '" class="button">Şifremi Sıfırla</a>
                        </div>
                        
                        <div class="message-text" style="font-size: 14px; color: #64748b;">
                            Bu bağlantı güvenliğiniz için <strong>1 saat</strong> süreyle geçerlidir.<br>
                            Eğer bu işlemi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz. Hesabınız güvendedir.
                        </div>
                        
                        <div class="link-text">
                            Buton çalışmıyorsa aşağıdaki bağlantıyı tarayıcınıza yapıştırın:<br>
                            ' . $reset_link . '
                        </div>
                    </div>
                    <div class="footer">
                        &copy; ' . date("Y") . ' Kalkan Social. Tüm hakları saklıdır.<br>
                        Kas Digital Solutions
                    </div>
                </div>
            </body>
            </html>
            ';
            
            if (send_smtp_email($email, $subject, $message_body)) {
                // Remove test link for production security, or keep if user insists on debugging
                $msg = "Sıfırlama bağlantısı e-posta adresinize gönderildi. / Reset link sent to your email.";
                $msg_type = "success";
            } else {
                $msg = "E-posta gönderim hatası (SMTP). / Email sending error (SMTP).<br><strong>(Test Link/Code):</strong> <a href='reset_password?token=$token' class='underline font-bold'>Buraya Tıkla / Click Here</a>";
                $msg_type = "warning";
            }
        } else {
            // Security: Don't reveal if user exists? Usually yes, but for now user feedback is prioritized.
            $msg = "Bu e-posta adresiyle kayıtlı kullanıcı bulunamadı. / No user found with this email.";
            $msg_type = "error";
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
    <title>Şifremi Unuttum | Kalkan Social</title>
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
    </style>
</head>
<body class="flex items-center justify-center min-h-screen px-4 bg-slate-900 relative overflow-hidden font-sans">
    
    <!-- Background Image with Overlay -->
    <div class="fixed inset-0 z-0">
        <img src="assets/kalkan/kalkan4.jpg" class="w-full h-full object-cover animate-pan-slow" alt="Kalkan Background">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] bg-gradient-to-tr from-slate-900 via-slate-900/50 to-transparent"></div>
    </div>

    <div class="glass-card w-full max-w-md p-8 md:p-10 rounded-3xl relative z-10 animate-float border-t border-l border-white/20 border-b border-r border-black/10 my-10 mx-4">
        
        <?php if($msg): ?>
            <div class="mb-6 px-4 py-3 rounded-xl text-sm font-medium flex items-center backdrop-blur-md shadow-lg border <?php echo $msg_type == 'success' ? 'bg-green-500/20 border-green-500/50 text-white' : ($msg_type == 'warning' ? 'bg-yellow-500/20 border-yellow-500/50 text-white' : 'bg-red-500/20 border-red-500/50 text-white'); ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-[#0055FF] to-[#0033CC] rounded-2xl mx-auto flex items-center justify-center mb-4 transform rotate-3 shadow-[0_0_20px_rgba(0,85,255,0.5)] border border-white/20">
                <?php echo heroicon('key', 'text-2xl text-white w-8 h-8'); ?>
            </div>
            <h2 class="text-2xl font-bold text-white drop-shadow-md">
                Şifremi Unuttum <br>
                <span class="text-lg font-medium text-blue-100/90">Forgot Password</span>
            </h2>
            <p class="text-white/80 mt-2 text-sm font-medium">
                E-posta adresinizi girin, sıfırlama bağlantısı gönderelim.<br>
                <span class="text-white/60 font-normal">Enter your email for a reset link.</span>
            </p>
        </div>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-xs font-bold text-white/90 mb-2 pl-1 drop-shadow-md uppercase tracking-wider">
                    E-Posta Adresi / Email Address
                </label>
                <div class="relative group transition-all duration-300 transform hover:scale-[1.01]">
                    <span class="absolute left-4 top-4 text-white/60 pointer-events-none"><?php echo heroicon('envelope', 'w-5 h-5'); ?></span>
                    <input type="email" name="email" required class="glass-input w-full rounded-2xl pl-12 pr-4 py-4 focus:outline-none placeholder-white/40 text-white text-base font-medium shadow-inner" placeholder="mail@ornek.com">
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-[#0055FF] to-[#0033CC] hover:shadow-[0_0_35px_rgba(0,85,255,0.7)] text-white font-bold py-4 rounded-xl shadow-[0_0_25px_rgba(0,85,255,0.5)] transform hover:-translate-y-0.5 transition-all duration-200 border border-white/20">
                Sıfırlama Bağlantısı Gönder / Send Reset Link
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="login" class="text-sm font-bold text-white/70 hover:text-white transition-colors flex items-center justify-center gap-1 drop-shadow-sm">
                <?php echo heroicon('arrow_left', 'w-4 h-4 mr-1'); ?> Giriş Yap / Back to Login
            </a>
        </div>
        
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
</body>
</html>
