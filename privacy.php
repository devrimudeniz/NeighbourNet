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
    <title><?php echo $lang == 'en' ? 'Privacy Policy' : 'Gizlilik Politikası'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
</head>
<body class="bg-gradient-to-br from-pink-50 via-purple-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen">

    <!-- Header (Simple) -->
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
                <?php echo $lang == 'en' ? 'Privacy Policy' : 'Gizlilik Politikası'; ?>
            </h1>
            
            <p class="text-slate-600 dark:text-slate-400 mb-8">
                <?php echo $lang == 'en' ? 'Last updated: ' . date('F d, Y') : 'Son güncellenme: ' . date('d F Y'); ?>
            </p>

            <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 space-y-6">
                
                <?php if ($lang == 'en'): ?>
                
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">1. Information We Collect</h2>
                <p>When you use Kalkan Social, we collect the following information:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Account Information:</strong> Username, email address, full name, and profile picture</li>
                    <li><strong>Content:</strong> Posts, comments, stories, and event listings you create</li>
                    <li><strong>Usage Data:</strong> How you interact with our platform, including likes, follows, and views</li>
                    <li><strong>Device Information:</strong> IP address, browser type, and device identifiers</li>
                    <li><strong>Location:</strong> If you choose to share your location for events</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">2. How We Use Your Information</h2>
                <p>We use the collected information to:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Provide and improve our services</li>
                    <li>Personalize your experience</li>
                    <li>Send notifications about activity on your account</li>
                    <li>Communicate updates and promotional content (with your consent)</li>
                    <li>Ensure platform security and prevent fraud</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">3. Information Sharing</h2>
                <p>We do not sell your personal information. We may share your data with:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Other Users:</strong> Your public profile, posts, and events are visible to other users</li>
                    <li><strong>Service Providers:</strong> Third-party services that help us operate the platform</li>
                    <li><strong>Legal Requirements:</strong> When required by law or to protect our rights</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">4. Your Rights</h2>
                <p>You have the right to:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Access your personal data</li>
                    <li>Correct inaccurate information</li>
                    <li>Delete your account and associated data</li>
                    <li>Object to processing of your data</li>
                    <li>Export your data in a portable format</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">5. Data Security</h2>
                <p>We implement industry-standard security measures to protect your data, including encryption, secure servers, and regular security audits.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">6. Cookies</h2>
                <p>We use cookies and similar technologies to enhance your experience, analyze usage, and provide personalized content.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">7. Contact Us</h2>
                <p>For privacy-related questions, contact us at:</p>
                <p><strong>Email:</strong> privacy@kalkansocial.com<br>
                <strong>Address:</strong> KAS Digital Solutions, Kalkan, Antalya, Turkey</p>

                <?php else: ?>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">1. Topladığımız Bilgiler</h2>
                <p>Kalkan Social'i kullandığınızda aşağıdaki bilgileri topluyoruz:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Hesap Bilgileri:</strong> Kullanıcı adı, e-posta adresi, tam ad ve profil fotoğrafı</li>
                    <li><strong>İçerik:</strong> Oluşturduğunuz gönderiler, yorumlar, hikayeler ve etkinlik ilanları</li>
                    <li><strong>Kullanım Verileri:</strong> Platformla nasıl etkileşime girdiğiniz (beğeniler, takipler, görüntülemeler)</li>
                    <li><strong>Cihaz Bilgileri:</strong> IP adresi, tarayıcı türü ve cihaz tanımlayıcıları</li>
                    <li><strong>Konum:</strong> Etkinlikler için konumunuzu paylaşmayı seçerseniz</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">2. Bilgilerinizi Nasıl Kullanırız</h2>
                <p>Toplanan bilgileri şu amaçlarla kullanırız:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Hizmetlerimizi sağlamak ve geliştirmek</li>
                    <li>Deneyiminizi kişiselleştirmek</li>
                    <li>Hesabınızdaki aktiviteler hakkında bildirim göndermek</li>
                    <li>Güncellemeler ve promosyon içeriği iletmek (onayınızla)</li>
                    <li>Platform güvenliğini sağlamak ve dolandırıcılığı önlemek</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">3. Bilgi Paylaşımı</h2>
                <p>Kişisel bilgilerinizi satmıyoruz. Verilerinizi şunlarla paylaşabiliriz:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Diğer Kullanıcılar:</strong> Genel profiliniz, gönderileriniz ve etkinlikleriniz diğer kullanıcılar tarafından görülebilir</li>
                    <li><strong>Hizmet Sağlayıcılar:</strong> Platformu işletmemize yardımcı olan üçüncü taraf hizmetler</li>
                    <li><strong>Yasal Gereklilikler:</strong> Kanunun gerektirdiği veya haklarımızı korumak için gerekli durumlarda</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">4. Haklarınız</h2>
                <p>Aşağıdaki haklara sahipsiniz:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Kişisel verilerinize erişim</li>
                    <li>Yanlış bilgileri düzeltme</li>
                    <li>Hesabınızı ve ilişkili verileri silme</li>
                    <li>Verilerinizin işlenmesine itiraz etme</li>
                    <li>Verilerinizi taşınabilir formatta dışa aktarma</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">5. Veri Güvenliği</h2>
                <p>Verilerinizi korumak için şifreleme, güvenli sunucular ve düzenli güvenlik denetimleri dahil sektör standartlarında güvenlik önlemleri uyguluyoruz.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">6. Çerezler</h2>
                <p>Deneyiminizi geliştirmek, kullanımı analiz etmek ve kişiselleştirilmiş içerik sağlamak için çerezler ve benzer teknolojiler kullanıyoruz.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">7. İletişim</h2>
                <p>Giz lilik ile ilgili sorularınız için bizimle iletişime geçin:</p>
                <p><strong>E-posta:</strong> privacy@kalkansocial.com<br>
                <strong>Adres:</strong> KAS Digital Solutions, Kalkan, Antalya, Türkiye</p>

                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>
