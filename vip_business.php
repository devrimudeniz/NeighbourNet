<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_badge = $_SESSION['badge'] ?? null;

// Check if already VIP
$is_already_vip = in_array($current_badge, ['vip_business', 'founder', 'moderator']);

// Handle payment confirmation (in a real app, integrate with payment gateway)
$payment_success = isset($_GET['payment']) && $_GET['payment'] === 'success';
$payment_error = isset($_GET['error']) ? $_GET['error'] : null;

// If admin manually approves via URL (simple method for now)
if (isset($_GET['activate']) && $_GET['activate'] === 'manual' && isset($_GET['token'])) {
    // Verify token (simple security)
    $expected_token = md5($user_id . 'vip_business_secret_key');
    if ($_GET['token'] === $expected_token) {
        $pdo->prepare("UPDATE users SET badge = 'vip_business' WHERE id = ?")->execute([$user_id]);
        $_SESSION['badge'] = 'vip_business';
        $payment_success = true;
    }
}

// Price
$vip_price = 250;
$vip_price_formatted = number_format($vip_price, 0) . ' TL';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Business | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-4xl">
        
        <?php if($is_already_vip): ?>
        <!-- Already VIP -->
        <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-[3rem] p-12 text-white text-center shadow-2xl">
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-crown text-5xl"></i>
            </div>
            <h1 class="text-4xl font-black mb-4"><?php echo $lang == 'en' ? 'You are VIP!' : 'VIP Üyesiniz!'; ?></h1>
            <p class="text-xl opacity-90 mb-8">
                <?php echo $lang == 'en' 
                    ? 'You already have VIP Business access. Add unlimited businesses!' 
                    : 'Zaten VIP Business erişiminiz var. Sınırsız işletme ekleyebilirsiniz!'; ?>
            </p>
            <a href="add_business" class="inline-block bg-white text-amber-600 px-8 py-4 rounded-2xl font-black text-lg hover:scale-105 transition-transform shadow-lg">
                <i class="fas fa-plus mr-2"></i><?php echo $t['add_business'] ?? 'İşletme Ekle'; ?>
            </a>
        </div>

        <?php elseif($payment_success): ?>
        <!-- Payment Success -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-500 rounded-[3rem] p-12 text-white text-center shadow-2xl">
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce">
                <i class="fas fa-check text-5xl"></i>
            </div>
            <h1 class="text-4xl font-black mb-4"><?php echo $lang == 'en' ? 'Welcome to VIP!' : 'VIP\'e Hoş Geldiniz!'; ?></h1>
            <p class="text-xl opacity-90 mb-8">
                <?php echo $lang == 'en' 
                    ? 'Your VIP Business membership is now active. You can add unlimited businesses!' 
                    : 'VIP Business üyeliğiniz aktif! Artık sınırsız işletme ekleyebilirsiniz!'; ?>
            </p>
            <a href="add_business" class="inline-block bg-white text-emerald-600 px-8 py-4 rounded-2xl font-black text-lg hover:scale-105 transition-transform shadow-lg">
                <i class="fas fa-plus mr-2"></i><?php echo $t['add_business'] ?? 'İşletme Ekle'; ?>
            </a>
        </div>

        <?php else: ?>
        <!-- VIP Offer -->
        <div class="text-center mb-12">
            <span class="inline-block bg-gradient-to-r from-amber-500 to-orange-500 text-white px-4 py-2 rounded-full text-sm font-black mb-4">
                <i class="fas fa-crown mr-2"></i>VIP BUSINESS
            </span>
            <h1 class="text-5xl font-black mb-4 bg-clip-text text-transparent bg-gradient-to-r from-amber-600 to-orange-600">
                <?php echo $lang == 'en' ? 'Grow Your Business Empire' : 'İşletme İmparatorluğunuzu Büyütün'; ?>
            </h1>
            <p class="text-xl text-slate-600 dark:text-slate-400">
                <?php echo $lang == 'en' 
                    ? 'Add unlimited businesses to Kalkan Social directory' 
                    : 'Kalkan Social rehberine sınırsız işletme ekleyin'; ?>
            </p>
        </div>

        <div class="flex flex-col-reverse md:grid md:grid-cols-2 gap-8 mb-12">
            <!-- Standard Plan -->
            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl p-8 border border-slate-200 dark:border-slate-700">
                <div class="text-center mb-6">
                    <h3 class="text-xl font-bold text-slate-500 mb-2"><?php echo $lang == 'en' ? 'Standard' : 'Standart'; ?></h3>
                    <div class="text-4xl font-black text-slate-400"><?php echo $lang == 'en' ? 'FREE' : 'ÜCRETSİZ'; ?></div>
                </div>
                <ul class="space-y-3 text-slate-600 dark:text-slate-400 mb-6">
                    <li class="flex items-center gap-3">
                        <i class="fas fa-check text-emerald-500"></i>
                        <span><?php echo $lang == 'en' ? '1 Business listing' : '1 İşletme kaydı'; ?></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-check text-emerald-500"></i>
                        <span><?php echo $lang == 'en' ? 'Basic profile' : 'Temel profil'; ?></span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-400">
                        <i class="fas fa-times text-red-400"></i>
                        <span><?php echo $lang == 'en' ? 'Multiple businesses' : 'Birden fazla işletme'; ?></span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-400">
                        <i class="fas fa-times text-red-400"></i>
                        <span><?php echo $lang == 'en' ? 'VIP badge' : 'VIP rozeti'; ?></span>
                    </li>
                </ul>
                <a href="request_verification" class="block w-full text-center bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 py-4 rounded-2xl font-bold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <?php echo $lang == 'en' ? 'Get Verified Free' : 'Ücretsiz Doğrulan'; ?>
                </a>
            </div>

            <!-- VIP Plan -->
            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl p-6 pt-10 md:p-8 border-2 border-amber-400 relative shadow-2xl shadow-amber-500/20 mt-6 md:mt-0">
                <!-- VIP badge -->
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-2 rounded-full text-sm font-black flex items-center gap-2 shadow-lg">
                    <i class="fas fa-crown"></i>
                    VIP BUSINESS
                </div>
                
                <div class="text-center mb-6 mt-4">
                    <div class="text-5xl font-black text-amber-600"><?php echo $vip_price_formatted; ?></div>
                    <div class="text-sm text-slate-500"><?php echo $lang == 'en' ? 'One-time payment' : 'Tek seferlik ödeme'; ?></div>
                </div>
                
                <ul class="space-y-3 text-slate-700 dark:text-slate-300 mb-6">
                    <li class="flex items-center gap-3">
                        <i class="fas fa-crown text-amber-500"></i>
                        <span class="font-bold"><?php echo $lang == 'en' ? 'Unlimited businesses' : 'Sınırsız işletme'; ?></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-check text-amber-500"></i>
                        <span><?php echo $lang == 'en' ? 'VIP badge on profile' : 'Profilde VIP rozeti'; ?></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-check text-amber-500"></i>
                        <span><?php echo $lang == 'en' ? 'Priority support' : 'Öncelikli destek'; ?></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-check text-amber-500"></i>
                        <span><?php echo $lang == 'en' ? 'Featured listings' : 'Öne çıkan ilanlar'; ?></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-infinity text-amber-500"></i>
                        <span><?php echo $lang == 'en' ? 'Lifetime access' : 'Ömür boyu erişim'; ?></span>
                    </li>
                </ul>
                
                <!-- Payment Methods -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-4 mb-4 border border-amber-200 dark:border-amber-800">
                    <p class="text-sm font-bold mb-3 text-center text-amber-700 dark:text-amber-400"><?php echo $lang == 'en' ? 'Payment Methods' : 'Ödeme Yöntemleri'; ?></p>
                    <div class="flex justify-center gap-4 text-2xl text-amber-600">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fas fa-university"></i>
                    </div>
                </div>
                
                <a href="https://wa.me/905340119980?text=VIP%20Business%20satın%20almak%20istiyorum.%20Kullanıcı%20ID:%20<?php echo $user_id; ?>" 
                   target="_blank"
                   class="block w-full text-center bg-gradient-to-r from-amber-500 to-orange-500 text-white py-4 rounded-2xl font-black text-lg hover:scale-105 transition-transform shadow-lg shadow-amber-500/30">
                    <i class="fab fa-whatsapp mr-2"></i><?php echo $lang == 'en' ? 'Buy Now via WhatsApp' : 'WhatsApp ile Satın Al'; ?>
                </a>
                
                <p class="text-xs text-center mt-3 text-slate-500">
                    <?php echo $lang == 'en' 
                        ? 'Contact us on WhatsApp to complete payment and activate VIP' 
                        : 'Ödeme ve VIP aktivasyonu için WhatsApp\'tan iletişime geçin'; ?>
                </p>
            </div>
        </div>

        <!-- FAQ -->
        <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl p-8 border border-slate-200 dark:border-slate-700">
            <h3 class="text-xl font-black mb-6 text-center"><?php echo $lang == 'en' ? 'Frequently Asked Questions' : 'Sık Sorulan Sorular'; ?></h3>
            
            <div class="space-y-4">
                <details class="group">
                    <summary class="flex justify-between items-center cursor-pointer p-4 bg-slate-50 dark:bg-slate-900 rounded-xl font-bold">
                        <?php echo $lang == 'en' ? 'How do I activate VIP?' : 'VIP\'i nasıl aktifleştiririm?'; ?>
                        <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="p-4 text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' 
                            ? 'Click the WhatsApp button above, complete the payment, and we will activate your VIP status within minutes.' 
                            : 'Yukarıdaki WhatsApp butonuna tıklayın, ödemeyi tamamlayın, VIP statünüzü dakikalar içinde aktifleştireceğiz.'; ?>
                    </p>
                </details>
                
                <details class="group">
                    <summary class="flex justify-between items-center cursor-pointer p-4 bg-slate-50 dark:bg-slate-900 rounded-xl font-bold">
                        <?php echo $lang == 'en' ? 'Is this a one-time payment?' : 'Bu tek seferlik mi?'; ?>
                        <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="p-4 text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' 
                            ? 'Yes! Pay once and enjoy VIP Business benefits forever. No monthly fees.' 
                            : 'Evet! Bir kez ödeyin, VIP Business avantajlarının keyfini sonsuza kadar çıkarın. Aylık ücret yok.'; ?>
                    </p>
                </details>
                
                <details class="group">
                    <summary class="flex justify-between items-center cursor-pointer p-4 bg-slate-50 dark:bg-slate-900 rounded-xl font-bold">
                        <?php echo $lang == 'en' ? 'Can I get a refund?' : 'İade alabilir miyim?'; ?>
                        <i class="fas fa-chevron-down group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="p-4 text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' 
                            ? 'We offer a 7-day money-back guarantee if you are not satisfied.' 
                            : 'Memnun kalmazsanız 7 gün içinde para iade garantisi sunuyoruz.'; ?>
                    </p>
                </details>
            </div>
        </div>
        <?php endif; ?>

    </main>

</body>
</html>
