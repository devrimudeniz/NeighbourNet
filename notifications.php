<?php
require_once 'includes/bootstrap.php';
require_once 'includes/ui_components.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
$filter = $_GET['f'] ?? 'all';

// ── Friend Requests ──
$requests_stmt = $pdo->prepare("
    SELECT f.id, f.requester_id, f.created_at, u.username, u.full_name, u.avatar, u.badge
    FROM friendships f
    JOIN users u ON f.requester_id = u.id
    WHERE f.receiver_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
");
$requests_stmt->execute([$user_id]);
$friend_requests = $requests_stmt->fetchAll();

// ── Notifications ──
$notif_sql = "SELECT n.*, u.username, u.full_name, u.avatar, u.badge
              FROM notifications n
              LEFT JOIN users u ON n.actor_id = u.id
              WHERE n.user_id = ?";
$params = [$user_id];

if ($filter !== 'all') {
    $allowed_filters = ['like', 'comment', 'reaction', 'follow', 'friend_request', 'in_town', 'mention'];
    if (in_array($filter, $allowed_filters)) {
        $notif_sql .= " AND n.type = ?";
        $params[] = $filter;
    } elseif ($filter === 'unread') {
        $notif_sql .= " AND n.is_read = 0";
    }
}

$notif_sql .= " ORDER BY n.created_at DESC LIMIT 150";
$notif_stmt = $pdo->prepare($notif_sql);
$notif_stmt->execute($params);
$raw_notifications = $notif_stmt->fetchAll();

// Unread count
$unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_stmt->execute([$user_id]);
$unread_count = (int)$unread_stmt->fetchColumn();

// Type counts for filter badges
$type_counts = [];
$tc_stmt = $pdo->prepare("SELECT type, COUNT(*) as cnt FROM notifications WHERE user_id = ? GROUP BY type");
$tc_stmt->execute([$user_id]);
while ($row = $tc_stmt->fetch()) {
    $type_counts[$row['type']] = (int)$row['cnt'];
}
$total_notifs = array_sum($type_counts);

// Mark filtered as read (only current view)
if ($filter === 'all') {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);
} elseif ($filter === 'unread') {
    // Don't mark unread filter as read
} else {
    $mark = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = ? AND is_read = 0");
    $mark->execute([$user_id, $filter]);
}

// ── Group Notifications ──
$grouped_notifications = [];
$today = new DateTime();
$yesterday = (new DateTime())->modify('-1 day');

foreach ($raw_notifications as $n) {
    if (!$n['actor_id']) continue;

    $date = new DateTime($n['created_at']);
    $diff = $today->diff($date);
    $days = $diff->days;
    
    if ($date->format('Y-m-d') == $today->format('Y-m-d')) {
        $period = $lang == 'en' ? 'Today' : 'Bugün';
        $priority = 1;
    } elseif ($date->format('Y-m-d') == $yesterday->format('Y-m-d')) {
        $period = $lang == 'en' ? 'Yesterday' : 'Dün';
        $priority = 2;
    } elseif ($days < 7) {
        $period = $lang == 'en' ? 'This Week' : 'Bu Hafta';
        $priority = 3;
    } elseif ($days < 30) {
        $period = $lang == 'en' ? 'This Month' : 'Bu Ay';
        $priority = 4;
    } else {
        $period = $lang == 'en' ? 'Earlier' : 'Daha Eski';
        $priority = 5;
    }

    $url = $n['url'] ?? ($n['link'] ?? '#');
    $content = $n['content'] ?? ($n['message'] ?? '');
    $group_key = $priority . '_' . $period . '_' . $n['type'] . '_' . md5($url);
    
    if (!in_array($n['type'], ['like', 'comment', 'reaction'])) {
        $group_key .= '_' . $n['id'];
    }

    if (!isset($grouped_notifications[$group_key])) {
        $grouped_notifications[$group_key] = [
            'period' => $period,
            'priority' => $priority,
            'type' => $n['type'],
            'link' => $url,
            'message' => $content,
            'actors' => [],
            'ids' => [],
            'count' => 0,
            'latest_at' => $n['created_at'],
            'is_read' => $n['is_read'],
            'main_actor' => [
                'username' => $n['username'],
                'full_name' => $n['full_name'],
                'avatar' => $n['avatar']
            ]
        ];
    }
    
    if (count($grouped_notifications[$group_key]['actors']) < 3) {
        $grouped_notifications[$group_key]['actors'][] = $n['avatar'];
    }
    $grouped_notifications[$group_key]['ids'][] = $n['id'];
    $grouped_notifications[$group_key]['count']++;
}

// Helper
function getNotificationText($group, $lang) {
    if (!empty($group['message'])) return $group['message'];

    $count = $group['count'];
    $actorName = explode(' ', $group['main_actor']['full_name'])[0];
    
    if ($group['type'] == 'like' || $group['type'] == 'reaction') {
        if ($count > 1) {
            $others = $count - 1;
            return $lang == 'en' 
                ? "<strong>$actorName</strong> and <strong>$others others</strong> reacted to your post." 
                : "<strong>$actorName</strong> ve <strong>$others diğer kişi</strong> gönderine tepki verdi.";
        }
        return $lang == 'en' 
            ? "<strong>{$group['main_actor']['full_name']}</strong> reacted to your post." 
            : "<strong>{$group['main_actor']['full_name']}</strong> gönderine tepki verdi.";
    } elseif ($group['type'] == 'comment') {
        if ($count > 1) {
            $others = $count - 1;
            return $lang == 'en' 
                ? "<strong>$actorName</strong> and <strong>$others others</strong> commented on your post." 
                : "<strong>$actorName</strong> ve <strong>$others diğer kişi</strong> gönderine yorum yaptı.";
        }
        return $lang == 'en' 
            ? "<strong>{$group['main_actor']['full_name']}</strong> commented on your post." 
            : "<strong>{$group['main_actor']['full_name']}</strong> gönderine yorum yaptı.";
    } elseif ($group['type'] == 'follow') {
        return $lang == 'en' 
            ? "<strong>{$group['main_actor']['full_name']}</strong> started following you."
            : "<strong>{$group['main_actor']['full_name']}</strong> seni takip etmeye başladı.";
    } elseif ($group['type'] == 'mention') {
        return $lang == 'en'
            ? "<strong>{$group['main_actor']['full_name']}</strong> mentioned you in a post."
            : "<strong>{$group['main_actor']['full_name']}</strong> bir gönderide senden bahsetti.";
    }
    
    return $group['message'];
}

// Notification type config
$type_config = [
    'like' => ['icon' => 'fa-heart', 'color' => '#ec4899', 'bg' => '#fdf2f8', 'label' => $lang == 'en' ? 'Likes' : 'Beğeniler'],
    'reaction' => ['icon' => 'fa-heart', 'color' => '#ec4899', 'bg' => '#fdf2f8', 'label' => $lang == 'en' ? 'Reactions' : 'Tepkiler'],
    'comment' => ['icon' => 'fa-comment', 'color' => '#3b82f6', 'bg' => '#eff6ff', 'label' => $lang == 'en' ? 'Comments' : 'Yorumlar'],
    'follow' => ['icon' => 'fa-user-plus', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'label' => $lang == 'en' ? 'Follows' : 'Takipler'],
    'friend_request' => ['icon' => 'fa-user-check', 'color' => '#10b981', 'bg' => '#ecfdf5', 'label' => $lang == 'en' ? 'Friend Requests' : 'Arkadaşlık'],
    'in_town' => ['icon' => 'fa-map-marker-alt', 'color' => '#f97316', 'bg' => '#fff7ed', 'label' => $lang == 'en' ? 'In Town' : 'Şehirde'],
    'mention' => ['icon' => 'fa-at', 'color' => '#06b6d4', 'bg' => '#ecfeff', 'label' => $lang == 'en' ? 'Mentions' : 'Bahsetmeler'],
];

function getTypeConf($type, $config) {
    return $config[$type] ?? ['icon' => 'fa-bell', 'color' => '#64748b', 'bg' => '#f8fafc', 'label' => $type];
}

function timeAgo($datetime, $lang) {
    $now = new DateTime();
    $dt = new DateTime($datetime);
    $diff = $now->getTimestamp() - $dt->getTimestamp();
    
    if ($diff < 60) return $lang == 'en' ? 'just now' : 'az önce';
    if ($diff < 3600) return floor($diff / 60) . ($lang == 'en' ? 'm ago' : 'dk önce');
    if ($diff < 86400) return floor($diff / 3600) . ($lang == 'en' ? 'h ago' : 'sa önce');
    if ($diff < 604800) return floor($diff / 86400) . ($lang == 'en' ? 'd ago' : 'g önce');
    return $dt->format('d M');
}

$page_title = $lang == 'en' ? 'Notifications' : 'Bildirimler';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', 'Inter', system-ui, sans-serif; }
        .notif-item { transition: all 0.2s; }
        .notif-item:hover { background: #f8fafc; }
        .dark .notif-item:hover { background: rgba(30,41,59,0.5); }
        .notif-item.removing { opacity: 0; transform: translateX(80px); height: 0; padding: 0; margin: 0; overflow: hidden; }
        .filter-tab { transition: all 0.15s; }
        .filter-tab:hover { background: #f1f5f9; }
        .dark .filter-tab:hover { background: rgba(51,65,85,0.5); }
        .filter-tab.active { background: #e2e8f0; color: #0f172a; font-weight: 800; }
        .dark .filter-tab.active { background: #334155; color: #f1f5f9; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <div class="max-w-2xl mx-auto px-4 pt-24 pb-8">
        
        <!-- Page Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;">
            <div>
                <h1 style="font-size:24px;font-weight:900;margin:0;"><?php echo $page_title; ?></h1>
                <?php if($unread_count > 0): ?>
                <p style="font-size:13px;color:#64748b;margin:4px 0 0;"><?php echo $unread_count; ?> <?php echo $lang == 'en' ? 'unread' : 'okunmamış'; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Actions Menu -->
            <div style="display:flex;gap:8px;">
                <?php if($total_notifs > 0): ?>
                <div style="position:relative;" id="actions-menu-wrap">
                    <button onclick="document.getElementById('actions-dropdown').classList.toggle('hidden')" style="width:36px;height:36px;border-radius:10px;background:#f1f5f9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:14px;" title="İşlemler">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div id="actions-dropdown" class="hidden dark:bg-slate-800 dark:border-slate-700" style="position:absolute;right:0;top:42px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.12);min-width:220px;z-index:50;overflow:hidden;">
                        <button onclick="markAllRead()" class="dark:text-slate-300 dark:hover:bg-slate-700" style="width:100%;padding:12px 16px;border:none;background:none;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;color:#334155;text-align:left;" onmouseover="if(!document.documentElement.classList.contains('dark'))this.style.background='#f8fafc'" onmouseout="this.style.background='none'">
                            <i class="fas fa-check-double" style="width:16px;color:#3b82f6;"></i> <?php echo $lang == 'en' ? 'Mark all as read' : 'Tümünü okundu işaretle'; ?>
                        </button>
                        <div style="border-top:1px solid #f1f5f9;" class="dark:border-slate-700"></div>
                        <button onclick="if(confirm('<?php echo $lang == 'en' ? 'Delete all notifications?' : 'Tüm bildirimleri silmek istediğinize emin misiniz?'; ?>')) deleteAll()" style="width:100%;padding:12px 16px;border:none;background:none;cursor:pointer;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;color:#ef4444;text-align:left;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">
                            <i class="fas fa-trash-alt" style="width:16px;"></i> <?php echo $lang == 'en' ? 'Delete all' : 'Tümünü sil'; ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div style="display:flex;gap:6px;margin-bottom:20px;overflow-x:auto;padding-bottom:4px;" class="hide-scrollbar">
            <a href="?f=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>" style="padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;color:inherit;">
                <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?>
                <span style="opacity:0.6;margin-left:2px;">(<?php echo $total_notifs; ?>)</span>
            </a>
            <?php if($unread_count > 0): ?>
            <a href="?f=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>" style="padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;color:inherit;">
                <?php echo $lang == 'en' ? 'Unread' : 'Okunmamış'; ?>
                <span style="background:#ef4444;color:#fff;font-size:10px;padding:1px 6px;border-radius:6px;margin-left:4px;"><?php echo $unread_count; ?></span>
            </a>
            <?php endif; ?>
            <?php foreach($type_config as $tk => $tc): 
                if (!isset($type_counts[$tk]) || $type_counts[$tk] == 0) continue;
            ?>
            <a href="?f=<?php echo $tk; ?>" class="filter-tab <?php echo $filter === $tk ? 'active' : ''; ?>" style="padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;display:flex;align-items:center;gap:5px;color:inherit;">
                <i class="fas <?php echo $tc['icon']; ?>" style="font-size:10px;color:<?php echo $filter === $tk ? 'inherit' : $tc['color']; ?>;"></i>
                <?php echo $tc['label']; ?>
                <span style="opacity:0.6;">(<?php echo $type_counts[$tk]; ?>)</span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Friend Requests -->
        <?php if(count($friend_requests) > 0 && ($filter === 'all' || $filter === 'friend_request')): ?>
        <div style="margin-bottom:24px;">
            <h2 style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin:0 0 10px 4px;"><?php echo $lang == 'en' ? 'Friend Requests' : 'Arkadaşlık İstekleri'; ?></h2>
            <?php foreach($friend_requests as $req): ?>
            <div id="freq-<?php echo $req['requester_id']; ?>" style="display:flex;align-items:center;gap:12px;padding:14px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:8px;" class="dark:bg-slate-800 dark:border-slate-700">
                <a href="profile?uid=<?php echo $req['requester_id']; ?>">
                    <img src="<?php echo htmlspecialchars($req['avatar']); ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;" alt="">
                </a>
                <div style="flex:1;min-width:0;">
                    <a href="profile?uid=<?php echo $req['requester_id']; ?>" style="font-weight:700;font-size:14px;color:#0f172a;text-decoration:none;" class="dark:text-white"><?php echo htmlspecialchars($req['full_name']); ?></a>
                    <div style="font-size:12px;color:#94a3b8;">@<?php echo htmlspecialchars($req['username']); ?> &middot; <?php echo timeAgo($req['created_at'], $lang); ?></div>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <button onclick="respondToRequest(<?php echo $req['requester_id']; ?>, 'accept', this)" style="padding:8px 16px;border-radius:10px;background:#0f172a;color:#fff;border:none;font-size:12px;font-weight:700;cursor:pointer;"><?php echo $lang == 'en' ? 'Accept' : 'Kabul Et'; ?></button>
                    <button onclick="respondToRequest(<?php echo $req['requester_id']; ?>, 'decline', this)" style="padding:8px 16px;border-radius:10px;background:#f1f5f9;color:#64748b;border:none;font-size:12px;font-weight:700;cursor:pointer;"><?php echo $lang == 'en' ? 'Delete' : 'Sil'; ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Notifications List -->
        <?php 
        $current_period = '';
        $notif_count = 0;
        foreach ($grouped_notifications as $gkey => $group): 
            $notif_count++;
        ?>
            <?php if ($current_period != $group['period']): $current_period = $group['period']; ?>
            <h2 style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin:20px 0 8px 4px;position:sticky;top:70px;background:rgba(248,250,252,0.95);padding:6px 0;z-index:5;" class="dark:bg-slate-900/95"><?php echo $current_period; ?></h2>
            <?php endif; ?>
            
            <?php 
                $href = ($group['link'] && $group['link'] !== 'nolink') ? htmlspecialchars($group['link']) : '#';
                $tc = getTypeConf($group['type'], $type_config);
                $ids_json = htmlspecialchars(json_encode($group['ids']));
            ?>
            
            <div class="notif-item" id="notif-<?php echo $gkey; ?>" style="display:flex;align-items:flex-start;gap:12px;padding:12px;border-radius:14px;margin-bottom:4px;position:relative;<?php echo !$group['is_read'] ? 'background:rgba(59,130,246,0.04);' : ''; ?>">
                
                <!-- Avatar -->
                <a href="<?php echo $href; ?>" style="flex-shrink:0;position:relative;">
                    <?php if(count($group['actors']) > 1): ?>
                    <div style="width:48px;height:48px;position:relative;">
                        <img src="<?php echo htmlspecialchars($group['actors'][0]); ?>" style="position:absolute;top:0;right:0;width:36px;height:36px;border-radius:50%;border:2px solid #fff;object-fit:cover;z-index:2;" alt="">
                        <img src="<?php echo htmlspecialchars($group['actors'][1]); ?>" style="position:absolute;bottom:0;left:0;width:32px;height:32px;border-radius:50%;border:2px solid #fff;object-fit:cover;z-index:1;opacity:0.8;" alt="">
                    </div>
                    <?php else: ?>
                    <div style="position:relative;">
                        <img src="<?php echo htmlspecialchars($group['actors'][0] ?? ''); ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:1px solid #e2e8f0;" alt="">
                        <div style="position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-radius:50%;background:<?php echo $tc['color']; ?>;display:flex;align-items:center;justify-content:center;border:2px solid #fff;">
                            <i class="fas <?php echo $tc['icon']; ?>" style="color:#fff;font-size:8px;"></i>
                        </div>
                    </div>
                    <?php endif; ?>
                </a>
                
                <!-- Content -->
                <a href="<?php echo $href; ?>" style="flex:1;min-width:0;text-decoration:none;color:inherit;">
                    <p style="font-size:13px;line-height:1.5;margin:0 0 2px;color:#334155;" class="dark:text-slate-300">
                        <?php echo getNotificationText($group, $lang); ?>
                    </p>
                    <span style="font-size:11px;color:#94a3b8;font-weight:600;">
                        <?php echo timeAgo($group['latest_at'], $lang); ?>
                        <?php if($group['count'] > 1): ?>
                        &middot; <?php echo $group['count']; ?> <?php echo $lang == 'en' ? 'notifications' : 'bildirim'; ?>
                        <?php endif; ?>
                    </span>
                </a>
                
                <!-- Delete Button -->
                <button onclick="deleteNotification('<?php echo $gkey; ?>', <?php echo $ids_json; ?>)" style="width:28px;height:28px;border-radius:8px;background:transparent;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:12px;flex-shrink:0;transition:all 0.15s;" onmouseover="this.style.background='#fef2f2';this.style.color='#ef4444'" onmouseout="this.style.background='transparent';this.style.color='#cbd5e1'" title="<?php echo $lang == 'en' ? 'Delete' : 'Sil'; ?>">
                    <i class="fas fa-times"></i>
                </button>

                <!-- Unread dot -->
                <?php if(!$group['is_read']): ?>
                <div style="position:absolute;left:4px;top:50%;transform:translateY(-50%);width:6px;height:6px;border-radius:50%;background:#3b82f6;"></div>
                <?php endif; ?>
            </div>
            
        <?php endforeach; ?>
        
        <?php if($notif_count == 0 && count($friend_requests) == 0): ?>
        <div style="text-align:center;padding:60px 20px;">
            <div style="width:64px;height:64px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="far fa-bell-slash" style="font-size:24px;color:#cbd5e1;"></i>
            </div>
            <h3 style="font-weight:800;color:#94a3b8;font-size:16px;margin:0 0 4px;">
                <?php echo $filter === 'unread' 
                    ? ($lang == 'en' ? 'All caught up!' : 'Tümü okundu!') 
                    : ($lang == 'en' ? 'No notifications yet' : 'Henüz bildirim yok'); ?>
            </h3>
            <p style="font-size:13px;color:#cbd5e1;">
                <?php echo $filter !== 'all' && $filter !== 'unread'
                    ? ($lang == 'en' ? 'No notifications in this category.' : 'Bu kategoride bildirim yok.')
                    : ($lang == 'en' ? 'When someone interacts with you, it will show up here.' : 'Biri sizinle etkileşime geçtiğinde burada görünecek.'); ?>
            </p>
        </div>
        <?php endif; ?>
        
    </div>

    <!-- Bottom nav comes from header.php -->

    <script>
    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        var wrap = document.getElementById('actions-menu-wrap');
        var dd = document.getElementById('actions-dropdown');
        if (wrap && dd && !wrap.contains(e.target)) dd.classList.add('hidden');
    });

    // Delete single/grouped notification
    function deleteNotification(key, ids) {
        var el = document.getElementById('notif-' + key);
        if (el) el.classList.add('removing');
        
        // Delete each notification in the group
        ids.forEach(function(id) {
            var fd = new FormData();
            fd.append('id', id);
            fetch('api/delete_notification.php', { method: 'POST', body: fd });
        });
        
        setTimeout(function() { if (el) el.remove(); }, 300);
    }

    // Delete all
    function deleteAll() {
        fetch('api/delete_all_notifications.php', { method: 'POST' })
        .then(function(r) { return r.json(); })
        .then(function() { location.reload(); });
    }

    // Mark all read
    function markAllRead() {
        var fd = new FormData();
        fd.append('mark_read', '1');
        fetch('api/delete_notification.php', { method: 'POST', body: fd })
        .then(function() { location.reload(); });
    }

    // Friend request response
    function respondToRequest(userId, action, btn) {
        btn.disabled = true;
        var fd = new FormData();
        fd.append('user_id', userId);
        fd.append('action', action);
        
        fetch('api/friend_response.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                var row = document.getElementById('freq-' + userId);
                if (row) {
                    if (action === 'accept') {
                        row.style.borderColor = '#10b981';
                        row.innerHTML = '<div style="display:flex;align-items:center;gap:8px;width:100%;justify-content:center;padding:4px;"><i class="fas fa-check-circle" style="color:#10b981;"></i><span style="font-weight:700;font-size:13px;color:#10b981;"><?php echo $lang == "en" ? "Friend added!" : "Arkadaş eklendi!"; ?></span></div>';
                    } else {
                        row.style.opacity = '0';
                    }
                    setTimeout(function() { if(row) row.remove(); }, 1500);
                }
            } else {
                alert(data.message || 'Hata');
                btn.disabled = false;
            }
        })
        .catch(function() { btn.disabled = false; });
    }
    </script>
</body>
</html>
