<?php
/**
 * Profanity Filter - Küfürlü sözcük koruması
 * Blocks posts containing offensive words (TR + EN)
 */
class ProfanityFilter {
    private static $words = null;

    private static function getWords() {
        if (self::$words === null) {
            self::$words = array_merge(
                self::getTurkishWords(),
                self::getEnglishWords()
            );
        }
        return self::$words;
    }

    private static function getTurkishWords() {
        return [
            'amk', 'aq', 'sik', 'sikim', 'sikeyim', 'sikerim', 'siksin', 'siktir', 'siktirgit',
            'orospu', 'orosbu', 'orospucocuk', 'orospu cocuk', 'piç', 'pic', 'göt', 'got',
            'götüm', 'götün', 'götüne', 'amcık', 'amcik', 'yarrak', 'yarrrak',
            'kahpe', 'döl', 'dol', 'bok', 'boktan', 'anan', 'ananı', 'anani', 'anani sikeyim',
            'baban', 'babani',             'pezevenk', 'pezeveng', 'ibne', 'ibneler', 'kaltak'
        ];
    }

    private static function getEnglishWords() {
        return [
            'fuck', 'fucking', 'fucker', 'fucked', 'fuck you', 'motherfucker', 'motherfucking',
            'shit', 'bullshit', 'shitty', 'asshole', 'ass hole', 'bitch', 'bitches',
            'dick', 'dickhead', 'cock', 'cunt', 'pussy', 'whore', 'slut', 'bastard',
            'nigga', 'nigger', 'retard', 'retarded', 'fag', 'faggot', 'piss', 'pissed'
        ];
    }

    /**
     * Check if text contains profanity
     * @param string $text Plain text (HTML stripped)
     * @return bool True if profanity found
     */
    public static function containsProfanity($text) {
        if (empty($text) || !is_string($text)) return false;

        $text = self::normalize($text);
        $words = self::getWords();

        foreach ($words as $word) {
            // Word boundary match (with optional Turkish chars)
            $pattern = '/\b' . preg_quote($word, '/') . '\b/ui';
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize text for matching: strip HTML, lowercase, collapse spaces
     */
    private static function normalize($text) {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
