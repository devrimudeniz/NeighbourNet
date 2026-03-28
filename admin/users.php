<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

require_once "../includes/lang.php";

// Filters
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$badge_filter = $_GET['badge'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Stats
$stats = [];
$stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['active'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active' OR status IS NULL")->fetchColumn();
$stats['banned'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'banned'")->fetchColumn();
$stats['new_week'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$stats['new_month'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

try {
    $stats['online'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_activity WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();
} catch(Exception $e) { $stats['online'] = 0; }

// Badge distribution
$badge_dist = $pdo->query("SELECT badge, COUNT(*) as cnt FROM users GROUP BY badge ORDER BY cnt DESC")->fetchAll(PDO::FETCH_KEY_PAIR);

// Build query
$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ? OR CAST(id AS CHAR) = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

if ($filter === 'active') {
    $where_clauses[] = "(status = 'active' OR status IS NULL)";
} elseif ($filter === 'banned') {
    $where_clauses[] = "status = 'banned'";
} elseif ($filter === 'new') {
    $where_clauses[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

if ($badge_filter) {
    $where_clauses[] = "badge = ?";
    $params[] = $badge_filter;
}

$where_sql = implode(' AND ', $where_clauses);

// Count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_sql");
$count_stmt->execute($params);
$total_results = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_results / $per_page));

// Sort
$order = "created_at DESC";
if ($sort === 'oldest') $order = "created_at ASC";
elseif ($sort === 'alpha') $order = "username ASC";
elseif ($sort === 'posts') $order = "post_count DESC";

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
          (SELECT COUNT(*) FROM post_comments WHERE user_id = u.id) as comment_count
          FROM users u 
          WHERE $where_sql 
          ORDER BY $order 
          LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Online check helper
function isUserOnline($pdo, $user_id) {
    try {
        $s = $pdo->prepare("SELECT 1 FROM visitor_activity WHERE user_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1");
        $s->execute([$user_id]);
        return $s->fetchColumn() ? true : false;
    } catch(Exception $e) { return false; }
}

// Badge color map
$badge_colors = [
    'founder'           => ['bg' => '#0f172a', 'text' => '#fff', 'icon' => 'fa-crown'],
    'admin'             => ['bg' => '#1e293b', 'text' => '#fff', 'icon' => 'fa-shield-alt'],
    'moderator'         => ['bg' => '#7c3aed', 'text' => '#fff', 'icon' => 'fa-gavel'],
    'business'          => ['bg' => '#2563eb', 'text' => '#fff', 'icon' => 'fa-store'],
    'verified_business' => ['bg' => '#0891b2', 'text' => '#fff', 'icon' => 'fa-gem'],
    'user'              => ['bg' => '#e2e8f0', 'text' => '#475569', 'icon' => 'fa-user'],
];

// Build query string helper
function buildUrl($overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi | Admin</title>
    <?php include '../includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .user-row { transition: background 0.1s; }
        .user-row:hover { background: #f8fafc !important; }
        .filter-chip { transition: all 0.15s; cursor: pointer; }
        .filter-chip:hover { border-color: #94a3b8; }
        .filter-chip.active { background: #0f172a; color: #fff; border-color: #0f172a; }
        .action-btn { transition: all 0.12s; }
        .action-btn:hover { transform: scale(1.08); }
        .quick-info { display: none; }
        .user-row:hover .quick-info { display: flex; }
        @media (max-width: 768px) {
            .hide-mobile { display: none !important; }
            .show-mobile { display: block !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        
        <!-- Page Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
            <div>
                <h1 class="page-title" style="margin:0 0 4px;">Kullanıcı Yönetimi</h1>
                <p class="page-subtitle" style="margin:0;">
                    <?php echo number_format($stats['total']); ?> toplam kullanıcı &middot; <?php echo $stats['online']; ?> çevrimiçi
                </p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <a href="?sort=newest&filter=new" class="btn btn-outline btn-sm">
                    <i class="fas fa-user-plus"></i> Bu Hafta +<?php echo $stats['new_week']; ?>
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:12px;margin-bottom:24px;">
            <a href="?filter=all" class="stat-box" style="text-decoration:none;color:inherit;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div class="stat-icon" style="background:#f0f9ff;color:#0ea5e9;"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Toplam Kullanıcı</div>
            </a>
            <a href="?filter=active" class="stat-box" style="text-decoration:none;color:inherit;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div class="stat-icon" style="background:#ecfdf5;color:#10b981;"><i class="fas fa-user-check"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
                <div class="stat-label">Aktif</div>
            </a>
            <a href="?filter=banned" class="stat-box" style="text-decoration:none;color:inherit;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div class="stat-icon" style="background:#fef2f2;color:#ef4444;"><i class="fas fa-user-slash"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['banned']; ?></div>
                <div class="stat-label">Yasaklı</div>
            </a>
            <div class="stat-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div class="stat-icon" style="background:#f0fdf4;color:#22c55e;"><i class="fas fa-circle"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['online']; ?></div>
                <div class="stat-label">Şu An Online</div>
            </div>
            <a href="?filter=new" class="stat-box" style="text-decoration:none;color:inherit;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div class="stat-icon" style="background:#faf5ff;color:#a855f7;"><i class="fas fa-user-plus"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['new_month']; ?></div>
                <div class="stat-label">Son 30 Gün</div>
            </a>
        </div>

        <!-- Search & Filters -->
        <div class="admin-card" style="margin-bottom:20px;padding:16px;">
            <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <!-- Preserve filters -->
                <?php if ($filter): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>"><?php endif; ?>
                <?php if ($badge_filter): ?><input type="hidden" name="badge" value="<?php echo htmlspecialchars($badge_filter); ?>"><?php endif; ?>
                <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>"><?php endif; ?>
                
                <div style="flex:1;min-width:200px;position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="İsim, kullanıcı adı, e-posta veya ID ara..." 
                           class="admin-input" style="padding-left:36px;">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Ara</button>
                <?php if ($search || $filter !== 'all' || $badge_filter): ?>
                <a href="users" class="btn btn-outline"><i class="fas fa-times"></i> Temizle</a>
                <?php endif; ?>
            </form>

            <!-- Filter chips -->
            <div style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap;align-items:center;">
                <span style="font-size:11px;font-weight:700;color:#94a3b8;margin-right:4px;">Filtre:</span>
                <a href="<?php echo buildUrl(['filter'=>'all','badge'=>'','page'=>1]); ?>" class="filter-chip <?php echo $filter==='all'&&!$badge_filter?'active':''; ?>" style="padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #e2e8f0;text-decoration:none;color:inherit;">
                    Tümü
                </a>
                <a href="<?php echo buildUrl(['filter'=>'active','badge'=>'','page'=>1]); ?>" class="filter-chip <?php echo $filter==='active'?'active':''; ?>" style="padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #e2e8f0;text-decoration:none;color:inherit;">
                    <i class="fas fa-check-circle" style="color:#10b981;font-size:10px;"></i> Aktif
                </a>
                <a href="<?php echo buildUrl(['filter'=>'banned','badge'=>'','page'=>1]); ?>" class="filter-chip <?php echo $filter==='banned'?'active':''; ?>" style="padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #e2e8f0;text-decoration:none;color:inherit;">
                    <i class="fas fa-ban" style="color:#ef4444;font-size:10px;"></i> Yasaklı
                </a>
                <a href="<?php echo buildUrl(['filter'=>'new','badge'=>'','page'=>1]); ?>" class="filter-chip <?php echo $filter==='new'?'active':''; ?>" style="padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #e2e8f0;text-decoration:none;color:inherit;">
                    <i class="fas fa-sparkles" style="color:#a855f7;font-size:10px;"></i> Yeni
                </a>
                
                <span style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></span>
                <span style="font-size:11px;font-weight:700;color:#94a3b8;margin-right:4px;">Rozet:</span>
                <?php foreach($badge_dist as $bname => $bcnt): 
                    if (empty($bname)) continue;
                    $bc = $badge_colors[$bname] ?? $badge_colors['user'];
                ?>
                <a href="<?php echo buildUrl(['badge'=>$bname,'filter'=>'all','page'=>1]); ?>" class="filter-chip <?php echo $badge_filter===$bname?'active':''; ?>" style="padding:5px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #e2e8f0;text-decoration:none;color:inherit;display:flex;align-items:center;gap:4px;">
                    <i class="fas <?php echo $bc['icon']; ?>" style="font-size:10px;"></i>
                    <?php echo ucfirst($bname); ?>
                    <span style="opacity:0.4;font-size:10px;"><?php echo $bcnt; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Sort -->
            <div style="display:flex;gap:6px;margin-top:10px;align-items:center;">
                <span style="font-size:11px;font-weight:700;color:#94a3b8;">Sıralama:</span>
                <a href="<?php echo buildUrl(['sort'=>'newest']); ?>" style="font-size:12px;font-weight:<?php echo $sort==='newest'?'800':'600'; ?>;color:<?php echo $sort==='newest'?'#0f172a':'#94a3b8'; ?>;text-decoration:none;">Yeni</a>
                <a href="<?php echo buildUrl(['sort'=>'oldest']); ?>" style="font-size:12px;font-weight:<?php echo $sort==='oldest'?'800':'600'; ?>;color:<?php echo $sort==='oldest'?'#0f172a':'#94a3b8'; ?>;text-decoration:none;">Eski</a>
                <a href="<?php echo buildUrl(['sort'=>'alpha']); ?>" style="font-size:12px;font-weight:<?php echo $sort==='alpha'?'800':'600'; ?>;color:<?php echo $sort==='alpha'?'#0f172a':'#94a3b8'; ?>;text-decoration:none;">A-Z</a>
                <a href="<?php echo buildUrl(['sort'=>'posts']); ?>" style="font-size:12px;font-weight:<?php echo $sort==='posts'?'800':'600'; ?>;color:<?php echo $sort==='posts'?'#0f172a':'#94a3b8'; ?>;text-decoration:none;">Gönderi</a>
            </div>
        </div>

        <!-- Results count -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <p style="font-size:13px;color:#64748b;font-weight:600;margin:0;">
                <?php echo number_format($total_results); ?> kullanıcı bulundu
                <?php if ($search): ?> &middot; "<?php echo htmlspecialchars($search); ?>" araması<?php endif; ?>
            </p>
            <p style="font-size:12px;color:#94a3b8;margin:0;">
                Sayfa <?php echo $page; ?>/<?php echo $total_pages; ?>
            </p>
        </div>

        <!-- Users Table -->
        <div class="admin-card" style="padding:0;overflow:hidden;">
            <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <p>Kullanıcı bulunamadı.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table" style="min-width:700px;">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">#</th>
                            <th>Kullanıcı</th>
                            <th class="hide-mobile">E-posta</th>
                            <th>Rozet</th>
                            <th>Durum</th>
                            <th class="hide-mobile" style="text-align:center;">Gönderi</th>
                            <th class="hide-mobile">Kayıt</th>
                            <th style="text-align:right;width:120px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $i => $user): 
                            $is_banned = ($user['status'] ?? 'active') === 'banned';
                            $bc = $badge_colors[$user['badge'] ?? 'user'] ?? $badge_colors['user'];
                            $online = isUserOnline($pdo, $user['id']);
                            $join_date = date('d M Y', strtotime($user['created_at']));
                            $join_ago = '';
                            $diff = time() - strtotime($user['created_at']);
                            if ($diff < 86400) $join_ago = 'bugün';
                            elseif ($diff < 604800) $join_ago = floor($diff/86400) . ' gün önce';
                            elseif ($diff < 2592000) $join_ago = floor($diff/604800) . ' hafta önce';
                            else $join_ago = floor($diff/2592000) . ' ay önce';
                        ?>
                        <tr class="user-row" style="<?php echo $is_banned ? 'background:#fef2f2;' : ''; ?>">
                            <td style="text-align:center;font-size:12px;color:#94a3b8;font-weight:700;">
                                <?php echo $offset + $i + 1; ?>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="position:relative;flex-shrink:0;">
                                        <img src="<?php echo getAdminAvatar($user['avatar']); ?>" 
                                             style="width:40px;height:40px;border-radius:10px;object-fit:cover;background:#f1f5f9;<?php echo $is_banned ? 'filter:grayscale(1);opacity:0.5;' : ''; ?>" alt="">
                                        <?php if ($online && !$is_banned): ?>
                                        <div style="position:absolute;bottom:-1px;right:-1px;width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid #fff;"></div>
                                        <?php endif; ?>
                                        <?php if ($is_banned): ?>
                                        <div style="position:absolute;top:-3px;right:-3px;width:16px;height:16px;border-radius:50%;background:#ef4444;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-ban" style="font-size:8px;color:#fff;"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <a href="../profile?id=<?php echo $user['id']; ?>" target="_blank" 
                                               style="font-size:14px;font-weight:800;color:#0f172a;text-decoration:none;<?php echo $is_banned ? 'text-decoration:line-through;color:#ef4444;' : ''; ?>">
                                                <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                            </a>
                                            <?php if ($online && !$is_banned): ?>
                                            <span style="font-size:9px;font-weight:800;color:#22c55e;text-transform:uppercase;">Online</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:12px;color:#94a3b8;font-weight:600;">@<?php echo htmlspecialchars($user['username']); ?> &middot; ID: <?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="hide-mobile">
                                <span style="font-size:12px;color:#64748b;font-weight:500;"><?php echo htmlspecialchars($user['email'] ?? '—'); ?></span>
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:800;background:<?php echo $bc['bg']; ?>;color:<?php echo $bc['text']; ?>;">
                                    <i class="fas <?php echo $bc['icon']; ?>" style="font-size:9px;"></i>
                                    <?php echo ucfirst($user['badge'] ?? 'user'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_banned): ?>
                                <span class="status-pill status-rejected"><i class="fas fa-ban" style="font-size:9px;"></i> Banned</span>
                                <?php else: ?>
                                <span class="status-pill status-active"><i class="fas fa-check" style="font-size:9px;"></i> Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="hide-mobile" style="text-align:center;">
                                <div style="display:flex;flex-direction:column;align-items:center;gap:1px;">
                                    <span style="font-size:14px;font-weight:800;color:#0f172a;"><?php echo $user['post_count']; ?></span>
                                    <span style="font-size:9px;color:#94a3b8;font-weight:600;"><?php echo $user['comment_count']; ?> yorum</span>
                                </div>
                            </td>
                            <td class="hide-mobile">
                                <div>
                                    <span style="font-size:12px;font-weight:600;color:#334155;"><?php echo $join_date; ?></span>
                                    <br><span style="font-size:10px;color:#94a3b8;"><?php echo $join_ago; ?></span>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($_SESSION['user_id'] != $user['id'] && ($user['badge'] ?? '') !== 'founder'): ?>
                                <div style="display:flex;gap:4px;justify-content:flex-end;">
                                    <button onclick="openBadgeModal(<?php echo $user['id']; ?>, '<?php echo $user['badge'] ?? 'user'; ?>')" 
                                            class="action-btn" title="Rozet Düzenle"
                                            style="width:32px;height:32px;border-radius:8px;background:#f0f9ff;border:1px solid #e0f2fe;color:#0ea5e9;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-id-badge"></i>
                                    </button>
                                    <?php if ($is_banned): ?>
                                    <button onclick="toggleBan(<?php echo $user['id']; ?>, 'unban_user')" 
                                            class="action-btn" title="Yasağı Kaldır"
                                            style="width:32px;height:32px;border-radius:8px;background:#ecfdf5;border:1px solid #d1fae5;color:#10b981;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                    <?php else: ?>
                                    <button onclick="toggleBan(<?php echo $user['id']; ?>, 'ban_user')" 
                                            class="action-btn" title="Yasakla"
                                            style="width:32px;height:32px;border-radius:8px;background:#fef2f2;border:1px solid #fee2e2;color:#ef4444;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                    <?php endif; ?>
                                    <a href="../profile?id=<?php echo $user['id']; ?>" target="_blank"
                                       class="action-btn" title="Profili Görüntüle"
                                       style="width:32px;height:32px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                                <?php else: ?>
                                <span style="font-size:11px;color:#94a3b8;font-weight:600;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:20px;">
            <?php if ($page > 1): ?>
            <a href="<?php echo buildUrl(['page' => $page - 1]); ?>" class="btn btn-outline btn-sm"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            
            <?php 
            $start_p = max(1, $page - 2);
            $end_p = min($total_pages, $page + 2);
            if ($start_p > 1): ?>
            <a href="<?php echo buildUrl(['page' => 1]); ?>" class="btn btn-outline btn-sm">1</a>
            <?php if ($start_p > 2): ?><span style="color:#94a3b8;">...</span><?php endif; ?>
            <?php endif; ?>
            
            <?php for ($p = $start_p; $p <= $end_p; $p++): ?>
            <a href="<?php echo buildUrl(['page' => $p]); ?>" class="btn <?php echo $p===$page ? 'btn-primary' : 'btn-outline'; ?> btn-sm"><?php echo $p; ?></a>
            <?php endfor; ?>
            
            <?php if ($end_p < $total_pages): ?>
            <?php if ($end_p < $total_pages - 1): ?><span style="color:#94a3b8;">...</span><?php endif; ?>
            <a href="<?php echo buildUrl(['page' => $total_pages]); ?>" class="btn btn-outline btn-sm"><?php echo $total_pages; ?></a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="<?php echo buildUrl(['page' => $page + 1]); ?>" class="btn btn-outline btn-sm"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Badge Distribution Mini Chart -->
        <div class="admin-card" style="margin-top:24px;">
            <h3 class="section-title" style="font-size:15px;margin-bottom:12px;">
                <i class="fas fa-chart-pie" style="color:#64748b;margin-right:6px;font-size:13px;"></i> Rozet Dağılımı
            </h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php foreach ($badge_dist as $bname => $bcnt): 
                    if (empty($bname)) continue;
                    $bc = $badge_colors[$bname] ?? $badge_colors['user'];
                    $pct = $stats['total'] > 0 ? round(($bcnt / $stats['total']) * 100, 1) : 0;
                ?>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;">
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:800;background:<?php echo $bc['bg']; ?>;color:<?php echo $bc['text']; ?>;">
                        <i class="fas <?php echo $bc['icon']; ?>" style="font-size:8px;"></i>
                        <?php echo ucfirst($bname); ?>
                    </span>
                    <span style="font-size:16px;font-weight:900;color:#0f172a;"><?php echo $bcnt; ?></span>
                    <span style="font-size:11px;color:#94a3b8;font-weight:600;">(<?php echo $pct; ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Visual bar -->
            <div style="display:flex;height:8px;border-radius:4px;overflow:hidden;margin-top:12px;">
                <?php foreach ($badge_dist as $bname => $bcnt): 
                    if (empty($bname)) continue;
                    $bc = $badge_colors[$bname] ?? $badge_colors['user'];
                    $pct = $stats['total'] > 0 ? ($bcnt / $stats['total']) * 100 : 0;
                ?>
                <div style="width:<?php echo $pct; ?>%;background:<?php echo $bc['bg']; ?>;min-width:<?php echo $pct > 0 ? '3px' : '0'; ?>;" title="<?php echo ucfirst($bname) . ': ' . $bcnt; ?>"></div>
                <?php endforeach; ?>
            </div>
        </div>

    </main>

    <!-- Badge Edit Modal -->
    <div id="badge-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:60;padding:16px;" onclick="if(event.target===this)closeBadgeModal()">
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;padding:24px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h3 style="font-size:17px;font-weight:900;margin:0;">Rozet Düzenle</h3>
                <button onclick="closeBadgeModal()" style="width:32px;height:32px;border-radius:8px;background:#f1f5f9;border:none;cursor:pointer;color:#64748b;font-size:14px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="badge-form">
                <input type="hidden" name="user_id" id="badge-user-id">
                <input type="hidden" name="action" value="change_badge">
                
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:6px;">Ana Rozet</label>
                    <select name="new_badge" id="badge-select" class="admin-input">
                        <option value="user">User</option>
                        <option value="business">Business</option>
                        <option value="verified_business">VIP Business</option>
                        <option value="moderator">Moderator</option>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Uzman Rozetleri</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <?php 
                        $expert_options = [
                            'real_estate' => ['label' => 'Real Estate', 'icon' => 'fa-home', 'color' => '#0ea5e9'],
                            'captain' => ['label' => 'Captain', 'icon' => 'fa-ship', 'color' => '#3b82f6'],
                            'local_guide' => ['label' => 'Local Guide', 'icon' => 'fa-map-marked-alt', 'color' => '#10b981'],
                            'place_scout' => ['label' => 'Place Scout', 'icon' => 'fa-binoculars', 'color' => '#f59e0b'],
                            'taxi' => ['label' => 'Taxi', 'icon' => 'fa-taxi', 'color' => '#eab308'],
                        ];
                        foreach ($expert_options as $val => $opt): ?>
                        <label style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all 0.15s;" 
                               onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" name="expert_badges[]" value="<?php echo $val; ?>" style="width:16px;height:16px;accent-color:<?php echo $opt['color']; ?>;">
                            <i class="fas <?php echo $opt['icon']; ?>" style="color:<?php echo $opt['color']; ?>;font-size:13px;width:18px;text-align:center;"></i>
                            <span style="font-size:13px;font-weight:700;"><?php echo $opt['label']; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size:10px;color:#94a3b8;margin:8px 0 0;">Rozet eklemek Trust Score'u günceller.</p>
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="button" onclick="closeBadgeModal()" class="btn btn-outline" style="flex:1;">İptal</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleBan(id, action) {
        var label = action === 'ban_user' ? 'YASAKLA' : 'YASAĞI KALDIR';
        if (!confirm('Bu kullanıcıyı ' + label + ' etmek istediğinize emin misiniz?')) return;

        var params = new URLSearchParams();
        params.append('action', action);
        params.append('user_id', id);

        fetch('api_admin.php', { method: 'POST', body: params })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') location.reload();
            else alert(data.message || 'Hata');
        });
    }

    var modal = document.getElementById('badge-modal');
    var form = document.getElementById('badge-form');
    var userIdInput = document.getElementById('badge-user-id');
    var badgeSelect = document.getElementById('badge-select');

    function openBadgeModal(userId, currentBadge) {
        userIdInput.value = userId;
        badgeSelect.value = currentBadge || 'user';
        
        document.querySelectorAll('input[name="expert_badges[]"]').forEach(function(cb) { cb.checked = false; });

        fetch('api_admin.php?action=get_badges&user_id=' + userId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success' && data.badges) {
                data.badges.forEach(function(badge) {
                    var cb = document.querySelector('input[name="expert_badges[]"][value="' + badge + '"]');
                    if (cb) cb.checked = true;
                });
            }
        });

        modal.style.display = 'block';
    }

    function closeBadgeModal() {
        modal.style.display = 'none';
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        fetch('api_admin.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') location.reload();
            else alert(data.message || 'Hata');
        });
    });
    </script>
</body>
</html>
