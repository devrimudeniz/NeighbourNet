<?php
$url = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
$content = file_get_contents($url);
if ($content) {
    echo "SHA384: " . base64_encode(hash('sha384', $content, true));
} else {
    echo "Failed to download";
}
?>
