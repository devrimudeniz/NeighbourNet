<?php
/**
 * Kalkan Social - Hyper-Local Weather API
 * Fetches data from OpenWeather and provides seasonal sea temperature estimates.
 */

header('Content-Type: application/json');
require_once '../includes/env.php';

// --- CONFIGURATION ---
$api_key = env_value('OPENWEATHER_API_KEY', '');
$lat = '36.2651';
$lon = '29.4148';
$city = 'Kalkan';
$cache_file = '../cache/weather_cache.json';
$cache_time = 1800; // 30 minutes

// Ensure cache directory exists
if (!is_dir('../cache')) {
    mkdir('../cache', 0755, true);
}

// Check cache
if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    echo file_get_contents($cache_file);
    exit();
}

/**
 * Seasonal Sea Temperature Model for Kalkan (Estimate)
 * Based on historical monthly averages for the region.
 */
function getEstimatedSeaTemp() {
    $month = (int)date('n');
    $day = (int)date('j');
    
    // Monthly averages (Celsius)
    $averages = [
        1 => 17.5, 2 => 16.5, 3 => 17.0, 4 => 18.5,
        5 => 21.0, 6 => 24.5, 7 => 27.5, 8 => 28.5,
        9 => 27.0, 10 => 24.5, 11 => 21.5, 12 => 19.0
    ];
    
    $current_avg = $averages[$month];
    $next_month = ($month % 12) + 1;
    $next_avg = $averages[$next_month];
    
    // Linear interpolation between months
    $days_in_month = (int)date('t');
    $progress = $day / $days_in_month;
    
    return round($current_avg + ($next_avg - $current_avg) * $progress, 1);
}

try {
    if ($api_key === '') {
        throw new Exception("OpenWeather API key missing");
    }

    // 1. Fetch Current Weather (includes Wind)
    $weather_url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$api_key}&units=metric";
    $weather_response = file_get_contents($weather_url);
    if (!$weather_response) throw new Exception("Failed to fetch current weather");
    $weather_data = json_decode($weather_response, true);

    // 2. Fetch UV Index (Requires separate call in some API versions or One Call)
    // Using current weather for basic data, but UV often needs another endpoint or One Call.
    // Let's try to get UV if possible.
    $uv_url = "https://api.openweathermap.org/data/2.5/air_pollution?lat={$lat}&lon={$lon}&appid={$api_key}";
    // Note: Air pollution response includes 'uv_index' in some plans, or we use One Call.
    // For now, let's assume we want a reliable UV source. 
    // If One Call 3.0 is active for this key:
    $onecall_url = "https://api.openweathermap.org/data/3.0/onecall?lat={$lat}&lon={$lon}&appid={$api_key}&units=metric&exclude=minutely,hourly,daily";
    
    // We'll use a combined approach or stick to what we know works.
    // Let's fallback gracefully.
    $uv_index = 0;
    $onecall_response = @file_get_contents($onecall_url);
    if ($onecall_response) {
        $onecall_data = json_decode($onecall_response, true);
        $uv_index = $onecall_data['current']['uvi'] ?? 0;
    }

    $result = [
        'status' => 'success',
        'city' => $city,
        'temp' => round($weather_data['main']['temp']),
        'description' => $weather_data['weather'][0]['description'],
        'icon' => $weather_data['weather'][0]['icon'],
        'wind_speed' => round($weather_data['wind']['speed'] * 3.6, 1), // km/h
        'wind_deg' => $weather_data['wind']['deg'],
        'uv_index' => $uv_index,
        'sea_temp' => getEstimatedSeaTemp(),
        'updated_at' => time()
    ];

    $json_result = json_encode($result);
    file_put_contents($cache_file, $json_result);
    echo $json_result;

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'sea_temp' => getEstimatedSeaTemp() // Return estimate anyway
    ]);
}
