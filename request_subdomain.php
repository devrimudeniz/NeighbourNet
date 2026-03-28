<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
require_once 'includes/ui_components.php';

// $lang is already set by lang.php

// Check if user has business badge
if (!isset($_SESSION['user_id']) || !isset($_SESSION['badge']) || 
    !in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])) {
    header('Location: request_verification?type=business');
    exit();
}

$business_id = isset($_GET['business_id']) ? (int)$_GET['business_id'] : 0;
$user_id = $_SESSION['user_id'];

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM business_listings WHERE id = ? AND owner_id = ?");
$stmt->execute([$business_id, $user_id]);
$business = $stmt->fetch();

if (!$business) {
    header('Location: business_panel.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subdomain = isset($_POST['subdomain']) ? strtolower(trim($_POST['subdomain'])) : '';
    
    // Validate subdomain
    if (empty($subdomain)) {
        $error = $lang == 'en' ? 'Subdomain is required' : 'Subdomain gereklidir';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
        $error = $lang == 'en' ? 'Subdomain can only contain lowercase letters, numbers, and hyphens' : 'Subdomain sadece küçük harf, rakam ve tire içerebilir';
    } elseif (strlen($subdomain) < 3 || strlen($subdomain) > 30) {
        $error = $lang == 'en' ? 'Subdomain must be between 3 and 30 characters' : 'Subdomain 3-30 karakter arasında olmalıdır';
    } else {
        // Check if subdomain is already taken
        $check = $pdo->prepare("SELECT id FROM business_listings WHERE subdomain = ? AND id != ?");
        $check->execute([$subdomain, $business_id]);
        
        if ($check->fetch()) {
            $error = $lang == 'en' ? 'This subdomain is already taken' : 'Bu subdomain zaten alınmış';
        } else {
            // Reserved subdomains
            $reserved = ['www', 'mail', 'ftp', 'admin', 'api', 'app', 'blog', 'shop', 'store', 'cdn', 'static', 'assets', 'media', 'images', 'files', 'docs', 'help', 'support', 'status', 'dev', 'test', 'staging'];
            
            if (in_array($subdomain, $reserved)) {
                $error = $lang == 'en' ? 'This subdomain is reserved' : 'Bu subdomain rezerve edilmiş';
            } else {
                // Update business with subdomain request
                $stmt = $pdo->prepare("
                    UPDATE business_listings 
                    SET subdomain = ?, 
                        subdomain_status = 'pending',
                        subdomain_requested_at = NOW()
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$subdomain, $business_id])) {
                    $success = $lang == 'en' ? 'Subdomain request submitted! Waiting for admin approval.' : 'Subdomain talebi gönderildi! Admin onayı bekleniyor.';
                    
                    // Refresh business data
                    $stmt = $pdo->prepare("SELECT * FROM business_listings WHERE id = ?");
                    $stmt->execute([$business_id]);
                    $business = $stmt->fetch();
                } else {
                    $error = $lang == 'en' ? 'Failed to submit request' : 'Talep gönderilemedi';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Request Domain' : 'Alan Adı Talep Et'; ?> - <?php echo htmlspecialchars($business['name']); ?></title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32">

    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 md:pt-28 max-w-3xl">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="business_panel.php" class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-arrow-left text-slate-600 dark:text-slate-300"></i>
                </a>
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white">
                        <?php echo $lang == 'en' ? 'Request Domain' : 'Alan Adı Talep Et'; ?>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                        <?php echo htmlspecialchars($business['name']); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                <p class="text-red-700 dark:text-red-300 font-bold"><?php echo $error; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                <p class="text-emerald-700 dark:text-emerald-300 font-bold"><?php echo $success; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Current Status -->
        <?php if ($business['subdomain']): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 mb-6">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">
                <?php echo $lang == 'en' ? 'Current Status' : 'Mevcut Durum'; ?>
            </h3>
            
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' ? 'Requested Domain' : 'Talep Edilen Alan Adı'; ?>
                    </label>
                    <div class="mt-1 text-xl font-black text-violet-500">
                        <?php echo htmlspecialchars($business['subdomain']); ?>.kalkansocial.com
                    </div>
                </div>

                <div>
                    <label class="text-sm font-bold text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' ? 'Status' : 'Durum'; ?>
                    </label>
                    <div class="mt-1">
                        <?php if ($business['subdomain_status'] === 'pending'): ?>
                        <span class="inline-flex items-center gap-2 bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 px-3 py-1 rounded-lg font-bold">
                            <i class="fas fa-clock"></i>
                            <?php echo $lang == 'en' ? 'Pending Approval' : 'Onay Bekliyor'; ?>
                        </span>
                        <?php elseif ($business['subdomain_status'] === 'approved'): ?>
                        <span class="inline-flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 px-3 py-1 rounded-lg font-bold">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $lang == 'en' ? 'Approved' : 'Onaylandı'; ?>
                        </span>
                        <?php elseif ($business['subdomain_status'] === 'rejected'): ?>
                        <span class="inline-flex items-center gap-2 bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 px-3 py-1 rounded-lg font-bold">
                            <i class="fas fa-times-circle"></i>
                            <?php echo $lang == 'en' ? 'Rejected' : 'Reddedildi'; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($business['subdomain_status'] === 'approved'): ?>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-bold text-emerald-700 dark:text-emerald-300 mb-1">
                                <?php echo $lang == 'en' ? 'Your subdomain is active!' : 'Subdomain\'iniz aktif!'; ?>
                            </div>
                            <a href="https://<?php echo htmlspecialchars($business['subdomain']); ?>.<?php echo htmlspecialchars(preg_replace('/^www\./', '', site_host())); ?>" target="_blank" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">
                                <?php echo htmlspecialchars($business['subdomain']); ?>.<?php echo htmlspecialchars(preg_replace('/^www\./', '', site_host())); ?>
                                <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Request Form -->
        <?php if (!$business['subdomain'] || $business['subdomain_status'] === 'rejected'): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">
                <?php echo $lang == 'en' ? 'Request Your Domain' : 'Alan Adınızı Talep Edin'; ?>
            </h3>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                        <?php echo $lang == 'en' ? 'Choose Your Domain' : 'Alan Adınızı Seçin'; ?> *
                    </label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            name="subdomain" 
                            required 
                            pattern="[a-z0-9-]+"
                            minlength="3"
                            maxlength="30"
                            placeholder="<?php echo $lang == 'en' ? 'yourname' : 'isminiz'; ?>"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 outline-none font-mono"
                            oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '')"
                        >
                        <span class="text-slate-600 dark:text-slate-400 font-bold whitespace-nowrap">
                            .<?php echo htmlspecialchars(preg_replace('/^www\./', '', site_host())); ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        <?php echo $lang == 'en' ? 'Only lowercase letters, numbers, and hyphens. 3-30 characters.' : 'Sadece küçük harf, rakam ve tire. 3-30 karakter.'; ?>
                    </p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                    <h4 class="font-bold text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <?php echo $lang == 'en' ? 'Important Notes' : 'Önemli Notlar'; ?>
                    </h4>
                    <ul class="text-sm text-blue-600 dark:text-blue-400 space-y-1">
                        <li>• <?php echo $lang == 'en' ? 'Your request will be reviewed by admins' : 'Talebiniz adminler tarafından incelenecek'; ?></li>
                        <li>• <?php echo $lang == 'en' ? 'Approval usually takes 24-48 hours' : 'Onay genellikle 24-48 saat sürer'; ?></li>
                        <li>• <?php echo $lang == 'en' ? 'Choose a professional name for your business' : 'İşletmeniz için profesyonel bir isim seçin'; ?></li>
                        <li>• <?php echo $lang == 'en' ? 'Once approved, domain cannot be changed' : 'Onaylandıktan sonra alan adı değiştirilemez'; ?></li>
                    </ul>
                </div>

                <button type="submit" class="w-full bg-slate-700 hover:bg-slate-800 text-white px-6 py-4 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all">
                    <i class="fas fa-paper-plane mr-2"></i>
                    <?php echo $lang == 'en' ? 'Submit Request' : 'Talebi Gönder'; ?>
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Benefits -->
        <div class="mt-8 bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-2xl border border-violet-200 dark:border-violet-800 p-6">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-star text-violet-500"></i>
                <?php echo $lang == 'en' ? 'Benefits of Having a Subdomain' : 'Subdomain Sahibi Olmanın Avantajları'; ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-violet-500 mt-1"></i>
                    <div>
                        <div class="font-bold text-slate-800 dark:text-white text-sm">
                            <?php echo $lang == 'en' ? 'Professional URL' : 'Profesyonel URL'; ?>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Easy to remember and share' : 'Hatırlaması ve paylaşması kolay'; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-violet-500 mt-1"></i>
                    <div>
                        <div class="font-bold text-slate-800 dark:text-white text-sm">
                            <?php echo $lang == 'en' ? 'Better SEO' : 'Daha İyi SEO'; ?>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Improved search rankings' : 'Gelişmiş arama sıralaması'; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-violet-500 mt-1"></i>
                    <div>
                        <div class="font-bold text-slate-800 dark:text-white text-sm">
                            <?php echo $lang == 'en' ? 'Brand Identity' : 'Marka Kimliği'; ?>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Strengthen your brand' : 'Markanızı güçlendirin'; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fas fa-check-circle text-violet-500 mt-1"></i>
                    <div>
                        <div class="font-bold text-slate-800 dark:text-white text-sm">
                            <?php echo $lang == 'en' ? 'QR Code Ready' : 'QR Kod Hazır'; ?>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Perfect for menus' : 'Menüler için mükemmel'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

</body>
</html>
