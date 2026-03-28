<?php
/**
 * API to Fetch URL Open Graph Data
 */
header('Content-Type: application/json');

if (!isset($_GET['url'])) {
    echo json_encode(['status' => 'error', 'message' => 'URL missing']);
    exit;
}

$url = trim($_GET['url']);

// Basic Validation
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid URL']);
    exit;
}

try {
    // Use cURL for better compatibility
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'KalkanSocialBot/1.0 (Mozilla/5.0 compatible)');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $html = curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if (!$html) {
        throw new Exception("Could not fetch URL");
    }
    
    // Handle Encoding
    if ($contentType && preg_match('/charset=([^;]+)/', $contentType, $matches)) {
        $charset = $matches[1];
        if (strcasecmp($charset, 'utf-8') !== 0) {
            $html = @mb_convert_encoding($html, 'HTML-ENTITIES', $charset);
        }
    }

    $data = [
        'title' => '',
        'description' => '',
        'image' => '',
        'url' => $url
    ];

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    
    // Get Meta Tags (OpenGraph)
    $metas = $doc->getElementsByTagName('meta');
    foreach ($metas as $meta) {
        $property = $meta->getAttribute('property');
        $name = $meta->getAttribute('name');
        $content = $meta->getAttribute('content');
        
        if ($property == 'og:title' || $name == 'twitter:title') $data['title'] = $content;
        if ($property == 'og:description' || $name == 'twitter:description' || $name == 'description') $data['description'] = $content;
        if ($property == 'og:image' || $name == 'twitter:image') $data['image'] = $content;
    }

    // Fallbacks
    if (empty($data['title'])) {
        $titles = $doc->getElementsByTagName('title');
        if ($titles->length > 0) $data['title'] = $titles->item(0)->nodeValue;
    }
    
    // Fallback Image (find first meaningful image)
    if (empty($data['image'])) {
        $images = $doc->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (strlen($src) > 10 && strpos($src, 'data:') === false && strpos($src, 'logo') === false && strpos($src, 'icon') === false) {
                 // Fix relative URLs
                 if (strpos($src, 'http') === false) {
                     $parsed = parse_url($url);
                     $base = $parsed['scheme'] . '://' . $parsed['host'];
                     if (strpos($src, '/') === 0) {
                         $src = $base . $src;
                     } else {
                         $src = $base . '/' . $src;
                     }
                 }
                 $data['image'] = $src;
                 break;
            }
        }
    }

    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
