<?php
/**
 * API Endpoint: Map Events
 * Returns JSON list of events with coordinates
 */
require_once '../includes/db.php';
header('Content-Type: application/json');

try {
    // Basic filtering params
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
    
    $sql = "SELECT e.id, e.title, e.event_date, e.description, e.location, 
            e.image_url, e.category, e.latitude, e.longitude, u.venue_name 
            FROM events e 
            JOIN users u ON e.user_id = u.id 
            WHERE e.event_date >= CURDATE() AND e.latitude IS NOT NULL";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND e.category = ?";
        $params[] = $category;
    }
    
    if ($date_filter === 'today') {
        $sql .= " AND e.event_date = CURDATE()";
    } elseif ($date_filter === 'tomorrow') {
        $sql .= " AND e.event_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($date_filter === 'weekend') {
        // Simple logic: Next Friday/Saturday/Sunday - simplified for now to just next 7 days
        $sql .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    
    // Format for frontend
    $formatted_events = [];
    foreach ($events as $evt) {
        $formatted_events[] = [
            'id' => (int)$evt['id'],
            'title' => $evt['title'],
            'venue' => $evt['venue_name'],
            'date' => date('d.m.Y', strtotime($evt['event_date'])),
            'image' => $evt['image_url'],
            'category' => $evt['category'],
            'lat' => (float)$evt['latitude'],
            'lng' => (float)$evt['longitude'],
            'desc' => mb_substr($evt['description'], 0, 100) . '...' // Short desc
        ];
    }
    
    // Fetch Pharmacies on Duty (Could be multiple)
    $pharmacies = [];
    $pharm_stmt = $pdo->query("SELECT * FROM pharmacies WHERE is_on_duty = 1");
    $all_pharmacies = $pharm_stmt->fetchAll();
    
    foreach ($all_pharmacies as $p) {
        $pharmacies[] = [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'phone' => $p['phone'],
            'address' => $p['address'],
            'lat' => (float)$p['latitude'],
            'lng' => (float)$p['longitude'],
            'type' => 'pharmacy'
        ];
    }
    
    // Fetch Live Tracks
    $tracks = [];
    $track_stmt = $pdo->query("
        SELECT tt.*, u.username, u.avatar 
        FROM trail_tracking tt 
        JOIN users u ON tt.user_id = u.id 
        WHERE tt.is_active = 1 AND tt.updated_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $all_tracks = $track_stmt->fetchAll();
    foreach ($all_tracks as $t) {
        $tracks[] = [
            'username' => $t['username'],
            'avatar' => $t['avatar'],
            'lat' => (float)$t['last_lat'],
            'lng' => (float)$t['last_lng'],
            'updated_at' => $t['updated_at']
        ];
    }
    
    echo json_encode([
        'status' => 'success', 
        'events' => $formatted_events,
        'pharmacies' => $pharmacies,
        'tracks' => $tracks
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
