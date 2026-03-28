<?php
/**
 * Admin Sidebar v2 - Clean, professional, grouped
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/lang.php";
require_once __DIR__ . "/../../includes/site_settings.php";

// Security
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'] ?? '', ['founder', 'moderator'])) {
    header("Location: ../index");
    exit();
}

// Counts (single query for performance)
try {
    $counts = [];
    $counts['verifications'] = (int)$pdo->query("SELECT COUNT(*) FROM verification_requests WHERE status='pending'")->fetchColumn();
    $counts['experts'] = (int)$pdo->query("SELECT COUNT(*) FROM expert_applications WHERE status='pending'")->fetchColumn();
    $counts['subdomains'] = (int)$pdo->query("SELECT COUNT(*) FROM business_listings WHERE subdomain IS NOT NULL AND subdomain_status='pending'")->fetchColumn();
    $counts['events'] = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetchColumn();
    $counts['properties'] = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='pending'")->fetchColumn();
    $counts['listings'] = (int)$pdo->query("SELECT COUNT(*) FROM marketplace_listings WHERE status='pending'")->fetchColumn();
    $counts['boats'] = (int)$pdo->query("SELECT COUNT(*) FROM boat_trips WHERE status='pending'")->fetchColumn();
    $counts['catdex'] = (int)$pdo->query("SELECT COUNT(*) FROM user_cat_collection WHERE status='pending' AND user_photo IS NOT NULL")->fetchColumn();
    $counts['reports'] = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
    $counts['groups'] = (int)$pdo->query("SELECT COUNT(*) FROM `groups` WHERE status='pending'")->fetchColumn();
    $counts['online'] = (int)$pdo->query("SELECT COUNT(*) FROM visitor_activity WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();
} catch(Exception $e) {
    $counts = array_fill_keys(['verifications','experts','subdomains','events','properties','listings','boats','catdex','reports','groups','online'], 0);
}

// Avatar helper
if (!function_exists('getAdminAvatar')) {
    function getAdminAvatar($path) {
        if (empty($path)) return 'https://ui-avatars.com/api/?name=A&background=e2e8f0&color=64748b&bold=true';
        if (preg_match('/^https?:\/\//', $path)) return $path;
        if ($path[0] === '/') return $path;
        return '/' . ltrim($path, './');
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
$total_pending = $counts['verifications'] + $counts['experts'] + $counts['subdomains'] + $counts['events'] + $counts['properties'] + $counts['listings'] + $counts['boats'] + $counts['catdex'] + $counts['groups'] + $counts['reports'];

// Menu items grouped
$menu = [
    'main' => [
        ['url' => 'index', 'file' => 'index.php', 'icon' => 'fa-th-large', 'label' => 'Dashboard'],
    ],
    'content' => [
        ['url' => 'users', 'file' => 'users.php', 'icon' => 'fa-users', 'label' => 'Kullanıcılar'],
        ['url' => 'posts', 'file' => 'posts.php', 'icon' => 'fa-pen-square', 'label' => 'Gönderiler'],
        ['url' => 'events', 'file' => 'events.php', 'icon' => 'fa-calendar-alt', 'label' => 'Etkinlikler', 'count' => $counts['events']],
        ['url' => 'visitors', 'file' => 'visitors.php', 'icon' => 'fa-chart-line', 'label' => 'Ziyaretçiler', 'count' => $counts['online'], 'count_color' => 'blue'],
    ],
    'approvals' => [
        ['url' => 'verifications', 'file' => 'verifications.php', 'icon' => 'fa-user-check', 'label' => 'Doğrulamalar', 'count' => $counts['verifications']],
        ['url' => 'expert_applications', 'file' => 'expert_applications.php', 'icon' => 'fa-certificate', 'label' => 'Uzman Başvuruları', 'count' => $counts['experts']],
        ['url' => 'subdomain_requests', 'file' => 'subdomain_requests.php', 'icon' => 'fa-globe', 'label' => 'Domain Talepleri', 'count' => $counts['subdomains']],
        ['url' => 'properties', 'file' => 'properties.php', 'icon' => 'fa-building', 'label' => 'Emlak Onayları', 'count' => $counts['properties']],
        ['url' => 'listings', 'file' => 'listings.php', 'icon' => 'fa-store', 'label' => 'Pazar Yeri', 'count' => $counts['listings']],
        ['url' => 'boat_trips', 'file' => 'boat_trips.php', 'icon' => 'fa-ship', 'label' => 'Tekne Turları', 'count' => $counts['boats']],
        ['url' => 'catdex_approvals', 'file' => 'catdex_approvals.php', 'icon' => 'fa-cat', 'label' => 'Catdex Onay', 'count' => $counts['catdex']],
        ['url' => 'groups', 'file' => 'groups.php', 'icon' => 'fa-users-cog', 'label' => 'Gruplar', 'count' => $counts['groups']],
        ['url' => 'reports', 'file' => 'reports.php', 'icon' => 'fa-flag', 'label' => 'Şikayetler', 'count' => $counts['reports'], 'count_color' => 'red'],
    ],
    'manage' => [
        ['url' => 'badges', 'file' => 'badges.php', 'icon' => 'fa-award', 'label' => 'Rozetler'],
        ['url' => 'cats', 'file' => 'cats.php', 'icon' => 'fa-paw', 'label' => 'Kediler'],
        ['url' => 'pharmacies', 'file' => 'pharmacies.php', 'icon' => 'fa-pills', 'label' => 'Eczaneler'],
    ],
    'system' => [
        ['url' => 'site_settings', 'file' => 'site_settings.php', 'icon' => 'fa-sliders-h', 'label' => 'Site Ayarları'],
        ['url' => 'opcache', 'file' => 'opcache.php', 'icon' => 'fa-bolt', 'label' => 'OPcache'],
    ],
];

$section_labels = [
    'main' => '',
    'content' => 'İçerik',
    'approvals' => 'Onaylar',
    'manage' => 'Yönetim',
    'system' => 'Sistem',
];
?>
<?php include __DIR__ . '/admin_styles.php'; ?>
<script>document.body.classList.add('admin-panel');</script>

<!-- Mobile Top Bar -->
<div id="adminMobileBar" style="display:none;position:fixed;top:0;left:0;right:0;z-index:40;background:#0f172a;padding:12px 16px;border-bottom:1px solid #1e293b;">
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <button onclick="toggleAdminSidebar()" style="width:36px;height:36px;border-radius:8px;background:#1e293b;border:none;color:#94a3b8;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
            <img src="../logo.jpg" style="width:28px;height:28px;border-radius:6px;" alt="">
            <span style="color:#fff;font-weight:900;font-size:15px;">Admin</span>
            <?php if($total_pending > 0): ?>
            <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:800;padding:2px 6px;border-radius:8px;"><?php echo $total_pending; ?></span>
            <?php endif; ?>
        </a>
        <img src="<?php echo getAdminAvatar($_SESSION['avatar'] ?? ''); ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover;background:#1e293b;" alt="">
    </div>
</div>

<!-- Overlay -->
<div id="adminSidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:49;" onclick="toggleAdminSidebar()"></div>

<!-- Sidebar -->
<aside id="adminSidebar" style="position:fixed;top:0;left:0;bottom:0;width:260px;background:#0f172a;z-index:50;display:flex;flex-direction:column;border-right:1px solid #1e293b;">
    
    <!-- Logo -->
    <div style="padding:20px 20px 16px;border-bottom:1px solid #1e293b;">
        <a href="index" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
            <img src="../logo.jpg" style="width:32px;height:32px;border-radius:8px;" alt="">
            <div>
                <div style="color:#fff;font-weight:900;font-size:15px;line-height:1.2;"><?php echo htmlspecialchars(site_name()); ?></div>
                <div style="color:#475569;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Admin Panel</div>
            </div>
        </a>
    </div>

    <!-- Nav -->
    <nav style="flex:1;overflow-y:auto;padding:12px;" class="custom-scrollbar">
        <?php foreach($menu as $section => $items): ?>
            <?php if(!empty($section_labels[$section])): ?>
            <div style="padding:16px 12px 6px;font-size:10px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:1.5px;">
                <?php echo $section_labels[$section]; ?>
            </div>
            <?php endif; ?>
            
            <?php foreach($items as $item):
                $is_active = ($current_page === $item['file']);
                $has_count = !empty($item['count']) && $item['count'] > 0;
                $count_bg = '#ef4444';
                if (isset($item['count_color'])) {
                    $colors = ['red'=>'#ef4444','blue'=>'#3b82f6','green'=>'#10b981','orange'=>'#f97316','yellow'=>'#eab308'];
                    $count_bg = $colors[$item['count_color']] ?? '#ef4444';
                }
            ?>
            <a href="<?php echo $item['url']; ?>" style="display:flex;align-items:center;gap:10px;padding:9px 12px;margin:1px 0;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:all 0.15s;<?php echo $is_active ? 'background:#1e293b;color:#fff;' : 'color:#94a3b8;'; ?>" onmouseover="if(!<?php echo $is_active?'true':'false'; ?>){this.style.background='#1e293b';this.style.color='#e2e8f0';}" onmouseout="if(!<?php echo $is_active?'true':'false'; ?>){this.style.background='transparent';this.style.color='#94a3b8';}">
                <i class="fas <?php echo $item['icon']; ?>" style="width:18px;text-align:center;font-size:13px;<?php echo $is_active ? 'color:#3b82f6;' : ''; ?>"></i>
                <span style="flex:1;"><?php echo $item['label']; ?></span>
                <?php if($has_count): ?>
                <span style="background:<?php echo $count_bg; ?>;color:#fff;font-size:10px;font-weight:800;padding:2px 7px;border-radius:6px;min-width:18px;text-align:center;"><?php echo $item['count']; ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <!-- User Footer -->
    <div style="padding:16px;border-top:1px solid #1e293b;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <img src="<?php echo getAdminAvatar($_SESSION['avatar'] ?? ''); ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover;background:#1e293b;" alt="">
            <div style="flex:1;min-width:0;">
                <div style="color:#e2e8f0;font-weight:700;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                <div style="color:#475569;font-size:10px;font-weight:700;text-transform:uppercase;"><?php echo $_SESSION['badge'] ?? 'Admin'; ?></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="<?php echo htmlspecialchars(site_url()); ?>" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;border-radius:8px;background:#1e293b;color:#94a3b8;font-size:12px;font-weight:700;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">
                <i class="fas fa-home"></i> Siteye Git
            </a>
            <a href="logout" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;border-radius:8px;background:#1e293b;color:#94a3b8;font-size:12px;font-weight:700;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.background='#7f1d1d';this.style.color='#fca5a5'" onmouseout="this.style.background='#1e293b';this.style.color='#94a3b8'">
                <i class="fas fa-sign-out-alt"></i> Çıkış
            </a>
        </div>
    </div>
</aside>

<script>
// Responsive sidebar
(function() {
    var mq = window.matchMedia('(max-width: 1023px)');
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminSidebarOverlay');
    var mobileBar = document.getElementById('adminMobileBar');
    var isOpen = false;

    function applyLayout(mobile) {
        if (mobile) {
            mobileBar.style.display = 'block';
            sidebar.style.transform = 'translateX(-100%)';
            sidebar.style.transition = 'transform 0.25s ease';
        } else {
            mobileBar.style.display = 'none';
            sidebar.style.transform = 'translateX(0)';
            sidebar.style.transition = 'none';
            overlay.style.display = 'none';
            isOpen = false;
        }
    }

    window.toggleAdminSidebar = function() {
        isOpen = !isOpen;
        sidebar.style.transform = isOpen ? 'translateX(0)' : 'translateX(-100%)';
        overlay.style.display = isOpen ? 'block' : 'none';
        document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    mq.addEventListener('change', function(e) { applyLayout(e.matches); });
    applyLayout(mq.matches);
})();
</script>
