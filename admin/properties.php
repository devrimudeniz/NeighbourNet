<?php
require_once '../includes/db.php';
require_once 'auth_session.php';
require_once "../includes/lang.php";

$stmt = $pdo->prepare("SELECT p.*, pi.image_path as main_image, u.username, u.full_name 
                       FROM properties p 
                       LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.status = 'pending'
                       ORDER BY p.created_at DESC");
$stmt->execute();
$pending_properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emlak Onayları | Admin</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div style="margin-bottom:24px;">
            <h1 class="page-title">Emlak Onayları</h1>
            <p class="page-subtitle">Bekleyen emlak ilanlarını inceleyin ve onaylayın.</p>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;">
            <?php foreach($pending_properties as $prop): ?>
            <div id="prop-<?php echo $prop['id']; ?>" class="admin-card" style="display:flex;gap:20px;align-items:flex-start;">
                <div style="width:180px;height:120px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#f1f5f9;">
                    <img src="/<?php echo $prop['main_image'] ?? ''; ?>" style="width:100%;height:100%;object-fit:cover;" alt="" onerror="this.style.display='none'">
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:8px;">
                        <div>
                            <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 4px;"><?php echo htmlspecialchars($prop['title']); ?></h3>
                            <p style="font-size:12px;color:#64748b;margin:0;"><i class="fas fa-map-marker-alt" style="color:#10b981;margin-right:4px;"></i><?php echo htmlspecialchars($prop['location'] ?? ''); ?></p>
                        </div>
                        <span style="font-size:18px;font-weight:900;color:#10b981;white-space:nowrap;"><?php echo $prop['price']; ?> <?php echo $prop['currency'] ?? ''; ?></span>
                    </div>
                    <p style="font-size:13px;color:#64748b;margin:0 0 12px;line-height:1.5;" class="truncate-2"><?php echo htmlspecialchars($prop['description'] ?? ''); ?></p>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="font-size:11px;color:#94a3b8;">@<?php echo htmlspecialchars($prop['username']); ?></span>
                        <div style="margin-left:auto;display:flex;gap:8px;">
                            <a href="/property_detail?id=<?php echo $prop['id']; ?>" target="_blank" class="btn btn-outline btn-sm">Detay</a>
                            <button onclick="approveProperty(<?php echo $prop['id']; ?>)" class="btn btn-success btn-sm">Onayla</button>
                            <button onclick="deleteProperty(<?php echo $prop['id']; ?>)" class="btn btn-danger btn-sm">Sil</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($pending_properties)): ?>
            <div class="empty-state"><i class="fas fa-building"></i><p>Bekleyen emlak ilanı yok.</p></div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function approveProperty(id) {
        var params = new URLSearchParams();
        params.append('action', 'approve');
        params.append('property_id', id);
        fetch('../api/properties.php', { method:'POST', body:params })
        .then(function(r){return r.json();})
        .then(function(data){ if(data.status==='success') document.getElementById('prop-'+id).remove(); else alert(data.message); });
    }
    function deleteProperty(id) {
        if(!confirm('Silmek istediğinize emin misiniz?')) return;
        var params = new URLSearchParams();
        params.append('action', 'delete');
        params.append('property_id', id);
        fetch('../api/properties.php', { method:'POST', body:params })
        .then(function(r){return r.json();})
        .then(function(data){ if(data.status==='success') document.getElementById('prop-'+id).remove(); else alert(data.message); });
    }
    </script>
</body>
</html>
