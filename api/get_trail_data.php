<?php
/**
 * Trail Mate - Consolidated Data API
 * Integrates GPX tracks, Curated POIs, Sections, and Weather Points
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$base_dir = dirname(__DIR__);
$gpx_dir = $base_dir . '/gpx';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'tr';

$data = [
    'tracks' => [],
    'waypoints' => [
        'water' => [],
        'camps' => [],
        'places' => [],
        'misc' => []
    ],
    'pois' => [],
    'sections' => [],
    'weather_points' => []
];

// 1. Scan GPX Folder (merged logic)
if (is_dir($gpx_dir)) {
    $files = scandir($gpx_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'gpx') {
            $rel_path = 'gpx/' . $file;
            $lowerFile = strtolower($file);
            
            if (strpos($lowerFile, 'water') !== false) {
                $data['waypoints']['water'][] = $rel_path;
            } elseif (strpos($lowerFile, 'camp') !== false) {
                $data['waypoints']['camps'][] = $rel_path;
            } elseif (strpos($lowerFile, 'places') !== false) {
                $data['waypoints']['places'][] = $rel_path;
            } elseif (strpos($lowerFile, 'lycianway-') !== false) {
                $data['tracks'][] = $rel_path;
            } else {
                $data['waypoints']['misc'][] = $rel_path;
            }
        }
    }
}

// 2. Curated POIs (English only, no accommodations)
$data['pois'] = [
    // Ancient Sites
    ['id' => 'phaselis', 'name' => 'Phaselis', 'type' => 'ANCIENT_SITE', 'lat' => 36.5244, 'lng' => 30.5528, 'desc' => 'Ancient Greek and Roman city with three harbors'],
    ['id' => 'olympos', 'name' => 'Olympos', 'type' => 'ANCIENT_SITE', 'lat' => 36.3972, 'lng' => 30.4731, 'desc' => 'Ancient Lycian city near the beach'],
    ['id' => 'myra', 'name' => 'Myra', 'type' => 'ANCIENT_SITE', 'lat' => 36.2597, 'lng' => 29.9850, 'desc' => 'Ancient Lycian city with rock-cut tombs'],
    ['id' => 'patara', 'name' => 'Patara', 'type' => 'ANCIENT_SITE', 'lat' => 36.2656, 'lng' => 29.3156, 'desc' => 'Ancient Lycian capital with famous beach'],
    ['id' => 'xanthos', 'name' => 'Xanthos', 'type' => 'ANCIENT_SITE', 'lat' => 36.3569, 'lng' => 29.3194, 'desc' => 'Ancient Lycian capital, UNESCO World Heritage'],
    ['id' => 'letoon', 'name' => 'Letoon', 'type' => 'ANCIENT_SITE', 'lat' => 36.3306, 'lng' => 29.2900, 'desc' => 'Sacred site of ancient Lycia'],
    ['id' => 'tlos', 'name' => 'Tlos', 'type' => 'ANCIENT_SITE', 'lat' => 36.5519, 'lng' => 29.4208, 'desc' => 'Ancient Lycian city with acropolis'],
    ['id' => 'pinara', 'name' => 'Pinara', 'type' => 'ANCIENT_SITE', 'lat' => 36.4900, 'lng' => 29.2556, 'desc' => 'Ancient Lycian city with rock tombs'],
    
    // Camping Sites
    ['id' => 'camp_olympos', 'name' => 'Olympos Camping', 'type' => 'CAMPING', 'lat' => 36.3950, 'lng' => 30.4700, 'desc' => 'Camping area near Olympos beach'],
    ['id' => 'camp_cirali', 'name' => 'Cirali Camping', 'type' => 'CAMPING', 'lat' => 36.4200, 'lng' => 30.4800, 'desc' => 'Popular camping area near Cirali beach'],
    ['id' => 'camp_patara', 'name' => 'Patara Camping', 'type' => 'CAMPING', 'lat' => 36.2600, 'lng' => 29.3100, 'desc' => 'Camping near Patara beach'],
    ['id' => 'camp_kabak', 'name' => 'Kabak Bay Camping', 'type' => 'CAMPING', 'lat' => 36.4500, 'lng' => 29.1500, 'desc' => 'Secluded camping area in Kabak Bay'],
    ['id' => 'camp_faralya', 'name' => 'Faralya Camping', 'type' => 'CAMPING', 'lat' => 36.4800, 'lng' => 29.1800, 'desc' => 'Camping in Faralya village'],
    
    // Water Sources
    ['id' => 'water_olympos', 'name' => 'Olympos Water', 'type' => 'WATER', 'lat' => 36.4000, 'lng' => 30.4750, 'desc' => 'Fresh water source'],
    ['id' => 'water_geyikbayiri', 'name' => 'Geyikbayiri Water', 'type' => 'WATER', 'lat' => 36.5500, 'lng' => 30.3500, 'desc' => 'Water source in Geyikbayiri village'],
    ['id' => 'water_beycik', 'name' => 'Beycik Water', 'type' => 'WATER', 'lat' => 36.5000, 'lng' => 30.2000, 'desc' => 'Water source near Beycik'],
    ['id' => 'water_faralya', 'name' => 'Faralya Water', 'type' => 'WATER', 'lat' => 36.4800, 'lng' => 29.1800, 'desc' => 'Water source in Faralya village'],
    
    // Viewpoints
    ['id' => 'viewpoint_tahtali', 'name' => 'Tahtali Mountain View', 'type' => 'VIEWPOINT', 'lat' => 36.5400, 'lng' => 30.4500, 'desc' => 'Scenic viewpoint of Tahtali Mountain'],
    ['id' => 'viewpoint_babadag', 'name' => 'Babadag Viewpoint', 'type' => 'VIEWPOINT', 'lat' => 36.5000, 'lng' => 29.2000, 'desc' => 'Panoramic view of Oludeniz'],
    ['id' => 'viewpoint_kas', 'name' => 'Kas Viewpoint', 'type' => 'VIEWPOINT', 'lat' => 36.2100, 'lng' => 29.6400, 'desc' => 'Sea view above Kas'],
    
    // Markets/Resupply
    ['id' => 'market_kalkan', 'name' => 'Kalkan Market', 'type' => 'RESUPPLY', 'lat' => 36.2640, 'lng' => 29.4140, 'desc' => 'Supermarket in Kalkan'],
    ['id' => 'market_kas', 'name' => 'Kas Supermarket', 'type' => 'RESUPPLY', 'lat' => 36.2017, 'lng' => 29.6361, 'desc' => 'Well-stocked supermarket in Kas'],
    ['id' => 'market_patara', 'name' => 'Patara Market', 'type' => 'RESUPPLY', 'lat' => 36.2650, 'lng' => 29.3150, 'desc' => 'Small market in Patara village']
];

// 3. Trail Sections (English only)
$data['sections'] = [
    ['id' => 1, 'name' => 'Fethiye - Kalkan', 'km' => 85.0, 'days' => 4, 'difficulty' => 'MEDIUM', 'start' => ['lat' => 36.6214, 'lng' => 29.1164], 'desc' => 'The starting section. Features coastal views and ancient ruins.'],
    ['id' => 2, 'name' => 'Kalkan - Kas', 'km' => 25.0, 'days' => 2, 'difficulty' => 'EASY', 'start' => ['lat' => 36.2644, 'lng' => 29.4131], 'desc' => 'A short and easy section. Passes through traditional villages.'],
    ['id' => 3, 'name' => 'Kas - Demre', 'km' => 75.0, 'days' => 3, 'difficulty' => 'HARD', 'start' => ['lat' => 36.1978, 'lng' => 29.6364], 'desc' => 'A challenging section. Stunning mountain views.']
];

// 4. Weather Points (Extracted from Android LycianWeatherPoints.kt)
$data['weather_points'] = [
    ['name' => 'Fethiye', 'lat' => 36.6210, 'lng' => 29.1164],
    ['name' => 'Kalkan', 'lat' => 36.2651, 'lng' => 29.4134],
    ['name' => 'Kaş', 'lat' => 36.2023, 'lng' => 29.6357],
    ['name' => 'Demre', 'lat' => 36.2444, 'lng' => 29.9850]
];

echo json_encode([
    'status' => 'success',
    'lang' => $lang,
    'data' => $data
]);
?>
