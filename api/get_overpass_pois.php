<?php
/**
 * Overpass API - Fetch Historic & Tourism POIs
 * Queries OpenStreetMap for ruins, archaeological sites, and attractions
 * in the Antalya/Muğla (Lycian Way) region
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Cache file to avoid hammering Overpass API
$cache_file = __DIR__ . '/cache/overpass_pois.json';
$cache_duration = 86400; // 24 hours

// Force refresh if ?refresh=1 is passed
$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

// Delete old cache if force refresh
if ($force_refresh && file_exists($cache_file)) {
    unlink($cache_file);
}

// Check cache first (skip if force refresh)
if (!$force_refresh && file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_duration)) {
    echo file_get_contents($cache_file);
    exit;
}

// Bounding box for Antalya/Muğla region (Lycian Way area)
// Format: south,west,north,east
$bbox = "35.8,28.5,37.2,31.0";

// Overpass QL Query (beaches only)
$query = <<<OVERPASS
[out:json][timeout:60];
(
  node["natural"="beach"]($bbox);
  way["natural"="beach"]($bbox);
);
out center;
OVERPASS;

// Query Overpass API
$url = 'https://overpass-api.de/api/interpreter';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'data=' . urlencode($query));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_USERAGENT, 'KalkanSocial/1.0');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    echo json_encode(['status' => 'error', 'message' => 'Overpass API request failed']);
    exit;
}

$data = json_decode($response, true);
if (!$data || !isset($data['elements'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid response from Overpass API']);
    exit;
}

// Process elements into POIs
$pois = [];
$seen_names = [];

foreach ($data['elements'] as $element) {
    // Get coordinates (for ways, use center)
    $lat = isset($element['lat']) ? $element['lat'] : (isset($element['center']['lat']) ? $element['center']['lat'] : null);
    $lon = isset($element['lon']) ? $element['lon'] : (isset($element['center']['lon']) ? $element['center']['lon'] : null);
    
    if (!$lat || !$lon) continue;
    
    $tags = isset($element['tags']) ? $element['tags'] : [];
    $name = isset($tags['name']) ? $tags['name'] : (isset($tags['name:en']) ? $tags['name:en'] : null);
    
    // Skip unnamed POIs
    if (!$name) continue;
    
    // Skip short/generic names (just "plaj", "beach", etc.)
    $name_lower = strtolower(trim($name));
    if (in_array($name_lower, ['plaj', 'beach', 'sahil', 'koy'])) continue;
    if (strlen($name) < 5) continue;
    
    // Skip unwanted POIs (blacklist)
    $blacklist = [
        'Beach Club', 'Secret', 'Cliff beach access', 'S.A.R.C.E.D.', 'plaj Vika', 'STP',
        'rups', 'Пиратская', 'Get Enjoy', 'Champion Holiday', 'BLMBEACh', 'Ucretli plaj',
        'Hotel beach', 'Holiday Village'
    ];
    $skip = false;
    foreach ($blacklist as $term) {
        if (stripos($name, $term) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;
    
    // Skip duplicates (same name)
    $name_key = strtolower(trim($name));
    if (isset($seen_names[$name_key])) continue;
    $seen_names[$name_key] = true;
    
    $pois[] = [
        'id' => 'osm_' . $element['id'],
        'name' => $name,
        'type' => 'BEACH',
        'lat' => $lat,
        'lng' => $lon,
        'desc' => isset($tags['description']) ? $tags['description'] : ''
    ];
}

// Add curated beaches manually (coordinates verified - Kaputaş: between Kalkan & Kaş, D400)
$curated_beaches = [
    ['id' => 'kalkan_beach', 'name' => 'Kalkan Beach', 'type' => 'BEACH', 'lat' => 36.2630, 'lng' => 29.4160, 'desc' => 'Main beach in Kalkan town'],
    ['id' => 'kaputas_beach', 'name' => 'Kaputaş Beach', 'type' => 'BEACH', 'lat' => 36.2292, 'lng' => 29.4492, 'desc' => 'Famous turquoise beach between Kalkan and Kaş, 187 steps down'],
    ['id' => 'patara_beach', 'name' => 'Patara Beach', 'type' => 'BEACH', 'lat' => 36.2650, 'lng' => 29.2850, 'desc' => '18km long sandy beach, turtle nesting site']
];

foreach ($curated_beaches as $beach) {
    $name_key = strtolower(trim($beach['name']));
    if (!isset($seen_names[$name_key])) {
        $pois[] = $beach;
        $seen_names[$name_key] = true;
    }
}

$result = [
    'status' => 'success',
    'count' => count($pois),
    'pois' => $pois
];

// Cache the result
if (!is_dir(__DIR__ . '/cache')) {
    mkdir(__DIR__ . '/cache', 0755, true);
}
file_put_contents($cache_file, json_encode($result));

echo json_encode($result);
?>
