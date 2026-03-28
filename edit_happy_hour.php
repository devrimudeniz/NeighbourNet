<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    header('Location: happy_hour');
    exit;
}

// Fetch the event
$stmt = $pdo->prepare("SELECT * FROM happy_hours WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: happy_hour');
    exit;
}

// Check ownership
if ($event['user_id'] != $_SESSION['user_id'] && ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: happy_hour');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Edit Event' : 'Etkinliği Düzenle'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-28 max-w-lg">
        
        <!-- Header -->
        <div class="flex items-center gap-4 mb-8">
            <a href="happy_hour" class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-center text-slate-500 hover:text-pink-500 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-800 dark:text-white">
                    <?php echo $lang == 'en' ? 'Edit Event' : 'Etkinliği Düzenle'; ?>
                </h1>
                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($event['venue_name']); ?></p>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-lg border border-slate-100 dark:border-slate-700">
            <form id="editEventForm" class="space-y-5">
                <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                
                <!-- Type Display -->
                <div class="flex items-center gap-3 p-4 bg-<?php echo $event['event_type'] == 'live_music' ? 'violet' : 'pink'; ?>-50 dark:bg-<?php echo $event['event_type'] == 'live_music' ? 'violet' : 'pink'; ?>-900/20 rounded-2xl">
                    <span class="text-2xl"><?php echo $event['event_type'] == 'live_music' ? '🎸' : '🍹'; ?></span>
                    <span class="font-bold text-<?php echo $event['event_type'] == 'live_music' ? 'violet' : 'pink'; ?>-600 dark:text-<?php echo $event['event_type'] == 'live_music' ? 'violet' : 'pink'; ?>-400">
                        <?php echo $event['event_type'] == 'live_music' ? ($lang == 'en' ? 'Live Music' : 'Canlı Müzik') : 'Happy Hour'; ?>
                    </span>
                </div>
                <input type="hidden" name="event_type" value="<?php echo $event['event_type']; ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'Venue Name' : 'Mekan Adı'; ?></label>
                    <input type="text" name="venue_name" required value="<?php echo htmlspecialchars($event['venue_name']); ?>" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all font-bold">
                </div>

                <?php if($event['event_type'] == 'live_music'): ?>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'Performer / Band Name' : 'Sanatçı / Grup Adı'; ?></label>
                    <input type="text" name="performer_name" value="<?php echo htmlspecialchars($event['performer_name'] ?? ''); ?>" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all font-bold text-lg text-violet-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'Music Genre' : 'Müzik Türü'; ?></label>
                    <select name="music_genre" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all font-bold">
                        <option value="">Select Genre...</option>
                        <option value="Jazz" <?php echo ($event['music_genre'] ?? '') == 'Jazz' ? 'selected' : ''; ?>>Jazz 🎷</option>
                        <option value="Acoustic" <?php echo ($event['music_genre'] ?? '') == 'Acoustic' ? 'selected' : ''; ?>>Acoustic 🎸</option>
                        <option value="Rock" <?php echo ($event['music_genre'] ?? '') == 'Rock' ? 'selected' : ''; ?>>Rock 🤘</option>
                        <option value="Pop" <?php echo ($event['music_genre'] ?? '') == 'Pop' ? 'selected' : ''; ?>>Pop 🎤</option>
                        <option value="Turkish Pop" <?php echo ($event['music_genre'] ?? '') == 'Turkish Pop' ? 'selected' : ''; ?>>Turkish Pop 🇹🇷</option>
                        <option value="DJ Performance" <?php echo ($event['music_genre'] ?? '') == 'DJ Performance' ? 'selected' : ''; ?>>DJ Performance 🎧</option>
                        <option value="Traditional" <?php echo ($event['music_genre'] ?? '') == 'Traditional' ? 'selected' : ''; ?>>Traditional / Fasıl 🎻</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'Start Time' : 'Başlangıç'; ?></label>
                        <input type="time" name="start_time" required value="<?php echo date('H:i', strtotime($event['start_time'])); ?>" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'End Time' : 'Bitiş'; ?></label>
                        <input type="time" name="end_time" required value="<?php echo date('H:i', strtotime($event['end_time'])); ?>" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'Description' : 'Açıklama'; ?></label>
                    <textarea name="description" rows="3" class="w-full p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent focus:border-violet-500 outline-none transition-all font-medium"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                </div>

                <?php if($event['photo_url']): ?>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'Current Photo' : 'Mevcut Fotoğraf'; ?></label>
                    <img src="<?php echo $event['photo_url']; ?>" class="w-full h-40 object-cover rounded-2xl mb-2">
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2"><?php echo $lang == 'en' ? 'New Photo (Optional)' : 'Yeni Fotoğraf (İsteğe bağlı)'; ?></label>
                    <input type="file" name="photo" accept="image/*" class="w-full p-3 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 transition-colors cursor-pointer">
                </div>

                <div class="flex gap-3 pt-4">
                    <a href="happy_hour" class="flex-1 py-4 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-center hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </a>
                    <button type="submit" class="flex-1 py-4 rounded-2xl bg-gradient-to-r from-violet-600 to-pink-500 text-white font-black shadow-lg shadow-pink-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                        <?php echo $lang == 'en' ? 'Save Changes' : 'Değişiklikleri Kaydet'; ?> ✓
                    </button>
                </div>
            </form>
        </div>

    </main>

    <script>
    document.getElementById('editEventForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const res = await fetch('api/update_happy_hour.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(data.status === 'success') {
                // Show success with button change then redirect
                btn.innerHTML = '✓ <?php echo $lang == 'en' ? 'Saved!' : 'Kaydedildi!'; ?>';
                btn.classList.remove('from-violet-600', 'to-pink-500');
                btn.classList.add('bg-emerald-500');
                
                // Try toast, fallback to simple visual feedback
                if(typeof showGlobalToast === 'function') {
                    showGlobalToast('<?php echo $lang == 'en' ? 'Event updated!' : 'Etkinlik güncellendi!'; ?>', 'success');
                }
                
                setTimeout(() => window.location.href = 'happy_hour', 1200);
            } else {
                alert(data.message || 'Error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch(err) {
            console.error(err);
            alert('<?php echo $lang == 'en' ? 'An error occurred' : 'Bir hata oluştu'; ?>');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
    </script>
</body>
</html>
