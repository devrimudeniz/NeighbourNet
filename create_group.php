<?php
require_once 'includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_badge = $_SESSION['badge'] ?? '';
$is_admin = in_array($user_badge, ['founder', 'admin', 'moderator']);

$error = '';
$success = '';

// Check user's group count (max 2 for non-admin)
$group_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE creator_id = ? AND status != 'rejected'");
$group_count_stmt->execute([$user_id]);
$user_group_count = (int)$group_count_stmt->fetchColumn();

$max_groups = $is_admin ? 999 : 2;
$can_create = $user_group_count < $max_groups;

// Check if user has a pending group
$pending_stmt = $pdo->prepare("SELECT id, name_tr, name_en FROM `groups` WHERE creator_id = ? AND status = 'pending' LIMIT 1");
$pending_stmt->execute([$user_id]);
$pending_group = $pending_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_create) {
    $name_tr = trim($_POST['name_tr'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $description_tr = trim($_POST['description_tr'] ?? '');
    $description_en = trim($_POST['description_en'] ?? '');
    $category = $_POST['category'] ?? 'hobby';
    $privacy = $_POST['privacy'] ?? 'public';
    
    // Validate category
    $allowed_cats = ['hobby', 'lifestyle', 'professional', 'marketplace'];
    if (!in_array($category, $allowed_cats)) $category = 'hobby';
    
    // Validate privacy
    if (!in_array($privacy, ['public', 'private'])) $privacy = 'public';
    
    if (empty($name_tr) || empty($name_en)) {
        $error = $lang == 'en' ? 'Group name is required in both languages' : 'Grup adı her iki dilde de gereklidir';
    } elseif (strlen($name_tr) < 3 || strlen($name_en) < 3) {
        $error = $lang == 'en' ? 'Group name must be at least 3 characters' : 'Grup adı en az 3 karakter olmalı';
    } elseif (strlen($name_tr) > 100 || strlen($name_en) > 100) {
        $error = $lang == 'en' ? 'Group name too long (max 100 chars)' : 'Grup adı çok uzun (maks 100 karakter)';
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name_en)));
        
        $check = $pdo->prepare("SELECT id FROM `groups` WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            $slug .= '-' . time();
        }
        
        // Admin creates approved, regular users create pending
        $status = $is_admin ? 'approved' : 'pending';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO `groups` (name_tr, name_en, slug, description_tr, description_en, category, privacy, status, creator_id, member_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name_tr, $name_en, $slug, $description_tr, $description_en, $category, $privacy, $status, $user_id]);
            
            $group_id = $pdo->lastInsertId();
            
            // Add creator as admin member
            $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')")
                ->execute([$group_id, $user_id]);
            
            if ($is_admin) {
                // Admin bypasses approval
                header("Location: group_detail?id=" . $group_id);
                exit();
            } else {
                $success = 'pending';
            }
            
        } catch (PDOException $e) {
            $error = $lang == 'en' ? 'Error creating group' : 'Grup oluşturulurken hata';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Create Group' : 'Grup Oluştur'; ?> | Kalkan Social</title>
    <link rel="icon" href="/logo.jpg">
    <?php include 'includes/header_css.php'; ?>
    <style>body { font-family: 'Outfit', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen">

    <?php include 'includes/header.php'; ?>

    <div class="max-w-xl mx-auto px-4 pt-24 pb-24">
        
        <!-- Header -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <a href="groups" style="width:36px;height:36px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;font-size:14px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 style="font-size:22px;font-weight:900;margin:0;"><?php echo $lang == 'en' ? 'Create Group' : 'Grup Oluştur'; ?></h1>
                <?php if(!$is_admin): ?>
                <p style="font-size:12px;color:#94a3b8;margin:2px 0 0;"><?php echo $user_group_count; ?>/<?php echo $max_groups; ?> <?php echo $lang == 'en' ? 'groups used' : 'grup hakkı kullanıldı'; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($success === 'pending'): ?>
        <!-- Success: Pending Approval -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;text-align:center;">
            <div style="width:64px;height:64px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-hourglass-half" style="font-size:24px;color:#f59e0b;"></i>
            </div>
            <h2 style="font-size:20px;font-weight:900;margin:0 0 8px;"><?php echo $lang == 'en' ? 'Group Submitted!' : 'Grup Gönderildi!'; ?></h2>
            <p style="font-size:14px;color:#64748b;line-height:1.6;margin:0 0 20px;">
                <?php echo $lang == 'en' 
                    ? 'Your group has been submitted for review. Our administrators will review and approve your group shortly. You will be notified once it is approved.'
                    : 'Grubunuz incelenmek üzere gönderildi. Yöneticilerimiz grubunuzu en kısa sürede inceleyip onaylayacaktır. Onaylandığında bilgilendirileceksiniz.'; ?>
            </p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <a href="groups" style="padding:10px 20px;border-radius:10px;background:#0f172a;color:#fff;font-size:13px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-arrow-left" style="margin-right:6px;"></i><?php echo $lang == 'en' ? 'Back to Groups' : 'Gruplara Dön'; ?>
                </a>
            </div>
        </div>

        <?php elseif (!$can_create): ?>
        <!-- Max Limit Reached -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;text-align:center;">
            <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-ban" style="font-size:24px;color:#ef4444;"></i>
            </div>
            <h2 style="font-size:20px;font-weight:900;margin:0 0 8px;"><?php echo $lang == 'en' ? 'Group Limit Reached' : 'Grup Limitine Ulaşıldı'; ?></h2>
            <p style="font-size:14px;color:#64748b;line-height:1.6;margin:0 0 20px;">
                <?php echo $lang == 'en' 
                    ? 'You can create a maximum of 2 groups. You have already reached this limit.'
                    : 'En fazla 2 grup oluşturabilirsiniz. Bu limite zaten ulaştınız.'; ?>
            </p>
            <a href="groups" style="padding:10px 20px;border-radius:10px;background:#0f172a;color:#fff;font-size:13px;font-weight:700;text-decoration:none;display:inline-block;">
                <i class="fas fa-arrow-left" style="margin-right:6px;"></i><?php echo $lang == 'en' ? 'Back to Groups' : 'Gruplara Dön'; ?>
            </a>
        </div>

        <?php else: ?>
        <!-- Pending Info Banner -->
        <?php if($pending_group && !$is_admin): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;margin-bottom:20px;">
            <i class="fas fa-clock" style="color:#f59e0b;font-size:16px;flex-shrink:0;"></i>
            <div style="flex:1;min-width:0;">
                <p style="font-size:13px;color:#92400e;margin:0;font-weight:600;">
                    <?php echo $lang == 'en' 
                        ? '"' . htmlspecialchars($lang == 'en' ? $pending_group['name_en'] : $pending_group['name_tr']) . '" is waiting for approval.'
                        : '"' . htmlspecialchars($lang == 'tr' ? $pending_group['name_tr'] : $pending_group['name_en']) . '" onay bekliyor.'; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Approval Notice (for non-admins) -->
        <?php if(!$is_admin): ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;margin-bottom:20px;">
            <i class="fas fa-info-circle" style="color:#3b82f6;font-size:16px;flex-shrink:0;margin-top:1px;"></i>
            <p style="font-size:13px;color:#1e40af;margin:0;line-height:1.5;">
                <?php echo $lang == 'en'
                    ? 'Groups are reviewed by administrators before being published. This process usually takes a short time.'
                    : 'Gruplar yayınlanmadan önce yöneticiler tarafından incelenir. Bu işlem genellikle kısa sürer.'; ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;" class="dark:bg-slate-800 dark:border-slate-700">

            <?php if ($error): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:20px;">
                <i class="fas fa-exclamation-circle" style="color:#ef4444;"></i>
                <span style="font-size:13px;color:#dc2626;font-weight:600;"><?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Group Names -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;">Grup Adı (TR) *</label>
                        <input type="text" name="name_tr" required maxlength="100"
                            placeholder="örn. Kalkan Dalış Ekibi" value="<?php echo htmlspecialchars($_POST['name_tr'] ?? ''); ?>"
                            style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;font-size:14px;outline:none;box-sizing:border-box;"
                            class="dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;">Group Name (EN) *</label>
                        <input type="text" name="name_en" required maxlength="100"
                            placeholder="e.g. Kalkan Diving Team" value="<?php echo htmlspecialchars($_POST['name_en'] ?? ''); ?>"
                            style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;font-size:14px;outline:none;box-sizing:border-box;"
                            class="dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                    </div>
                </div>

                <!-- Descriptions -->
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;">Açıklama (TR)</label>
                    <textarea name="description_tr" rows="2" maxlength="500"
                        placeholder="Grubunuz hakkında kısa bilgi..."
                        style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;font-size:14px;outline:none;resize:none;box-sizing:border-box;"
                        class="dark:bg-slate-700 dark:border-slate-600 dark:text-white"><?php echo htmlspecialchars($_POST['description_tr'] ?? ''); ?></textarea>
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;">Description (EN)</label>
                    <textarea name="description_en" rows="2" maxlength="500"
                        placeholder="Brief info about your group..."
                        style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;font-size:14px;outline:none;resize:none;box-sizing:border-box;"
                        class="dark:bg-slate-700 dark:border-slate-600 dark:text-white"><?php echo htmlspecialchars($_POST['description_en'] ?? ''); ?></textarea>
                </div>

                <!-- Category & Privacy in same row -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;"><?php echo $lang == 'en' ? 'Category' : 'Kategori'; ?> *</label>
                        <select name="category" required
                            style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;font-size:14px;outline:none;box-sizing:border-box;background:#fff;"
                            class="dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            <option value="hobby">🎯 <?php echo $t['hobby']; ?></option>
                            <option value="lifestyle">🌟 <?php echo $t['lifestyle']; ?></option>
                            <option value="professional">💼 <?php echo $t['professional']; ?></option>
                            <option value="marketplace">🛒 <?php echo $t['marketplace']; ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px;"><?php echo $lang == 'en' ? 'Privacy' : 'Gizlilik'; ?> *</label>
                        <select name="privacy" required
                            style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0;font-size:14px;outline:none;box-sizing:border-box;background:#fff;"
                            class="dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            <option value="public">🌐 <?php echo $lang == 'en' ? 'Public' : 'Herkese Açık'; ?></option>
                            <option value="private">🔒 <?php echo $lang == 'en' ? 'Private' : 'Gizli'; ?></option>
                        </select>
                    </div>
                </div>

                <!-- Submit -->
                <div style="display:flex;gap:10px;">
                    <button type="submit" style="flex:1;padding:12px;border-radius:10px;background:#0f172a;color:#fff;border:none;font-size:14px;font-weight:800;cursor:pointer;">
                        <i class="fas fa-paper-plane" style="margin-right:6px;"></i>
                        <?php echo $is_admin 
                            ? ($lang == 'en' ? 'Create Group' : 'Grup Oluştur')
                            : ($lang == 'en' ? 'Submit for Review' : 'İncelemeye Gönder'); ?>
                    </button>
                    <a href="groups" style="padding:12px 20px;border-radius:10px;background:#f1f5f9;color:#64748b;font-size:14px;font-weight:700;text-decoration:none;display:flex;align-items:center;">
                        <?php echo $lang == 'en' ? 'Cancel' : 'İptal'; ?>
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
