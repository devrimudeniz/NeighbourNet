<?php
require_once 'includes/bootstrap.php';
require_once 'includes/text_helper.php';
require_once 'includes/hashtag_helper.php';

if (!isset($_GET['id'])) {
    header('Location: feed');
    exit;
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? 0;

// View Tracking (Unique User)
if ($user_id > 0) {
    // Check if previously viewed
    $check_view = $pdo->prepare("SELECT 1 FROM post_views WHERE post_id = ? AND user_id = ?");
    $check_view->execute([$post_id, $user_id]);
    if (!$check_view->fetch()) {
        try {
            // Record view
            $ins_view = $pdo->prepare("INSERT INTO post_views (post_id, user_id) VALUES (?, ?)");
            $ins_view->execute([$post_id, $user_id]);
            
            // Increment counter
            $upd_count = $pdo->prepare("UPDATE posts SET view_count = view_count + 1 WHERE id = ?");
            $upd_count->execute([$post_id]);
        } catch (PDOException $e) {
            // Ignore Duplicate Entry errors just in case
        }
    }
}

// Fetch unique post with all details (Reusing the robust query from feed.php)
$sql = "SELECT p.*, 
        u.username, u.full_name, u.avatar, u.badge, u.venue_name,
        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
        (SELECT reaction_type FROM post_likes WHERE post_id = p.id AND user_id = ?) as my_reaction,
        (SELECT GROUP_CONCAT(reaction_type) FROM post_likes WHERE post_id = p.id) as top_reactions,
        (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
        
        (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as is_saved,
        
        -- Shared Post Data
        p.shared_from_id,
        orig.content as shared_content,
        orig.media_url as shared_media,
        orig.media_type as shared_media_type,
        orig_u.username as shared_username,
        orig_u.full_name as shared_fullname,
        orig_u.avatar as shared_avatar
        
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN posts orig ON p.shared_from_id = orig.id
    LEFT JOIN users orig_u ON orig.user_id = orig_u.id 
    WHERE p.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id, $post_id]);
$post = $stmt->fetch();

if (!$post) {
    // Check if it's a group post
    // For simplicity, redirection to feed if simplified query fails, 
    // or we could expand the query to include union like feed.php if needed.
    // Given the user request, let's assume standard posts for now or add the union.
    // Let's add the Union for completeness to support Group posts opening in detail too.
    
    $sql_group = "SELECT gp.id, gp.user_id, gp.content, 'image' as media_type, gp.image as media_url, gp.created_at, 
        NULL as shared_from_id,
        u.username, u.full_name, u.avatar, u.badge, u.venue_name,
        gp.comment_count,
        NULL as my_reaction,
        NULL as top_reactions,
        (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
        NULL as shared_from_id, NULL as shared_content, NULL as shared_media, NULL as shared_media_type, NULL as shared_username, NULL as shared_fullname, NULL as shared_avatar
    FROM group_posts gp 
    JOIN users u ON gp.user_id = u.id 
    WHERE gp.id = ?";
    
    $stmt = $pdo->prepare($sql_group);
    $stmt->execute([$post_id]);
    $group_post = $stmt->fetch();
    
    if ($group_post) {
        $post = $group_post;
        $post['post_type'] = 'group';
    } else {
        die('Post not found or deleted.');
    }
} else {
    $post['post_type'] = 'regular';
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/seo_tags.php'; ?>
    <?php include 'includes/header_css.php'; ?>
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <style>
        /* Full Screen Image Viewer */
        .image-viewer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .image-viewer-overlay.active {
            display: flex;
        }
        
        .image-viewer-container {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        .image-viewer-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10000;
            transition: all 0.3s;
        }
        
        .image-viewer-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        
        .image-viewer-close i {
            color: white;
            font-size: 20px;
        }
        
        .swiper-slide {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .swiper-slide img {
            max-width: 100%;
            max-height: 100vh;
            object-fit: contain;
        }
        
        .swiper-button-next,
        .swiper-button-prev {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            width: 44px !important;
            height: 44px !important;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px !important;
        }
        
        .swiper-pagination-bullet {
            background: white !important;
            opacity: 0.5;
        }
        
        .swiper-pagination-bullet-active {
            opacity: 1;
        }
        
        .post-image-clickable {
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .post-image-clickable:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>



    <!-- Main Content -->
    <main id="swup-main" class="transition-main container mx-auto px-4 pt-24 max-w-2xl">
        <a href="feed" class="inline-flex items-center gap-2 text-slate-500 hover:text-pink-500 mb-6 transition-colors">
            <i class="fas fa-arrow-left"></i> <?php echo $lang == 'en' ? 'Back to Feed' : 'Akışa Dön'; ?>
        </a>

        <!-- Single Post Card (Structure matches feed.php exactly) -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700">
            <!-- Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex gap-3">
                    <a href="profile?uid=<?php echo $post['user_id']; ?>" class="w-12 h-12 rounded-full bg-slate-200 overflow-hidden">
                         <?php $p_avatar = !empty($post['avatar']) ? $post['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['full_name'] ?? 'User') . '&background=random'; ?>
                         <img src="<?php echo $p_avatar; ?>" class="w-full h-full object-cover" loading="lazy">
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="profile?uid=<?php echo $post['user_id']; ?>" class="font-bold text-slate-700 dark:text-slate-200 hover:text-pink-500 transition-colors">
                                <?php echo htmlspecialchars($post['full_name']); ?>
                            </a>
                            <?php if($post['badge'] == 'founder') echo '<i class="fas fa-shield-alt text-pink-500 text-xs" title="Kurucu"></i>'; ?>
                            <?php if($post['badge'] == 'moderator') echo '<i class="fas fa-gavel text-blue-500 text-xs" title="Moderatör"></i>'; ?>
                            <?php if($post['badge'] == 'business') echo '<i class="fas fa-check-circle text-green-500 text-xs" title="İşletme"></i>'; ?>
                            
                            <?php 
                            if (!empty($post['expert_badges'])) {
                                $e_badges = explode(',', $post['expert_badges']);
                                foreach($e_badges as $eb) {
                                    echo '<span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 text-[10px] font-bold border border-yellow-200">' . ucfirst($eb) . '</span>';
                                }
                            }
                            ?>
                        </div>
                        <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('d.m H:i', strtotime($post['created_at'])); ?></span>
                    </div>
                </div>
                
                  <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                    <div class="relative" id="post-menu-container-<?php echo $post['id']; ?>">
                        <button onclick="togglePostMenu(<?php echo $post['id']; ?>)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div id="post-menu-<?php echo $post['id']; ?>" class="hidden absolute right-0 top-10 w-40 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-20">
                            <button onclick="editPost(<?php echo $post['id']; ?>)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                <i class="fas fa-pen text-xs text-blue-500"></i> Edit Post
                            </button>
                            <button onclick="toggleSave(<?php echo $post['id']; ?>, this)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                <i class="<?php echo ($post['is_saved'] ?? 0) ? 'fas' : 'far'; ?> fa-bookmark text-xs text-yellow-500"></i>
                                <span class="save-text"><?php echo ($post['is_saved'] ?? 0) ? ($lang == 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar') : ($lang == 'en' ? 'Save' : 'Kaydet'); ?></span>
                            </button>
                            <button onclick="confirmDeletePost(<?php echo $post['id']; ?>)" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors border-t border-slate-50 dark:border-slate-700">
                                <i class="fas fa-trash text-xs"></i> Delete Post
                            </button>
                        </div>
                    </div>
                  <?php elseif(isset($_SESSION['user_id'])): ?>
                    <?php $can_mod_delete = in_array($_SESSION['badge'] ?? '', ['founder', 'moderator']); ?>
                    <div class="relative" id="post-menu-container-<?php echo $post['id']; ?>">
                        <button onclick="togglePostMenu(<?php echo $post['id']; ?>)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div id="post-menu-<?php echo $post['id']; ?>" class="hidden absolute right-0 top-10 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-20">
                            <button onclick="toggleSave(<?php echo $post['id']; ?>, this)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                <i class="<?php echo ($post['is_saved'] ?? 0) ? 'fas' : 'far'; ?> fa-bookmark text-xs text-yellow-500"></i>
                                <span class="save-text"><?php echo ($post['is_saved'] ?? 0) ? ($lang == 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar') : ($lang == 'en' ? 'Save' : 'Kaydet'); ?></span>
                            </button>
                            <?php if ($can_mod_delete): ?>
                            <button onclick="confirmDeletePost(<?php echo $post['id']; ?>)" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors border-t border-slate-50 dark:border-slate-700">
                                <i class="fas fa-trash text-xs"></i> <?php echo $lang == 'en' ? 'Delete Post' : 'Gönderiyi Sil'; ?>
                            </button>
                            <?php endif; ?>
                            <button onclick="openReportModal(<?php echo $post['id']; ?>, 'post')" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors">
                                <i class="fas fa-flag text-xs"></i> <?php echo $lang == 'en' ? 'Report Post' : 'Gönderiyi Şikayet Et'; ?>
                            </button>
                        </div>
                    </div>
                  <?php endif; ?>
            </div>

            <!-- Content -->
            <div id="post-content-wrapper-<?php echo $post['id']; ?>" class="mb-4">
                <p id="post-original-<?php echo $post['id']; ?>" class="text-slate-800 dark:text-slate-200 leading-relaxed text-lg">
                    <?php
                    $content = $post['content'];
                    $prev = '';
                    while ($prev !== $content) { $prev = $content; $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8'); }
                    echo nl2br(linkifyHashtags($content));
                    ?>
                </p>
                <p id="post-translated-<?php echo $post['id']; ?>" class="hidden text-slate-800 dark:text-slate-200 leading-relaxed text-lg italic border-l-4 border-pink-500 pl-4 transition-all duration-300">
                </p>

                <!-- Shared Content Card -->
                <?php if(!empty($post['shared_from_id'])): ?>
                <div class="mt-3 border border-slate-200 dark:border-slate-700 rounded-xl p-4 bg-slate-50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-2 mb-2">
                         <?php $s_avatar = !empty($post['shared_avatar']) ? $post['shared_avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['shared_fullname'] ?? 'User') . '&background=random'; ?>
                         <img src="<?php echo $s_avatar; ?>" class="w-6 h-6 rounded-full object-cover" loading="lazy">
                         <div class="flex flex-col">
                             <span class="text-sm font-bold dark:text-slate-200"><?php echo htmlspecialchars($post['shared_fullname']); ?></span>
                             <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['shared_username']); ?></span>
                         </div>
                    </div>
                    <p class="text-sm text-slate-700 dark:text-slate-300 mb-2"><?php echo nl2br(linkifyHashtags($post['shared_content'] ?? '')); ?></p>
                    <?php if(!empty($post['shared_media'])): ?>
                        <?php if($post['shared_media_type'] == 'image'): ?>
                            <img src="<?php echo htmlspecialchars($post['shared_media']); ?>" class="rounded-lg w-full object-cover max-h-60 post-image-clickable" loading="lazy" onclick="openImageViewer('<?php echo htmlspecialchars($post['shared_media']); ?>')">
                        <?php elseif($post['shared_media_type'] == 'video'): ?>
                            <div class="rounded-lg overflow-hidden bg-black">
                                <?php if(strpos($post['shared_media'], 'youtube.com') !== false || strpos($post['shared_media'], 'youtu.be') !== false || strpos($post['shared_media'], 'vimeo.com') !== false): ?>
                                    <div class="relative pt-[56.25%]">
                                        <iframe src="<?php echo htmlspecialchars($post['shared_media']); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <video src="<?php echo htmlspecialchars($post['shared_media']); ?>" class="w-full max-h-60" controls playsinline></video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($post['media_url']) && empty($post['shared_from_id'])): ?>
                <?php if($post['media_type'] == 'image'): ?>
                <img src="<?php echo htmlspecialchars($post['media_url']); ?>" class="rounded-xl mb-4 w-full post-image-clickable" loading="lazy" onclick="openImageViewer('<?php echo htmlspecialchars($post['media_url']); ?>')">
                <?php elseif($post['media_type'] == 'video'): ?>
                <div class="rounded-xl overflow-hidden mb-4 bg-black">
                    <?php if(strpos($post['media_url'], 'youtube.com') !== false || strpos($post['media_url'], 'youtu.be') !== false || strpos($post['media_url'], 'vimeo.com') !== false): ?>
                        <div class="relative pt-[56.25%]">
                            <iframe src="<?php echo htmlspecialchars($post['media_url']); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                        </div>
                    <?php else: ?>
                        <video src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full max-h-[600px]" controls playsinline></video>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>

             <!-- Reactions & Comments Count Row -->
             <?php if($post['like_count'] > 0 || $post['comment_count'] > 0): ?>
             <div class="flex items-center justify-between py-2.5 mb-2 mt-2 border-t border-slate-100 dark:border-slate-700">
                 <div class="flex items-center gap-1 cursor-pointer" onclick="showReactionDetails(<?php echo $post['id']; ?>)">
                     <?php if($post['like_count'] > 0 && !empty($post['top_reactions'])): 
                         $reactions_raw = explode(',', $post['top_reactions']);
                         $counts = array_count_values($reactions_raw);
                         arsort($counts);
                         
                         $r_icons = [
                             'like' => 'fas fa-thumbs-up', 'love' => 'fas fa-heart', 
                             'haha' => 'fas fa-laugh-squint', 'wow' => 'fas fa-surprise', 
                             'sad' => 'fas fa-sad-tear', 'angry' => 'fas fa-angry'
                         ];
                         $r_bg = [
                             'like' => 'bg-blue-500', 'love' => 'bg-red-500', 
                             'haha' => 'bg-yellow-500', 'wow' => 'bg-yellow-500', 
                             'sad' => 'bg-yellow-500', 'angry' => 'bg-orange-500'
                         ];
                     ?>
                     <div class="flex -space-x-1.5 mr-1">
                         <?php $shown = 0; foreach($counts as $type => $count): if($shown >= 3) break; ?>
                         <div class="w-5 h-5 rounded-full border-2 border-white dark:border-slate-800 <?php echo $r_bg[$type]; ?> flex items-center justify-center">
                             <i class="<?php echo $r_icons[$type]; ?> text-[10px] text-white"></i>
                         </div>
                         <?php $shown++; endforeach; ?>
                     </div>
                     <span class="text-sm text-slate-500 dark:text-slate-400 hover:underline" id="like-count-<?php echo $post['id']; ?>">
                         <?php echo $post['like_count']; ?>
                     </span>
                     <?php endif; ?>
                 </div>
                 
                 <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                      <?php if($post['comment_count'] > 0): ?>
                      <span><?php echo $post['comment_count']; ?> <?php echo $lang == 'en' ? 'comments' : 'yorum'; ?></span>
                      <?php endif; ?>
                      
                      <?php if(($post['view_count'] ?? 0) > 0): ?>
                      <span><?php echo $post['view_count']; ?> <?php echo $lang == 'en' ? 'views' : 'görüntülenme'; ?></span>
                      <?php endif; ?>
                 </div>
             </div>
             <?php endif; ?>


            <!-- Actions -->
             <div class="flex gap-6 border-t border-slate-100 dark:border-slate-700 pt-4 text-slate-400 mb-6">
                 <!-- Reactions (Static for Detail View or Functional?) -->
                 <!-- Let's keep them functional -->
                 <div class="relative group/reaction z-10">
                     <!-- Reaction Bar -->
                     <div class="absolute bottom-10 left-0 hidden group-hover/reaction:flex bg-white dark:bg-slate-800 p-2 rounded-full shadow-2xl border border-slate-100 dark:border-slate-700 gap-2 animate-in fade-in slide-in-from-bottom-2 duration-200 z-50">
                        <button onclick="sendReaction(<?php echo $post['id']; ?>, 'like', event)" class="hover:scale-125 transition-transform text-2xl" title="Beğen">👍</button>
                        <button onclick="sendReaction(<?php echo $post['id']; ?>, 'love', event)" class="hover:scale-125 transition-transform text-2xl" title="Bayıldım">❤️</button>
                        <button onclick="sendReaction(<?php echo $post['id']; ?>, 'haha', event)" class="hover:scale-125 transition-transform text-2xl" title="Komik">😂</button>
                        <button onclick="sendReaction(<?php echo $post['id']; ?>, 'wow', event)" class="hover:scale-125 transition-transform text-2xl" title="Şaşırdım">😮</button>
                        <button onclick="sendReaction(<?php echo $post['id']; ?>, 'sad', event)" class="hover:scale-125 transition-transform text-2xl" title="Üzüldüm">😢</button>
                        <button onclick="sendReaction(<?php echo $post['id']; ?>, 'angry', event)" class="hover:scale-125 transition-transform text-2xl" title="Kızgın">😡</button>
                     </div>

                     <button id="like-btn-<?php echo $post['id']; ?>" 
                             onclick="toggleLike(<?php echo $post['id']; ?>, this, event)" 
                             class="flex items-center gap-2 py-2 px-2 rounded-lg transition-colors bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 <?php 
                                 $r_colors = [
                                     'like' => 'text-blue-500', 
                                     'love' => 'text-red-500', 
                                     'haha' => 'text-yellow-500',
                                     'wow' => 'text-yellow-500',
                                     'sad' => 'text-yellow-500', 
                                     'angry' => 'text-orange-500'
                                 ];
                                 echo $post['my_reaction'] ? ($r_colors[$post['my_reaction']] ?? 'text-pink-500') : 'text-slate-500 dark:text-slate-400'; 
                             ?>">
                         
                         <?php 
                         $r_icons = [
                             'like' => 'fas fa-thumbs-up', 'love' => 'fas fa-heart', 
                             'haha' => 'fas fa-laugh-squint', 'wow' => 'fas fa-surprise', 
                             'sad' => 'fas fa-sad-tear', 'angry' => 'fas fa-angry'
                         ];
                         ?>
                         <i class="<?php echo $post['my_reaction'] ? ($r_icons[$post['my_reaction']] ?? 'fas fa-heart') : 'far fa-thumbs-up'; ?> text-xl mb-[2px]"></i>
                         <span class="font-bold text-sm"><?php echo $lang == 'en' ? 'Like' : 'Beğen'; ?></span>
                     </button>
                 </div>
                 
                 <div class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg py-2 px-3 transition-colors text-slate-500 dark:text-slate-400">
                     <i class="far fa-comment text-xl"></i>
                     <span class="text-sm font-bold"><?php echo $lang == 'en' ? 'Comment' : 'Yorum Yap'; ?></span>
                 </div>
                 
                 <?php
                 $post_share_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com') . '/post_detail?id=' . $post['id'];
                 ?>
                 <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($post_share_url); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg py-2 px-3 transition-colors text-slate-500 dark:text-slate-400 hover:text-[#1877F2]">
                     <i class="fab fa-facebook-f text-xl"></i>
                     <span class="text-sm font-bold"><?php echo $lang == 'en' ? 'Share' : 'Paylaş'; ?></span>
                 </a>
                 
                 <button onclick="toggleTranslation(<?php echo $post['id']; ?>)" 
                         class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-pink-500 transition-all ml-auto"
                         id="trans-btn-<?php echo $post['id']; ?>" title="<?php echo $lang == 'en' ? 'See Translation' : 'Çeviriyi Gör'; ?>">
                     <i class="fas fa-language text-xl"></i>
                 </button>
             </div>

             <!-- Comments Section (Always Visible) -->
             <div class="border-t border-slate-100 dark:border-slate-700 pt-6">
                 <h3 class="font-bold text-slate-800 dark:text-white mb-4"><?php echo $lang == 'en' ? 'Comments' : 'Yorumlar'; ?></h3>
                                  <!-- Comment Form -->
                  <?php if(isset($_SESSION['user_id'])): ?>
                  <div class="flex gap-3 mb-6 items-center">
                      <img src="<?php echo $_SESSION['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name'] ?? 'User'); ?>" class="w-10 h-10 rounded-full object-cover flex-shrink-0" loading="lazy">
                      <input type="text" id="comment-input" class="flex-1 bg-slate-100 dark:bg-slate-900 border-0 rounded-full py-3 px-5 focus:outline-none focus:ring-2 focus:ring-blue-500/30 transition-all" placeholder="<?php echo $lang == 'en' ? 'Write a comment...' : 'Yorum yaz...'; ?>">
                      <button onclick="postComment(<?php echo $post['id']; ?>)" class="w-10 h-10 bg-blue-500 rounded-full text-white flex items-center justify-center hover:bg-blue-600 transition-colors shadow-md flex-shrink-0">
                          <i class="fas fa-paper-plane text-sm"></i>
                      </button>
                  </div>
                  <?php else: ?>
                  <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 mb-6 text-center border border-slate-100 dark:border-slate-800">
                      <p class="text-sm text-slate-500 mb-2"><?php echo $lang == 'en' ? 'Log in to join the conversation' : 'Sohbete katılmak için giriş yapın'; ?></p>
                      <a href="login" class="inline-block bg-pink-500 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-pink-500/20 hover:bg-pink-600 transition-all hover:scale-105">
                          <?php echo $lang == 'en' ? 'Login' : 'Giriş Yap'; ?>
                      </a>
                  </div>
                  <?php endif; ?>

                 <!-- Comments List -->
                 <div id="comments-list" class="space-y-4">
                     <?php
                     // Fetch Comments
                     $stmt = $pdo->prepare("SELECT c.*, u.full_name, u.avatar, u.username FROM post_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                     $stmt->execute([$post['id']]);
                     $all_comments = $stmt->fetchAll();

                     // Group Comments
                     $parents = [];
                     $replies = [];

                     foreach ($all_comments as $c) {
                         if ($c['parent_id']) {
                             $replies[$c['parent_id']][] = $c;
                         } else {
                             $parents[] = $c;
                         }
                     }
                     
                     // Display Function
                     $renderComment = function($comment, $is_reply = false) use ($replies, $lang, $post) {
                         global $t; 
                         $margin = $is_reply ? 'ml-12 border-l-2 border-slate-100 dark:border-slate-700 pl-4' : '';
                         $bg = $is_reply ? 'bg-slate-50/50 dark:bg-slate-800/50' : 'bg-slate-50 dark:bg-slate-900';
                         
                         // Permission Check: yorum sahibi, gönderi sahibi veya founder/moderator
                         $can_delete = false;
                         if (isset($_SESSION['user_id'])) {
                             if ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['user_id'] == $post['user_id'] || in_array($_SESSION['badge'] ?? '', ['founder', 'moderator'])) {
                                 $can_delete = true;
                             }
                         }
                         ?>
                         <div id="comment-row-<?php echo $comment['id']; ?>" class="flex gap-3 <?php echo $margin; ?>">
                             <a href="profile?uid=<?php echo $comment['user_id']; ?>">
                                 <img src="<?php echo $comment['avatar']; ?>" class="w-8 h-8 rounded-full object-cover">
                             </a>
                             <div class="flex-1">
                                 <div class="<?php echo $bg; ?> rounded-2xl rounded-tl-none p-3 relative group">
                                     <div class="flex justify-between items-center mb-1">
                                         <a href="profile?uid=<?php echo $comment['user_id']; ?>" class="text-sm font-bold text-slate-800 dark:text-white hover:underline">
                                             <?php echo htmlspecialchars($comment['full_name']); ?>
                                         </a>
                                         <div class="flex items-center gap-2">
                                             <span class="text-xs text-slate-400"><?php echo date('d.m H:i', strtotime($comment['created_at'])); ?></span>
                                             <?php if($can_delete): ?>
                                             <button onclick="deleteComment(<?php echo $comment['id']; ?>)" class="text-slate-300 hover:text-red-500 transition-colors" title="<?php echo $lang == 'en' ? 'Delete' : 'Sil'; ?>">
                                                 <i class="fas fa-trash text-xs"></i>
                                             </button>
                                             <?php endif; ?>
                                         </div>
                                     </div>
                                      <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed" id="comment-original-<?php echo $comment['id']; ?>">
                                          <?php echo formatContent($comment['content']); ?>
                                      </p>
                                      <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed hidden italic border-l-2 border-pink-500 pl-3 mt-1" id="comment-translated-<?php echo $comment['id']; ?>"></p>
                                      
                                      <div class="flex gap-3 mt-2">
                                          <button onclick="translateComment(<?php echo $comment['id']; ?>)" class="text-xs font-bold text-slate-400 hover:text-violet-500 transition-colors flex items-center gap-1" id="trans-btn-c-<?php echo $comment['id']; ?>">
                                              <i class="fas fa-language"></i> <?php echo $t['translate'] ?? ($lang == 'en' ? 'Translate' : 'Çevir'); ?>
                                          </button>
                                          <!-- Reply Button moved here for better layout -->
                                          <button onclick="prepareReply(<?php echo $comment['id']; ?>, '@<?php echo addslashes($comment['username']); ?>')" class="text-xs font-bold text-slate-400 hover:text-pink-500 transition-colors">
                                              <?php echo $lang == 'en' ? 'Reply' : 'Yanıtla'; ?>
                                          </button>
                                      </div>
                                 </div>
                             </div>
                         </div>
                         <?php
                     };

                     foreach($parents as $parent):
                         $renderComment($parent);
                         if (isset($replies[$parent['id']])) {
                             foreach ($replies[$parent['id']] as $reply) {
                                 $renderComment($reply, true);
                             }
                         }
                     endforeach;
                     ?>

                 </div>
             </div>
        </div>
    </main>

    <div id="mention-dropdown" class="hidden fixed bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-100 dark:border-slate-700 z-[100] max-h-60 overflow-y-auto w-64"></div>
    <script src="js/mentions.js" defer></script>

    <script>
    let currentParentId = null;
    
    // Global Configuration & Translations
    const POST_CONFIG = {
        lang: <?php echo json_encode($lang ?? 'tr'); ?>,
        translations: {
            deleteComment: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Delete this comment?' : 'Bu yorumu silmek istiyor musunuz?'); ?>,
            deletePost: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Are you sure you want to delete this post?' : 'Bu gönderiyi silmek istediğinizden emin misiniz?'); ?>,
            postDeleted: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Post deleted successfully' : 'Gönderi başarıyla silindi'); ?>,
            addedCollection: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Added to collection!' : 'Koleksiyona eklendi!'); ?>,
            error: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Error' : 'Hata'); ?>,
            connectionError: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Connection error' : 'Bağlantı hatası'); ?>,
            translate: <?php echo json_encode($t["translate"] ?? (($lang ?? 'tr') == "en" ? "Translate" : "Çevir")); ?>,
            original: <?php echo json_encode(($lang ?? 'tr') == "en" ? "Original" : "Orijinal"); ?>,
            savedToast: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Saved to Private Items' : 'Kaydedilenlere Eklendi'); ?>,
            addToCollection: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Add to Collection' : 'Koleksiyona Ekle'); ?>,
            noCollections: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'No collections found' : 'Koleksiyon bulunamadı'); ?>,
            newCollection: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'New Collection' : 'Yeni Koleksiyon Oluştur'); ?>,
            cancel: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Cancel' : 'İptal'); ?>,
            saveChanges: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Save Changes' : 'Kaydet'); ?>,
            replyingTo: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Replying to nested comment...' : 'Yoruma yanıt veriliyor...'); ?>,
            deletePostConfirm: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Are you sure you want to delete this post?' : 'Bu gönderiyi silmek istediğinizden emin misiniz?'); ?>,
            postDeleted: <?php echo json_encode(($lang ?? 'tr') == 'en' ? 'Post deleted successfully' : 'Gönderi başarıyla silindi'); ?>
        }
    };

    async function deleteComment(commentId) {
        const title = POST_CONFIG.lang === 'en' ? 'Delete Comment' : 'Yorumu Sil';
        
        KalkanModal.showConfirm(
            title,
            POST_CONFIG.translations.deleteComment,
            async () => {
                try {
                    const formData = new FormData();
                    formData.append('comment_id', commentId);
                    
                    const res = await fetch('api/delete_comment.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        const row = document.getElementById('comment-row-' + commentId);
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    } else {
                        KalkanModal.showAlert(POST_CONFIG.translations.error, data.message || 'Error');
                    }
                } catch(e) {
                    console.error(e);
                    KalkanModal.showAlert(POST_CONFIG.translations.error, POST_CONFIG.translations.connectionError);
                }
            }
        );
    }

    /* Save Post Logic */
    async function toggleSave(postId, btn) {
        btn.disabled = true;
        const icon = btn.querySelector('i');
        const textSpan = btn.querySelector('.save-text');
        const isSaved = icon.classList.contains('fas');
        const lang = document.documentElement.lang || 'tr';
        
        // Optimistic Update
        if(isSaved) {
            icon.classList.replace('fas', 'far'); // Unsaving
            icon.classList.remove('text-yellow-500');
            if(textSpan) textSpan.innerText = lang === 'en' ? 'Save' : 'Kaydet';
        } else {
            icon.classList.replace('far', 'fas'); // Saving
            icon.classList.add('text-yellow-500');
            icon.classList.add('scale-125');
            setTimeout(()=>icon.classList.remove('scale-125'), 300);
            if(textSpan) textSpan.innerText = lang === 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar';
        }

        try {
            const formData = new FormData();
            formData.append('action', 'toggle_save');
            formData.append('post_id', postId);
            
            const response = await fetch('api/save_post.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if(data.status === 'success') {
                if(data.is_saved) {
                    showSavedToast(postId);
                }
            } else {
                // Revert on error
                if(isSaved) {
                   icon.classList.replace('far', 'fas');
                   icon.classList.add('text-yellow-500');
                   if(textSpan) textSpan.innerText = lang === 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar';
                } else {
                   icon.classList.replace('fas', 'far');
                   icon.classList.remove('text-yellow-500');
                   if(textSpan) textSpan.innerText = lang === 'en' ? 'Save' : 'Kaydet';
                }
            }
        } catch(e) { console.error(e); }
        btn.disabled = false;
    }

    function showSavedToast(postId) {
        // Remove existing toast
        const existing = document.getElementById('saved-toast');
        if(existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.id = 'saved-toast';
        toast.className = 'fixed bottom-24 left-1/2 transform -translate-x-1/2 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-4 z-[100] animate-in slide-in-from-bottom-5 fade-in duration-300 min-w-[300px] border border-slate-700/50';
        toast.innerHTML = `
            <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-check text-white text-lg"></i>
            </div>
            <div class="flex-1">
                <p class="font-bold text-sm mb-0.5">${POST_CONFIG.translations.savedToast}</p>
                <button onclick="promptAddToCollection(${postId})" class="text-xs text-yellow-400 font-bold hover:underline">
                    ${POST_CONFIG.translations.addToCollection}
                </button>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-500 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        `;
        document.body.appendChild(toast);
        
        // Auto remove after 5s
        setTimeout(() => {
            if(toast && document.body.contains(toast)) {
                toast.classList.add('opacity-0', 'translate-y-4');
                setTimeout(()=>toast.remove(), 300);
            }
        }, 5000);
    }
    
    async function promptAddToCollection(postId) {
         const toast = document.getElementById('saved-toast');
         if(toast) toast.remove();
         
         // Fetch collections first
         try {
             const formData = new FormData();
             formData.append('action', 'get_collections');
             const res = await fetch('api/save_post.php', { method: 'POST', body: formData });
             const data = await res.json();
             
             if(data.status === 'success') {
                 showCollectionModal(postId, data.collections);
             }
         } catch(e) { console.error(e); }
    }
    
    function showCollectionModal(postId, collections) {
        let listHtml = '';
        if(collections.length > 0) {
            collections.forEach(col => {
                 listHtml += `
                    <button onclick="addToCollection(${postId}, ${col.id})" class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl flex items-center gap-3 transition-colors mb-1">
                        <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-500">
                            <i class="fas fa-folder"></i>
                        </div>
                        <span class="font-bold text-slate-700 dark:text-slate-200">${col.name}</span>
                    </button>
                 `;
            });
        } else {
            listHtml = `<p class="text-center text-slate-500 py-4 italic">${POST_CONFIG.translations.noCollections}</p>`;
        }

        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200';
        modal.onclick = (e) => { if(e.target === modal) modal.remove(); };
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2rem] shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200 relative">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-lg dark:text-white">${POST_CONFIG.translations.addToCollection}</h3>
                     <button onclick="this.closest('.fixed').remove()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-red-500 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="p-4 max-h-[50vh] overflow-y-auto custom-scrollbar">
                    ${listHtml}
                </div>
                
                <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <button onclick="location.href='saved'" class="w-full py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-xl shadow-lg shadow-yellow-500/20 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> ${POST_CONFIG.translations.newCollection}
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    async function addToCollection(postId, collectionId) {
         try {
             const formData = new FormData();
             formData.append('action', 'add_to_collection');
             formData.append('post_id', postId);
             formData.append('collection_id', collectionId);
             
             const res = await fetch('api/save_post.php', { method: 'POST', body: formData });
             const data = await res.json();
             
             if(data.status === 'success') {
                 // Close all modals
                 document.querySelectorAll('.fixed.z-\\[110\\]').forEach(m => m.remove());
                 alert(POST_CONFIG.translations.addedCollection);
             }
         } catch(e) { console.error(e); }
    }

    /* REACTION SYSTEM JS (Ported from index.php) */
    function toggleLike(postId, btn, event) {
        if(event) event.preventDefault();
        sendReaction(postId, 'like', event);
    }
    
    async function sendReaction(postId, type, event) {
        if(event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        // In post_detail, the button might have different structure or ID.
        // Let's ensure the IDs match what we add to the button.
        // For detail view, we might not need the "like-btn-ID" pattern if there is only one, 
        // but keeping it consistent is best.
        
        const btn = document.getElementById('like-btn-' + postId);
        const icon = btn.querySelector('i');
        const countSpan = document.getElementById('like-count-' + postId); // We need to add this ID to the span
        
        const icons = {
            'like': 'fas fa-thumbs-up',
            'love': 'fas fa-heart',
            'haha': 'fas fa-laugh-squint',
            'wow': 'fas fa-surprise',
            'sad': 'fas fa-sad-tear',
            'angry': 'fas fa-angry'
        };
        const colors = {
            'like': 'text-blue-500', 
            'love': 'text-red-500',
            'haha': 'text-yellow-500',
            'wow': 'text-yellow-500',
            'sad': 'text-yellow-500', 
            'angry': 'text-orange-500'
        };

        btn.className = `flex items-center gap-2 transition-colors text-sm ${colors[type] || 'text-pink-500'}`;
        icon.className = `${icons[type] || 'fas fa-heart'} transform transition-transform duration-300 text-xl scale-125`;
        setTimeout(() => icon.classList.remove('scale-125'), 200);

        // Play Lottie Animation
        if (type === 'like' || type === 'love') {

        }

        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('reaction_type', type);
            
            const res = await fetch('api/like_post.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                if(countSpan) countSpan.innerText = data.count;
                if (!data.reacted && !data.reaction) {
                     resetLikeUI(postId, btn, icon);
                }
            }
        } catch(e) {
            console.error(e);
        }
    }

    function resetLikeUI(postId, btn, icon) {
        btn.className = 'flex items-center gap-2 transition-colors text-sm hover:text-pink-500 text-slate-400';
        icon.className = 'fas fa-thumbs-up transform transition-transform duration-300 text-xl'; // Default icon
    }

    /* Post Management Logic */
    function togglePostMenu(postId) {
        const menu = document.getElementById('post-menu-' + postId);
        if(menu) menu.classList.toggle('hidden');
    }

    // Close menus when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.fa-ellipsis-h') && !event.target.closest('button')) {
            const menus = document.querySelectorAll('[id^=post-menu-]:not([id*="container"])');
            menus.forEach(menu => {
                if (!menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                }
            });
        }
    }

    async function confirmDeletePost(postId) {
        if(!confirm(POST_CONFIG.translations.deletePost)) return;
        
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            const response = await fetch('api/delete_post.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if(data.success) {
                alert(POST_CONFIG.translations.postDeleted);
                window.location.href = 'feed';
            } else {
                alert(data.error || 'Delete failed');
            }
        } catch(e) { console.error(e); }
    }

    function editPost(postId) {
        togglePostMenu(postId);
        // Note: For detail view, we might need to target the content wrapper correctly
        // Assuming the structure is similar to feed but simplified.
        // Let's verify: The content wrapper in post_detail.php line 153 is actually just "div class=mb-4"
        // We need to give it an ID to make this work!
        const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
        if (!contentWrapper) {
             console.error("Content wrapper not found! Please reload.");
             return;
        }

        const originalP = document.getElementById('post-original-' + postId);
        
        let text = "";
        if (originalP) {
             text = originalP.innerText;
        } else {
             // Fallback if ID is missing (which it is currently)
             const p = contentWrapper.querySelector('p');
             text = p.innerText;
             // Add ID for consistency
             p.id = 'post-original-' + postId;
        }
        
        contentWrapper.dataset.originalHtml = contentWrapper.innerHTML;
        
        contentWrapper.innerHTML = `
            <textarea id="edit-input-${postId}" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 mb-2 resize-none transition-all dark:text-white" rows="4">${text}</textarea>
            <div class="flex gap-2 justify-end">
                <button onclick="cancelEdit(${postId})" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    ${POST_CONFIG.translations.cancel}
                </button>
                <button onclick="savePost(${postId})" class="px-3 py-1.5 text-xs font-bold text-white bg-pink-500 hover:bg-pink-600 rounded-lg shadow-lg shadow-pink-500/30 transition-all">
                    ${POST_CONFIG.translations.saveChanges}
                </button>
            </div>
        `;
        document.getElementById('edit-input-' + postId).focus();
    }

    function cancelEdit(postId) {
        const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
        if(contentWrapper.dataset.originalHtml) {
            contentWrapper.innerHTML = contentWrapper.dataset.originalHtml;
        }
    }

    async function savePost(postId) {
        const newVal = document.getElementById('edit-input-' + postId).value;
        
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('content', newVal);
            
            const response = await fetch('api/edit_post.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if(data.success) {
                const contentWrapper = document.getElementById('post-content-wrapper-' + postId);
                contentWrapper.innerHTML = `
                    <p id="post-original-${postId}" class="text-slate-800 dark:text-slate-200 leading-relaxed text-lg transition-all duration-300">
                        ${newVal.replace(/\n/g, '<br>')}
                    </p>
                `;
            } else {
                alert(data.error || 'Update failed');
            }
        } catch(e) { console.error(e); }
    }


    function prepareReply(parentId, username) {
        currentParentId = parentId;
        const input = document.getElementById('comment-input');
        input.value = username + ' ';
        input.focus();
        
        // Visual indicator
        let indicator = document.getElementById('reply-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'reply-indicator';
            indicator.className = 'text-xs text-pink-500 mb-1 ml-12 font-bold flex items-center gap-2';
            input.parentElement.parentElement.insertBefore(indicator, input.parentElement);
        }
        indicator.innerHTML = `<span>${POST_CONFIG.translations.replyingTo}</span> <button onclick="cancelReply()" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>`;
    }

    function cancelReply() {
        currentParentId = null;
        document.getElementById('comment-input').value = '';
        const indicator = document.getElementById('reply-indicator');
        if (indicator) indicator.remove();
    }

    function postComment(postId) {
        const input = document.getElementById('comment-input');
        const content = input.value.trim();
        
        if (!content) return;

        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('content', content);
        if (currentParentId) {
            formData.append('parent_id', currentParentId);
        }

        fetch('api/post_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message || 'Error posting comment');
            }
        });
    }

    async function toggleTranslation(postId) {
        const originalP = document.getElementById('post-original-' + postId);
        const translatedP = document.getElementById('post-translated-' + postId);
        const btn = document.getElementById('trans-btn-' + postId);
        const btnText = document.getElementById('trans-text-' + postId); // May be null if icon-only
        
        const currentLang = POST_CONFIG.lang;
        
        const isShowingOriginal = !originalP.classList.contains('hidden');
        
        if (isShowingOriginal) {
            if (!translatedP.innerHTML.trim()) {
                const originalText = originalP.innerText.trim();
                const oldBtnText = btnText ? btnText.innerText : null;
                const oldIcon = btn ? btn.querySelector('i') : null;
                
                if (oldIcon) oldIcon.className = 'fas fa-circle-notch fa-spin text-xl';
                
                try {
                    const formData = new FormData();
                    formData.append('text', originalText);
                    formData.append('target_lang', currentLang);
                    
                    const response = await fetch('api/translate.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    
                    if (data.success) {
                        translatedP.innerText = data.translated_text;
                        if (oldIcon) oldIcon.className = 'fas fa-language text-xl';
                    } else {
                        alert(data.error || 'Translation failed');
                        if (btnText) btnText.innerText = oldBtnText;
                        if (oldIcon) oldIcon.className = 'fas fa-language text-xl';
                        return;
                    }
                } catch (error) {
                    console.error(error);
                    alert('Translation error. Make sure API is running.');
                    if (btnText) btnText.innerText = oldBtnText;
                    if (oldIcon) oldIcon.className = 'fas fa-language text-xl';
                    return;
                }
            }
            
            originalP.style.opacity = '0';
            setTimeout(() => {
                originalP.classList.add('hidden');
                translatedP.classList.remove('hidden');
                void translatedP.offsetWidth; 
                translatedP.style.opacity = '1';
            }, 300);
            
            if (btnText) btnText.innerText = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
            if (btn) btn.title = currentLang === 'en' ? 'See Original' : 'Orijinali Gör';
        } else {
            translatedP.style.opacity = '0';
            setTimeout(() => {
                translatedP.classList.add('hidden');
                originalP.classList.remove('hidden');
                void originalP.offsetWidth;
                originalP.style.opacity = '1';
            }, 300);
            
            if (btnText) btnText.innerText = currentLang === 'en' ? 'See Translation' : 'Çeviriyi Gör';
            if (btn) btn.title = currentLang === 'en' ? 'See Translation' : 'Çeviriyi Gör';
        }
    }

    /* Comment Translation Logic */
    async function translateComment(commentId) {
        const originalP = document.getElementById('comment-original-' + commentId);
        const translatedP = document.getElementById('comment-translated-' + commentId);
        const btn = document.getElementById('trans-btn-c-' + commentId);
        
        const currentLang = POST_CONFIG.lang;
        // Simple toggle check: if translated is visible, we are "translated", else "original"
        const isTranslated = !translatedP.classList.contains('hidden');
        
        if (isTranslated) {
            // Revert to original
            translatedP.classList.add('hidden');
            originalP.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-language"></i> ' + POST_CONFIG.translations.translate;
            btn.classList.remove('text-violet-600');
            btn.classList.add('text-slate-400');
        } else {
            // Show translation
            if (!translatedP.innerText.trim()) {
                const originalText = originalP.innerText.trim();
                const originalIcon = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                try {
                    const formData = new FormData();
                    formData.append('text', originalText);
                    formData.append('target_lang', currentLang);
                    
                    const response = await fetch('api/translate.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    
                    if (data.success) {
                        translatedP.innerText = data.translated_text;
                    } else {
                        alert('Translation failed');
                        btn.innerHTML = originalIcon;
                        return;
                    }
                } catch (e) {
                    console.error(e);
                    btn.innerHTML = originalIcon;
                    return;
                }
            }
            
            originalP.classList.add('hidden');
            translatedP.classList.remove('hidden');
            btn.innerHTML = '<i class="fas fa-undo"></i> ' + POST_CONFIG.translations.original;
            btn.classList.add('text-violet-600');
            btn.classList.remove('text-slate-400');
        }
    }
    </script>


<!-- Reaction Details Modal -->
<div id="reactionModalOverlay" class="fixed inset-0 bg-black/60 z-[100] hidden flex items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeReactionModal()">
    <div onclick="event.stopPropagation()" class="bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="reactionModalContent">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50 backdrop-blur">
            <h3 class="font-bold text-lg dark:text-white">Beğeniler</h3>
            <button onclick="closeReactionModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 hover:text-pink-500 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-0 max-h-[60vh] overflow-y-auto custom-scrollbar" id="reactionList">
            <!-- Content -->
        </div>
    </div>
</div>

<script>
function showReactionDetails(postId) {
    const overlay = document.getElementById('reactionModalOverlay');
    const content = document.getElementById('reactionModalContent');
    const list = document.getElementById('reactionList');
    
    // Show Modal
    overlay.classList.remove('hidden');
    setTimeout(() => {
        overlay.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);

    list.innerHTML = '<div class="flex justify-center p-8"><i class="fas fa-spinner fa-spin text-pink-500 text-2xl"></i></div>';

    fetch(`api/get_post_reactions.php?post_id=${postId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.reactions.length > 0) {
                let html = '';
                const icons = {
                    'like': '👍', 'love': '❤️', 'haha': '😂', 
                    'wow': '😮', 'sad': '😢', 'angry': '😡'
                };
                
                data.reactions.forEach(user => {
                    html += `
                        <a href="profile?uid=${user.user_id}" class="flex items-center gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-b border-slate-50 dark:border-slate-800/50 last:border-0">
                            <div class="relative">
                                <img src="${user.avatar}" class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                                <span class="absolute -bottom-1 -right-1 text-xs bg-white dark:bg-slate-800 rounded-full w-5 h-5 flex items-center justify-center shadow-sm border border-slate-100 dark:border-slate-700">
                                    ${icons[user.reaction_type] || '👍'}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white truncate flex items-center gap-1">
                                    ${user.full_name || user.username}
                                    ${user.badge === 'verified' ? '<i class="fas fa-check-circle text-blue-500 text-xs"></i>' : ''}
                                </h4>
                                <p class="text-xs text-slate-500 truncate">@${user.username}</p>
                            </div>
                        </a>
                    `;
                });
                list.innerHTML = html;
            } else {
                list.innerHTML = '<div class="text-center p-8 text-slate-500 dark:text-slate-400">Henüz beğeni yok.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            list.innerHTML = '<div class="text-center p-8 text-red-500">Bir hata oluştu.</div>';
        });
}

function closeReactionModal() {
    const overlay = document.getElementById('reactionModalOverlay');
    const content = document.getElementById('reactionModalContent');
    
    overlay.classList.add('opacity-0');
    content.classList.add('scale-95', 'opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
    }, 300);
}

// Full Screen Image Viewer
let imageSwiper = null;

function openImageViewer(imageUrl) {
    const overlay = document.getElementById('imageViewerOverlay');
    const swiperWrapper = document.getElementById('imageViewerWrapper');
    
    // Clear previous images
    swiperWrapper.innerHTML = `
        <div class="swiper-slide">
            <img src="${imageUrl}" alt="Full Image">
        </div>
    `;
    
    // Show overlay
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Initialize Swiper
    if (imageSwiper) {
        imageSwiper.destroy();
    }
    
    imageSwiper = new Swiper('#imageViewerSwiper', {
        zoom: {
            maxRatio: 3,
            minRatio: 1
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        keyboard: {
            enabled: true,
        },
    });
}

function closeImageViewer() {
    const overlay = document.getElementById('imageViewerOverlay');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
    
    if (imageSwiper) {
        imageSwiper.destroy();
        imageSwiper = null;
    }
}

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageViewer();
    }
});
</script>

<!-- Full Screen Image Viewer -->
<div id="imageViewerOverlay" class="image-viewer-overlay" onclick="if(event.target === this) closeImageViewer()">
    <div class="image-viewer-container">
        <button class="image-viewer-close" onclick="closeImageViewer()">
            <i class="fas fa-times"></i>
        </button>
        
        <div id="imageViewerSwiper" class="swiper h-full">
            <div id="imageViewerWrapper" class="swiper-wrapper">
                <!-- Images will be loaded here -->
            </div>
            
            <!-- Navigation -->
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            
            <!-- Pagination -->
            <div class="swiper-pagination"></div>
        </div>
    </div>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</body>
</html>
