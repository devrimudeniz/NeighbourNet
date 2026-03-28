<?php
/**
 * Kalkan Social - SPA Router Helper
 * 
 * Bu dosya SPA isteklerini tespit eder ve sayfa içeriğini uygun şekilde render eder.
 * 
 * KULLANIM:
 * 1. Her sayfanın başında bu dosyayı include edin
 * 2. İçeriği spa_content_start() ve spa_content_end() arasına yazın
 * 3. Ya da isSPARequest() fonksiyonunu kullanarak manuel kontrol yapın
 * 
 * @version 1.0
 */

/**
 * SPA isteği olup olmadığını kontrol eder
 * 
 * @return bool
 */
function isSPARequest(): bool {
    return (
        isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true'
    ) || (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
        isset($_SERVER['HTTP_X_SPA_REQUEST'])
    );
}

/**
 * SPA modu aktif olduğunda sadece içerik döndürür
 * Normal isteklerde tam sayfa döndürür
 * 
 * @param callable $contentCallback İçerik render eden fonksiyon
 * @param array $options Opsiyonlar (title, meta vb.)
 */
function renderSPAPage(callable $contentCallback, array $options = []): void {
    $title = $options['title'] ?? 'Kalkan Social';
    $meta = $options['meta'] ?? [];

    if (isSPARequest()) {
        // SPA request - sadece içerik ve title döndür
        echo "<!-- SPA-TITLE: {$title} -->";
        ob_start();
        $contentCallback();
        $content = ob_get_clean();
        echo $content;
    } else {
        // Normal request - tam sayfa render
        $GLOBALS['spa_page_title'] = $title;
        $GLOBALS['spa_page_meta'] = $meta;
        $GLOBALS['spa_content_callback'] = $contentCallback;
        
        // Layout'u include et
        include __DIR__ . '/layout.php';
    }
}

// ============================================
// ALTERNATİF YÖNTEM: Output Buffering ile
// ============================================

$_SPA_STARTED = false;

/**
 * SPA içerik bölümünü başlatır
 * Normal isteklerde header'ı include eder
 */
function spa_start(): void {
    global $_SPA_STARTED;
    $_SPA_STARTED = true;
    
    if (!isSPARequest()) {
        // Normal istek - header'ı include et
        include_once __DIR__ . '/header.php';
        echo '<div id="spa-content">';
    }
    
    // Output buffering başlat
    ob_start();
}

/**
 * SPA içerik bölümünü bitirir
 * Normal isteklerde footer'ı include eder
 */
function spa_end(): void {
    global $_SPA_STARTED;
    
    if (!$_SPA_STARTED) {
        trigger_error('spa_end() called without spa_start()', E_USER_WARNING);
        return;
    }
    
    $content = ob_get_clean();
    
    if (isSPARequest()) {
        // SPA request - sadece içerik döndür
        echo $content;
    } else {
        // Normal istek - içerik ve footer
        echo $content;
        echo '</div>'; // #spa-content kapanışı
        
        // Footer varsa include et
        if (file_exists(__DIR__ . '/footer.php')) {
            include_once __DIR__ . '/footer.php';
        }
        
        // SPA.js'i ekle
        echo '<script src="/js/spa.js" defer></script>';
    }
    
    $_SPA_STARTED = false;
}

// ============================================
// BASİT WRAPPER FONKSİYON
// ============================================

/**
 * Sayfa için wrapper - en basit kullanım
 * 
 * @param string $title Sayfa başlığı
 * @param callable $content İçerik render fonksiyonu
 * @param array $options Ek opsiyonlar
 */
function spa_page(string $title, callable $content, array $options = []): void {
    $GLOBALS['page_title'] = $title;
    
    if (isSPARequest()) {
        // SPA Request - Sadece içerik
        header('Content-Type: text/html; charset=utf-8');
        header('X-SPA-Title: ' . urlencode($title));
        
        // Title bilgisini de gönder (JavaScript'te parse edilebilir)
        echo "<!-- spa-title:{$title} -->\n";
        $content();
    } else {
        // Normal Request - Tam sayfa
        include_once __DIR__ . '/../includes/header.php';
        
        echo '<div id="spa-content">';
        echo '<main class="container mx-auto px-4 md:px-6 pt-24 pb-24">';
        $content();
        echo '</main>';
        echo '</div>';
        
        // SPA.js'i ekle
        echo '<script src="js/spa.js" defer></script>';
        
        // Footer/Navbar
        if (file_exists(__DIR__ . '/../includes/mobile_nav.php')) {
            include_once __DIR__ . '/../includes/mobile_nav.php';
        }
        
        echo '</body></html>';
    }
}
