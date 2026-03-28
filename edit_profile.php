<?php
require_once 'includes/bootstrap.php';
require_once 'includes/image_helper.php'; // Optimization helper

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Ensure uploads directory exists
$upload_dir = "uploads/profiles/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle File Uploads
function uploadImage($file, $type = 'avatar') {
    global $upload_dir, $user_id;
    
    if (!isset($file) || $file['error'] != 0) {
        return null;
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return null;
    }
    
    // Check file size (max 20MB - will be optimized)
    if ($file['size'] > 20 * 1024 * 1024) {
        return null;
    }
    
    // Resize dimensions based on type
    $maxWidth = ($type == 'avatar') ? 200 : 1080;
    $maxHeight = ($type == 'avatar') ? 200 : 1080;
    
    $new_name = $type . '_' . $user_id . '_' . uniqid() . '.webp';
    $target_file = $upload_dir . $new_name;
    
    // Optimize and convert to WebP
    $result = optimizeImage($file['tmp_name'], $target_file, $maxWidth, $maxHeight);
    
    if ($result) {
        return $result;
    }
    
    return null;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? null;
    $relationship_status = $_POST['relationship_status'] ?? null;
    $website = trim($_POST['website'] ?? '');
    $phone_visibility = $_POST['phone_visibility'] ?? 'private';
    $email_visibility = $_POST['email_visibility'] ?? 'private';
    
    // Business specific
    $venue_name = trim($_POST['venue_name'] ?? '');
    $facebook_link = trim($_POST['facebook_link'] ?? '');
    $instagram_link = trim($_POST['instagram_link'] ?? '');
    
    // Handle avatar upload
    $avatar = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $avatar = uploadImage($_FILES['avatar'], 'avatar');
    }
    
    // Handle cover photo upload
    $cover_photo = null;
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
        $cover_photo = uploadImage($_FILES['cover_photo'], 'cover');
    }
    
    // Build update query
    $updates = [];
    $params = [];
    
    if (!empty($full_name)) {
        $updates[] = "full_name = ?";
        $params[] = $full_name;
    }
    
    if ($bio !== null) {
        $updates[] = "bio = ?";
        $params[] = $bio;
    }
    
    if ($location !== null) {
        $updates[] = "location = ?";
        $params[] = $location;
    }
    
    if ($birth_date !== null) {
        $updates[] = "birth_date = ?";
        $params[] = $birth_date;
    }
    
    if ($avatar) {
        $updates[] = "avatar = ?";
        $params[] = $avatar;
    }
    
    if ($cover_photo) {
        $updates[] = "cover_photo = ?";
        $params[] = $cover_photo;
    }
    
    // Add new fields if they exist in database
    try {
        // Check if columns exist and add them if needed
        $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if (!$check_columns->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER birth_date");
        }
        
        $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
        if (!$check_columns->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER phone");
        }
        
        $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'gender'");
        if (!$check_columns->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT NULL AFTER email");
        }
        
        $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'relationship_status'");
        if (!$check_columns->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN relationship_status ENUM('single', 'in_relationship', 'married', 'complicated', 'prefer_not_to_say') DEFAULT NULL AFTER gender");
        }
        
        $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'website'");
        if (!$check_columns->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN website VARCHAR(255) DEFAULT NULL AFTER relationship_status");
        }
        
        $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_visibility'");
        if (!$check_columns->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone_visibility ENUM('public', 'friends', 'private') DEFAULT 'private' AFTER phone");
        }
        
        $check_columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_visibility'");
        if (!$check_columns->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_visibility ENUM('public', 'friends', 'private') DEFAULT 'private' AFTER email");
        }
    } catch (PDOException $e) {
        // Columns might already exist, continue
    }
    
    if ($phone !== null) {
        $updates[] = "phone = ?";
        $params[] = $phone;
    }
    
    if ($email !== null) {
        $updates[] = "email = ?";
        $params[] = $email;
    }
    
    if ($gender !== null) {
        $updates[] = "gender = ?";
        $params[] = $gender;
    }
    
    if ($relationship_status !== null) {
        $updates[] = "relationship_status = ?";
        $params[] = $relationship_status;
    }
    
    if ($website !== null) {
        $updates[] = "website = ?";
        $params[] = $website;
    }
    
    if ($phone_visibility !== null) {
        $updates[] = "phone_visibility = ?";
        $params[] = $phone_visibility;
    }
    
    if ($email_visibility !== null) {
        $updates[] = "email_visibility = ?";
        $params[] = $email_visibility;
    }

    if (!empty($venue_name)) {
        $updates[] = "venue_name = ?";
        $params[] = $venue_name;
    }

    if ($facebook_link !== null) {
        $updates[] = "facebook_link = ?";
        $params[] = $facebook_link;
    }

    if ($instagram_link !== null) {
        $updates[] = "instagram_link = ?";
        $params[] = $instagram_link;
    }
    
    if (!empty($updates)) {
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            $message = $t['update_success'];
            // Update session
            if (!empty($full_name)) {
                $_SESSION['full_name'] = $full_name;
            }
            if ($avatar) {
                $_SESSION['avatar'] = $avatar;
            }
        } else {
            $error = $t['update_error'];
        }
    }
}

// Get User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: profile");
    exit();
}

$is_business = ($user['role'] == 'venue' || in_array($user['badge'] ?? '', ['business', 'verified_business', 'vip_business']));
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['edit_profile_title']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .image-preview { max-width: 100%; max-height: 200px; object-fit: cover; }
        .cover-preview { width: 100%; height: 200px; object-fit: cover; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen transition-colors duration-300">

    <!-- Header -->
    <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="profile" class="flex items-center gap-3 text-slate-600 dark:text-slate-400 hover:text-pink-500 transition-colors">
                <?php echo heroicon('arrow_left', 'w-5 h-5'); ?>
                <span class="font-bold"><?php echo $t['edit_profile_title']; ?></span>
            </a>
            <a href="profile" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                <?php echo $t['cancel']; ?>
            </a>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        
        <?php if ($message): ?>
            <div class="bg-green-100 dark:bg-green-900/30 border border-green-500/50 text-green-600 dark:text-green-400 p-4 rounded-xl mb-6 font-bold text-center">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 dark:bg-red-900/30 border border-red-500/50 text-red-600 dark:text-red-400 p-4 rounded-xl mb-6 font-bold text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <!-- Cover Photo Section -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="relative h-48 bg-gradient-to-r from-cyan-500 to-blue-500">
                    <?php if($user['cover_photo']): ?>
                        <img src="<?php echo htmlspecialchars($user['cover_photo']); ?>" id="cover-preview" class="cover-preview w-full h-full object-cover">
                    <?php else: ?>
                        <div id="cover-placeholder" class="w-full h-full bg-gradient-to-r from-cyan-500 to-blue-500"></div>
                    <?php endif; ?>
                    <label class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/50 cursor-pointer transition-colors group">
                        <div class="text-center text-white">
                            <div class="flex justify-center mb-2 group-hover:scale-110 transition-transform"><?php echo heroicon('camera', 'w-8 h-8'); ?></div>
                            <p class="text-sm font-bold"><?php echo $t['cover_photo_add']; ?></p>
                        </div>
                        <input type="file" name="cover_photo" id="cover_photo" accept="image/*" class="hidden" onchange="previewCover(this)">
                    </label>
                </div>
            </div>

            <!-- Profile Photo Section -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center gap-6">
                    <div class="relative">
                        <div class="w-32 h-32 rounded-full border-4 border-white dark:border-slate-900 overflow-hidden shadow-xl bg-slate-200 dark:bg-slate-700">
                            <img src="<?php echo htmlspecialchars($user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'])); ?>" id="avatar-preview" class="w-full h-full object-cover">
                        </div>
                        <label class="absolute bottom-0 right-0 bg-pink-500 text-white w-10 h-10 rounded-full flex items-center justify-center cursor-pointer hover:bg-pink-600 transition-colors shadow-lg">
                            <?php echo heroicon('camera', 'w-4 h-4'); ?>
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                        </label>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold mb-2"><?php echo $t['profile_photo_title']; ?></h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4"><?php echo $t['profile_photo_desc']; ?></p>
                        <label for="avatar" class="inline-block bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-4 py-2 rounded-lg text-sm font-bold cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors flex items-center gap-2 w-fit">
                            <?php echo heroicon('arrow_up_tray', 'w-4 h-4'); ?><?php echo $t['select_photo']; ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <?php echo heroicon('user', 'text-pink-500 w-5 h-5'); ?>
                    <?php echo $t['basic_info']; ?>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['full_name_label']; ?> *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['username_label']; ?></label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="w-full bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-500 dark:text-slate-400 cursor-not-allowed">
                        <p class="text-xs text-slate-400 mt-1"><?php echo $t['username_change_locked']; ?></p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['bio']; ?></label>
                        <textarea name="bio" rows="4" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors resize-none" placeholder="<?php echo $t['bio_placeholder']; ?>"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['location_map']; ?> <span class="font-normal text-slate-400">(<?php echo $lang == 'tr' ? 'opsiyonel' : 'optional'; ?>)</span></label>
                        <input type="text" name="location" id="location-input" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors mb-4" placeholder="Kalkan, Antalya">
                        
                        <!-- Live Map Preview -->
                        <div class="h-48 rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 relative border border-slate-200 dark:border-slate-600">
                            <iframe id="map-preview" width="100%" height="100%" frameborder="0" style="border:0"
                                src="https://maps.google.com/maps?q=<?php echo urlencode($user['location'] ?? 'Kalkan, Turkey'); ?>&output=embed">
                            </iframe>
                        </div>
                    </div>

            <!-- Business Information (Conditional) -->
             <?php if($is_business): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <?php echo heroicon('store', 'text-pink-500 w-5 h-5'); ?>
                    İşletme Ayarları
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['venue_name']; ?></label>
                        <input type="text" name="venue_name" value="<?php echo htmlspecialchars($user['venue_name'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors font-bold text-lg" placeholder="<?php echo $t['venue_name_placeholder']; ?>">
                    </div>
                </div>
            </div>
            <?php endif; ?>

                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['website']; ?></label>
                        <input type="url" name="website" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors" placeholder="https://...">
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <?php echo heroicon('information_circle', 'text-pink-500 w-5 h-5'); ?>
                    <?php echo $t['personal_info']; ?>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['birth_date']; ?></label>
                        <input type="date" name="birth_date" value="<?php echo $user['birth_date'] ?? ''; ?>" max="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['gender']; ?></label>
                        <select name="gender" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors">
                            <option value=""><?php echo $t['gender_select']; ?></option>
                            <option value="male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>><?php echo $t['gender_male']; ?></option>
                            <option value="female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>><?php echo $t['gender_female']; ?></option>
                            <option value="other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>><?php echo $t['gender_other']; ?></option>
                            <option value="prefer_not_to_say" <?php echo ($user['gender'] ?? '') == 'prefer_not_to_say' ? 'selected' : ''; ?>><?php echo $t['gender_prefer_not_to_say']; ?></option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold"><?php echo $t['relationship_status']; ?></label>
                        <select name="relationship_status" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors">
                            <option value=""><?php echo $t['gender_select']; ?></option>
                            <option value="single" <?php echo ($user['relationship_status'] ?? '') == 'single' ? 'selected' : ''; ?>><?php echo $t['rel_single']; ?></option>
                            <option value="in_relationship" <?php echo ($user['relationship_status'] ?? '') == 'in_relationship' ? 'selected' : ''; ?>><?php echo $t['rel_in_relationship']; ?></option>
                            <option value="married" <?php echo ($user['relationship_status'] ?? '') == 'married' ? 'selected' : ''; ?>><?php echo $t['rel_married']; ?></option>
                            <option value="complicated" <?php echo ($user['relationship_status'] ?? '') == 'complicated' ? 'selected' : ''; ?>><?php echo $t['rel_complicated']; ?></option>
                            <option value="prefer_not_to_say" <?php echo ($user['relationship_status'] ?? '') == 'prefer_not_to_say' ? 'selected' : ''; ?>><?php echo $t['gender_prefer_not_to_say']; ?></option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold">Telefon</label>
                        <div class="flex gap-2">
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="flex-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors" placeholder="+90 555 123 45 67">
                            <select name="phone_visibility" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-3 focus:outline-none focus:border-pink-500 transition-colors text-sm" title="Gizlilik Ayarı">
                                <option value="public" <?php echo ($user['phone_visibility'] ?? 'private') == 'public' ? 'selected' : ''; ?> title="Herkes görebilir">🌐</option>
                                <option value="friends" <?php echo ($user['phone_visibility'] ?? 'private') == 'friends' ? 'selected' : ''; ?> title="Arkadaşlar görebilir">👥</option>
                                <option value="private" <?php echo ($user['phone_visibility'] ?? 'private') == 'private' ? 'selected' : ''; ?> title="Sadece ben görebilirim">🔒</option>
                            </select>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <?php echo heroicon('information_circle', 'w-3 h-3'); ?>
                            <span id="phone-visibility-text"><?php echo $t['visibility_private']; ?></span>
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 font-bold">E-posta</label>
                        <div class="flex gap-2">
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="flex-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-pink-500 transition-colors" placeholder="ornek@email.com">
                            <select name="email_visibility" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-3 focus:outline-none focus:border-pink-500 transition-colors text-sm" title="Gizlilik Ayarı">
                                <option value="public" <?php echo ($user['email_visibility'] ?? 'private') == 'public' ? 'selected' : ''; ?> title="Herkes görebilir">🌐</option>
                                <option value="friends" <?php echo ($user['email_visibility'] ?? 'private') == 'friends' ? 'selected' : ''; ?> title="Arkadaşlar görebilir">👥</option>
                                <option value="private" <?php echo ($user['email_visibility'] ?? 'private') == 'private' ? 'selected' : ''; ?> title="Sadece ben görebilirim">🔒</option>
                            </select>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                            <?php echo heroicon('information_circle', 'w-3 h-3'); ?>
                            <span id="email-visibility-text"><?php echo $t['visibility_private']; ?></span>
                        </p>
                    </div>

                    <div class="md:col-span-2 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <h4 class="text-xs uppercase tracking-widest text-pink-500 mb-4 font-bold flex items-center gap-2">
                             <?php echo heroicon('share', 'w-3 h-3'); ?> Sosyal Medya Hesapları
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-bold">Facebook</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-blue-600"><i class="fab fa-facebook"></i></span>
                                    <input type="url" name="facebook_link" value="<?php echo htmlspecialchars($user['facebook_link'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-blue-500 transition-colors text-sm" placeholder="https://facebook.com/kullanici">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-bold">Instagram</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-pink-500"><i class="fab fa-instagram"></i></span>
                                    <input type="url" name="instagram_link" value="<?php echo htmlspecialchars($user['instagram_link'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-pink-500 transition-colors text-sm" placeholder="https://instagram.com/kullanici">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <button type="submit" class="flex-1 bg-gradient-to-r from-pink-600 to-violet-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transform active:scale-95 transition-all flex items-center justify-center gap-2">
                    <?php echo heroicon('check', 'w-5 h-5'); ?><?php echo $t['save_changes']; ?>
                </button>
                <a href="profile" class="px-6 py-4 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors flex items-center">
                    <?php echo $t['cancel']; ?>
                </a>
            </div>

        </form>
    </div>

    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewCover(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const placeholder = document.getElementById('cover-placeholder');
                    if (placeholder) placeholder.style.display = 'none';
                    
                    let preview = document.getElementById('cover-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'cover-preview';
                        preview.className = 'cover-preview w-full h-full object-cover';
                        input.closest('.relative').querySelector('label').before(preview);
                    }
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // File size validation (Increased to 20MB for auto-optimization)
        document.getElementById('avatar').addEventListener('change', function(e) {
            if (e.target.files[0] && e.target.files[0].size > 20 * 1024 * 1024) {
                alert('Dosya boyutu 20MB\'dan büyük olamaz!');
                e.target.value = '';
            } else if (e.target.files[0] && e.target.files[0].size > 5 * 1024 * 1024) {
                console.log('Büyük dosya algılandı, optimize edilecek...');
            }
        });

        document.getElementById('cover_photo').addEventListener('change', function(e) {
            if (e.target.files[0] && e.target.files[0].size > 20 * 1024 * 1024) {
                alert('Dosya boyutu 20MB\'dan büyük olamaz!');
                e.target.value = '';
            } else if (e.target.files[0] && e.target.files[0].size > 5 * 1024 * 1024) {
                console.log('Büyük dosya algılandı, optimize edilecek...');
            }
        });

        // Visibility text updates
        const visibilityTexts = {
            'public': 'Herkes görebilir',
            'friends': 'Arkadaşlar görebilir',
            'private': 'Sadece sen görebilirsin'
        };

        const phoneVisibility = document.querySelector('select[name="phone_visibility"]');
        const emailVisibility = document.querySelector('select[name="email_visibility"]');
        const phoneVisibilityText = document.getElementById('phone-visibility-text');
        const emailVisibilityText = document.getElementById('email-visibility-text');

        function updateVisibilityText(select, textElement) {
            textElement.textContent = visibilityTexts[select.value] || 'Sadece sen görebilirsin';
        }

        phoneVisibility.addEventListener('change', function() {
            updateVisibilityText(this, phoneVisibilityText);
        });
        emailVisibility.addEventListener('change', function() {
            updateVisibilityText(this, emailVisibilityText);
        });

        // Initialize on page load
        updateVisibilityText(phoneVisibility, phoneVisibilityText);
        updateVisibilityText(emailVisibility, emailVisibilityText);

        // Live Map Update
        const locationInput = document.getElementById('location-input');
        const mapPreview = document.getElementById('map-preview');
        let timeout = null;

        locationInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const query = this.value.trim();
                if (query.length > 2) {
                    mapPreview.src = `https://maps.google.com/maps?q=${encodeURIComponent(query)}&output=embed`;
                }
            }, 1000); // Wait 1s after typing stops to avoid too many requests
        });
    </script>

</body>
</html>
