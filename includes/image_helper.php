<?php
/**
 * Kalkan Social - Image Helper
 * Handles resizing, EXIF orientation fix, and WebP conversion for performance optimization.
 */

function optimizeImage($sourcePath, $targetPath, $maxWidth = 1080, $maxHeight = 1080, $quality = 70) {
    // Check if file exists
    if (!file_exists($sourcePath)) return false;

    // Get image info
    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $width = $info[0];
    $height = $info[1];
    $type = $info[2];

    // Create image from source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            imagealphablending($source, true);
            imagesavealpha($source, true);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$source) return false;

    // ========================================
    // FIX EXIF ORIENTATION (for mobile uploads)
    // ========================================
    if ($type == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($sourcePath);
        if ($exif && isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $source = imagerotate($source, 180, 0);
                    break;
                case 6:
                    $source = imagerotate($source, -90, 0);
                    // Swap dimensions after rotation
                    $temp = $width;
                    $width = $height;
                    $height = $temp;
                    break;
                case 8:
                    $source = imagerotate($source, 90, 0);
                    // Swap dimensions after rotation
                    $temp = $width;
                    $width = $height;
                    $height = $temp;
                    break;
            }
        }
    }

    // Calculate new dimensions
    $ratio = $width / $height;
    if ($width > $maxWidth || $height > $maxHeight) {
        if ($width / $maxWidth > $height / $maxHeight) {
            $newWidth = (int)$maxWidth;
            $newHeight = (int)round($maxWidth / $ratio);
        } else {
            $newHeight = (int)$maxHeight;
            $newWidth = (int)round($maxHeight * $ratio);
        }
    } else {
        $newWidth = (int)$width;
        $newHeight = (int)$height;
    }

    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Maintain transparency for PNG/WebP
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Change target path extension to .webp if not already
    $targetInfo = pathinfo($targetPath);
    if ($targetInfo['extension'] !== 'webp') {
        $targetPath = $targetInfo['dirname'] . DIRECTORY_SEPARATOR . $targetInfo['filename'] . '.webp';
    }

    // Save as WebP
    $success = imagewebp($newImage, $targetPath, $quality);

    // Free memory
    imagedestroy($source);
    imagedestroy($newImage);

    return $success ? $targetPath : false;
}

/**
 * Index sayfası için thumbnail URL - küçük önizleme, detay sayfasında HD
 * uploads/xxx.webp -> uploads/xxx_thumb.webp (varsa)
 */
function getIndexThumbUrl($url) {
    if (empty($url)) return $url;
    $path = ltrim(preg_replace('#^https?://[^/]+#', '', $url), '/');
    $thumb_path = preg_replace('/\.(webp|jpg|jpeg|png|gif)$/i', '_thumb.webp', $path);
    if ($thumb_path === $path) return $url;
    $base = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') : realpath(__DIR__ . '/..');
    $full_thumb = $base . '/' . $thumb_path;
    if (file_exists($full_thumb)) return '/' . ltrim($thumb_path, '/');
    // Mevcut thumb yoksa orijinali kullan (yeni yüklemeler thumb oluşturacak)
    return $url;
}
?>
