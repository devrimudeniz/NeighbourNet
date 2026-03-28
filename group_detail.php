<?php
require_once 'includes/bootstrap.php';
require_once 'includes/hashtag_helper.php';

$group_id = (int)($_GET['id'] ?? 0);

if (!$group_id) {
    header("Location: groups");
    exit();
}

// Get group details - dynamic language selection (whitelist validated)
$name_field = ($lang === 'tr') ? 'name_tr' : 'name_en';
$desc_field = ($lang === 'tr') ? 'description_tr' : 'description_en';
// Whitelist validation for dynamic column names
$allowed_cols = ['name_tr', 'name_en', 'description_tr', 'description_en'];
if (!in_array($name_field, $allowed_cols) || !in_array($desc_field, $allowed_cols)) {
    $name_field = 'name_en'; $desc_field = 'description_en';
}

$stmt = $pdo->prepare("SELECT g.*, g.`$name_field` as name, g.`$desc_field` as description, 
                       u.username as creator_username, u.full_name as creator_name 
                       FROM groups g 
                       JOIN users u ON g.creator_id = u.id 
                       WHERE g.id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    header("Location: groups");
    exit();
}

// Check if user is member
$is_member = false;
$user_role = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group_id, $_SESSION['user_id']]);
    $membership = $stmt->fetch();
    if ($membership) {
        $is_member = true;
        $user_role = $membership['role'];
    }
}

// Get members
$stmt = $pdo->prepare("SELECT gm.*, u.username, u.full_name, u.avatar, u.badge 
                       FROM group_members gm 
                       JOIN users u ON gm.user_id = u.id 
                       WHERE gm.group_id = ? 
                       ORDER BY 
                         CASE gm.role 
                           WHEN 'admin' THEN 1 
                           WHEN 'moderator' THEN 2 
                           ELSE 3 
                         END, 
                         gm.joined_at DESC 
                       LIMIT 20");
$stmt->execute([$group_id]);
$members = $stmt->fetchAll();

// Get group posts
$stmt = $pdo->prepare("SELECT gp.*, u.username, u.full_name, u.avatar, u.badge 
                       FROM group_posts gp 
                       JOIN users u ON gp.user_id = u.id 
                       WHERE gp.group_id = ? 
                       ORDER BY gp.created_at DESC 
                       LIMIT 50");
$stmt->execute([$group_id]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20">
        <!-- Group Header -->
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl overflow-hidden border border-white/20 dark:border-slate-800/50 mb-6">
            <!-- Cover Photo -->
            <div class="h-48 bg-gradient-to-br from-pink-500 to-violet-500 relative">
                <?php if ($group['cover_photo']): ?>
                <img src="<?php echo htmlspecialchars($group['cover_photo']); ?>" class="w-full h-full object-cover" loading="lazy">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-white text-7xl">
                    <?php 
                    $icons = ['hobby' => '🎯', 'lifestyle' => '🌟', 'professional' => '💼', 'marketplace' => '🛒'];
                    echo $icons[$group['category']] ?? '👥';
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Group Info -->
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white"><?php echo htmlspecialchars($group['name']); ?></h1>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 dark:text-white">
                                <?php echo $t[$group['category']]; ?>
                            </span>
                        </div>
                        <?php if ($group['description']): ?>
                        <p class="text-slate-600 dark:text-slate-400 mb-4">
                            <?php echo nl2br(htmlspecialchars($group['description'])); ?>
                        </p>
                        <?php endif; ?>
                        <div class="flex items-center gap-4 text-sm text-slate-500">
                            <span><i class="fas fa-users mr-1"></i><?php echo $group['member_count']; ?> <?php echo $t['group_members']; ?></span>
                            <span><i class="fas fa-comments mr-1"></i><?php echo $group['post_count']; ?> <?php echo $t['group_posts']; ?></span>
                            <span><i class="fas fa-crown mr-1"></i><?php echo htmlspecialchars($group['creator_name']); ?></span>
                        </div>
                    </div>

                    <!-- Join/Leave Button -->
                    <div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($is_member): ?>
                            <button onclick="leaveGroup()" 
                                    class="px-6 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                <?php echo $t['leave_group']; ?>
                            </button>
                            <?php else: ?>
                            <button onclick="joinGroup()" 
                                    class="px-6 py-3 rounded-xl bg-gradient-to-r from-pink-500 to-violet-500 text-white font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                                <?php echo $t['join_group']; ?>
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                        <a href="login" class="px-6 py-3 rounded-xl bg-gradient-to-r from-pink-500 to-violet-500 text-white font-bold inline-block">
                            <?php echo $t['join_group']; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Post Composer (Members Only) -->
                <?php if ($is_member): ?>
                <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-slate-800/50">
                    <form onsubmit="postInGroup(event)">
                        <textarea id="post-content" rows="3" 
                                  placeholder="<?php echo $t['post_in_group']; ?>..." 
                                  class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white border-none focus:outline-none focus:ring-2 focus:ring-pink-500 mb-3"></textarea>
                        
                        <!-- Visibility Options -->
                        <div class="flex items-center gap-4 mb-3">
                            <span class="text-sm font-bold text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Visibility:' : 'Görünürlük:'; ?></span>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="visibility" value="everyone" checked class="rounded-full text-pink-500 focus:ring-pink-500">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    <i class="fas fa-globe text-emerald-500 mr-1"></i><?php echo $lang == 'en' ? 'Everyone (Feed + Group)' : 'Herkese Açık (Akış + Grup)'; ?>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="visibility" value="group_only" class="rounded-full text-pink-500 focus:ring-pink-500">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    <i class="fas fa-lock text-slate-500 mr-1"></i><?php echo $lang == 'en' ? 'Group Only' : 'Sadece Grup'; ?>
                                </span>
                            </label>
                        </div>
                        
                        <button type="submit" 
                                class="bg-gradient-to-r from-pink-500 to-violet-500 text-white px-6 py-2 rounded-xl font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                            <i class="fas fa-paper-plane mr-2"></i><?php echo $lang == 'en' ? 'Share' : 'Paylaş'; ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Posts Feed -->
                <div id="posts-container" class="space-y-4">
                    <?php if (count($posts) > 0): ?>
                        <?php foreach ($posts as $post): ?>
                        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-slate-800/50">
                            <!-- Post Header -->
                            <div class="flex items-start gap-3 mb-4">
                                     <img src="<?php echo $post['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($post['full_name']); ?>" 
                                          class="w-12 h-12 rounded-full object-cover" loading="lazy">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($post['full_name']); ?></span>
                                        <?php if ($post['badge']): ?>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600">
                                            <?php echo $post['badge']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-slate-400">
                                        <?php echo date('d M H:i', strtotime($post['created_at'])); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <p class="text-slate-700 dark:text-slate-300 mb-4">
                                <?php echo nl2br(linkifyHashtags($post['content'] ?? '')); ?>
                            </p>

                            <?php if ($post['image']): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                                 class="rounded-xl mb-4 max-w-full" loading="lazy">
                            <?php endif; ?>

                            <!-- Post Actions -->
                            <div class="flex items-center gap-4 text-sm text-slate-500">
                                <button class="hover:text-pink-500 transition-colors">
                                    <i class="far fa-heart mr-1"></i><?php echo $post['like_count']; ?>
                                </button>
                                <button class="hover:text-blue-500 transition-colors">
                                    <i class="far fa-comment mr-1"></i><?php echo $post['comment_count']; ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-12 border border-white/20 dark:border-slate-800/50 text-center">
                        <i class="fas fa-comments text-6xl text-slate-300 dark:text-slate-600 mb-4"></i>
                        <p class="text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'No posts yet. Be the first to share!' : 'Henüz gönderi yok. İlk paylaşan siz olun!'; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Members -->
                <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-slate-800/50">
                    <h3 class="font-bold text-lg mb-4 text-slate-900 dark:text-white"><?php echo $t['group_members']; ?></h3>
                    <div class="space-y-3">
                        <?php foreach ($members as $member): ?>
                        <a href="profile?uid=<?php echo $member['user_id']; ?>" 
                           class="flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800 p-2 rounded-xl transition-colors">
                             <img src="<?php echo $member['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($member['full_name']); ?>" 
                                  class="w-10 h-10 rounded-full object-cover" loading="lazy">
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-sm truncate text-slate-900 dark:text-white"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                <div class="text-xs text-slate-400">
                                    <?php if ($member['role'] === 'admin'): ?>
                                    <i class="fas fa-crown text-yellow-500 mr-1"></i><?php echo $lang == 'en' ? 'Admin' : 'Yönetici'; ?>
                                    <?php elseif ($member['role'] === 'moderator'): ?>
                                    <i class="fas fa-shield-alt text-blue-500 mr-1"></i><?php echo $lang == 'en' ? 'Moderator' : 'Moderatör'; ?>
                                    <?php else: ?>
                                    <?php echo $lang == 'en' ? 'Member' : 'Üye'; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function joinGroup() {
            fetch('api/join_group.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=join&group_id=<?php echo $group_id; ?>'
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('<?php echo $t['joined_group_success']; ?>');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Error joining group', 'error');
                }
            });
        }

        function leaveGroup() {
            if (confirm('<?php echo $lang == 'en' ? 'Are you sure you want to leave this group?' : 'Bu gruptan ayrılmak istediğinize emin misiniz?'; ?>')) {
                fetch('api/join_group.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=leave&group_id=<?php echo $group_id; ?>'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = 'groups';
                    } else {
                        showToast(data.message || 'Error leaving group', 'error');
                    }
                });
            }
        }

        function postInGroup(e) {
            e.preventDefault();
            const content = document.getElementById('post-content').value.trim();
            const visibility = document.querySelector('input[name="visibility"]:checked')?.value || 'everyone';
            
            if (!content) return;

            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('group_id', '<?php echo $group_id; ?>');
            formData.append('content', content);
            formData.append('visibility', visibility);

            fetch('api/group_post.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
