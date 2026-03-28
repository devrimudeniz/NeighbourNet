<?php
header('Content-Type: application/json');
require_once '../includes/env.php';
header('Cache-Control: public, max-age=3600'); // Tarayıcı da 1 saat önbelleğe alsın

// ExchangeRate-API Configuration
$api_key = env_value('EXCHANGERATE_API_KEY', '');
$api_url = "https://v6.exchangerate-api.com/v6/{$api_key}/latest/GBP";

// Cache configuration
$cache_file = __DIR__ . '/../cache/currency_cache.json';
// ÖNEMLİ DEĞİŞİKLİK: Cache süresini 1 saatten (3600) -> 12 saate (43200) çıkardık.
$cache_duration = 43200; 

// Ensure cache directory exists
$cache_dir = dirname($cache_file);
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0777, true);
}

// 1. ADIM: Cache var mı ve taze mi kontrol et
$cache_exists = file_exists($cache_file);
$cached_data = $cache_exists ? json_decode(file_get_contents($cache_file), true) : null;

if ($cache_exists && (time() - filemtime($cache_file) < $cache_duration)) {
    if (empty($cached_data['try_to_gbp']) && !empty($cached_data['rate'])) {
        $r = (float)$cached_data['rate'];
        $cached_data['try_to_gbp'] = round(1 / $r, 6);
        $cached_data['try_to_usd'] = round(1.27 / $r, 6);
        $cached_data['try_to_eur'] = round(1.17 / $r, 6);
    }
    echo json_encode($cached_data);
    exit;
}

if ($api_key === '') {
    if ($cached_data) {
        $cached_data['warning'] = 'Serving cache because EXCHANGERATE_API_KEY is missing';
        echo json_encode($cached_data);
        exit;
    }
}

// 2. ADIM: Cache bayatlamışsa veya yoksa yenisini çek (CURL ile Timeout Ayarlı)
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Bağlanmak için max 2 saniye bekle
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);        // Cevap almak için max 2 saniye bekle
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Eğer hata varsa veya cevap boşsa
    if ($response === false || $http_code !== 200) {
        throw new Exception("API Timeout or Error: " . $curl_error);
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['conversion_rates']['TRY'])) {
        $rates = $data['conversion_rates'];
        $try_per_gbp = (float)$rates['TRY'];
        $usd_per_gbp = isset($rates['USD']) ? (float)$rates['USD'] : 1.27;
        $eur_per_gbp = isset($rates['EUR']) ? (float)$rates['EUR'] : 1.17;
        $last_update = date('Y-m-d H:i:s');
        
        // TRY -> diğer para birimleri (1 TRY = X GBP/USD/EUR)
        $result = [
            'status' => 'success',
            'rate' => round($try_per_gbp, 2),
            'try_to_gbp' => round(1 / $try_per_gbp, 6),
            'try_to_usd' => round($usd_per_gbp / $try_per_gbp, 6),
            'try_to_eur' => round($eur_per_gbp / $try_per_gbp, 6),
            'last_update' => $last_update,
            'source' => 'live'
        ];
        
        // Save to cache
        file_put_contents($cache_file, json_encode($result));
        
        echo json_encode($result);
    } else {
        throw new Exception("Invalid Data Structure");
    }
    
} catch (Exception $e) {
    // 3. ADIM: HATA OLDU! API CEVAP VERMEDİ!
    // Kullanıcıyı bekletmek yerine VARSA eski cache'i göster (Bayat veri hiç yoktan iyidir)
    if ($cached_data) {
        $cached_data['warning'] = 'Serving stale cache (API Timeout)';
        // Dosya tarihini güncelle ki ("touch") bir sonraki istek hemen tekrar API'yi denemesin, 5 dk beklesin
        touch($cache_file, time() - ($cache_duration - 300)); 
        echo json_encode($cached_data);
    } else {
        // 4. ADIM: Cache yok, API çalışmadı -> HARDCODED FALLBACK
        $try_per_gbp = 57.15;
        $fallback_data = [
            'status' => 'success',
            'rate' => $try_per_gbp,
            'try_to_gbp' => round(1 / $try_per_gbp, 6),
            'try_to_usd' => round(1.27 / $try_per_gbp, 6),
            'try_to_eur' => round(1.17 / $try_per_gbp, 6),
            'last_update' => date('Y-m-d H:i:s'),
            'source' => 'hardcoded_fallback'
        ];
        file_put_contents($cache_file, json_encode($fallback_data));
        echo json_encode($fallback_data);
    }
}
?>
