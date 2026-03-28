<?php
/**
 * Simple On-the-Fly Minifier
 * Usage: minify.php?file=assets/js/app.js&type=js
 */

require_once 'includes/CacheHelper.php';

$file = $_GET['file'] ?? '';
$type = $_GET['type'] ?? pathinfo($file, PATHINFO_EXTENSION);

// Security Check
$allowed_extensions = ['js', 'css'];
if (!in_array($type, $allowed_extensions)) {
    header("HTTP/1.0 403 Forbidden");
    exit('Forbidden type');
}

// Prevent directory traversal
$file = str_replace('..', '', $file);
$filepath = __DIR__ . '/' . $file;

if (!file_exists($filepath)) {
    header("HTTP/1.0 404 Not Found");
    exit('File not found');
}

// Cache Logic
$cache = new CacheHelper();
$cacheKey = 'minified_' . md5($file . filemtime($filepath));
$cachedContent = $cache->get($cacheKey);

// Headers
if ($type === 'css') {
    header("Content-Type: text/css");
} else {
    header("Content-Type: application/javascript");
}
header("Cache-Control: max-age=31536000, public");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

if ($cachedContent) {
    echo $cachedContent;
    exit;
}

// Minify Logic
$content = file_get_contents($filepath);

if ($type === 'js') {
    // Remove comments
    $content = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '', $content);
    // Remove whitespace
    $content = preg_replace('/\s+/', ' ', $content);
    $content = str_replace(['; ', ' {', '{ ', '} ', ' }', ', ', ' =', '= '], [';', '{', '{', '}', '}', ',', '=', '='], $content);
} elseif ($type === 'css') {
    // Remove comments
    $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
    // Remove whitespace
    $content = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $content);
}

// Save to Cache (Always save minified version)
$cache->set($cacheKey, $content, 31536000); // 1 Year

echo $content;
?>
