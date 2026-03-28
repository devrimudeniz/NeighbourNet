<?php
require_once 'includes/bootstrap.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT id, username, full_name, avatar, bio, badge, created_at, status,
        (SELECT COUNT(*) FROM posts WHERE user_id = users.id AND deleted_at IS NULL) as post_count
        FROM users WHERE 1=1";
$params = [];

if ($filter !== 'all') {
    $sql .= " AND badge = ?";
    $params[] = $filter;
}

if ($search) {
    $sql .= " AND (username LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY FIELD(badge, 'founder', 'moderator', 'verified_business', 'captain', 'taxi', '') DESC, created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'founder' => $pdo->query("SELECT COUNT(*) FROM users WHERE badge = 'founder'")->fetchColumn(),
    'moderator' => $pdo->query("SELECT COUNT(*) FROM users WHERE badge = 'moderator'")->fetchColumn(),
    'verified_business' => $pdo->query("SELECT COUNT(*) FROM users WHERE badge = 'verified_business'")->fetchColumn(),
    'captain' => $pdo->query("SELECT COUNT(*) FROM users WHERE badge = 'captain'")->fetchColumn(),
    'taxi' => $pdo->query("SELECT COUNT(*) FROM users WHERE badge = 'taxi'")->fetchColumn(),
];

$badge_meta = [
    'founder'           => ['label' => 'Founder',     'icon' => 'fa-crown',        'color' => '#d97706', 'bg' => '#fffbeb'],
    'moderator'         => ['label' => 'Moderator',   'icon' => 'fa-shield-alt',   'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'verified_business' => ['label' => 'Verified',    'icon' => 'fa-check-circle', 'color' => '#2563eb', 'bg' => '#eff6ff'],
    'captain'           => ['label' => 'Captain',     'icon' => 'fa-anchor',       'color' => '#0284c7', 'bg' => '#f0f9ff'],
    'taxi'              => ['label' => 'Taxi',        'icon' => 'fa-car',          'color' => '#ca8a04', 'bg' => '#fefce8'],
    'expert'            => ['label' => 'Expert',      'icon' => 'fa-star',         'color' => '#059669', 'bg' => '#ecfdf5'],
];

function getBadgeMeta($badge, $meta) {
    return $meta[$badge] ?? ['label' => ucfirst($badge), 'icon' => 'fa-user', 'color' => '#64748b', 'bg' => '#f8fafc'];
}

// Online check helper
function isOnline($pdo, $user_id) {
    static $online_ids = null;
    if ($online_ids === null) {
        $stmt = $pdo->query("SELECT DISTINCT user_id FROM visitor_activity WHERE user_id IS NOT NULL AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $online_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return in_array($user_id, $online_ids);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Members' : 'Üyeler'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
        .member-row { transition: all 0.15s; }
        .member-row:hover { background: #f8fafc; }
        .dark .member-row:hover { background: rgba(30,41,59,0.4); }
        .filter-chip { transition: all 0.15s; }
        .filter-chip:hover { background: #f1f5f9; }
        .dark .filter-chip:hover { background: rgba(51,65,85,0.5); }
        .filter-chip.active { font-weight: 800; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <div class="max-w-2xl mx-auto px-4 pt-24 pb-8">
        
        <!-- Header -->
        <div style="margin-bottom:20px;">
            <h1 style="font-size:24px;font-weight:900;margin:0;"><?php echo $lang == 'en' ? 'Members' : 'Üyeler'; ?></h1>
            <p style="font-size:13px;color:#94a3b8;margin:4px 0 0;"><?php echo $counts['all']; ?> <?php echo $lang == 'en' ? 'people in our community' : 'kişi topluluğumuzda'; ?></p>
        </div>

        <!-- Search -->
        <form method="GET" style="margin-bottom:14px;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="<?php echo $lang == 'en' ? 'Search by name or username...' : 'İsim veya kullanıcı adı ara...'; ?>"
                    style="width:100%;padding:11px 14px 11px 38px;border-radius:12px;border:1px solid #e2e8f0;background:#fff;font-size:14px;outline:none;box-sizing:border-box;"
                    class="dark:bg-slate-800 dark:border-slate-700 dark:text-white">
            </div>
        </form>

        <!-- Filter Chips -->
        <div style="display:flex;gap:6px;margin-bottom:20px;overflow-x:auto;padding-bottom:2px;" class="hide-scrollbar">
            <a href="?filter=all<?php echo $search ? '&search='.urlencode($search) : ''; ?>"
               class="filter-chip <?php echo $filter === 'all' ? 'active' : ''; ?>"
               style="padding:7px 14px;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;flex-shrink:0;color:inherit;<?php echo $filter === 'all' ? 'background:#e2e8f0;' : ''; ?>">
                <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?>
                <span style="opacity:0.5;margin-left:2px;"><?php echo $counts['all']; ?></span>
            </a>
            <?php 
            $filter_items = [
                'founder' => ['emoji' => '👑', 'label' => $t['founder']],
                'moderator' => ['emoji' => '🛡️', 'label' => $t['moderator']],
                'verified_business' => ['emoji' => '✓', 'label' => $t['verified_business']],
                'captain' => ['emoji' => '⚓', 'label' => $t['captain']],
                'taxi' => ['emoji' => '🚕', 'label' => $t['taxi']],
            ];
            foreach($filter_items as $fk => $fi): 
                if ($counts[$fk] == 0) continue;
                $bm = getBadgeMeta($fk, $badge_meta);
            ?>
            <a href="?filter=<?php echo $fk; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
               class="filter-chip <?php echo $filter === $fk ? 'active' : ''; ?>"
               style="padding:7px 14px;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:4px;color:inherit;<?php echo $filter === $fk ? 'background:'.$bm['bg'].';color:'.$bm['color'].';' : ''; ?>">
                <?php echo $fi['emoji']; ?> <?php echo $fi['label']; ?>
                <span style="opacity:0.5;"><?php echo $counts[$fk]; ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Members List -->
        <?php if (count($members) > 0): ?>
        <div style="display:flex;flex-direction:column;gap:2px;">
            <?php foreach ($members as $member): 
                $is_banned = ($member['status'] ?? 'active') === 'banned';
                $online = !$is_banned && isOnline($pdo, $member['id']);
                $bm = getBadgeMeta($member['badge'] ?? '', $badge_meta);
                $avatar = $member['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($member['full_name']) . '&background=e2e8f0&color=64748b&bold=true';
            ?>
            <a href="profile?id=<?php echo $member['id']; ?>" class="member-row" style="display:flex;align-items:center;gap:14px;padding:12px;border-radius:14px;text-decoration:none;color:inherit;">
                
                <!-- Avatar -->
                <div style="position:relative;flex-shrink:0;">
                    <img src="<?php echo htmlspecialchars($avatar); ?>" 
                         style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;<?php echo $is_banned ? 'filter:grayscale(1);opacity:0.4;' : ''; ?>" 
                         loading="lazy" alt="">
                    <?php if($online): ?>
                    <div style="position:absolute;bottom:1px;right:1px;width:12px;height:12px;border-radius:50%;background:#10b981;border:2px solid #fff;"></div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:1px;">
                        <span style="font-size:15px;font-weight:700;<?php echo $is_banned ? 'text-decoration:line-through;color:#94a3b8;' : ''; ?>" class="dark:text-white"><?php echo htmlspecialchars($member['full_name']); ?></span>
                        <?php if($is_banned): ?>
                        <span style="padding:1px 6px;border-radius:4px;font-size:9px;font-weight:800;background:#0f172a;color:#fff;">BANNED</span>
                        <?php elseif(!empty($member['badge'])): ?>
                        <span style="padding:1px 6px;border-radius:6px;font-size:10px;font-weight:700;background:<?php echo $bm['bg']; ?>;color:<?php echo $bm['color']; ?>;">
                            <i class="fas <?php echo $bm['icon']; ?>" style="font-size:8px;margin-right:2px;"></i><?php echo $bm['label']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:12px;color:#94a3b8;">
                        @<?php echo htmlspecialchars($member['username']); ?>
                        <?php if($member['bio']): ?>
                        <span style="margin:0 4px;">&middot;</span>
                        <span style="color:#64748b;"><?php echo htmlspecialchars(mb_strimwidth($member['bio'], 0, 60, '...')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right info -->
                <div style="flex-shrink:0;text-align:right;">
                    <div style="font-size:11px;color:#cbd5e1;font-weight:600;">
                        <?php echo date('M Y', strtotime($member['created_at'])); ?>
                    </div>
                    <?php if((int)$member['post_count'] > 0): ?>
                    <div style="font-size:10px;color:#94a3b8;margin-top:2px;">
                        <?php echo $member['post_count']; ?> <?php echo $lang == 'en' ? 'posts' : 'gönderi'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:60px 20px;">
            <div style="width:64px;height:64px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-users-slash" style="font-size:24px;color:#cbd5e1;"></i>
            </div>
            <h3 style="font-weight:800;color:#94a3b8;font-size:16px;margin:0 0 4px;">
                <?php echo $lang == 'en' ? 'No members found' : 'Üye bulunamadı'; ?>
            </h3>
            <p style="font-size:13px;color:#cbd5e1;">
                <?php echo $lang == 'en' ? 'Try a different search or filter.' : 'Farklı bir arama veya filtre deneyin.'; ?>
            </p>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
