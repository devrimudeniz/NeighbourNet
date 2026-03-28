<?php
require_once '../includes/db.php';
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$photo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$business_id = isset($_GET['business_id']) ? (int)$_GET['business_id'] : 0;

if (!$photo_id || !$business_id) {
    die("Invalid ID");
}

// Verify ownership
// The user must be the owner of the business OR a moderator/founder
// First get business owner
$stmt = $pdo->prepare("SELECT owner_id FROM business_listings WHERE id = ?");
$stmt->execute([$business_id]);
$business_owner = $stmt->fetchColumn();

$is_owner = ($business_owner == $_SESSION['user_id']);
$is_admin = isset($_SESSION['badge']) && in_array($_SESSION['badge'], ['founder', 'moderator']);

if (!$is_owner && !$is_admin) {
    die("Permission denied");
}

// Check if photo exists and get path
$stmt = $pdo->prepare("SELECT photo_url FROM business_photos WHERE id = ? AND business_id = ?");
$stmt->execute([$photo_id, $business_id]);
$photo_path = $stmt->fetchColumn();

if ($photo_path) {
    // Delete from DB
    $del = $pdo->prepare("DELETE FROM business_photos WHERE id = ?");
    $del->execute([$photo_id]);
    
    // Optional: Delete physical file (often skipped to avoid breaking backups or caches, but good practice to clean up)
    // if (file_exists('../' . $photo_path)) { unlink('../' . $photo_path); }
}

// Redirect back
header("Location: ../edit_business?id=$business_id");
exit();
?>
