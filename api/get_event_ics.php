<?php
/**
 * Event ICS Calendar Export - .ics dosyası oluşturur
 * Kullanım: /api/get_event_ics.php?id=123
 */
require_once __DIR__ . '/../includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$stmt = $pdo->prepare("SELECT e.*, u.venue_name FROM events e JOIN users u ON e.user_id = u.id WHERE e.id = ? AND e.status = 'approved'");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$title = $event['title'];
$desc = strip_tags($event['description'] ?? '');
$location = $event['venue_name'] ?? '';
$dt_start = $event['event_date'] . 'T' . ($event['start_time'] ?? '00:00:00');
$dt_end = ($event['end_date'] ?? $event['event_date']) . 'T' . ($event['end_time'] ?? '23:59:00');
$url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com') . '/event_detail?id=' . $id;

// ICS format
$ics = "BEGIN:VCALENDAR\r\n";
$ics .= "VERSION:2.0\r\n";
$ics .= "PRODID:-//Kalkan Social//Event//EN\r\n";
$ics .= "METHOD:PUBLISH\r\n";
$ics .= "BEGIN:VEVENT\r\n";
$ics .= "UID:event-" . $id . "@kalkansocial.com\r\n";
$ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
$ics .= "DTSTART:" . date('Ymd\THis', strtotime($dt_start)) . "\r\n";
$ics .= "DTEND:" . date('Ymd\THis', strtotime($dt_end)) . "\r\n";
$ics .= "SUMMARY:" . str_replace(["\r", "\n", ','], ['', ' ', '\\,'], $title) . "\r\n";
if ($desc) $ics .= "DESCRIPTION:" . str_replace(["\r", "\n", ','], ['', ' ', '\\,'], substr($desc, 0, 500)) . "\r\n";
if ($location) $ics .= "LOCATION:" . str_replace(',', '\\,', $location) . "\r\n";
$ics .= "URL:" . $url . "\r\n";
$ics .= "END:VEVENT\r\n";
$ics .= "END:VCALENDAR\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="event-' . $id . '.ics"');
echo $ics;
