<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';

// Only allow business users
if (!isset($_SESSION['user_id']) || !isset($_SESSION['badge']) || !in_array($_SESSION['badge'], ['business', 'verified_business'])) {
    header('Location: jobs');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'waiter';
    $employment_type = $_POST['employment_type'] ?? 'seasonal';
    $salary_range = trim($_POST['salary_range'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');

    if (empty($title) || empty($description)) {
        $error = $lang == 'en' ? 'Title and description are required.' : 'Başlık ve açıklama zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO job_listings (employer_id, title, description, category, employment_type, salary_range, requirements, contact_info, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $category,
                $employment_type,
                $salary_range,
                $requirements,
                $contact_info
            ]);
            $success = $lang == 'en' ? 'Job posted successfully!' : 'İş ilanı başarıyla yayınlandı!';
        } catch (PDOException $e) {
            $error = $lang == 'en' ? 'Database error. Please try again.' : 'Veritabanı hatası. Lütfen tekrar deneyin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Post a Job' : 'İş İlanı Yayınla'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <script>
        // Force light mode override
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    </script>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-6 pt-32 pb-20 max-w-2xl">
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-3xl p-8 border border-white/20 dark:border-slate-800/50 shadow-xl">
            <h1 class="text-3xl font-extrabold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                <i class="fas fa-briefcase mr-2"></i>
                <?php echo $lang == 'en' ? 'Post a Job' : 'İş İlanı Yayınla'; ?>
            </h1>

            <?php if ($error): ?>
            <div class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                <a href="jobs" class="underline font-bold ml-2"><?php echo $lang == 'en' ? 'View Jobs' : 'İlanları Gör'; ?></a>
            </div>
            <?php else: ?>

            <form method="POST" class="space-y-6">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Job Title' : 'İş Başlığı'; ?> *</label>
                    <input type="text" name="title" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-pink-500"
                           placeholder="<?php echo $lang == 'en' ? 'e.g. Experienced Waiter Needed' : 'Örn: Deneyimli Garson Aranıyor'; ?>">
                </div>

                <!-- Category & Type -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                        <select name="category" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3">
                            <option value="waiter">🍽️ <?php echo $lang == 'en' ? 'Waiter' : 'Garson'; ?></option>
                            <option value="chef">👨‍🍳 <?php echo $lang == 'en' ? 'Chef' : 'Aşçı'; ?></option>
                            <option value="bartender">🍹 <?php echo $lang == 'en' ? 'Bartender' : 'Barmen'; ?></option>
                            <option value="receptionist">📞 <?php echo $lang == 'en' ? 'Receptionist' : 'Resepsiyon'; ?></option>
                            <option value="housekeeping">🛏️ <?php echo $lang == 'en' ? 'Housekeeping' : 'Oda Temizlik'; ?></option>
                            <option value="kitchen_staff">🥗 <?php echo $lang == 'en' ? 'Kitchen Staff' : 'Mutfak Personeli'; ?></option>
                            <option value="manager">👔 <?php echo $lang == 'en' ? 'Manager' : 'Yönetici'; ?></option>
                            <option value="boat_crew">⛵ <?php echo $lang == 'en' ? 'Boat Crew' : 'Tekne Mürettebatı'; ?></option>
                            <option value="other">📋 <?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?></option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Employment Type' : 'Çalışma Şekli'; ?></label>
                        <select name="employment_type" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3">
                            <option value="seasonal">☀️ <?php echo $lang == 'en' ? 'Seasonal' : 'Sezonluk'; ?></option>
                            <option value="full_time">💼 <?php echo $lang == 'en' ? 'Full Time' : 'Tam Zamanlı'; ?></option>
                            <option value="part_time">⏰ <?php echo $lang == 'en' ? 'Part Time' : 'Yarı Zamanlı'; ?></option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Job Description' : 'İş Açıklaması'; ?> *</label>
                    <textarea name="description" rows="5" required
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="<?php echo $lang == 'en' ? 'Describe the job responsibilities and expectations...' : 'İş sorumluluklarını ve beklentilerinizi açıklayın...'; ?>"></textarea>
                </div>

                <!-- Requirements -->
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Requirements (Optional)' : 'Gereksinimler (Opsiyonel)'; ?></label>
                    <textarea name="requirements" rows="3"
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-pink-500"
                              placeholder="<?php echo $lang == 'en' ? 'e.g. 2 years experience, English speaking...' : 'Örn: 2 yıl tecrübe, İngilizce bilen...'; ?>"></textarea>
                </div>

                <!-- Salary -->
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Salary Range (Optional)' : 'Maaş Aralığı (Opsiyonel)'; ?></label>
                    <input type="text" name="salary_range"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-pink-500"
                           placeholder="<?php echo $lang == 'en' ? 'e.g. 15.000 - 20.000 TL' : 'Örn: 15.000 - 20.000 TL'; ?>">
                </div>

                <!-- Contact -->
                <div>
                    <label class="block text-sm font-bold mb-2"><?php echo $lang == 'en' ? 'Contact Info' : 'İletişim Bilgisi'; ?></label>
                    <input type="text" name="contact_info"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-pink-500"
                           placeholder="<?php echo $lang == 'en' ? 'Phone or email for applications' : 'Başvuru için telefon veya email'; ?>">
                </div>

                <!-- Submit -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-pink-500 to-violet-500 text-white px-6 py-4 rounded-xl font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i><?php echo $lang == 'en' ? 'Post Job' : 'İlanı Yayınla'; ?>
                    </button>
                    <a href="jobs" class="px-6 py-4 rounded-xl font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Force light mode
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        function toggleTheme() { alert('Bu sayfa sadece açık tema destekler.'); }
    </script>

</body>
</html>
