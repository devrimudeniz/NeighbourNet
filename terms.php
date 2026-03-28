<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
session_start();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Terms of Service' : 'Kullanım Şartları'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
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
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 md:p-12 border border-white/20 dark:border-slate-800/50">
            
            <h1 class="text-4xl font-extrabold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                <?php echo $lang == 'en' ? 'Terms of Service' : 'Kullanım Şartları'; ?>
            </h1>
            
            <p class="text-slate-600 dark:text-slate-400 mb-8">
                <?php echo $lang == 'en' ? 'Last updated: ' . date('F d, Y') : 'Son güncellenme: ' . date('d F Y'); ?>
            </p>

            <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 space-y-6">
                
                <?php if ($lang == 'en'): ?>
                
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">1. Acceptance of Terms</h2>
                <p>By accessing and using Kalkan Social, you accept and agree to be bound by the terms and provisions of this agreement.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">2. User Accounts</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>You must be at least 13 years old to use this service</li>
                    <li>You are responsible for maintaining the confidentiality of your account</li>
                    <li>You must provide accurate and complete information</li>
                    <li>One person may not maintain more than one account</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">3. User Content</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>You retain ownership of content you post</li>
                    <li>By posting content, you grant us a non-exclusive license to use, display, and distribute it</li>
                    <li>You are responsible for the content you post</li>
                    <li>We reserve the right to remove content that violates our policies</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">4. Prohibited Conduct</h2>
                <p>You agree not to:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Post illegal, harmful, or offensive content</li>
                    <li>Harass, bully, or intimidate other users</li>
                    <li>Spam or engage in commercial solicitation</li>
                    <li>Impersonate others or misrepresent your affiliation</li>
                    <li>Attempt to gain unauthorized access to the platform</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">5. Intellectual Property</h2>
                <p>All platform content, features, and functionality are owned by Kalkan Social and protected by intellectual property laws.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">6. Termination</h2>
                <p>We reserve the right to suspend or terminate your account for violations of these terms or at our discretion.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">7. Disclaimer</h2>
                <p>The service is provided "as is" without warranties of any kind. We are not responsible for user-generated content or interactions.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">8. Changes to Terms</h2>
                <p>We may modify these terms at any time. Continued use of the service constitutes acceptance of modified terms.</p>

                <?php else: ?>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">1. Şartların Kabulü</h2>
                <p>Kalkan Social'e erişerek ve kullanarak, bu sözleşmenin hüküm ve koşullarına bağlı kalmayı kabul edersiniz.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">2. Kullanıcı Hesapları</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Bu hizmeti kullanmak için en az 13 yaşında olmalısınız</li>
                    <li>Hesabınızın gizliliğini korumaktan siz sorumlusunuz</li>
                    <li>Doğru ve eksiksiz bilgi sağlamalısınız</li>
                    <li>Bir kişi birden fazla hesap açamaz</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">3. Kullanıcı İçeriği</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Gönderdiğiniz içeriğin sahipliğini siz korursunuz</li>
                    <li>İçerik göndererek, bize kullanma, görüntüleme ve dağıtma konusunda münhasır olmayan bir lisans verirsiniz</li>
                    <li>Gönderdiğiniz içerikten siz sorumlusunuz</li>
                    <li>Politikalarımızı ihlal eden içerikleri kaldırma hakkımızı saklı tutarız</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">4. Yasaklanan Davranışlar</h2>
                <p>Aşağıdakileri yapmamayı kabul edersiniz:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Yasadışı, zararlı veya saldırgan içerik paylaşmak</li>
                    <li>Diğer kullanıcıları taciz etmek, zorbalamak veya tehdit etmek</li>
                    <li>Spam veya ticari talepte bulunmak</li>
                    <li>Başkalarının kimliğine bürünmek veya bağlantınızı yanlış göstermek</li>
                    <li>Platforma yetkisiz erişim sağlamaya çalışmak</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">5. Fikri Mülkiyet</h2>
                <p>Tüm platform içeriği, özellikleri ve işlevselliği Kalkan Social'e aittir ve fikri mülkiyet yasalarıyla korunmaktadır.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">6. Fesih</h2>
                <p>Bu şartların ihlali veya takdirimize bağlı olarak hesabınızı askıya alma veya feshetme hakkımızı saklı tutarız.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">7. Sorumluluk Reddi</h2>
                <p>Hizmet "olduğu gibi" herhangi bir garanti olmaksızın sağlanmaktadır. Kullanıcı tarafından oluşturulan içerik veya etkileşimlerden sorumlu değiliz.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">8. Şartlarda Değişiklikler</h2>
                <p>Bu şartları istediğimiz zaman değiştirebiliriz. Hizmeti kullanmaya devam etmek, değiştirilen şartları kabul etmek anlamına gelir.</p>

                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>
