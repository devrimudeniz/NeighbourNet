<?php
// Translation API using Google Gemini
header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/env.php';

$GEMINI_API_KEY = env_value('GEMINI_API_KEY', '');

// Support both JSON (from lingo.php) and FormData (from feed/index.php)
$text = null;
$target_lang = null;

if (isset($_POST['text'])) {
    $text = trim($_POST['text']);
    $target_lang = $_POST['target_lang'] ?? 'tr';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['text'])) {
        $text = trim($input['text']);
        $target_lang = $input['target_lang'] ?? 'tr';
    }
}

if (!$text || !$target_lang) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

if ($GEMINI_API_KEY === '') {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Gemini API key missing']);
    exit;
}

// Ensure Cache Table Exists
try {
    $check = $pdo->query("SHOW TABLES LIKE 'translation_cache'");
    if($check->rowCount() == 0) {
        $pdo->exec("CREATE TABLE translation_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            text_hash VARCHAR(32) NOT NULL,
            original_text TEXT NOT NULL,
            target_lang VARCHAR(5) NOT NULL,
            translated_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (text_hash, target_lang)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
} catch (Exception $e) {
    // Continue without cache if DB error
}

// CHECK CACHE
$text_hash = md5($text);
try {
    $stmt = $pdo->prepare("SELECT translated_text FROM translation_cache WHERE text_hash = ? AND target_lang = ? LIMIT 1");
    $stmt->execute([$text_hash, $target_lang]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'success' => true,
            'status' => 'success',
            'translated_text' => $row['translated_text'],
            'cached' => true
        ]);
        exit;
    }
} catch (Exception $e) {
    // Cache miss or error, proceed
}

// Language names for prompt
$languages = [
    'tr' => 'Turkish',
    'en' => 'English',
    'de' => 'German',
    'fr' => 'French',
    'es' => 'Spanish',
    'it' => 'Italian',
    'ru' => 'Russian',
    'ar' => 'Arabic',
    'zh' => 'Chinese',
    'ja' => 'Japanese',
    'ko' => 'Korean',
    'pt' => 'Portuguese',
    'nl' => 'Dutch',
    'pl' => 'Polish',
    'sv' => 'Swedish',
    'no' => 'Norwegian',
    'da' => 'Danish',
    'fi' => 'Finnish',
    'el' => 'Greek',
    'he' => 'Hebrew',
    'hi' => 'Hindi',
    'th' => 'Thai',
    'vi' => 'Vietnamese',
    'uk' => 'Ukrainian',
    'cs' => 'Czech',
    'hu' => 'Hungarian',
    'ro' => 'Romanian',
    'bg' => 'Bulgarian',
    'hr' => 'Croatian',
    'sk' => 'Slovak',
    'sl' => 'Slovenian'
];

$target_language_name = $languages[$target_lang] ?? 'English';

// Build prompt for Gemini
$prompt = "Detect the language of the following text. If it is Turkish, translate it to English. If it is English, translate it to Turkish. For any other language, translate it to English. Return ONLY the translated text, nothing else. Do not add any explanations, notes, or formatting. Just the pure translation.\n\nText to translate:\n{$text}";

// Call Gemini API
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $GEMINI_API_KEY;

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.1,
        'maxOutputTokens' => 2048
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_NONE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_NONE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_NONE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_NONE'
        ]
    ]
];

$target_language_name = $languages[$target_lang] ?? 'English';
$prompt = "Translate the following text to {$target_language_name}. Return ONLY the translated text, nothing else. Do not add any explanations, notes, or formatting. Just the pure translation.\n\nText to translate:\n{$text}";

$payload['contents'][0]['parts'][0]['text'] = $prompt;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curl_err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log for debugging
$log_entry = date('Y-m-d H:i:s') . " - Code: $http_code - Err: $curl_err - Resp: $response\n";
file_put_contents('translation_debug.log', $log_entry, FILE_APPEND);

if ($curl_err) {
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Network error: ' . $curl_err
    ]);
    exit;
}

$data = json_decode($response, true);

// Check for API errors
if ($http_code !== 200) {
    $error_msg = $data['error']['message'] ?? 'API error';
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $error_msg,
        'http_code' => $http_code,
        'raw_response' => $data // Send back raw data for debugging
    ]);
    exit;
}

// Extract translated text from Gemini response
$translated = null;
if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $translated = trim($data['candidates'][0]['content']['parts'][0]['text']);
}

if ($translated) {
    // Save to cache
    try {
        $ins = $pdo->prepare("INSERT IGNORE INTO translation_cache (text_hash, original_text, target_lang, translated_text) VALUES (?, ?, ?, ?)");
        $ins->execute([$text_hash, $text, $target_lang, $translated]);
    } catch (Exception $e) { }

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'translated_text' => $translated,
        'provider' => 'gemini'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'No translation received',
        'debug' => $data
    ]);
}
