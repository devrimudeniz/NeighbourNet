<?php
require_once '../includes/db.php';
require_once '../includes/lang.php'; // For text localization if needed, though API usually returns raw data or localized strings
session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

// Helper to format user
function formatUser($u) {
    return [
        'id' => $u['id'],
        'username' => $u['username'],
        'full_name' => $u['full_name'],
        'avatar' => !empty($u['avatar']) ? $u['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($u['full_name']) . '&background=random',
        'badge' => $u['badge'],
        'venue_name' => $u['venue_name']
    ];
}

// Handle POST actions (Log / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user_id == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'log') {
        $searched_uid = $_POST['searched_user_id'] ?? null;
        $term = $_POST['term'] ?? null;

        if ($searched_uid) {
            // Check if already exists recently? Or simple insert. 
            // Better: Delete old verify same user same searcher, then insert new (to update timestamp)
            $pdo->prepare("DELETE FROM search_history WHERE user_id = ? AND searched_user_id = ?")->execute([$user_id, $searched_uid]);
            $stmt = $pdo->prepare("INSERT INTO search_history (user_id, searched_user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $searched_uid]);
        }
        echo json_encode(['status' => 'success']);
    } 
    elseif ($action === 'delete') {
        $history_id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM search_history WHERE id = ? AND user_id = ?");
        $stmt->execute([$history_id, $user_id]);
        echo json_encode(['status' => 'success']);
    }
    exit;
}

// Handle GET (Search / Suggestions)
$query = $_GET['q'] ?? '';

if (!empty($query)) {
    $results = [];

    // 1. Search Users
    $sql_users = "SELECT id, username, full_name, avatar, badge, venue_name, 'user' as type FROM users 
            WHERE (username LIKE ? OR full_name LIKE ?) AND id != ? 
            LIMIT 5";
    $stmt = $pdo->prepare($sql_users);
    $term = "%$query%";
    $stmt->execute([$term, $term, $user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($users as $u) {
        $data = formatUser($u);
        $data['type'] = 'user';
        $results[] = $data;
    }

    // 2. Search Posts
    // Join users to get author info
    $sql_posts = "SELECT p.id, p.content, p.user_id, 'post' as type, u.username, u.full_name, u.avatar 
                  FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.content LIKE ? 
                  ORDER BY p.created_at DESC 
                  LIMIT 5";
    $stmt = $pdo->prepare($sql_posts);
    $stmt->execute([$term]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($posts as $p) {
        $results[] = [
            'id' => $p['id'],
            'type' => 'post',
            'content' => mb_strimwidth($p['content'], 0, 60, "..."), // Truncate
            'user' => [
                'username' => $p['username'],
                'full_name' => $p['full_name'],
                'avatar' => !empty($p['avatar']) ? $p['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($p['full_name']) . '&background=random'
            ]
        ];
    }
    
    echo json_encode(['results' => $results]);

} else {
    // Default View: History + Suggestions
    $response = ['history' => [], 'suggestions' => []];

    if ($user_id > 0) {
        // 1. History
        $hist_sql = "
            SELECT h.id as history_id, h.created_at, u.id, u.username, u.full_name, u.avatar, u.badge, u.venue_name 
            FROM search_history h 
            JOIN users u ON h.searched_user_id = u.id 
            WHERE h.user_id = ? 
            ORDER BY h.created_at DESC 
            LIMIT 5";
        $stmt = $pdo->prepare($hist_sql);
        $stmt->execute([$user_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($history as $h) {
            $u = formatUser($h);
            $u['history_id'] = $h['history_id']; // For deletion
            $u['type'] = 'history';
            $response['history'][] = $u;
        }

        // 2. Suggestions (People you may know)
        // Algorithm: Users not in friends, not followed, maybe popular or random for now
        // Simple V1: Random 5 users not me and not in my history
        $sug_sql = "
            SELECT u.id, u.username, u.full_name, u.avatar, u.badge, u.venue_name 
            FROM users u 
            WHERE u.id != ? 
            AND u.id NOT IN (SELECT searched_user_id FROM search_history WHERE user_id = ?)
            ORDER BY RAND() 
            LIMIT 5";
        $stmt = $pdo->prepare($sug_sql);
        $stmt->execute([$user_id, $user_id]);
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['suggestions'] = array_map('formatUser', $suggestions);
    }

    echo json_encode($response);
}
?>
