<?php
require_once '../includes/db.php';
require_once 'auth_session.php';
require_once "../includes/lang.php";

// ── Filter ──
$filter = $_GET['f'] ?? 'all'; // all, members, guests, bots

// ── Active visitors (last 1 hour) ──
$visitors_sql = "SELECT v.*, u.username as registered_name, u.full_name as registered_fullname, u.avatar as registered_avatar 
                 FROM visitor_activity v 
                 LEFT JOIN users u ON v.user_id = u.id 
                 WHERE v.last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                 ORDER BY v.last_activity DESC";
$all_visitors = $pdo->query($visitors_sql)->fetchAll();

// Stats
$total_online = count($all_visitors);
$bots_count = 0;
$guest_count = 0;
$member_count = 0;
$country_stats = [];
$page_stats = [];
$device_stats = ['mobile' => 0, 'desktop' => 0, 'tablet' => 0, 'bot' => 0];

foreach ($all_visitors as $v) {
    if ($v['is_bot']) $bots_count++;
    elseif ($v['user_id']) $member_count++;
    else $guest_count++;
    
    $cc = strtoupper($v['country_code'] ?? 'XX');
    if (!isset($country_stats[$cc])) $country_stats[$cc] = 0;
    $country_stats[$cc]++;
    
    $page = $v['current_page'] ?? '/';
    $page = strtok($page, '?'); // remove query string
    if (!isset($page_stats[$page])) $page_stats[$page] = 0;
    $page_stats[$page]++;
    
    $dt = $v['device_type'] ?? 'desktop';
    if (isset($device_stats[$dt])) $device_stats[$dt]++;
}
arsort($country_stats);
arsort($page_stats);
$page_stats = array_slice($page_stats, 0, 10, true);

// Filter
$visitors = $all_visitors;
if ($filter === 'members') $visitors = array_filter($all_visitors, fn($v) => !$v['is_bot'] && $v['user_id']);
elseif ($filter === 'guests') $visitors = array_filter($all_visitors, fn($v) => !$v['is_bot'] && !$v['user_id']);
elseif ($filter === 'bots') $visitors = array_filter($all_visitors, fn($v) => $v['is_bot']);

// ── Historical stats ──
$today_total = (int)$pdo->query("SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM visitor_activity WHERE DATE(last_activity) = CURDATE()")->fetchColumn();
$yesterday_total = (int)$pdo->query("SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM visitor_activity WHERE DATE(last_activity) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
$week_total = (int)$pdo->query("SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM visitor_activity WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// ── Hourly chart (last 24h) ──
$hourly = [];
for ($i = 23; $i >= 0; $i--) {
    $h = date('H', strtotime("-$i hours"));
    $hStart = date('Y-m-d H:00:00', strtotime("-$i hours"));
    $hEnd = date('Y-m-d H:59:59', strtotime("-$i hours"));
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM visitor_activity WHERE last_activity BETWEEN '$hStart' AND '$hEnd'")->fetchColumn();
    $hourly[] = ['hour' => $h . ':00', 'count' => $cnt];
}

// Helpers
function countryFlag($code) {
    if (empty($code) || strlen($code) != 2) return '🌍';
    $flags = ['TR'=>'🇹🇷','GB'=>'🇬🇧','US'=>'🇺🇸','DE'=>'🇩🇪','FR'=>'🇫🇷','NL'=>'🇳🇱','RU'=>'🇷🇺','IT'=>'🇮🇹','ES'=>'🇪🇸','SE'=>'🇸🇪','NO'=>'🇳🇴','DK'=>'🇩🇰','FI'=>'🇫🇮','PL'=>'🇵🇱','UA'=>'🇺🇦','BE'=>'🇧🇪','AT'=>'🇦🇹','CH'=>'🇨🇭','GR'=>'🇬🇷','PT'=>'🇵🇹','CZ'=>'🇨🇿','RO'=>'🇷🇴','HU'=>'🇭🇺','IE'=>'🇮🇪','AU'=>'🇦🇺','CA'=>'🇨🇦','JP'=>'🇯🇵','CN'=>'🇨🇳','IN'=>'🇮🇳','BR'=>'🇧🇷','AE'=>'🇦🇪','SA'=>'🇸🇦','IL'=>'🇮🇱','EG'=>'🇪🇬','ZA'=>'🇿🇦','KR'=>'🇰🇷','XX'=>'🌍','??'=>'🌍'];
    return $flags[strtoupper($code)] ?? '🌍';
}
function countryName($code) {
    $c = ['TR'=>'Türkiye','GB'=>'İngiltere','US'=>'ABD','DE'=>'Almanya','FR'=>'Fransa','NL'=>'Hollanda','RU'=>'Rusya','IT'=>'İtalya','ES'=>'İspanya','SE'=>'İsveç','NO'=>'Norveç','DK'=>'Danimarka','FI'=>'Finlandiya','PL'=>'Polonya','UA'=>'Ukrayna','BE'=>'Belçika','AT'=>'Avusturya','CH'=>'İsviçre','GR'=>'Yunanistan','PT'=>'Portekiz','AU'=>'Avustralya','CA'=>'Kanada','JP'=>'Japonya','CN'=>'Çin','IN'=>'Hindistan','BR'=>'Brezilya','AE'=>'BAE','XX'=>'Bilinmiyor'];
    return $c[strtoupper($code)] ?? strtoupper($code);
}
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
    <title>Ziyaretçiler | Admin</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        
        <!-- Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
            <div>
                <h1 class="page-title">Ziyaretçiler</h1>
                <p class="page-subtitle">Anlık kullanıcı takibi ve trafik analizi</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="display:flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#10b981;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:blink 1.5s infinite;"></span> Canlı
                </span>
                <button onclick="location.reload()" class="btn btn-outline btn-sm"><i class="fas fa-sync-alt"></i> Yenile</button>
            </div>
        </div>

        <!-- Stat Cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:24px;">
            <div class="stat-box">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="stat-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fas fa-eye"></i></div>
                </div>
                <div class="stat-value"><?php echo $total_online; ?></div>
                <div class="stat-label">Şu An Aktif</div>
            </div>
            <div class="stat-box">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-user"></i></div>
                </div>
                <div class="stat-value"><?php echo $member_count; ?></div>
                <div class="stat-label">Üye</div>
            </div>
            <div class="stat-box">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="stat-icon" style="background:#ecfdf5;color:#10b981;"><i class="fas fa-user-secret"></i></div>
                </div>
                <div class="stat-value"><?php echo $guest_count; ?></div>
                <div class="stat-label">Misafir</div>
            </div>
            <div class="stat-box">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="stat-icon" style="background:#fef2f2;color:#ef4444;"><i class="fas fa-robot"></i></div>
                </div>
                <div class="stat-value"><?php echo $bots_count; ?></div>
                <div class="stat-label">Bot</div>
            </div>
            <div class="stat-box">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="stat-icon" style="background:#fff7ed;color:#f97316;"><i class="fas fa-calendar-day"></i></div>
                </div>
                <div class="stat-value"><?php echo $today_total; ?></div>
                <div class="stat-label">Bugün Toplam</div>
            </div>
            <div class="stat-box">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="stat-icon" style="background:#f1f5f9;color:#64748b;"><i class="fas fa-calendar-minus"></i></div>
                </div>
                <div class="stat-value"><?php echo $yesterday_total; ?></div>
                <div class="stat-label">Dün Toplam</div>
            </div>
            <div class="stat-box">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="stat-icon" style="background:#faf5ff;color:#a855f7;"><i class="fas fa-calendar-week"></i></div>
                </div>
                <div class="stat-value"><?php echo $week_total; ?></div>
                <div class="stat-label">Bu Hafta</div>
            </div>
        </div>

        <!-- Two column: Chart + Sidebar info -->
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
            
            <!-- Hourly Chart -->
            <div class="admin-card">
                <div class="section-title" style="margin-bottom:12px;">Saatlik Trafik <span style="font-weight:500;font-size:12px;color:#94a3b8;">(Son 24 Saat)</span></div>
                <div style="height:200px;"><canvas id="hourlyChart"></canvas></div>
            </div>

            <!-- Country + Device + Top Pages -->
            <div style="display:flex;flex-direction:column;gap:16px;">
                
                <!-- Countries -->
                <?php if(!empty($country_stats)): ?>
                <div class="admin-card admin-card-sm">
                    <div style="font-size:13px;font-weight:800;color:#0f172a;margin-bottom:10px;">Ülkeler</div>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <?php $i=0; foreach($country_stats as $cc => $cnt): if($i++ >= 6) break; ?>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:16px;"><?php echo countryFlag($cc); ?></span>
                            <span style="flex:1;font-size:12px;font-weight:600;color:#334155;"><?php echo countryName($cc); ?></span>
                            <span style="font-size:12px;font-weight:800;color:#0f172a;"><?php echo $cnt; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Device Split -->
                <div class="admin-card admin-card-sm">
                    <div style="font-size:13px;font-weight:800;color:#0f172a;margin-bottom:10px;">Cihaz Dağılımı</div>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <?php 
                        $device_labels = ['mobile'=>['Mobil','fa-mobile-alt','#3b82f6'], 'desktop'=>['Masaüstü','fa-desktop','#10b981'], 'tablet'=>['Tablet','fa-tablet-alt','#f97316'], 'bot'=>['Bot','fa-robot','#ef4444']];
                        foreach($device_labels as $dk => $dl):
                            $dv = $device_stats[$dk] ?? 0;
                            $pct = $total_online > 0 ? round($dv / $total_online * 100) : 0;
                        ?>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <i class="fas <?php echo $dl[1]; ?>" style="width:16px;text-align:center;color:<?php echo $dl[2]; ?>;font-size:12px;"></i>
                            <span style="flex:1;font-size:12px;font-weight:600;color:#334155;"><?php echo $dl[0]; ?></span>
                            <span style="font-size:11px;font-weight:700;color:#94a3b8;"><?php echo $pct; ?>%</span>
                            <span style="font-size:12px;font-weight:800;color:#0f172a;min-width:20px;text-align:right;"><?php echo $dv; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top Pages -->
                <?php if(!empty($page_stats)): ?>
                <div class="admin-card admin-card-sm">
                    <div style="font-size:13px;font-weight:800;color:#0f172a;margin-bottom:10px;">Popüler Sayfalar</div>
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <?php $i=0; foreach($page_stats as $pg => $cnt): if($i++ >= 6) break; ?>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="flex:1;font-size:11px;font-weight:600;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:monospace;"><?php echo htmlspecialchars($pg); ?></span>
                            <span style="font-size:11px;font-weight:800;color:#0f172a;"><?php echo $cnt; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <span style="font-size:14px;font-weight:800;color:#0f172a;margin-right:8px;">Aktif Ziyaretçiler</span>
            <?php
            $tabs = [
                'all' => ['Tümü', $total_online],
                'members' => ['Üyeler', $member_count],
                'guests' => ['Misafirler', $guest_count],
                'bots' => ['Botlar', $bots_count],
            ];
            foreach($tabs as $k => $t):
                $active = ($filter === $k);
            ?>
            <a href="?f=<?php echo $k; ?>" style="padding:6px 12px;border-radius:6px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid <?php echo $active ? '#0f172a' : '#e2e8f0'; ?>;<?php echo $active ? 'background:#0f172a;color:#fff;' : 'background:#fff;color:#64748b;'; ?>"><?php echo $t[0]; ?> <span style="opacity:0.7;">(<?php echo $t[1]; ?>)</span></a>
            <?php endforeach; ?>
        </div>

        <!-- Visitors Table -->
        <div class="admin-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ziyaretçi</th>
                            <th>Ülke</th>
                            <th>IP</th>
                            <th>Sayfa</th>
                            <th>Cihaz</th>
                            <th style="text-align:right;">Son Görülme</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($visitors as $v): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php if($v['is_bot']): ?>
                                    <div style="width:34px;height:34px;border-radius:8px;background:#fef2f2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;"><i class="fas fa-robot"></i></div>
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:#0f172a;"><?php echo htmlspecialchars($v['bot_name'] ?? 'Bot'); ?></div>
                                        <div style="font-size:10px;font-weight:700;color:#ef4444;text-transform:uppercase;">Bot</div>
                                    </div>
                                    <?php elseif($v['user_id']): ?>
                                    <img src="<?php echo getAdminAvatar($v['registered_avatar']); ?>" style="width:34px;height:34px;border-radius:8px;object-fit:cover;background:#f1f5f9;flex-shrink:0;" alt="">
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:#0f172a;"><?php echo htmlspecialchars($v['registered_fullname'] ?? $v['registered_name']); ?></div>
                                        <div style="font-size:10px;font-weight:700;color:#3b82f6;">@<?php echo htmlspecialchars($v['registered_name']); ?></div>
                                    </div>
                                    <?php else: ?>
                                    <div style="width:34px;height:34px;border-radius:8px;background:#f1f5f9;color:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;"><i class="fas fa-user"></i></div>
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:#0f172a;">Misafir</div>
                                        <div style="font-size:10px;font-weight:700;color:#10b981;">Ziyaretçi</div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="font-size:16px;"><?php echo countryFlag($v['country_code']); ?></span>
                                    <span style="font-size:12px;font-weight:600;color:#334155;"><?php echo countryName($v['country_code']); ?></span>
                                </div>
                            </td>
                            <td><span style="font-family:monospace;font-size:11px;font-weight:600;color:#64748b;background:#f8fafc;padding:3px 8px;border-radius:4px;"><?php echo htmlspecialchars($v['ip_address']); ?></span></td>
                            <td>
                                <div style="max-width:200px;">
                                    <div style="font-size:12px;font-weight:600;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($v['current_page']); ?>"><?php echo htmlspecialchars($v['current_page']); ?></div>
                                    <?php if(!empty($v['referer'])): ?>
                                    <div style="font-size:10px;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($v['referer']); ?>">&larr; <?php echo parse_url($v['referer'], PHP_URL_HOST); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $icon = 'fa-desktop'; $ic = '#64748b';
                                if(($v['device_type'] ?? '') == 'mobile') { $icon = 'fa-mobile-alt'; $ic = '#3b82f6'; }
                                if(($v['device_type'] ?? '') == 'tablet') { $icon = 'fa-tablet-alt'; $ic = '#f97316'; }
                                if(($v['device_type'] ?? '') == 'bot') { $icon = 'fa-robot'; $ic = '#ef4444'; }
                                ?>
                                <span style="display:flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:<?php echo $ic; ?>;"><i class="fas <?php echo $icon; ?>"></i> <?php echo ucfirst($v['device_type'] ?? 'desktop'); ?></span>
                            </td>
                            <td style="text-align:right;">
                                <div style="font-size:13px;font-weight:800;color:#0f172a;"><?php echo date('H:i:s', strtotime($v['last_activity'])); ?></div>
                                <div style="font-size:10px;color:#94a3b8;font-weight:600;"><?php echo date('d M', strtotime($v['last_activity'])); ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($visitors)): ?>
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-ghost"></i><p>Bu filtrede ziyaretçi yok.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <style>
        @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }
        @media (max-width: 1023px) {
            div[style*="grid-template-columns:2fr 1fr"] { grid-template-columns: 1fr !important; }
            div[style*="grid-template-columns:repeat(auto-fit,minmax(150px"] { grid-template-columns: repeat(auto-fit,minmax(120px,1fr)) !important; }
        }
    </style>

    <script>
    // Hourly chart
    var ctx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($hourly, 'hour')); ?>,
            datasets: [{
                label: 'Ziyaretçi',
                data: <?php echo json_encode(array_column($hourly, 'count')); ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 3,
                barPercentage: 0.7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { family: 'Outfit', weight: '700', size: 11 },
                    bodyFont: { family: 'Outfit', size: 11 },
                    padding: 8,
                    cornerRadius: 6
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family:'Outfit', size:10, weight:'600' }, color:'#94a3b8', maxRotation:0 } },
                y: { grid: { color:'#f1f5f9' }, ticks: { font: { family:'Outfit', size:10, weight:'600' }, color:'#94a3b8', stepSize:1 }, beginAtZero:true }
            }
        }
    });

    // Auto-refresh every 60 seconds
    setTimeout(function(){ location.reload(); }, 60000);
    </script>
</body>
</html>
