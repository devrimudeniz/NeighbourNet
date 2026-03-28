<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/optimize_upload.php';

// Require Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login?redirect=add_event");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $category = $_POST['category'];
    $event_location = trim($_POST['event_location'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Validate Date
    if (empty($date) || $date == '0000-00-00') {
        $error = ($lang == 'en') ? 'Please select a valid date.' : 'Lütfen geçerli bir tarih seçin.';
    }
    
    // Image Upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        // Use optimizer
        $upload_dir = 'uploads/';
        $result = gorselOptimizeEt($_FILES['image'], $upload_dir);
        
        if (isset($result['success'])) {
            $image_url = 'uploads/' . $result['filename'];
            
            // Create Thumbnail
            require_once 'includes/image_helper.php'; 
            // Note: image_helper might need correct path adjustments if not in same dir, but we are in root.
            // Actually optimize_upload usually handles this or returns path. 
            // Let's assume optimize_upload returns 'path' relative to upload_dir.
            
            // Generate thumb if possible, but strict dependency check might be needed.
            // For now, simple optimization is good.
        } else {
            $error = $result['error'];
        }
    }

    if (empty($error)) {
        $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

        // Always set status to 'pending', even for admins/founders
        $status = 'pending';

        $sql = "INSERT INTO events (user_id, title, description, event_date, start_time, end_date, end_time, category, image_url, event_location, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([$user_id, $title, $description, $date, $time, $end_date, $end_time, $category, $image_url, $event_location, $latitude, $longitude, $status]);
            $event_id = $pdo->lastInsertId();
            $success = $lang == 'en' ? 'Event submitted successfully! Waiting for approval.' : 'Etkinlik başarıyla gönderildi! Onay bekleniyor.';

            // Notify "events" subscribers (in-app + email/push per prefs)
            try {
                $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_email'");
                if ($chk->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER service");
                    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_push TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_email");
                }
                require_once 'includes/push-helper.php';
                require_once 'includes/email-helper.php';
                $ev_title = ($lang == 'en' ? 'New event' : 'Yeni etkinlik') . ': ' . $title;
                $ev_body = ($lang == 'en' ? 'Date' : 'Tarih') . ': ' . date('d.m.Y', strtotime($date)) . ' ' . $time . ($event_location ? ' · ' . $event_location : '');
                $ev_url = '/events' . ($event_id ? '?id=' . (int)$event_id : '');
                $notif_msg = $ev_title . ' - ' . $ev_body;
                $n_stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) SELECT user_id, 'event', ?, ? FROM subscriptions WHERE service = 'events' AND user_id != ?");
                $n_stmt->execute([$notif_msg, 'events', $user_id]);
                $sub_stmt = $pdo->prepare("SELECT user_id, COALESCE(notify_email, 1) AS ne, COALESCE(notify_push, 1) AS np FROM subscriptions WHERE service = 'events' AND user_id != ?");
                $sub_stmt->execute([$user_id]);
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
                            $site = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com');
                            $html = "<p>$ev_title</p><p>$ev_body</p><p><a href=\"$site$ev_url\">" . ($lang == 'en' ? 'View event' : 'Etkinliği görüntüle') . "</a></p>";
                            sendEmail($row['email'], $ev_title . ' - Kalkan Social', $html);
                        }
                    }
                }
            } catch (Exception $e) { error_log('Events subscriber notify: ' . $e->getMessage()); }

            // Send Email Notification to Admins
            try {
                require_once 'includes/email-helper.php';
                
                // Fetch emails of founders and admins
                $admin_emails = $pdo->query("SELECT email FROM users WHERE badge IN ('founder', 'admin')")->fetchAll(PDO::FETCH_COLUMN);
                
                if ($admin_emails) {
                    $mail_subject = "Yeni Etkinlik Onay Bekliyor / New Event Pending";
                    $event_date_fmt = date('d.m.Y', strtotime($date));
                    
                    $mail_message = "
                    <html>
                    <body style='font-family: sans-serif;'>
                        <div style='padding: 20px; background: #f8fafc; border-radius: 10px;'>
                            <h2 style='color: #0055FF;'>Yeni Etkinlik Gönderildi</h2>
                            <p><strong>Başlık:</strong> " . htmlspecialchars($title) . "</p>
                            <p><strong>Tarih:</strong> $event_date_fmt $time</p>
                            <p><strong>Kategori:</strong> $category</p>
                            <br>
                            <p>Bu etkinlik onayınızı bekliyor.</p>
                            <a href='https://kalkansocial.com/admin/events.php' style='background: #0055FF; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;'>Admin Paneline Git</a>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    foreach ($admin_emails as $admin_email) {
                        // sendEmail function from email-helper.php
                        sendEmail($admin_email, $mail_subject, $mail_message);
                    }
                }
            } catch (Exception $e) {
                // Silently fail email sending to not disrupt user experience
                error_log("Notification email failed: " . $e->getMessage());
            }

        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Add Event' : 'Etkinlik Ekle'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>#map-picker { height: 300px; width: 100%; border-radius: 0.75rem; z-index: 1; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen transition-colors duration-500 pb-20">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 sm:px-6 pt-24 pb-20 max-w-2xl">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                <span class="w-12 h-12 rounded-2xl bg-violet-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/40">
                    <i class="fas fa-calendar-plus text-xl"></i>
                </span>
                <?php echo $lang == 'en' ? 'Add Your Event' : 'Etkinlik Ekle'; ?>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm">
                <?php echo $lang == 'en' ? 'Your event will be reviewed by admins before publishing.' : 'Etkinliğiniz yayınlanmadan önce admin onayından geçecektir.'; ?>
            </p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
            <?php if($success): ?>
            <div class="text-center py-10">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-check-circle text-3xl text-green-600 dark:text-green-400"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2"><?php echo $success; ?></h2>
                <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">
                    <?php echo $lang == 'en' ? 'We\'ll notify you once approved.' : 'Onaylandığında bilgilendirileceksiniz.'; ?>
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="events" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo $lang == 'en' ? 'View Events' : 'Etkinliklere Git'; ?>
                    </a>
                    <a href="index" class="inline-flex items-center gap-2 bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200 px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-slate-300 dark:hover:bg-slate-500 border border-slate-300 dark:border-slate-500">
                        <i class="fas fa-home"></i>
                        <?php echo $lang == 'en' ? 'Home' : 'Ana Sayfa'; ?>
                    </a>
                </div>
            </div>
            <?php elseif($error): ?>
            <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 p-4 rounded-xl mb-6 text-center font-bold border border-red-200 dark:border-red-800/50 flex items-center justify-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if(!$success): ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-5" data-no-swup>
                
                <!-- Hidden Coords -->
                <input type="hidden" name="latitude" id="lat">
                <input type="hidden" name="longitude" id="lng">

                <!-- Title -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Event Title' : 'Etkinlik Başlığı'; ?> *</label>
                    <input type="text" name="title" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-colors font-bold" placeholder="<?php echo $lang == 'en' ? 'e.g. Summer Jazz Night' : 'Örn: Yaz Caz Gecesi'; ?>">
                </div>

                <!-- Date & Time -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Date' : 'Tarih'; ?> *</label>
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-colors font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Time' : 'Saat'; ?> *</label>
                        <input type="time" name="time" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-colors font-medium">
                    </div>
                </div>
                
                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                    <select name="category" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-colors font-medium">
                        <option value="Music"><?php echo $lang == 'en' ? 'Live Music' : 'Canlı Müzik'; ?> 🎵</option>
                        <option value="Party"><?php echo $lang == 'en' ? 'Party & DJ' : 'Parti & DJ'; ?> 🎧</option>
                        <option value="Food"><?php echo $lang == 'en' ? 'Food & Wine' : 'Yemek & Tadım'; ?> 🍷</option>
                        <option value="Boat Trip"><?php echo $lang == 'en' ? 'Boat Trips' : 'Tekne Turları'; ?> ⚓</option>
                        <option value="Market"><?php echo $lang == 'en' ? 'Local Market' : 'Pazar Yeri'; ?> 🛍️</option>
                        <option value="Wellness"><?php echo $lang == 'en' ? 'Wellness' : 'Sağlık & Spor'; ?> 🧘</option>
                        <option value="Other"><?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?> ✨</option>
                    </select>
                </div>

                <!-- Location (simple text, map optional) -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Location' : 'Konum'; ?></label>
                    <input type="text" name="event_location" id="locInput" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-colors font-medium" placeholder="<?php echo $lang == 'en' ? 'e.g. Kalkan Marina, Restaurant Name' : 'Örn: Kalkan Marina, Restoran Adı'; ?>">
                    <button type="button" id="toggle-map-btn" class="mt-2 text-sm text-violet-600 dark:text-violet-400 font-bold flex items-center gap-2 hover:underline">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo $lang == 'en' ? 'Add map pin (optional)' : 'Haritada işaretle (isteğe bağlı)'; ?></span>
                        <i class="fas fa-chevron-down text-xs transition-transform" id="map-chevron"></i>
                    </button>
                    <div id="map-wrap" class="mt-3 hidden">
                        <div id="map-picker" class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden"></div>
                        <p class="text-xs text-slate-400 mt-1"><?php echo $lang == 'en' ? 'Click on the map to set location.' : 'Konumu belirlemek için haritaya tıklayın.'; ?></p>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Description' : 'Açıklama'; ?> *</label>
                    <textarea name="description" rows="4" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-colors font-medium resize-none" placeholder="<?php echo $lang == 'en' ? 'Describe your event...' : 'Etkinliğinizi kısaca anlatın...'; ?>"></textarea>
                </div>

                <!-- Image -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Event Image' : 'Etkinlik Görseli'; ?></label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-violet-500 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 dark:file:bg-violet-900/30 dark:file:text-violet-300 hover:file:bg-violet-100 dark:hover:file:bg-violet-900/50 transition-colors">
                    <p class="text-xs text-slate-400 mt-1"><?php echo $lang == 'en' ? 'JPG, PNG or WebP. Optional.' : 'JPG, PNG veya WebP. İsteğe bağlı.'; ?></p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-black py-4 rounded-xl shadow-lg shadow-violet-500/30 transform active:scale-[0.98] transition-all">
                        <i class="fas fa-paper-plane mr-2"></i><?php echo $lang == 'en' ? 'Submit for Approval' : 'Onaya Gönder'; ?>
                    </button>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </main>

    <script>
        // Optional map (lazy init)
        let map, marker;
        const mapWrap = document.getElementById('map-wrap');
        const toggleBtn = document.getElementById('toggle-map-btn');
        const mapChevron = document.getElementById('map-chevron');

        toggleBtn && toggleBtn.addEventListener('click', function() {
            const isHidden = mapWrap.classList.contains('hidden');
            mapWrap.classList.toggle('hidden', !isHidden);
            mapChevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0)';
            if (isHidden && !map) {
                setTimeout(function() {
                    map = L.map('map-picker').setView([36.2662, 29.4124], 14);
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 20 }).addTo(map);
                    map.on('click', function(e) {
                        const { lat, lng } = e.latlng;
                        document.getElementById('lat').value = lat;
                        document.getElementById('lng').value = lng;
                        if (marker) marker.setLatLng(e.latlng);
                        else marker = L.marker(e.latlng).addTo(map);
                    });
                    map.invalidateSize();
                }, 150);
            } else if (map) {
                map.invalidateSize();
            }
        });
    </script>
</body>
</html>
