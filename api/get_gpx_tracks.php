<?php
// 1. CORS Hatalarını önlemek için
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

/**
 * Lycian Way - Consolidated GPX Listing API
 * Scans the 'gpx' folder for tracks and waypoints.
 */

$base_dir = dirname(__DIR__); // Root folder
$gpx_dir = $base_dir . '/gpx';

$data = [
    'tracks' => [],
    'waypoints' => [
        'water' => [],
        'camps' => [],
        'places' => [],
        'misc' => []
    ]
];

if (is_dir($gpx_dir)) {
    $files = scandir($gpx_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'gpx') {
            $rel_path = 'gpx/' . $file;
            $lowerFile = strtolower($file);
            
            // Categorization logic based on new filenames
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

echo json_encode([
    'status' => 'success',
    'count' => count($data['tracks']),
    'data' => $data
]);
?>