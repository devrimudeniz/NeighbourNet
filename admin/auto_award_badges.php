<?php
require_once 'auth_session.php';
require_once '../includes/db.php';

$results = [];

// 1. AWARD FOODIE (GURME) 
// Criteria: > 5 Business Reviews
$sql_foodie = "
    INSERT IGNORE INTO user_badges (user_id, badge_type)
    SELECT user_id, 'foodie' FROM business_reviews 
    GROUP BY user_id 
    HAVING COUNT(*) > 5
";
$stmt = $pdo->prepare($sql_foodie);
$stmt->execute();
$results[] = "🍔 Gurme (Foodie) rozeti " . $stmt->rowCount() . " kişiye verildi.";

// 2. AWARD PLACE SCOUT (MEKAN AVCISI)
// Criteria: 10+ reviews to DIFFERENT businesses
$sql_place_scout = "
    INSERT IGNORE INTO user_badges (user_id, badge_type)
    SELECT user_id, 'place_scout' FROM business_reviews 
    GROUP BY user_id 
    HAVING COUNT(DISTINCT business_id) >= 10
";
$stmt = $pdo->prepare($sql_place_scout);
$stmt->execute();
$results[] = "🔍 Mekan Avcısı (Place Scout) rozeti " . $stmt->rowCount() . " kişiye verildi.";

// 3. AWARD PHOTOGRAPHER (FOTOĞRAFÇI)
// Criteria: > 5 Posts with Images
$sql_photo = "
    INSERT IGNORE INTO user_badges (user_id, badge_type)
    SELECT user_id, 'photographer' FROM (
        SELECT user_id FROM posts WHERE content LIKE '%img%' OR content LIKE '%upload%' -- Simplified check, ideally checks for image column if exists
        UNION ALL
        SELECT user_id FROM group_posts WHERE image IS NOT NULL AND image != ''
    ) as source
    GROUP BY user_id 
    HAVING COUNT(*) > 5
";
// Note: Since 'posts' table doesn't have image column in original schema (only group_posts has), 
// we'll rely on group_posts for now or add image column to posts. 
// Let's assume group_posts for "Photographer" for now to be safe.
$sql_photo_safe = "
    INSERT IGNORE INTO user_badges (user_id, badge_type)
    SELECT user_id, 'photographer' FROM group_posts 
    WHERE image IS NOT NULL AND image != ''
    GROUP BY user_id 
    HAVING COUNT(*) >= 3
";
$stmt = $pdo->prepare($sql_photo_safe);
$stmt->execute();
$results[] = "📸 Fotoğrafçı (Photographer) rozeti " . $stmt->rowCount() . " kişiye verildi.";

// 4. AWARD LOCAL EXPERT (YEREL UZMAN)
// Criteria: Active for > 30 Days AND > 10 Posts
$sql_local = "
    INSERT IGNORE INTO user_badges (user_id, badge_type)
    SELECT u.id, 'local' FROM users u
    JOIN posts p ON u.id = p.user_id
    WHERE u.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY u.id
    HAVING COUNT(p.id) > 10
";
$stmt = $pdo->prepare($sql_local);
$stmt->execute();
$results[] = "🤝 Yerel Uzman (Local Expert) rozeti " . $stmt->rowCount() . " kişiye verildi.";

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Otomatik Rozet Dağıtımı</title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-slate-800 p-8 rounded-3xl border border-slate-700 shadow-2xl text-center">
        <div class="w-20 h-20 bg-gradient-to-tr from-green-400 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-500/30">
            <i class="fas fa-magic text-3xl text-white"></i>
        </div>
        <h2 class="text-2xl font-bold mb-6">Otomatik Dağıtım Tamamlandı</h2>
        <div class="space-y-4 text-left bg-slate-900/50 p-6 rounded-xl border border-slate-700 mb-6">
            <?php foreach($results as $res): ?>
                <p class="flex items-center gap-3 text-slate-300">
                    <i class="fas fa-check-circle text-green-500"></i> <?php echo $res; ?>
                </p>
            <?php endforeach; ?>
        </div>
        <a href="badges" class="block w-full bg-slate-700 hover:bg-slate-600 text-white py-3 rounded-xl font-bold transition-colors">
            Yönetim Paneline Dön
        </a>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>
