<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/friendship-helper.php';

$user_id_param = (int)($_GET['user_id'] ?? 0);
$viewer_id = $_SESSION['user_id'] ?? 0;

if (!$user_id_param) {
    header("Location: index");
    exit();
}

// Get all active stories for this user
$has_visibility = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM stories LIKE 'visibility'");
    $has_visibility = $col->fetch() !== false;
} catch (PDOException $e) {}

$stmt = $pdo->prepare("SELECT s.*, u.username, u.full_name, u.avatar,
                       (SELECT COUNT(*) FROM story_views WHERE story_id = s.id) as view_count
                       FROM stories s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.user_id = ? AND s.expires_at > NOW() 
                       ORDER BY s.created_at ASC");
$stmt->execute([$user_id_param]);
$all_stories = $stmt->fetchAll();

// Filter by visibility: friends_only visible only to owner or friends
$stories = [];
foreach ($all_stories as $story) {
    $visibility = ($has_visibility && isset($story['visibility'])) ? $story['visibility'] : 'everyone';
    if ($visibility === 'friends_only') {
        if ($viewer_id && ($story['user_id'] == $viewer_id || areFriends($viewer_id, $story['user_id']))) {
            $stories[] = $story;
        }
    } else {
        $stories[] = $story;
    }
}

if (empty($stories)) {
    header("Location: index");
    exit();
}

$user_info = [
    'username' => $stories[0]['username'],
    'full_name' => $stories[0]['full_name'],
    'avatar' => $stories[0]['avatar']
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hikaye | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        /* body { font-family: 'Outfit', sans-serif; } - Included in header_css */
        .story-progress {
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            overflow: hidden;
        }
        .story-progress-bar {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.1s linear;
        }
        .story-progress-bar.active {
            animation: progress 5s linear forwards;
        }
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
        /* Facebook-style floating emoji on double-tap */
        .story-emoji-pop {
            position: absolute;
            pointer-events: none;
            font-size: 5rem;
            z-index: 45;
            animation: emoji-pop 0.8s ease-out forwards;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));
        }
        @keyframes emoji-pop {
            0% { transform: translate(-50%, -50%) scale(0.3); opacity: 1; }
            30% { transform: translate(-50%, -50%) scale(1.4); opacity: 1; }
            70% { transform: translate(-50%, -60%) scale(1.2); opacity: 0.9; }
            100% { transform: translate(-50%, -80%) scale(1.5); opacity: 0; }
        }
    </style>
</head>
<body class="bg-black overflow-hidden">
    
    <div class="fixed inset-0 flex items-center justify-center">
        <!-- Story Container -->
        <div class="relative w-full h-full max-w-md mx-auto bg-black">
            
            <!-- Progress Bars -->
            <div class="absolute top-0 left-0 right-0 z-50 flex gap-1 p-2">
                <?php foreach($stories as $index => $story): ?>
                <div class="story-progress flex-1">
                    <div class="story-progress-bar" id="progress-<?php echo $index; ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Header -->
            <div class="absolute top-12 left-0 right-0 z-40 p-4 flex items-center justify-between bg-gradient-to-b from-black/70 to-transparent">
                <div class="flex items-center gap-3">
                    <img src="<?php echo htmlspecialchars($user_info['avatar']); ?>" class="w-10 h-10 rounded-full border-2 border-white">
                    <div>
                        <p class="text-white font-bold text-sm"><?php echo htmlspecialchars($user_info['full_name']); ?></p>
                        <p class="text-white/70 text-xs" id="story-time"></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                     <!-- Delete Button (Added dynamically via JS) -->
                     <button id="delete-btn" class="text-white hover:text-red-500 hidden transition-colors" onclick="deleteStory()">
                        <i class="fas fa-trash"></i>
                     </button>
                    <a href="index" class="text-white text-xl hover:opacity-80 transition-opacity">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>

            <!-- Story Media (double-tap zone for emoji) -->
            <div class="w-full h-full flex items-center justify-center relative" id="story-media">
                <!-- Media will be loaded here -->
                <div id="story-emoji-overlay" class="absolute inset-0 pointer-events-none z-10"></div>
            </div>

            <!-- Caption -->
            <div class="absolute bottom-24 left-0 right-0 z-40 p-6 bg-gradient-to-t from-black/70 to-transparent" id="story-caption" style="display: none;">
                <p class="text-white text-sm"></p>
            </div>

            <!-- Reaction & Reply Bar (Only for logged-in users viewing others' stories) -->
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id_param): ?>
            <div class="absolute bottom-0 left-0 right-0 z-40 p-4 bg-gradient-to-t from-black via-black/90 to-transparent" id="reaction-bar">
                <!-- Emoji Reactions -->
                <div class="flex justify-center gap-2 mb-3" id="emoji-reactions">
                    <button class="reaction-btn text-2xl p-2 rounded-full hover:bg-white/20 transition-all transform hover:scale-125" data-reaction="❤️" title="Beğen">❤️</button>
                    <button class="reaction-btn text-2xl p-2 rounded-full hover:bg-white/20 transition-all transform hover:scale-125" data-reaction="😂" title="Güldüm">😂</button>
                    <button class="reaction-btn text-2xl p-2 rounded-full hover:bg-white/20 transition-all transform hover:scale-125" data-reaction="😮" title="Şaşırdım">😮</button>
                    <button class="reaction-btn text-2xl p-2 rounded-full hover:bg-white/20 transition-all transform hover:scale-125" data-reaction="😢" title="Üzüldüm">😢</button>
                    <button class="reaction-btn text-2xl p-2 rounded-full hover:bg-white/20 transition-all transform hover:scale-125" data-reaction="😡" title="Kızdım">😡</button>
                    <button class="reaction-btn text-2xl p-2 rounded-full hover:bg-white/20 transition-all transform hover:scale-125" data-reaction="🔥" title="Ateş">🔥</button>
                </div>
                
                <!-- Reply Input -->
                <form id="reply-form" class="flex gap-2 items-center">
                    <input type="text" id="reply-input" placeholder="Yanıtla..." 
                           class="flex-1 bg-white/10 border border-white/20 rounded-full px-4 py-2 text-white placeholder-white/50 focus:outline-none focus:border-white/50 text-sm"
                           maxlength="500">
                    <button type="submit" class="bg-green-500 hover:bg-green-400 text-white rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Navigation Areas (double-tap = heart emoji) -->
            <div class="absolute inset-0 flex z-30" id="story-tap-zone">
                <div class="w-1/3 h-full cursor-pointer" data-action="prev" onclick="handleStoryTap(event, 'prev')"></div>
                <div class="w-1/3 h-full cursor-pointer" data-action="pause" onclick="handleStoryTap(event, 'pause')"></div>
                <div class="w-1/3 h-full cursor-pointer" data-action="next" onclick="handleStoryTap(event, 'next')"></div>
            </div>

            <!-- Views Indicator (Final Position beneath Profile) -->
            <!-- Positioned at top-24 (below header). Starts HIDDEN. -->
            <div id="views-indicator" class="absolute top-24 left-6 z-50 hidden" onclick="openViewersModal(event)">
                 <button class="flex items-center gap-2 text-white bg-black/30 backdrop-blur-md px-3 py-1.5 rounded-full hover:bg-black/50 transition-colors border border-white/20">
                     <i class="fas fa-eye text-sm"></i>
                     <span class="text-sm font-bold" id="view-count">...</span>
                 </button>
            </div>

            <!-- Viewers Modal -->
            <div id="viewers-modal" class="absolute inset-0 z-[60] bg-black/95 transform translate-y-full transition-transform duration-300 hidden">
                <div class="h-full flex flex-col">
                    <div class="p-4 border-b border-white/10 flex justify-between items-center">
                        <h3 class="text-white font-bold text-lg">Görüntüleyenler</h3>
                        <button onclick="closeViewersModal()" class="text-white/70 hover:text-white"><i class="fas fa-times text-xl"></i></button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="viewers-list">
                        <!-- List loaded by JS -->
                        <div class="text-center text-white/50 mt-10">Yükleniyor...</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        const stories = <?php echo json_encode($stories); ?>;
        const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
        let currentIndex = 0;
        let isPaused = false;
        let progressInterval = null;

        // Load first story on page load
        loadStory(0);

        // Helper Functions (DEFINED BEFORE USE TO AVOID ERRORS)
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            if (seconds < 60) return 'Az önce';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' dakika önce';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' saat önce';
            return Math.floor(seconds / 86400) + ' gün önce';
        }

        // Viewers System Logic
        function loadStory(index) {
            if (index < 0 || index >= stories.length) {
                window.location.href = 'index';
                return;
            }

            // Clear previous timers
            if (progressInterval) clearTimeout(progressInterval);

            currentIndex = index;
            const story = stories[index];
            
            // Update media
            const mediaContainer = document.getElementById('story-media');
            let duration = 5000; // Default 5s for image

            if (story.media_type === 'image') {
                mediaContainer.innerHTML = `<img src="${story.media_url}" class="max-w-full max-h-full object-contain">`;
                startProgress(duration);
            } else {
                // Video: Autoplay, No Loop
                mediaContainer.innerHTML = `<video id="story-video" src="${story.media_url}" class="max-w-full max-h-full object-contain" autoplay muted playsinline></video>`;
                
                const video = document.getElementById('story-video');
                
                // When metadata loads, update duration and animation
                video.onloadedmetadata = function() {
                    duration = video.duration * 1000;
                    if (!isFinite(duration) || duration === 0) duration = 5000;
                    startProgress(duration);
                };

                // When video ends, go next
                video.onended = function() {
                    if (!isPaused) nextStory();
                };
                
                // Fallback for video error
                video.onerror = function() {
                    startProgress(5000);
                };
            }

            // Update caption
            const captionDiv = document.getElementById('story-caption');
            if (story.caption) {
                captionDiv.style.display = 'block';
                captionDiv.querySelector('p').textContent = story.caption;
            } else {
                captionDiv.style.display = 'none';
            }

            // Update time
            const timeAgo = getTimeAgo(new Date(story.created_at));
            document.getElementById('story-time').textContent = timeAgo;

            // Viewers Indicator Logic
            const eyeBtn = document.getElementById('views-indicator');
            
            // Robust comparison (String to String)
            if (String(story.user_id) === String(currentUserId)) {
                eyeBtn.classList.remove('hidden');
                document.getElementById('delete-btn').classList.remove('hidden'); // Show delete button
                
                // Fetch real-time count
                fetch(`api/get_story_viewers.php?story_id=${story.id}`)
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            document.getElementById('view-count').textContent = data.viewers.length;
                        } else {
                             document.getElementById('view-count').textContent = story.view_count || 0;
                        }
                    })
                    .catch(err => {
                        console.error('Fetch viewer count error:', err);
                        document.getElementById('view-count').textContent = story.view_count || 0;
                    });
            } else {
                eyeBtn.classList.add('hidden');
                document.getElementById('delete-btn').classList.add('hidden');
            }

            // Record view
            <?php if(isset($_SESSION['user_id'])): ?>
            fetch('api/view_story.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `story_id=${story.id}`
            });
            <?php endif; ?>

            // Reset all progress bars
            document.querySelectorAll('.story-progress-bar').forEach(bar => {
                bar.classList.remove('active');
                bar.style.width = '0%';
                bar.style.animation = 'none'; // Reset animation
            });

            // Fill completed bars
            for (let i = 0; i < index; i++) {
                document.getElementById(`progress-${i}`).style.width = '100%';
            }
        }

        async function deleteStory() {
            if(!confirm('Bu hikayeyi silmek istediğinize emin misiniz?')) return;
            
            const story = stories[currentIndex];
            try {
                const res = await fetch('api/delete_story.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `story_id=${story.id}`
                });
                const data = await res.json();
                if(data.success) {
                    location.reload(); // Reload to refresh list
                } else {
                    alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
                }
            } catch(e) {
                alert('Silme işlemi başarısız.');
            }
        }

        function startProgress(duration) {
            // Start current progress
            const currentProgress = document.getElementById(`progress-${currentIndex}`);
            
            // RESET ANIMATION Trick (Force Reflow)
            currentProgress.style.animation = 'none';
            currentProgress.offsetHeight; /* trigger reflow */
            currentProgress.style.animation = null; 
            
            currentProgress.classList.add('active');
            
            // Set dynamic duration
            currentProgress.style.animation = `progress ${duration}ms linear forwards`;

            // If image, auto advance using setTimeout
            const story = stories[currentIndex];
            if (story.media_type === 'image') {
                progressInterval = setTimeout(() => {
                    if (!isPaused) nextStory();
                }, duration);
            }
        }    // Pause story
        async function openViewersModal(event) {
            if(event) event.stopPropagation(); // Prevent navigation click
            
            // Pause story
            isPaused = true;
            const currentProgress = document.getElementById(`progress-${currentIndex}`);
            currentProgress.style.animationPlayState = 'paused';

            const video = document.getElementById('story-video');
            if (video) video.pause();

            // Show modal
            const modal = document.getElementById('viewers-modal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('translate-y-full'), 10); // Slide up transition

            // Load Viewers and Reactions
            const story = stories[currentIndex];
            const listContainer = document.getElementById('viewers-list');
            listContainer.innerHTML = '<div class="text-center text-white/50 mt-10">Yükleniyor...</div>';

            try {
                // Fetch both viewers and reactions in parallel
                const [viewersRes, reactionsRes] = await Promise.all([
                    fetch(`api/get_story_viewers.php?story_id=${story.id}`),
                    fetch(`api/get_story_reactions.php?story_id=${story.id}`)
                ]);
                
                const viewersData = await viewersRes.json();
                const reactionsData = await reactionsRes.json();
                
                // Create reaction map for quick lookup
                const reactionMap = {};
                if (reactionsData.success && reactionsData.reactions) {
                    reactionsData.reactions.forEach(r => {
                        reactionMap[r.user_id] = r.reaction_type;
                    });
                }
                
                let html = '';
                
                // Reaction Summary
                if (reactionsData.success && reactionsData.total_count > 0) {
                    html += `<div class="mb-4 p-3 bg-white/5 rounded-xl">
                        <div class="text-white/70 text-xs mb-2">Tepkiler</div>
                        <div class="flex gap-3 flex-wrap">`;
                    
                    for (const [emoji, count] of Object.entries(reactionsData.reaction_counts)) {
                        html += `<span class="flex items-center gap-1 bg-white/10 px-2 py-1 rounded-full text-sm">
                            <span>${emoji}</span>
                            <span class="text-white font-bold">${count}</span>
                        </span>`;
                    }
                    
                    html += `</div></div>`;
                }
                
                // Viewers list with reactions
                if(viewersData.success && viewersData.viewers.length > 0) {
                    html += `<div class="text-white/70 text-xs mb-2">Görüntüleyenler (${viewersData.viewers.length})</div>`;
                    viewersData.viewers.forEach(v => {
                        const reaction = reactionMap[v.user_id] || '';
                        html += `
                        <div class="flex items-center gap-3 p-2 hover:bg-white/5 rounded-lg transition-colors">
                            <img src="${v.avatar}" class="w-10 h-10 rounded-full bg-slate-700 object-cover">
                            <div class="flex-1">
                                <div class="text-white font-bold text-sm">${v.full_name}</div>
                                <div class="text-white/50 text-xs text-left">@${v.username} • ${formatDate(v.viewed_at)}</div>
                            </div>
                            ${reaction ? `<span class="text-2xl">${reaction}</span>` : ''}
                        </div>`;
                    });
                } else {
                    html += '<div class="text-center text-white/50 mt-10">Henüz kimse görüntülememiş.</div>';
                }
                
                listContainer.innerHTML = html;
            } catch(e) {
                console.error(e);
                listContainer.innerHTML = '<div class="text-center text-red-400 mt-10">Hata oluştu.</div>';
            }
        }

        function closeViewersModal() {
            const modal = document.getElementById('viewers-modal');
            modal.classList.add('translate-y-full');
            setTimeout(() => modal.classList.add('hidden'), 300);
            
            // Resume story
            isPaused = false;
            const currentProgress = document.getElementById(`progress-${currentIndex}`);
            currentProgress.style.animationPlayState = 'running';
            
            // Resume video if present
            const video = document.getElementById('story-video');
            if (video) video.play();
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
        }

        // Facebook-style double-tap for emoji (anywhere on story)
        let lastTapTime = 0;
        let lastTapX = 0;
        let lastTapY = 0;
        const doubleTapDelay = 350;
        const doubleTapDist = 50;

        function handleStoryTap(evt, action) {
            const e = evt || window.event;
            const x = e.clientX || 0;
            const y = e.clientY || 0;
            const now = Date.now();
            const isDoubleTap = (now - lastTapTime < doubleTapDelay) && 
                Math.abs(x - lastTapX) < doubleTapDist && 
                Math.abs(y - lastTapY) < doubleTapDist;
            lastTapTime = now;
            lastTapX = x;
            lastTapY = y;

            if (isDoubleTap) {
                const story = stories[currentIndex];
                if (story && story.user_id != currentUserId) {
                    showFloatingEmoji('❤️', x, y);
                    sendReaction('❤️');
                }
                return;
            }
            if (action === 'prev') previousStory();
            else if (action === 'next') nextStory();
            else if (action === 'pause') togglePause();
        }

        function showFloatingEmoji(emoji, clientX, clientY) {
            const overlay = document.getElementById('story-emoji-overlay');
            if (!overlay) return;
            const rect = overlay.getBoundingClientRect();
            const x = (clientX || rect.width/2) - rect.left;
            const y = (clientY || rect.height/2) - rect.top;
            const el = document.createElement('div');
            el.className = 'story-emoji-pop';
            el.textContent = emoji;
            el.style.left = x + 'px';
            el.style.top = y + 'px';
            overlay.appendChild(el);
            setTimeout(() => el.remove(), 800);
        }

        function nextStory() {
            loadStory(currentIndex + 1);
        }

        function previousStory() {
            loadStory(currentIndex - 1);
        }

        function togglePause() {
            isPaused = !isPaused;
            const currentProgress = document.getElementById(`progress-${currentIndex}`);
            const video = document.getElementById('story-video');
            
            if (isPaused) {
                currentProgress.style.animationPlayState = 'paused';
                if (video) video.pause();
            } else {
                currentProgress.style.animationPlayState = 'running';
                if (video) video.play();
            }
        }

        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            if (seconds < 60) return 'Az önce';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' dakika önce';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' saat önce';
            return Math.floor(seconds / 86400) + ' gün önce';
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            // Don't trigger if typing in reply input
            if (document.activeElement.id === 'reply-input') return;
            
            if (e.key === 'ArrowLeft') previousStory();
            if (e.key === 'ArrowRight') nextStory();
            if (e.key === 'Escape') window.location.href = 'index';
            if (e.key === ' ') { e.preventDefault(); togglePause(); }
        });

        // ====== REACTION & REPLY FUNCTIONALITY ======
        
        // Load user's current reaction for each story
        async function loadUserReaction() {
            const story = stories[currentIndex];
            if (!story) return;
            
            try {
                const res = await fetch(`api/get_story_reactions.php?story_id=${story.id}`);
                const data = await res.json();
                
                // Reset all reaction buttons
                document.querySelectorAll('.reaction-btn').forEach(btn => {
                    btn.classList.remove('bg-white/30', 'scale-110');
                });
                
                // Highlight user's reaction if exists
                if (data.success && data.user_reaction) {
                    const activeBtn = document.querySelector(`.reaction-btn[data-reaction="${data.user_reaction}"]`);
                    if (activeBtn) {
                        activeBtn.classList.add('bg-white/30', 'scale-110');
                    }
                }
            } catch (e) {
                console.error('Failed to load reaction:', e);
            }
        }

        // Send reaction to current story
        async function sendReaction(reactionType) {
            const story = stories[currentIndex];
            if (!story) return;
            
            // Pause story while reacting
            isPaused = true;
            const currentProgress = document.getElementById(`progress-${currentIndex}`);
            currentProgress.style.animationPlayState = 'paused';
            
            const video = document.getElementById('story-video');
            if (video) video.pause();
            
            // Visual feedback
            const btn = document.querySelector(`.reaction-btn[data-reaction="${reactionType}"]`);
            if (btn) {
                btn.classList.add('animate-bounce');
                setTimeout(() => btn.classList.remove('animate-bounce'), 500);
            }
            
            try {
                const res = await fetch('api/react_story.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `story_id=${story.id}&reaction_type=${encodeURIComponent(reactionType)}`
                });
                const data = await res.json();
                
                if (data.success) {
                    // Update button states
                    document.querySelectorAll('.reaction-btn').forEach(b => {
                        b.classList.remove('bg-white/30', 'scale-110');
                    });
                    
                    if (data.action !== 'removed' && data.reaction) {
                        const activeBtn = document.querySelector(`.reaction-btn[data-reaction="${data.reaction}"]`);
                        if (activeBtn) {
                            activeBtn.classList.add('bg-white/30', 'scale-110');
                        }
                    }
                    
                    // Show toast
                    showToast(data.action === 'removed' ? 'Tepki kaldırıldı' : 'Tepki gönderildi! ' + reactionType);
                } else {
                    showToast(data.error || 'Bir hata oluştu', 'error');
                }
            } catch (e) {
                console.error('Reaction error:', e);
                showToast('Bağlantı hatası', 'error');
            }
            
            // Resume story after short delay
            setTimeout(() => {
                isPaused = false;
                currentProgress.style.animationPlayState = 'running';
                if (video) video.play();
            }, 800);
        }

        // Send reply to current story
        async function sendReply(message) {
            const story = stories[currentIndex];
            if (!story || !message.trim()) return;
            
            const input = document.getElementById('reply-input');
            const submitBtn = input.nextElementSibling;
            
            // Disable input while sending
            input.disabled = true;
            submitBtn.disabled = true;
            
            try {
                const res = await fetch('api/send_story_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `story_id=${story.id}&message=${encodeURIComponent(message)}`
                });
                const data = await res.json();
                
                if (data.success) {
                    input.value = '';
                    showToast('Yanıt gönderildi! 💬');
                } else {
                    showToast(data.error || 'Gönderilemedi', 'error');
                }
            } catch (e) {
                console.error('Reply error:', e);
                showToast('Bağlantı hatası', 'error');
            }
            
            input.disabled = false;
            submitBtn.disabled = false;
            input.focus();
        }

        // Toast notification
        function showToast(message, type = 'success') {
            // Remove existing toast
            const existing = document.getElementById('toast');
            if (existing) existing.remove();
            
            const toast = document.createElement('div');
            toast.id = 'toast';
            toast.className = `fixed top-20 left-1/2 transform -translate-x-1/2 z-[100] px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ${
                type === 'error' ? 'bg-red-500 text-white' : 'bg-white/90 text-slate-900'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Animate in
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, -20px)';
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translate(-50%, 0)';
            }, 10);
            
            // Remove after 2s
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, -20px)';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }

        // Event listeners for reactions
        document.querySelectorAll('.reaction-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                sendReaction(btn.dataset.reaction);
            });
        });

        // Event listener for reply form
        const replyForm = document.getElementById('reply-form');
        if (replyForm) {
            replyForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = document.getElementById('reply-input');
                if (input.value.trim()) {
                    sendReply(input.value.trim());
                }
            });
            
            // Pause story when focusing on input
            const replyInput = document.getElementById('reply-input');
            replyInput.addEventListener('focus', () => {
                isPaused = true;
                const currentProgress = document.getElementById(`progress-${currentIndex}`);
                currentProgress.style.animationPlayState = 'paused';
                const video = document.getElementById('story-video');
                if (video) video.pause();
            });
            
            replyInput.addEventListener('blur', () => {
                // Resume after short delay if input is empty
                setTimeout(() => {
                    if (!replyInput.value.trim()) {
                        isPaused = false;
                        const currentProgress = document.getElementById(`progress-${currentIndex}`);
                        currentProgress.style.animationPlayState = 'running';
                        const video = document.getElementById('story-video');
                        if (video) video.play();
                    }
                }, 100);
            });
        }

        // Load reactions when story changes
        const originalLoadStory = loadStory;
        loadStory = function(index) {
            originalLoadStory(index);
            // Load user's reaction for this story
            setTimeout(loadUserReaction, 300);
        };

        // Initial load
        loadStory(0);
    </script>

</body>
</html>

