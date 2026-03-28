<?php
/**
 * Bootstrap - Ortak include'ları merkezi bir yerde toplar
 * Tüm sayfalar bu dosyayı include edebilir.
 * 
 * Sağladıkları:
 *  - session_start()
 *  - $pdo (veritabanı bağlantısı)
 *  - $lang, $t (dil desteği)
 *  - CSRF fonksiyonları
 *  - Auth helper (remember me)
 */

// session_start() zaten db.php içinde session parametreleri ayarladıktan sonra
// lang.php içinde çağrılıyor. Ama bazı sayfalar kendi başına çağırıyor.
// Güvenli başlatma:
if (session_status() === PHP_SESSION_NONE) {
    // db.php session cookie params ayarlıyor, önce onu yükle
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';
