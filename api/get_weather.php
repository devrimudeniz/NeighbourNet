<?php
header('Content-Type: application/json');
require_once '../includes/env.php';

/**
 * Kalkan Social - Live Weather & Sea Temperature API (Multi-Location)
 * Fetches data from OpenWeatherMap and Open-Meteo with caching.
 */

// Define Coordinates
$locations = [
    'kalkan' => ['lat' => '36.2651', 'lon' => '29.4128', 'name' => 'Kalkan'],
    'kas' => ['lat' => '36.2023', 'lon' => '29.6322', 'name' => 'Kaş'],
    'fethiye' => ['lat' => '36.6231', 'lon' => '29.1167', 'name' => 'Fethiye'],
    'dalaman' => ['lat' => '36.7644', 'lon' => '28.8021', 'name' => 'Dalaman']
];

$loc_key = isset($_GET['location']) && isset($locations[$_GET['location']]) ? $_GET['location'] : 'kalkan';
$loc_data = $locations[$loc_key];

$cache_file = "../cache/weather_{$loc_key}.json";
$cache_time = 1800; // 30 minutes in seconds

// Use cache if available and not expired
if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    $cache_data = json_decode(file_get_contents($cache_file), true);
    if ($cache_data) {
        $cache_data['source'] = 'cache';
        $cache_data['cache_age'] = time() - filemtime($cache_file);
        echo json_encode($cache_data);
        exit;
    }
}

$lat = $loc_data['lat'];
$lon = $loc_data['lon'];
$api_key = env_value('OPENWEATHER_API_KEY', '');

if ($api_key === '') {
    echo json_encode(['status' => 'error', 'message' => 'OpenWeather API key missing']);
    exit;
}

$weather_data = [];
$status = 'error';

try {
    // 1. Fetch Weather from OpenWeatherMap
    $owm_url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$api_key}&units=metric";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $owm_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $owm_response = curl_exec($ch);
    $owm_json = json_decode($owm_response, true);
    
    // 2. Fetch Sea Surface Temperature from Open-Meteo
    $marine_url = "https://marine-api.open-meteo.com/v1/marine?latitude={$lat}&longitude={$lon}&current=sea_surface_temperature";
    curl_setopt($ch, CURLOPT_URL, $marine_url);
    $marine_response = curl_exec($ch);
    $marine_json = json_decode($marine_response, true);
    
    curl_close($ch);

    if (isset($owm_json['main']['temp'])) {
        $weather_data = [
            'location' => $loc_data['name'],
            'location_key' => $loc_key,
            'temp' => round($owm_json['main']['temp']),
            'feels_like' => round($owm_json['main']['feels_like']),
            'humidity' => $owm_json['main']['humidity'],
            'wind_speed' => round($owm_json['wind']['speed'] * 3.6), // Convert m/s to km/h
            'icon' => $owm_json['weather'][0]['icon'],
            'description' => $owm_json['weather'][0]['description'],
            'main' => $owm_json['weather'][0]['main'],
            'sea_temp' => isset($marine_json['current']['sea_surface_temperature']) ? round($marine_json['current']['sea_surface_temperature']) : null,
            'updated_at' => time()
        ];
        $status = 'success';
        
        // Save to cache
        file_put_contents($cache_file, json_encode(['status' => 'success', 'data' => $weather_data]));
        
        echo json_encode(['status' => 'success', 'data' => $weather_data, 'source' => 'api']);
    } else {
        throw new Exception("Invalid API response from OpenWeatherMap");
    }

} catch (Exception $e) {
    // Try to return old cache on error even if expired
    if (file_exists($cache_file)) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        if ($cache_data) {
            $cache_data['offline_fallback'] = true;
            echo json_encode($cache_data);
            exit;
        }
    }
    
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
