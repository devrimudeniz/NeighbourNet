<?php
/**
 * Security Helper - Merkezi güvenlik fonksiyonları
 * SQL Injection, XSS, CSRF, Upload, Rate Limit korumaları
 */

// ── CSRF Token ──
// (generate_csrf_token ve verify_csrf_token db.php'de tanımlı, burada API için kısa yardımcılar)

/**
 * API endpoint'leri için CSRF kontrolü
 * Başarısızsa JSON hata döner ve script durur
 */
function require_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
}

/**
 * Giriş yapmış kullanıcı kontrolü (API için)
 * Başarısızsa JSON hata döner ve script durur
 */
function require_auth() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    return (int)$_SESSION['user_id'];
}

/**
 * POST method kontrolü (API için)
 */
function require_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
}

// ── Input Sanitization ──

/**
 * Integer input - güvenli cast
 */
function safe_int($value, $default = 0) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
}

/**
 * String input - trim + strip, opsiyonel max uzunluk
 */
function safe_string($value, $max_length = 0) {
    $val = trim((string)$value);
    if ($max_length > 0 && mb_strlen($val) > $max_length) {
        $val = mb_substr($val, 0, $max_length);
    }
    return $val;
}

/**
 * Email doğrulama
 */
function safe_email($value) {
    return filter_var(trim($value), FILTER_VALIDATE_EMAIL) ?: '';
}

/**
 * URL doğrulama
 */
function safe_url($value) {
    $url = filter_var(trim($value), FILTER_VALIDATE_URL);
    if (!$url) return '';
    // sadece http/https
    if (!preg_match('#^https?://#i', $url)) return '';
    return $url;
}

// ── File Upload Security ──

/**
 * Güvenli dosya yükleme doğrulaması
 * @param array $file $_FILES['key']
 * @param array $allowed_types İzin verilen MIME tipleri
 * @param int $max_size_mb Maksimum boyut (MB)
 * @return array ['valid' => bool, 'error' => string]
 */
function validate_upload($file, $allowed_types = [], $max_size_mb = 5) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error: ' . ($file['error'] ?? 'unknown')];
    }

    // Boyut kontrolü
    $max_bytes = $max_size_mb * 1024 * 1024;
    if ($file['size'] > $max_bytes) {
        return ['valid' => false, 'error' => "File too large (max {$max_size_mb}MB)"];
    }

    // MIME tipi kontrolü
    if (!empty($allowed_types)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowed_types)) {
            return ['valid' => false, 'error' => 'Invalid file type: ' . $mime];
        }
    }

    // Dosya adında path traversal kontrolü
    if (preg_match('/[\/\\\\]/', $file['name']) || strpos($file['name'], '..') !== false) {
        return ['valid' => false, 'error' => 'Invalid filename'];
    }

    return ['valid' => true, 'error' => ''];
}

/** İzin verilen resim MIME tipleri */
function allowed_image_types() {
    return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
}

/** İzin verilen video MIME tipleri */
function allowed_video_types() {
    return ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/mpeg', 'video/webm'];
}

// ── Open Redirect Prevention ──

/**
 * Güvenli yönlendirme - sadece kendi sitemize
 */
function safe_redirect($path, $fallback = '/') {
    // Sadece relative path'lere izin ver
    if (empty($path) || preg_match('#^https?://#i', $path) || strpos($path, '//') === 0) {
        $path = $fallback;
    }
    // Path traversal kontrolü
    if (strpos($path, '..') !== false) {
        $path = $fallback;
    }
    header('Location: ' . $path);
    exit;
}

/**
 * Host kontrolü ile güvenli tam URL yönlendirme
 */
function safe_redirect_url($url, $fallback = '/') {
    $parsed = parse_url($url);
    $allowed_hosts = ['kalkansocial.com', 'www.kalkansocial.com'];
    
    if (!isset($parsed['host']) || !in_array($parsed['host'], $allowed_hosts)) {
        header('Location: ' . $fallback);
        exit;
    }
    header('Location: ' . $url);
    exit;
}

// ── SQL Security ──

/**
 * Whitelist check - dinamik kolon/tablo adları için
 */
function validate_column($name, $allowed) {
    if (!in_array($name, $allowed, true)) {
        throw new InvalidArgumentException("Invalid column name: $name");
    }
    return $name;
}

// ── Brute Force / Honeypot ──

/**
 * Honeypot alan kontrolü - bot tespiti
 * Formlarınıza gizli bir alan ekleyin: <input type="text" name="website_url" style="display:none" value="">
 * Bot doldurursa spam olarak algılanır
 */
function check_honeypot($field = 'website_url') {
    if (!empty($_POST[$field])) {
        // Bot tespit edildi - sessizce başarılı gibi davran
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }
}

/**
 * JSON response helper
 */
function json_error($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function json_success($data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}
