<?php
require_once 'includes/db.php';
session_start();

$token = isset($_GET['token']) ? $_GET['token'] : '';
$msg = '';
$msg_type = '';
$valid_token = false;

if (!$token) {
    header("Location: login");
    exit();
}

// 1. Verify Token
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > ?");
$stmt->execute([$token, $now]);
$user = $stmt->fetch();

if ($user) {
    $valid_token = true;
} else {
    $msg = "Geçersiz veya süresi dolmuş bağlantı.";
    $msg_type = "error";
}

// 2. Handle Submission
if ($valid_token && $_SERVER["REQUEST_METHOD"] == "POST") {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];
    
    if (strlen($pass1) < 6) {
        $msg = "Şifre en az 6 karakter olmalı.";
        $msg_type = "error";
    } elseif ($pass1 !== $pass2) {
        $msg = "Şifreler eşleşmiyor.";
        $msg_type = "error";
    } else {
        // Update Password
        $hashed = password_hash($pass1, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        
        if ($upd->execute([$hashed, $user['id']])) {
            $count = $upd->rowCount();
            if ($count > 0) {
                // Determine protocol for redirect
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $login_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/login";
                
                $msg = "Şifreniz güncellendi! (Etkilenen Kayıt: $count)<br>Yeni Hash: " . substr($hashed, 0, 10) . "...<br><a href='$login_url' class='underline font-bold'>Giriş Yap</a>";
                $msg_type = "success";
                // header("refresh:3;url=login"); // Disable auto-redirect for debug visibility
            } else {
                $msg = "Veritabanı güncellenemedi (Değişiklik yok veya hata).";
                $msg_type = "warning";
            }
        } else {
            $msg = "Bir hata oluştu.";
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white/20 dark:border-slate-700/50 relative overflow-hidden">
        
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">Yeni Şifre Belirle</h2>
        </div>

        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl text-center font-bold <?php echo $msg_type == 'success' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if($valid_token && $msg_type != 'success'): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold ml-1">Yeni Şifre</label>
                <div class="relative group">
                    <span class="absolute left-4 top-3.5 text-slate-400 group-focus-within:text-pink-500 transition-colors"><?php echo heroicon('lock', 'w-5 h-5'); ?></span>
                    <input type="password" name="pass1" required class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl py-3 pl-12 pr-4 focus:outline-none focus:border-pink-500 focus:ring-2 focus:ring-pink-200 dark:focus:ring-pink-900/30 transition-all font-medium" placeholder="******">
                </div>
            </div>

            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold ml-1">Şifre Tekrar</label>
                <div class="relative group">
                    <span class="absolute left-4 top-3.5 text-slate-400 group-focus-within:text-pink-500 transition-colors"><?php echo heroicon('lock', 'w-5 h-5'); ?></span>
                    <input type="password" name="pass2" required class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl py-3 pl-12 pr-4 focus:outline-none focus:border-pink-500 focus:ring-2 focus:ring-pink-200 dark:focus:ring-pink-900/30 transition-all font-medium" placeholder="******">
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-pink-500 to-violet-500 hover:from-pink-600 hover:to-violet-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-pink-500/30 transform hover:-translate-y-0.5 transition-all duration-200">
                Şifreyi Güncelle
            </button>
        </form>
        <?php elseif(!$valid_token): ?>
            <div class="text-center">
                <a href="forgot_password" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-xl font-bold hover:bg-slate-200 transition-colors">Yeni Bağlantı İste</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
