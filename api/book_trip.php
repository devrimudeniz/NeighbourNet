<?php
/**
 * API: Handle Boat Trip Booking Requests
 */

require_once '../includes/db.php';
require_once '../includes/email-helper.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit();
}

$user_id = $_SESSION['user_id'];
$trip_id = $_POST['trip_id'] ?? 0;
$date = $_POST['booking_date'] ?? '';
$guests = $_POST['num_guests'] ?? 1;
$phone = $_POST['contact_phone'] ?? '';
$requests = $_POST['special_requests'] ?? '';

if (!$trip_id || !$date || !$phone) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

try {
    // Verify Trip exists and get captain info
    $stmt = $pdo->prepare("
        SELECT bt.*, u.id as captain_user_id, u.full_name as captain_name, u.email as captain_email, u.preferred_language as captain_lang
        FROM boat_trips bt
        JOIN users u ON bt.captain_id = u.id
        WHERE bt.id = ?
    ");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch();

    if (!$trip) {
        echo json_encode(['status' => 'error', 'message' => 'Trip not found']);
        exit();
    }

    $total_price = $trip['price_per_person'] * $guests;

    // Get requester info
    $req_stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $req_stmt->execute([$user_id]);
    $requester = $req_stmt->fetch();

    // Create Booking
    $stmt = $pdo->prepare("
        INSERT INTO trip_bookings 
        (trip_id, user_id, booking_date, num_guests, total_price, status, contact_phone, special_requests)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    
    $stmt->execute([
        $trip_id,
        $user_id,
        $date,
        $guests,
        $total_price,
        $phone,
        $requests
    ]);

    $booking_id = $pdo->lastInsertId();

    // === IN-APP NOTIFICATION FOR CAPTAIN ===
    $notif_content = ($trip['captain_lang'] == 'en')
        ? $requester['full_name'] . ' requested a booking for your trip: ' . $trip['title']
        : $requester['full_name'] . ' tekne turunuz için rezervasyon talebi gönderdi: ' . $trip['title'];
    
    $notif_url = 'my_trips?booking=' . $booking_id;
    
    $notif_stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, actor_id, type, source_id, content, url) 
        VALUES (?, ?, 'booking_request', ?, ?, ?)
    ");
    $notif_stmt->execute([
        $trip['captain_user_id'],
        $user_id,
        $booking_id,
        $notif_content,
        $notif_url
    ]);

    // === EMAIL NOTIFICATION FOR CAPTAIN ===
    if (!empty($trip['captain_email'])) {
        $lang = $trip['captain_lang'] ?? 'tr';
        $subject = $lang == 'en' 
            ? '🚢 New Booking Request - ' . $trip['title']
            : '🚢 Yeni Rezervasyon Talebi - ' . $trip['title'];
        
        $email_body = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%); color: white; padding: 40px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .info-box { background: #f1f5f9; border-radius: 12px; padding: 20px; margin: 20px 0; }
                .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
                .info-row:last-child { border-bottom: none; }
                .label { color: #64748b; font-size: 14px; }
                .value { color: #1e293b; font-weight: bold; }
                .btn { display: inline-block; background: #06b6d4; color: white; padding: 15px 30px; text-decoration: none; border-radius: 12px; font-weight: bold; margin-top: 20px; }
                .footer { padding: 20px; text-align: center; color: #64748b; font-size: 12px; background: #f8fafc; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚓ " . ($lang == 'en' ? 'New Booking Request!' : 'Yeni Rezervasyon Talebi!') . "</h1>
                </div>
                <div class='content'>
                    <p style='color: #475569;'>" . 
                        ($lang == 'en' 
                            ? 'You have received a new booking request for your boat trip.'
                            : 'Tekne turunuz için yeni bir rezervasyon talebi aldınız.') . 
                    "</p>
                    
                    <div class='info-box'>
                        <div class='info-row'>
                            <span class='label'>" . ($lang == 'en' ? 'Trip' : 'Tur') . "</span>
                            <span class='value'>" . htmlspecialchars($trip['title']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>" . ($lang == 'en' ? 'Guest Name' : 'Misafir') . "</span>
                            <span class='value'>" . htmlspecialchars($requester['full_name']) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>" . ($lang == 'en' ? 'Date' : 'Tarih') . "</span>
                            <span class='value'>" . date('d.m.Y', strtotime($date)) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>" . ($lang == 'en' ? 'Guests' : 'Kişi Sayısı') . "</span>
                            <span class='value'>$guests " . ($lang == 'en' ? 'people' : 'kişi') . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>" . ($lang == 'en' ? 'Phone' : 'Telefon') . "</span>
                            <span class='value'>" . htmlspecialchars($phone) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='label'>" . ($lang == 'en' ? 'Total' : 'Toplam') . "</span>
                            <span class='value'>$total_price " . $trip['currency'] . "</span>
                        </div>
                    </div>
                    
                    " . (!empty($requests) ? "
                    <p style='color: #64748b; font-size: 14px;'><strong>" . ($lang == 'en' ? 'Special Requests:' : 'Özel İstekler:') . "</strong><br>" . htmlspecialchars($requests) . "</p>
                    " : "") . "
                    
                    <center>
                        <a href='https://kalkansocial.com/my_trips' class='btn'>
                            " . ($lang == 'en' ? 'View Booking Requests' : 'Talepleri Görüntüle') . "
                        </a>
                    </center>
                </div>
                <div class='footer'>
                    " . ($lang == 'en' 
                        ? 'Please respond to this request as soon as possible.'
                        : 'Lütfen bu talebe en kısa sürede yanıt verin.') . "
                </div>
            </div>
        </body>
        </html>
        ";
        
        sendEmail($trip['captain_email'], $subject, $email_body);
    }

    echo json_encode(['status' => 'success', 'booking_id' => $booking_id]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

