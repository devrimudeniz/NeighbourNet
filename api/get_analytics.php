<?php
/**
 * Analytics API Endpoint
 * Returns analytics data in JSON format
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/analytics_helper.php';

header('Content-Type: application/json');

// Require login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info to check if business
$stmt = $pdo->prepare("SELECT role, badge FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$is_business = ($user['role'] == 'venue' || in_array($user['badge'], ['founder', 'business', 'moderator', 'verified_business']));

if (!$is_business) {
    echo json_encode(['success' => false, 'error' => 'Not a business account']);
    exit();
}

// Get period
$period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
$period = in_array($period, [7, 14, 30, 90]) ? $period : 30;

// Get stats
$stats = getProfileStats($pdo, $user_id, $period);
$engagement = getEngagementStats($pdo, $user_id, $period);
$top_posts = getTopPosts($pdo, $user_id, 5);

// Prepare chart data
$chart_labels = [];
$chart_data = [];
$start_date = new DateTime("-{$period} days");
$end_date = new DateTime();

while ($start_date <= $end_date) {
    $date_str = $start_date->format('Y-m-d');
    $chart_labels[] = $start_date->format('d M');
    $chart_data[] = $stats['daily'][$date_str] ?? 0;
    $start_date->modify('+1 day');
}

echo json_encode([
    'success' => true,
    'period' => $period,
    'stats' => [
        'total_views' => $stats['total_views'],
        'unique_visitors' => $stats['unique_visitors'],
        'member_views' => $stats['member_views'],
        'guest_views' => $stats['guest_views'],
        'devices' => $stats['devices'],
        'top_referrers' => $stats['top_referrers']
    ],
    'engagement' => $engagement,
    'top_posts' => $top_posts,
    'chart' => [
        'labels' => $chart_labels,
        'data' => $chart_data
    ]
]);
?>
