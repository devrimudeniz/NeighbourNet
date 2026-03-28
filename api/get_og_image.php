<?php
// api/get_og_image.php
header('Content-Type: application/json');
require_once '../includes/db.php'; // For consistency if needed, though strictly not needed for this

$url = $_GET['url'] ?? '';

if (!$url) {
    echo json_encode(['status' => 'error', 'message' => 'No URL provided']);
    exit;
}

// Simple file cache
$cache_dir = __DIR__ . '/../cache/og_images';
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

$cache_key = md5($url);
$cache_file = $cache_dir . '/' . $cache_key . '.json';

// Return cached if valid (cache for 1 week as OG images rarely change)
if (file_exists($cache_file) && (time() - filemtime($cache_file) < 604800)) {
    echo file_get_contents($cache_file);
    exit;
}

// Fetch content
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects (Important for Google News)
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10s timeout
curl_setopt($ch, CURLOPT_USERAGENT, 'KalkanSocialbot/1.0 (+https://kalkansocial.com)');
// Ignore SSL for speed/compatibility
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$html = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch); // Get info before closing or reusing

if (!$html || $error) {
    curl_close($ch);
    $response = ['status' => 'error', 'message' => 'Failed to fetch URL', 'debug' => $error];
    echo json_encode($response);
    exit;
}

// Google News Redirect Handling
if (strpos($url, 'news.google.com') !== false || (isset($info['url']) && strpos($info['url'], 'news.google.com') !== false)) {
    // Check if we are on a redirect page
    if (preg_match('/<a[^>]+href="([^"]+)"[^>]*>opening it/i', $html, $match) || 
        preg_match('/<a[^>]+jsname="[^"]+"[^>]+href="([^"]+)"/i', $html, $match) ||
        preg_match('/class="ip" href="([^"]+)"/i', $html, $match)) {
        
        $real_url = $match[1];
        if (strpos($real_url, '/') === 0) {
            $real_url = 'https://news.google.com' . $real_url;
        }
        
        // Fetch the REAL url
        curl_setopt($ch, CURLOPT_URL, $real_url);
        $html = curl_exec($ch);
        $url = $real_url; 
    }
}
curl_close($ch); // Close finally

// Parse OG Image
$image = null;

// Parse OG Image
$image = null;

// Try og:image
if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $matches)) {
    $image = $matches[1];
} 
// Try twitter:image
elseif (preg_match('/<meta\s+name="twitter:image"\s+content="([^"]+)"/i', $html, $matches)) {
    $image = $matches[1];
}
// Try generic link rel image_src
elseif (preg_match('/<link\s+rel="image_src"\s+href="([^"]+)"/i', $html, $matches)) {
    $image = $matches[1];
}

if ($image) {
    // Fix relative URLs
    if (strpos($image, 'http') !== 0) {
        $parsed_url = parse_url($url);
        $base = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        $image = $base . '/' . ltrim($image, '/');
    }
    
    // Ensure valid URL
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        $response = ['status' => 'success', 'image' => $image];
    } else {
         $response = ['status' => 'error', 'message' => 'Invalid image URL extracted'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'No image found'];
}

// Save cache
file_put_contents($cache_file, json_encode($response));

echo json_encode($response);
?>
