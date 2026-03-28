<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/env.php';

$GEMINI_API_KEY = env_value('GEMINI_API_KEY', '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No image uploaded or upload error']);
    exit;
}

if ($GEMINI_API_KEY === '') {
    echo json_encode(['error' => 'Gemini API key missing']);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
    exit;
}

// Read file data
$imageData = base64_encode(file_get_contents($file['tmp_name']));

// Prompt for Gemini
$prompt = "You are an expert assistant for expats living in Turkey. 
Analyze this image. It is likely a Turkish document (bill, official letter, receipt) or an SMS screenshot.
1. Identify what type of document it is.
2. Extract the key information (dates, amounts, due dates, warnings).
3. Summarize it in clear, simple English.
4. Tell the user exactly what action they need to take (e.g. 'Pay 1500 TL by Friday at PTT', 'No action needed', 'Go to Immigration Office').
Format your response in simple HTML (using <b> for bolding key info), but do not include ```html or markdown blocks. Just the content.";

// Prepare API Payload for Gemini 2.5 Flash
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $GEMINI_API_KEY;

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt],
                [
                    'inline_data' => [
                        'mime_type' => $mime,
                        'data' => $imageData
                    ]
                ]
            ]
        ]
    ]
];

// Execute Curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Allow time for vision processing

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

// Debug Logging
$log_entry = date('Y-m-d H:i:s') . " - HTTP: $http_code - Err: $curl_err - Resp: " . substr($response, 0, 500) . "...\n";
file_put_contents('debug_gemini.log', $log_entry, FILE_APPEND);

if ($curl_err) {
    echo json_encode(['error' => 'Network error: ' . $curl_err]);
    exit;
}

$data = json_decode($response, true);

// Extract text
$analysis = "Could not analyze image. Please try again or ask an admin.";

// Check for errors in response
if(isset($data['error'])) {
    $analysis = "AI Error: " . ($data['error']['message'] ?? 'Unknown error');
} elseif (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $analysis = $data['candidates'][0]['content']['parts'][0]['text'];
    
    // Clean Output
    // 1. Remove Markdown code blocks (```html ... ```)
    $analysis = preg_replace('/^```html/m', '', $analysis);
    $analysis = preg_replace('/^```/m', '', $analysis);
    
    // 2. Remove full HTML structure if present
    $analysis = str_replace(['<!DOCTYPE html>', '<html>', '</html>', '<body>', '</body>', '<head>', '</head>'], '', $analysis);
    
    // 3. Convert Markdown Bold (**text**) to HTML Bold (<b>text</b>)
    $analysis = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $analysis);
    
    // 4. Convert Markdown headers (### Text) to HTML headers (<h4>Text</h4>)
    $analysis = preg_replace('/^### (.*)$/m', '<h4>$1</h4>', $analysis);
    $analysis = preg_replace('/^## (.*)$/m', '<h3>$1</h3>', $analysis);

} elseif (isset($data['promptFeedback'])) {
    $analysis = "Blocked by safety filters. Please try another image.";
}

echo json_encode([
    'success' => true,
    'analysis' => $analysis,
    'debug_model' => 'gemini-2.5-flash',
    'debug_http' => $http_code
]);
?>
