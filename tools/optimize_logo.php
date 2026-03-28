<?php
// tools/optimize_logo.php

$source = __DIR__ . '/../logo.jpg';
$dest = __DIR__ . '/../logo_header.webp';

if (!file_exists($source)) {
    die("Source file logo.jpg not found.\n");
}

$info = getimagesize($source);
$mime = $info['mime'];

switch ($mime) {
    case 'image/jpeg':
        $img = imagecreatefromjpeg($source);
        break;
    case 'image/png':
        $img = imagecreatefrompng($source);
        break;
    case 'image/webp':
        $img = imagecreatefromwebp($source);
        break;
    default:
        die("Unsupported image type: $mime\n");
}

if (!$img) {
    die("Failed to load image.\n");
}

// Target Height: 120px (for Retina 60px display)
$newHeight = 120;
$ratio = $info[0] / $info[1];
$newWidth = (int)($newHeight * $ratio);

$newImg = imagecreatetruecolor($newWidth, $newHeight);
imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $info[0], $info[1]);

if (imagewebp($newImg, $dest, 85)) {
    echo "Success! Created logo_header.webp ($newWidth x $newHeight)\n";
    echo "Old size: " . filesize($source) . " bytes\n";
    echo "New size: " . filesize($dest) . " bytes\n";
} else {
    echo "Failed to save WebP image.\n";
}

imagedestroy($img);
imagedestroy($newImg);
?>
