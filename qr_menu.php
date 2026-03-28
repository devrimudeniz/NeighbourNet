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

// Generate QR code URL - use subdomain if approved, otherwise use /menu/ path
if (!empty($business['subdomain']) && $business['subdomain_status'] === 'approved') {
    $menu_url = "https://" . $business['subdomain'] . ".kalkansocial.com";
} else {
    $menu_url = "https://kalkansocial.com/menu/" . $business_id;
}

// QR Code API (using QR Server API - free)
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=" . urlencode($menu_url);
$qr_code_download = "https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&format=png&data=" . urlencode($menu_url);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'QR Menu Code' : 'QR Menü Kodu'; ?> - <?php echo htmlspecialchars($business['name']); ?></title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        @media print {
            body * { visibility: hidden; }
            #printable, #printable * { visibility: visible; }
            #printable { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32">

    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 md:pt-28 max-w-4xl">
        
        <!-- Header -->
        <div class="mb-8 no-print">
            <div class="flex items-center gap-3 mb-4">
                <a href="menu_manager.php?business_id=<?php echo $business_id; ?>" class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                    <i class="fas fa-arrow-left text-slate-600 dark:text-slate-300"></i>
                </a>
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-800 dark:text-white">
                        <?php echo $lang == 'en' ? 'QR Menu Code' : 'QR Menü Kodu'; ?>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                        <?php echo htmlspecialchars($business['name']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- QR Code Display -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- QR Code Card -->
            <div id="printable" class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 p-8 text-center">
                <div class="mb-6">
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white mb-2">
                        <?php echo htmlspecialchars($business['name']); ?>
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                        <?php echo $lang == 'en' ? 'Scan to view our digital menu' : 'Dijital menümüzü görüntülemek için tarayın'; ?>
                    </p>
                </div>

                <div class="bg-white p-6 rounded-2xl inline-block mb-6">
                    <img src="<?php echo $qr_code_url; ?>" alt="QR Code" class="w-64 h-64 mx-auto">
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <i class="fas fa-link"></i>
                        <span class="font-mono"><?php echo $menu_url; ?></span>
                    </div>
                    <div class="text-xs text-slate-400 dark:text-slate-500">
                        Powered by Kalkan Social
                    </div>
                </div>
            </div>

            <!-- Actions & Info -->
            <div class="space-y-6 no-print">
                <!-- Download Options -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-4">
                        <?php echo $lang == 'en' ? 'Download & Print' : 'İndir & Yazdır'; ?>
                    </h3>
                    <div class="space-y-3">
                        <a href="<?php echo $qr_code_download; ?>" download="qr-menu-<?php echo $business_id; ?>.png" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all w-full">
                            <i class="fas fa-download"></i>
                            <?php echo $lang == 'en' ? 'Download QR Code (PNG)' : 'QR Kodu İndir (PNG)'; ?>
                        </a>
                        <button onclick="window.print()" class="flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all w-full">
                            <i class="fas fa-print"></i>
                            <?php echo $lang == 'en' ? 'Print QR Code' : 'QR Kodu Yazdır'; ?>
                        </button>
                        <button onclick="copyLink()" class="flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all w-full">
                            <i class="fas fa-copy"></i>
                            <?php echo $lang == 'en' ? 'Copy Menu Link' : 'Menü Linkini Kopyala'; ?>
                        </button>
                    </div>
                </div>

                <!-- Usage Instructions -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500"></i>
                        <?php echo $lang == 'en' ? 'How to Use' : 'Nasıl Kullanılır'; ?>
                    </h3>
                    <ol class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                            <span><?php echo $lang == 'en' ? 'Download or print the QR code' : 'QR kodu indirin veya yazdırın'; ?></span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                            <span><?php echo $lang == 'en' ? 'Place it on tables, menu cards, or entrance' : 'Masalara, menü kartlarına veya girişe yerleştirin'; ?></span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                            <span><?php echo $lang == 'en' ? 'Customers scan with their phone camera' : 'Müşteriler telefon kameralarıyla tarar'; ?></span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                            <span><?php echo $lang == 'en' ? 'They instantly see your digital menu!' : 'Dijital menünüzü anında görürler!'; ?></span>
                        </li>
                    </ol>
                </div>

                <!-- Features -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">
                        <?php echo $lang == 'en' ? 'Benefits' : 'Avantajlar'; ?>
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                            <div>
                                <div class="font-bold text-slate-800 dark:text-white text-sm">
                                    <?php echo $lang == 'en' ? 'Always Up-to-Date' : 'Her Zaman Güncel'; ?>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    <?php echo $lang == 'en' ? 'Update prices and items instantly' : 'Fiyatları ve ürünleri anında güncelleyin'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                            <div>
                                <div class="font-bold text-slate-800 dark:text-white text-sm">
                                    <?php echo $lang == 'en' ? 'Eco-Friendly' : 'Çevre Dostu'; ?>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    <?php echo $lang == 'en' ? 'No need to print new menus' : 'Yeni menü basmaya gerek yok'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                            <div>
                                <div class="font-bold text-slate-800 dark:text-white text-sm">
                                    <?php echo $lang == 'en' ? 'Multilingual' : 'Çok Dilli'; ?>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    <?php echo $lang == 'en' ? 'Supports Turkish and English' : 'Türkçe ve İngilizce destekler'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                            <div>
                                <div class="font-bold text-slate-800 dark:text-white text-sm">
                                    <?php echo $lang == 'en' ? 'Analytics' : 'Analitik'; ?>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    <?php echo $lang == 'en' ? 'Track views and popular items' : 'Görüntülemeleri ve popüler ürünleri takip edin'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Link -->
                <a href="<?php echo $menu_url; ?>" target="_blank" class="block bg-slate-700 hover:bg-slate-800 text-white p-6 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all text-center">
                    <i class="fas fa-external-link-alt text-2xl mb-2"></i>
                    <div class="font-black">
                        <?php echo $lang == 'en' ? 'Preview Your Menu' : 'Menünüzü Önizleyin'; ?>
                    </div>
                    <div class="text-sm text-white/80 mt-1">
                        <?php echo $lang == 'en' ? 'See how customers will view it' : 'Müşterilerin nasıl göreceğini görün'; ?>
                    </div>
                </a>
            </div>
        </div>

    </main>

    <script>
    function copyLink() {
        const link = '<?php echo $menu_url; ?>';
        navigator.clipboard.writeText(link).then(() => {
            alert('<?php echo $lang == 'en' ? 'Menu link copied to clipboard!' : 'Menü linki panoya kopyalandı!'; ?>');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
    </script>

</body>
</html>
