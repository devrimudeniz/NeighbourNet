<?php
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// Get Slug
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: guidebook.php");
    exit();
}

// Fetch Guidebook
$stmt = $pdo->prepare("
    SELECT g.*, u.username, u.full_name, u.avatar, u.badge, 
           (SELECT GROUP_CONCAT(badge_type) FROM user_badges WHERE user_id = u.id) as author_badges,
           (SELECT COUNT(*) FROM guidebook_votes WHERE guidebook_id = g.id AND user_id = ?) as my_vote,
           (SELECT COUNT(*) FROM guidebook_votes WHERE guidebook_id = g.id AND vote_type = 'helpful') as total_helpful
    FROM guidebooks g
    JOIN users u ON g.user_id = u.id
    WHERE g.slug = ? AND g.status = 'published'
");
$current_user_id = $_SESSION['user_id'] ?? 0;
$stmt->execute([$current_user_id, $slug]);
$guide = $stmt->fetch();

if (!$guide) {
    http_response_code(404);
    include '404.php'; // Assuming 404.php exists, else simple die
    exit();
}

// Increment Views (Anti-spam: Session based or simple)
// Simple approach for now
if (!isset($_SESSION['viewed_guide_' . $guide['id']])) {
    $pdo->prepare("UPDATE guidebooks SET views = views + 1 WHERE id = ?")->execute([$guide['id']]);
    $_SESSION['viewed_guide_' . $guide['id']] = true;
    $guide['views']++;
}

// Parse Author Badges
$author_badges = [];
if (!empty($guide['badge'])) $author_badges[] = $guide['badge'];
if (!empty($guide['author_badges'])) {
    $author_badges = array_merge($author_badges, explode(',', $guide['author_badges']));
}
$author_badges = array_unique($author_badges);
$is_expert_author = in_array('expert', $author_badges) || in_array('local_guide', $author_badges);

// Tags
$tags = !empty($guide['tags']) ? json_decode($guide['tags'], true) : [];

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/seo_tags.php'; ?>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>

    <!-- Cover Image -->
    <div class="relative w-full overflow-hidden" style="min-height:340px;height:50vh;max-height:480px;">
        <div class="absolute inset-0 z-10" style="background:linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.6) 40%, rgba(15,23,42,0.1) 70%, transparent 100%);"></div>
        <img src="<?php echo htmlspecialchars($guide['cover_image']); ?>" class="w-full h-full object-cover" alt="">
        
        <div class="absolute bottom-0 left-0 w-full z-20" style="padding:20px 16px 24px;">
            <div class="container mx-auto max-w-4xl">
                <span style="display:inline-block;padding:4px 12px;background:#7c3aed;color:#fff;font-size:11px;font-weight:700;border-radius:20px;margin-bottom:10px;">
                    <?php echo htmlspecialchars($guide['category']); ?>
                </span>
                <h1 style="font-size:clamp(22px, 5vw, 40px);font-weight:900;color:#fff;line-height:1.2;margin:0 0 12px;text-shadow:0 2px 8px rgba(0,0,0,0.3);">
                    <?php echo htmlspecialchars($guide['title']); ?>
                </h1>
                
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
                    <!-- Author -->
                    <a href="profile?username=<?php echo $guide['username']; ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#fff;padding:4px 8px 4px 4px;border-radius:10px;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">
                        <img src="<?php echo $guide['avatar']; ?>" style="width:36px;height:36px;border-radius:50%;border:2px solid rgba(255,255,255,0.5);object-fit:cover;" alt="">
                        <div>
                            <p style="font-weight:700;font-size:13px;margin:0;"><?php echo htmlspecialchars($guide['full_name']); ?> <?php if($is_expert_author) echo '<i class="fas fa-check-circle" style="color:#60a5fa;margin-left:2px;"></i>'; ?></p>
                            <p style="font-size:11px;color:rgba(255,255,255,0.6);margin:0;">@<?php echo htmlspecialchars($guide['username']); ?></p>
                        </div>
                    </a>
                    
                    <!-- Stats -->
                    <div style="display:flex;align-items:center;gap:12px;font-size:12px;color:rgba(255,255,255,0.7);font-weight:500;">
                        <span><i class="far fa-clock" style="margin-right:3px;"></i> <?php echo $guide['reading_time']; ?> min</span>
                        <span><i class="far fa-eye" style="margin-right:3px;"></i> <?php echo number_format($guide['views']); ?></span>
                        <span><i class="far fa-calendar" style="margin-right:3px;"></i> <?php echo date('d M Y', strtotime($guide['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back Button -->
        <a href="guidebook" style="position:absolute;top:80px;left:16px;z-index:30;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:14px;">
            <i class="fas fa-arrow-left"></i>
        </a>

        <!-- Edit Button -->
        <?php if($current_user_id == $guide['user_id'] || in_array($_SESSION['badge'] ?? '', ['founder', 'moderator'])): ?>
        <a href="edit_guide.php?id=<?php echo $guide['id']; ?>" style="position:absolute;top:80px;right:16px;z-index:30;padding:8px 14px;border-radius:12px;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);display:flex;align-items:center;gap:6px;color:#fff;text-decoration:none;font-size:13px;font-weight:700;border:1px solid rgba(255,255,255,0.2);">
            <i class="fas fa-edit"></i>
            <span class="hidden md:inline"><?php echo $lang == 'en' ? 'Edit' : 'Düzenle'; ?></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <main class="container mx-auto px-4 max-w-3xl -mt-10 relative z-30 pb-12">
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 md:p-10 shadow-xl border border-slate-100 dark:border-slate-700">
            
            <!-- Article Content -->
            <article class="prose prose-lg dark:prose-invert max-w-none mb-10">
                <?php 
                function parseSimpleMarkdown($text) {
                    $text = htmlspecialchars($text);
                    
                    // Heads
                    $text = preg_replace('/^###### (.*?)$/m', '<h6>$1</h6>', $text);
                    $text = preg_replace('/^##### (.*?)$/m', '<h5>$1</h5>', $text);
                    $text = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $text);
                    $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
                    $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
                    $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
                    
                    // Bold
                    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
                    
                    // Italic
                    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
                    
                    // Markdown links [text](url)
                    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener" class="text-violet-600 hover:underline font-semibold">$1</a>', $text);
                    
                    // Auto-link bare URLs (not already inside an href or tag)
                    $text = preg_replace(
                        '~(?<!href=["\'])(?<!src=["\'])(https?://[^\s<>\)]+)~i',
                        '<a href="$1" target="_blank" rel="noopener" class="text-violet-600 hover:underline break-all">$1</a>',
                        $text
                    );
                    
                    // Blockquotes
                    $text = preg_replace('/^&gt; (.*?)$/m', '<blockquote class="border-l-4 border-violet-500 pl-4 py-2 italic bg-slate-50 dark:bg-slate-900 my-4">$1</blockquote>', $text);
                    
                    // Lists
                    $text = preg_replace('/^\* (.*?)$/m', '<li>$1</li>', $text);
                    $text = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $text);
                    
                    // Paragraphs: double newline
                    $text = '<p>' . preg_replace('/\n\s*\n/', '</p><p>', $text) . '</p>';
                    
                    // Single newlines to <br>
                    $text = preg_replace('/(?<!\>)\n(?!\<)/', '<br>', $text);
                    
                    // Cleanup
                    $text = str_replace('<p></p>', '', $text);
                    $text = str_replace('<p><br></p>', '', $text);
                    
                    return $text;
                }

                echo parseSimpleMarkdown($guide['content']); 
                ?> 
            </article>
            
            <!-- Tags -->
            <?php if(!empty($tags)): ?>
            <div class="flex flex-wrap gap-2 mb-10 pt-6 border-t border-slate-100 dark:border-slate-700">
                <?php foreach($tags as $tag): ?>
                    <a href="guidebook.php?search=<?php echo urlencode($tag); ?>" class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-sm hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        #<?php echo htmlspecialchars($tag); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Helpful Section -->
            <div class="bg-violet-50 dark:bg-slate-900/50 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white text-lg">Did you find this guide helpful?</h3>
                    <p class="text-slate-500 text-sm">Help others find the best local advice.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <?php 
                    $has_voted = ($guide['my_vote'] ?? 0) > 0;
                    $helpful_count = $guide['total_helpful'] ?? 0;
                    ?>
                    <button onclick="voteGuide(<?php echo $guide['id']; ?>, 'helpful')" id="btn-helpful" 
                        class="px-6 py-2 rounded-xl font-bold transition-all flex items-center gap-2 <?php echo $has_voted ? 'bg-violet-600 text-white shadow-lg cursor-default' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-violet-500 hover:text-violet-500 shadow-sm'; ?>">
                        <i class="fas fa-thumbs-up"></i> Yes
                        <?php if($helpful_count > 0): ?>
                            <span class="ml-1 opacity-80">(<?php echo $helpful_count; ?>)</span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>

        </div>
        
        <!-- Author Box -->
        <div class="mt-8 bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700 flex items-center gap-4">
            <img src="<?php echo $guide['avatar']; ?>" class="w-16 h-16 rounded-full object-cover border-2 border-violet-100 dark:border-slate-700">
            <div class="flex-1">
                <h4 class="font-bold text-lg text-slate-800 dark:text-white">
                    Written by <?php echo htmlspecialchars($guide['full_name']); ?>
                </h4>
                <p class="text-slate-500 text-sm mb-2">Verified Local Guide</p>
                <a href="profile?username=<?php echo $guide['username']; ?>" class="text-violet-600 font-bold text-sm hover:underline">
                    View Profile & Other Guides
                </a>
            </div>
             <?php if($current_user_id != $guide['user_id']): ?>
            <button onclick="window.location.href='messages?uid=<?php echo $guide['user_id']; ?>'" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-white rounded-xl font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                Message
            </button>
            <?php endif; ?>
        </div>
        
    </main>

    <!-- Bottom nav comes from header.php -->
    
    <script>
    function voteGuide(id, type) {
        // Prevent multiple votes if already voted UI check (backend also checks)
        const btn = document.getElementById('btn-helpful');
        if(btn.classList.contains('bg-violet-600')) return;

        fetch('api/guidebook_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=vote&guide_id=${id}&vote_type=${type}`
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                // Update UI to Voted state
                btn.className = "px-6 py-2 rounded-xl font-bold transition-all flex items-center gap-2 bg-violet-600 text-white shadow-lg cursor-default";
                // Increment count strictly for UI visual (optional)
                const countSpan = btn.querySelector('span');
                if(countSpan) {
                    let c = parseInt(countSpan.innerText.replace('(', '').replace(')', ''));
                    countSpan.innerText = '(' + (c + 1) + ')';
                } else {
                    btn.innerHTML += ' <span class="ml-1 opacity-80">(1)</span>';
                }
            } else if(data.error) {
                alert(data.error);
            }
        });
    }
    </script>

</body>
</html>
