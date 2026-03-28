<?php
require_once '../includes/db.php';
require_once 'auth_session.php';
require_once '../includes/lang.php';
require_once '../includes/site_settings.php';

$settings = load_site_settings($pdo);
$success = '';
$error = '';

$fields = [
    'site_name' => ['label' => 'Site adi', 'max' => 120],
    'site_short_name' => ['label' => 'Kisa ad', 'max' => 40],
    'site_tagline_tr' => ['label' => 'Turkce slogan', 'max' => 190],
    'site_tagline_en' => ['label' => 'English tagline', 'max' => 190],
    'support_email' => ['label' => 'Destek e-postasi', 'max' => 190],
    'contact_phone' => ['label' => 'Iletisim telefonu', 'max' => 50],
    'app_url' => ['label' => 'Site URL', 'max' => 190],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Gecersiz oturum anahtari. Sayfayi yenileyip tekrar deneyin.';
    } else {
        $payload = [];

        foreach ($fields as $key => $meta) {
            $value = trim((string) ($_POST[$key] ?? ''));
            $payload[$key] = mb_substr($value, 0, $meta['max']);
        }

        if ($payload['site_name'] === '') {
            $error = 'Site adi bos birakilamaz.';
        } elseif ($payload['site_short_name'] === '') {
            $error = 'Kisa ad bos birakilamaz.';
        } elseif ($payload['support_email'] !== '' && !filter_var($payload['support_email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Destek e-postasi gecerli degil.';
        } elseif ($payload['app_url'] !== '' && !filter_var($payload['app_url'], FILTER_VALIDATE_URL)) {
            $error = 'Site URL alani gecerli degil.';
        } else {
            ensure_site_settings_table($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($payload as $key => $value) {
                $stmt->execute([$key, $value]);
            }

            $settings = array_merge($settings, $payload);
            $GLOBALS['site_settings'] = $settings;
            $success = 'Site ayarlari guncellendi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Ayarlari | <?php echo htmlspecialchars(site_name()); ?></title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:28px;">
            <div>
                <h1 class="page-title" style="margin:0 0 6px;">Site Ayarlari</h1>
                <p style="margin:0;color:#64748b;font-size:14px;max-width:720px;">
                    Marka adini, kisa adi, sloganlari ve ana site adresini buradan yonetin. Domain baglantilari bu ayardan okunur.
                </p>
            </div>
        </div>

        <?php if ($success): ?>
            <div style="margin-bottom:18px;background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;padding:14px 16px;border-radius:12px;font-size:14px;font-weight:700;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="margin-bottom:18px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 16px;border-radius:12px;font-size:14px;font-weight:700;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="admin-card" style="max-width:920px;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

            <div style="margin-bottom:20px;padding:16px 18px;border-radius:14px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;">
                <div style="font-size:13px;font-weight:800;margin-bottom:6px;">Kurulum ipucu</div>
                <div style="font-size:13px;line-height:1.6;">
                    Site URL alanina tam adresinizi yazin. Ornek: `http://localhost/NeighbourNet` veya `https://neighbournet.com`
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;">
                <?php foreach ($fields as $key => $meta): ?>
                    <label style="display:flex;flex-direction:column;gap:8px;">
                        <span style="font-size:13px;font-weight:800;color:#334155;"><?php echo htmlspecialchars($meta['label']); ?></span>
                        <input
                            type="<?php echo $key === 'support_email' ? 'email' : ($key === 'app_url' ? 'url' : 'text'); ?>"
                            name="<?php echo htmlspecialchars($key); ?>"
                            value="<?php echo htmlspecialchars($settings[$key] ?? ''); ?>"
                            maxlength="<?php echo (int) $meta['max']; ?>"
                            style="height:48px;border:1px solid #cbd5e1;border-radius:12px;padding:0 14px;font-size:14px;font-weight:600;color:#0f172a;outline:none;"
                        >
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:24px;padding:18px;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0;">
                <div style="font-size:13px;font-weight:800;color:#334155;margin-bottom:8px;">Onizleme</div>
                <div style="font-size:18px;font-weight:900;color:#0f172a;"><?php echo htmlspecialchars($settings['site_name'] ?? site_name()); ?></div>
                <div style="font-size:14px;color:#64748b;"><?php echo htmlspecialchars($settings['site_tagline_tr'] ?? site_tagline('tr')); ?></div>
                <div style="margin-top:10px;font-size:12px;color:#94a3b8;">Manifest kisa adi: <?php echo htmlspecialchars($settings['site_short_name'] ?? site_short_name()); ?></div>
                <div style="margin-top:6px;font-size:12px;color:#94a3b8;">Site URL: <?php echo htmlspecialchars($settings['app_url'] ?? site_url()); ?></div>
            </div>

            <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-top:24px;">
                <a href="index" style="display:inline-flex;align-items:center;justify-content:center;height:46px;padding:0 18px;border-radius:12px;background:#e2e8f0;color:#334155;text-decoration:none;font-size:13px;font-weight:800;">
                    Vazgec
                </a>
                <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;height:46px;padding:0 20px;border:none;border-radius:12px;background:#0f172a;color:#fff;font-size:13px;font-weight:800;cursor:pointer;">
                    <i class="fas fa-save" style="margin-right:8px;"></i> Ayarlari Kaydet
                </button>
            </div>
        </form>
    </main>
</body>
</html>
