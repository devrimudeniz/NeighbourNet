<?php
require_once '../includes/db.php';
require_once 'auth_session.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $lat = $_POST['latitude'];
        $lng = $_POST['longitude'];
        $stmt = $pdo->prepare("INSERT INTO pharmacies (name, phone, address, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $address, $lat, $lng]);
        header("Location: pharmacies");
        exit();
    }
    if (isset($_POST['action']) && $_POST['action'] == 'toggle_duty') {
        $id = (int)$_POST['id'];
        $pdo->exec("UPDATE pharmacies SET is_on_duty = 0");
        $pdo->prepare("UPDATE pharmacies SET is_on_duty = 1 WHERE id = ?")->execute([$id]);
        header("Location: pharmacies");
        exit();
    }
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM pharmacies WHERE id = ?")->execute([$id]);
        header("Location: pharmacies");
        exit();
    }
}

$pharmacies = $pdo->query("SELECT * FROM pharmacies ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eczane Yönetimi | Admin</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
            <div>
                <h1 class="page-title">Eczane Yönetimi</h1>
                <p class="page-subtitle">Nöbetçi eczaneyi seçin ve yeni eczane ekleyin.</p>
            </div>
            <a href="fetch_pharmacy_api.php" class="btn btn-primary"><i class="fas fa-sync-alt"></i> API'den Çek</a>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            
            <!-- List -->
            <div>
                <div class="section-title">Kayıtlı Eczaneler</div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach($pharmacies as $p): ?>
                    <div class="admin-card admin-card-sm" style="display:flex;align-items:center;justify-content:space-between;gap:12px;<?php echo $p['is_on_duty'] ? 'border-color:#ef4444;border-width:2px;' : ''; ?>">
                        <div>
                            <div style="font-weight:700;font-size:14px;color:#0f172a;"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div style="font-size:12px;color:#64748b;"><i class="fas fa-phone" style="margin-right:4px;"></i><?php echo htmlspecialchars($p['phone']); ?></div>
                            <?php if($p['is_on_duty']): ?>
                            <span style="display:inline-block;background:#fef2f2;color:#dc2626;font-size:10px;font-weight:800;padding:2px 8px;border-radius:4px;margin-top:4px;text-transform:uppercase;">Nöbetçi</span>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0;">
                            <?php if(!$p['is_on_duty']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_duty">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm">Seç</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($pharmacies)): ?>
                    <div class="empty-state"><i class="fas fa-pills"></i><p>Kayıtlı eczane yok.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add Form -->
            <div class="admin-card" style="height:fit-content;position:sticky;top:80px;">
                <div class="section-title">Yeni Eczane Ekle</div>
                <form method="POST" style="display:flex;flex-direction:column;gap:12px;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="latitude" id="lat">
                    <input type="hidden" name="longitude" id="lng">
                    
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Eczane Adı</label>
                        <input type="text" name="name" required class="admin-input">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Telefon</label>
                        <input type="text" name="phone" required class="admin-input">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Adres</label>
                        <textarea name="address" rows="2" class="admin-input" style="resize:none;"></textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Konum (haritaya tıklayın)</label>
                        <div id="map-picker" style="height:200px;border-radius:8px;border:1px solid #e2e8f0;"></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="justify-content:center;">Ekle</button>
                </form>
            </div>
        </div>
    </main>

    <script>
    var map = L.map('map-picker').setView([36.2662, 29.4124], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OSM'}).addTo(map);
    var marker;
    map.on('click', function(e) {
        document.getElementById('lat').value = e.latlng.lat;
        document.getElementById('lng').value = e.latlng.lng;
        if(marker) marker.setLatLng(e.latlng);
        else marker = L.marker(e.latlng).addTo(map);
    });
    // Fix map rendering after sidebar loads
    setTimeout(function(){ map.invalidateSize(); }, 300);
    </script>

    <style>
    @media (max-width: 1023px) {
        div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
    }
    </style>
</body>
</html>
