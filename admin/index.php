<?php
require_once '../includes/db.php';
require_once 'auth_session.php';
require_once "../includes/lang.php";
require_once "../includes/site_settings.php";

// ── Stats ──
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$new_today = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$new_yesterday = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();

$total_posts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL")->fetchColumn();
$posts_today = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE() AND deleted_at IS NULL")->fetchColumn();

$total_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
$events_week = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

$online_count = (int)$pdo->query("SELECT COUNT(*) FROM visitor_activity WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();
$total_comments = (int)$pdo->query("SELECT COUNT(*) FROM post_comments")->fetchColumn();

// Pending counts
$p_verif = (int)$pdo->query("SELECT COUNT(*) FROM verification_requests WHERE status='pending'")->fetchColumn();
$p_reports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
$p_props = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='pending'")->fetchColumn();
$p_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetchColumn();
$p_listings = (int)$pdo->query("SELECT COUNT(*) FROM marketplace_listings WHERE status='pending'")->fetchColumn();
$p_boats = (int)$pdo->query("SELECT COUNT(*) FROM boat_trips WHERE status='pending'")->fetchColumn();
$total_pending = $p_verif + $p_reports + $p_props + $p_events + $p_listings + $p_boats;

// Chart data (7 days)
$chart_labels = [];
$chart_users = [];
$chart_posts = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime($d));
    $chart_users[] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = '$d'")->fetchColumn();
    $chart_posts[] = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = '$d' AND deleted_at IS NULL")->fetchColumn();
}

// Newest users
$newest_users = $pdo->query("SELECT id, username, full_name, avatar, badge, created_at FROM users ORDER BY created_at DESC LIMIT 8")->fetchAll();

// Recent activity
$recent_logs = $pdo->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Top posts (most liked)
$top_posts = $pdo->query("SELECT p.id, p.content, p.created_at, u.full_name, u.avatar, (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes FROM posts p JOIN users u ON p.user_id = u.id WHERE p.deleted_at IS NULL ORDER BY likes DESC LIMIT 5")->fetchAll();

// System
$php_version = phpversion();
$db_size = $pdo->query("SELECT ROUND(SUM(data_length + index_length)/1024/1024, 2) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
$opcache_on = function_exists('opcache_get_status') && @opcache_get_status() ? true : false;

$user_growth = $new_yesterday > 0 ? round((($new_today - $new_yesterday) / $new_yesterday) * 100, 1) : ($new_today > 0 ? 100 : 0);

// Avatar helper
if (!function_exists('getAdminAvatar')) {
    function getAdminAvatar($path) {
        if (empty($path)) return 'https://ui-avatars.com/api/?name=A&background=e2e8f0&color=64748b&bold=true';
        if (preg_match('/^https?:\/\//', $path)) return $path;
        if ($path[0] === '/') return $path;
        return '/' . ltrim($path, './');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo htmlspecialchars(site_name()); ?></title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        
        <!-- Page Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
            <div>
                <h1 class="page-title" style="margin:0 0 4px;">Dashboard</h1>
                <div style="display:flex;align-items:center;gap:12px;font-size:13px;color:#64748b;font-weight:500;">
                    <span><i class="far fa-calendar-alt" style="margin-right:4px;"></i><?php echo date('d M Y'); ?></span>
                    <span style="color:#cbd5e1;">|</span>
                    <span><i class="far fa-clock" style="margin-right:4px;"></i><span id="live-time"><?php echo date('H:i'); ?></span></span>
                    <span style="color:#cbd5e1;">|</span>
                    <span style="display:flex;align-items:center;gap:4px;"><span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;"></span> <?php echo $online_count; ?> çevrimiçi</span>
                </div>
            </div>
            <?php if($total_pending > 0): ?>
            <div style="display:flex;align-items:center;gap:8px;background:#fef2f2;border:1px solid #fecaca;padding:8px 16px;border-radius:8px;">
                <i class="fas fa-bell" style="color:#ef4444;"></i>
                <span style="font-size:13px;font-weight:700;color:#991b1b;"><?php echo $total_pending; ?> bekleyen işlem</span>
                <a href="verifications" style="font-size:12px;font-weight:700;color:#ef4444;text-decoration:none;margin-left:8px;">Gör &rarr;</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stat Cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;">
            <!-- Users -->
            <div class="stat-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div class="stat-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fas fa-users"></i></div>
                    <?php if($new_today > 0): ?>
                    <span style="font-size:11px;font-weight:700;color:#10b981;background:#ecfdf5;padding:2px 8px;border-radius:6px;">+<?php echo $new_today; ?> bugün</span>
                    <?php endif; ?>
                </div>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-label" style="margin-top:4px;">Toplam Kullanıcı</div>
            </div>

            <!-- Posts -->
            <div class="stat-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div class="stat-icon" style="background:#faf5ff;color:#8b5cf6;"><i class="fas fa-pen-square"></i></div>
                    <?php if($posts_today > 0): ?>
                    <span style="font-size:11px;font-weight:700;color:#8b5cf6;background:#faf5ff;padding:2px 8px;border-radius:6px;">+<?php echo $posts_today; ?> bugün</span>
                    <?php endif; ?>
                </div>
                <div class="stat-value"><?php echo number_format($total_posts); ?></div>
                <div class="stat-label" style="margin-top:4px;">Toplam Gönderi</div>
            </div>

            <!-- Events -->
            <div class="stat-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-calendar-alt"></i></div>
                    <span style="font-size:11px;font-weight:700;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:6px;">Bu hafta: <?php echo $events_week; ?></span>
                </div>
                <div class="stat-value"><?php echo $total_events; ?></div>
                <div class="stat-label" style="margin-top:4px;">Aktif Etkinlik</div>
            </div>

            <!-- Online -->
            <div class="stat-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div class="stat-icon" style="background:#ecfdf5;color:#10b981;"><i class="fas fa-signal"></i></div>
                    <span style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:#10b981;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#10b981;animation:blink 1.5s infinite;"></span> canlı
                    </span>
                </div>
                <div class="stat-value" id="online-val"><?php echo $online_count; ?></div>
                <div class="stat-label" style="margin-top:4px;">Şu An Çevrimiçi</div>
            </div>

            <!-- Comments -->
            <div class="stat-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <div class="stat-icon" style="background:#f0fdf4;color:#22c55e;"><i class="fas fa-comments"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($total_comments); ?></div>
                <div class="stat-label" style="margin-top:4px;">Toplam Yorum</div>
            </div>
        </div>

        <!-- Two-column layout -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;">
            
            <!-- Chart -->
            <div class="admin-card" style="grid-column:span 1;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                    <div>
                        <div class="section-title" style="margin-bottom:2px;">Haftalık Aktivite</div>
                        <div style="font-size:12px;color:#94a3b8;">Son 7 gün</div>
                    </div>
                    <div style="display:flex;gap:12px;font-size:11px;font-weight:700;">
                        <span style="display:flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:2px;background:#3b82f6;display:inline-block;"></span> Kullanıcı</span>
                        <span style="display:flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:2px;background:#10b981;display:inline-block;"></span> Gönderi</span>
                    </div>
                </div>
                <div style="height:240px;"><canvas id="activityChart"></canvas></div>
            </div>

            <!-- Pending Actions -->
            <div class="admin-card">
                <div class="section-title" style="margin-bottom:16px;">Bekleyen İşlemler</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php
                    $pending_items = [
                        ['Doğrulamalar', $p_verif, 'verifications', 'fa-user-check', '#3b82f6'],
                        ['Şikayetler', $p_reports, 'reports', 'fa-flag', '#ef4444'],
                        ['Etkinlikler', $p_events, 'events', 'fa-calendar-alt', '#d97706'],
                        ['Emlak', $p_props, 'properties', 'fa-building', '#8b5cf6'],
                        ['Pazar Yeri', $p_listings, 'listings', 'fa-store', '#10b981'],
                        ['Tekne Turları', $p_boats, 'boat_trips', 'fa-ship', '#06b6d4'],
                    ];
                    foreach($pending_items as $pi): ?>
                    <a href="<?php echo $pi[2]; ?>" style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;text-decoration:none;border:1px solid #f1f5f9;transition:all 0.15s;" onmouseover="this.style.background='#f8fafc';this.style.borderColor='#e2e8f0'" onmouseout="this.style.background='transparent';this.style.borderColor='#f1f5f9'">
                        <div style="width:32px;height:32px;border-radius:8px;background:<?php echo $pi[4]; ?>10;color:<?php echo $pi[4]; ?>;display:flex;align-items:center;justify-content:center;font-size:13px;">
                            <i class="fas <?php echo $pi[3]; ?>"></i>
                        </div>
                        <span style="flex:1;font-size:13px;font-weight:600;color:#334155;"><?php echo $pi[0]; ?></span>
                        <span style="font-size:14px;font-weight:800;color:<?php echo $pi[1] > 0 ? $pi[4] : '#cbd5e1'; ?>;"><?php echo $pi[1]; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Three-column: Users, Top Posts, Activity Log -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:28px;">
            
            <!-- New Members -->
            <div class="admin-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div class="section-title" style="margin-bottom:0;">Yeni Üyeler</div>
                    <a href="users" style="font-size:12px;font-weight:700;color:#3b82f6;text-decoration:none;">Tümü &rarr;</a>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;">
                    <?php foreach($newest_users as $u): ?>
                    <a href="users?search=<?php echo urlencode($u['username']); ?>" style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <img src="<?php echo getAdminAvatar($u['avatar']); ?>" style="width:34px;height:34px;border-radius:8px;object-fit:cover;background:#f1f5f9;" alt="">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:700;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($u['full_name']); ?></div>
                            <div style="font-size:11px;color:#94a3b8;">@<?php echo htmlspecialchars($u['username']); ?></div>
                        </div>
                        <div style="font-size:10px;color:#94a3b8;font-weight:600;white-space:nowrap;"><?php echo date('d M', strtotime($u['created_at'])); ?></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Posts -->
            <div class="admin-card">
                <div class="section-title" style="margin-bottom:16px;">En Çok Beğenilen</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach($top_posts as $tp): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;border:1px solid #f1f5f9;">
                        <img src="<?php echo getAdminAvatar($tp['avatar']); ?>" style="width:30px;height:30px;border-radius:6px;object-fit:cover;background:#f1f5f9;" alt="">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12px;font-weight:600;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars(mb_substr($tp['content'] ?? '', 0, 50)); ?></div>
                            <div style="font-size:10px;color:#94a3b8;"><?php echo htmlspecialchars($tp['full_name']); ?></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;font-size:12px;font-weight:800;color:#ef4444;">
                            <i class="fas fa-heart" style="font-size:10px;"></i><?php echo $tp['likes']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($top_posts)): ?>
                    <div class="empty-state" style="padding:24px;"><p>Henüz gönderi yok</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="admin-card">
                <div class="section-title" style="margin-bottom:16px;">Son İşlemler</div>
                <div style="display:flex;flex-direction:column;gap:4px;max-height:320px;overflow-y:auto;" class="custom-scrollbar">
                    <?php if(!empty($recent_logs)): ?>
                        <?php foreach($recent_logs as $log): ?>
                        <div style="display:flex;align-items:flex-start;gap:10px;padding:8px;border-radius:8px;">
                            <div style="width:6px;height:6px;border-radius:50%;background:#cbd5e1;margin-top:6px;flex-shrink:0;"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:600;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($log['action']); ?></div>
                                <div style="font-size:10px;color:#94a3b8;font-weight:600;"><?php echo date('H:i · d M', strtotime($log['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding:24px;"><p>Kayıt yok</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Info -->
        <div class="admin-card" style="margin-bottom:28px;">
            <div class="section-title" style="margin-bottom:16px;">Sistem Bilgisi</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#f8fafc;border-radius:8px;">
                    <i class="fab fa-php" style="color:#4f46e5;font-size:18px;"></i>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;">PHP</div>
                        <div style="font-size:14px;font-weight:800;color:#0f172a;"><?php echo $php_version; ?></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#f8fafc;border-radius:8px;">
                    <i class="fas fa-database" style="color:#3b82f6;font-size:16px;"></i>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;">Veritabanı</div>
                        <div style="font-size:14px;font-weight:800;color:#0f172a;"><?php echo $db_size; ?> MB</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#f8fafc;border-radius:8px;">
                    <i class="fas fa-bolt" style="color:<?php echo $opcache_on ? '#10b981' : '#ef4444'; ?>;font-size:16px;"></i>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;">OPcache</div>
                        <div style="font-size:14px;font-weight:800;color:<?php echo $opcache_on ? '#10b981' : '#ef4444'; ?>;"><?php echo $opcache_on ? 'Aktif' : 'Kapalı'; ?></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#f8fafc;border-radius:8px;">
                    <i class="fas fa-server" style="color:#64748b;font-size:16px;"></i>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;">Sunucu</div>
                        <div style="font-size:14px;font-weight:800;color:#0f172a;"><?php echo php_uname('s'); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <style>
        @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }
        @media (max-width: 1023px) {
            div[style*="grid-template-columns:1fr 1fr 1fr"] { grid-template-columns: 1fr !important; }
            div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 640px) {
            div[style*="grid-template-columns:repeat(auto-fit"] { grid-template-columns: 1fr 1fr !important; }
        }
    </style>

    <script>
        // Live clock
        setInterval(function() {
            var n = new Date();
            document.getElementById('live-time').textContent = n.toLocaleTimeString('tr-TR', {hour:'2-digit',minute:'2-digit'});
        }, 30000);

        // Chart
        var ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Kullanıcı',
                        data: <?php echo json_encode($chart_users); ?>,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    },
                    {
                        label: 'Gönderi',
                        data: <?php echo json_encode($chart_posts); ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Outfit', weight: '700', size: 12 },
                        bodyFont: { family: 'Outfit', size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family:'Outfit', weight:'700', size:11 }, color:'#94a3b8' } },
                    y: { grid: { color:'#f1f5f9' }, ticks: { font: { family:'Outfit', weight:'600', size:11 }, color:'#94a3b8', stepSize:1 }, beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
