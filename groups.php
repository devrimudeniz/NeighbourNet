<?php
require_once 'includes/bootstrap.php';

$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$name_field = ($lang === 'tr') ? 'name_tr' : 'name_en';
$desc_field = ($lang === 'tr') ? 'description_tr' : 'description_en';

$sql = "SELECT g.*, g.`$name_field` as name, g.`$desc_field` as description, u.username as creator_username, u.full_name as creator_name
        FROM `groups` g 
        JOIN users u ON g.creator_id = u.id 
        WHERE g.status = 'approved'";
$params = [];

if ($category !== 'all') {
    $sql .= " AND g.category = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (g.name_tr LIKE ? OR g.name_en LIKE ? OR g.description_tr LIKE ? OR g.description_en LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY g.member_count DESC, g.created_at DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$groups = $stmt->fetchAll();

$counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM `groups` WHERE status = 'approved'")->fetchColumn(),
    'hobby' => $pdo->query("SELECT COUNT(*) FROM `groups` WHERE category = 'hobby' AND status = 'approved'")->fetchColumn(),
    'lifestyle' => $pdo->query("SELECT COUNT(*) FROM `groups` WHERE category = 'lifestyle' AND status = 'approved'")->fetchColumn(),
    'professional' => $pdo->query("SELECT COUNT(*) FROM `groups` WHERE category = 'professional' AND status = 'approved'")->fetchColumn(),
    'marketplace' => $pdo->query("SELECT COUNT(*) FROM `groups` WHERE category = 'marketplace' AND status = 'approved'")->fetchColumn(),
];

$user_groups = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT group_id FROM group_members WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_groups = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$cat_meta = [
    'hobby'        => ['icon' => 'fa-bullseye',      'color' => '#3b82f6', 'bg' => '#eff6ff',  'emoji' => '🎯'],
    'lifestyle'    => ['icon' => 'fa-sun',            'color' => '#f59e0b', 'bg' => '#fffbeb',  'emoji' => '🌟'],
    'professional' => ['icon' => 'fa-briefcase',      'color' => '#8b5cf6', 'bg' => '#f5f3ff',  'emoji' => '💼'],
    'marketplace'  => ['icon' => 'fa-shopping-bag',   'color' => '#ef4444', 'bg' => '#fef2f2',  'emoji' => '🛒'],
];

// Dark mode colors
$dk = defined('CURRENT_THEME') && CURRENT_THEME == 'dark';
$c_bg      = $dk ? '#1e293b' : '#fff';
$c_border  = $dk ? '#334155' : '#e2e8f0';
$c_title   = $dk ? '#f1f5f9' : '#0f172a';
$c_muted   = '#94a3b8';
$c_surface = $dk ? '#0f172a' : '#f1f5f9';
$c_input   = $dk ? '#0f172a' : '#fff';
$c_btn_bg  = $dk ? '#e2e8f0' : '#0f172a';
$c_btn_txt = $dk ? '#0f172a' : '#fff';
$c_joined_bg  = $dk ? '#334155' : '#f1f5f9';
$c_joined_brd = $dk ? '#475569' : '#e2e8f0';
$c_joined_txt = $dk ? '#94a3b8' : '#64748b';
$c_tab_active = $dk ? '#334155' : '#e2e8f0';

// Dark mode category backgrounds (subtle/transparent)
if ($dk) {
    $cat_meta['hobby']['bg']        = 'rgba(59,130,246,0.12)';
    $cat_meta['lifestyle']['bg']    = 'rgba(245,158,11,0.12)';
    $cat_meta['professional']['bg'] = 'rgba(139,92,246,0.12)';
    $cat_meta['marketplace']['bg']  = 'rgba(239,68,68,0.12)';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['groups']; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
        .group-card { transition: transform 0.15s, box-shadow 0.15s; }
        .group-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.08); }
        .dark .group-card:hover { box-shadow: 0 12px 32px rgba(0,0,0,0.25); }
        .cat-tab { transition: all 0.15s; }
        .cat-tab:hover { background: #f1f5f9; }
        .dark .cat-tab:hover { background: rgba(51,65,85,0.5); }
        .cat-tab.active { font-weight: 800; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <div class="max-w-4xl mx-auto px-4 pt-24 pb-24">
        
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;">
            <div>
                <h1 style="font-size:24px;font-weight:900;margin:0;color:<?php echo $c_title; ?>;"><?php echo $lang == 'en' ? 'Groups' : 'Gruplar'; ?></h1>
                <p style="font-size:13px;color:<?php echo $c_muted; ?>;margin:4px 0 0;"><?php echo $counts['all']; ?> <?php echo $lang == 'en' ? 'communities' : 'topluluk'; ?></p>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="create_group" style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:<?php echo $c_btn_bg; ?>;color:<?php echo $c_btn_txt; ?>;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
                <i class="fas fa-plus" style="font-size:11px;"></i> <?php echo $lang == 'en' ? 'Create' : 'Oluştur'; ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Search -->
        <form method="GET" style="margin-bottom:16px;">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                    placeholder="<?php echo $lang == 'en' ? 'Search groups...' : 'Grup ara...'; ?>"
                    style="width:100%;padding:12px 14px 12px 38px;border-radius:12px;border:1px solid <?php echo $c_border; ?>;background:<?php echo $c_input; ?>;font-size:14px;outline:none;box-sizing:border-box;color:<?php echo $c_title; ?>;">
            </div>
        </form>

        <!-- Category Tabs -->
        <div style="display:flex;gap:6px;margin-bottom:24px;overflow-x:auto;padding-bottom:2px;" class="hide-scrollbar">
            <a href="?category=all<?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
               class="cat-tab <?php echo $category == 'all' ? 'active' : ''; ?>"
               style="padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;flex-shrink:0;color:inherit;<?php echo $category == 'all' ? 'background:'.$c_tab_active.';' : ''; ?>">
                <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?>
                <span style="opacity:0.5;margin-left:2px;"><?php echo $counts['all']; ?></span>
            </a>
            <?php foreach($cat_meta as $ck => $cm): ?>
            <a href="?category=<?php echo $ck; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
               class="cat-tab <?php echo $category == $ck ? 'active' : ''; ?>"
               style="padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:5px;color:inherit;<?php echo $category == $ck ? 'background:'.$cm['bg'].';color:'.$cm['color'].';' : ''; ?>">
                <?php echo $cm['emoji']; ?> <?php echo $t[$ck]; ?>
                <span style="opacity:0.5;"><?php echo $counts[$ck]; ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Groups List -->
        <?php if (count($groups) > 0): ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($groups as $group): 
                $is_member = in_array($group['id'], $user_groups);
                $cm = $cat_meta[$group['category']] ?? ['icon' => 'fa-users', 'color' => '#64748b', 'bg' => '#f8fafc', 'emoji' => '👥'];
            ?>
            <div class="group-card" style="display:flex;align-items:center;gap:14px;padding:16px;background:<?php echo $c_bg; ?>;border:1px solid <?php echo $c_border; ?>;border-radius:16px;" >
                
                <!-- Group Icon / Cover -->
                <a href="group_detail?id=<?php echo $group['id']; ?>" style="flex-shrink:0;">
                    <?php if ($group['cover_photo']): ?>
                    <img src="<?php echo htmlspecialchars($group['cover_photo']); ?>" style="width:56px;height:56px;border-radius:14px;object-fit:cover;" loading="lazy" alt="">
                    <?php else: ?>
                    <div style="width:56px;height:56px;border-radius:14px;background:<?php echo $cm['bg']; ?>;display:flex;align-items:center;justify-content:center;font-size:24px;">
                        <?php echo $cm['emoji']; ?>
                    </div>
                    <?php endif; ?>
                </a>

                <!-- Info -->
                <div style="flex:1;min-width:0;">
                    <a href="group_detail?id=<?php echo $group['id']; ?>" style="text-decoration:none;color:inherit;">
                        <h3 style="font-size:15px;font-weight:800;margin:0 0 2px;color:<?php echo $c_title; ?>;"><?php echo htmlspecialchars($group['name']); ?></h3>
                    </a>
                    <?php if ($group['description']): ?>
                    <p style="font-size:12px;color:#94a3b8;margin:0 0 6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($group['description']); ?></p>
                    <?php endif; ?>
                    <div style="display:flex;align-items:center;gap:10px;font-size:11px;color:#94a3b8;font-weight:600;">
                        <span><i class="fas fa-users" style="margin-right:3px;"></i><?php echo $group['member_count']; ?></span>
                        <span><i class="fas fa-comment" style="margin-right:3px;"></i><?php echo $group['post_count']; ?></span>
                        <span style="padding:2px 8px;border-radius:6px;background:<?php echo $cm['bg']; ?>;color:<?php echo $cm['color']; ?>;font-weight:700;"><?php echo $t[$group['category']]; ?></span>
                    </div>
                </div>

                <!-- Action -->
                <div style="flex-shrink:0;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($is_member): ?>
                        <button onclick="leaveGroup(<?php echo $group['id']; ?>)" style="padding:8px 16px;border-radius:10px;background:<?php echo $c_joined_bg; ?>;border:1px solid <?php echo $c_joined_brd; ?>;color:<?php echo $c_joined_txt; ?>;font-size:12px;font-weight:700;cursor:pointer;">
                            <?php echo $lang == 'en' ? 'Joined' : 'Katıldın'; ?>
                        </button>
                        <?php else: ?>
                        <button onclick="joinGroup(<?php echo $group['id']; ?>)" style="padding:8px 16px;border-radius:10px;background:<?php echo $c_btn_bg; ?>;border:none;color:<?php echo $c_btn_txt; ?>;font-size:12px;font-weight:700;cursor:pointer;">
                            <?php echo $lang == 'en' ? 'Join' : 'Katıl'; ?>
                        </button>
                        <?php endif; ?>
                    <?php else: ?>
                    <a href="login" style="padding:8px 16px;border-radius:10px;background:<?php echo $c_btn_bg; ?>;color:<?php echo $c_btn_txt; ?>;font-size:12px;font-weight:700;text-decoration:none;display:inline-block;">
                        <?php echo $lang == 'en' ? 'Join' : 'Katıl'; ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:60px 20px;">
            <div style="width:64px;height:64px;border-radius:50%;background:<?php echo $c_surface; ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;">
                👥
            </div>
            <h3 style="font-weight:800;color:<?php echo $c_muted; ?>;font-size:16px;margin:0 0 4px;">
                <?php echo $lang == 'en' ? 'No groups found' : 'Grup bulunamadı'; ?>
            </h3>
            <p style="font-size:13px;color:<?php echo $dk ? '#475569' : '#cbd5e1'; ?>;">
                <?php echo $lang == 'en' ? 'Try a different search or category.' : 'Farklı bir arama veya kategori deneyin.'; ?>
            </p>
        </div>
        <?php endif; ?>

    </div>

    <script>
    function joinGroup(groupId) {
        fetch('api/join_group.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=join&group_id=' + groupId
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message || 'Error');
            }
        });
    }

    function leaveGroup(groupId) {
        if (confirm('<?php echo $lang == 'en' ? 'Leave this group?' : 'Bu gruptan ayrılmak istediğinize emin misiniz?'; ?>')) {
            fetch('api/join_group.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=leave&group_id=' + groupId
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message || 'Error');
                }
            });
        }
    }
    </script>
</body>
</html>
