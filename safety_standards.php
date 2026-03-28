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
    <title><?php echo $lang == 'en' ? 'Child Safety Standards (CSAE)' : 'Çocuk Güvenliği Standartları (CSAE)'; ?> | Kalkan Social</title>
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
            
            <h1 class="text-3xl md:text-4xl font-extrabold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500 leading-tight">
                <?php echo $lang == 'en' ? 'Child Safety Standards & CSAE Policy' : 'Çocuk Güvenliği ve CSAE Politikası'; ?>
            </h1>
            
            <p class="text-slate-600 dark:text-slate-400 mb-8 border-b border-slate-200 dark:border-slate-700 pb-4">
                <?php echo $lang == 'en' ? 'Commitment to Child Safety' : 'Çocuk Güvenliğine Bağlılık'; ?>
            </p>

            <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 space-y-6">
                
                <?php if ($lang == 'en'): ?>
                
                <p class="lead font-medium text-lg">Kalkan Social has a <strong>zero-tolerance policy</strong> regarding Child Sexual Abuse Material (CSAM) and the exploitation of children. We are committed to maintaining a safe environment for all users and strictly adhere to global safety standards.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">1. Zero Tolerance Policy</h2>
                <p>We strictly prohibit the uploading, sharing, or distribution of any content that depicts, promotes, or facilitates child sexual abuse or exploitation. Any user found violating this policy will be:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Immediately and permanently banned from the platform.</li>
                    <li>Reported to the relevant law enforcement agencies, including the National Center for Missing and Exploited Children (NCMEC) and local Turkish authorities.</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">2. Content Monitoring & Moderation</h2>
                <p>To ensure the safety of our platform:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>We utilize both automated detection technologies and manual human moderation to identify harmful content.</li>
                    <li>All reported content concerning child safety is prioritized for immediate review.</li>
                    <li>We actively monitor for keywords, patterns, and behaviors associated with predatory conduct.</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">3. Reporting Mechanism</h2>
                <p>We encourage our community to report any suspicious activity immediately. You can report content directly through the app via the "Report" function or contact our safety team:</p>
                <p><strong>Email:</strong> safety@kalkansocial.com</p>
                <p>Reports related to child safety are reviewed 24/7.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">4. Cooperation with Law Enforcement</h2>
                <p>We fully cooperate with international and local law enforcement agencies investigating crimes against children. We will preserve and provide user data and content evidence when required by law or when we have a good-faith belief that it is necessary to prevent imminent harm to a child.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">5. External Resources</h2>
                <p>If you are aware of a child in danger, please contact local law enforcement immediately.</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><a href="https://report.cybertip.org/" target="_blank" class="text-pink-500 hover:underline">NCMEC CyberTipline</a></li>
                    <li><a href="https://www.internethelpline.org.tr/" target="_blank" class="text-pink-500 hover:underline">IHBAR WEB (Turkey)</a></li>
                </ul>

                <?php else: ?>

                <p class="lead font-medium text-lg">Kalkan Social, Çocuk Cinsel İstismarı Materyali (CSAM) ve çocukların istismarı konusunda <strong>sıfır tolerans politikasına</strong> sahiptir. Tüm kullanıcılar için güvenli bir ortam sağlamayı taahhüt ediyor ve küresel güvenlik standartlarına sıkı sıkıya bağlı kalıyoruz.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">1. Sıfır Tolerans Politikası</h2>
                <p>Çocuk cinsel istismarını veya sömürüsünü tasvir eden, teşvik eden veya kolaylaştıran her türlü içeriğin yüklenmesini, paylaşılmasını veya dağıtılmasını kesinlikle yasaklıyoruz. Bu politikayı ihlal ettiği tespit edilen herhangi bir kullanıcı:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Platformdan derhal ve kalıcı olarak yasaklanacaktır.</li>
                    <li>Ulusal Kayıp ve İstismara Uğramış Çocuklar Merkezi (NCMEC) ve yerel Türk makamları dahil olmak üzere ilgili kolluk kuvvetlerine bildirilecektir.</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">2. İçerik İzleme ve Moderasyon</h2>
                <p>Platformumuzun güvenliğini sağlamak için:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Zararlı içeriği belirlemek için hem otomatik tespit teknolojilerini hem de manuel insan denetimini kullanıyoruz.</li>
                    <li>Çocuk güvenliği ile ilgili bildirilen tüm içerikler, derhal incelenmek üzere önceliklendirilir.</li>
                    <li>İstismarcı davranışlarla ilişkili anahtar kelimeleri, kalıpları ve davranışları aktif olarak izliyoruz.</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">3. Raporlama Mekanizması</h2>
                <p>Topluluğumuzu şüpheli etkinlikleri derhal bildirmeye teşvik ediyoruz. İçeriği doğrudan uygulama üzerinden "Şikayet Et" işleviyle bildirebilir veya güvenlik ekibimizle iletişime geçebilirsiniz:</p>
                <p><strong>E-posta:</strong> safety@kalkansocial.com</p>
                <p>Çocuk güvenliği ile ilgili raporlar 7/24 incelenmektedir.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">4. Kolluk Kuvvetleri ile İşbirliği</h2>
                <p>Çocuklara karşı işlenen suçları soruşturan uluslararası ve yerel kolluk kuvvetleriyle tam işbirliği yapıyoruz. Kanunen gerekli olduğunda veya bir çocuğun zarar görmesini önlemek için gerekli olduğuna iyi niyetle inandığımızda, kullanıcı verilerini ve içerik kanıtlarını saklayacak ve yetkililere sunacağız.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">5. Harici Kaynaklar</h2>
                <p>Tehlike altındaki bir çocuktan haberdarsanız, lütfen derhal yerel kolluk kuvvetleriyle iletişime geçin.</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><a href="https://report.cybertip.org/" target="_blank" class="text-pink-500 hover:underline">NCMEC CyberTipline (Uluslararası)</a></li>
                    <li><a href="https://www.internethelpline.org.tr/" target="_blank" class="text-pink-500 hover:underline">İHBAR WEB (Türkiye)</a></li>
                </ul>

                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>
