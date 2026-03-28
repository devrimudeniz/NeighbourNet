<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/env.php';

$GEMINI_API_KEY = env_value('GEMINI_API_KEY', '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$hasImage = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;

if (!$hasImage) {
    echo json_encode(['error' => 'Please upload a menu photo.']);
    exit;
}

if ($GEMINI_API_KEY === '') {
    echo json_encode(['error' => 'Gemini API key missing']);
    exit;
}

// Build Prompt
$promptText = "You are a gourmet guide for tourists in Turkey. 
Analyze this restaurant menu photo.
1. Identify the dish names visible.
2. Translate them to clear English.
3. Describe the main ingredients (Meat? Veggie?).
4. Mark vegetarian/vegan options clearly.
5. Mention if it is known to be Spicy.
Format as a simple, appetizing list. Use <b>bold</b> for Dish Names. Do NOT use markdown.";

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

// Attach Image
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

// API URL (Gemini 2.5 Flash)
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

$analysis = "Could not analyze menu. Please try again.";

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
