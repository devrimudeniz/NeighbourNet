<?php
require_once 'includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// Fetch active trail posts
$stmt = $pdo->prepare("
    SELECT tp.*, u.username, u.avatar, u.phone 
    FROM trail_posts tp 
    JOIN users u ON tp.user_id = u.id 
    WHERE tp.status = 'active' AND tp.planned_date >= CURDATE()
    ORDER BY tp.planned_date ASC
");
$stmt->execute();
$active_posts = $stmt->fetchAll();

// Helper to fix potential encoding issues in output
function repairTurkish($str) {
    $search = ['Ovac?k', 'ovac?k', 'Sar?belen', 'sar?belen', 'G?k?e?ren', 'g?k?e?ren', 'Bo?azc?k', 'bo?azc?k', 'Ka?', 'ka?'];
    $replace = ['Ovacık', 'ovacık', 'Sarıbelen', 'sarıbelen', 'Gökçeören', 'gökçeören', 'Boğazcık', 'boğazcık', 'Kaş', 'kaş'];
    return str_replace($search, $replace, $str);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['trail_mate']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-5xl">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <div class="inline-flex items-center gap-3 bg-emerald-100 dark:bg-emerald-900/30 px-6 py-3 rounded-full mb-4">
                    <i class="fas fa-hiking text-emerald-600 dark:text-emerald-400 text-2xl"></i>
                    <span class="text-emerald-600 dark:text-emerald-400 font-black uppercase tracking-wider text-sm">
                        <?php echo $t['lycian_way']; ?>
                    </span>
                </div>
                <h1 class="text-4xl md:text-5xl font-black text-emerald-700 dark:text-emerald-400">
                    Likya Yolu
                </h1>
                <p class="text-slate-600 dark:text-slate-400 mt-2 font-medium">
                    Find a hiking partner for your next adventure in Kalkan.
                </p>
            </div>
            
            <button onclick="openPostModal()" class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-black px-8 py-4 rounded-2xl shadow-xl shadow-emerald-500/20 hover:scale-105 transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <?php echo $t['partner_wanted']; ?>
            </button>
        </div>

        <!-- Active Alerts Grid -->
        <h2 class="text-2xl font-black mb-8 flex items-center gap-3">
             <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
             Active Alerts
        </h2>
        
        <div class="grid md:grid-cols-2 gap-6">
            <?php if (empty($active_posts)): ?>
                <div class="col-span-full py-20 text-center glass-card rounded-3xl">
                    <i class="fas fa-comment-slash text-4xl text-slate-300 mb-4"></i>
                    <p class="text-slate-500 font-bold">No partner alerts at the moment. Why not post one?</p>
                </div>
            <?php else: ?>
                <?php foreach ($active_posts as $post): ?>
                <div class="glass-card rounded-3xl p-6 border border-white/20 hover:shadow-2xl transition-all group relative" data-post-id="<?php echo $post['id']; ?>">
                    <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                        <button onclick="deletePost(<?php echo $post['id']; ?>, this)" class="absolute top-4 right-4 text-slate-400 hover:text-red-500 transition-colors p-2">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>

                    <div class="flex items-center gap-4 mb-4">
                        <?php echo renderAvatar($post['avatar'], ['size' => 'md', 'isBordered' => true]); ?>
                        <div>
                            <h4 class="font-black text-slate-900 dark:text-emerald-50"><?php echo htmlspecialchars($post['username']); ?></h4>
                            <p class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-widest">Planned for: <?php echo date('d M, Y', strtotime($post['planned_date'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl mb-4 border border-slate-100 dark:border-slate-700">
                        <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase block mb-1">Route Segment</span>
                        <h4 class="font-bold text-slate-900 dark:text-white mb-2"><?php echo htmlspecialchars(repairTurkish($post['trail_segment'])); ?></h4>
                        <p class="text-sm text-slate-600 dark:text-slate-100 leading-relaxed"><?php echo htmlspecialchars(repairTurkish($post['description'])); ?></p>
                    </div>
                    
                    <div class="flex items-center justify-between gap-4">
                        <button onclick="joinHike(<?php echo $post['id']; ?>, '<?php echo addslashes($post['username']); ?>', '<?php echo addslashes($post['phone']); ?>', '<?php echo addslashes(repairTurkish($post['trail_segment'])); ?>', '<?php echo $post['planned_date']; ?>')" 
                                class="flex-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-black py-3 rounded-xl hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center gap-2">
                            <i class="fab fa-whatsapp"></i> Join via WhatsApp
                        </button>
                        <a href="messages?user=<?php echo $post['user_id']; ?>" class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-center text-slate-500 hover:text-emerald-600 transition-colors">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Post Modal -->
    <div id="postModal" class="fixed inset-0 z-[1000] hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closePostModal()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-6">
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20">
                <div class="p-8 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-2xl font-black"><?php echo $t['partner_wanted']; ?></h3>
                </div>
                <form id="trailPostForm" class="p-8 space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-2">Trail Segment</label>
                        <select name="trail_segment" class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-emerald-500 transition-all font-bold text-sm">
                            <optgroup label="Western Section (Fethiye - Kaş)">
                                <option value="Ovacık - Faralya">Ovacık - Faralya</option>
                                <option value="Faralya - Kabak Koyu">Faralya - Kabak Koyu</option>
                                <option value="Kabak Koyu - Alınca">Kabak Koyu - Alınca</option>
                                <option value="Alınca - Yediburunlar (Gey)">Alınca - Yediburunlar (Gey)</option>
                                <option value="Yediburunlar (Gey) - Bel">Yediburunlar (Gey) - Bel</option>
                                <option value="Bel - Gavurağılı">Bel - Gavurağılı</option>
                                <option value="Gavurağılı - Patara">Gavurağılı - Patara</option>
                                <option value="Patara - Kalkan">Patara - Kalkan</option>
                                <option value="Kalkan - Sarıbelen">Kalkan - Sarıbelen</option>
                                <option value="Sarıbelen - Gökçeören">Sarıbelen - Gökçeören</option>
                                <option value="Gökçeören - Çukurbağ (Kaş)">Gökçeören - Çukurbağ (Kaş)</option>
                                <option value="Çukurbağ - Kaş (Antiphellos)">Çukurbağ - Kaş (Antiphellos)</option>
                            </optgroup>
                            <optgroup label="Central Section (Kaş - Finike)">
                                <option value="Kaş - Limanağzı">Kaş - Limanağzı</option>
                                <option value="Limanağzı - Boğazcık">Limanağzı - Boğazcık</option>
                                <option value="Boğazcık - Üçağız (Kekova)">Boğazcık - Üçağız (Kekova)</option>
                                <option value="Üçağız - Çayağzı">Üçağız - Çayağzı</option>
                                <option value="Çayağzı - Myra (Demre)">Çayağzı - Myra (Demre)</option>
                                <option value="Myra - Belören">Myra - Belören</option>
                                <option value="Belören - Alakilise">Belören - Alakilise</option>
                                <option value="Alakilise - Finike">Alakilise - Finike</option>
                            </optgroup>
                            <optgroup label="Eastern Section (Finike - Antalya)">
                                <option value="Finike - Mavikent">Finike - Mavikent</option>
                                <option value="Mavikent - Karaöz">Mavikent - Karaöz</option>
                                <option value="Karaöz - Adrasan (Gelidonya)">Karaöz - Adrasan (Gelidonya)</option>
                                <option value="Adrasan - Olympos">Adrasan - Olympos</option>
                                <option value="Olympos - Çıralı">Olympos - Çıralı</option>
                                <option value="Çıralı - Tekirova">Çıralı - Tekirova</option>
                                <option value="Tekirova - Phaselis">Tekirova - Phaselis</option>
                                <option value="Phaselis - Gedelme">Phaselis - Gedelme</option>
                                <option value="Gedelme - Göynük">Gedelme - Göynük</option>
                                <option value="Göynük - Hisarçandır">Göynük - Hisarçandır</option>
                                <option value="Hisarçandır - Geyikbayırı">Hisarçandır - Geyikbayırı</option>
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-2">Planned Date</label>
                        <input type="date" name="planned_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-emerald-500 transition-all font-bold text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-2">Description</label>
                        <textarea name="description" placeholder="Looking for someone to join... level of experience, pace, etc." class="w-full bg-slate-50 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-emerald-500 transition-all font-medium text-sm h-32"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-500/20 hover:scale-[1.02] transition-all">
                        Post Alert
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openPostModal() {
            document.getElementById('postModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closePostModal() {
            document.getElementById('postModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.getElementById('trailPostForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.action = 'create_post';

            const res = await fetch('api/trail_mate_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) location.reload();
        };

        async function deletePost(id, btn) {
            if (!confirm('Are you sure you want to delete this alert?')) return;

            const res = await fetch('api/trail_mate_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_post', post_id: id })
            });
            const result = await res.json();
            if (result.success) {
                // Find the card element and remove it with animation
                const card = btn.closest('[data-post-id]');
                if (card) {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 300);
                }
            } else {
                alert('Error: ' + result.message);
            }
        }

        function joinHike(id, username, phone, segment, date) {
            if (!phone || phone === '') {
                alert(username + ' has not provided a phone number. Please try direct messaging them on Kalkan Social.');
                return;
            }

            // Simple phone number cleaning (remove non-digits, but keep + if lead)
            let cleanedPhone = phone.replace(/[^\d+]/g, '');
            if (!cleanedPhone.startsWith('+') && cleanedPhone.length > 5) {
                // If no +, assume it might need one (but Turkish numbers usually start with 0 or 5)
                // We'll leave it as is if it looks complete
            }

            const message = `Hi ${username}! I saw your post on Kalkan Social about the Lycian Way hike: ${segment} on ${date}. I'd love to join you!`;
            const waUrl = `https://wa.me/${cleanedPhone}?text=${encodeURIComponent(message)}`;
            
            window.open(waUrl, '_blank');
        }
    </script>

</body>
</html>
