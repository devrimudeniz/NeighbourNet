<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/ui_components.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is already an expert
$is_expert = false; 

// Check if user has pending application
$stmt = $pdo->prepare("SELECT * FROM expert_applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$application = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Local Expert - Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php include 'includes/seo_tags.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20">
<?php require_once 'includes/header.php'; ?>



<div class="container mx-auto px-4 py-8 max-w-2xl pt-24">
    <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-[2rem] p-8 shadow-2xl border border-white/20 relative overflow-hidden">
        
        <!-- Decorative Header -->
        <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-violet-600 to-indigo-600 opacity-10"></div>
        <div class="relative z-10 mb-8 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-2xl mx-auto flex items-center justify-center shadow-lg shadow-violet-500/30 mb-4 transform -rotate-3">
                <i class="fas fa-certificate text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-violet-600 to-indigo-500 mb-2">
                Become a Local Expert
            </h1>
            <p class="text-slate-600 dark:text-slate-300">
                Share your knowledge, earn a verified badge, and guide the community.
            </p>
        </div>

        <?php if ($application && $application['status'] == 'pending'): ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-6 rounded-r-xl mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    <h3 class="font-bold text-yellow-800 dark:text-yellow-200">Application Pending</h3>
                </div>
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    We have received your application for <strong><?php echo htmlspecialchars($application['area_of_expertise']); ?></strong>. 
                    Our team is reviewing it. You'll be notified once a decision is made.
                </p>
            </div>
        <?php elseif ($application && $application['status'] == 'approved'): ?>
             <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-6 rounded-r-xl mb-6 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                <h3 class="font-bold text-green-800 dark:text-green-200 text-xl">Congratulations!</h3>
                <p class="text-green-700 dark:text-green-300 mt-2">
                    You are a verified Local Expert! You can now access the Guidebook tools and Q&A features.
                </p>
                <a href="guidebook.php" class="inline-block mt-4 px-6 py-2 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition">
                    Go to Guidehub
                </a>
            </div>
        <?php else: ?>

            <form id="expertForm" class="space-y-6">
                <!-- Expertise Area -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Area of Expertise</label>
                    <select name="area_of_expertise" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none transition-all">
                        <option value="">Select your niche...</option>
                        <option value="History & Culture">History & Culture</option>
                        <option value="Food & Dining">Food & Dining</option>
                        <option value="Nightlife & Events">Nightlife & Events</option>
                        <option value="Nature & Hiking">Nature & Hiking</option>
                        <option value="Marine & Boating">Marine & Boating</option>
                        <option value="Local Living">Local Living (Expat Life)</option>
                    </select>
                </div>

                <!-- Motivation -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Why should you be an expert?</label>
                    <textarea name="motivation" rows="4" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none transition-all" placeholder="Tell us about your experience in Kalkan/Kaş..."></textarea>
                    <p class="text-xs text-slate-400 mt-1 text-right">Min 20 characters</p>
                </div>

                <!-- Social Links -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Social Media / Portfolio (Optional)</label>
                    <input type="text" name="social_links" class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 font-medium focus:ring-2 focus:ring-violet-500 outline-none transition-all" placeholder="Instagram, Website, etc.">
                </div>

                <!-- Benefits List -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border border-indigo-100 dark:border-indigo-800">
                    <h4 class="font-bold text-indigo-800 dark:text-indigo-300 mb-2 text-sm">Expert Benefits:</h4>
                    <ul class="text-xs text-indigo-700 dark:text-indigo-400 space-y-1">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-indigo-500"></i> Verified "Expert" Badge on profile</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-indigo-500"></i> Ability to write official Guidebooks</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-indigo-500"></i> "Ask Me Anything" Q&A Tab</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-indigo-500"></i> Priority support & community recognition</li>
                    </ul>
                </div>

                <button type="submit" id="submitBtn" class="w-full py-4 bg-gradient-to-r from-violet-600 to-indigo-600 text-white rounded-xl font-black shadow-lg shadow-violet-500/30 hover:scale-[1.02] active:scale-95 transition-all text-lg flex items-center justify-center gap-2">
                    <span>Submit Application</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>

        <?php endif; ?>

    </div>
</div>

<script>
document.getElementById('expertForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const originalContent = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData(this);

    fetch('api/apply_expert.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success Animation
            btn.innerHTML = '<i class="fas fa-check"></i> Submitted!';
            btn.classList.remove('from-violet-600', 'to-indigo-600');
            btn.classList.add('bg-green-500');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert(data.error || 'Something went wrong');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    })
    .catch(error => {
        alert('Error: ' + error);
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
});
</script>

<?php require_once 'includes/bottom_nav.php'; ?>
</body>
</html>
