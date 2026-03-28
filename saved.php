<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/ui_components.php';
require_once 'includes/lang.php';
require_once 'includes/image_helper.php';
require_once 'includes/icon_helper.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
// $lang is already set by lang.php

// Get Collections
$collections_stmt = $pdo->prepare("SELECT * FROM collections WHERE user_id = ? ORDER BY created_at DESC");
$collections_stmt->execute([$user_id]);
$collections = $collections_stmt->fetchAll();

// Get Saved Posts (Default: All)
$collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : null;

$sql = "
    SELECT p.*, u.username, u.full_name, u.avatar, u.badge,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
           sp.created_at as saved_at
    FROM saved_posts sp
    JOIN posts p ON sp.post_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE sp.user_id = ?
";

$params = [$user_id];

if ($collection_id) {
    $sql .= " AND sp.collection_id = ?";
    $params[] = $collection_id;
} else {
    $sql .= " ORDER BY sp.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$page_title = $lang == 'en' ? 'Saved Posts' : 'Kaydedilenler';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> - Kalkan Social</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 pb-24">

    <!-- Header -->
    <div class="fixed top-0 left-0 right-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="profile" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i class="fas fa-arrow-left text-slate-600 dark:text-slate-400"></i>
                </a>
                <h1 class="text-xl font-bold bg-gradient-to-r from-yellow-500 to-orange-500 bg-clip-text text-transparent">
                    <?php echo $page_title; ?>
                </h1>
            </div>
            
            <button onclick="createCollectionModal()" class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-yellow-500 transition-colors">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        
        <!-- Tabs -->
        <div class="max-w-3xl mx-auto px-4 flex gap-4 overflow-x-auto hide-scrollbar pb-2">
            <a href="saved" class="px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all <?php echo !$collection_id ? 'bg-yellow-500 text-white shadow-lg shadow-yellow-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'; ?>">
                <?php echo $lang == 'en' ? 'All Posts' : 'Tüm Gönderiler'; ?>
            </a>
            <?php foreach($collections as $col): ?>
            <a href="saved?collection_id=<?php echo $col['id']; ?>" class="px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all flex items-center gap-2 <?php echo $collection_id == $col['id'] ? 'bg-yellow-500 text-white shadow-lg shadow-yellow-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'; ?>">
                <span><?php echo htmlspecialchars($col['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="pt-32 max-w-3xl mx-auto px-4 min-h-screen">
        
        <?php if(count($posts) > 0): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <?php foreach($posts as $post): ?>
            <div class="relative group bg-slate-200 dark:bg-slate-800 rounded-xl overflow-hidden aspect-square cursor-pointer" onclick="location.href='post_detail?id=<?php echo $post['id']; ?>'">
                <?php if($post['media_type'] == 'image'): ?>
                    <img src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                <?php elseif($post['media_type'] == 'video'): ?>
                    <video src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full h-full object-cover"></video>
                    <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                        <i class="fas fa-play text-white text-2xl drop-shadow-lg"></i>
                    </div>
                <?php else: ?>
                    <div class="p-4 flex items-center justify-center h-full text-center text-xs">
                        <p class="line-clamp-4"><?php echo htmlspecialchars($post['content']); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-3">
                    <div class="flex items-center justify-between text-white text-xs font-bold">
                        <span class="flex items-center gap-1"><i class="fas fa-heart"></i> <?php echo $post['like_count']; ?></span>
                        <button onclick="event.stopPropagation(); toggleSave(<?php echo $post['id']; ?>, this)" class="text-yellow-400 hover:text-white transition-colors">
                            <i class="fas fa-bookmark"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-20 text-slate-400">
            <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                <i class="far fa-bookmark"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-600 dark:text-slate-300 mb-2"><?php echo $lang == 'en' ? 'No saved posts yet' : 'Henüz kaydedilen yok'; ?></h3>
            <p class="text-sm max-w-xs mx-auto"><?php echo $lang == 'en' ? 'Tap the bookmark icon on posts to save them for later.' : 'Gönderilerdeki kaydet ikonuna tıklayarak buraya ekleyebilirsin.'; ?></p>
            <a href="feed" class="inline-block mt-6 px-6 py-2 bg-slate-800 dark:bg-white text-white dark:text-slate-900 rounded-full font-bold text-sm">
                <?php echo $lang == 'en' ? 'Explore Feed' : 'Akışı Keşfet'; ?>
            </a>
        </div>
        <?php endif; ?>
        
    </div>

    <!-- Create Collection Modal -->
    <div id="collection-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCollectionModal()"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-slate-900 rounded-2xl w-full max-w-sm p-6 shadow-2xl">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-folder-plus text-yellow-500"></i>
                <?php echo $lang == 'en' ? 'New Collection' : 'Yeni Koleksiyon'; ?>
            </h3>
            <input type="text" id="collection-name" placeholder="<?php echo $lang == 'en' ? 'Collection Name (e.g. Summer Vibes)' : 'Koleksiyon Adı (Örn: Yaz Tatili)'; ?>" 
                   class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl px-4 py-3 mb-4 focus:ring-2 focus:ring-yellow-500 outline-none">
            <div class="flex gap-3">
                <button onclick="closeCollectionModal()" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 rounded-xl font-bold text-slate-500">
                    <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                </button>
                <button onclick="createCollection()" class="flex-1 py-3 bg-yellow-500 text-white rounded-xl font-bold shadow-lg shadow-yellow-500/30">
                    <?php echo $lang == 'en' ? 'Create' : 'Oluştur'; ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        function createCollectionModal() {
            document.getElementById('collection-modal').classList.remove('hidden');
            document.getElementById('collection-name').focus();
        }
        
        function closeCollectionModal() {
            document.getElementById('collection-modal').classList.add('hidden');
        }
        
        async function createCollection() {
            const name = document.getElementById('collection-name').value;
            if(!name) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'create_collection');
                formData.append('name', name);
                
                const res = await fetch('api/save_post.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if(data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch(e) {
                console.error(e);
            }
        }
        
        async function toggleSave(postId, btn) {
            // Unsaving from grid removes the item
            if(!confirm('<?php echo $lang == "en" ? "Remove from saved?" : "Kaydedilenlerden kaldırılsın mı?"; ?>')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_save');
                formData.append('post_id', postId);
                
                const response = await fetch('api/save_post.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.status === 'success' && !data.is_saved) {
                    // Remove element
                    btn.closest('.relative').remove();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    </script>
</body>
</html>
