<?php
/**
 * Modern Profile Header Component
 * Based on reference UI design with centered avatar, stats row, and action buttons
 */

// Calculate stats
$friends_count = count($friends_list);
$posts_count = count($posts);

// Get user status
$avatar_color = 'default';
if (in_array($profile_user['badge'], ['founder', 'moderator'])) $avatar_color = 'primary';
elseif ($profile_user['badge'] == 'verified_business') $avatar_color = 'success';
elseif ($profile_user['badge'] == 'taxi') $avatar_color = 'warning';

$ring_class = match($avatar_color) {
    'primary' => 'ring-pink-500',
    'success' => 'ring-green-500', 
    'warning' => 'ring-yellow-500',
    default => 'ring-white dark:ring-slate-800'
};

$avatar_position = $profile_user['avatar_position'] ?? '50 50';
$avatar_pos_parts = explode(' ', $avatar_position);
$avatar_pos_x = $avatar_pos_parts[0] ?? 50;
$avatar_pos_y = $avatar_pos_parts[1] ?? 50;

$user_status = getUserStatus($profile_user['last_seen'] ?? null);
$is_online = ($user_status === 'online');

$cover_position = $profile_user['cover_position'] ?? '50 50';
$pos_parts = explode(' ', $cover_position);
$pos_x = $pos_parts[0] ?? 50;
$pos_y = $pos_parts[1] ?? 50;
$has_cover = !empty($profile_user['cover_photo']) && trim($profile_user['cover_photo']) !== '';

// Badge icon mapping
$badge_icons = [
    'founder' => '👑',
    'moderator' => '🛡️',
    'verified_business' => '✓',
    'business' => '💼',
    'captain' => '⚓',
    'taxi' => '🚕'
];
?>

<!-- Modern Profile Header -->
<div class="relative">
    <!-- Cover Photo with Gradient Overlay -->
    <div class="relative w-full h-56 sm:h-64 md:h-72 overflow-hidden" id="cover-container">
        <?php 
        $seed = 'user_' . ($profile_user['id'] ?? 'guest');
        $random_cover = "https://picsum.photos/seed/{$seed}/1200/400";
        ?>
        <?php if($has_cover): ?>
            <img src="<?php echo htmlspecialchars(media_url($profile_user['cover_photo'])); ?>" 
                 class="w-full h-full object-cover" 
                 id="cover-image-display"
                 style="object-position: <?php echo $pos_x; ?>% <?php echo $pos_y; ?>%"
                 loading="eager"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <img src="<?php echo $random_cover; ?>" class="w-full h-full object-cover" style="display:none;" alt="Default Cover">
        <?php else: ?>
            <img src="<?php echo $random_cover; ?>" class="w-full h-full object-cover" alt="Default Cover">
        <?php endif; ?>
        
        <!-- Dark Gradient Overlay for text visibility -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
        
        <!-- Top Actions (Share & Settings) -->
        <div class="absolute top-4 right-4 flex gap-2 z-20">
            <button onclick="shareProfile()" class="w-10 h-10 rounded-full bg-black/30 backdrop-blur-md text-white flex items-center justify-center border border-white/20 hover:bg-black/50 transition-all">
                <i class="fas fa-share-alt"></i>
            </button>
            <?php if ($is_own_profile && $has_cover): ?>
            <button onclick="openPositionModal('cover')" class="w-10 h-10 rounded-full bg-black/30 backdrop-blur-md text-white flex items-center justify-center border border-white/20 hover:bg-black/50 transition-all">
                <i class="fas fa-crop-alt"></i>
            </button>
            <?php endif; ?>
            <?php if ($user_id): ?>
            <button onclick="openSettingsModal()" class="w-10 h-10 rounded-full bg-black/30 backdrop-blur-md text-white flex items-center justify-center border border-white/20 hover:bg-black/50 transition-all">
                <?php echo heroicon('cog_6_tooth', 'w-5 h-5'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Info Card (Overlapping Cover) -->
    <div class="relative px-4 sm:px-6 -mt-20 z-10">
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 p-6 pt-0">
            
            <!-- Centered Avatar -->
            <div class="flex justify-center -mt-16 mb-4">
                <div class="relative group" id="avatar-container">
                    <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full ring-4 <?php echo $ring_class; ?> ring-offset-4 ring-offset-white dark:ring-offset-slate-800 overflow-hidden bg-white dark:bg-slate-700 shadow-2xl">
                        <img src="<?php echo htmlspecialchars(media_url($profile_user['avatar'])); ?>" 
                             alt="Avatar"
                             id="avatar-image"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                             style="object-position: <?php echo $avatar_pos_x; ?>% <?php echo $avatar_pos_y; ?>%"
                             loading="eager">
                    </div>
                    
                    <!-- Online Status -->
                    <span class="absolute bottom-1 right-1 w-6 h-6 rounded-full border-4 border-white dark:border-slate-800 shadow-lg <?php echo $is_online ? 'bg-emerald-500' : 'bg-slate-400'; ?>" 
                          title="<?php echo $is_online ? 'Online' : 'Offline'; ?>">
                        <?php if($is_online): ?>
                        <span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-75"></span>
                        <?php endif; ?>
                    </span>
                    
                    <?php if($is_own_profile): ?>
                    <button onclick="openPositionModal('avatar')" 
                            class="absolute bottom-0 right-0 w-8 h-8 bg-pink-500 text-white rounded-full flex items-center justify-center shadow-lg border-2 border-white dark:border-slate-800 hover:bg-pink-600 transition-colors">
                        <i class="fas fa-camera text-xs"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

                <!-- Name & Username -->
            <div class="text-center mb-4">
                <?php $is_banned = ($profile_user['status'] ?? 'active') === 'banned'; ?>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white flex items-center justify-center gap-2 <?php echo $is_banned ? 'line-through decoration-red-500 decoration-4 text-slate-400' : ''; ?>">
                    <?php echo htmlspecialchars($profile_user['full_name']); ?>
                    <?php if ($is_banned): ?>
                        <span class="inline-block px-3 py-1 text-xs font-black rounded-md bg-black text-white no-underline shadow-lg">
                            🚫 BANNED
                        </span>
                    <?php elseif (isset($badge_icons[$profile_user['badge']])): ?>
                        <span class="text-lg" title="<?php echo $profile_user['badge']; ?>"><?php echo $badge_icons[$profile_user['badge']]; ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium mt-1">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                <?php
                $last_active_text = getLastActiveText($profile_user['last_seen'] ?? null, $t ?? []);
                if ($last_active_text !== ''):
                ?>
                <p class="text-xs text-slate-400 mt-0.5"><?php echo ($t['last_active'] ?? 'Last active'); ?>: <?php echo htmlspecialchars($last_active_text); ?></p>
                <?php endif; ?>
            </div>

            <!-- Stats Row -->
            <div class="flex justify-center gap-8 sm:gap-12 py-4 border-y border-slate-100 dark:border-slate-700 mb-4">
                <div class="text-center">
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"><?php echo number_format($friends_count); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?php echo $t['friends_tab']; ?></p>
                </div>
                <div class="text-center">
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"><?php echo number_format($posts_count); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?php echo $lang == 'en' ? 'Posts' : 'Gönderi'; ?></p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-center gap-3">
                <?php if ($is_own_profile): ?>
                    <!-- Own Profile Actions -->
                    <a href="edit_profile" class="flex-1 max-w-[200px] flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-pink-500 to-violet-600 text-white font-bold text-sm shadow-lg shadow-pink-500/30 hover:shadow-pink-500/50 transition-all active:scale-95">
                        <?php echo heroicon('pencil_square', 'w-5 h-5'); ?>
                        <?php echo $t['edit_profile']; ?>
                    </a>
                    
                    <?php if ($is_business): ?>
                    <a href="business_analytics" class="flex-1 max-w-[200px] flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-slate-700 text-slate-700 dark:text-white font-bold text-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all active:scale-95">
                        <i class="fas fa-chart-line"></i>
                        <?php echo $lang == 'en' ? 'Insights' : 'İstatistikler'; ?>
                    </a>
                    <?php else: ?>
                    <a href="saved" class="flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-slate-700 text-slate-700 dark:text-white font-bold text-sm border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-all active:scale-95">
                        <i class="fas fa-bookmark"></i>
                        <?php echo $lang == 'en' ? 'Saved' : 'Kaydedilenler'; ?>
                    </a>
                    <?php endif; ?>
                    
                <?php elseif ($user_id): ?>
                    <!-- Other User Actions -->
                    <div class="flex gap-3 w-full justify-center" id="friendship-actions">
                        <?php if ($friendship_status == 'friends'): ?>
                            <div class="relative group">
                                <button class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-white font-bold text-sm border border-slate-200 dark:border-slate-600">
                                    <?php echo heroicon('user_check', 'w-5 h-5 text-emerald-500'); ?>
                                    <?php echo $t['friendship_friend']; ?>
                                    <?php echo heroicon('chevron_down', 'w-4 h-4 ml-1 opacity-50'); ?>
                                </button>
                                <div class="hidden group-hover:block absolute left-0 top-full mt-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden z-30">
                                    <button onclick="unfriend(<?php echo $view_user_id; ?>)" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/10 flex items-center gap-3">
                                        <?php echo heroicon('user_minus', 'w-4 h-4'); ?> <?php echo $t['friendship_unfriend']; ?>
                                    </button>
                                </div>
                            </div>
                            
                            <a href="messages?uid=<?php echo $view_user_id; ?>" class="flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all active:scale-95">
                                <?php echo heroicon('chat_bubble_left_right', 'w-5 h-5'); ?>
                                <?php echo $t['messages']; ?>
                            </a>
                            
                        <?php elseif ($friendship_status == 'pending_sent'): ?>
                            <button disabled class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-400 font-bold text-sm flex items-center gap-2 cursor-not-allowed">
                                <?php echo heroicon('clock', 'w-5 h-5'); ?> <?php echo $t['friendship_request_sent']; ?>
                            </button>
                            
                        <?php elseif ($friendship_status == 'pending_received'): ?>
                            <button onclick="respondToRequest(<?php echo $view_user_id; ?>, 'accept')" class="flex-1 max-w-[150px] px-4 py-3 rounded-2xl bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-500/30 active:scale-95">
                                <?php echo $t['friendship_accept']; ?>
                            </button>
                            <button onclick="respondToRequest(<?php echo $view_user_id; ?>, 'decline')" class="flex-1 max-w-[150px] px-4 py-3 rounded-2xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-sm active:scale-95">
                                <?php echo $t['friendship_decline']; ?>
                            </button>
                            
                        <?php else: ?>
                            <button onclick="sendFriendRequest(<?php echo $view_user_id; ?>)" class="flex-1 max-w-[250px] px-6 py-3 rounded-2xl bg-gradient-to-r from-pink-500 to-violet-600 text-white font-bold text-sm shadow-lg shadow-pink-500/30 hover:shadow-pink-500/50 active:scale-95 transition-all flex items-center justify-center gap-2">
                                <?php echo heroicon('user_plus', 'w-5 h-5'); ?>
                                <?php echo $t['friendship_add_friend']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mutual Friends -->
            <?php if ($user_id && !$is_own_profile && $mutual_count > 0): ?>
            <div class="flex items-center justify-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="flex -space-x-2">
                    <?php foreach ($mutual_friends as $mf): ?>
                        <img src="<?php echo $mf['avatar']; ?>" class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800 object-cover" title="<?php echo htmlspecialchars($mf['full_name']); ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
                <span><?php echo $mutual_count; ?> <?php echo $t['mutual_friends']; ?></span>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Profile Tabs Navigation -->
<div class="sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl z-30 border-b border-slate-200 dark:border-slate-700 mt-4">
    <div class="flex justify-center gap-0">
        <button onclick="switchTab('about')" id="tab-about" class="flex-1 max-w-[120px] py-4 flex flex-col items-center gap-1 text-pink-600 dark:text-pink-400 border-b-2 border-pink-500 transition-all">
            <i class="fas fa-user text-lg"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider"><?php echo $lang == 'en' ? 'About' : 'Hakkında'; ?></span>
        </button>
        <button onclick="switchTab('posts')" id="tab-posts" class="flex-1 max-w-[120px] py-4 flex flex-col items-center gap-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 border-b-2 border-transparent transition-all">
            <i class="fas fa-th-large text-lg"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider"><?php echo $t['posts_tab']; ?></span>
        </button>
        <?php if($is_business): ?>
        <button onclick="switchTab('events')" id="tab-events" class="flex-1 max-w-[120px] py-4 flex flex-col items-center gap-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 border-b-2 border-transparent transition-all">
            <i class="fas fa-calendar-alt text-lg"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider"><?php echo $t['events_tab']; ?></span>
        </button>
        <?php endif; ?>
        <button onclick="switchTab('friends')" id="tab-friends" class="flex-1 max-w-[120px] py-4 flex flex-col items-center gap-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 border-b-2 border-transparent transition-all">
            <i class="fas fa-users text-lg"></i>
            <span class="text-[10px] font-bold uppercase tracking-wider"><?php echo $t['friends_tab']; ?></span>
        </button>
    </div>
</div>

<script>
function shareProfile() {
    const url = window.location.href;
    const title = '<?php echo addslashes($profile_user['full_name']); ?> - KalkanSocial';
    
    if (navigator.share) {
        navigator.share({ title: title, url: url });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            alert('<?php echo $lang == 'en' ? 'Profile link copied!' : 'Profil linki kopyalandı!'; ?>');
        });
    }
}
</script>
