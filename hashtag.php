<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
require_once 'includes/ui_components.php';
require_once 'includes/hashtag_helper.php';

$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$tag = mb_strtolower(preg_replace('/[^a-zA-Z0-9_şŞıİğĞüÜöÖçÇ]/u', '', $tag));

$posts = [];
$hashtag_info = null;

if (!empty($tag)) {
    // Get hashtag info
    $info_stmt = $pdo->prepare("SELECT * FROM hashtags WHERE tag_name = ?");
    $info_stmt->execute([$tag]);
    $hashtag_info = $info_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get posts
    $posts = getPostsByHashtag($pdo, $tag, 50,0);
}

// Get trending hashtags for sidebar
$trending = getTrendingHashtags($pdo, 15);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>#<?php echo htmlspecialchars($tag); ?> - Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Header -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 mb-6 shadow-sm border border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-violet-600 rounded-2xl flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-pink-500/30">
                            #
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 dark:text-white">#<?php echo htmlspecialchars($tag); ?></h1>
                            <?php if ($hashtag_info): ?>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">
                                <span class="font-bold text-pink-500"><?php echo number_format($hashtag_info['usage_count']); ?></span>
                                <?php echo $lang == 'en' ? 'posts' : 'gönderi'; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Posts -->
                <?php if (count($posts) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($posts as $post): ?>
                        <div onclick="location.href='post_detail?id=<?php echo $post['id']; ?>'" 
                           class="block bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 cursor-pointer hover:shadow-lg transition-all group mb-4">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex gap-3">
                                    <div class="relative">
                                        <?php echo renderAvatar($post['avatar'], ['size' => 'md', 'isBordered' => true]); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-base group-hover:text-pink-500 transition-colors flex items-center gap-1">
                                            <?php echo htmlspecialchars($post['full_name'] ?? $post['username']); ?>
                                            <?php if(isset($post['badge']) && in_array($post['badge'], ['verified', 'business', 'founder', 'moderator'])) echo heroicon('check-badge', 'text-blue-500 w-4 h-4'); ?>
                                        </h4>
                                        <p class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('d.m H:i', strtotime($post['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="text-slate-800 dark:text-slate-200 text-sm leading-relaxed mb-4">
                                <?php echo nl2br(linkifyHashtags($post['content'] ?? '')); ?>
                            </div>

                            <?php if (!empty($post['media_url'])): ?>
                                <?php if($post['media_type'] == 'image'): ?>
                                <div class="rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 mb-4">
                                    <img src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full h-auto object-cover max-h-[500px]" loading="lazy">
                                </div>
                                <?php elseif($post['media_type'] == 'video'): ?>
                                <div class="rounded-xl overflow-hidden bg-black mb-4 aspect-video relative">
                                    <video src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full h-full object-contain" controls></video>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Footer Stats -->
                            <div class="flex items-center gap-6 border-t border-slate-100 dark:border-slate-700/50 pt-3">
                                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm group-hover:text-pink-500 transition-colors">
                                    <?php echo heroicon('heart', 'w-5 h-5'); ?>
                                    <span class="font-bold"><?php echo $post['like_count'] ?? 0; ?></span>
                                </span>
                                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm group-hover:text-blue-500 transition-colors">
                                    <?php echo heroicon('chat-bubble-left', 'w-5 h-5'); ?>
                                    <span class="font-bold"><?php echo $post['comment_count'] ?? 0; ?></span>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16">
                        <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="text-4xl text-slate-300 dark:text-slate-600">#</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-500 dark:text-slate-400 mb-2">
                            <?php echo $lang == 'en' ? 'No posts found' : 'Gönderi bulunamadı'; ?>
                        </h3>
                        <p class="text-slate-400 dark:text-slate-500">
                            <?php echo $lang == 'en' ? 'Be the first to use this hashtag!' : 'Bu hashtag\'i ilk kullanan siz olun!'; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar: Trending -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-800 rounded-3xl p-5 shadow-sm border border-slate-100 dark:border-slate-700 sticky top-24">
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <?php echo heroicon('fire', 'w-5 h-5 text-orange-500'); ?>
                        <?php echo $lang == 'en' ? 'Trending' : 'Popüler Etiketler'; ?>
                    </h3>
                    <?php if (count($trending) > 0): ?>
                    <div class="space-y-2">
                        <?php foreach ($trending as $i => $t_tag): ?>
                        <a href="hashtag?tag=<?php echo urlencode($t_tag['tag_name']); ?>" 
                           class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group <?php echo $t_tag['tag_name'] === $tag ? 'bg-pink-50 dark:bg-pink-900/20' : ''; ?>">
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-pink-500 transition-colors">
                                    #<?php echo htmlspecialchars($t_tag['tag_name']); ?>
                                </p>
                                <p class="text-xs text-slate-400"><?php echo number_format($t_tag['usage_count']); ?> <?php echo $lang == 'en' ? 'posts' : 'gönderi'; ?></p>
                            </div>
                            <span class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-500"><?php echo $i + 1; ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-slate-400 text-center py-4">
                        <?php echo $lang == 'en' ? 'No trending hashtags yet' : 'Henüz popüler etiket yok'; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/bottom_nav.php'; ?>
</body>
</html>
