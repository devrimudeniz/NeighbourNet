<?php
/**
 * CDN Helper
 * Manages CDN URLs for static assets
 * 
 * Usage:
 *   cdn_url('/css/main.min.css')          -> https://cdn.kalkansocial.com/css/main.min.css?v=1234567890
 *   cdn_url('/js/app.js', false)          -> https://cdn.kalkansocial.com/js/app.js
 *   upload_url('/uploads/image.webp')     -> https://cdn.kalkansocial.com/uploads/image.webp
 */

// CDN Configuration
define('CDN_ENABLED', false);
define('CDN_BASE_URL', 'https://cdn.kalkansocial.com');
define('MAIN_BASE_URL', 'https://kalkansocial.com');

// Version for cache busting (update this when deploying new assets)
define('ASSET_VERSION', '20260101');

/**
 * Get CDN URL for a static asset
 * 
 * @param string $path Path to the asset (e.g., '/css/main.min.css')
 * @param bool $versioned Whether to add version query parameter
 * @return string Full CDN URL
 */
function cdn_url($path, $versioned = true) {
    // Ensure path starts with /
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    // Use CDN if enabled, otherwise fallback to main domain
    $base_url = CDN_ENABLED ? CDN_BASE_URL : MAIN_BASE_URL;
    
    // Build URL
    $url = $base_url . $path;
    
    // Add version for cache busting (for CSS/JS files)
    if ($versioned) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url .= $separator . 'v=' . ASSET_VERSION;
    }
    
    return $url;
}

/**
 * Get CDN URL for user uploads
 * 
 * @param string $path Path relative to uploads directory
 * @return string Full CDN URL
 */
function upload_url($path) {
    // Remove leading 'uploads/' if present
    $path = preg_replace('/^uploads\//', '', $path);
    
    // Ensure path starts with /
    if (strpos($path, '/') !== 0) {
        $path = '/uploads/' . $path;
    } else if (strpos($path, '/uploads') !== 0) {
        $path = '/uploads' . $path;
    }
    
    return cdn_url($path, false);
}

/**
 * Convert any media path to CDN URL
 * Handles avatars, post images, videos, etc.
 * 
 * @param string $path Original path (can be relative, absolute, or already a URL)
 * @return string Full CDN URL or original if already external
 */
function media_url($path) {
    // Return as-is if empty or already external URL
    if (empty($path)) {
        return $path;
    }
    
    // Already an external URL (http/https)
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        // Check if it's our main domain, convert to CDN
        if (strpos($path, MAIN_BASE_URL) === 0) {
            $relativePath = str_replace(MAIN_BASE_URL, '', $path);
            return CDN_BASE_URL . $relativePath;
        }
        return $path; // External URL, return as-is
    }
    
    // Clean the path - remove leading ./ or /
    $cleanPath = ltrim($path, '/.');
    
    // Check if it's a media path that should use CDN
    $mediaPatterns = [
        'uploads/',           // User uploads
        'profiles/',          // Old profile path format
        'assets/',            // Static assets
        'css/',               // CSS files
        'js/',                // JS files
        'fonts/',             // Font files
        'images/',            // Static images
    ];
    
    foreach ($mediaPatterns as $pattern) {
        if (strpos($cleanPath, $pattern) === 0) {
            return CDN_BASE_URL . '/' . $cleanPath;
        }
    }
    
    // Not a CDN path, return with leading slash for absolute path
    return '/' . $cleanPath;
}

/**
 * Get CSS URL
 * 
 * @param string $filename CSS filename (e.g., 'main.min.css')
 * @return string Full CDN URL
 */
function css_url($filename) {
    return cdn_url('/css/' . $filename);
}

/**
 * Get JS URL
 * 
 * @param string $filename JS filename (e.g., 'app.js')
 * @return string Full CDN URL
 */
function js_url($filename) {
    return cdn_url('/js/' . $filename);
}

/**
 * Get Font URL
 * 
 * @param string $filename Font filename (e.g., 'fa-solid-900.woff2')
 * @return string Full CDN URL
 */
function font_url($filename) {
    return cdn_url('/fonts/' . $filename, false);
}

/**
 * Get Image URL (for static images)
 * 
 * @param string $path Path to image
 * @return string Full CDN URL
 */
function static_image_url($path) {
    // Ensure path starts with /
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    // Add /images prefix if not present
    if (strpos($path, '/images') !== 0 && strpos($path, '/assets') !== 0) {
        $path = '/images' . $path;
    }
    
    return cdn_url($path, false);
}

/**
 * Generate preload link tags for critical assets
 * 
 * @return string HTML link tags
 */
function cdn_preload_tags() {
    $tags = [];
    
    // Preload main CSS
    $tags[] = '<link rel="preload" href="' . css_url('main.min.css') . '" as="style">';
    
    // Preload critical fonts
    $tags[] = '<link rel="preload" href="' . font_url('fa-solid-900.woff2') . '" as="font" type="font/woff2" crossorigin>';
    $tags[] = '<link rel="preload" href="' . font_url('fa-regular-400.woff2') . '" as="font" type="font/woff2" crossorigin>';
    
    return implode("\n    ", $tags);
}

/**
 * Output CSS link tag with CDN URL
 * 
 * @param string $filename CSS filename
 * @param array $attributes Additional attributes
 */
function cdn_css($filename, $attributes = []) {
    $url = css_url($filename);
    $attrs = '';
    foreach ($attributes as $key => $value) {
        $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    echo '<link rel="stylesheet" href="' . htmlspecialchars($url) . '"' . $attrs . '>' . "\n";
}

/**
 * Output JS script tag with CDN URL
 * 
 * @param string $filename JS filename
 * @param array $attributes Additional attributes (defer, async, etc.)
 */
function cdn_js($filename, $attributes = []) {
    $url = js_url($filename);
    $attrs = '';
    foreach ($attributes as $key => $value) {
        if ($value === true) {
            $attrs .= ' ' . htmlspecialchars($key);
        } else {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
    }
    echo '<script src="' . htmlspecialchars($url) . '"' . $attrs . '></script>' . "\n";
}
?>
