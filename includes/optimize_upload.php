<?php
/**
 * Image Optimization Helper
 * Uses GD Library to optimize, resize, and convert images to WebP.
 */

function gorselOptimizeEt($file, $target_dir, $quality = 90) {
    // 1. Güvenlik ve Dosya Kontrolü
    $image_info = @getimagesize($file['tmp_name']);
    
    if ($image_info === false) {
         return ['error' => 'Dosya bir resim dosyası değil veya bozuk.'];
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
    $file_type = $image_info['mime']; // Uses underlying reliable detection
    
    if (!in_array($file_type, $allowed_types)) {
        return ['error' => 'Geçersiz dosya türü (' . $file_type . '). Sadece JPG, PNG, WebP ve AVIF kabul edilir.'];
    }

    // Dosya uzantısı kontrolü (ek güvenlik)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'avif'])) {
        return ['error' => 'Geçersiz dosya uzantısı.'];
    }

    // 2. Dosya Adı Oluşturma (SEO Dostu ve Benzersiz)
    $filename_raw = pathinfo($file['name'], PATHINFO_FILENAME);
    
    // Türkçe karakterleri ve boşlukları temizle
    $tr_chars = ['ş', 'Ş', 'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç', ' '];
    $en_chars = ['s', 's', 'i', 'i', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c', '-'];
    $clean_name = str_replace($tr_chars, $en_chars, $filename_raw);
    $clean_name = preg_replace('/[^a-z0-9-]/', '', strtolower($clean_name));
    $clean_name = preg_replace('/-+/', '-', $clean_name); // Tekrarlayan tireleri sil
    $clean_name = trim($clean_name, '-');

    // Benzersiz isim oluştur
    $new_filename = $clean_name . '-' . uniqid('', true) . '.webp';
    $target_dir = rtrim($target_dir, '/'); // Ensure consistent path
    $target_path = $target_dir . '/' . $new_filename;

    // Hedef klasör yoksa oluştur
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // 3. Görseli İşleme (GD Library)
    list($width, $height) = getimagesize($file['tmp_name']);
    
    // Kaynak görseli oluştur
    switch ($file_type) {
        case 'image/jpeg':
            $source = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $source = @imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $source = @imagecreatefromwebp($file['tmp_name']);
            break;
        case 'image/avif':
            if (function_exists('imagecreatefromavif')) {
                $source = @imagecreatefromavif($file['tmp_name']);
            } else {
                return ['error' => 'Sunucu AVIF formatını desteklemiyor.'];
            }
            break;
        default:
            return ['error' => 'Görsel işlenemedi.'];
    }

    if (!$source) {
        return ['error' => 'Görsel kaynağı oluşturulamadı.'];
    }


    // 4. Boyutlandırma (Max 2560px Genişlik - Kalite artışı için yükseltildi)
    $max_width = 2560;
    if ($width > $max_width) {
        $ratio = $height / $width;
        $new_width = $max_width;
        $new_height = intval($new_width * $ratio);
    } else {
        $new_width = $width;
        $new_height = $height;
    }

    $new_image = imagecreatetruecolor($new_width, $new_height);

    // 5. Şeffaflık Koruma (PNG ve WebP için)
    if ($file_type == 'image/png' || $file_type == 'image/webp') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }

    // Resmi yeniden boyutlandırarak kopyala
    // imagecopyresampled daha kaliteli küçültme yapar
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // 6. WebP Olarak Kaydet (Default Kalite: 96)
    // Eğer quality parametresi varsayılan (90) geldiyse, bunu 96 yapalım.
    if ($quality == 90) $quality = 96;
    
    $save_result = imagewebp($new_image, $target_path, $quality);

    // Belleği temizle
    imagedestroy($source);
    imagedestroy($new_image);

    if ($save_result) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $target_path];
    } else {
        return ['error' => 'Dosya kaydedilemedi.'];
    }
}

/**
 * Creates a cropped thumbnail from an image
 */
function createThumbnail($source_path, $target_path, $target_width, $target_height) {
    // Debug Log
    file_put_contents(__DIR__ . '/../debug_thumb.log', "Starting creation for: $source_path -> $target_path\n", FILE_APPEND);
    
    if (!file_exists($source_path)) {
        file_put_contents(__DIR__ . '/../debug_thumb.log', "Error: Source not found: $source_path\n", FILE_APPEND);
        return false;
    }
    
    $image_info = @getimagesize($source_path);
    if ($image_info === false) {
        file_put_contents(__DIR__ . '/../debug_thumb.log', "Error: GetImageSize failed for $source_path\n", FILE_APPEND);
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $mime = $image_info['mime'];

    $thumb = imagecreatetruecolor($target_width, $target_height);
    
    // Handle transparency
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
    imagefilledrectangle($thumb, 0, 0, $target_width, $target_height, $transparent);

    // Load source
    $source = null;
    switch ($mime) {
        case 'image/jpeg': $source = @imagecreatefromjpeg($source_path); break;
        case 'image/png': $source = @imagecreatefrompng($source_path); break;
        case 'image/webp': $source = @imagecreatefromwebp($source_path); break;
        case 'image/avif': 
            if(function_exists('imagecreatefromavif')) $source = @imagecreatefromavif($source_path); 
            break;
    }

    if (!$source) {
        file_put_contents(__DIR__ . '/../debug_thumb.log', "Error: Failed to create image resource from $mime\n", FILE_APPEND);
        return false;
    }

    // Smart Crop (Center)
    $ratio_thumb = $target_width / $target_height;
    $ratio_orig = $width / $height;

    $src_x = 0;
    $src_y = 0;
    $src_w = $width;
    $src_h = $height;

    if ($ratio_orig > $ratio_thumb) {
        // Source is wider than thumb (crop sides)
        $src_w = intval($height * $ratio_thumb);
        $src_x = intval(($width - $src_w) / 2);
    } else {
        // Source is taller than thumb (crop top/bottom)
        $src_h = intval($width / $ratio_thumb);
        $src_y = intval(($height - $src_h) / 2);
    }
    
    // Copy and Resize
    imagecopyresampled($thumb, $source, 0, 0, $src_x, $src_y, $target_width, $target_height, $src_w, $src_h);
    
    // Save as WebP
    $save_result = imagewebp($thumb, $target_path, 60);
    
    imagedestroy($thumb);
    imagedestroy($source);
    
    if (!$save_result) {
        file_put_contents(__DIR__ . '/../debug_thumb.log', "Error: Failed to save to $target_path\n", FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/../debug_thumb.log', "Success: Created $target_path\n", FILE_APPEND);
    }
    
    return $save_result;
}
?>
