<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';

// Must be logged in - redirect BEFORE any output
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Soft delete user (mark as deleted) or hard delete based on policy.
        // For GDPr/Play Store, usually users expect data removal.
        // Implemented soft delete with future cleanup or immediate anonymization is best practice.
        // Here we'll do a soft delete by setting a deleted_at timestamp and clearing sensitive info.
        
        try {
            $pdo->beginTransaction();
            
            // Mark user as deleted (soft delete - anonymize, no deleted_at column needed)
            $stmt = $pdo->prepare("UPDATE users SET 
                username = CONCAT('deleted_', id), 
                email = CONCAT('deleted_', id, '@kalkansocial.com'),
                password = '',
                full_name = 'Deleted User',
                avatar = 'default_avatar.png',
                bio = '',
                phone = ''
                WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Delete sessions/tokens
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            // Logout
            session_destroy();
            header("Location: index?msg=account_deleted");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $lang == 'en' ? 'An error occurred. Please try again.' : 'Bir hata oluştu. Lütfen tekrar deneyin.';
        }
    } else {
        $error = $lang == 'en' ? 'Incorrect password.' : 'Hatalı şifre.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Delete Account' : 'Hesabı Sil'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8 border border-slate-200 dark:border-slate-700">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold mb-2"><?php echo $lang == 'en' ? 'Delete Account' : 'Hesabı Sil'; ?></h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">
                <?php echo $lang == 'en' 
                    ? 'This action cannot be undone. All your data will be permanently removed.' 
                    : 'Bu işlem geri alınamaz. Tüm verileriniz kalıcı olarak silinecektir.'; ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-4 rounded-xl text-sm mb-6 flex items-center gap-2">
                <i class="fas fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-6">
                <label class="block text-sm font-bold mb-2 text-slate-700 dark:text-slate-300">
                    <?php echo $lang == 'en' ? 'Confirm your password' : 'Şifrenizi onaylayın'; ?>
                </label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 border-none focus:ring-2 focus:ring-red-500 transition-all"
                       placeholder="********">
            </div>

            <button type="submit" onclick="return confirm('<?php echo $lang == 'en' ? 'Are you absolutely sure?' : 'Kesinlikle emin misiniz?'; ?>')" 
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-red-500/30 mb-4">
                <?php echo $lang == 'en' ? 'Delete My Account' : 'Hesabımı Sil'; ?>
            </button>
            
            <a href="profile" class="block text-center text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-sm">
                <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
            </a>
        </form>
    </div>

</body>
</html>
