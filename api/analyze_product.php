<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/env.php';

$GEMINI_API_KEY = env_value('GEMINI_API_KEY', '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$textInput = $_POST['text'] ?? '';
$hasImage = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;

if (empty($textInput) && !$hasImage) {
    echo json_encode(['error' => 'Please provide text or an image.']);
    exit;
}

if ($GEMINI_API_KEY === '') {
    echo json_encode(['error' => 'Gemini API key missing']);
    exit;
}

// Build Prompt
$promptText = "You are a culinary expert and local shopping guide for British expats living in Turkey.
Your goal is to help them find Turkish equivalents of UK grocery products (like Double Cream, Self-raising flour, Cheddar, etc.) OR explain what a Turkish product is.

Input: " . ($textInput ? "User asks: '$textInput'" : "User uploaded a product photo.") . "

Instructions:
1. If the user asks for a UK product (e.g. 'Double Cream'), explain that it might not exist exactly, but recommend the BEST local alternative (e.g. 'Tıkveşli Krema' in the green carton). Mention usage tips (e.g. 'It has less fat, so be careful when whipping').
2. If the user uploads a photo/text of a Turkish product (e.g. 'Labne'), explain what it is compared to UK products (e.g. 'It is similar to Cream Cheese but saltier/sourer. Good for toast, bad for cheesecake').
3. Be practical, concise, and helpful. Use bold text (<b>) for product names.
4. Do NOT include markdown blocks. Just simple HTML text.
";

// Prepare Payload
$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $promptText]
            ]
        ]
    ]
];

// Attach Image if present
if ($hasImage) {
    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (in_array($mime, $allowedTypes)) {
        $imageData = base64_encode(file_get_contents($file['tmp_name']));
        $payload['contents'][0]['parts'][] = [
            'inline_data' => [
                'mime_type' => $mime,
                'data' => $imageData
            ]
        ];
    }
}

// API URL (Using 2.5 Flash as verified working)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $GEMINI_API_KEY;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$curl_err = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    echo json_encode(['error' => 'Network error: ' . $curl_err]);
    exit;
}

$data = json_decode($response, true);

$analysis = "Could not analyze. Please try again.";

if(isset($data['error'])) {
    $analysis = "AI Error: " . ($data['error']['message'] ?? 'Unknown error');
} elseif (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $analysis = $data['candidates'][0]['content']['parts'][0]['text'];

    // Clean Output
    $analysis = preg_replace('/^```html/m', '', $analysis);
    $analysis = preg_replace('/^```/m', '', $analysis);
    $analysis = str_replace(['<!DOCTYPE html>', '<html>', '</html>', '<body>', '</body>'], '', $analysis);
    $analysis = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $analysis);
}

echo json_encode([
    'success' => true,
    'analysis' => $analysis
]);
?>
