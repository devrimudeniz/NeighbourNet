<?php
/**
 * Dynamic Sitemap Generator
 * Tüm statik sayfalar + dinamik içerikler (posts, events, businesses, guides, jobs, properties, boat trips, groups, marketplace)
 */
header("Content-Type: application/xml; charset=utf-8");
require_once 'includes/db.php';
require_once 'includes/site_settings.php';

$baseUrl = site_url();
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php
// ── Static Pages ──
$staticPages = [
    // Ana sayfalar
    ['/', 'daily', '1.0'],
    ['/feed', 'hourly', '0.9'],
    ['/events', 'daily', '0.9'],
    ['/directory', 'daily', '0.9'],
    ['/members', 'weekly', '0.7'],
    ['/groups', 'weekly', '0.7'],
    ['/news', 'daily', '0.8'],
    ['/photo_contest.php', 'daily', '0.8'],

    // Önemli Hizmetler
    ['/guidebook.php', 'weekly', '0.8'],
    ['/community_support', 'daily', '0.8'],
    ['/duty_pharmacy', 'daily', '0.8'],
    ['/first_aid', 'monthly', '0.7'],
    ['/weather', 'hourly', '0.7'],
    ['/pati_safe.php', 'daily', '0.8'],
    ['/lost_found.php', 'daily', '0.8'],
    ['/pet_sitting.php', 'weekly', '0.7'],
    ['/status', 'daily', '0.7'],

    // İş & Ticaret
    ['/marketplace.php', 'daily', '0.8'],
    ['/jobs', 'daily', '0.8'],
    ['/properties', 'daily', '0.8'],

    // Ulaşım
    ['/transportation', 'weekly', '0.7'],
    ['/rides', 'daily', '0.7'],
    ['/boat_trips.php', 'daily', '0.7'],
    ['/flights', 'monthly', '0.5'],

    // Keşfet & Aktiviteler
    ['/trail_mate', 'weekly', '0.7'],
    ['/happy_hour', 'daily', '0.7'],
    ['/time_travel', 'monthly', '0.6'],
    ['/what_to_do.php', 'monthly', '0.7'],
    ['/lingo', 'weekly', '0.6'],

    // AI Araçları
    ['/paperwork.php', 'monthly', '0.6'],
    ['/grocery.php', 'monthly', '0.6'],
    ['/menu_decoder.php', 'monthly', '0.6'],
    ['/pharmacy_ai.php', 'monthly', '0.6'],
    ['/culture_lens.php', 'monthly', '0.6'],

    // İletişim & Bilgi
    ['/contact.php', 'monthly', '0.5'],
    ['/faq.php', 'monthly', '0.5'],
    ['/changelog.php', 'monthly', '0.5'],
    ['/expert_application.php', 'monthly', '0.5'],
    ['/login', 'monthly', '0.4'],
    ['/register', 'monthly', '0.4'],

    // Yasal
    ['/privacy', 'yearly', '0.3'],
    ['/terms', 'yearly', '0.3'],
    ['/kvkk', 'yearly', '0.3'],
    ['/safety_standards', 'yearly', '0.3'],
];

foreach ($staticPages as $page) {
    echo "<url>\n";
    echo "  <loc>{$baseUrl}{$page[0]}</loc>\n";
    echo "  <changefreq>{$page[1]}</changefreq>\n";
    echo "  <priority>{$page[2]}</priority>\n";
    echo "</url>\n";
}

// ── Dynamic: Posts (son 500) ──
try {
    $stmt = $pdo->query("SELECT id, created_at FROM posts WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 500");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/post_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>monthly</changefreq>\n";
        echo "  <priority>0.5</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Events ──
try {
    $stmt = $pdo->query("SELECT id, created_at FROM events WHERE status = 'approved' ORDER BY event_date DESC LIMIT 200");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/event_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.6</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Businesses (Directory) ──
try {
    $stmt = $pdo->query("SELECT id, updated_at FROM businesses WHERE status = 'approved' ORDER BY updated_at DESC LIMIT 300");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['updated_at'] ?? $today));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/business_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.7</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Guidebook articles ──
try {
    $stmt = $pdo->query("SELECT id, updated_at, created_at FROM guides WHERE status = 'published' ORDER BY created_at DESC LIMIT 100");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['updated_at'] ?? $row['created_at']));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/guide_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>monthly</changefreq>\n";
        echo "  <priority>0.6</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Jobs ──
try {
    $stmt = $pdo->query("SELECT id, created_at FROM jobs WHERE status = 'active' ORDER BY created_at DESC LIMIT 100");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/job_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.6</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Properties ──
try {
    $stmt = $pdo->query("SELECT id, created_at FROM properties WHERE status = 'active' ORDER BY created_at DESC LIMIT 200");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/property_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.6</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Boat Trips ──
try {
    $stmt = $pdo->query("SELECT id, created_at FROM boat_trips WHERE status = 'approved' ORDER BY created_at DESC LIMIT 50");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/trip_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.6</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Marketplace Listings ──
try {
    $stmt = $pdo->query("SELECT id, created_at FROM marketplace_listings WHERE status = 'active' ORDER BY created_at DESC LIMIT 200");
    while ($row = $stmt->fetch()) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/listing_detail?id={$row['id']}</loc>\n";
        echo "  <lastmod>{$date}</lastmod>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.5</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: Groups ──
try {
    $stmt = $pdo->query("SELECT id FROM groups ORDER BY id DESC LIMIT 100");
    while ($row = $stmt->fetch()) {
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/group_detail?id={$row['id']}</loc>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.5</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}

// ── Dynamic: User Profiles (aktif kullanıcılar) ──
try {
    $stmt = $pdo->query("SELECT id FROM users WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 90 DAY) ORDER BY last_seen DESC LIMIT 500");
    while ($row = $stmt->fetch()) {
        echo "<url>\n";
        echo "  <loc>{$baseUrl}/profile?uid={$row['id']}</loc>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.4</priority>\n";
        echo "</url>\n";
    }
} catch (Exception $e) {}
?>
</urlset>
