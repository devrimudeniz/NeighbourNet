<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// Helper response function
function jsonResponse($status, $message, $data = []) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// 1. Handle Reporting (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $neighborhood = $_POST['neighborhood'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;
    
    // Basic validation
    if (!in_array($type, ['water', 'electricity']) || empty($neighborhood)) {
        jsonResponse('error', 'Invalid parameters');
    }

    // Rate Limiting / Duplicate Check
    $spamCheckKey = "report_{$neighborhood}_{$type}";
    
    // 1. Check User ID in DB (if logged in)
    if ($userId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM outage_reports WHERE neighborhood = ? AND type = ? AND user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)");
        $stmt->execute([$neighborhood, $type, $userId]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse('error', 'Bu kesintiyi zaten bildirdiniz. / You already reported this issue.');
        }
    } 
    // 2. Check Session (if guest or extra layer)
    elseif (isset($_SESSION['reported_outages'][$spamCheckKey]) && (time() - $_SESSION['reported_outages'][$spamCheckKey] < 7200)) {
        jsonResponse('error', 'Bu kesintiyi zaten bildirdiniz. / You already reported this issue.');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO outage_reports (type, neighborhood, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$type, $neighborhood, $userId]);
        
        // Log to session
        $_SESSION['reported_outages'][$spamCheckKey] = time();
        
        jsonResponse('success', 'Report submitted successfully');
    } catch (PDOException $e) {
        jsonResponse('error', 'Database error');
    }
}

// 2. Handle Fetching Status (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get reports from last 2 hours
    try {
        $sql = "SELECT r.neighborhood, r.type, COUNT(*) as report_count,
                GROUP_CONCAT(COALESCE(u.username, 'Misafir') ORDER BY r.created_at DESC SEPARATOR ', ') as reporters
                FROM outage_reports r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR) 
                GROUP BY r.neighborhood, r.type";
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $statusData = [];
        
        // Initialize zeros
        // Neighborhoods: Kalamar, Ortaalan, Kördere, Old Town, Kızıltaş, İslamlar, Üzümlü
        $neighborhoods = ['Kalamar', 'Ortaalan', 'Kordere', 'Old Town', 'Kiziltas', 'Islamlar', 'Uzumlu'];
        foreach ($neighborhoods as $nb) {
            $statusData[$nb] = [
                'water' => ['count' => 0, 'reporters' => ''],
                'electricity' => ['count' => 0, 'reporters' => '']
            ];
        }

        foreach ($results as $row) {
            $nbKey = $row->neighborhood; 
            if (isset($statusData[$nbKey])) {
                $statusData[$nbKey][$row->type] = [
                    'count' => (int)$row->report_count,
                    'reporters' => $row->reporters
                ];
            }
        }

        jsonResponse('success', 'Status data fetched', $statusData);

    } catch (PDOException $e) {
        jsonResponse('error', 'Database error: ' . $e->getMessage());
    }
}
?>
