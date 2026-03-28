<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once 'auth_session.php';

// $lang is already set by lang.php

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['business_id'])) {
    $business_id = (int)$_POST['business_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE business_listings SET subdomain_status = 'approved', subdomain_approved_at = NOW(), subdomain_approved_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $business_id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE business_listings SET subdomain_status = 'rejected', subdomain = NULL, subdomain_requested_at = NULL WHERE id = ?");
        $stmt->execute([$business_id]);
    }
    header('Location: subdomain_requests.php');
    exit();
}

$pending = $pdo->query("SELECT bl.*, u.username, u.full_name FROM business_listings bl JOIN users u ON bl.owner_id = u.id WHERE bl.subdomain IS NOT NULL AND bl.subdomain_status = 'pending' ORDER BY bl.subdomain_requested_at DESC")->fetchAll();
$approved = $pdo->query("SELECT bl.*, u.username, u.full_name, a.username as approver_username FROM business_listings bl JOIN users u ON bl.owner_id = u.id LEFT JOIN users a ON bl.subdomain_approved_by = a.id WHERE bl.subdomain IS NOT NULL AND bl.subdomain_status = 'approved' ORDER BY bl.subdomain_approved_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alan Adı Talepleri | Admin</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div style="margin-bottom:24px;">
            <h1 class="page-title">Alan Adı Talepleri</h1>
            <p class="page-subtitle">Subdomain taleplerini onaylayın veya reddedin.</p>
        </div>

        <!-- Pending -->
        <div class="admin-card" style="margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <div class="section-title" style="margin:0;">Bekleyen Talepler</div>
                <?php if(count($pending) > 0): ?>
                <span class="badge-count badge-orange"><?php echo count($pending); ?></span>
                <?php endif; ?>
            </div>

            <?php if(empty($pending)): ?>
            <div class="empty-state" style="padding:32px;"><i class="fas fa-inbox"></i><p>Bekleyen talep yok.</p></div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach($pending as $req): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px;border:1px solid #e2e8f0;border-radius:10px;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:800;font-size:14px;color:#0f172a;"><?php echo htmlspecialchars($req['name']); ?></div>
                        <div style="font-size:12px;color:#64748b;margin:2px 0;">Sahip: <strong><?php echo htmlspecialchars($req['full_name']); ?></strong> (@<?php echo htmlspecialchars($req['username']); ?>)</div>
                        <div style="font-size:15px;font-weight:900;color:#3b82f6;"><?php echo htmlspecialchars($req['subdomain']); ?>.<?php echo htmlspecialchars(preg_replace('/^www\./', '', site_host())); ?></div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Talep: <?php echo date('d M Y H:i', strtotime($req['subdomain_requested_at'])); ?></div>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                        <form method="POST" style="display:inline;"><input type="hidden" name="business_id" value="<?php echo $req['id']; ?>"><input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Onayla</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Reddetmek istediğinize emin misiniz?')"><input type="hidden" name="business_id" value="<?php echo $req['id']; ?>"><input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Reddet</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Approved -->
        <div class="admin-card" style="padding:0;overflow:hidden;">
            <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
                <div class="section-title" style="margin:0;">Onaylanmış Subdomain'ler (<?php echo count($approved); ?>)</div>
            </div>
            <?php if(empty($approved)): ?>
            <div class="empty-state" style="padding:32px;"><p>Henüz onaylanmış subdomain yok.</p></div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead><tr><th>İşletme</th><th>Subdomain</th><th>Sahip</th><th>Onaylayan</th><th>Tarih</th></tr></thead>
                    <tbody>
                        <?php foreach($approved as $item): ?>
                        <tr>
                            <td style="font-weight:700;"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><a href="https://<?php echo htmlspecialchars($item['subdomain']); ?>.<?php echo htmlspecialchars(preg_replace('/^www\./', '', site_host())); ?>" target="_blank" style="color:#3b82f6;font-weight:700;font-family:monospace;text-decoration:none;"><?php echo htmlspecialchars($item['subdomain']); ?>.<?php echo htmlspecialchars(preg_replace('/^www\./', '', site_host())); ?> <i class="fas fa-external-link-alt" style="font-size:10px;"></i></a></td>
                            <td style="color:#64748b;font-size:13px;"><?php echo htmlspecialchars($item['full_name']); ?></td>
                            <td style="color:#64748b;font-size:13px;">@<?php echo htmlspecialchars($item['approver_username'] ?? 'N/A'); ?></td>
                            <td style="color:#94a3b8;font-size:12px;"><?php echo date('d M Y', strtotime($item['subdomain_approved_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
