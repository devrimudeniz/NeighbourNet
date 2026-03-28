<?php
require_once 'includes/db.php';
session_start();
require_once 'includes/lang.php';
require_once 'includes/contest_helper.php';

// Trigger winner check on page load (lazy execution)
checkAndPickWinner($pdo);

// Handle Upload
$upload_error = '';
$upload_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login");
        exit;
    }

    // Handle Delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $submission_id = (int)$_POST['post_id'];
        $del_stmt = $pdo->prepare("UPDATE contest_submissions SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $del_stmt->execute([$submission_id, $_SESSION['user_id']]);
        header("Location: photo_contest.php");
        exit;
    }

    // Handle File Upload
    if (isset($_FILES['photo'])) {
        $caption = trim($_POST['caption'] ?? '');
        $file = $_FILES['photo'];

        // Haftalık limit: her kullanıcı haftada 1 fotoğraf (kendi user_id'si için kontrol)
        $week_start_check = getCurrentContestWeek() . " 00:00:00";
        $dup_stmt = $pdo->prepare("SELECT id FROM contest_submissions WHERE user_id = ? AND created_at >= ? AND deleted_at IS NULL");
        $dup_stmt->execute([$_SESSION['user_id'], $week_start_check]);
        if ($dup_stmt->fetch()) {
            $upload_error = $lang == 'en' ? 'You have already submitted a photo this week. Wait for next week!' : 'Bu hafta zaten bir fotoğraf gönderdiniz. Gelecek haftayı bekleyin!';
        } elseif ($file['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                require_once 'includes/optimize_upload.php';
                $filename = uniqid('cs_') . '.' . $ext;
                $upload_path = 'uploads/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    try {
                        // Index için küçük thumb (h-40 = 160px gösterim)
                        $abs_path = realpath($upload_path) ?: (__DIR__ . '/' . $upload_path);
                        $thumb_path = dirname($abs_path) . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_thumb.webp';
                        if (function_exists('createThumbnail')) {
                            createThumbnail($abs_path, $thumb_path, 400, 200);
                        }
                        $stmt = $pdo->prepare("INSERT INTO contest_submissions (user_id, caption, image_path, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$_SESSION['user_id'], $caption, $upload_path]);
                        $upload_success = $lang == 'en' ? 'Photo submitted successfully!' : 'Fotoğraf başarıyla gönderildi!';
                    } catch (PDOException $e) {
                        error_log("Contest upload DB error: " . $e->getMessage());
                        $upload_error = $lang == 'en' ? 'Could not save photo. Please try again.' : 'Fotoğraf kaydedilemedi. Lütfen tekrar deneyin.';
                    }
                } else {
                    $upload_error = $lang == 'en' ? 'Upload failed.' : 'Yükleme başarısız.';
                }
            } else {
                $upload_error = $lang == 'en' ? 'Invalid file type. Use JPG, PNG or WebP.' : 'Geçersiz dosya türü. JPG, PNG veya WebP kullanın.';
            }
        } else {
            $upload_error = $lang == 'en' ? 'Error selecting file.' : 'Dosya seçme hatası.';
        }
    }
}

// Check if current user already submitted this week
$user_already_submitted = false;
if (isset($_SESSION['user_id'])) {
    $week_check = getCurrentContestWeek() . " 00:00:00";
    $sub_check = $pdo->prepare("SELECT id FROM contest_submissions WHERE user_id = ? AND created_at >= ? AND deleted_at IS NULL");
    $sub_check->execute([$_SESSION['user_id'], $week_check]);
    $user_already_submitted = (bool)$sub_check->fetch();
}

// Get Current Week's Entries
$current_week_start = getCurrentContestWeek() . " 00:00:00";
$entries_stmt = $pdo->prepare("
    SELECT s.*, u.username, u.full_name, u.avatar,
           (SELECT COUNT(*) FROM contest_votes WHERE submission_id = s.id) as love_count,
           (SELECT COUNT(*) FROM contest_votes WHERE submission_id = s.id AND user_id = ?) as loved_by_me
    FROM contest_submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.created_at >= ?
    AND s.deleted_at IS NULL
    ORDER BY love_count DESC, s.created_at DESC
");
$entries_stmt->execute([$_SESSION['user_id'] ?? 0, $current_week_start]);
$entries = $entries_stmt->fetchAll();

// Get Past Winners
$winners = [];
try {
    $winners_stmt = $pdo->query("
        SELECT cw.*, s.image_path, s.caption, u.username, u.full_name, u.avatar
        FROM contest_winners cw
        JOIN contest_submissions s ON cw.submission_id = s.id
        JOIN users u ON cw.user_id = u.id
        ORDER BY cw.week_of DESC
        LIMIT 10
    ");
    $winners = $winners_stmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkan Snaps | Photo Contest</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        .photo-card:hover .overlay { opacity: 1; }
        .love-btn.active { color: #ec4899; }
        /* Lightbox */
        .lightbox { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.92); backdrop-filter:blur(8px); align-items:center; justify-content:center; }
        .lightbox.active { display:flex; }
        .lightbox img { max-width:92vw; max-height:88vh; object-fit:contain; border-radius:12px; box-shadow:0 25px 60px rgba(0,0,0,.5); }
        .lightbox-close { position:absolute; top:16px; right:16px; width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.15); color:#fff; border:none; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:10; }
        .lightbox-close:hover { background:rgba(255,255,255,.25); }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="relative h-[500px] flex items-center justify-center text-center text-white overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img src="assets/kalkan/kalkan5.jpg" class="w-full h-full object-cover scale-105 hover:scale-100 transition-transform duration-[10s] ease-in-out">
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-slate-50 dark:to-slate-900"></div>
        </div>
        <div class="relative z-10 px-4 max-w-4xl mx-auto mt-10">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white font-bold mb-6 animate-fade-in-up">
                <span class="bg-gradient-to-r from-pink-500 to-violet-500 text-white text-xs px-2 py-0.5 rounded-full">NEW</span>
                <span class="text-sm tracking-wide uppercase"><?php echo $lang == 'en' ? 'Weekly Challenge' : 'Haftalık Yarışma'; ?></span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tight drop-shadow-2xl animate-fade-in-up delay-100">
                Kalkan <span class="text-pink-400">Snaps</span>
            </h1>
            
            <p class="text-xl md:text-2xl text-white/90 mb-10 max-w-2xl mx-auto font-light leading-relaxed animate-fade-in-up delay-200">
                <?php echo $lang == 'en' 
                    ? 'Leave your mark on Kalkan\'s history. Share your best shots, win the community\'s heart, and become a legend.'
                    : 'Kalkan tarihine iz bırakın. En iyi karelerinizi paylaşın, topluluğun kalbini kazanın ve bir efsaneye dönüşün.'; ?>
            </p>
            
            <div class="animate-fade-in-up delay-300">
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($user_already_submitted): ?>
                <div class="inline-flex items-center gap-3 px-8 py-4 bg-white/10 backdrop-blur-md border border-white/20 text-white rounded-full font-bold text-lg">
                    <i class="fas fa-check-circle text-emerald-400 text-xl"></i>
                    <?php echo $lang == 'en' ? 'You\'ve entered this week!' : 'Bu hafta katıldınız!'; ?>
                </div>
                <?php else: ?>
                <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="group relative px-8 py-4 bg-white text-slate-900 rounded-full font-black text-lg hover:scale-105 transition-all shadow-[0_0_40px_-10px_rgba(255,255,255,0.5)] hover:shadow-[0_0_60px_-15px_rgba(255,255,255,0.7)]">
                    <span class="relative z-10 flex items-center gap-3">
                        <i class="fas fa-camera text-xl group-hover:rotate-12 transition-transform"></i>
                        <?php echo $lang == 'en' ? 'Submit Your Masterpiece' : 'Eserini Gönder'; ?>
                    </span>
                    <div class="absolute inset-0 rounded-full bg-gradient-to-r from-pink-500 to-violet-500 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                </button>
                <?php endif; ?>
            <?php else: ?>
                <a href="login" class="inline-block px-8 py-4 bg-white/10 backdrop-blur-md border border-white/30 text-white rounded-full font-bold text-lg hover:bg-white/20 hover:scale-105 transition-all">
                    <?php echo $lang == 'en' ? 'Login to Compete' : 'Yarışmak İçin Giriş Yap'; ?>
                </a>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- HOW IT WORKS Section -->
    <section class="max-w-6xl mx-auto px-4 -mt-20 relative z-20 mb-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Step 1 -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl border border-slate-100 dark:border-slate-700 backdrop-blur-xl relative overflow-hidden group hover:-translate-y-2 transition-transform duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-blue-500/20 transition-colors"></div>
                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mb-6 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-camera-retro text-3xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2"><?php echo $lang == 'en' ? '1. Snap & Share' : '1. Çek & Paylaş'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                    <?php echo $lang == 'en' ? 'Capture a stunning moment in Kalkan and upload it to the weekly contest.' : 'Kalkan\'da büyüleyici bir an yakalayın ve haftalık yarışmaya yükleyin.'; ?>
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl border border-slate-100 dark:border-slate-700 backdrop-blur-xl relative overflow-hidden group hover:-translate-y-2 transition-transform duration-300 delay-100">
                <div class="absolute top-0 right-0 w-32 h-32 bg-pink-500/10 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-pink-500/20 transition-colors"></div>
                <div class="w-16 h-16 bg-pink-50 dark:bg-pink-900/30 rounded-2xl flex items-center justify-center mb-6 text-pink-600 dark:text-pink-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-heart text-3xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2"><?php echo $lang == 'en' ? '2. Collect Love' : '2. Love Topla'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                    <?php echo $lang == 'en' ? 'Share with friends! The photo with the most "Love" reactions wins.' : 'Arkadaşlarınla paylaş! En çok "Love" reaksiyonu alan fotoğraf kazanır.'; ?>
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl border border-slate-100 dark:border-slate-700 backdrop-blur-xl relative overflow-hidden group hover:-translate-y-2 transition-transform duration-300 delay-200">
                <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/10 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-yellow-500/20 transition-colors"></div>
                <div class="w-16 h-16 bg-yellow-50 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center mb-6 text-yellow-600 dark:text-yellow-400 group-hover:scale-110 transition-transform">
                    <i class="fas fa-trophy text-3xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2"><?php echo $lang == 'en' ? '3. Be Immortal' : '3. Efsane Ol'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                    <?php echo $lang == 'en' ? 'Winners earn the "Photographer" badge and are featured on the homepage.' : 'Kazananlar "Fotoğrafçı" rozeti kazanır ve ana sayfada sergilenir.'; ?>
                </p>
            </div>
        </div>
    </section>

    <main class="container mx-auto px-4 py-12">
        
        <!-- Alerts -->
        <?php if($upload_success): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 p-4 rounded-xl mb-8 text-center font-bold">
                <?php echo $upload_success; ?>
            </div>
        <?php endif; ?>
        <?php if($upload_error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-8 text-center font-bold">
                <?php echo $upload_error; ?>
            </div>
        <?php endif; ?>

        <!-- Current Week Entries -->
        <section class="mb-16">
            <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <span class="bg-pink-500 text-white w-10 h-10 rounded-full flex items-center justify-center text-lg"><i class="fas fa-fire"></i></span>
                        <?php echo $lang == 'en' ? "This Week's Entries" : 'Bu Haftanın Adayları'; ?>
                    </h2>
                    <p class="text-slate-500 mt-2 font-medium">
                        <?php echo $lang == 'en' ? 'Vote for your favorite by reacting with "Love"!' : 'Favorinizi "Love" ile oylayın!'; ?>
                    </p>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Countdown -->
                    <div class="hidden md:block text-right">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1"><?php echo $lang == 'en' ? 'Time Remaining' : 'Kalan Süre'; ?></div>
                        <div class="text-2xl font-black font-mono text-slate-900 dark:text-white" id="countdown">Calculating...</div>
                    </div>
                </div>
            </div>

            <?php if(count($entries) > 0): ?>
            <div class="columns-1 md:columns-2 lg:columns-3 gap-6 space-y-6">
                
                <!-- Inline Upload Card (first position) -->
                <?php if(isset($_SESSION['user_id']) && !$user_already_submitted): ?>
                <div class="break-inside-avoid mb-0">
                    <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" 
                            class="w-full bg-gradient-to-br from-pink-50 to-violet-50 dark:from-pink-900/20 dark:to-violet-900/20 rounded-2xl border-2 border-dashed border-pink-300 dark:border-pink-700 p-8 text-center hover:border-pink-500 dark:hover:border-pink-500 hover:shadow-lg hover:shadow-pink-500/10 hover:-translate-y-1 transition-all group cursor-pointer">
                        <div class="w-16 h-16 bg-gradient-to-r from-pink-500 to-violet-500 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform shadow-lg shadow-pink-500/30">
                            <i class="fas fa-camera text-white text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mb-1">
                            <?php echo $lang == 'en' ? 'Share Your Shot' : 'Fotoğrafını Paylaş'; ?>
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                            <?php echo $lang == 'en' ? 'Upload your best Kalkan photo and compete!' : 'En iyi Kalkan fotoğrafını yükle ve yarış!'; ?>
                        </p>
                        <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-pink-500 to-violet-500 text-white text-sm font-bold rounded-xl shadow-md group-hover:shadow-lg group-hover:shadow-pink-500/30 transition-all">
                            <i class="fas fa-plus"></i>
                            <?php echo $lang == 'en' ? 'Add Photo' : 'Fotoğraf Ekle'; ?>
                        </span>
                    </button>
                </div>
                <?php elseif(!isset($_SESSION['user_id'])): ?>
                <div class="break-inside-avoid mb-0">
                    <a href="login" class="block w-full bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-800 dark:to-blue-900/20 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 p-8 text-center hover:border-blue-400 hover:shadow-lg hover:-translate-y-1 transition-all group">
                        <div class="w-16 h-16 bg-slate-200 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-sign-in-alt text-slate-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mb-1">
                            <?php echo $lang == 'en' ? 'Join the Contest' : 'Yarışmaya Katıl'; ?>
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <?php echo $lang == 'en' ? 'Login to submit your photo' : 'Fotoğraf göndermek için giriş yap'; ?>
                        </p>
                    </a>
                </div>
                <?php endif; ?>

                <?php foreach($entries as $entry): ?>
                <div class="break-inside-avoid bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-xl transition-shadow relative photo-card" data-submission-creator="<?php echo $entry['user_id']; ?>">
                    
                    <div class="relative group">
                        <img src="<?php echo htmlspecialchars($entry['image_path'] ?? ''); ?>" 
                             class="w-full object-cover cursor-pointer" 
                             style="max-height: 320px;"
                             onclick="openLightbox('<?php echo htmlspecialchars($entry['image_path'] ?? '', ENT_QUOTES); ?>')"
                             loading="lazy"
                             alt="<?php echo htmlspecialchars($entry['full_name']); ?>'s entry">
                        
                        <!-- Expand icon on hover -->
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors pointer-events-none flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-expand text-white text-2xl drop-shadow-lg"></i>
                            </div>
                        </div>

                        <!-- DELETE BUTTON FOR OWNER (always visible) -->
                        <?php if(isset($_SESSION['user_id']) && $entry['user_id'] == $_SESSION['user_id']): ?>
                        <form method="POST" onsubmit="return confirm('<?php echo $lang == 'en' ? 'Delete this photo?' : 'Bu fotoğrafı silmek istiyor musunuz?'; ?>');" class="absolute top-3 right-3 z-20">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="post_id" value="<?php echo $entry['id']; ?>">
                            <button type="submit" class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <img src="<?php echo htmlspecialchars($entry['avatar']); ?>" class="w-8 h-8 rounded-full object-cover">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($entry['full_name']); ?></h4>
                                <p class="text-xs text-slate-400"><?php echo date('d M', strtotime($entry['created_at'])); ?></p>
                            </div>
                            <!-- Love button -->
                            <button onclick="toggleLove(<?php echo $entry['id']; ?>, this)" 
                                    class="love-btn flex items-center gap-1.5 px-3 py-1.5 rounded-full border transition-all <?php echo $entry['loved_by_me'] ? 'active bg-pink-50 dark:bg-pink-900/20 border-pink-200 dark:border-pink-800' : 'bg-slate-50 dark:bg-slate-700 border-slate-200 dark:border-slate-600 hover:bg-pink-50 dark:hover:bg-pink-900/20'; ?>">
                                <i class="fas fa-heart text-sm <?php echo $entry['loved_by_me'] ? 'text-pink-500' : 'text-slate-400'; ?>"></i>
                                <span class="font-bold text-sm love-count <?php echo $entry['loved_by_me'] ? 'text-pink-600 dark:text-pink-400' : 'text-slate-600 dark:text-slate-300'; ?>"><?php echo $entry['love_count']; ?></span>
                            </button>
                        </div>
                        <?php if(!empty($entry['caption'])): ?>
                        <p class="text-sm text-slate-600 dark:text-slate-300 line-clamp-2"><?php echo htmlspecialchars($entry['caption']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-20 bg-gradient-to-br from-pink-50 to-violet-50 dark:from-slate-800/50 dark:to-slate-800/50 rounded-3xl border-2 border-dashed border-pink-200 dark:border-slate-700">
                <div class="w-24 h-24 bg-gradient-to-r from-pink-500 to-violet-500 rounded-3xl flex items-center justify-center mx-auto mb-6 text-white text-4xl shadow-lg shadow-pink-500/20">
                    <i class="fas fa-camera-retro"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-800 dark:text-slate-200 mb-2"><?php echo $lang == 'en' ? 'No entries yet!' : 'Henüz katılım yok!'; ?></h3>
                <p class="text-slate-500 dark:text-slate-400 mb-8 max-w-sm mx-auto"><?php echo $lang == 'en' ? 'Be the first to submit a photo this week and start the competition!' : 'Bu haftanın ilk fotoğrafını siz paylaşın ve yarışmayı başlatın!'; ?></p>
                <?php if(isset($_SESSION['user_id']) && !$user_already_submitted): ?>
                <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" 
                        class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-pink-500 to-violet-500 text-white font-black text-lg rounded-2xl shadow-lg shadow-pink-500/30 hover:shadow-xl hover:shadow-pink-500/40 hover:scale-105 active:scale-95 transition-all">
                    <i class="fas fa-cloud-upload-alt text-xl"></i>
                    <?php echo $lang == 'en' ? 'Upload First Photo' : 'İlk Fotoğrafı Yükle'; ?>
                </button>
                <?php elseif(!isset($_SESSION['user_id'])): ?>
                <a href="login" class="inline-flex items-center gap-2 px-8 py-4 bg-slate-800 dark:bg-white dark:text-slate-800 text-white font-bold text-lg rounded-2xl shadow-lg hover:scale-105 transition-all">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php echo $lang == 'en' ? 'Login to Participate' : 'Katılmak İçin Giriş Yap'; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Hall of Fame (Past Winners) -->
        <section class="mb-16">
            <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-8 flex items-center gap-3">
                <span class="bg-yellow-400 text-yellow-900 w-10 h-10 rounded-full flex items-center justify-center text-lg"><i class="fas fa-trophy"></i></span>
                <?php echo $lang == 'en' ? 'Hall of Fame' : 'Şöhretler Geçidi'; ?>
            </h2>
            
            <?php if(count($winners) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($winners as $winner): ?>
                <div class="group relative aspect-[4/3] rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all cursor-pointer" onclick="openLightbox('<?php echo htmlspecialchars($winner['image_path'] ?? '', ENT_QUOTES); ?>')">
                    <img src="<?php echo htmlspecialchars($winner['image_path'] ?? ''); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700" loading="lazy">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent p-6 pt-20">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($winner['avatar']); ?>" class="w-10 h-10 rounded-full border-2 border-yellow-400">
                            <div>
                                <h3 class="text-white font-bold truncate max-w-[200px]"><?php echo htmlspecialchars($winner['full_name']); ?></h3>
                                <p class="text-yellow-400 text-xs font-bold uppercase tracking-wider">
                                    <?php echo date('d M Y', strtotime($winner['week_of'])); ?> <?php echo $lang == 'en' ? 'Winner' : 'Kazananı'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="absolute top-4 right-4 bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-lg transform rotate-3 hover:rotate-0 transition-transform">
                        #1
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- WINNERS EMPTY STATE -->
            <div class="bg-slate-100 dark:bg-slate-800/50 rounded-3xl p-8 text-center border border-dashed border-slate-300 dark:border-slate-700">
                <p class="text-slate-500 font-medium">
                    <?php echo $lang == 'en' ? 'The first winner will appear here next Monday!' : 'İlk kazanan önümüzdeki Pazartesi burada görünecek!'; ?>
                </p>
            </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- FAB removed - replaced with inline upload card -->

    <!-- Upload Modal -->
    <div id="upload-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm overflow-y-auto" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="min-h-full flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="bg-white dark:bg-slate-900 w-full sm:max-w-md sm:rounded-3xl rounded-t-3xl p-5 pb-8 shadow-2xl relative">
                <!-- Header -->
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white"><?php echo $lang == 'en' ? 'Submit Entry' : 'Yarışmaya Katıl'; ?></h2>
                    <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="w-8 h-8 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="contest-upload-form">
                    <!-- File input (visually hidden but accessible) -->
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" id="contest-file-input" style="position:fixed;left:-9999px;opacity:0;" required>

                    <!-- STEP 1: Select Photo Button (big & obvious) -->
                    <div id="upload-step1" style="margin-bottom:16px;">
                        <button type="button" id="select-photo-btn" onclick="document.getElementById('contest-file-input').click()" style="width:100%;padding:24px 16px;background:linear-gradient(135deg,#f0f0ff,#fff0f5);border:2px dashed #d946ef;border-radius:16px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:8px;">
                            <div style="width:56px;height:56px;background:linear-gradient(135deg,#ec4899,#8b5cf6);border-radius:14px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-camera" style="color:#fff;font-size:24px;"></i>
                            </div>
                            <span style="font-size:15px;font-weight:800;color:#1e293b;"><?php echo $lang == 'en' ? 'Select Photo from Gallery' : 'Galeriden Fotoğraf Seç'; ?></span>
                            <span style="font-size:12px;color:#94a3b8;">JPG, PNG, WebP</span>
                        </button>
                    </div>

                    <!-- STEP 2: Preview (shown after photo selected) -->
                    <div id="upload-step2" style="display:none;margin-bottom:16px;">
                        <div style="position:relative;border-radius:16px;overflow:hidden;background:#f1f5f9;">
                            <img id="contest-image-preview" src="" alt="Preview" style="width:100%;max-height:220px;object-fit:cover;display:block;">
                            <button type="button" onclick="document.getElementById('contest-file-input').click()" style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,0.6);color:#fff;border:none;padding:6px 14px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">
                                <i class="fas fa-sync-alt" style="margin-right:4px;"></i><?php echo $lang == 'en' ? 'Change' : 'Değiştir'; ?>
                            </button>
                        </div>
                        <div id="file-name-display" style="font-size:11px;color:#64748b;margin-top:6px;text-align:center;">
                            <i class="fas fa-check-circle" style="color:#10b981;margin-right:4px;"></i><span id="file-name-text"></span>
                        </div>
                    </div>
                    
                    <textarea name="caption" rows="2" style="width:100%;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;font-size:14px;resize:none;outline:none;margin-bottom:12px;box-sizing:border-box;" placeholder="<?php echo $lang == 'en' ? 'Add a caption (optional)...' : 'Bir açıklama ekle (isteğe bağlı)...'; ?>"></textarea>
                    
                    <button type="submit" id="contest-submit-btn" disabled style="width:100%;padding:14px;background:linear-gradient(to right,#ec4899,#8b5cf6);color:#fff;font-weight:800;font-size:15px;border:none;border-radius:12px;cursor:pointer;opacity:0.5;">
                        <span id="submit-btn-text"><i class="fas fa-paper-plane" style="margin-right:8px;"></i><?php echo $lang == 'en' ? 'Submit Photo' : 'Fotoğrafı Gönder'; ?></span>
                        <span id="submit-btn-loading" style="display:none;"><i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i><?php echo $lang == 'en' ? 'Uploading...' : 'Resim Yükleniyor...'; ?></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Contest Upload Preview
    (function() {
        var fileInput = document.getElementById('contest-file-input');
        if (!fileInput) return;

        var step1 = document.getElementById('upload-step1');
        var step2 = document.getElementById('upload-step2');
        var preview = document.getElementById('contest-image-preview');
        var fileNameText = document.getElementById('file-name-text');
        var submitBtn = document.getElementById('contest-submit-btn');

        // Form submit → loading state
        var form = document.getElementById('contest-upload-form');
        if (form) {
            form.addEventListener('submit', function() {
                var btnText = document.getElementById('submit-btn-text');
                var btnLoading = document.getElementById('submit-btn-loading');
                if (btnText) btnText.style.display = 'none';
                if (btnLoading) btnLoading.style.display = 'inline';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.cursor = 'wait';
                }
            });
        }

        fileInput.addEventListener('change', function() {
            var file = this.files && this.files[0];
            if (!file) return;

            // Show file name
            if (fileNameText) {
                fileNameText.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + ' MB)';
            }

            // Enable submit
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }

            // Show preview, hide select button
            try {
                var objectUrl = URL.createObjectURL(file);
                preview.onload = function() {
                    step1.style.display = 'none';
                    step2.style.display = 'block';
                    URL.revokeObjectURL(objectUrl);
                };
                preview.src = objectUrl;
            } catch(e) {
                // Fallback: still switch steps
                step1.style.display = 'none';
                step2.style.display = 'block';
                step2.querySelector('img').style.display = 'none';
            }
        });
    })();
    </script>

    <!-- Lightbox Viewer -->
    <div class="lightbox" id="photo-lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <img id="lightbox-img" src="" alt="Full size photo">
    </div>

    <script>
        function openLightbox(src) {
            var lb = document.getElementById('photo-lightbox');
            var img = document.getElementById('lightbox-img');
            img.src = src;
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            var lb = document.getElementById('photo-lightbox');
            lb.classList.remove('active');
            document.body.style.overflow = '';
        }
        // ESC key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });

        function toggleLove(submissionId, btn) {
            <?php if(!isset($_SESSION['user_id'])): ?>
            window.location.href = 'login';
            return;
            <?php endif; ?>

            const photoCard = btn.closest('.photo-card');
            const ownerId = photoCard.getAttribute('data-submission-creator');
            const currentUserId = '<?php echo $_SESSION['user_id'] ?? 0; ?>';
            
            if (ownerId === currentUserId) {
                alert('<?php echo $lang == 'en' ? 'You cannot vote for your own masterpiece!' : 'Kendi eserine oy veremezsin!'; ?>');
                return;
            }

            // Optimistic UI update
            const isActive = btn.classList.contains('active');
            const countEl = photoCard.querySelector('.love-count');
            const heartIcon = btn.querySelector('i');
            let count = parseInt(countEl.innerText);
            
            if (isActive) {
                btn.classList.remove('active', 'bg-pink-50', 'dark:bg-pink-900/20', 'border-pink-200', 'dark:border-pink-800');
                btn.classList.add('bg-slate-50', 'dark:bg-slate-700', 'border-slate-200', 'dark:border-slate-600');
                if (heartIcon) { heartIcon.classList.remove('text-pink-500'); heartIcon.classList.add('text-slate-400'); }
                if (countEl) { countEl.classList.remove('text-pink-600', 'dark:text-pink-400'); countEl.classList.add('text-slate-600', 'dark:text-slate-300'); }
                count--;
            } else {
                btn.classList.add('active', 'bg-pink-50', 'dark:bg-pink-900/20', 'border-pink-200', 'dark:border-pink-800');
                btn.classList.remove('bg-slate-50', 'dark:bg-slate-700', 'border-slate-200', 'dark:border-slate-600');
                if (heartIcon) { heartIcon.classList.add('text-pink-500'); heartIcon.classList.remove('text-slate-400'); }
                if (countEl) { countEl.classList.add('text-pink-600', 'dark:text-pink-400'); countEl.classList.remove('text-slate-600', 'dark:text-slate-300'); }
                count++;
            }
            countEl.innerText = count;

            // Send Request to correct contest API
            const formData = new FormData();
            formData.append('submission_id', submissionId);
            
            fetch('api/vote_contest.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    // Rollback UI
                    if (isActive) { btn.classList.add('active'); countEl.innerText = count + 1; }
                    else { btn.classList.remove('active'); countEl.innerText = count - 1; }
                } else {
                    countEl.innerText = data.new_count;
                    if (data.action === 'added') btn.classList.add('active');
                    else btn.classList.remove('active');
                }
            })
            .catch(err => console.error('Vote error:', err));
        }
        
        // Countdown Timer to Next Sunday 23:59:59
        function updateCountdown() {
            const now = new Date();
            const currentDay = now.getDay(); // 0 is Sunday
            const daysUntilSunday = currentDay === 0 ? 0 : 7 - currentDay;
            
            const nextSunday = new Date(now);
            nextSunday.setDate(now.getDate() + daysUntilSunday);
            nextSunday.setHours(23, 59, 59, 999);
            
            const diff = nextSunday - now;
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            document.getElementById('countdown').innerText = `${days}d ${hours}h ${minutes}m`;
        }
        setInterval(updateCountdown, 60000);
        updateCountdown();
    </script>
</body>
</html>
