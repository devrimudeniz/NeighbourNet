<?php
require_once '../includes/db.php';

// Get a valid user ID (preferably admin or venue)
$stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'venue') LIMIT 1");
$user_id = $stmt->fetchColumn();
if (!$user_id) {
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $user_id = $stmt->fetchColumn();
}

if (!$user_id) {
    die("No user found to assign events to.");
}

$events = [
    [
        'title' => 'Sunset Jazz Night',
        'description' => 'Enjoy the sunset with smooth jazz music at the harbor. Cocktails and good vibes.',
        'category' => 'Music',
        'location' => 'Kalkan Harbor',
        'image' => 'https://images.unsplash.com/photo-1511192336575-5a79af67a629?w=800&q=80',
        'days_offset' => 1
    ],
    [
        'title' => 'Happy Hour Beach Party',
        'description' => 'Best DJ performance and happy hour prices. Don\'t miss out!',
        'category' => 'Party',
        'location' => 'Indigo Beach Club',
        'image' => 'https://images.unsplash.com/photo-1533174072545-e8d4aa97edf9?w=800&q=80',
        'days_offset' => 2
    ],
    [
        'title' => 'Wine & Cheese Tasting',
        'description' => 'Local wines and specially selected cheeses. A gourmet experience.',
        'category' => 'Food',
        'location' => 'Old Town Wine House',
        'image' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800&q=80',
        'days_offset' => 3
    ],
    [
        'title' => 'Morning Yoga Session',
        'description' => 'Start your day with energy. Yoga session with sea view.',
        'category' => 'Health',
        'location' => 'Public Beach',
        'image' => 'https://images.unsplash.com/photo-1544367563-12123d8965cd?w=800&q=80',
        'days_offset' => 1
    ]
];

foreach ($events as $evt) {
    $date = date('Y-m-d', strtotime('+' . $evt['days_offset'] . ' days'));
    $time = '20:00:00';
    if ($evt['category'] == 'Health') $time = '08:00:00';
    
    // Check if duplicate (simple check)
    $check = $pdo->prepare("SELECT id FROM events WHERE title = ? AND event_date = ?");
    $check->execute([$evt['title'], $date]);
    if (!$check->fetch()) {
        $sql = "INSERT INTO events (user_id, title, description, event_date, start_time, category, event_location, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$user_id, $evt['title'], $evt['description'], $date, $time, $evt['category'], $evt['location'], $evt['image']]);
        echo "Added: " . $evt['title'] . "\n";
    } else {
        echo "Skipped (exists): " . $evt['title'] . "\n";
    }
}
?>
