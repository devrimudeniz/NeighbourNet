<?php
require_once '../includes/db.php';
require_once 'auth_session.php';
require_once "../includes/lang.php";

$stmt = $pdo->prepare("SELECT p.*, u.username, u.full_name, u.avatar 
                       FROM posts p 
                       JOIN users u ON p.user_id = u.id 
                       ORDER BY p.created_at DESC LIMIT 100");
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gönderiler | Admin</title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">

    <?php include "includes/sidebar.php"; ?>

    <main class="admin-main">
        <div style="margin-bottom:24px;">
            <h1 class="page-title">Gönderiler</h1>
            <p class="page-subtitle">Son 100 gönderiyi yönetin ve uygunsuz içerikleri kaldırın.</p>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach($posts as $post): ?>
            <div id="post-<?php echo $post['id']; ?>" class="admin-card" style="display:flex;gap:16px;align-items:flex-start;">
                <img src="/<?php echo $post['avatar'] ?? ''; ?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;background:#f1f5f9;flex-shrink:0;" alt="" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($post['username']); ?>&background=e2e8f0&color=64748b'">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span style="font-weight:700;font-size:13px;color:#0f172a;"><?php echo htmlspecialchars($post['full_name'] ?? $post['username']); ?></span>
                        <span style="font-size:11px;color:#94a3b8;">@<?php echo htmlspecialchars($post['username']); ?></span>
                        <span style="font-size:11px;color:#cbd5e1;">&middot;</span>
                        <span style="font-size:11px;color:#94a3b8;"><?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></span>
                    </div>
                    <p style="font-size:14px;color:#334155;margin:0 0 8px;line-height:1.5;"><?php echo nl2br(htmlspecialchars(mb_substr($post['content'] ?? '', 0, 300))); ?></p>
                    <?php if(!empty($post['media_url'])): ?>
                        <?php if(isset($post['media_type']) && $post['media_type'] === 'video'): ?>
                        <div style="width:200px;height:120px;background:#0f172a;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">
                            <iframe src="<?php echo htmlspecialchars($post['media_url']); ?>" style="width:100%;height:100%;border:0;" allowfullscreen></iframe>
                        </div>
                        <?php else: ?>
                        <img src="/<?php echo $post['media_url']; ?>" style="width:120px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;" alt="">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <a href="/post_detail?id=<?php echo $post['id']; ?>" target="_blank" class="btn btn-outline btn-sm">Görüntüle</a>
                    <button onclick="deletePost(<?php echo $post['id']; ?>)" class="btn btn-danger btn-sm">Sil</button>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($posts)): ?>
            <div class="empty-state"><i class="fas fa-pen-square"></i><p>Gönderi bulunamadı.</p></div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function deletePost(id) {
        if(!confirm('Bu gönderiyi silmek istediğinize emin misiniz?')) return;
        var params = new URLSearchParams();
        params.append('action', 'delete_post');
        params.append('post_id', id);
        fetch('api_admin.php', { method: 'POST', body: params })
        .then(function(r){return r.json();})
        .then(function(data){
            if(data.status === 'success') document.getElementById('post-'+id).remove();
            else alert(data.message);
        });
    }
    </script>
</body>
</html>
