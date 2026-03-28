<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: events");
    exit();
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$event = $stmt->fetch();

// Also allow admins to edit
if (!$event && isset($_SESSION['badge']) && in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
}

if (!$event) {
    die("Etkinlik bulunamadı veya düzenleme yetkiniz yok.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $category = $_POST['category'];
    $event_location = trim($_POST['event_location'] ?? '');
    
    // Image Upload
    $image_url = $event['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $image_info = getimagesize($file_tmp);
        
        if ($image_info) {
            $mime = $image_info['mime'];
            $src_img = null;
            
            switch($mime) {
                case 'image/jpeg': $src_img = imagecreatefromjpeg($file_tmp); break;
                case 'image/png': $src_img = imagecreatefrompng($file_tmp); break;
                case 'image/webp': $src_img = imagecreatefromwebp($file_tmp); break;
            }
            
            if ($src_img) {
                // Resize logic (Target 800px width for optimized display)
                $orig_w = imagesx($src_img);
                $orig_h = imagesy($src_img);
                $target_w = 800;
                
                if ($orig_w > $target_w) {
                    $target_h = floor($orig_h * ($target_w / $orig_w));
                } else {
                    $target_w = $orig_w;
                    $target_h = $orig_h;
                }
                
                $dst_img = imagecreatetruecolor($target_w, $target_h);
                
                // Preserve transparency
                if ($mime == 'image/png' || $mime == 'image/webp') {
                    imagealphablending($dst_img, false);
                    imagesavealpha($dst_img, true);
                    $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
                    imagefilledrectangle($dst_img, 0, 0, $target_w, $target_h, $transparent);
                }
                
                imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $target_w, $target_h, $orig_w, $orig_h);
                
                require_once 'includes/optimize_upload.php';
                
                // Save main WebP
                $new_name = uniqid() . '.webp';
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (imagewebp($dst_img, $upload_dir . $new_name, 80)) {
                    $image_url = 'uploads/' . $new_name;

                    // Create Thumbnail for Index (413x168)
                    $thumb_width = 413;
                    $thumb_height = 168;
                    $thumb_path = $upload_dir . str_replace('.webp', '_thumb.webp', $new_name);
                    createThumbnail($upload_dir . $new_name, $thumb_path, $thumb_width, $thumb_height);
                }
                
                imagedestroy($src_img);
                imagedestroy($dst_img);
            }
        }
    }

    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    $sql = "UPDATE events SET title=?, description=?, event_date=?, start_time=?, category=?, image_url=?, event_location=?, latitude=?, longitude=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $description, $date, $time, $category, $image_url, $event_location, $latitude, $longitude, $id]);

    header("Location: event_detail?id=" . $id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlik Düzenle | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>body { font-family: 'Outfit', sans-serif; } #map-picker { height: 300px; width: 100%; border-radius: 0.75rem; z-index: 1; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-3xl mx-auto px-6 pt-24">
        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-xl border border-slate-100 dark:border-slate-700">
            <div class="flex justify-between items-center mb-8 border-b border-slate-100 dark:border-slate-700 pb-6">
                <h1 class="text-2xl font-black">Etkinliği Düzenle</h1>
                <a href="event_detail?id=<?php echo $id; ?>" class="text-sm font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    <i class="fas fa-times mr-1"></i> İptal
                </a>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Hidden Coords -->
                <input type="hidden" name="latitude" id="lat" value="<?php echo htmlspecialchars($event['latitude'] ?? ''); ?>">
                <input type="hidden" name="longitude" id="lng" value="<?php echo htmlspecialchars($event['longitude'] ?? ''); ?>">

                <!-- Title -->
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Etkinlik Başlığı</label>
                    <input type="text" 
                           name="title" 
                           value="<?php echo htmlspecialchars($event['title']); ?>" 
                           required 
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors font-bold" 
                           placeholder="Örn: Canlı Jazz Gecesi">
                </div>

                <!-- Date & Time -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Tarih</label>
                        <input type="date" 
                               name="date" 
                               value="<?php echo htmlspecialchars($event['event_date']); ?>" 
                               required 
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-600 dark:text-slate-300 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Saat</label>
                        <input type="time" 
                               name="time" 
                               value="<?php echo date('H:i', strtotime($event['start_time'])); ?>" 
                               required 
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-600 dark:text-slate-300 font-bold">
                    </div>
                </div>

                <!-- End Date & Time -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Bitiş Tarihi (Opsiyonel)</label>
                        <input type="date" 
                               name="end_date" 
                               value="<?php echo htmlspecialchars($event['end_date'] ?? ''); ?>" 
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-600 dark:text-slate-300 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Bitiş Saati (Opsiyonel)</label>
                        <input type="time" 
                               name="end_time" 
                               value="<?php echo $event['end_time'] ? date('H:i', strtotime($event['end_time'])) : ''; ?>" 
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-600 dark:text-slate-300 font-bold">
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Konum <span class="text-slate-400 font-normal">(Haritadan Seçin)</span></label>
                    <input type="text" 
                           name="event_location" 
                           id="locInput" 
                           value="<?php echo htmlspecialchars($event['event_location'] ?? ''); ?>" 
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors mb-3 font-bold" 
                           placeholder="Örn: Kalkan Merkez, Kalamar Yolu">
                    <div id="map-picker" class="overflow-hidden shadow-inner border border-slate-200 dark:border-slate-700"></div>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Kategori</label>
                    <select name="category" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-600 dark:text-slate-300 font-bold">
                        <?php
                        $cats = ['Music' => 'Canlı Müzik 🎵', 'Party' => 'Parti & DJ 🎧', 'Food' => 'Yemek & Tadım 🍷', 'Other' => 'Diğer ✨'];
                        foreach($cats as $k => $v) {
                            $sel = ($event['category'] == $k) ? 'selected' : '';
                            echo "<option value='$k' $sel>$v</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Açıklama</label>
                    <textarea name="description" rows="4" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors font-medium"><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <!-- Image -->
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2 font-bold">Etkinlik Afişi (Değiştirmek için seçin)</label>
                    <?php if($event['image_url']): ?>
                        <div class="mb-3 relative group inline-block">
                            <img src="<?php echo $event['image_url']; ?>" class="h-32 w-auto rounded-xl object-cover shadow-md">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-violet-600 file:text-white hover:file:bg-violet-500">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 text-white font-black py-4 rounded-xl shadow-lg shadow-violet-500/30 transform active:scale-95 transition-all mt-4 text-lg">
                    Değişiklikleri Kaydet
                </button>
            </form>
        </div>
    </main>

    <script>
        // Init Map
        const map = L.map('map-picker');
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 20
        }).addTo(map);

        let marker;
        // Check if existing coords
        const lat = "<?php echo $event['latitude'] ?? ''; ?>";
        const lng = "<?php echo $event['longitude'] ?? ''; ?>";

        if (lat && lng) {
            map.setView([lat, lng], 15);
            marker = L.marker([lat, lng]).addTo(map);
        } else {
            map.setView([36.2662, 29.4124], 14);
        }

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
