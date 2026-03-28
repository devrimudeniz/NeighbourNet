<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/site_settings.php';

header('Content-Type: application/manifest+json; charset=UTF-8');

$appName = site_name();
$shortName = site_short_name();
$description = site_tagline('en') ?: 'Community and events platform';

$manifest = [
    'name' => $appName,
    'short_name' => $shortName,
    'description' => $description,
    'start_url' => '/',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#ec4899',
    'orientation' => 'portrait',
    'scope' => '/',
    'icons' => [
        [
            'src' => 'logo.jpg',
            'sizes' => 'any',
            'type' => 'image/jpeg',
            'purpose' => 'any',
        ],
        [
            'src' => 'icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => 'icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
    ],
    'categories' => ['social', 'lifestyle', 'entertainment'],
    'screenshots' => [],
    'shortcuts' => [
        [
            'name' => 'Likya Yolu',
            'short_name' => 'Trail',
            'description' => 'Explore the Lycian Way',
            'url' => '/map.php',
            'icons' => [],
        ],
        [
            'name' => 'Events',
            'short_name' => 'Events',
            'description' => 'View upcoming events',
            'url' => '/events.php',
            'icons' => [],
        ],
        [
            'name' => 'Feed',
            'short_name' => 'Feed',
            'description' => 'View community feed',
            'url' => '/feed.php',
            'icons' => [],
        ],
    ],
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
