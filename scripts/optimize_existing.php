<?php
/**
 * optimize_existing.php
 * Scans uploads/ directory and converts existing JPG/PNG/GIF to WebP
 * Resizes large images to 1080px max.
 */

// Basic check to prevent accidental execution from web
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("Use CLI or ?run=1 to execute this script.");
}

set_time_limit(0); // No time limit
require_once __DIR__ . '/../includes/image_helper.php';

$base_dir = __DIR__ . '/../uploads/';
$stats = [
    'converted' => 0,
    'failed' => 0,
    'already_webp' => 0,
    'skipped' => 0
];

function scanAndOptimize($dir) {
    global $stats;
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . $file;
        
        if (is_dir($path)) {
            scanAndOptimize($path . '/');
        } else {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            if ($ext === 'webp') {
                $stats['already_webp']++;
                continue;
            }
            
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $target = $dir . pathinfo($path, PATHINFO_FILENAME) . '.webp';
                
                // If webp already exists, we skip to avoid re-converting unless you want to replace
                if (file_exists($target)) {
                    $stats['skipped']++;
                    continue;
                }

                echo "Optimizing: $file ... ";
                
                $result = optimizeImage($path, $target, 1080, 1080);
                
                if ($result) {
                    echo "Done! (Saved as WebP)\n";
                    $stats['converted']++;
                    // Optionally delete original to save space
                    // unlink($path); 
                } else {
                    echo "FAILED.\n";
                    $stats['failed']++;
                }
            }
        }
    }
}

echo "Starting Image Optimization Scan...\n";
scanAndOptimize($base_dir);

echo "\n--- STATS ---\n";
echo "Converted to WebP: " . $stats['converted'] . "\n";
echo "Already WebP:      " . $stats['already_webp'] . "\n";
echo "Skipped (Exists):  " . $stats['skipped'] . "\n";
echo "Failed:            " . $stats['failed'] . "\n";
echo "--------------\n";
?>
