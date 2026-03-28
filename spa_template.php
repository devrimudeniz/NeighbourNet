<?php
/**
 * SPA Template - Örnek Sayfa
 * 
 * Bu dosya yeni sayfaları SPA'ya uyumlu şekilde oluşturmak için şablon görevi görür.
 * 
 * KULLANIM:
 * 1. Bu dosyayı kopyalayın ve yeni sayfanızın adıyla kaydedin
 * 2. İçeriği main bölümüne yazın
 * 3. Hazır!
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/spa_helper.php';

// SPA kontrolü
$isSPA = isSPARequest();

// Sayfa değişkenleri
$pageTitle = 'Sayfa Başlığı | Kalkan Social';

// Veri çekme işlemleri burada yapılır
// $data = ...

?>
<?php if (!$isSPA): ?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20">

    <?php include 'includes/header.php'; ?>
    
    <div id="spa-content">
<?php endif; ?>

    <!-- ============================================ -->
    <!-- ANA İÇERİK BURADAN BAŞLAR -->
    <!-- ============================================ -->
    
    <main class="container mx-auto px-4 md:px-6 pt-24 pb-24">
        
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-black mb-6"><?php echo $lang == 'en' ? 'Page Title' : 'Sayfa Başlığı'; ?></h1>
            
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-slate-600 dark:text-slate-400">
                    İçeriğiniz burada...
                </p>
            </div>
        </div>
        
    </main>

    <!-- ============================================ -->
    <!-- ANA İÇERİK BURADA BİTER -->
    <!-- ============================================ -->

<?php if (!$isSPA): ?>
    </div><!-- #spa-content -->
</body>
</html>
<?php endif; ?>
