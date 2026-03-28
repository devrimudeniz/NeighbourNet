<?php
require_once '../includes/db.php';
require_once '../includes/site_settings.php';
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Lutfen tum alanlari doldurun.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, venue_name, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['venue_name'] = $user['venue_name'];
            $_SESSION['role'] = $user['role'];

            header("Location: index");
            exit();
        } else {
            $error = "Hatali kullanici adi veya sifre.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giris Yap | <?php echo htmlspecialchars(site_name()); ?></title>
    <?php include '../includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center h-screen px-4">
    <div class="max-w-md w-full bg-slate-800 p-8 rounded-2xl shadow-2xl border border-slate-700">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500"><?php echo htmlspecialchars(site_name()); ?></h1>
            <p class="text-slate-400 mt-2 text-sm">Admin Panel</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-500 px-4 py-3 rounded-xl mb-6 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-5">
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Kullanici Adi</label>
                <input type="text" name="username" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors placeholder-slate-600" placeholder="ornek: admin" required>
            </div>
            <div class="mb-8">
                <label class="block text-xs uppercase tracking-widest text-slate-500 mb-2">Sifre</label>
                <input type="password" name="password" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-violet-500 transition-colors placeholder-slate-600" placeholder="********" required>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-pink-600 to-violet-600 hover:from-pink-500 hover:to-violet-500 text-white font-bold py-4 rounded-xl shadow-lg transform active:scale-95 transition-all">
                Giris Yap
            </button>
        </form>
    </div>
</body>
</html>
