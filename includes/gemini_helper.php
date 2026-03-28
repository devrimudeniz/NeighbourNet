<?php
/**
 * Gemini AI Helper
 * Handles interactions with Google's Gemini API
 */
class GeminiHelper {
    private $apiKey;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function generateSummary($contextData, $lang = 'tr') {
        $msg_lang = ($lang == 'en') ? "English" : "Turkish";
        
        $prompt = "You are a friendly, slightly cheeky local assistant for Kalkan, Turkey. Your style is casual, neighborly, and community-focused.
        
        Below is a list of recent activities from the 'Kalkan Social' platform.
        
        Your task is to generate a 'Daily Briefing' paragraph.
        - TONE: If writing in English, use British slang and idioms (e.g., 'Cheers', 'Lovely', 'Mate', 'Proper', 'Spot on', 'Brilliant', 'A bit of a trek'). Make it sound like a friendly expat or local who's been here for years.
        - TONE: If writing in Turkish, use a warm, friendly, and sincere 'Kalkanlı' or neighborly tone.
        - CRITICAL: If there are MISSING PETS (Lost Pets), mention them FIRST with genuine urgency and concern to help find them.
        - CONTENT: Group the rest logically (Nightlife/Events, Jobs/Marketplace, Hiking/Outdoors).
        - STYLE: Be concise but engaging. Maximum 5-6 sentences.
        - FORMAT: Write as a single continuous narrative paragraph. Use **bold** for key terms. NO bullet points.
        - Language: " . $msg_lang . ". (Write the entire summary in this language).
        
        If there is absolutely no specific data in the context, just give a lovely, sunny Kalkan greeting and encourage people to post something brilliant.
        
        DATA:
        " . $contextData;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            error_log('Gemini API Error: ' . curl_error($ch));
            return null;
        }
        
        curl_close($ch);

        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        } else {
            error_log('Gemini API Invlaid Response: ' . $response);
            return null;
        }
    }
}
?>
