<?php
/**
 * API: Delete Listing
 * Allows listing owners to delete their listings
 */
require_once __DIR__ . '/../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Giriş yapmalısınız']);
    exit();
}

$user_id = $_SESSION['user_id'];
$listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : 0;

if (!$listing_id) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz ilan ID']);
    exit();
}

try {
    // Check if user owns the listing
    $stmt = $pdo->prepare("SELECT id, user_id, image_url FROM marketplace_listings WHERE id = ?");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();

    if (!$listing) {
        echo json_encode(['success' => false, 'error' => 'İlan bulunamadı']);
        exit();
    }

    if ($listing['user_id'] != $user_id) {
        // Check if user is admin
        $is_admin = in_array($_SESSION['badge'] ?? '', ['founder', 'moderator']);
        if (!$is_admin) {
            echo json_encode(['success' => false, 'error' => 'Bu ilanı silme yetkiniz yok']);
            exit();
        }
    }

    // Delete associated images from listing_images table
    $pdo->prepare("DELETE FROM listing_images WHERE listing_id = ?")->execute([$listing_id]);
    
    // Delete questions
    $pdo->prepare("DELETE FROM marketplace_questions WHERE listing_id = ?")->execute([$listing_id]);
    
    // Delete reviews
    $pdo->prepare("DELETE FROM marketplace_reviews WHERE listing_id = ?")->execute([$listing_id]);

    // Delete the listing
    $stmt = $pdo->prepare("DELETE FROM marketplace_listings WHERE id = ?");
    $stmt->execute([$listing_id]);

    echo json_encode(['success' => true, 'message' => 'İlan başarıyla silindi']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
