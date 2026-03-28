<?php
require_once __DIR__ . '/env.php';
// Security Headers
if (!headers_sent()) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(self), microphone=(self), camera=(self)");
    // Helper for CSP - Adjusted for shared hosting compatibility while improving security
    // We allow 'unsafe-inline' and 'unsafe-eval' because the application relies on them (e.g. inline scripts, likely some jQuery plugins).
    // wildcards allowed for media/images to prevent broken user content.
    header("Content-Security-Policy: default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; img-src * data: blob:; media-src * data: blob:; connect-src 'self' https:; font-src 'self' https: data:; frame-src 'self' https:;");
}

// Session lifetime: 30 days (in seconds)
$session_lifetime = 30 * 24 * 60 * 60; // 2592000 seconds

// Only set session ini settings if no session is active yet
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters BEFORE session_start() is called
    // Use the @ opperator to suppress warnings on some shared hosting setups where ini_set is restricted
    @ini_set('session.gc_maxlifetime', $session_lifetime);
    @ini_set('session.cookie_lifetime', $session_lifetime);
    
    // Configure cookie params
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// CSRF Protection Functions
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

$host = env_value('DB_HOST', '127.0.0.1');
$db   = env_value('DB_NAME', 'kalkansocial');
$user = env_value('DB_USER', 'root');
$pass = env_value('DB_PASS', '');
$charset = env_value('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If DB doesn't exist, try connecting without dbname to create it
    if ($e->getCode() == 1049) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db`");
        } catch (\PDOException $ex) {
            die("Database connection failed: " . $ex->getMessage());
        }
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Auto-login with remember token if session not active
if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/auth_helper.php';
    checkRememberMe($pdo);
}
