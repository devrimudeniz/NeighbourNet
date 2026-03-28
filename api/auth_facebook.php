<?php
/**
 * Facebook OAuth Callback Handler
 * Handles Facebook Login authentication
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/oauth_config.php';

$redirect_base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com');

// Step 1: If no code, redirect to Facebook
if (!isset($_GET['code'])) {
    $params = [
        'client_id' => FACEBOOK_APP_ID,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'scope' => 'email,public_profile',
        'response_type' => 'code',
        'state' => bin2hex(random_bytes(16)) // CSRF protection
    ];
    
    $_SESSION['fb_state'] = $params['state'];
    
    $auth_url = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit();
}

// Step 2: Exchange code for access token
$code = $_GET['code'];

$token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
$token_params = [
    'client_id' => FACEBOOK_APP_ID,
    'client_secret' => FACEBOOK_APP_SECRET,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'code' => $code
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url . '?' . http_build_query($token_params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$token_response = curl_exec($ch);
curl_close($ch);

$token_info = json_decode($token_response, true);

if (!isset($token_info['access_token'])) {
    error_log('Facebook OAuth Error: ' . $token_response);
    header('Location: ../login.php?error=facebook_auth_failed');
    exit();
}

// Step 3: Get user info from Facebook
$access_token = $token_info['access_token'];
$user_info_url = 'https://graph.facebook.com/v18.0/me?fields=id,name,email,picture.type(large)&access_token=' . $access_token;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_info_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$user_response = curl_exec($ch);
curl_close($ch);

$fb_user = json_decode($user_response, true);

if (!isset($fb_user['id'])) {
    error_log('Facebook User Info Error: ' . $user_response);
    header('Location: ../login.php?error=facebook_user_failed');
    exit();
}

// Step 4: Check if user exists or create new one
$facebook_id = $fb_user['id'];
$full_name = $fb_user['name'] ?? '';
$email = $fb_user['email'] ?? null;
$avatar = $fb_user['picture']['data']['url'] ?? '';

// Check if user exists by facebook_id or email
$stmt = $pdo->prepare("SELECT * FROM users WHERE facebook_id = ?" . ($email ? " OR email = ?" : ""));
$params = $email ? [$facebook_id, $email] : [$facebook_id];
$stmt->execute($params);
$user = $stmt->fetch();

if ($user) {
    // User exists - update facebook_id if not set and login
    if (empty($user['facebook_id'])) {
        $update = $pdo->prepare("UPDATE users SET facebook_id = ?, avatar = COALESCE(NULLIF(avatar, ''), ?) WHERE id = ?");
        $update->execute([$facebook_id, $avatar, $user['id']]);
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['avatar'] = $user['avatar'] ?: $avatar;
    $_SESSION['role'] = $user['role'];
    $_SESSION['badge'] = $user['badge'];
    $_SESSION['language'] = $user['preferred_language'] ?? 'tr';
    session_write_close();
    header('Location: ' . $redirect_base . '/index');
    exit();
} else {
    // Create new user
    // Generate unique username from name
    $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $full_name));
    if (empty($base_username)) $base_username = 'user';
    $username = $base_username;
    $counter = 1;
    
    // Ensure username is unique
    while (true) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if (!$check->fetch()) break;
        $username = $base_username . $counter;
        $counter++;
    }
    
    // Random password (user will use Facebook to login)
    $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    // Email might be null if user didn't grant email permission
    $insert = $pdo->prepare("INSERT INTO users (username, email, password, full_name, avatar, facebook_id, role, created_at) VALUES (?, ?, ?, ?, ?, ?, 'standard', NOW())");
    $insert->execute([$username, $email, $random_password, $full_name, $avatar, $facebook_id]);
    
    $new_user_id = $pdo->lastInsertId();
    
    // Set session
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['avatar'] = $avatar;
    $_SESSION['role'] = 'standard';
    $_SESSION['badge'] = null;
    $_SESSION['language'] = 'tr';
    $_SESSION['show_welcome_tour'] = true; // Show welcome tour for new users
    session_write_close();
    header('Location: ' . $redirect_base . '/index?registered=1');
    exit();
}
?>
