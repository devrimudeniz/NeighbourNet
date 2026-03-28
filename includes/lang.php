<?php
/**
 * Language Detection and Management System
 * Detects user's preferred language and loads translations
 */
require_once __DIR__ . '/site_settings.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Detect user's preferred language based on priority:
 * 1. User database preference (if logged in)
 * 2. Session/Cookie override
 * 3. Browser language
 * 4. Default (tr)
 */
function detectLanguage($pdo = null) {
    // 1. Check if user is logged in and has saved preference
    if (isset($_SESSION['user_id']) && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT preferred_language FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_lang = $stmt->fetchColumn();
            if ($user_lang) {
                return $user_lang;
            }
        } catch (PDOException $e) {
            // Column might not exist yet, continue to other methods
        }
    }

    // 2. Check session (current browsing session only)
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }

    // 3. For LOGGED-IN users: also check cookie (persistent preference)
    //    For guests: ignore cookie — always default to English
    //    This prevents old 'tr' cookies (from browser detection era) 
    //    from overriding the default in Facebook WebView, new tabs, etc.
    if (isset($_SESSION['user_id']) && isset($_COOKIE['language'])) {
        return $_COOKIE['language'];
    }

    // 4. Default: English for all guests
    return 'en';
}

/**
 * Set user's language preference
 */
function setLanguage($lang, $pdo = null) {
    if (!in_array($lang, ['tr', 'en'])) {
        return false;
    }

    // Set in session
    $_SESSION['language'] = $lang;

    // Set cookie for 1 year
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');

    // If user is logged in, save to database
    if (isset($_SESSION['user_id']) && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
            $stmt->execute([$lang, $_SESSION['user_id']]);
        } catch (PDOException $e) {
            // Ignore if column doesn't exist yet
        }
    }

    return true;
}

/**
 * Load translations for the current language
 */
function loadTranslations($lang) {
    $file = __DIR__ . "/../lang/{$lang}.php";
    if (file_exists($file)) {
        return require $file;
    }
    // Fallback to Turkish if file not found
    return require __DIR__ . "/../lang/tr.php";
}

// Auto-detect language if not manually set
$current_lang = detectLanguage($pdo ?? null);

// Handle language switch request
if (isset($_GET['lang']) && in_array($_GET['lang'], ['tr', 'en'])) {
    setLanguage($_GET['lang'], $pdo ?? null);
    $current_lang = $_GET['lang'];
    
    // Redirect to remove ?lang= from URL
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect_url");
    exit();
}

// Load translations
$t = loadTranslations($current_lang);
$lang = $current_lang; // Language code string ('tr' or 'en') for conditionals

$t['site_name'] = site_name();
$t['site_title'] = site_title($current_lang);

// ==========================================
// THEME MANAGEMENT (Added)
// ==========================================
function detectTheme($pdo_param = null) {
    // Ensure PDO is available
    $pdo_conn = $pdo_param;
    if (!$pdo_conn && isset($GLOBALS['pdo'])) {
        $pdo_conn = $GLOBALS['pdo'];
    }

    // 1. Check DB (Priority)
    if (isset($_SESSION['user_id']) && $pdo_conn) {
        try {
            $stmt = $pdo_conn->prepare("SELECT theme FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_theme = $stmt->fetchColumn();
            
            if ($user_theme && in_array($user_theme, ['light', 'dark'])) {
                // Update Session & Cookie to match DB
                if (($_SESSION['theme'] ?? '') !== $user_theme) {
                    $_SESSION['theme'] = $user_theme;
                    setcookie('theme', $user_theme, time() + (86400 * 30), "/"); // Sync cookie
                }
                return $user_theme;
            }
        } catch (PDOException $e) {
            // DB Error, fallthrough
        }
    }
    
    // 2. Session
    if (isset($_SESSION['theme'])) return $_SESSION['theme'];
    
    // 3. Cookie
    if (isset($_COOKIE['theme'])) return $_COOKIE['theme'];
    
    // 4. Default
    return 'light';
}

// Pass global $pdo explicitly
$theme = detectTheme($pdo ?? $GLOBALS['pdo'] ?? null);
if (!defined('CURRENT_THEME')) define('CURRENT_THEME', $theme);


// Helper function for JavaScript translations
function getJsTranslations($t) {
    return json_encode([
        'loading' => $t['loading'],
        'error' => $t['error'],
        'success' => $t['success'],
        'confirm' => $t['confirm'],
        'cancel' => $t['cancel'],
    ]);
}
?>
