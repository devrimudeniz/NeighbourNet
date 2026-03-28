<?php
require_once '../includes/db.php';
require_once 'auth_session.php';

// Handle Badge Add/Remove
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_POST['user_id'];
    $badge = $_POST['badge'];
    $action = $_POST['action'];

    if ($action == 'add') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_type) VALUES (?, ?)");
        $stmt->execute([$user_id, $badge]);
    } elseif ($action == 'remove') {
        $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ? AND badge_type = ?");
        $stmt->execute([$user_id, $badge]);
    }
    header("Location: badges");
    exit();
}

// Search
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search) {
    $where = "WHERE u.username LIKE ? OR u.full_name LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$sql = "SELECT u.id, u.username, u.full_name, u.avatar, 
        GROUP_CONCAT(ub.badge_type) as badges 
        FROM users u 
        LEFT JOIN user_badges ub ON u.id = ub.user_id 
        $where
        GROUP BY u.id 
        ORDER BY u.id DESC
        LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$available_badges = [
    'foodie' => 'Gurme',
    'place_scout' => 'Mekan Avcısı',
    'explorer' => 'Kaşif',
    'photographer' => 'Fotoğrafçı',
    'historian' => 'Tarihçi',
    'local' => 'Yerel Uzman'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rozet Yönetimi | Admin</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
            <div>
                <h1 class="page-title">Rozet Yönetimi</h1>
                <p class="page-subtitle">Kullanıcılara uzman rozetleri ekleyin veya kaldırın.</p>
            </div>
            <a href="auto_award_badges" class="btn btn-primary"><i class="fas fa-magic"></i> Otomatik Dağıt</a>
        </div>

        <!-- Search -->
        <form method="GET" style="margin-bottom:20px;">
            <div style="display:flex;gap:8px;max-width:400px;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kullanıcı ara..." class="admin-input" style="flex:1;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <!-- Table -->
        <div class="admin-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kullanıcı</th>
                            <th>Mevcut Rozetler</th>
                            <th style="width:280px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): 
                            $user_badges = !empty($user['badges']) ? explode(',', $user['badges']) : [];
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?php echo htmlspecialchars($user['avatar'] ?? ''); ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover;background:#f1f5f9;" alt="" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=e2e8f0&color=64748b'">
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:#0f172a;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div style="font-size:11px;color:#94a3b8;">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                    <?php if(empty($user_badges)): ?>
                                    <span style="font-size:12px;color:#94a3b8;font-style:italic;">Rozet yok</span>
                                    <?php else: ?>
                                        <?php foreach($user_badges as $ub): ?>
                                        <span style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;color:#166534;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;">
                                            <?php echo $available_badges[$ub] ?? $ub; ?>
                                            <form method="POST" style="display:inline;margin:0;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="badge" value="<?php echo $ub; ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <button type="submit" style="background:none;border:none;cursor:pointer;color:#ef4444;font-size:10px;padding:0;margin:0;" title="Kaldır"><i class="fas fa-times"></i></button>
                                            </form>
                                        </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <form method="POST" style="display:flex;align-items:center;gap:6px;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <select name="badge" class="admin-input" style="width:auto;padding:6px 10px;font-size:12px;">
                                        <?php foreach($available_badges as $key => $label): ?>
                                            <?php if(!in_array($key, $user_badges)): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-success btn-sm">Ekle</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
