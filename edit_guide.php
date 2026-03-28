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

$id = $_GET['id'] ?? 0;
if (!$id) {
    header("Location: guidebook.php");
    exit;
}

// Fetch Guide
$stmt = $pdo->prepare("SELECT * FROM guidebooks WHERE id = ?");
$stmt->execute([$id]);
$guide = $stmt->fetch();

if (!$guide) {
    header("Location: guidebook.php");
    exit;
}

// Permission Check (Author or Admin)
$current_user_id = $_SESSION['user_id'];
$is_admin = in_array($_SESSION['badge'] ?? '', ['founder', 'moderator']);
if ($guide['user_id'] != $current_user_id && !$is_admin) {
    echo "<div class='pt-24 container mx-auto text-center'><h1 class='text-2xl font-bold'>Access Denied</h1><p>You can only edit your own guides.</p></div>";
    exit;
}

// Tags handling
$tags_json = $guide['tags'] ?: '[]';
$tags_array = json_decode($tags_json, true) ?: [];
$tags_string = implode(', ', $tags_array);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Edit Guide' : 'Yazıyı Düzenle'; ?> - Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20">
<?php require_once 'includes/header.php'; ?>

<div class="container mx-auto px-4 py-8 pt-24 max-w-4xl">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2"><?php echo $lang == 'en' ? 'Edit Guidebook' : 'Yazıyı Düzenle'; ?></h1>
            <p class="text-slate-500"><?php echo $lang == 'en' ? 'Keep your information up to date.' : 'Bilgilerinizi güncel tutun.'; ?></p>
        </div>
        <a href="guidebook_detail.php?slug=<?php echo $guide['slug']; ?>" class="text-sm font-bold text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i> <?php echo $lang == 'en' ? 'Back to Guide' : 'Yazıya Dön'; ?>
        </a>
    </div>

    <form id="guideForm" class="space-y-6">
        <input type="hidden" name="id" value="<?php echo $guide['id']; ?>">
        <div class="bg-white dark:bg-slate-800 rounded-[2rem] p-6 lg:p-8 shadow-xl border border-slate-100 dark:border-slate-700">
            
            <!-- Title -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2"><?php echo $lang == 'en' ? 'Guide Title' : 'Yazı Başlığı'; ?></label>
                <input type="text" name="title" required value="<?php echo htmlspecialchars($guide['title']); ?>"
                    class="w-full text-2xl font-bold bg-transparent border-b-2 border-slate-200 dark:border-slate-600 focus:border-violet-500 outline-none py-2 transition-colors placeholder-slate-300 dark:placeholder-slate-600">
            </div>

            <!-- Category, Cover & Reading Time -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                    <select name="category" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none">
                        <?php 
                        $cats = ['Food & Drink', 'History', 'Beaches', 'Lifestyle', 'Nature', 'Hidden Gems'];
                        foreach($cats as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $guide['category'] == $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2"><?php echo $lang == 'en' ? 'Cover Image URL' : 'Kapak Görseli Linki'; ?></label>
                    <input type="url" name="cover_image" value="<?php echo htmlspecialchars($guide['cover_image']); ?>" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none">
                </div>
            </div>

            <!-- Tags -->
            <div class="mb-6">
                 <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2"><?php echo $lang == 'en' ? 'Tags (Max 5)' : 'Etiketler (Max 5)'; ?></label>
                 <input type="text" name="tags" value="<?php echo htmlspecialchars($tags_string); ?>" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none" placeholder="Vegan, Sunset, Budget...">
            </div>

            <!-- Content Editor -->
             <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2"><?php echo $lang == 'en' ? 'Content' : 'İçerik'; ?></label>
                <div class="text-xs text-slate-400 mb-2"><?php echo $lang == 'en' ? 'Tip: Use Markdown for formatting.' : 'İpucu: Biçimlendirme için Markdown kullanın.'; ?></div>
                <textarea name="content" required rows="15" 
                    class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-4 font-medium focus:ring-2 focus:ring-violet-500 outline-none leading-relaxed"><?php echo htmlspecialchars($guide['content']); ?></textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="submit" class="flex-1 py-4 bg-violet-600 text-white rounded-xl font-bold shadow-lg shadow-violet-500/30 hover:bg-violet-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> <?php echo $lang == 'en' ? 'Save Changes' : 'Değişiklikleri Kaydet'; ?>
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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo $lang == "en" ? "Saving..." : "Kaydediliyor..."; ?>';

    const formData = new FormData(this);
    formData.append('action', 'update');

    fetch('api/guidebook_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> <?php echo $lang == "en" ? "Saved!" : "Kaydedildi!"; ?>';
            btn.classList.replace('bg-violet-600', 'bg-green-600');
            setTimeout(() => {
                window.location.href = 'guidebook_detail.php?slug=<?php echo $guide['slug']; ?>';
            }, 1000);
        } else {
            alert(data.error || 'Failed to save');
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
