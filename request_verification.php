<?php
require_once 'includes/db.php';
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Check if already requested or already verified
$stmt = $pdo->prepare("SELECT status, request_type FROM verification_requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pending_request = $stmt->fetch();

$u_stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
$u_stmt->execute([$_SESSION['user_id']]);
$user_badge = $u_stmt->fetchColumn();

$is_verified = in_array($user_badge, ['founder', 'moderator', 'business', 'verified_business', 'captain']);

// Check if user wants to upgrade to captain (verified_business users)
$upgrade_to_captain = isset($_GET['upgrade']) && $_GET['upgrade'] === 'captain' && in_array($user_badge, ['business', 'verified_business']);

// Also check type parameter from boat_trips page
$request_type_param = $_GET['type'] ?? '';
if ($request_type_param === 'captain' && in_array($user_badge, ['business', 'verified_business'])) {
    $upgrade_to_captain = true;
}

// Check if user wants to add business verification (captain users or other verified users)
$upgrade_to_business = false;
if ($request_type_param === 'business' && in_array($user_badge, ['captain', 'real_estate'])) {
    $upgrade_to_business = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['request_verification']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-20 transition-colors">

    <?php include 'includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-6 pt-32">
        <div class="bg-white dark:bg-slate-800 rounded-[3rem] p-10 shadow-2xl border border-slate-100 dark:border-slate-800">
            
            <?php if ($upgrade_to_captain): ?>
                <!-- Captain Upgrade Section for verified_business users -->
                <div class="mb-8 text-center">
                    <div class="w-20 h-20 bg-cyan-100 dark:bg-cyan-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-ship text-cyan-600 dark:text-cyan-400 text-3xl"></i>
                    </div>
                    <h1 class="text-3xl font-black mb-2"><?php echo $lang == 'en' ? 'Become a Captain' : 'Kaptan Ol'; ?></h1>
                    <p class="text-slate-500 dark:text-slate-400">
                        <?php echo $lang == 'en' 
                            ? 'Upgrade your account to list boat trips and reach travelers in Kalkan.' 
                            : 'Hesabınızı yükselterek tekne turları ekleyin ve Kalkan\'daki gezginlere ulaşın.'; ?>
                    </p>
                </div>

                <!-- Captain Upgrade Form -->
                <form id="captainUpgradeForm" class="space-y-6" enctype="multipart/form-data">
                    <input type="hidden" name="request_type" value="captain">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Full Name' : 'Ad Soyad'; ?></label>
                        <input type="text" name="full_name" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Boat Name' : 'Tekne Adı'; ?></label>
                        <input type="text" name="boat_name" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Boat License Number' : 'Tekne Ruhsat Numarası'; ?></label>
                        <input type="text" name="boat_license_number" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Phone' : 'Telefon'; ?></label>
                        <input type="tel" name="phone" required placeholder="+90 ..." class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'License/Documentation (PDF, JPG, PNG)' : 'Ruhsat/Belge (PDF, JPG, PNG)'; ?></label>
                        <input type="file" name="documentation" accept=".pdf,.jpg,.jpeg,.png" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Additional Information' : 'Ek Bilgi'; ?></label>
                        <textarea name="additional_info" rows="4" placeholder="<?php echo $lang == 'en' ? 'Years of experience, boat type, services offered...' : 'Deneyim yılı, tekne tipi, sunulan hizmetler...'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-medium"></textarea>
                    </div>

                    <div class="p-6 bg-cyan-50 dark:bg-cyan-900/20 rounded-2xl border border-dashed border-cyan-200 dark:border-cyan-700">
                        <p class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest mb-2"><?php echo $lang == 'en' ? 'Note' : 'Not'; ?></p>
                        <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                            <?php echo $lang == 'en' 
                                ? 'Once approved, your badge will be upgraded from Verified Business to Captain. You will then be able to add boat trips.' 
                                : 'Onaylandığında, badge\'iniz Onaylı İşletme\'den Kaptan\'a yükseltilecek. Daha sonra tekne turları ekleyebileceksiniz.'; ?>
                        </p>
                    </div>

                    <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-black py-5 rounded-[2rem] shadow-2xl shadow-cyan-500/30 transition-all transform active:scale-95 text-xl">
                        <i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Captain Application' : 'Kaptan Başvurusu Gönder'; ?>
                    </button>
                </form>

                <script>
                    document.getElementById('captainUpgradeForm')?.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        const btn = this.querySelector('button[type="submit"]');
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?php echo $lang == 'en' ? 'Submitting...' : 'Gönderiliyor...'; ?>';

                        fetch('api/verification.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.querySelector('main').innerHTML = `
                                    <div class="bg-gradient-to-br from-cyan-500 to-blue-500 rounded-[3rem] p-12 text-white text-center shadow-2xl max-w-2xl mx-auto">
                                        <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                                            <i class="fas fa-ship text-5xl animate-bounce"></i>
                                        </div>
                                        <h1 class="text-4xl font-black mb-4"><?php echo $lang == 'en' ? 'Application Submitted!' : 'Başvuru Gönderildi!'; ?></h1>
                                        <p class="text-xl opacity-90 mb-6">
                                            <?php echo $lang == 'en' 
                                                ? 'Your captain upgrade request is being reviewed.' 
                                                : 'Kaptan yükseltme başvurunuz inceleniyor.'; ?>
                                        </p>
                                        <a href="boat_trips" class="inline-block bg-white text-slate-800 px-8 py-4 rounded-2xl font-black text-lg hover:scale-105 transition-transform shadow-lg">
                                            <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang == 'en' ? 'Back to Boat Trips' : 'Tekne Turlarına Dön'; ?>
                                        </a>
                                    </div>
                                `;
                            } else {
                                alert(data.message || '<?php echo $lang == 'en' ? 'An error occurred' : 'Bir hata oluştu'; ?>');
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Captain Application' : 'Kaptan Başvurusu Gönder'; ?>';
                            }
                        })
                        .catch(err => {
                            alert('<?php echo $lang == 'en' ? 'An error occurred' : 'Bir hata oluştu'; ?>');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Captain Application' : 'Kaptan Başvurusu Gönder'; ?>';
                        });
                    });
                </script>

            <?php elseif ($upgrade_to_business): ?>
                <!-- Business Upgrade Section for captain/real_estate users -->
                <div class="mb-8 text-center">
                    <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-store text-emerald-600 dark:text-emerald-400 text-3xl"></i>
                    </div>
                    <h1 class="text-3xl font-black mb-2"><?php echo $lang == 'en' ? 'Add Your Business' : 'İşletmeni Ekle'; ?></h1>
                    <p class="text-slate-500 dark:text-slate-400">
                        <?php echo $lang == 'en' 
                            ? 'Get your business verified to list it in our directory.' 
                            : 'İşletmenizi doğrulayarak rehberimizde listeleyin.'; ?>
                    </p>
                </div>

                <!-- Business Upgrade Form -->
                <form id="businessUpgradeForm" class="space-y-6" enctype="multipart/form-data">
                    <input type="hidden" name="request_type" value="business">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Business Name' : 'İşletme Adı'; ?></label>
                        <input type="text" name="business_name" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                        <select name="business_category" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                            <option value=""><?php echo $lang == 'en' ? 'Select...' : 'Seçiniz...'; ?></option>
                            <option value="restaurant">🍽️ <?php echo $lang == 'en' ? 'Restaurant' : 'Restoran'; ?></option>
                            <option value="hotel">🏨 <?php echo $lang == 'en' ? 'Hotel' : 'Otel'; ?></option>
                            <option value="shop">🛍️ <?php echo $lang == 'en' ? 'Shop' : 'Mağaza'; ?></option>
                            <option value="cafe">☕ <?php echo $lang == 'en' ? 'Cafe' : 'Kafe'; ?></option>
                            <option value="bar">🍹 <?php echo $lang == 'en' ? 'Bar' : 'Bar'; ?></option>
                            <option value="other">🏪 <?php echo $lang == 'en' ? 'Other' : 'Diğer'; ?></option>
                        </select>
                        </select>

                        <!-- Menu Photos Section (Restaurant/Cafe/Bar) -->
                        <div id="menuUploadSection" class="hidden space-y-2 mt-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2">
                                <i class="fas fa-utensils text-pink-500 mr-1"></i>
                                <?php echo $lang == 'en' ? 'Menu Photos' : 'Menü Fotoğrafları'; ?>
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400 ml-2 mb-2">
                                <?php echo $lang == 'en' ? 'Upload photos of your menu pages.' : 'Menü sayfalarınızın fotoğraflarını yükleyin.'; ?>
                            </p>
                            <input type="file" name="menu_photos[]" multiple accept="image/*" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Phone' : 'Telefon'; ?></label>
                        <input type="tel" name="phone" required placeholder="+90 ..." class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Additional Information' : 'Ek Bilgi'; ?></label>
                        <textarea name="additional_info" rows="4" placeholder="<?php echo $lang == 'en' ? 'Tell us about your business...' : 'İşletmeniz hakkında bilgi verin...'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-medium"></textarea>
                    </div>

                    <div class="p-6 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl border border-dashed border-emerald-200 dark:border-emerald-700">
                        <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-2"><?php echo $lang == 'en' ? 'Note' : 'Not'; ?></p>
                        <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                            <?php echo $lang == 'en' 
                                ? 'Your business verification request will be reviewed by our team. You will keep your existing badge and gain business listing capabilities.' 
                                : 'İşletme doğrulama talebiniz ekibimiz tarafından incelenecektir. Mevcut badge\'inizi koruyacak ve işletme listeleme yetkisi kazanacaksınız.'; ?>
                        </p>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-5 rounded-[2rem] shadow-2xl shadow-emerald-500/30 transition-all transform active:scale-95 text-xl">
                        <i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Business Application' : 'İşletme Başvurusu Gönder'; ?>
                    </button>
                </form>

                <script>
                    document.getElementById('businessUpgradeForm')?.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        const btn = this.querySelector('button[type="submit"]');
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?php echo $lang == 'en' ? 'Submitting...' : 'Gönderiliyor...'; ?>';

                        fetch('api/verification.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.querySelector('main').innerHTML = `
                                    <div class="bg-gradient-to-br from-emerald-500 to-teal-500 rounded-[3rem] p-12 text-white text-center shadow-2xl max-w-2xl mx-auto">
                                        <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                                            <i class="fas fa-store text-5xl animate-bounce"></i>
                                        </div>
                                        <h1 class="text-4xl font-black mb-4"><?php echo $lang == 'en' ? 'Application Submitted!' : 'Başvuru Gönderildi!'; ?></h1>
                                        <p class="text-xl opacity-90 mb-6">
                                            <?php echo $lang == 'en' 
                                                ? 'Your business verification request is being reviewed.' 
                                                : 'İşletme doğrulama başvurunuz inceleniyor.'; ?>
                                        </p>
                                        <a href="directory" class="inline-block bg-white text-slate-800 px-8 py-4 rounded-2xl font-black text-lg hover:scale-105 transition-transform shadow-lg">
                                            <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang == 'en' ? 'Back to Directory' : 'Rehbere Dön'; ?>
                                        </a>
                                    </div>
                                `;
                            } else {
                                alert(data.message || '<?php echo $lang == 'en' ? 'An error occurred' : 'Bir hata oluştu'; ?>');
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Business Application' : 'İşletme Başvurusu Gönder'; ?>';
                            }
                        })
                        .catch(err => {
                            alert('<?php echo $lang == 'en' ? 'An error occurred' : 'Bir hata oluştu'; ?>');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Business Application' : 'İşletme Başvurusu Gönder'; ?>';
                        });
                    });
                </script>

            <?php elseif ($is_verified): ?>
                <div class="text-center">
                    <div class="w-24 h-24 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check-double text-emerald-600 dark:text-emerald-400 text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-black mb-4"><?php echo $lang == 'en' ? 'Already Verified!' : 'Zaten Doğrulanmış!'; ?></h1>
                    <p class="text-slate-500 dark:text-slate-400 mb-8">
                        <?php echo $lang == 'en' 
                            ? 'Your account is already verified. You can now access exclusive features.' 
                            : 'Hesabınız zaten doğrulanmış durumda. Artık özel özelliklere erişebilirsiniz.'; ?>
                    </p>
                    
                    <div class="flex flex-col md:flex-row justify-center gap-4 flex-wrap">
                        <?php if (in_array($user_badge, ['business', 'verified_business'])): ?>
                            <a href="add_business" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                                <i class="fas fa-store"></i> <?php echo $lang == 'en' ? 'Add/Manage Business' : 'İşletme Ekle/Yönet'; ?>
                            </a>
                            <!-- Allow verified_business users to also become a Captain -->
                            <a href="request_verification?upgrade=captain" class="inline-flex items-center justify-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white px-8 py-4 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                                <i class="fas fa-ship"></i> <?php echo $lang == 'en' ? 'Become a Captain' : 'Kaptan Ol'; ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($user_badge == 'captain'): ?>
                            <a href="add_boat_trip" class="inline-flex items-center justify-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white px-8 py-4 rounded-2xl font-black shadow-lg hover:scale-105 transition-all">
                                <i class="fas fa-ship"></i> <?php echo $lang == 'en' ? 'Add Boat Trip' : 'Tekne Turu Ekle'; ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user_badge == 'expert'): // Assuming real_estate maps to 'expert' or similar, let's check code ?>
                             <!-- Expert/Real Estate actions if needed -->
                        <?php endif; ?>

                        <a href="index" class="inline-flex items-center justify-center gap-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-8 py-4 rounded-2xl font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                            <?php echo $lang == 'en' ? 'Back to Home' : 'Ana Sayfaya Dön'; ?>
                        </a>
                    </div>
                </div>

            <?php elseif ($pending_request): ?>
                <div class="text-center">
                    <div class="w-24 h-24 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                        <i class="fas fa-hourglass-half text-amber-600 dark:text-amber-400 text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-black mb-4"><?php echo $t['verification_request'] ?? ($lang == 'en' ? 'Verification Pending' : 'Doğrulama Beklemede'); ?></h1>
                    <p class="text-slate-500 dark:text-slate-400 mb-4">
                        <?php echo $lang == 'en' 
                            ? 'Your verification request is being reviewed by our team.' 
                            : 'Doğrulama talebiniz ekibimiz tarafından inceleniyor.'; ?>
                    </p>
                    <p class="text-sm text-slate-400 mb-8">
                        <?php echo $lang == 'en' ? 'Request Type: ' : 'Talep Türü: '; ?>
                        <span class="font-bold text-emerald-500">
                            <?php echo $pending_request['request_type'] == 'captain' ? ($lang == 'en' ? 'Captain/Boat Operator' : 'Kaptan/Tekne Operatörü') : ($lang == 'en' ? 'Business Owner' : 'İşletme Sahibi'); ?>
                        </span>
                    </p>
                    <a href="index" class="text-emerald-500 font-bold hover:underline"><?php echo $lang == 'en' ? 'Back to Home' : 'Ana Sayfaya Dön'; ?></a>
                </div>

            <?php else: ?>
                <div class="mb-8">
                    <h1 class="text-3xl font-black mb-2"><?php echo $t['request_verification'] ?? ($lang == 'en' ? 'Request Verification' : 'Doğrulama Talebi'); ?></h1>
                    <p class="text-slate-500 dark:text-slate-400">
                        <?php echo $lang == 'en' 
                            ? 'Get verified to list properties, boat trips, or promote your business.' 
                            : 'Villa ilanları, tekne turları paylaşmak veya işletmenizi tanıtmak için doğrulanın.'; ?>
                    </p>
                </div>

                <!-- Verification Type Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <button onclick="selectVerificationType('business')" id="btn-business" class="verification-type-btn p-8 rounded-3xl border-2 border-slate-200 dark:border-slate-700 hover:border-emerald-500 transition-all text-left group">
                        <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-store text-emerald-600 dark:text-emerald-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-black mb-2"><?php echo $lang == 'en' ? 'Business Owner' : 'İşletme Sahibi'; ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Restaurants, hotels, shops' : 'Restoran, otel, mağaza'; ?></p>
                    </button>

                    <button onclick="selectVerificationType('captain')" id="btn-captain" class="verification-type-btn p-8 rounded-3xl border-2 border-slate-200 dark:border-slate-700 hover:border-cyan-500 transition-all text-left group">
                        <div class="w-16 h-16 bg-cyan-100 dark:bg-cyan-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-ship text-cyan-600 dark:text-cyan-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-black mb-2"><?php echo $lang == 'en' ? 'Captain / Boat Operator' : 'Kaptan / Tekne Operatörü'; ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Boat tours, charters, fishing' : 'Tekne turları, charter, balık avı'; ?></p>
                    </button>

                    <button onclick="selectVerificationType('real_estate')" id="btn-real_estate" class="verification-type-btn p-8 rounded-3xl border-2 border-slate-200 dark:border-slate-700 hover:border-purple-500 transition-all text-left group md:col-span-2 lg:col-span-1">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-home text-purple-600 dark:text-purple-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-black mb-2"><?php echo $lang == 'en' ? 'Real Estate Agent' : 'Emlak Danışmanı'; ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo $lang == 'en' ? 'Agencies, property managers' : 'Emlak ofisleri, gayrimenkul danışmanları'; ?></p>
                    </button>
                </div>

                <!-- Business Verification Form -->
                <form id="businessForm" class="space-y-6 hidden" enctype="multipart/form-data">
                    <input type="hidden" name="request_type" value="business">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Business Name' : 'İşletme Adı'; ?></label>
                        <input type="text" name="business_name" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?></label>
                        <select name="business_category" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold">
                            <option value="">Select...</option>
                            <option value="restaurant">🍽️ Restaurant</option>
                            <option value="hotel">🏨 Hotel</option>
                            <option value="shop">🛍️ Shop</option>
                            <option value="cafe">☕ Cafe</option>
                            <option value="other">🏪 Other</option>
                        </select>
                        
                        <!-- Menu Photos Section (Restaurant/Cafe/Bar) -->
                        <div id="menuUploadSection" class="hidden space-y-2 mt-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
                            <label class="text-xs font-black uppercase text-slate-400 ml-2">
                                <i class="fas fa-utensils text-pink-500 mr-1"></i>
                                <?php echo $lang == 'en' ? 'Menu Photos' : 'Menü Fotoğrafları'; ?>
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400 ml-2 mb-2">
                                <?php echo $lang == 'en' ? 'Upload photos of your menu pages.' : 'Menü sayfalarınızın fotoğraflarını yükleyin.'; ?>
                            </p>
                            <input type="file" name="menu_photos[]" multiple accept="image/*" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-emerald-500 outline-none transition-all font-bold file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-5 rounded-[2rem] shadow-2xl shadow-emerald-500/30 transition-all transform active:scale-95 text-xl">
                        <i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Request' : 'Başvuruyu Gönder'; ?>
                    </button>
                </form>

                <!-- Captain Verification Form -->
                <form id="captainForm" class="space-y-6 hidden" enctype="multipart/form-data">
                    <input type="hidden" name="request_type" value="captain">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Full Name' : 'Ad Soyad'; ?></label>
                        <input type="text" name="full_name" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Boat Name' : 'Tekne Adı'; ?></label>
                        <input type="text" name="boat_name" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Boat License Number' : 'Tekne Ruhsat Numarası'; ?></label>
                        <input type="text" name="boat_license_number" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Phone' : 'Telefon'; ?></label>
                        <input type="tel" name="phone" required placeholder="+90 ..." class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'License/Documentation (PDF, JPG, PNG)' : 'Ruhsat/Belge (PDF, JPG, PNG)'; ?></label>
                        <input type="file" name="documentation" accept=".pdf,.jpg,.jpeg,.png" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-bold file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Additional Information' : 'Ek Bilgi'; ?></label>
                        <textarea name="additional_info" rows="4" placeholder="<?php echo $lang == 'en' ? 'Years of experience, boat type, services offered...' : 'Deneyim yılı, tekne tipi, sunulan hizmetler...'; ?>" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-cyan-500 outline-none transition-all font-medium"></textarea>
                    </div>

                    <div class="p-6 bg-cyan-50 dark:bg-cyan-900/20 rounded-2xl border border-dashed border-cyan-200 dark:border-cyan-700">
                        <p class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-widest mb-2"><?php echo $lang == 'en' ? 'Requirements' : 'Gereksinimler'; ?></p>
                        <ul class="text-xs space-y-2 text-slate-600 dark:text-slate-400 font-medium">
                            <li>• <?php echo $lang == 'en' ? 'Valid boat operator license required' : 'Geçerli tekne işletme ruhsatı gereklidir'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'Verification typically completed within 24-48 hours' : 'Doğrulama genellikle 24-48 saat içinde tamamlanır'; ?></li>
                            <li>• <?php echo $lang == 'en' ? 'False information may result in account suspension' : 'Yanlış bilgi hesabın askıya alınmasına neden olabilir'; ?></li>
                        </ul>
                    </div>

                    <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-black py-5 rounded-[2rem] shadow-2xl shadow-cyan-500/30 transition-all transform active:scale-95 text-xl">
                        <i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Request' : 'Başvuruyu Gönder'; ?>
                    </button>
                </form>

                <!-- Real Estate Verification Form -->
                <form id="real_estateForm" class="space-y-6 hidden">
                    <input type="hidden" name="request_type" value="real_estate">
                    
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Agency Name' : 'Emlak Ofisi Adı'; ?></label>
                        <input type="text" name="business_name" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-purple-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'License Number' : 'Yetki Belgesi No'; ?></label>
                        <input type="text" name="license_number" required class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-purple-500 outline-none transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Documentation (License)' : 'Belge Yükle (Yetki Belgesi)'; ?></label>
                        <div class="relative border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-8 text-center hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer group">
                            <input type="file" name="documentation" required accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 group-hover:text-purple-500 mb-2 transition-colors"></i>
                            <p class="text-sm font-bold text-slate-500 group-hover:text-purple-500 transition-colors"><?php echo $lang == 'en' ? 'Click to upload PDF or Image' : 'PDF veya Resim yüklemek için tıklayın'; ?></p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase text-slate-400 ml-2"><?php echo $lang == 'en' ? 'Additional Information' : 'Ek Bilgi'; ?></label>
                        <textarea name="additional_info" rows="4" class="w-full bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-transparent focus:border-purple-500 outline-none transition-all font-medium"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-black py-5 rounded-[2rem] shadow-2xl shadow-purple-500/30 transition-all transform active:scale-95 text-xl">
                        <i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Application' : 'Başvuruyu Gönder'; ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        let selectedType = null;

        function selectVerificationType(type) {
            selectedType = type;
            
            // Hide all forms
            document.getElementById('businessForm').classList.add('hidden');
            document.getElementById('captainForm').classList.add('hidden');
            document.getElementById('real_estateForm').classList.add('hidden');
            
            // Reset button styles
            document.querySelectorAll('.verification-type-btn').forEach(btn => {
                btn.classList.remove('border-emerald-500', 'border-cyan-500', 'border-purple-500', 'bg-emerald-50', 'bg-cyan-50', 'bg-purple-50', 'dark:bg-emerald-900/20', 'dark:bg-cyan-900/20', 'dark:bg-purple-900/20');
                btn.classList.add('border-slate-200', 'dark:border-slate-700');
            });
            
            // Show selected form and highlight button
            if (type === 'business') {
                document.getElementById('businessForm').classList.remove('hidden');
                document.getElementById('btn-business').classList.add('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-900/20');
                document.getElementById('btn-business').classList.remove('border-slate-200', 'dark:border-slate-700');
            } else if (type === 'captain') {
                document.getElementById('captainForm').classList.remove('hidden');
                document.getElementById('btn-captain').classList.add('border-cyan-500', 'bg-cyan-50', 'dark:bg-cyan-900/20');
                document.getElementById('btn-captain').classList.remove('border-slate-200', 'dark:border-slate-700');
            } else if (type === 'real_estate') {
                document.getElementById('real_estateForm').classList.remove('hidden');
                document.getElementById('btn-real_estate').classList.add('border-purple-500', 'bg-purple-50', 'dark:bg-purple-900/20');
                document.getElementById('btn-real_estate').classList.remove('border-slate-200', 'dark:border-slate-700');
            }
        }

        // Business form handler
        document.getElementById('businessForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            submitVerification(this, 'business');
        });

        // Captain form handler
        document.getElementById('captainForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            submitVerification(this, 'captain');
        });

        // Real Estate form handler
        document.getElementById('real_estateForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            submitVerification(this, 'real_estate');
        });

        function submitVerification(form, type) {
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?php echo $lang == 'en' ? 'Submitting...' : 'Gönderiliyor...'; ?>';

            fetch('api/verification.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show beautiful success message
                    showSuccessMessage(type);
                } else {
                    alert(data.message || '<?php echo $lang == 'en' ? 'An error occurred' : 'Bir hata oluştu'; ?>');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Request' : 'Başvuruyu Gönder'; ?>';
                }
            })
            .catch(err => {
                alert('<?php echo $lang == 'en' ? 'An error occurred' : 'Bir hata oluştu'; ?>');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> <?php echo $lang == 'en' ? 'Submit Request' : 'Başvuruyu Gönder'; ?>';
            });
        }

        function showSuccessMessage(type) {
            const typeColors = {
                'business': 'from-emerald-500 to-teal-500',
                'captain': 'from-cyan-500 to-blue-500',
                'real_estate': 'from-purple-500 to-pink-500'
            };
            
            const typeIcons = {
                'business': 'fa-store',
                'captain': 'fa-ship',
                'real_estate': 'fa-home'
            };

            const color = typeColors[type] || 'from-emerald-500 to-teal-500';
            const icon = typeIcons[type] || 'fa-check';

            // Replace page content with success message
            document.querySelector('main').innerHTML = `
                <div class="bg-gradient-to-br ${color} rounded-[3rem] p-12 text-white text-center shadow-2xl max-w-2xl mx-auto animate-fade-in">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas ${icon} text-5xl animate-bounce"></i>
                    </div>
                    <h1 class="text-4xl font-black mb-4">
                        <?php echo $lang == 'en' ? 'Thank You!' : 'Teşekkürler!'; ?>
                    </h1>
                    <p class="text-xl opacity-90 mb-6">
                        <?php echo $lang == 'en' 
                            ? 'Your verification request has been submitted to our team.' 
                            : 'Doğrulama talebiniz yetkililerimize iletildi.'; ?>
                    </p>
                    <div class="bg-white/20 rounded-2xl p-6 mb-8">
                        <p class="text-sm opacity-90 mb-2">
                            <i class="fas fa-clock mr-2"></i>
                            <?php echo $lang == 'en' 
                                ? 'Verification is typically completed within 24-48 hours.' 
                                : 'Doğrulama genellikle 24-48 saat içinde tamamlanır.'; ?>
                        </p>
                        <p class="text-sm opacity-75">
                            <?php echo $lang == 'en' 
                                ? 'You will be notified once your request is reviewed.' 
                                : 'Talebiniz incelendiğinde bilgilendirileceksiniz.'; ?>
                        </p>
                    </div>
                    <a href="index" class="inline-block bg-white text-slate-800 px-8 py-4 rounded-2xl font-black text-lg hover:scale-105 transition-transform shadow-lg">
                        <i class="fas fa-home mr-2"></i>
                        <?php echo $lang == 'en' ? 'Back to Home' : 'Ana Sayfaya Dön'; ?>
                    </a>
                </div>
            `;
        }

        // Show info message when food category is selected
        const categorySelect = document.querySelector('select[name="business_category"]');
        if (categorySelect) {
            categorySelect.addEventListener('change', function(e) {
                const val = e.target.value;
                const uploadSection = document.getElementById('menuUploadSection');
                if (uploadSection) {
                    if (['restaurant', 'cafe', 'bar'].includes(val)) {
                        uploadSection.classList.remove('hidden');
                    } else {
                        uploadSection.classList.add('hidden');
                    }
                }
            });
        }
    </script>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fade-in 0.5s ease-out; }
    </style>
</body>
</html>
