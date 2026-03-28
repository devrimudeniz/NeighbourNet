<?php
header('Content-Type: application/json');
require_once '../includes/env.php';

// CollectAPI Configuration
$api_key = env_value('COLLECTAPI_KEY', '');
$api_url = 'https://api.collectapi.com/health/dutyPharmacy?ilce=Kas&il=Antalya';

// Cache configuration
$cache_file = __DIR__ . '/../cache/duty_pharmacy.json';
$cache_duration = 21600; // 6 hours in seconds

// Ensure cache directory exists
$cache_dir = dirname($cache_file);
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0777, true);
}

// Check if cache exists and is valid
$use_cache = false;
if (file_exists($cache_file)) {
    $cache_age = time() - filemtime($cache_file);
    if ($cache_age < $cache_duration) {
        $use_cache = true;
    }
}

if ($use_cache) {
    // Return cached data
    $cached_data = file_get_contents($cache_file);
    echo $cached_data;
    exit;
}

if ($api_key === '') {
    echo json_encode([
        'success' => false,
        'message' => 'CollectAPI key missing. Set COLLECTAPI_KEY in your .env file.',
        'result' => []
    ]);
    exit;
}

// Fetch fresh data from API using cURL
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "authorization: " . $api_key,
        "content-type: application/json"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    // cURL error, return cached data if available
    if (file_exists($cache_file)) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        $cached_data['warning'] = 'Using cached data (API error)';
        echo json_encode($cached_data);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'cURL Error: ' . $err,
            'result' => []
        ]);
    }
    exit;
}

// Parse response
$data = json_decode($response, true);

if ($data && isset($data['success']) && $data['success']) {
    // Save to cache
    file_put_contents($cache_file, $response);
    echo $response;
} else {
    // Invalid API response, return cached data if available
    if (file_exists($cache_file)) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        $cached_data['warning'] = 'Using cached data (Invalid API response)';
        echo json_encode($cached_data);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid API response',
            'result' => []
        ]);
    }
}
?>
