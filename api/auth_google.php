<?php
/**
 * Google OAuth Callback Handler
 * Handles Google Sign-In authentication
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/oauth_config.php';

$redirect_base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'kalkansocial.com');

// Step 1: If no code, redirect to Google
if (!isset($_GET['code'])) {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    
    $auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit();
}

// Step 2: Exchange code for access token
$code = $_GET['code'];

$token_url = 'https://oauth2.googleapis.com/token';
$token_data = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$token_response = curl_exec($ch);
curl_close($ch);

$token_info = json_decode($token_response, true);

if (!isset($token_info['access_token'])) {
    error_log('Google OAuth Error: ' . $token_response);
    header('Location: ../login.php?error=google_auth_failed');
    exit();
}

// Step 3: Get user info from Google
$access_token = $token_info['access_token'];
$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_info_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$user_response = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($user_response, true);

if (!isset($google_user['email'])) {
    error_log('Google User Info Error: ' . $user_response);
    header('Location: ../login.php?error=google_user_failed');
    exit();
}

// Step 4: Check if user exists or create new one
$email = $google_user['email'];
$full_name = $google_user['name'] ?? '';
$avatar = $google_user['picture'] ?? '';
$google_id = $google_user['id'];

// PHASE 1: Try finding by Google ID first (immutable identifier)
$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
$stmt->execute([$google_id]);
$user = $stmt->fetch();

// PHASE 2: If not found by Google ID, try finding by email
if (!$user) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // If found by email, link Google ID if not already linked to another account
    if ($user && empty($user['google_id'])) {
        $update = $pdo->prepare("UPDATE users SET google_id = ?, avatar = COALESCE(NULLIF(avatar, ''), ?) WHERE id = ?");
        $update->execute([$google_id, $avatar, $user['id']]);
        $user['google_id'] = $google_id; // Update local array for subsequent use
    }
}

if ($user) {
    // Set session (existing user)
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
    // Generate unique username from email
    $base_username = strtolower(explode('@', $email)[0]);
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
    
    // Random password (user will use Google to login)
    $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $insert = $pdo->prepare("INSERT INTO users (username, email, password, full_name, avatar, google_id, role, created_at) VALUES (?, ?, ?, ?, ?, ?, 'standard', NOW())");
    $insert->execute([$username, $email, $random_password, $full_name, $avatar, $google_id]);
    
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
