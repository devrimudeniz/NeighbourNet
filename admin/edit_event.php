<?php
require_once 'auth_session.php';
require_once '../includes/db.php';
require_once '../includes/optimize_upload.php';

if (!isset($_GET['id'])) {
    header("Location: index");
    exit();
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Etkinlik bulunamadı veya yetkiniz yok.");
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
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        // Use optimizer - Absolute Path
        $upload_dir = __DIR__ . '/../uploads/';
        $result = gorselOptimizeEt($_FILES['image'], $upload_dir);
        
        if (isset($result['success'])) {
            $image_url = '/uploads/' . $result['filename'];
            
            // Create Thumbnail for Index (413x168)
            $thumb_width = 413;
            $thumb_height = 168;
            $thumb_path = str_replace('.webp', '_thumb.webp', $result['path']);
            
            // DEBUG: Stop and show what happened
            echo "<h1>DEBUG MODE</h1>";
            echo "path original: " . $result['path'] . "<br>";
            echo "path thumb: " . $thumb_path . "<br>";
            echo "Calling createThumbnail...<br>";
            
            $res = createThumbnail($result['path'], $thumb_path, $thumb_width, $thumb_height);
            
            echo "Result: " . ($res ? 'TRUE' : 'FALSE') . "<br>";
            if (!$res) {
                echo "Log file: " . __DIR__ . '/../debug_thumb.log';
            }
            exit;
            
            // createThumbnail($result['path'], $thumb_path, $thumb_width, $thumb_height);
        }
    }

    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;

    $sql = "UPDATE events SET title=?, description=?, event_date=?, start_time=?, end_date=?, end_time=?, category=?, image_url=?, event_location=?, latitude=?, longitude=? WHERE id=? AND user_id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $description, $date, $time, $end_date, $end_time, $category, $image_url, $event_location, $latitude, $longitude, $id, $user_id]);

    header("Location: index");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etkinlik Düzenle | Kalkan Social</title>
    <?php include '../includes/header_css.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>body { font-family: 'Outfit', sans-serif; } #map-picker { height: 300px; width: 100%; border-radius: 0.75rem; z-index: 1; }</style>
</head>
<body class="bg-slate-900 text-white min-h-screen p-6 flex items-center justify-center">

    <div class="max-w-2xl w-full bg-slate-800 rounded-2xl border border-slate-700 p-8 shadow-2xl">
        <div class="flex justify-between items-center mb-8 border-b border-slate-700 pb-6">
            <h2 class="text-2xl font-bold">Etkinliği Düzenle</h2>
            <a href="index" class="text-slate-400 hover:text-white transition-colors">Geri Dön</a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <!-- Hidden Coords -->
            <input type="hidden" name="latitude" id="lat" value="<?php echo $event['latitude']; ?>">
            <input type="hidden" name="longitude" id="lng" value="<?php echo $event['longitude']; ?>">

            <!-- Title -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Etkinlik Başlığı</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors" placeholder="Örn: Canlı Jazz Gecesi">
            </div>

            <!-- Date & Time -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Tarih</label>
                    <input type="date" name="date" value="<?php echo $event['event_date']; ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Saat</label>
                    <input type="time" name="time" value="<?php echo date('H:i', strtotime($event['start_time'])); ?>" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
            </div>

            <!-- End Date & Time -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Bitiş Tarihi (Opsiyonel)</label>
                    <input type="date" name="end_date" value="<?php echo $event['end_date']; ?>" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Bitiş Saati (Opsiyonel)</label>
                    <input type="time" name="end_time" value="<?php echo $event['end_time'] ? date('H:i', strtotime($event['end_time'])) : ''; ?>" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                </div>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Konum <span class="text-slate-400">(Haritadan Seçin)</span></label>
                <input type="text" name="event_location" id="locInput" value="<?php echo htmlspecialchars($event['event_location'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors mb-3" placeholder="Örn: Kalkan Merkez, Kalamar Yolu">
                <div id="map-picker"></div>
            </div>

            <!-- Category -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Kategori</label>
                <select name="category" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-slate-300">
                    <?php
                    $cats = [
                        'Music' => 'Canlı Müzik 🎵', 
                        'Party' => 'Parti & DJ 🎧', 
                        'Food' => 'Yemek & Tadım 🍷', 
                        'Boat Trip' => 'Tekne Turları ⚓',
                        'Market' => 'Pazar Yeri 🛍️',
                        'Wellness' => 'Sağlık & Spor 🧘',
                        'Other' => 'Diğer ✨'
                    ];
                    foreach($cats as $k => $v) {
                        $sel = ($event['category'] == $k) ? 'selected' : '';
                        echo "<option value='$k' $sel>$v</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Açıklama</label>
                <textarea name="description" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors"><?php echo htmlspecialchars($event['description']); ?></textarea>
            </div>

            <!-- Image -->
            <div>
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Etkinlik Afişi (Değiştirmek için seçin)</label>
                <?php if($event['image_url']): ?>
                    <img src="<?php echo $event['image_url']; ?>" class="h-20 w-auto rounded-lg mb-2 object-cover">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-violet-600 file:text-white hover:file:bg-violet-500">
            </div>

            <button type="submit" class="w-full bg-violet-600 hover:bg-violet-500 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition-all mt-4">
                Güncelle
            </button>
        </form>
    </div>

    <script>
        // Init Map
        const map = L.map('map-picker');
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 20
        }).addTo(map);

        let marker;
        // Check if existing coords
        const lat = "<?php echo $event['latitude']; ?>";
        const lng = "<?php echo $event['longitude']; ?>";

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
