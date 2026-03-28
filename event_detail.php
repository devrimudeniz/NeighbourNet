<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT e.*, u.venue_name, 
    (SELECT COUNT(*) FROM likes WHERE event_id = e.id) as like_count
    FROM events e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) die("Etkinlik bulunamadı.");

// Check if user liked
$is_liked = false;
$user_rsvp = null; // going, interested, not_going

if (isset($_SESSION['user_id'])) {
    $l_stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND event_id = ?");
    $l_stmt->execute([$_SESSION['user_id'], $id]);
    if ($l_stmt->fetch()) $is_liked = true;

    // Check RSVP
    $r_stmt = $pdo->prepare("SELECT status FROM event_attendees WHERE user_id = ? AND event_id = ?");
    $r_stmt->execute([$_SESSION['user_id'], $id]);
    $rsvp = $r_stmt->fetch();
    if ($rsvp) $user_rsvp = $rsvp['status'];
}

// Get Attendees (Separated)
$going_stmt = $pdo->prepare("SELECT ea.*, u.username, u.full_name, u.avatar 
                            FROM event_attendees ea 
                            JOIN users u ON ea.user_id = u.id 
                            WHERE ea.event_id = ? AND ea.status = 'going' 
                            ORDER BY ea.created_at DESC LIMIT 10");
$going_stmt->execute([$id]);
$going_attendees = $going_stmt->fetchAll();

$interested_stmt = $pdo->prepare("SELECT ea.*, u.username, u.full_name, u.avatar 
                            FROM event_attendees ea 
                            JOIN users u ON ea.user_id = u.id 
                            WHERE ea.event_id = ? AND ea.status = 'interested' 
                            ORDER BY ea.created_at DESC LIMIT 10");
$interested_stmt->execute([$id]);
$interested_attendees = $interested_stmt->fetchAll();

// Get Comments with Replies
$c_stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.event_id = ? AND c.parent_id IS NULL ORDER BY c.created_at DESC");
$c_stmt->execute([$id]);
$comments = $c_stmt->fetchAll();

// Prepare a way to fetch replies easily or fetch all and group
$r_stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.event_id = ? AND c.parent_id IS NOT NULL ORDER BY c.created_at ASC");
$r_stmt->execute([$id]);
$all_replies = $r_stmt->fetchAll();
$replies_by_parent = [];
foreach($all_replies as $r) {
    $replies_by_parent[$r['parent_id']][] = $r;
}

// Get Likers
$likers_stmt = $pdo->prepare("SELECT u.id, u.username, u.avatar, u.full_name 
                            FROM likes l 
                            JOIN users u ON l.user_id = u.id 
                            WHERE l.event_id = ? 
                            ORDER BY l.created_at DESC LIMIT 5");
$likers_stmt->execute([$id]);
$likers = $likers_stmt->fetchAll();

// Check Expiration
$is_expired = false;
$current_dt = new DateTime();
$event_end_dt = null;

if (!empty($event['end_date'])) {
    $end_time_str = !empty($event['end_time']) ? $event['end_time'] : '23:59:59';
    $event_end_dt = new DateTime($event['end_date'] . ' ' . $end_time_str);
} else {
    // If no end date explicit, assume end of event day
    $event_end_dt = new DateTime($event['event_date'] . ' 23:59:59');
}

if ($current_dt > $event_end_dt) {
    $is_expired = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32 md:pb-24 transition-colors">

    <?php include 'includes/header.php'; ?>

    <!-- Header Image -->
    <div class="relative h-[25rem] lg:h-[32rem] w-full">
        <?php if($event['image_url']): ?>
            <img src="<?php echo htmlspecialchars($event['image_url']); ?>" class="w-full h-full object-cover" loading="lazy">
        <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br from-violet-600 to-pink-500"></div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-50 via-slate-50/20 to-transparent dark:from-slate-900 dark:via-slate-900/40"></div>
        
        <!-- Back Button -->
        <button onclick="history.back()" class="absolute top-24 left-6 w-10 h-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/40 transition-all z-20">
            <i class="fas fa-arrow-left"></i>
        </button>

        <?php if((isset($_SESSION['user_id']) && $_SESSION['user_id'] == $event['user_id']) || (isset($_SESSION['badge']) && in_array($_SESSION['badge'], ['founder', 'moderator']))): ?>
            <a href="edit_event?id=<?php echo $event['id']; ?>" class="absolute top-24 right-6 px-4 py-2 rounded-full bg-white/20 backdrop-blur-md flex items-center gap-2 text-white hover:bg-white/40 transition-all z-20 text-sm font-bold">
                <i class="fas fa-edit"></i> Edit Event
            </a>
        <?php endif; ?>
    </div>

    <div class="max-w-4xl mx-auto px-6 -mt-32 relative z-10">
        <!-- Main Info Card -->
        <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-2xl shadow-pink-500/10 border border-white/20 dark:border-slate-700/50 backdrop-blur-xl relative overflow-hidden">
            <?php if($is_expired): ?>
            <div class="absolute top-0 left-0 w-full bg-red-500 text-white text-center text-sm font-bold py-2 shadow-md z-20">
                <i class="fas fa-exclamation-circle mr-2"></i> Bu etkinlik sona ermiştir
            </div>
            <?php endif; ?>
            <div class="flex flex-col md:flex-row justify-between items-start gap-6 <?php echo $is_expired ? 'mt-6' : ''; ?>">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border border-pink-200/50 dark:border-pink-500/20">
                            <?php echo $event['category']; ?>
                        </span>
                        <?php if ($event['attendee_limit'] > 0): ?>
                            <?php $spots_left = max(0, $event['attendee_limit'] - $event['attendee_count']); ?>
                            <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest <?php echo $spots_left < 5 ? 'text-red-500' : 'text-green-500'; ?>">
                                <i class="fas fa-users text-xs"></i>
                                <?php echo $spots_left; ?> <?php echo $lang == 'en' ? 'Spots Left' : 'Kontenjan Kalan'; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white leading-[1.1] mb-3 tracking-tight">
                        <?php echo htmlspecialchars($event['title']); ?>
                    </h1>
                    
                    <div class="flex flex-wrap gap-4 text-sm font-medium">
                        <div class="flex items-center gap-2 text-pink-500 bg-pink-50 dark:bg-pink-900/20 px-3 py-1.5 rounded-xl">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($event['venue_name']); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-violet-500 bg-violet-50 dark:bg-violet-900/20 px-3 py-1.5 rounded-xl">
                            <i class="far fa-calendar-alt"></i>
                            <span><?php echo date('d.m.Y', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-blue-500 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-xl">
                            <i class="far fa-clock"></i>
                            <span><?php echo date('H:i', strtotime($event['start_time'])); ?></span>
                        </div>
                        <?php if(!empty($event['end_time'])): ?>
                        <div class="flex items-center gap-2 text-slate-500 bg-slate-100 dark:bg-slate-700/50 px-3 py-1.5 rounded-xl">
                            <i class="fas fa-flag-checkered"></i>
                            <span>Bitiş: <?php echo date('H:i', strtotime($event['end_time'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <a href="api/get_event_ics.php?id=<?php echo $id; ?>" class="inline-flex items-center gap-2 text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 hover:bg-violet-100 dark:hover:bg-violet-900/50 px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                            <i class="far fa-calendar-plus"></i>
                            <?php echo $lang == 'en' ? 'Add to Calendar' : 'Takvimime Ekle'; ?>
                        </a>
                        <?php
                        $event_share_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com') . '/event_detail?id=' . $id;
                        ?>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($event_share_url); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-[#1877F2] bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 px-4 py-2 rounded-xl text-sm font-bold transition-colors">
                            <i class="fab fa-facebook-f"></i>
                            <?php echo $lang == 'en' ? 'Share on Facebook' : 'Facebook\'ta Paylaş'; ?>
                        </a>
                    </div>

                    <!-- Likers Avatars (Instagram Style) -->
                    <?php if (!empty($likers)): ?>
                    <div class="mt-6 flex items-center gap-3">
                        <div class="flex -space-x-2 overflow-hidden">
                            <?php foreach ($likers as $liker): ?>
                            <a href="profile?uid=<?php echo $liker['id']; ?>" class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-slate-800 bg-slate-200 dark:bg-slate-700 transition-transform hover:scale-110 hover:z-20">
                                <img src="<?php echo $liker['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($liker['username']); ?>" class="h-full w-full object-cover rounded-full" title="<?php echo htmlspecialchars($liker['full_name'] ?: $liker['username']); ?>" loading="lazy">
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <?php 
                            $first_liker = $likers[0]['full_name'] ?: $likers[0]['username'];
                            if (count($likers) == 1) {
                                echo htmlspecialchars($first_liker) . " " . ($lang == 'en' ? 'liked this' : 'beğendi');
                            } else {
                                $others_count = $event['like_count'] - 1;
                                if ($others_count > 0) {
                                    echo htmlspecialchars($first_liker) . " " . ($lang == 'en' ? 'and' : 've') . " " . $others_count . " " . ($lang == 'en' ? 'others liked' : 'kişi daha beğendi');
                                } else {
                                     echo htmlspecialchars($first_liker) . " " . ($lang == 'en' ? 'liked this' : 'beğendi');
                                }
                            }
                            ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RSVP Redesign (Separated Going/Interested) -->
            <div class="mt-10 pt-8 border-t border-slate-100 dark:border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                    <div class="space-y-6">
                        <!-- Going -->
                        <div>
                            <h3 class="font-bold text-xs text-green-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <i class="fas fa-check-circle"></i> <?php echo $t['going']; ?> 
                                <span class="text-slate-400 font-medium">(<?php echo count($going_attendees); ?>)</span>
                            </h3>
                            <div class="flex items-center gap-3">
                                <div class="flex -space-x-3 overflow-hidden">
                                    <?php foreach($going_attendees as $att): ?>
                                    <a href="profile?uid=<?php echo $att['user_id']; ?>" class="inline-block h-12 w-12 rounded-full ring-4 ring-white dark:ring-slate-800 bg-slate-200 dark:bg-slate-700 transition-transform hover:scale-110 hover:z-20">
                                        <img src="<?php echo $att['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($att['username']); ?>" class="h-full w-full object-cover rounded-full" title="<?php echo htmlspecialchars($att['full_name']); ?>" loading="lazy">
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Interested -->
                        <div>
                            <h3 class="font-bold text-xs text-yellow-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <i class="fas fa-star"></i> <?php echo $t['interested']; ?>
                                <span class="text-slate-400 font-medium">(<?php echo count($interested_attendees); ?>)</span>
                            </h3>
                            <div class="flex items-center gap-3">
                                <div class="flex -space-x-3 overflow-hidden">
                                    <?php foreach($interested_attendees as $att): ?>
                                    <a href="profile?uid=<?php echo $att['user_id']; ?>" class="inline-block h-10 w-10 rounded-full ring-4 ring-white dark:ring-slate-800 bg-slate-200 dark:bg-slate-700 transition-transform hover:scale-110 hover:z-20">
                                        <img src="<?php echo $att['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($att['username']); ?>" class="h-full w-full object-cover rounded-full" title="<?php echo htmlspecialchars($att['full_name']); ?>" loading="lazy">
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="flex p-1 bg-slate-100 dark:bg-slate-900 rounded-2xl w-full relative">
                                <?php if($is_expired): ?>
                                <div class="absolute inset-0 bg-slate-50/80 dark:bg-slate-900/80 z-10 flex items-center justify-center rounded-2xl backdrop-blur-[1px]">
                                    <span class="text-red-500 font-bold text-sm bg-white dark:bg-slate-800 px-4 py-2 rounded-full shadow-lg border border-red-100 dark:border-red-900/30">
                                        <i class="fas fa-lock mr-2"></i>Katılım Kapandı
                                    </span>
                                </div>
                                <?php endif; ?>
                                <button onclick="setRSVP('going')" id="btn-going" 
                                        class="flex-1 px-6 py-4 rounded-xl font-extrabold text-sm transition-all duration-300 flex items-center justify-center gap-2 <?php echo $user_rsvp == 'going' ? 'bg-white dark:bg-slate-800 text-green-500 shadow-xl' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'; ?>" <?php echo $is_expired ? 'disabled' : ''; ?>>
                                    <i class="fas <?php echo $user_rsvp == 'going' ? 'fa-check-circle' : 'fa-circle-notch'; ?>"></i> 
                                    <span><?php echo $t['going']; ?></span>
                                </button>
                                <button onclick="setRSVP('interested')" id="btn-interested" 
                                        class="flex-1 px-6 py-4 rounded-xl font-extrabold text-sm transition-all duration-300 flex items-center justify-center gap-2 <?php echo $user_rsvp == 'interested' ? 'bg-white dark:bg-slate-800 text-yellow-500 shadow-xl' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'; ?>" <?php echo $is_expired ? 'disabled' : ''; ?>>
                                    <i class="fas <?php echo $user_rsvp == 'interested' ? 'fa-star' : 'far fa-star'; ?>"></i> 
                                    <span><?php echo $t['interested']; ?></span>
                                </button>
                            </div>
                        <?php else: ?>
                            <a href="login" class="block text-center px-10 py-5 rounded-2xl bg-gradient-to-r from-pink-500 to-violet-600 text-white font-extrabold text-base shadow-lg shadow-pink-500/25 hover:scale-105 transition-all">
                                <?php echo $t['join_event']; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mt-10">
            <!-- Content Left -->
            <div class="lg:col-span-2 space-y-10">
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-3">
                        <i class="fas fa-info-circle text-pink-500"></i>
                        <?php echo $t['details']; ?>
                    </h3>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-lg">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </p>
                    
                    <!-- Interaction Buttons Inline -->
                    <div class="flex items-center gap-4 mt-8 pt-8 border-t border-slate-100 dark:border-slate-700">
                        <button onclick="toggleLike(<?php echo $event['id']; ?>)" id="like-btn" class="flex-1 h-16 rounded-2xl font-black text-sm transition-all flex items-center justify-center gap-3 <?php echo $is_liked ? 'bg-pink-100 dark:bg-pink-900/30 text-pink-600 border border-pink-200 dark:border-pink-500/30' : 'bg-slate-50 dark:bg-slate-900 text-slate-400 border border-slate-100 dark:border-slate-800 hover:bg-pink-50 dark:hover:bg-pink-900/10 hover:text-pink-500'; ?> group">
                            <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart text-xl group-hover:scale-125 transition-transform"></i> 
                            <span id="like-count" class="text-lg"><?php echo $event['like_count']; ?></span>
                        </button>
                        <button onclick="document.getElementById('comment-input').focus()" class="flex-1 h-16 bg-slate-50 dark:bg-slate-900 rounded-2xl font-black text-sm text-slate-400 border border-slate-100 dark:border-slate-800 hover:bg-violet-50 dark:hover:bg-violet-900/10 hover:text-violet-500 transition-all flex items-center justify-center gap-3">
                            <i class="far fa-comment-dots text-xl"></i>
                            <span class="text-lg"><?php echo count($comments); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                    <h3 class="text-xl font-bold mb-8 flex items-center gap-3">
                        <i class="far fa-comments text-violet-500"></i>
                        <?php echo $t['comments']; ?>
                    </h3>
                    
                    <div id="comments-list" class="space-y-8">
                        <?php if(empty($comments)): ?>
                            <div class="text-center py-10 opacity-30">
                                <i class="fas fa-comment-slash text-4xl mb-3"></i>
                                <p><?php echo $t['no_comments_yet']; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($comments as $comment): ?>
                        <div class="space-y-4" id="comment-group-<?php echo $comment['id']; ?>">
                            <!-- Main Comment -->
                            <div class="flex gap-4 group">
                                <img src="<?php echo $comment['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($comment['username']); ?>" class="w-12 h-12 rounded-2xl object-cover shadow-lg" loading="lazy">
                                <div class="flex-1">
                                    <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-3xl rounded-tl-none border border-slate-100/50 dark:border-slate-700/30 group-hover:border-violet-500/30 transition-colors">
                                        <div class="flex justify-between items-center mb-1">
                                            <h5 class="font-bold text-sm text-slate-800 dark:text-slate-200">@<?php echo htmlspecialchars($comment['username']); ?></h5>
                                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider"><?php echo date('d M, H:i', strtotime($comment['created_at'])); ?></span>
                                        </div>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?php echo htmlspecialchars($comment['content']); ?></p>
                                    </div>
                                    
                                    <!-- Reply Button for Venue Owner -->
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $event['user_id']): ?>
                                    <button onclick="prepareReply(<?php echo $comment['id']; ?>, '<?php echo $comment['username']; ?>')" class="mt-2 text-xs font-bold text-violet-500 hover:text-violet-600 ml-4 flex items-center gap-1">
                                        <i class="fas fa-reply"></i> <?php echo $t['reply']; ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Replies -->
                            <?php if(isset($replies_by_parent[$comment['id']])): ?>
                                <div class="ml-12 space-y-4 border-l-2 border-slate-100 dark:border-slate-800 pl-4">
                                    <?php foreach($replies_by_parent[$comment['id']] as $reply): ?>
                                    <div class="flex gap-3">
                                        <img src="<?php echo $reply['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($reply['username']); ?>" class="w-8 h-8 rounded-xl object-cover shadow-md" loading="lazy">
                                        <div class="flex-1">
                                            <div class="bg-violet-50 dark:bg-violet-900/20 p-3 rounded-2xl rounded-tl-none border border-violet-100 dark:border-violet-500/10">
                                                <div class="flex justify-between items-center mb-1">
                                                    <h5 class="font-bold text-[11px] text-violet-600 dark:text-violet-400 flex items-center gap-1">
                                                        @<?php echo htmlspecialchars($reply['username']); ?>
                                                        <?php if($reply['user_id'] == $event['user_id']) echo '<span class="bg-violet-600 text-white text-[8px] px-1.5 py-0.5 rounded ml-1 uppercase">' . $t['venue'] . '</span>'; ?>
                                                    </h5>
                                                    <span class="text-[8px] uppercase font-bold text-slate-400 tracking-wider"><?php echo date('d M, H:i', strtotime($reply['created_at'])); ?></span>
                                                </div>
                                                <p class="text-xs text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($reply['content']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Mobile-only comment form (in flow, always visible when scrolling to comments) -->
                    <div class="lg:hidden mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3"><?php echo isset($t['add_comment']) ? $t['add_comment'] : ($lang == 'en' ? 'Write a comment' : 'Yorum yaz'); ?></p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <div id="reply-indicator-mobile" class="hidden mb-2 py-2 px-3 flex items-center justify-between bg-violet-50 dark:bg-violet-900/20 rounded-xl border border-violet-100 dark:border-violet-500/20">
                            <span class="text-xs font-bold text-violet-600 dark:text-violet-400 truncate"><i class="fas fa-reply mr-1"></i> <span id="reply-to-user-mobile"></span> <?php echo $t['replying_to']; ?></span>
                            <button type="button" onclick="cancelReply()" class="text-violet-400 hover:text-violet-600 p-1"><i class="fas fa-times-circle"></i></button>
                        </div>
                        <div class="flex gap-2">
                            <input type="hidden" id="reply-parent-id-mobile" value="">
                            <input type="text" id="comment-input-mobile" class="flex-1 min-w-0 h-12 bg-slate-100 dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-600 focus:border-violet-500 rounded-xl px-4 text-sm font-medium focus:outline-none dark:text-white dark:placeholder-slate-400" placeholder="<?php echo $t['share_thoughts']; ?>">
                            <button type="button" onclick="postCommentFromMobile(<?php echo $event['id']; ?>)" class="h-12 px-4 shrink-0 bg-violet-600 rounded-xl text-white font-bold hover:bg-violet-700 active:scale-95 flex items-center justify-center">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <a href="login" class="flex items-center justify-center gap-2 h-12 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 rounded-xl font-bold text-sm">
                            <i class="fas fa-sign-in-alt"></i> <?php echo $t['login_to_comment']; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Content Right (Sidebar/Map) -->
            <div class="space-y-10">
                 <!-- Venue Card -->
                 <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800 text-center overflow-hidden relative group">
                    <div class="absolute inset-0 bg-gradient-to-br from-pink-500/5 to-violet-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto rounded-[2rem] bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-500 text-3xl mb-4 shadow-xl">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4 class="font-black text-xl mb-1 text-slate-800 dark:text-white"><?php echo htmlspecialchars($event['venue_name']); ?></h4>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6"><?php echo $t['owner']; ?></p>
                        
                        <a href="profile?uid=<?php echo $event['user_id']; ?>" class="block w-full py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 font-bold text-slate-600 dark:text-slate-300 hover:bg-violet-600 hover:text-white transition-all">
                            <?php echo $t['view_profile']; ?>
                        </a>
                    </div>
                 </div>

                 <!-- Small Map -->
                 <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-2 shadow-sm border border-slate-100 dark:border-slate-800 h-64 overflow-hidden relative">
                    <iframe width="100%" height="100%" frameborder="0" style="border:0; border-radius: 1.5rem;"
                        src="https://maps.google.com/maps?q=<?php echo urlencode($event['venue_name'] . ' Kalkan'); ?>&output=embed">
                    </iframe>
                 </div>
            </div>
        </div>
    </div>

    <!-- Fixed Comment Input (desktop only; mobile uses in-flow form above) -->
    <div class="hidden lg:block fixed left-0 w-full bottom-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-100 dark:border-slate-800 z-40">
        <!-- Reply Indicator -->
        <div id="reply-indicator" class="hidden max-w-4xl mx-auto px-4 lg:px-6 py-2 flex items-center justify-between bg-violet-50 dark:bg-violet-900/20 border-b border-violet-100 dark:border-violet-500/10">
            <span class="text-xs font-bold text-violet-600 dark:text-violet-400 truncate">
                <i class="fas fa-reply mr-1"></i> <span id="reply-to-user"></span> <?php echo $t['replying_to']; ?>
            </span>
            <button type="button" onclick="cancelReply()" class="text-violet-400 hover:text-violet-600 shrink-0 p-1">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        
        <div class="max-w-4xl mx-auto p-3 lg:p-4 flex gap-3 pb-[env(safe-area-inset-bottom)] lg:pb-0">
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="relative flex-1 min-w-0">
                    <input type="hidden" id="reply-parent-id" value="">
                    <input type="text" id="comment-input" class="w-full h-12 lg:h-14 bg-slate-100 dark:bg-slate-800 border-2 border-transparent focus:border-violet-500 focus:bg-white dark:focus:bg-slate-900 rounded-2xl pl-4 pr-24 lg:pr-24 py-3 text-sm font-medium transition-all focus:outline-none dark:text-white shadow-inner" placeholder="<?php echo $t['share_thoughts']; ?>">
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 lg:right-3 lg:top-2 lg:bottom-2 lg:translate-y-0">
                        <button type="button" onclick="postComment(<?php echo $event['id']; ?>)" class="h-9 lg:h-full px-4 lg:px-6 bg-violet-600 rounded-xl text-white font-bold shadow-lg shadow-violet-500/30 hover:bg-violet-700 active:scale-95 transition-all flex items-center justify-center">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <a href="login" class="w-full flex items-center justify-center gap-3 h-12 lg:h-14 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 rounded-2xl font-bold text-sm hover:bg-slate-200 transition-all border border-slate-200 dark:border-slate-700">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $t['login_to_comment']; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentParentId = null;

        function prepareReply(parentId, username) {
            currentParentId = parentId;
            var replyId = parentId, userLabel = '@' + username;
            var replyEl = document.getElementById('reply-parent-id');
            var replyElM = document.getElementById('reply-parent-id-mobile');
            if (replyEl) replyEl.value = replyId;
            if (replyElM) replyElM.value = replyId;
            var replyTo = document.getElementById('reply-to-user');
            if (replyTo) replyTo.innerText = userLabel;
            var replyToM = document.getElementById('reply-to-user-mobile');
            if (replyToM) replyToM.innerText = userLabel;
            document.getElementById('reply-indicator').classList.remove('hidden');
            var indM = document.getElementById('reply-indicator-mobile');
            if (indM) indM.classList.remove('hidden');
            var input = document.getElementById('comment-input');
            var inputM = document.getElementById('comment-input-mobile');
            var ph = '<?php echo $t['type_your_reply']; ?>';
            if (input) { input.focus(); input.placeholder = ph; }
            if (inputM) { inputM.focus(); inputM.placeholder = ph; }
        }

        function cancelReply() {
            currentParentId = null;
            var replyEl = document.getElementById('reply-parent-id');
            var replyElM = document.getElementById('reply-parent-id-mobile');
            if (replyEl) replyEl.value = '';
            if (replyElM) replyElM.value = '';
            document.getElementById('reply-indicator').classList.add('hidden');
            var indM = document.getElementById('reply-indicator-mobile');
            if (indM) indM.classList.add('hidden');
            var ph = '<?php echo $t['share_thoughts']; ?>';
            var input = document.getElementById('comment-input');
            var inputM = document.getElementById('comment-input-mobile');
            if (input) input.placeholder = ph;
            if (inputM) inputM.placeholder = ph;
        }

        function toggleLike(id) {
            const btn = document.getElementById('like-btn');
            const icon = btn.querySelector('i');
            const count = document.getElementById('like-count');
            
            // UI Feedback
            btn.classList.add('scale-90');
            setTimeout(() => btn.classList.remove('scale-90'), 100);

            fetch('api/like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'event_id=' + id
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'liked') {
                        btn.classList.remove('bg-slate-50', 'dark:bg-slate-900', 'text-slate-400', 'border-slate-100');
                        btn.classList.add('bg-pink-100', 'dark:bg-pink-900/30', 'text-pink-600', 'border-pink-200');
                        icon.classList.replace('far', 'fas');
                    } else {
                        btn.classList.remove('bg-pink-100', 'dark:bg-pink-900/30', 'text-pink-600', 'border-pink-200');
                        btn.classList.add('bg-slate-50', 'dark:bg-slate-900', 'text-slate-400', 'border-slate-100');
                        icon.classList.replace('fas', 'far');
                    }
                    count.innerText = data.count;
                } else {
                    if(data.message === 'Lütfen giriş yapın') window.location.href = 'login';
                }
            });
        }

        function setRSVP(status) {
            const goingBtn = document.getElementById('btn-going');
            const interestedBtn = document.getElementById('btn-interested');
            
            // Visual feedback before reload
            if(status === 'going') {
                goingBtn.classList.add('bg-white', 'text-green-500', 'shadow-xl');
                interestedBtn.classList.remove('bg-white', 'text-yellow-500', 'shadow-xl');
            } else {
                interestedBtn.classList.add('bg-white', 'text-yellow-500', 'shadow-xl');
                goingBtn.classList.remove('bg-white', 'text-green-500', 'shadow-xl');
            }

            fetch('api/event_rsvp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=rsvp&event_id=<?php echo $event['id']; ?>&status=' + status
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

        function postComment(id) {
            var input = document.getElementById('comment-input');
            if (!input) return;
            var btn = event.currentTarget;
            var content = input.value.trim();
            var parentId = (document.getElementById('reply-parent-id') || {}).value || '';
            if (!content || btn.disabled) return;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendCommentRequest(id, content, parentId, btn, '<i class="fas fa-paper-plane"></i>');
        }

        function postCommentFromMobile(id) {
            var input = document.getElementById('comment-input-mobile');
            if (!input) return;
            var btn = event.currentTarget;
            var content = input.value.trim();
            var parentId = (document.getElementById('reply-parent-id-mobile') || {}).value || '';
            if (!content || btn.disabled) return;
            btn.disabled = true;
            var origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendCommentRequest(id, content, parentId, btn, origHtml);
        }

        function sendCommentRequest(eventId, content, parentId, btn, restoreHtml) {
            fetch('api/comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'event_id=' + eventId + '&content=' + encodeURIComponent(content) + '&parent_id=' + (parentId || '')
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message || '<?php echo $t['error']; ?>');
                    if (btn) { btn.disabled = false; btn.innerHTML = restoreHtml; }
                }
            })
            .catch(function(err) {
                console.error(err);
                if (btn) { btn.disabled = false; btn.innerHTML = restoreHtml; }
            });
        }
    </script>
</body>
</html>
