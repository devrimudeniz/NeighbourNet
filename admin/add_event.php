<?php
require_once 'auth_session.php';
require_once '../includes/db.php';
require_once '../includes/optimize_upload.php';
require_once '../includes/site_settings.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Double check role - security measure
    if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'venue' && $_SESSION['role'] != 'admin')) {
        header("Location: ../index");
        exit();
    }
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $category = $_POST['category'];
    $event_location = trim($_POST['event_location'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Ensure event_location column exists
    try {
        $check_col = $pdo->query("SHOW COLUMNS FROM events LIKE 'event_location'");
        if (!$check_col->fetch()) {
            $pdo->exec("ALTER TABLE events ADD COLUMN event_location VARCHAR(255) DEFAULT NULL AFTER image_url");
        }
    } catch (PDOException $e) {
        // Column might already exist
    }
    
    // Image Upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        // Use optimizer
        $upload_dir = '../uploads/';
        $result = gorselOptimizeEt($_FILES['image'], $upload_dir);
        
        if (isset($result['success'])) {
            $image_url = '/uploads/' . $result['filename'];
            
            // Create Thumbnail for Index (413x168)
            $thumb_width = 413;
            $thumb_height = 168;
            $thumb_path = str_replace('.webp', '_thumb.webp', $result['path']);
            createThumbnail($result['path'], $thumb_path, $thumb_width, $thumb_height);
        }
    }

    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    $sql = "INSERT INTO events (user_id, title, description, event_date, start_time, end_date, end_time, category, image_url, event_location, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $title, $description, $date, $time, $end_date, $end_time, $category, $image_url, $event_location, $latitude, $longitude]);
    $event_id = $pdo->lastInsertId();

    // Notify "events" subscribers (in-app + email/push per prefs)
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_email'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER service");
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_push TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_email");
        }
        require_once __DIR__ . '/../includes/push-helper.php';
        require_once __DIR__ . '/../includes/email-helper.php';
        $ev_title = 'Yeni etkinlik: ' . $title;
        $ev_body = 'Tarih: ' . date('d.m.Y', strtotime($date)) . ' ' . $time . ($event_location ? ' · ' . $event_location : '');
        $ev_url = '/events' . ($event_id ? '?id=' . (int)$event_id : '');
        $notif_msg = $ev_title . ' - ' . $ev_body;
        $n_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) SELECT user_id, 'event', ?, ? FROM subscriptions WHERE service = 'events'");
        $n_stmt->execute([$notif_msg, 'events']);
        $sub_stmt = $pdo->query("SELECT user_id, COALESCE(notify_email, 1) AS ne, COALESCE(notify_push, 1) AS np FROM subscriptions WHERE service = 'events'");
        $subs = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($subs as $s) {
            $uid = (int)$s['user_id'];
            if (!empty($s['np'])) {
                sendPushNotification($uid, $ev_title, $ev_body, $ev_url);
            }
            if (!empty($s['ne'])) {
                $em = $pdo->prepare("SELECT email FROM users WHERE id = ? AND email IS NOT NULL AND email != ''");
                $em->execute([$uid]);
                $row = $em->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $site = site_url();
                    $html = "<p>$ev_title</p><p>$ev_body</p><p><a href=\"$site$ev_url\">Etkinliği görüntüle</a></p>";
                    sendEmail($row['email'], $ev_title . ' - ' . site_name(), $html);
                }
            }
        }
    } catch (Exception $e) { error_log('Events subscriber notify: ' . $e->getMessage()); }

    // Redirect based on protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = dirname(dirname($_SERVER['SCRIPT_NAME']));
    if ($script_dir == '/') $script_dir = '';
    header("Location: " . $protocol . $host . $script_dir . "/index");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlik Ekle | <?php echo htmlspecialchars(site_name()); ?></title>
    <?php include '../includes/header_css.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>body { font-family: 'Outfit', sans-serif; } #map-picker { height: 300px; width: 100%; border-radius: 0.75rem; z-index: 1; }</style>
</head>
<body class="bg-slate-900 text-white min-h-screen p-6 flex items-center justify-center">

    <div class="max-w-2xl w-full bg-slate-800 rounded-2xl border border-slate-700 p-8 shadow-2xl">
        <div class="flex justify-between items-center mb-8 border-b border-slate-700 pb-6">
            <h2 class="text-2xl font-bold">Yeni Etkinlik Ekle</h2>
            <a href="../index" class="text-slate-400 hover:text-white transition-colors">Ana Sayfaya Dön</a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <!-- Hidden Coords -->
            <input type="hidden" name="latitude" id="lat">
            <input type="hidden" name="longitude" id="lng">

            <!-- Title -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Etkinlik Başlığı</label>
                <input type="text" name="title" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors" placeholder="Örn: Canlı Jazz Gecesi">
            </div>

            <!-- Date & Time -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Tarih</label>
                    <input type="date" name="date" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Saat</label>
                    <input type="time" name="time" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
            </div>
            
            <!-- End Date & Time -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Bitiş Tarihi (Opsiyonel)</label>
                    <input type="date" name="end_date" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Bitiş Saati (Opsiyonel)</label>
                    <input type="time" name="end_time" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Konum <span class="text-slate-400">(Haritadan Seçin)</span></label>
                <input type="text" name="event_location" id="locInput" value="<?php echo htmlspecialchars($_SESSION['location'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors mb-3" placeholder="Örn: Kalkan Merkez, Kalamar Yolu">
                <div id="map-picker"></div>
                <p class="text-xs text-slate-400 mt-1">Haritaya tıklayarak tam konumu işaretleyin.</p>
            </div>

            <!-- Category -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Kategori</label>
                <select name="category" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                    <option value="Music">Canlı Müzik 🎵</option>
                    <option value="Party">Parti & DJ 🎧</option>
                    <option value="Food">Yemek & Tadım 🍷</option>
                    <option value="Boat Trip">Tekne Turları ⚓</option>
                    <option value="Market">Pazar Yeri 🛍️</option>
                    <option value="Wellness">Sağlık & Spor 🧘</option>
                    <option value="Other">Diğer ✨</option>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Açıklama</label>
                <textarea name="description" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors" placeholder="Etkinlik detaylarından bahset..."></textarea>
            </div>

            <!-- Image -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Etkinlik Afişi</label>
                <input type="file" name="image" accept="image/*" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-violet-600 file:text-white hover:file:bg-violet-500">
            </div>

            <button type="submit" class="w-full bg-violet-600 hover:bg-violet-500 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition-all mt-4">
                Yayınla
            </button>
        </form>
    </div>

    <script>
        // Init Map
        const map = L.map('map-picker').setView([36.2662, 29.4124], 14);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 20
        }).addTo(map);

        let marker;

        map.on('click', function(e) {
            const { lat, lng } = e.latlng;
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });
    </script>

</body>
</html>
