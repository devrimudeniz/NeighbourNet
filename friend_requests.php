<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
require_once 'includes/friendship-helper.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$pending_requests = getPendingRequests($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arkadaşlık İstekleri | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors duration-300">

    <?php include 'includes/header.php'; ?>

    <!-- Mobile Nav -->
    <div class="md:hidden fixed bottom-0 left-0 w-full bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 z-50 flex justify-around py-3 text-2xl text-slate-400">
        <a href="index" class="hover:text-pink-500"><i class="far fa-calendar-alt"></i></a>
        <a href="feed" class="hover:text-pink-500"><i class="fas fa-stream"></i></a>
        <a href="profile" class="hover:text-pink-500"><i class="far fa-user"></i></a>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 pt-24 max-w-2xl">
        <div class="flex items-center gap-3 mb-8">
            <a href="feed" class="text-slate-500 hover:text-pink-500 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-3xl font-bold">Arkadaşlık İstekleri</h1>
            <?php if (count($pending_requests) > 0): ?>
                <span class="bg-pink-500 text-white px-3 py-1 rounded-full text-sm font-bold"><?php echo count($pending_requests); ?></span>
            <?php endif; ?>
        </div>

        <?php if (count($pending_requests) == 0): ?>
            <!-- Empty State -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-12 text-center border border-slate-200 dark:border-slate-700">
                <div class="w-24 h-24 mx-auto mb-6 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-friends text-4xl text-slate-300 dark:text-slate-500"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-700 dark:text-slate-300 mb-2">Hiç Arkadaşlık İsteği Yok</h2>
                <p class="text-slate-500 dark:text-slate-400">Yeni arkadaşlık istekleri burada görünecek</p>
            </div>
        <?php else: ?>
            <!-- Requests List -->
            <div class="space-y-4">
                <?php foreach ($pending_requests as $request): 
                    $mutual_count = getMutualFriendsCount($_SESSION['user_id'], $request['id']);
                    $mutual_friends = getMutualFriends($_SESSION['user_id'], $request['id'], 3);
                ?>
                <div id="request-<?php echo $request['id']; ?>" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all">
                    <div class="flex gap-4">
                        <!-- Avatar -->
                        <a href="profile?uid=<?php echo $request['id']; ?>" class="flex-shrink-0">
                            <img src="<?php echo $request['avatar']; ?>" class="w-16 h-16 rounded-full object-cover border-2 border-pink-200 dark:border-pink-800">
                        </a>

                        <!-- Info -->
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <a href="profile?uid=<?php echo $request['id']; ?>" class="font-bold text-lg hover:text-pink-500 transition-colors">
                                    <?php echo htmlspecialchars($request['full_name']); ?>
                                </a>
                                <?php if($request['badge'] == 'founder') echo '<i class="fas fa-shield-alt text-pink-500 text-sm" title="Kurucu"></i>'; ?>
                                <?php if($request['badge'] == 'moderator') echo '<i class="fas fa-gavel text-blue-500 text-sm" title="Moderatör"></i>'; ?>
                                <?php if($request['badge'] == 'business') echo '<i class="fas fa-check-circle text-green-500 text-sm" title="İşletme"></i>'; ?>
                            </div>
                            
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">@<?php echo htmlspecialchars($request['username']); ?></p>

                            <!-- Mutual Friends -->
                            <?php if ($mutual_count > 0): ?>
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="flex -space-x-2">
                                        <?php foreach ($mutual_friends as $mf): ?>
                                            <img src="<?php echo $mf['avatar']; ?>" class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-800 object-cover" title="<?php echo htmlspecialchars($mf['full_name']); ?>">
                                        <?php endforeach; ?>
                                    </div>
                                    <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                                        <?php echo $mutual_count; ?> ortak arkadaş
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <button onclick="respondToRequest(<?php echo $request['id']; ?>, 'accept')" 
                                        class="flex-1 bg-gradient-to-r from-pink-500 to-violet-600 text-white font-bold py-2 px-4 rounded-full hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                                    <i class="fas fa-check mr-1"></i> Kabul Et
                                </button>
                                <button onclick="respondToRequest(<?php echo $request['id']; ?>, 'decline')" 
                                        class="flex-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold py-2 px-4 rounded-full hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                    <i class="fas fa-times mr-1"></i> Reddet
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        async function respondToRequest(userId, action) {
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('action', action);

                const response = await fetch('api/friend_response.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // Remove the request card with animation
                    const card = document.getElementById('request-' + userId);
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if list is empty now
                        const remainingRequests = document.querySelectorAll('[id^="request-"]');
                        if (remainingRequests.length === 0) {
                            location.reload();
                        }
                    }, 300);
                    
                    if (action === 'accept') {
                        alert('✨ Artık arkadaşsınız!');
                    }
                } else {
                    alert(data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            }
        }
    </script>
</body>
</html>
