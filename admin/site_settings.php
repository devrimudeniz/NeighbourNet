<?php
require_once '../includes/db.php';
require_once 'auth_session.php';
require_once '../includes/lang.php';
require_once '../includes/site_settings.php';

$settings = load_site_settings($pdo);
$success = '';
$error = '';

$fields = [
    'site_name' => ['label' => 'Site adı', 'max' => 120],
    'site_short_name' => ['label' => 'Kısa ad', 'max' => 40],
    'site_tagline_tr' => ['label' => 'Türkçe slogan', 'max' => 190],
    'site_tagline_en' => ['label' => 'English tagline', 'max' => 190],
    'support_email' => ['label' => 'Destek e-postası', 'max' => 190],
    'contact_phone' => ['label' => 'İletişim telefonu', 'max' => 50],
    'app_url' => ['label' => 'Uygulama URL', 'max' => 190],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz oturum anahtarı. Sayfayı yenileyip tekrar deneyin.';
    } else {
        $payload = [];

        foreach ($fields as $key => $meta) {
            $value = trim((string) ($_POST[$key] ?? ''));
            $payload[$key] = mb_substr($value, 0, $meta['max']);
        }

        if ($payload['site_name'] === '') {
            $error = 'Site adı boş bırakılamaz.';
        } elseif ($payload['site_short_name'] === '') {
            $error = 'Kısa ad boş bırakılamaz.';
        } elseif ($payload['support_email'] !== '' && !filter_var($payload['support_email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Destek e-postası geçerli değil.';
        } elseif ($payload['app_url'] !== '' && !filter_var($payload['app_url'], FILTER_VALIDATE_URL)) {
            $error = 'Uygulama URL alanı geçerli değil.';
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

            try {
                $log = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_type, details) VALUES (?, ?, ?, ?)");
                $log->execute([
                    $_SESSION['user_id'],
                    'Updated Site Settings',
                    'settings',
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            } catch (Exception $e) {
                // Optional log table.
            }

            $settings = array_merge($settings, $payload);
            $GLOBALS['site_settings'] = $settings;
            $success = 'Site ayarları güncellendi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Ayarları | <?php echo htmlspecialchars(site_name()); ?></title>
    <link rel="icon" href="/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body style="margin:0;font-family:'Outfit',system-ui,sans-serif;">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:28px;">
            <div>
                <h1 class="page-title" style="margin:0 0 6px;">Site Ayarları</h1>
                <p style="margin:0;color:#64748b;font-size:14px;max-width:720px;">
                    Repo paylaşımı için marka, kısa ad, slogan ve temel iletişim bilgilerini buradan düzenleyebilirsiniz.
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
                <div style="font-size:13px;font-weight:800;color:#334155;margin-bottom:8px;">Önizleme</div>
                <div style="font-size:18px;font-weight:900;color:#0f172a;"><?php echo htmlspecialchars($settings['site_name'] ?? site_name()); ?></div>
                <div style="font-size:14px;color:#64748b;"><?php echo htmlspecialchars($settings['site_tagline_tr'] ?? site_tagline('tr')); ?></div>
                <div style="margin-top:10px;font-size:12px;color:#94a3b8;">Manifest kısa adı: <?php echo htmlspecialchars($settings['site_short_name'] ?? site_short_name()); ?></div>
            </div>

            <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-top:24px;">
                <a href="index" style="display:inline-flex;align-items:center;justify-content:center;height:46px;padding:0 18px;border-radius:12px;background:#e2e8f0;color:#334155;text-decoration:none;font-size:13px;font-weight:800;">
                    Vazgeç
                </a>
                <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;height:46px;padding:0 20px;border:none;border-radius:12px;background:#0f172a;color:#fff;font-size:13px;font-weight:800;cursor:pointer;">
                    <i class="fas fa-save" style="margin-right:8px;"></i> Ayarları Kaydet
                </button>
            </div>
        </form>
    </main>
</body>
</html>
