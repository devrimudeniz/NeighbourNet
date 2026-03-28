<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
session_start();

if (!isset($_GET['id'])) {
    header("Location: marketplace");
    exit();
}

$id = (int)$_GET['id'];

// Fetch Listing Details
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.full_name, u.avatar, u.badge 
    FROM marketplace_listings m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.id = ?
");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    header("Location: marketplace");
    exit();
}

// Fetch Images
$stmt_img = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, id ASC");
$stmt_img->execute([$id]);
$images = $stmt_img->fetchAll();

// If no images in separate table, fallback to main image if exists
if (empty($images) && !empty($listing['image_url'])) {
    $images[] = ['image_url' => $listing['image_url']];
}

$is_owner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $listing['user_id'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($listing['title']); ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-32 max-w-5xl">
        
        <a href="marketplace" class="inline-flex items-center text-slate-500 hover:text-slate-900 dark:hover:text-white mb-6 font-bold transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> <?php echo $t['marketplace']; ?>
        </a>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Gallery Section -->
            <div class="space-y-4">
                <?php if(!empty($images)): ?>
                    <!-- Main Image -->
                    <div class="aspect-w-4 aspect-h-3 rounded-3xl overflow-hidden shadow-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        <img id="mainImage" src="<?php echo htmlspecialchars($images[0]['image_url']); ?>" class="w-full h-full object-cover" loading="lazy">
                    </div>
                    
                    <!-- Thumbnails -->
                    <?php if(count($images) > 1): ?>
                    <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
                        <?php foreach($images as $img): ?>
                        <button onclick="document.getElementById('mainImage').src='<?php echo htmlspecialchars($img['image_url']); ?>'" class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-transparent hover:border-green-500 transition-all shadow-sm">
                            <img src="<?php echo htmlspecialchars($img['image_url']); ?>" class="w-full h-full object-cover" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="aspect-w-4 aspect-h-3 rounded-3xl bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                        <i class="fas fa-image text-4xl"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info Section -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl border border-slate-200 dark:border-slate-700 h-fit">
                <div class="flex justify-between items-start mb-4">
                    <span class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                         <?php 
                         if ($listing['category'] == 'item') echo $t['item_category'] ?? 'İkinci El';
                         elseif ($listing['category'] == 'service') echo $t['service_category'] ?? 'Hizmet';
                         else echo $t['free_category'] ?? 'Bedava';
                         ?>
                    </span>
                    <div class="text-right">
                        <div class="text-xs text-slate-400 mb-1"><i class="far fa-calendar-check mr-1"></i><?php echo $t['listing_start_date'] ?? 'Yayınlanma'; ?>: <?php echo date('d.m.Y', strtotime($listing['created_at'])); ?></div>
                        <div class="text-xs text-red-500 font-bold"><i class="far fa-clock mr-1"></i><?php echo $t['listing_end_date'] ?? 'Bitiş'; ?>: <?php echo date('d.m.Y', strtotime($listing['expires_at'])); ?></div>
                    </div>
                </div>

                <h1 class="text-3xl font-extrabold mb-4 leading-tight"><?php echo htmlspecialchars($listing['title']); ?></h1>
                
                <?php if($listing['price'] > 0): ?>
                    <div class="text-4xl font-bold text-green-600 dark:text-green-500 mb-6">
                        <?php echo number_format($listing['price'], 0); ?> 
                        <span class="text-2xl"><?php echo $listing['currency']; ?></span>
                    </div>
                <?php else: ?>
                    <div class="text-4xl font-bold text-pink-500 mb-6">
                        ÜCRETSİZ
                    </div>
                <?php endif; ?>

                <div class="prose dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 mb-8 leading-relaxed text-lg">
                    <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                    <div class="flex items-center gap-4 mb-6">
                        <img src="<?php echo $listing['avatar']; ?>" class="w-14 h-14 rounded-full object-cover bg-slate-200" loading="lazy">
                        <div>
                            <div class="font-bold text-lg"><?php echo htmlspecialchars($listing['full_name']); ?></div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">@<?php echo htmlspecialchars($listing['username']); ?></div>
                        </div>
                    </div>

                    <?php if(!$is_owner): ?>
                        <div class="flex gap-3">
                            <a href="messages?action=new&to=<?php echo $listing['user_id']; ?>&item=<?php echo $listing['id']; ?>" class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white py-4 rounded-xl font-bold text-center shadow-lg shadow-green-500/20 transition-all flex items-center justify-center gap-2">
                                <i class="far fa-envelope"></i> <?php echo $t['message_seller'] ?? 'Satıcıya Mesaj At'; ?>
                            </a>
                            <button class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-4 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors" title="<?php echo $t['report_listing'] ?? 'Şikayet Et'; ?>">
                                <i class="fas fa-flag"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Owner Actions -->
                        <div class="space-y-3">
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-xl text-center text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-200 dark:border-emerald-800">
                                <i class="fas fa-check-circle mr-2"></i><?php echo $t['this_is_your_listing'] ?? 'Bu sizin ilanınız'; ?>
                            </div>
                            <div class="flex gap-3">
                                <a href="edit_listing?id=<?php echo $listing['id']; ?>" class="flex-1 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white py-3 rounded-xl font-bold text-center shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-edit"></i> <?php echo $t['edit_listing'] ?? 'İlanı Düzenle'; ?>
                                </a>
                                <button onclick="deleteListing(<?php echo $listing['id']; ?>)" class="bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 px-4 rounded-xl hover:bg-red-500 hover:text-white transition-all font-bold" title="<?php echo $t['delete_listing'] ?? 'İlanı Sil'; ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <!-- Questions & Answers Section -->
        <div class="mt-12 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-3xl p-8 border border-white/20 dark:border-slate-800/50">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                <i class="fas fa-question-circle text-blue-500"></i> <?php echo $t['listing_questions'] ?? 'Sorular & Cevaplar'; ?>
            </h2>

            <?php
            // Create table if not exists and fetch questions
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS marketplace_questions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        listing_id INT NOT NULL,
                        user_id INT NOT NULL,
                        question TEXT NOT NULL,
                        answer TEXT DEFAULT NULL,
                        answered_at DATETIME DEFAULT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_listing (listing_id),
                        INDEX idx_user (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            } catch (Exception $e) {}

            $q_stmt = $pdo->prepare("
                SELECT mq.*, u.full_name, u.avatar 
                FROM marketplace_questions mq 
                JOIN users u ON mq.user_id = u.id 
                WHERE mq.listing_id = ? 
                ORDER BY mq.created_at DESC
            ");
            $q_stmt->execute([$id]);
            $questions = $q_stmt->fetchAll();
            ?>

            <!-- Questions List -->
            <?php if(empty($questions)): ?>
                <div class="text-center py-8 text-slate-500 italic">
                    <i class="far fa-comments text-4xl mb-3 opacity-50"></i>
                    <p><?php echo $t['no_questions_yet'] ?? 'Henüz soru sorulmamış. İlk soruyu siz sorun!'; ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-6 mb-8" id="questions-list">
                    <?php foreach($questions as $q): ?>
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                        <!-- Question -->
                        <div class="flex items-start gap-4 mb-4">
                            <img src="<?php echo $q['avatar']; ?>" class="w-10 h-10 rounded-full object-cover flex-shrink-0" loading="lazy">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-sm"><?php echo htmlspecialchars($q['full_name']); ?></span>
                                    <span class="text-xs text-slate-400"><?php echo date('d.m.Y H:i', strtotime($q['created_at'])); ?></span>
                                </div>
                                <p class="text-slate-700 dark:text-slate-300"><?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
                            </div>
                        </div>
                        
                        <!-- Answer -->
                        <?php if($q['answer']): ?>
                        <div class="ml-14 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border-l-4 border-emerald-500">
                            <div class="flex items-center gap-2 mb-2">
                                <img src="<?php echo $listing['avatar']; ?>" class="w-6 h-6 rounded-full object-cover">
                                <span class="font-bold text-sm text-emerald-700 dark:text-emerald-400"><?php echo htmlspecialchars($listing['full_name']); ?></span>
                                <span class="text-xs text-emerald-600 dark:text-emerald-500 ml-auto">
                                    <i class="fas fa-check-circle mr-1"></i><?php echo $t['answered'] ?? 'Cevaplandı'; ?>
                                </span>
                            </div>
                            <p class="text-slate-700 dark:text-slate-300 text-sm"><?php echo nl2br(htmlspecialchars($q['answer'])); ?></p>
                        </div>
                        <?php elseif($is_owner): ?>
                        <!-- Answer Form (for listing owner) -->
                        <div class="ml-14">
                            <form id="answer-form-<?php echo $q['id']; ?>" class="flex gap-2">
                                <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                <input type="text" name="answer" placeholder="<?php echo $t['your_answer'] ?? 'Cevabınız...'; ?>" 
                                       class="flex-1 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm transition-colors">
                                    <i class="fas fa-reply mr-1"></i><?php echo $t['answer'] ?? 'Cevapla'; ?>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="ml-14 text-xs text-amber-600 dark:text-amber-400">
                            <i class="fas fa-clock mr-1"></i><?php echo $t['waiting_answer'] ?? 'Cevap bekleniyor'; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Ask Question Form -->
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['user_id']): ?>
            <div class="border-t border-slate-200 dark:border-slate-700 pt-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-comment-medical text-blue-500"></i>
                    <?php echo $t['ask_question'] ?? 'Soru Sor'; ?>
                </h3>
                <form id="question-form" class="space-y-4">
                    <input type="hidden" name="listing_id" value="<?php echo $id; ?>">
                    <textarea name="question" id="question-input" rows="3" 
                              class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                              placeholder="<?php echo $t['your_question'] ?? 'Sorunuz...'; ?>"></textarea>
                    <button type="submit" id="submit-question-btn" 
                            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $t['submit_question'] ?? 'Gönder'; ?>
                    </button>
                </form>
            </div>
            <?php elseif(!isset($_SESSION['user_id'])): ?>
            <div class="border-t border-slate-200 dark:border-slate-700 pt-6 text-center">
                <a href="login" class="text-blue-500 hover:underline font-bold">
                    <i class="fas fa-sign-in-alt mr-1"></i><?php echo $t['login'] ?? 'Giriş yap'; ?>
                </a>
                <span class="text-slate-500"> - <?php echo $lang == 'tr' ? 'soru sormak için' : 'to ask a question'; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Seller Reviews Section -->
        <div class="mt-12 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-3xl p-8 border border-white/20 dark:border-slate-800/50">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                <i class="fas fa-star text-yellow-500"></i> Satıcı Değerlendirmeleri
            </h2>

            <?php
            // Get Reviews
            $r_stmt = $pdo->prepare("SELECT mr.*, u.full_name, u.avatar FROM marketplace_reviews mr JOIN users u ON mr.reviewer_id = u.id WHERE mr.seller_id = ? ORDER BY mr.created_at DESC LIMIT 5");
            $r_stmt->execute([$listing['user_id']]);
            $reviews = $r_stmt->fetchAll();
            ?>

            <?php if(empty($reviews)): ?>
                <div class="text-center py-8 text-slate-500 italic">
                    Bu satıcı için henüz değerlendirme yapılmamış.
                </div>
            <?php else: ?>
                <div class="grid gap-6">
                    <?php foreach($reviews as $rev): ?>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-4 mb-3">
                            <img src="<?php echo $rev['avatar']; ?>" class="w-10 h-10 rounded-full object-cover" loading="lazy">
                            <div>
                                <div class="font-bold text-sm"><?php echo htmlspecialchars($rev['full_name']); ?></div>
                                <div class="flex text-yellow-500 text-xs">
                                    <?php for($i=0; $i<5; $i++) echo $i < $rev['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                </div>
                            </div>
                            <div class="ml-auto text-xs text-slate-400">
                                <?php echo date('d.m.Y', strtotime($rev['created_at'])); ?>
                            </div>
                        </div>
                        <p class="text-slate-600 dark:text-slate-300 text-sm italic">
                            "<?php echo htmlspecialchars($rev['comment']); ?>"
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['user_id']): ?>
            <!-- Review Button (Triggers Modal) -->
            <div class="mt-8 text-center">
                <button onclick="document.getElementById('reviewModal').classList.remove('hidden')" class="px-6 py-3 bg-slate-900 dark:bg-slate-700 text-white rounded-xl font-bold hover:scale-105 transition-transform">
                    <i class="fas fa-pen mr-2"></i> Satıcıyı Değerlendir
                </button>
            </div>

            <!-- Review Modal -->
            <div id="reviewModal" class="fixed inset-0 z-50 hidden">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('reviewModal').classList.add('hidden')"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-slate-900 w-full max-w-md p-8 rounded-3xl shadow-2xl">
                    <h3 class="text-xl font-bold mb-4">Satıcıyı Puanla</h3>
                    <form action="api/submit_review.php" method="POST">
                        <input type="hidden" name="listing_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="seller_id" value="<?php echo $listing['user_id']; ?>">
                        
                        <div class="flex justify-center gap-4 mb-6 text-3xl text-slate-300">
                            <?php for($i=1; $i<=5; $i++): ?>
                            <i class="fas fa-star cursor-pointer hover:text-yellow-500 peer peer-hover:text-yellow-500 transition-colors" onclick="document.getElementById('ratingInput').value=<?php echo $i; ?>; updateStars(<?php echo $i; ?>)"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required>

                        <textarea name="comment" rows="3" class="w-full bg-slate-100 dark:bg-slate-800 rounded-xl p-4 mb-4" placeholder="Deneyimini paylaş..."></textarea>
                        
                        <button type="submit" class="w-full bg-pink-600 text-white py-3 rounded-xl font-bold">Gönder</button>
                    </form>
                </div>
            </div>
            
            <script>
                function updateStars(rating) {
                    const stars = document.querySelectorAll('#reviewModal .fa-star');
                    stars.forEach((star, index) => {
                        star.classList.toggle('text-yellow-500', index < rating);
                        star.classList.toggle('text-slate-300', index >= rating);
                    });
                }
            </script>
            <?php endif; ?>

        </div>

    </main>

</body>

<script>
// Question Form Submission
const questionForm = document.getElementById('question-form');
if (questionForm) {
    questionForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = document.getElementById('submit-question-btn');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
        btn.disabled = true;
        
        try {
            const response = await fetch('api/listing_question.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                // Add new question to DOM
                const q = data.question;
                const questionHTML = `
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm animate-fade-in">
                        <div class="flex items-start gap-4 mb-4">
                            <img src="${q.avatar}" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-bold text-sm">${q.full_name}</span>
                                    <span class="text-xs text-slate-400"><?php echo $lang == 'tr' ? 'Az önce' : 'Just now'; ?></span>
                                </div>
                                <p class="text-slate-700 dark:text-slate-300">${q.question}</p>
                            </div>
                        </div>
                        <div class="ml-14 text-xs text-amber-600 dark:text-amber-400">
                            <i class="fas fa-clock mr-1"></i><?php echo $t['waiting_answer'] ?? 'Cevap bekleniyor'; ?>
                        </div>
                    </div>
                `;
                
                const questionsList = document.getElementById('questions-list');
                if (questionsList) {
                    questionsList.insertAdjacentHTML('afterbegin', questionHTML);
                } else {
                    // If no questions existed before, create the list
                    location.reload();
                }
                
                document.getElementById('question-input').value = '';
                
                // Show success message
                showNotification('<?php echo $t["question_submitted"] ?? "Sorunuz gönderildi!"; ?>', 'success');
            } else {
                showNotification(data.error || 'Bir hata oluştu', 'error');
            }
        } catch (error) {
            showNotification('Bağlantı hatası', 'error');
        }
        
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Answer Form Submissions
document.querySelectorAll('[id^="answer-form-"]').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        try {
            const response = await fetch('api/listing_answer.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                // Replace form with answer display
                const answer = data.answer;
                const answerHTML = `
                    <div class="ml-14 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border-l-4 border-emerald-500 animate-fade-in">
                        <div class="flex items-center gap-2 mb-2">
                            <img src="<?php echo $listing['avatar']; ?>" class="w-6 h-6 rounded-full object-cover">
                            <span class="font-bold text-sm text-emerald-700 dark:text-emerald-400"><?php echo htmlspecialchars($listing['full_name']); ?></span>
                            <span class="text-xs text-emerald-600 dark:text-emerald-500 ml-auto">
                                <i class="fas fa-check-circle mr-1"></i><?php echo $t['answered'] ?? 'Cevaplandı'; ?>
                            </span>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 text-sm">${answer}</p>
                    </div>
                `;
                
                this.parentElement.innerHTML = answerHTML;
                showNotification('Cevabınız kaydedildi!', 'success');
            } else {
                showNotification(data.error || 'Bir hata oluştu', 'error');
            }
        } catch (error) {
            showNotification('Bağlantı hatası', 'error');
        }
        
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});

// Notification helper
function showNotification(message, type = 'success') {
    const notif = document.createElement('div');
    notif.className = `fixed top-20 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl font-bold text-sm animate-slide-in ${type === 'success' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'}`;
    notif.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>${message}`;
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.classList.add('animate-fade-out');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Delete Listing Function
function deleteListing(listingId) {
    const confirmMsg = '<?php echo $t["confirm_delete_listing"] ?? "Bu ilanı silmek istediğinizden emin misiniz?"; ?>';
    if (confirm(confirmMsg)) {
        fetch('api/delete_listing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'listing_id=' + listingId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('<?php echo $t["listing_deleted"] ?? "İlan silindi."; ?>', 'success');
                setTimeout(() => window.location.href = 'marketplace', 1000);
            } else {
                showNotification(data.error || 'Bir hata oluştu', 'error');
            }
        })
        .catch(() => showNotification('Bağlantı hatası', 'error'));
    }
}
</script>

<style>
@keyframes slide-in {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fade-out {
    from { opacity: 1; }
    to { opacity: 0; }
}
.animate-slide-in { animation: slide-in 0.3s ease-out; }
.animate-fade-in { animation: fade-in 0.3s ease-out; }
.animate-fade-out { animation: fade-out 0.3s ease-out; }
</style>

</html>
