<?php
require_once '../includes/db.php';
require_once '../includes/gemini_helper.php';
require_once '../includes/env.php';
session_start();

header('Content-Type: application/json');

// Get language preference
$lang = $_GET['lang'] ?? 'tr';

// Check cache for this specific language (valid for 4 hours)
$stmt = $pdo->prepare("SELECT * FROM daily_summaries WHERE lang = ? AND created_at > DATE_SUB(NOW(), INTERVAL 4 HOUR) ORDER BY id DESC LIMIT 1");
$stmt->execute([$lang]);
$cached = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cached) {
    echo json_encode(['status' => 'success', 'summary' => $cached['summary_text'], 'source' => 'cache', 'created_at' => $cached['created_at']]);
    exit;
}

// If no cache, aggregate data
try {
    // 1. Recent Posts
    $posts = [];
    try {
        $stmt = $pdo->query("SELECT content, location FROM posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 10");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: posts query failed - " . $e->getMessage()); }

    // 2. Recent Marketplace
    $items = [];
    try {
        $stmt = $pdo->query("SELECT title, price, currency FROM marketplace_listings WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: marketplace query failed - " . $e->getMessage()); }

    // 3. Upcoming Events (Today/Tomorrow)
    $events = [];
    try {
        $stmt = $pdo->query("SELECT e.title, e.event_date, u.venue_name FROM events e JOIN users u ON e.user_id = u.id WHERE e.event_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR) LIMIT 5");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: events query failed - " . $e->getMessage()); }

    // 4. Lost Pets - Fetch ACTIVE lost pets regardless of time (limit 3)
    $pets = [];
    try {
        $stmt = $pdo->query("SELECT pet_name, pet_type, location FROM lost_pets WHERE status = 'lost' ORDER BY created_at DESC LIMIT 3");
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: lost_pets query failed - " . $e->getMessage()); }

    // 5. Happy Hours & Nightlife
    $happy_hours = [];
    try {
        $stmt = $pdo->query("SELECT h.title, h.event_type, h.performer_name, u.venue_name FROM happy_hours h JOIN users u ON h.user_id = u.id WHERE h.event_date >= CURDATE() LIMIT 5");
        $happy_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: happy_hours query failed - " . $e->getMessage()); }

    // 6. Trail Mate Alerts
    $trails = [];
    try {
        $stmt = $pdo->query("SELECT trail_segment, planned_date FROM trail_posts WHERE status = 'active' AND planned_date >= CURDATE() LIMIT 3");
        $trails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: trail_posts query failed - " . $e->getMessage()); }

    // 7. Recent Jobs
    $jobs = [];
    try {
        $stmt = $pdo->query("SELECT title, company_name FROM jobs WHERE created_at > DATE_SUB(NOW(), INTERVAL 72 HOUR) LIMIT 3");
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: jobs query failed - " . $e->getMessage()); }

    // 8. Recent Properties
    $properties = [];
    try {
        $stmt = $pdo->query("SELECT title, price, type FROM properties WHERE created_at > DATE_SUB(NOW(), INTERVAL 72 HOUR) LIMIT 3");
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("Summary: properties query failed - " . $e->getMessage()); }

    // Prepare Context
    $context = "";
    
    if (!empty($pets)) {
        $context .= "⚠️ URGENT - MISSING PETS (Priority mention):\n";
        foreach ($pets as $p) $context .= "- LOST " . ($p['pet_type'] ?? 'Pet') . ": " . $p['pet_name'] . " at " . $p['location'] . "\n";
        $context .= "\n";
    }

    if (!empty($posts)) {
        $context .= "Recent Community Posts:\n";
        foreach ($posts as $p) $context .= "- " . substr($p['content'], 0, 100) . " (" . ($p['location'] ?: 'Kalkan') . ")\n";
    }

    if (!empty($happy_hours)) {
        $context .= "\nNightlife & Happy Hours:\n";
        foreach ($happy_hours as $h) {
            $type = ($h['event_type'] == 'live_music') ? "Live Music with " . $h['performer_name'] : "Happy Hour Deal";
            $context .= "- " . $h['title'] . " at " . $h['venue_name'] . " ($type)\n";
        }
    }

    if (!empty($events)) {
        $context .= "\nUpcoming General Events:\n";
        foreach ($events as $e) $context .= "- " . $e['title'] . " at " . $e['venue_name'] . " (" . $e['event_date'] . ")\n";
    }

    if (!empty($trails)) {
        $context .= "\nHiking Partners Wanted (Trail Mate):\n";
        foreach ($trails as $t) $context .= "- Hiking " . $t['trail_segment'] . " on " . $t['planned_date'] . "\n";
    }

    if (!empty($items)) {
        $context .= "\nMarketplace Steals:\n";
        foreach ($items as $i) $context .= "- " . $i['title'] . " (" . $i['price'] . $i['currency'] . ")\n";
    }

    if (!empty($jobs)) {
        $context .= "\nNew Job Opportunities:\n";
        foreach ($jobs as $j) $context .= "- " . $j['title'] . " at " . $j['company_name'] . "\n";
    }

    if (!empty($properties)) {
        $context .= "\nNew Property Listings:\n";
        foreach ($properties as $pr) $context .= "- " . $pr['title'] . " (" . $pr['price'] . ", " . $pr['type'] . ")\n";
    }

    if (empty($context)) {
        // Not enough data to generate a meaningful specific summary
        if ($lang == 'en') {
            $summary = "It's a quiet day in Kalkan today. No new events or major announcements yet. Enjoy the sunshine! ☀️";
        } else {
            $summary = "Kalkan'da bugün sakin bir gün geçiyor. Henüz yeni bir etkinlik veya önemli bir duyuru yok. Günü keyfini çıkarın! ☀️";
        }
    } else {
        $apiKey = env_value('GEMINI_API_KEY', '');

        if (!$apiKey) {
            // Don't fail, show fallback but log error
            if ($lang == 'en') {
                $summary = "Welcome to Kalkan Social! There is a lot to explore today.";
            } else {
                $summary = "Kalkan Social'a hoş geldiniz! Bugün keşfedilecek çok şey var.";
            }
        } else {
            $gemini = new GeminiHelper($apiKey);
            $summary = $gemini->generateSummary($context, $lang);
        }
        
        if (!$summary) {
            // Fail-safe fallback
            if ($lang == 'en') {
                $summary = "Kalkan Social update: " . count($posts) . " new posts, " . count($items) . " new listings available.";
            } else {
                $summary = "Kalkan Social günceli: " . count($posts) . " yeni gönderi, " . count($items) . " yeni ilan mevcut.";
            }
        }
    }

    // Cache result with language
    $ins = $pdo->prepare("INSERT INTO daily_summaries (summary_text, lang) VALUES (?, ?)");
    $ins->execute([$summary, $lang]);

    echo json_encode(['status' => 'success', 'summary' => $summary, 'source' => 'generated', 'created_at' => date('Y-m-d H:i:s')]);

} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    $err_msg = ($lang == 'en') ? "Beautiful day in Kalkan! Error loading summary but enjoy the view." : "Kalkan'da güzel bir gün! Veriler yüklenirken bir hata oluştu ama keyfini çıkarın.";
    echo json_encode(['status' => 'success', 'summary' => $err_msg, 'error_debug' => $e->getMessage()]);
}
?>
