<?php
header('Content-Type: text/plain');
$base_dir = dirname(__DIR__);

function listFiles($dir) {
    echo "--- Listing $dir ---\n";
    if (!is_dir($dir)) {
        echo "[ERROR] Not a directory\n";
        return;
    }
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            listFiles($path);
        } else {
             $rel = str_replace($base_dir . '/', '', $path);
             echo "Rel: $rel | Size: " . filesize($path) . "\n";
        }
    }
}

listFiles($base_dir . '/GPSTracks');
listFiles($base_dir . '/GPSWaypoints');
?>
