<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/ui_components.php';
session_start();

// Access Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check Expert Badge (via global session or DB check)
// Assuming user might have 'expert_badge' column now or 'expert' role in badge column
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_type IN ('expert', 'local_guide')");
$stmt->execute([$_SESSION['user_id']]);
$is_expert = $stmt->fetchColumn() > 0 || in_array($_SESSION['badge'] ?? '', ['founder', 'moderator', 'expert', 'local_guide']); // Added 'expert' check if direct badge

if (!$is_expert) {
    echo "<div class='pt-24 container mx-auto text-center'><h1 class='text-2xl font-bold'>Access Denied</h1><p>You must be a verified Local Expert to write guides.</p></div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write a Guide - Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20">
<?php require_once 'includes/header.php'; ?>

<div class="container mx-auto px-4 py-8 pt-24 max-w-4xl">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">Write a Guidebook</h1>
            <p class="text-slate-500">Share your expertise with the Kalkan community.</p>
        </div>
        <a href="guidebook.php" class="text-sm font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i> Back to Guidehub
        </a>
    </div>

    <form id="guideForm" class="space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-6 lg:p-8 shadow-xl border border-slate-100 dark:border-slate-700">
            
            <!-- Title -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Guide Title</label>
                <input type="text" name="title" required
                    class="w-full text-2xl font-bold bg-transparent border-b-2 border-slate-200 dark:border-slate-600 focus:border-violet-500 outline-none py-2 transition-colors placeholder-slate-300 dark:placeholder-slate-600"
                    placeholder="e.g., The Ultimate Guide to Kalkan's Nightlife">
            </div>

            <!-- Category, Cover & Reading Time -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Category</label>
                    <select name="category" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none">
                        <option value="Food & Drink">Food & Drink</option>
                        <option value="History">History</option>
                        <option value="Beaches">Beaches</option>
                        <option value="Lifestyle">Lifestyle</option>
                        <option value="Nature">Nature</option>
                        <option value="Hidden Gems">Hidden Gems</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Cover Image URL</label>
                    <input type="url" name="cover_image" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none" placeholder="https://...">
                </div>
            </div>

            <!-- Tags -->
            <div class="mb-6">
                 <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Tags (Max 5)</label>
                 <input type="text" name="tags" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none" placeholder="e.g. Vegan, Romantic, Sunset, Budget (comma separated)">
            </div>

            <!-- Content Editor -->
             <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Content</label>
                <div class="text-xs text-slate-400 mb-2">Tip: Use Markdown for formatting. Reading time will be calculated automatically.</div>
                <textarea name="content" required rows="15" 
                    class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-4 font-medium focus:ring-2 focus:ring-violet-500 outline-none leading-relaxed"
                    placeholder="Start writing your masterpiece..."></textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="flex-1 py-4 bg-violet-600 text-white rounded-xl font-bold shadow-lg shadow-violet-500/30 hover:bg-violet-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-upload"></i> Publish Guide
                </button>
            </div>

        </div>
    </form>
</div>

<script>
document.getElementById('guideForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';

    const formData = new FormData(this);
    formData.append('action', 'create');

    // Simple manual reading time calc if needed client side, but backend does it better.

    fetch('api/guidebook_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Published!';
            btn.classList.replace('bg-violet-600', 'bg-green-600');
            setTimeout(() => {
                window.location.href = 'guidebook.php';
            }, 1000);
        } else {
            alert(data.error || 'Failed to publish');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        alert('Error: ' + err);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>

<?php require_once 'includes/bottom_nav.php'; ?>
</body>
</html>
