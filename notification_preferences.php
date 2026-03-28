<?php
/**
 * Notification Preferences - Email & Push per channel (Pati Safe, Lost & Found, Events)
 * Linked from Settings modal (Ayarlar → Bildirim tercihleri)
 */
session_start();
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login?redirect=notification_preferences');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$lang = $lang ?? 'tr';

// Ensure subscriptions has notify_email, notify_push
try {
    $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_email'");
    if ($chk->rowCount() === 0) {
        $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_email TINYINT(1) NOT NULL DEFAULT 1 AFTER service");
    }
    $chk = $pdo->query("SHOW COLUMNS FROM subscriptions LIKE 'notify_push'");
    if ($chk->rowCount() === 0) {
        $pdo->exec("ALTER TABLE subscriptions ADD COLUMN notify_push TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_email");
    }
} catch (PDOException $e) { /* ignore */ }

// Load current preferences for pati_safe, lost_found, events
$services = ['pati_safe' => 'pati_safe', 'lost_found' => 'lost_found', 'events' => 'events'];
$prefs = [];
foreach ($services as $key => $service) {
    $stmt = $pdo->prepare("SELECT id, notify_email, notify_push FROM subscriptions WHERE user_id = ? AND service = ?");
    $stmt->execute([$user_id, $service]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $prefs[$key] = [
        'subscribed' => (bool)$row,
        'notify_email' => $row ? (int)$row['notify_email'] : 1,
        'notify_push' => $row ? (int)$row['notify_push'] : 1,
    ];
}

$titles = [
    'pati_safe' => $lang == 'en' ? 'Lost & Found Pets' : 'Kayıp Hayvan (Pati Safe)',
    'lost_found' => $lang == 'en' ? 'Lost & Found Items' : 'Kayıp Eşya',
    'events' => $lang == 'en' ? 'New Events' : 'Yeni Etkinlikler',
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Notification preferences' : 'Bildirim tercihleri'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white min-h-screen pb-24">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 max-w-lg">
        <div class="mb-6">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-sm font-bold">
                <?php echo heroicon('arrow_left', 'w-5 h-5'); ?>
                <?php echo $lang == 'en' ? 'Back' : 'Geri'; ?>
            </a>
        </div>
        <h1 class="text-2xl font-black text-slate-800 dark:text-white mb-2 flex items-center gap-2">
            <?php echo heroicon('bell', 'w-8 h-8 text-violet-500'); ?>
            <?php echo $lang == 'en' ? 'Notification preferences' : 'Bildirim tercihleri'; ?>
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-8">
            <?php echo $lang == 'en' ? 'Choose how you want to be notified for each service (email and/or push).' : 'Her hizmet için e-posta ve/veya push bildirimlerini seçin.'; ?>
        </p>

        <div class="space-y-4">
            <?php foreach ($prefs as $key => $p): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm" data-service="<?php echo $key; ?>">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-slate-800 dark:text-white">
                        <?php echo $key === 'pati_safe' ? '🐾' : ($key === 'lost_found' ? '🔑' : '📅'); ?>
                        <?php echo htmlspecialchars($titles[$key]); ?>
                    </h2>
                    <button type="button" class="toggle-subscribe relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 <?php echo $p['subscribed'] ? 'bg-violet-600' : 'bg-slate-300 dark:bg-slate-600'; ?>" role="switch" aria-checked="<?php echo $p['subscribed'] ? 'true' : 'false'; ?>" data-subscribed="<?php echo $p['subscribed'] ? '1' : '0'; ?>">
                        <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 ease-out <?php echo $p['subscribed'] ? 'translate-x-5' : 'translate-x-0.5'; ?>"></span>
                    </button>
                </div>
                <div class="flex flex-wrap gap-4 <?php echo !$p['subscribed'] ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="notify-email rounded border-slate-300 text-violet-600 focus:ring-violet-500" <?php echo $p['notify_email'] ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo heroicon('envelope', 'w-4 h-4 text-slate-500'); ?></span>
                        <span class="text-sm text-slate-600 dark:text-slate-400"><?php echo $lang == 'en' ? 'Email' : 'E-posta'; ?></span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="notify-push rounded border-slate-300 text-violet-600 focus:ring-violet-500" <?php echo $p['notify_push'] ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo heroicon('bolt', 'w-4 h-4 text-slate-500'); ?></span>
                        <span class="text-sm text-slate-600 dark:text-slate-400"><?php echo $lang == 'en' ? 'Push' : 'Push'; ?></span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500 mt-6">
            <?php echo $lang == 'en' ? 'Push notifications require that you have allowed them in your browser or PWA.' : 'Push bildirimleri tarayıcı veya PWA\'da izin vermenizi gerektirir.'; ?>
        </p>
    </main>

    <script>
    (function() {
        const csrf = '<?php echo isset($_SESSION["csrf_token"]) ? addslashes($_SESSION["csrf_token"]) : ""; ?>';
        function savePrefs(service, subscribed, notifyEmail, notifyPush) {
            const form = new FormData();
            form.append('service', service);
            form.append('subscribed', subscribed ? '1' : '0');
            form.append('notify_email', notifyEmail ? '1' : '0');
            form.append('notify_push', notifyPush ? '1' : '0');
            if (csrf) form.append('csrf_token', csrf);
            return fetch('api/save_notification_prefs.php', { method: 'POST', body: form }).then(r => r.json());
        }

        document.querySelectorAll('.toggle-subscribe').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const card = this.closest('[data-service]');
                const service = card.dataset.service;
                const was = this.dataset.subscribed === '1';
                const now = !was;
                this.dataset.subscribed = now ? '1' : '0';
                this.setAttribute('aria-checked', now ? 'true' : 'false');
                this.classList.toggle('bg-violet-600', now);
                this.classList.toggle('bg-slate-300', !now);
                this.classList.toggle('dark:bg-slate-600', !now);
                this.querySelector('span').classList.toggle('translate-x-5', now);
                this.querySelector('span').classList.toggle('translate-x-0.5', !now);
                card.querySelector('.flex.flex-wrap').classList.toggle('opacity-50', !now);
                card.querySelector('.flex.flex-wrap').classList.toggle('pointer-events-none', !now);
                const email = now ? card.querySelector('.notify-email').checked : false;
                const push = now ? card.querySelector('.notify-push').checked : false;
                savePrefs(service, now, email, push).then(function(data) {
                    if (data.status !== 'success') console.warn(data);
                });
            });
        });

        document.querySelectorAll('.notify-email, .notify-push').forEach(function(input) {
            input.addEventListener('change', function() {
                const card = this.closest('[data-service]');
                const service = card.dataset.service;
                const subscribed = card.querySelector('.toggle-subscribe').dataset.subscribed === '1';
                const email = card.querySelector('.notify-email').checked;
                const push = card.querySelector('.notify-push').checked;
                savePrefs(service, subscribed, email, push).then(function(data) {
                    if (data.status !== 'success') console.warn(data);
                });
            });
        });
    })();
    </script>
    <?php include 'includes/bottom_nav.php'; ?>
</body>
</html>
