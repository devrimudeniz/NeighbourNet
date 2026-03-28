<?php
require_once 'includes/bootstrap.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if ($name && $email && $subject && $message) {
        // Send email to admin
        require_once 'includes/email-helper.php';
        
        $to = 'devrimdeniz461@gmail.com';
        $mail_subject = "New Contact from KalkanSocial: " . $subject;
        
        $mail_content = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
            <h2 style='color: #0055FF; border-bottom: 2px solid #0055FF; padding-bottom: 10px;'>New Contact Message</h2>
            <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
            <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 15px;'>
                <p><strong>Message:</strong></p>
                <p style='white-space: pre-line;'>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
            <p style='font-size: 12px; color: #888; margin-top: 20px; text-align: center;'>Sent from KalkanSocial Contact Form</p>
        </div>
        ";

        if (sendEmail($to, $mail_subject, $mail_content)) {
            $success = true;
        } else {
            $error = $lang == 'en' ? 'Failed to send message. Please try again.' : 'Mesaj gönderilemedi. Lütfen tekrar deneyin.';
        }
    } else {
        $error = $lang == 'en' ? 'Please fill all fields' : 'Lütfen tüm alanları doldurun';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Contact Us' : 'İletişim'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <header class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border-b border-white/20 dark:border-slate-800/50 py-4 sticky top-0 z-50">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <a href="index" class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                Kalkan<span class="text-slate-900 dark:text-white">Social</span>
            </a>
            <a href="index" class="text-sm text-slate-600 dark:text-slate-400 hover:text-pink-500">
                <i class="fas fa-arrow-left mr-2"></i><?php echo $lang == 'en' ? 'Back to Home' : 'Ana Sayfaya Dön'; ?>
            </a>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12 max-w-4xl">
        
        <!-- About Us Section -->
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-black mb-4 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                <?php echo $lang == 'en' ? 'About Us' : 'Hakkımızda'; ?>
            </h1>
            <p class="text-slate-600 dark:text-slate-300 text-lg leading-relaxed max-w-2xl mx-auto">
                <?php echo $lang == 'en' 
                    ? 'Kalkan Social is the ultimate community platform for Kalkan, bringing together locals, expats, and visitors. We aim to connect people through events, information, and social interaction, making life in Kalkan more vibrant and accessible for everyone.' 
                    : 'Kalkan Social, yerlileri, yerleşik yabancıları ve ziyaretçileri bir araya getiren Kalkan\'ın en kapsamlı topluluk platformudur. Etkinlikler, bilgi paylaşımı ve sosyal etkileşim yoluyla insanları birbirine bağlamayı, Kalkan\'da yaşamı herkes için daha canlı ve erişilebilir kılmayı hedefliyoruz.'; ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Contact Form -->
            <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 border border-white/20 dark:border-slate-800/50">
                <h1 class="text-3xl font-extrabold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                    <?php echo $lang == 'en' ? 'Get in Touch' : 'İletişime Geçin'; ?>
                </h1>

                <?php if ($success): ?>
                <div class="bg-green-100 dark:bg-green-900/30 border border-green-500 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl mb-6">
                    <?php echo $lang == 'en' ? 'Thank you! We\'ll get back to you soon.' : 'Teşekkürler! En kısa sürede size dönüş yapacağız.'; ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="bg-red-100 dark:bg-red-900/30 border border-red-500 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl mb-6">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <?php echo $lang == 'en' ? 'Your Name' : 'Adınız'; ?>
                        </label>
                        <input type="text" name="name" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <?php echo $lang == 'en' ? 'Email Address' : 'E-posta Adresiniz'; ?>
                        </label>
                        <input type="email" name="email" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <?php echo $lang == 'en' ? 'Subject' : 'Konu'; ?>
                        </label>
                        <input type="text" name="subject" required
                               class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">
                            <?php echo $lang == 'en' ? 'Message' : 'Mesajınız'; ?>
                        </label>
                        <textarea name="message" required rows="5"
                                  class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-pink-500"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-pink-500 to-violet-500 text-white py-3 rounded-xl font-bold hover:shadow-lg hover:shadow-pink-500/30 transition-all">
                        <?php echo $lang == 'en' ? 'Send Message' : 'Mesaj Gönder'; ?>
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="space-y-6">
                <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 border border-white/20 dark:border-slate-800/50">
                    <h2 class="text-2xl font-bold mb-6">
                        <?php echo $lang == 'en' ? 'Contact Information' : 'İletişim Bilgileri'; ?>
                    </h2>

                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-pink-500 to-violet-500 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-envelope text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-bold mb-1"><?php echo $lang == 'en' ? 'Email' : 'E-posta'; ?></h3>
                                <p class="text-slate-600 dark:text-slate-400">info@kalkansocial.com</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-pink-500 to-violet-500 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-bold mb-1"><?php echo $lang == 'en' ? 'Address' : 'Adres'; ?></h3>
                                <p class="text-slate-600 dark:text-slate-400">Kalkan, Antalya, Turkey</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-pink-500 to-violet-500 flex items-center justify-center flex-shrink-0">
                                <i class="fab fa-instagram text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-bold mb-1">Instagram</h3>
                                <a href="https://instagram.com/devrimdnz07" target="_blank" class="text-pink-500 hover:text-pink-400">@devrimdnz07</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 border border-white/20 dark:border-slate-800/50">
                    <h2 class="text-2xl font-bold mb-4">
                        <?php echo $lang == 'en' ? 'Business Hours' : 'Çalışma Saatleri'; ?>
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400">
                        <?php echo $lang == 'en' 
                            ? 'Monday - Sunday: 24/7 Online Support' 
                            : 'Pazartesi - Pazar: 7/24 Online Destek'; ?>
                    </p>
                </div>
            </div>

        </div>

        <!-- FAQ / Help -->
        <div class="mt-12">
            <a href="faq.php" class="group flex items-center gap-4 bg-gradient-to-r from-emerald-500 to-teal-600 p-6 rounded-2xl shadow-lg border border-emerald-400 hover:shadow-xl hover:scale-[1.01] transition-all">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                    <i class="fas fa-question-circle text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-white"><?php echo $lang == 'en' ? 'Help & FAQ' : 'Yardım & SSS'; ?></h3>
                    <p class="text-sm text-emerald-100 mt-1"><?php echo $lang == 'en' ? 'Answers to common questions' : 'Sık sorulan sorular'; ?></p>
                </div>
                <i class="fas fa-arrow-right text-white/50 ml-auto group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

    </main>
</body>
</html>
