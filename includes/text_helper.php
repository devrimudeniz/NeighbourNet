<?php
/**
 * Format text with mentions and hashtags
 * SECURE VERSION: Escapes HTML first to prevent XSS
 */
function formatContent($text) {
    // 1. Escape HTML entities first for security
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // 2. Parse @mentions
    // We strictly match @username (letters, numbers, underscores)
    $text = preg_replace_callback('/@([a-zA-Z0-9_]+)/', function($matches) {
        $username = $matches[1];
        // We can't verify username existence here easily without DB, so we just link it. 
        // 404 handler on profile.php will handle invalid users.
        return '<a href="profile.php?username=' . $username . '" class="text-pink-500 hover:underline font-bold">@' . $username . '</a>';
    }, $text);
    
    // 3. Parse #hashtags
    $text = preg_replace_callback('/#([a-zA-Z0-9_]+)/', function($matches) {
        $tag = $matches[1];
        return '<a href="search.php?q=' . urlencode($tag) . '" class="text-blue-500 hover:underline">#' . $tag . '</a>';
    }, $text);
    
    // 4. Convert newlines
    return nl2br($text);
}

function time_elapsed_string($datetime, $full = false) {
    if ($datetime == '0000-00-00 00:00:00') return "Just now";
    
    try {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
    } catch (Exception $e) {
        return $datetime;
    }

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        $val = 0;
        if ($k == 'w') {
            $val = $weeks;
        } elseif ($k == 'd') {
            $val = $days;
        } elseif (property_exists($diff, $k)) {
            $val = $diff->$k;
        }

        if ($val) {
            $v = $val . ' ' . $v . ($val > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
