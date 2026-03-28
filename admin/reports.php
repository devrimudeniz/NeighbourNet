<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once 'auth_session.php';

$admin_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'pending';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['action'];
    
    if ($action === 'dismiss') {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$admin_id, $report_id]);
    } elseif ($action === 'delete_post') {
        $r_stmt = $pdo->prepare("SELECT post_id FROM reports WHERE id = ?");
        $r_stmt->execute([$report_id]);
        $post_id = $r_stmt->fetchColumn();
        if ($post_id) {
            $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id]);
        }
        $stmt = $pdo->prepare("UPDATE reports SET status = 'actioned', action_taken = 'Post deleted', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$admin_id, $report_id]);
    } elseif ($action === 'warn_user') {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'actioned', action_taken = 'User warned', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$admin_id, $report_id]);
    }
    header("Location: reports.php?status={$status_filter}");
    exit;
}

$sql = "SELECT r.*, 
        reporter.username as reporter_username, reporter.full_name as reporter_name,
        reported.username as reported_username, reported.full_name as reported_name,
        p.content as post_content, p.media_url as post_media
        FROM reports r
        LEFT JOIN users reporter ON r.reporter_id = reporter.id
        LEFT JOIN users reported ON r.reported_user_id = reported.id
        LEFT JOIN posts p ON r.post_id = p.id
        WHERE r.status = ?
        ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$status_filter]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_stmt = $pdo->query("SELECT SUM(status='pending') as pending, SUM(status='actioned') as actioned, SUM(status='dismissed') as dismissed FROM reports");
$counts = $count_stmt->fetch(PDO::FETCH_ASSOC);

$reason_labels = ['spam'=>'Spam','harassment'=>'Taciz/Zorbalık','hate_speech'=>'Nefret Söylemi','nudity'=>'Müstehcen İçerik','violence'=>'Şiddet','misinformation'=>'Yanlış Bilgi','other'=>'Diğer'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şikayetler | Admin</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div style="margin-bottom:24px;">
            <h1 class="page-title">Şikayetler</h1>
            <p class="page-subtitle">Kullanıcı şikayetlerini inceleyin ve işlem yapın.</p>
        </div>

        <!-- Status Tabs -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
            <?php
            $tabs = [
                'pending' => ['Bekleyen', $counts['pending'] ?? 0, '#f97316'],
                'actioned' => ['İşlem Yapıldı', $counts['actioned'] ?? 0, '#10b981'],
                'dismissed' => ['Reddedilen', $counts['dismissed'] ?? 0, '#64748b'],
            ];
            foreach($tabs as $key => $tab):
                $active = ($status_filter === $key);
            ?>
            <a href="?status=<?php echo $key; ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;border:1px solid <?php echo $active ? $tab[2] : '#e2e8f0'; ?>;<?php echo $active ? "background:{$tab[2]};color:#fff;" : 'background:#fff;color:#334155;'; ?>">
                <?php echo $tab[0]; ?>
                <span style="background:<?php echo $active ? 'rgba(255,255,255,0.25)' : '#f1f5f9'; ?>;padding:2px 8px;border-radius:6px;font-size:11px;"><?php echo $tab[1]; ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Reports -->
        <?php if(count($reports) > 0): ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach($reports as $report): ?>
            <div class="admin-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px;">
                    <div>
                        <span class="status-pill status-pending" style="margin-bottom:6px;display:inline-block;"><?php echo $reason_labels[$report['reason']] ?? $report['reason']; ?></span>
                        <p style="font-size:13px;color:#64748b;margin:4px 0 0;">
                            <strong style="color:#334155;"><?php echo htmlspecialchars($report['reporter_name'] ?? $report['reporter_username']); ?></strong> tarafından şikayet edildi
                        </p>
                        <p style="font-size:11px;color:#94a3b8;margin:2px 0 0;"><?php echo date('d.m.Y H:i', strtotime($report['created_at'])); ?></p>
                    </div>
                    <?php if($status_filter === 'pending'): ?>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                            <button type="submit" name="action" value="dismiss" class="btn btn-outline btn-sm">Reddet</button>
                        </form>
                        <?php if($report['post_id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Gönderiyi silmek istediğinize emin misiniz?')">
                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                            <button type="submit" name="action" value="delete_post" class="btn btn-danger btn-sm">Gönderiyi Sil</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span style="font-size:11px;color:#94a3b8;font-weight:600;"><?php echo $report['action_taken'] ?? 'Reddedildi'; ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if(!empty($report['description'])): ?>
                <div style="background:#f8fafc;border-radius:8px;padding:10px 12px;font-size:13px;color:#334155;margin-bottom:12px;">
                    <strong>Açıklama:</strong> <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if($report['post_id'] && !empty($report['post_content'])): ?>
                <div style="border-top:1px solid #f1f5f9;padding-top:12px;margin-top:8px;">
                    <p style="font-size:11px;color:#94a3b8;margin:0 0 6px;font-weight:600;">Şikayet Edilen Gönderi:</p>
                    <div style="background:#f8fafc;border-radius:8px;padding:10px 12px;">
                        <p style="font-size:13px;color:#334155;margin:0;" class="truncate-2"><?php echo nl2br(htmlspecialchars($report['post_content'])); ?></p>
                        <?php if(!empty($report['post_media'])): ?>
                        <img src="/<?php echo htmlspecialchars($report['post_media']); ?>" style="margin-top:8px;border-radius:6px;max-height:100px;object-fit:cover;" loading="lazy" alt="">
                        <?php endif; ?>
                        <a href="/post_detail?id=<?php echo $report['post_id']; ?>" target="_blank" style="font-size:12px;color:#3b82f6;font-weight:700;text-decoration:none;display:inline-block;margin-top:6px;">Gönderiyi Görüntüle &rarr;</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="fas fa-check-circle" style="color:#10b981;"></i><p><?php echo $status_filter === 'pending' ? 'Bekleyen şikayet yok!' : 'Bu kategoride şikayet yok.'; ?></p></div>
        <?php endif; ?>
    </main>
</body>
</html>
