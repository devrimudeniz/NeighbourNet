<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';
session_start();
?>
<!DOCTYPE html>
<html lang="tr" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KVKK Aydınlatma Metni | Kalkan Social</title>
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
                <i class="fas fa-arrow-left mr-2"></i>Ana Sayfaya Dön
            </a>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12 max-w-4xl">
        <div class="bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl rounded-2xl p-8 md:p-12 border border-white/20 dark:border-slate-800/50">
            
            <h1 class="text-4xl font-extrabold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-violet-500">
                KVKK Aydınlatma Metni
            </h1>
            
            <p class="text-slate-600 dark:text-slate-400 mb-8">
                Son güncellenme: <?php echo date('d F Y'); ?>
            </p>

            <div class="prose dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 space-y-6">
                
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">1. Veri Sorumlusu</h2>
                <p><strong>Şirket:</strong> KAS Digital Solutions<br>
                <strong>Adres:</strong> Kalkan, Antalya, Türkiye<br>
                <strong>E-posta:</strong> info@kalkansocial.com</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">2. Kişisel Verilerin Toplanma Yöntemi ve Hukuki Sebebi</h2>
                <p>Kişisel verileriniz, Kalkan Social platformunu kullanımınız sırasında elektronik ortamda otomatik veya yarı otomatik yöntemlerle ve aşağıdaki hukuki sebeplere dayanarak toplan

maktadır:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Açık rızanız</li>
                    <li>Hukuki yükümlülüklerimizin yerine getirilmesi</li>
                    <li>Bir sözleşmenin kurulması veya ifası</li>
                    <li>Meşru menfaatlerimiz</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">3. İşlenen Kişisel Veriler</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Kimlik Bilgileri:</strong> Ad, soyad, kullanıcı adı</li>
                    <li><strong>İletişim Bilgileri:</strong> E-posta adresi, telefon numarası (isteğe bağlı)</li>
                    <li><strong>Görsek Veri:</strong> Profil fotoğrafı, paylaştığınız görseller</li>
                    <li><strong>İçerik Verileri:</strong> Gönderiler, yorumlar, beğeniler</li>
                    <li><strong>Konum Bilgisi:</strong> Etkinlik konumları (isteğe bağlı)</li>
                    <li><strong>İşlem Güvenliği Bilgileri:</strong> IP adresi, çerez kayıtları</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">4. Kişisel Verilerin İşlenme Amaçları</h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Platform hizmetlerinin sunulması</li>
                    <li>Kullanıcı deneyiminin iyileştirilmesi</li>
                    <li>İletişim faaliyetlerinin yürütülmesi</li>
                    <li>Güvenlik ve dolandırıcılık önleme</li>
                    <li>Yasal yükümlülüklerin yerine getirilmesi</li>
                    <li>İstatistiksel çalışmalar</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">5. Kişisel Verilerin Aktarılması</h2>
                <p>Kişisel verileriniz aşağıdaki durumlarda üçüncü kişilerle paylaşılabilir:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Yasal yükümlülük gereği kamu kurum ve kuruluşlarına</li>
                    <li>Hizmet sağlayıcılarımıza (hosting, analitik servisleri vs.)</li>
                    <li>Açık rızanız dahilinde</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">6. Kişisel Veri Sahibinin Hakları</h2>
                <p>KVKK'nın 11. maddesi uyarınca şu haklara sahipsiniz:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
                    <li>İşlenmiş ise bilgi talep etme</li>
                    <li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme</li>
                    <li>Yurt içinde/dışında aktarıldığı üçüncü kişileri bilme</li>
                    <li>Eksik/yanlış işlenmişse düzeltilmesini isteme</li>
                    <li>KVKK'da öngörülen şartlar çerçevesinde silinmesini/yok edilmesini isteme</li>
                    <li>Düzeltme ve silinme işlemlerinin aktarıldığı üçüncü kişilere bildirilmesini isteme</li>
                    <li>Otomatik sistemlerle analiz edilmesi nedeniyle aleyhinize sonuç çıkmasına itiraz etme</li>
                    <li>Kanuna aykırı işleme nedeniyle zararınızın giderilmesini talep etme</li>
                </ul>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">7. Başvuru Yöntemi</h2>
                <p>Haklarınızı kullanmak için aşağıdaki kanallardan talebinizi iletebilirsiniz:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>E-posta:</strong> kvkk@kalkansocial.com</li>
                    <li><strong>Posta:</strong> KAS Digital Solutions, Kalkan, Antalya, Türkiye</li>
                </ul>
                <p class="mt-4">Başvurularınız en geç 30 gün içinde değerlendirilecektir.</p>

                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-8">8. Veri Güvenliği</h2>
                <p>Kişisel verilerinizin güvenliğini sağlamak için teknik ve idari tedbirler alınmaktadır. Verileriniz şifreleme, güvenli sunucular ve erişim kontrolleriyle korunmaktadır.</p>

            </div>
        </div>
    </main>

</body>
</html>
