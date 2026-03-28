<?php
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once '../includes/hashtag_helper.php';
session_start();

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 10;
$current_user_id = $_SESSION['user_id'] ?? 0;

// Check if wall_user_id column exists
$wall_column_exists = false;
try {
    $check = $pdo->query("SHOW COLUMNS FROM posts LIKE 'wall_user_id'");
    $wall_column_exists = $check->rowCount() > 0;
} catch (Exception $e) {}

$wall_condition = $wall_column_exists ? "AND (p.wall_user_id IS NULL OR p.wall_user_id = p.user_id)" : "";

// Standardized Query from feed.php
$name_field = isset($_SESSION['language']) && $_SESSION['language'] == 'tr' ? 'name_tr' : 'name_en';

$sql = "
    SELECT 
        'regular' as post_type,
        p.id, p.user_id, p.content, p.created_at, p.like_count, p.location,
        COALESCE(p.image_url, p.media_url) as image, NULL as group_id, NULL as group_name,
        u.username, u.full_name, u.avatar, u.badge, u.venue_name, u.last_seen,
        " . ($wall_column_exists ? "p.wall_user_id," : "NULL as wall_user_id,") . "

        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
        (SELECT reaction_type FROM post_likes WHERE post_id = p.id AND user_id = ?) as my_reaction,
        (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id AND user_id = ?) as is_saved,
        (SELECT GROUP_CONCAT(reaction_type) FROM post_likes WHERE post_id = p.id) as top_reactions,
        (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
        
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
    WHERE 1=1 $wall_condition AND p.deleted_at IS NULL

    UNION ALL

    SELECT 
        'group' as post_type,
        gp.id, gp.user_id, gp.content, gp.created_at, 0 as like_count, NULL as location,
        gp.image, gp.group_id, g.$name_field as group_name,
        u.username, u.full_name, u.avatar, u.badge, u.venue_name, u.last_seen,
        NULL as wall_user_id,
        gp.comment_count,
        NULL as my_reaction,
        NULL as is_saved,
        NULL as top_reactions,
        (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as expert_badges,
        NULL as shared_from_id, NULL as shared_content, NULL as shared_media, NULL as shared_media_type, NULL as shared_username, NULL as shared_fullname, NULL as shared_avatar
    FROM group_posts gp 
    JOIN users u ON gp.user_id = u.id 
    JOIN groups g ON gp.group_id = g.id
    WHERE g.privacy = 'public' OR g.id IN (SELECT group_id FROM group_members WHERE user_id = ?)

    ORDER BY created_at DESC 
    LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
$posts = $stmt->fetchAll();

if (empty($posts)) {
    echo ''; // No more posts
    exit;
}

// Render Posts (Copying structure from feed.php)
foreach($posts as $post): 
?>
<div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl p-6 rounded-[2rem] shadow-lg border border-white/20 dark:border-slate-700/50 hover:-translate-y-1 hover:shadow-2xl hover:shadow-pink-500/10 transition-all duration-500 group mb-4" data-post-id="<?php echo $post['id']; ?>">
    <!-- Header -->
    <div class="flex justify-between items-start mb-4">
        <div class="flex gap-3">
             <a href="profile.php?uid=<?php echo $post['user_id']; ?>" aria-label="<?php echo htmlspecialchars($post['full_name']); ?> profile" class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden">
                  <?php $p_avatar = !empty($post['avatar']) ? $post['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['full_name'] ?? 'User') . '&background=random'; ?>
                  <img src="<?php echo $p_avatar; ?>" class="w-full h-full object-cover">
             </a>
            <div>
                <div class="flex items-center gap-2">
                    <a href="profile.php?uid=<?php echo $post['user_id']; ?>" class="font-bold hover:underline text-sm dark:text-white">
                        <?php echo htmlspecialchars($post['full_name']); ?>
                    </a>
                    <?php if($post['badge'] == 'founder') echo '<i class="fas fa-shield-alt text-pink-500 text-xs" title="Kurucu"></i>'; ?>
                    <?php if($post['badge'] == 'moderator') echo '<i class="fas fa-gavel text-blue-500 text-xs" title="Moderatör"></i>'; ?>
                    <?php if($post['badge'] == 'business') echo '<i class="fas fa-check-circle text-green-500 text-xs" title="İşletme"></i>'; ?>
                    
                    <?php 
                    if($post['expert_badges']) {
                        $badges = explode(',', $post['expert_badges']);
                        foreach($badges as $badge) {
                            echo '<span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 text-[10px] font-bold border border-yellow-200">' . ucfirst($badge) . '</span>';
                        }
                    }
                    ?>
                </div>
                <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('d.m H:i', strtotime($post['created_at'])); ?></span>
            </div>
        </div>
        
        <!-- Post Menu (Edit/Delete) -->
        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
        <div class="relative" id="post-menu-container-<?php echo $post['id']; ?>">
            <button onclick="togglePostMenu(<?php echo $post['id']; ?>)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            <div id="post-menu-<?php echo $post['id']; ?>" class="hidden absolute right-0 top-10 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-20">
                <button onclick="editPost(<?php echo $post['id']; ?>)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                    <i class="fas fa-pen text-xs text-blue-500"></i> Edit Post
                </button>
                <button onclick="toggleSave(<?php echo $post['id']; ?>, this)" class="w-full text-left px-4 py-3 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                     <i class="<?php echo ($post['is_saved'] ?? 0) ? 'fas' : 'far'; ?> fa-bookmark text-xs text-yellow-500"></i>
                     <span class="save-text"><?php echo ($post['is_saved'] ?? 0) ? ($lang == 'en' ? 'Unsave' : 'Kaydedilenlerden Çıkar') : ($lang == 'en' ? 'Save' : 'Kaydet'); ?></span>
                </button>
                <button onclick="confirmDeletePost(<?php echo $post['id']; ?>, '<?php echo $post['post_type'] ?? 'regular'; ?>')" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors border-t border-slate-50 dark:border-slate-700">
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
                <button onclick="confirmDeletePost(<?php echo $post['id']; ?>, '<?php echo $post['post_type'] ?? 'regular'; ?>')" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-3 transition-colors border-t border-slate-50 dark:border-slate-700">
                    <i class="fas fa-trash text-xs"></i> <?php echo isset($t['delete_post']) ? $t['delete_post'] : ($lang == 'en' ? 'Delete Post' : 'Gönderiyi Sil'); ?>
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
    <div id="post-content-wrapper-<?php echo $post['id']; ?>" class="mb-3 group/trans cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 -mx-6 px-6 py-2 transition-colors" onclick="location.href='post_detail.php?id=<?php echo $post['id']; ?>'">
        <p id="post-original-<?php echo $post['id']; ?>" class="text-slate-800 dark:text-slate-200 leading-relaxed text-base transition-all duration-300">
            <?php
            $content = $post['content'];
            $prev = '';
            while ($prev !== $content) { $prev = $content; $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8'); }
            echo nl2br(linkifyHashtags($content));
            ?>
        </p>
        <p id="post-translated-<?php echo $post['id']; ?>" class="hidden text-slate-800 dark:text-slate-200 leading-relaxed text-base italic border-l-4 border-pink-500 pl-4 transition-all duration-300">
        </p>

        <!-- Shared Content Card -->
        <?php if(!empty($post['shared_from_id'])): ?>
        <div class="mt-3 border border-slate-200 dark:border-slate-700 rounded-xl p-4 bg-slate-50 dark:bg-slate-800/50">
            <div class="flex items-center gap-2 mb-2">
                 <?php $s_avatar = !empty($post['shared_avatar']) ? $post['shared_avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['shared_fullname'] ?? 'User') . '&background=random'; ?>
                 <img src="<?php echo $s_avatar; ?>" class="w-6 h-6 rounded-full object-cover">
                 <div class="flex flex-col">
                     <span class="text-sm font-bold dark:text-slate-200"><?php echo htmlspecialchars($post['shared_fullname']); ?></span>
                     <span class="text-xs text-slate-400">@<?php echo htmlspecialchars($post['shared_username']); ?></span>
                 </div>
            </div>
            <p class="text-sm text-slate-700 dark:text-slate-300 mb-2"><?php echo nl2br(linkifyHashtags($post['shared_content'] ?? '')); ?></p>
            <?php if(!empty($post['shared_media'])): ?>
                <?php if($post['shared_media_type'] == 'image'): ?>
                    <img src="<?php echo htmlspecialchars($post['shared_media']); ?>" class="rounded-lg w-full object-cover max-h-60">
                <?php elseif($post['shared_media_type'] == 'video'): ?>
                    <div class="relative pt-[56.25%] bg-black rounded-lg overflow-hidden">
                        <iframe src="<?php echo htmlspecialchars($post['shared_media']); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($post['media_url']) && empty($post['shared_from_id'])): ?>
        <?php if($post['media_type'] == 'image'): ?>
        <div class="rounded-2xl overflow-hidden mb-4 border border-slate-100 dark:border-slate-700">
            <img src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full object-cover max-h-[500px]">
        </div>
        <?php elseif($post['media_type'] == 'video'): ?>
        <div class="rounded-2xl overflow-hidden mb-4 border border-slate-100 dark:border-slate-700">
            <?php if(strpos($post['media_url'], 'youtube.com') !== false || strpos($post['media_url'], 'youtu.be') !== false || strpos($post['media_url'], 'vimeo.com') !== false): ?>
                <div class="relative pt-[56.25%] bg-black">
                    <iframe src="<?php echo htmlspecialchars($post['media_url']); ?>" class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <video src="<?php echo htmlspecialchars($post['media_url']); ?>" class="w-full max-h-[500px] bg-black" controls playsinline></video>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Reaction Stats Row (Top) -->
    <?php if($post['like_count'] > 0 || $post['comment_count'] > 0): ?>
    <div class="w-full flex items-center justify-between py-2 px-1 mb-2">
        <div class="flex items-center gap-1 cursor-pointer" onclick="event.stopPropagation(); showReactionDetails(<?php echo $post['id']; ?>)">
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
        
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex gap-6 border-t border-slate-100 dark:border-slate-700 pt-3 text-slate-400">
        
        <!-- Reaction System Button -->
        <div class="relative group/reaction z-10" id="like-wrapper-<?php echo $post['id']; ?>">
            <button type="button" 
                    class="flex items-center gap-2 py-2 px-2 rounded-lg transition-colors bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 <?php 
                        $r_type = $post['my_reaction'];
                        $r_colors = [
                            'like' => 'text-blue-500', 
                            'love' => 'text-red-500',
                            'haha' => 'text-yellow-500',
                            'wow' => 'text-yellow-500',
                            'sad' => 'text-yellow-500', 
                            'angry' => 'text-orange-500'
                        ];
                        echo $r_type ? ($r_colors[$r_type] ?? 'text-pink-500') : 'text-slate-500 dark:text-slate-400'; 
                    ?>" 
                    onclick="toggleLike(<?php echo $post['id']; ?>, this)"
                    ontouchstart="startLongPress(<?php echo $post['id']; ?>)"
                    ontouchend="endLongPress()"
                    ontouchmove="endLongPress()"
                    oncontextmenu="event.preventDefault();"
                    id="like-btn-<?php echo $post['id']; ?>">
                
                <?php 
                $r_icons = [
                    'like' => 'fas fa-thumbs-up',
                    'love' => 'fas fa-heart',
                    'haha' => 'fas fa-laugh-squint',
                    'wow' => 'fas fa-surprise',
                    'sad' => 'fas fa-sad-tear',
                    'angry' => 'fas fa-angry'
                ];
                $icon_class = $r_type ? ($r_icons[$r_type] ?? 'fas fa-heart') : 'far fa-thumbs-up';
                ?>
                <i class="<?php echo $icon_class; ?> text-xl mb-[2px]"></i>
                <span class="font-bold text-sm"><?php echo $lang == 'en' ? 'Like' : 'Beğen'; ?></span>
            </button>

            <!-- Reaction Bar -->
            <div id="reaction-bar-<?php echo $post['id']; ?>" class="absolute bottom-10 left-0 hidden group-hover/reaction:flex bg-white dark:bg-slate-800 p-2 rounded-full shadow-2xl border border-slate-100 dark:border-slate-700 gap-2 animate-in fade-in slide-in-from-bottom-2 duration-200 z-50">
                <button onclick="sendReaction(<?php echo $post['id']; ?>, 'like')" class="hover:scale-125 transition-transform text-2xl" title="Beğen">👍</button>
                <button onclick="sendReaction(<?php echo $post['id']; ?>, 'love')" class="hover:scale-125 transition-transform text-2xl" title="Bayıldım">❤️</button>
                <button onclick="sendReaction(<?php echo $post['id']; ?>, 'haha')" class="hover:scale-125 transition-transform text-2xl" title="Komik">😂</button>
                <button onclick="sendReaction(<?php echo $post['id']; ?>, 'wow')" class="hover:scale-125 transition-transform text-2xl" title="Şaşırdım">😮</button>
                <button onclick="sendReaction(<?php echo $post['id']; ?>, 'sad')" class="hover:scale-125 transition-transform text-2xl" title="Üzüldüm">😢</button>
                <button onclick="sendReaction(<?php echo $post['id']; ?>, 'angry')" class="hover:scale-125 transition-transform text-2xl" title="Kızgın">😡</button>
            </div>
        </div>

        <button onclick="event.stopPropagation(); toggleComments(<?php echo $post['id']; ?>)" class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg py-2 px-3 transition-colors text-slate-500 dark:text-slate-400" id="comment-btn-<?php echo $post['id']; ?>">
            <i class="far fa-comment text-xl"></i>
            <span class="font-bold text-sm"><?php echo $lang == 'en' ? 'Comment' : 'Yorum Yap'; ?></span>
        </button>
        <button class="flex items-center gap-2 hover:text-green-500 transition-colors ml-auto text-sm" onclick="openShareModal(<?php echo $post['id']; ?>)">
            <i class="fas fa-share"></i>
        </button>
        <button onclick="toggleTranslation(<?php echo $post['id']; ?>)" 
                class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-pink-500 transition-all opacity-80 hover:opacity-100 mr-2"
                id="trans-btn-<?php echo $post['id']; ?>">
            <i class="fas fa-language text-lg"></i>
            <span id="trans-text-<?php echo $post['id']; ?>">
                <?php echo $lang == 'en' ? 'See Translation' : 'Çeviriyi Gör'; ?>
            </span>
        </button>
    </div>


    <!-- Comments Section (Lazy Loaded) -->
    <div id="comments-section-<?php echo $post['id']; ?>" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
        
        <?php if($post['comment_count'] > 2): ?>
        <button onclick="event.stopPropagation(); toggleComments(<?php echo $post['id']; ?>)" class="text-slate-500 dark:text-slate-400 text-sm font-semibold hover:underline mb-3 ml-2">
             <?php echo isset($_SESSION['language']) && $_SESSION['language'] == 'en' ? 'View all ' . $post['comment_count'] . ' comments' : 'Tüm ' . $post['comment_count'] . ' yorumu gör'; ?>
        </button>
        <?php endif; ?>

        <!-- Preview Comments (Last 2) -->
        <?php 
        // Fetch last 2 comments
        $preview_stmt = $pdo->prepare("SELECT c.*, u.username, u.full_name, u.avatar FROM post_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC LIMIT 2");
        $preview_stmt->execute([$post['id']]);
        $preview_comments = $preview_stmt->fetchAll();
        
        foreach($preview_comments as $comment): 
        ?>
        <div class="flex gap-2 mb-2 last:mb-0">
            <a href="profile.php?uid=<?php echo $comment['user_id']; ?>" class="shrink-0">
                <img src="<?php echo !empty($comment['avatar']) ? $comment['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($comment['full_name']).'&background=random'; ?>" class="w-8 h-8 rounded-full object-cover">
            </a>
            <div class="flex flex-col items-start max-w-[90%]">
                 <div class="bg-slate-100 dark:bg-slate-700/50 rounded-2xl px-3 py-2 relative min-w-[120px]">
                     <div class="flex flex-col gap-0">
                         <a href="profile.php?uid=<?php echo $comment['user_id']; ?>" class="font-bold text-xs text-slate-900 dark:text-white hover:underline truncate leading-snug">
                             <?php echo htmlspecialchars($comment['full_name']); ?>
                         </a>
                         <div class="text-sm text-slate-800 dark:text-slate-200 break-words leading-tight">
                             <?php echo nl2br(htmlspecialchars(trim($comment['content']))); ?>
                         </div>
                     </div>
                 </div>
                 <div class="flex items-center gap-3 ml-2 mt-1">
                     <span class="text-[10px] text-slate-400 font-medium"><?php echo date('d.m H:i', strtotime($comment['created_at'])); ?></span>
                     <button class="text-[10px] font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition-colors">
                         <?php echo isset($_SESSION['language']) && $_SESSION['language'] == 'en' ? 'Like' : 'Beğen'; ?>
                     </button>
                     <button class="text-[10px] font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition-colors">
                         <?php echo isset($_SESSION['language']) && $_SESSION['language'] == 'en' ? 'Reply' : 'Yanıtla'; ?>
                     </button>
                 </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Hidden full loader for "View all" -->
        <div id="comments-loader-<?php echo $post['id']; ?>" class="hidden text-center text-slate-400 text-sm py-2">
            <i class="fas fa-spinner fa-spin mr-2"></i> Yükleniyor...
        </div>
    </div>
</div>
<?php endforeach; ?>
