<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen giriş yapın']);
    exit();
}

$user_id = $_SESSION['user_id'];
$experience_years = (int)($_POST['experience_years'] ?? 0);
$bio = $_POST['bio'] ?? '';
$daily_rate = $_POST['daily_rate'] ?? '';
$phone = $_POST['phone'] ?? '';
$pet_types = isset($_POST['pet_types']) ? implode(',', $_POST['pet_types']) : '';
$services = isset($_POST['services']) ? implode(',', $_POST['services']) : '';
$location = $_POST['location'] ?? '';

if (empty($bio) || empty($daily_rate)) {
    echo json_encode(['status' => 'error', 'message' => 'Lütfen biyografi ve ücret bilgilerini doldurun.']);
    exit();
}

try {
    // Check if sitter already exists
    $stmt = $pdo->prepare("SELECT id FROM pet_sitters WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update
        $sql = "UPDATE pet_sitters SET 
                bio = ?, experience_years = ?, daily_rate = ?, phone = ?,
                pet_types = ?, services = ?, status = 'active'
                WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bio, $experience_years, $daily_rate, $phone, $pet_types, $services, $user_id]);
        $msg = $_SESSION['language'] == 'en' ? 'Profile updated!' : 'Profiliniz güncellendi!';
    } else {
        // Insert
        $sql = "INSERT INTO pet_sitters (user_id, bio, experience_years, daily_rate, phone, pet_types, services, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $bio, $experience_years, $daily_rate, $phone, $pet_types, $services]);
        
        // Create Community Post (Announcement)
        $post_content = $_SESSION['language'] == 'en' 
            ? "🐾 I've just joined Kalkan Social as a Pet Sitter! Check out my profile: /pet_sitting" 
            : "🐾 Kalkan Social'a Pet Bakıcısı olarak katıldım! Profilime göz atın: /pet_sitting";
        
        $post_sql = "INSERT INTO posts (user_id, content, media_type) VALUES (?, ?, 'text')";
        $post_stmt = $pdo->prepare($post_sql);
        $post_stmt->execute([$user_id, $post_content]);

        $msg = $_SESSION['language'] == 'en' ? 'Sitter profile created!' : 'Bakıcı profiliniz oluşturuldu!';
    }

    echo json_encode(['status' => 'success', 'message' => $msg]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
