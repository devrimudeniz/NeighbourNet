<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/lang.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'] ?? '', ['founder', 'moderator'])) {
    header("Location: ../login");
    exit();
}

$msg = '';
$msg_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $group_id = (int)($_POST['group_id'] ?? 0);
    
    if ($group_id > 0) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE `groups` SET status = 'approved', reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?");
            $stmt->execute([$group_id]);
            
            // Notify creator
            $creator = $pdo->prepare("SELECT creator_id FROM `groups` WHERE id = ?");
            $creator->execute([$group_id]);
            $creator_id = $creator->fetchColumn();
            if ($creator_id) {
                $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, content, url, created_at) VALUES (?, ?, 'system', ?, ?, NOW())")
                    ->execute([$creator_id, $_SESSION['user_id'], 'Grubunuz onaylandı! / Your group has been approved!', 'groups']);
            }
            
            $msg = 'Grup onaylandı.';
            $msg_type = 'success';
            
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            $stmt = $pdo->prepare("UPDATE `groups` SET status = 'rejected', reviewed_at = NOW(), rejection_reason = ? WHERE id = ?");
            $stmt->execute([$reason ?: null, $group_id]);
            
            // Notify creator
            $creator = $pdo->prepare("SELECT creator_id FROM `groups` WHERE id = ?");
            $creator->execute([$group_id]);
            $creator_id = $creator->fetchColumn();
            if ($creator_id) {
                $notif_msg = 'Grubunuz reddedildi.' . ($reason ? ' Sebep: ' . $reason : '');
                $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, content, url, created_at) VALUES (?, ?, 'system', ?, ?, NOW())")
                    ->execute([$creator_id, $_SESSION['user_id'], $notif_msg, 'groups']);
            }
            
            $msg = 'Grup reddedildi.';
            $msg_type = 'error';
            
        } elseif ($action === 'delete') {
            // Delete group members first, then the group
            $pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$group_id]);
            $pdo->prepare("DELETE FROM `groups` WHERE id = ?")->execute([$group_id]);
            $msg = 'Grup silindi.';
            $msg_type = 'error';
        }
    }
}

// Filter
$status_filter = $_GET['status'] ?? 'pending';
$allowed_statuses = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($status_filter, $allowed_statuses)) $status_filter = 'pending';

// Get counts
$pending_count = (int)$pdo->query("SELECT COUNT(*) FROM `groups` WHERE status = 'pending'")->fetchColumn();
$approved_count = (int)$pdo->query("SELECT COUNT(*) FROM `groups` WHERE status = 'approved'")->fetchColumn();
$rejected_count = (int)$pdo->query("SELECT COUNT(*) FROM `groups` WHERE status = 'rejected'")->fetchColumn();
$total_count = $pending_count + $approved_count + $rejected_count;

// Fetch groups
$sql = "SELECT g.*, u.username, u.full_name, u.avatar, u.badge,
        (SELECT COUNT(*) FROM `groups` WHERE creator_id = g.creator_id AND status != 'rejected') as user_group_count
        FROM `groups` g 
        JOIN users u ON g.creator_id = u.id";
if ($status_filter !== 'all') {
    $sql .= " WHERE g.status = ?";
}
$sql .= " ORDER BY g.created_at DESC LIMIT 100";

if ($status_filter !== 'all') {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status_filter]);
} else {
    $stmt = $pdo->query($sql);
}
$groups = $stmt->fetchAll();

$cat_labels = ['hobby' => '🎯 Hobby', 'lifestyle' => '🌟 Lifestyle', 'professional' => '💼 Professional', 'marketplace' => '🛒 Marketplace'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grup Yönetimi - Admin</title>
    <link rel="icon" href="../logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">
    <?php include "includes/sidebar.php"; ?>
    
    <main class="admin-main">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
            <div>
                <h1 class="page-title" style="margin:0;">Grup Yönetimi</h1>
                <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Kullanıcıların oluşturduğu grupları incele ve onayla</p>
            </div>
            <?php if($pending_count > 0): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;">
                <i class="fas fa-clock" style="color:#f59e0b;"></i>
                <span style="font-size:13px;font-weight:700;color:#92400e;"><?php echo $pending_count; ?> bekleyen grup</span>
            </div>
            <?php endif; ?>
        </div>

        <?php if($msg): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:20px;<?php echo $msg_type === 'success' ? 'background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;' : 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;'; ?>">
            <i class="fas <?php echo $msg_type === 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
            <span style="font-size:13px;font-weight:600;"><?php echo $msg; ?></span>
        </div>
        <?php endif; ?>

        <!-- Status Tabs -->
        <div style="display:flex;gap:6px;margin-bottom:24px;flex-wrap:wrap;">
            <a href="?status=pending" style="padding:8px 16px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;<?php echo $status_filter === 'pending' ? 'background:#fef3c7;color:#92400e;' : 'background:#f1f5f9;color:#64748b;'; ?>">
                <i class="fas fa-clock" style="margin-right:4px;"></i> Bekleyen
                <?php if($pending_count > 0): ?><span style="background:#f59e0b;color:#fff;font-size:10px;padding:1px 6px;border-radius:6px;margin-left:4px;"><?php echo $pending_count; ?></span><?php endif; ?>
            </a>
            <a href="?status=approved" style="padding:8px 16px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;<?php echo $status_filter === 'approved' ? 'background:#ecfdf5;color:#065f46;' : 'background:#f1f5f9;color:#64748b;'; ?>">
                <i class="fas fa-check" style="margin-right:4px;"></i> Onaylı
                <span style="opacity:0.6;margin-left:2px;">(<?php echo $approved_count; ?>)</span>
            </a>
            <a href="?status=rejected" style="padding:8px 16px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;<?php echo $status_filter === 'rejected' ? 'background:#fef2f2;color:#991b1b;' : 'background:#f1f5f9;color:#64748b;'; ?>">
                <i class="fas fa-times" style="margin-right:4px;"></i> Reddedilen
                <span style="opacity:0.6;margin-left:2px;">(<?php echo $rejected_count; ?>)</span>
            </a>
            <a href="?status=all" style="padding:8px 16px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;<?php echo $status_filter === 'all' ? 'background:#e2e8f0;color:#0f172a;' : 'background:#f1f5f9;color:#64748b;'; ?>">
                Tümü <span style="opacity:0.6;margin-left:2px;">(<?php echo $total_count; ?>)</span>
            </a>
        </div>

        <!-- Groups List -->
        <?php if (count($groups) > 0): ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach($groups as $g): ?>
            <div class="admin-card" style="padding:20px;">
                <div style="display:flex;gap:16px;align-items:flex-start;">
                    
                    <!-- Group Icon -->
                    <div style="width:52px;height:52px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        <?php 
                        $emojis = ['hobby' => '🎯', 'lifestyle' => '🌟', 'professional' => '💼', 'marketplace' => '🛒'];
                        echo $emojis[$g['category']] ?? '👥'; 
                        ?>
                    </div>
                    
                    <!-- Info -->
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                            <h3 style="font-size:16px;font-weight:800;margin:0;"><?php echo htmlspecialchars($g['name_tr']); ?></h3>
                            
                            <!-- Status Badge -->
                            <?php if($g['status'] === 'pending'): ?>
                            <span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;background:#fef3c7;color:#92400e;">Bekliyor</span>
                            <?php elseif($g['status'] === 'approved'): ?>
                            <span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;background:#ecfdf5;color:#065f46;">Onaylı</span>
                            <?php else: ?>
                            <span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;background:#fef2f2;color:#991b1b;">Reddedildi</span>
                            <?php endif; ?>
                            
                            <span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:600;background:#f1f5f9;color:#64748b;"><?php echo $cat_labels[$g['category']] ?? $g['category']; ?></span>
                            <span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:600;background:#f1f5f9;color:#64748b;">
                                <?php echo $g['privacy'] === 'public' ? '🌐 Public' : '🔒 Private'; ?>
                            </span>
                        </div>
                        
                        <p style="font-size:12px;color:#94a3b8;margin:0 0 4px;">EN: <?php echo htmlspecialchars($g['name_en']); ?></p>
                        
                        <?php if($g['description_tr']): ?>
                        <p style="font-size:13px;color:#64748b;margin:0 0 8px;line-height:1.5;"><?php echo htmlspecialchars(mb_strimwidth($g['description_tr'], 0, 200, '...')); ?></p>
                        <?php endif; ?>
                        
                        <!-- Creator Info -->
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <img src="<?php echo htmlspecialchars($g['avatar'] ?? ''); ?>" style="width:24px;height:24px;border-radius:6px;object-fit:cover;" alt="">
                            <span style="font-size:12px;font-weight:600;color:#334155;"><?php echo htmlspecialchars($g['full_name']); ?></span>
                            <span style="font-size:11px;color:#94a3b8;">@<?php echo htmlspecialchars($g['username']); ?></span>
                            <span style="font-size:11px;color:#94a3b8;">&middot; <?php echo $g['user_group_count']; ?>/2 grup</span>
                        </div>

                        <!-- Stats -->
                        <div style="display:flex;gap:16px;font-size:11px;color:#94a3b8;font-weight:600;">
                            <span><i class="fas fa-users" style="margin-right:3px;"></i><?php echo $g['member_count']; ?> üye</span>
                            <span><i class="fas fa-comment" style="margin-right:3px;"></i><?php echo $g['post_count']; ?> gönderi</span>
                            <span><i class="fas fa-clock" style="margin-right:3px;"></i><?php echo date('d.m.Y H:i', strtotime($g['created_at'])); ?></span>
                        </div>
                        
                        <?php if($g['rejection_reason']): ?>
                        <div style="margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:8px;font-size:12px;color:#991b1b;">
                            <i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> <?php echo htmlspecialchars($g['rejection_reason']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                        <?php if($g['status'] === 'pending'): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="group_id" value="<?php echo $g['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" style="width:100%;padding:8px 16px;border-radius:8px;background:#065f46;color:#fff;border:none;font-size:12px;font-weight:700;cursor:pointer;">
                                <i class="fas fa-check" style="margin-right:4px;"></i> Onayla
                            </button>
                        </form>
                        <button onclick="showRejectModal(<?php echo $g['id']; ?>, '<?php echo htmlspecialchars(addslashes($g['name_tr'])); ?>')" style="padding:8px 16px;border-radius:8px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-times" style="margin-right:4px;"></i> Reddet
                        </button>
                        <?php elseif($g['status'] === 'approved'): ?>
                        <a href="../group_detail?id=<?php echo $g['id']; ?>" target="_blank" style="padding:8px 16px;border-radius:8px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:700;text-decoration:none;text-align:center;">
                            <i class="fas fa-external-link-alt" style="margin-right:4px;"></i> Görüntüle
                        </a>
                        <?php endif; ?>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Bu grubu silmek istediğinize emin misiniz?')">
                            <input type="hidden" name="group_id" value="<?php echo $g['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" style="width:100%;padding:8px 16px;border-radius:8px;background:transparent;color:#94a3b8;border:1px solid #e2e8f0;font-size:12px;font-weight:700;cursor:pointer;">
                                <i class="fas fa-trash" style="margin-right:4px;"></i> Sil
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="admin-card" style="text-align:center;padding:48px 20px;">
            <div style="font-size:40px;margin-bottom:12px;">
                <?php echo $status_filter === 'pending' ? '✅' : '📋'; ?>
            </div>
            <h3 style="font-weight:800;color:#94a3b8;font-size:16px;margin:0 0 4px;">
                <?php echo $status_filter === 'pending' ? 'Bekleyen grup yok' : 'Bu filtrede grup yok'; ?>
            </h3>
        </div>
        <?php endif; ?>
    </main>

    <!-- Reject Modal -->
    <div id="reject-modal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);" onclick="if(event.target===this)this.style.display='none'">
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;padding:24px;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <h3 style="font-size:18px;font-weight:800;margin:0 0 4px;">Grubu Reddet</h3>
            <p id="reject-group-name" style="font-size:13px;color:#64748b;margin:0 0 16px;"></p>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="group_id" id="reject-group-id" value="">
                <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;">Red Sebebi (isteğe bağlı)</label>
                <textarea name="reason" rows="3" placeholder="Neden reddedildiğini açıklayın..."
                    style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;font-size:14px;outline:none;resize:none;box-sizing:border-box;margin-bottom:16px;"></textarea>
                <div style="display:flex;gap:8px;">
                    <button type="submit" style="flex:1;padding:10px;border-radius:10px;background:#dc2626;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-times" style="margin-right:4px;"></i> Reddet
                    </button>
                    <button type="button" onclick="document.getElementById('reject-modal').style.display='none'" style="padding:10px 20px;border-radius:10px;background:#f1f5f9;color:#64748b;border:none;font-size:13px;font-weight:700;cursor:pointer;">
                        İptal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showRejectModal(id, name) {
        document.getElementById('reject-group-id').value = id;
        document.getElementById('reject-group-name').textContent = '"' + name + '"';
        document.getElementById('reject-modal').style.display = 'block';
    }
    </script>
</body>
</html>
