<?php
/**
 * Auth Helper - Persistent Login/Remember Me Functionality
 */

/**
 * Generate a secure remember token and save to database
 */
function createRememberToken($pdo, $user_id) {
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $hashed_validator = hash('sha256', $validator);
    $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
    
    // Delete any existing tokens for this user
    $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Insert new token
    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $selector, $hashed_validator, $expires]);
    
    // Return token for cookie (selector:validator)
    return $selector . ':' . $validator;
}

/**
 * Validate remember token and return user if valid
 */
function validateRememberToken($pdo, $token) {
    if (empty($token) || strpos($token, ':') === false) {
        return null;
    }
    
    list($selector, $validator) = explode(':', $token, 2);
    
    $stmt = $pdo->prepare("SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()");
    $stmt->execute([$selector]);
    $token_row = $stmt->fetch();
    
    if (!$token_row) {
        return null;
    }
    
    // Verify validator
    if (!hash_equals($token_row['hashed_validator'], hash('sha256', $validator))) {
        return null;
    }
    
    // Get user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$token_row['user_id']]);
    return $stmt->fetch();
}

/**
 * Set remember me cookie
 */
function setRememberCookie($token) {
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('remember_token', $token, [
        'expires' => $expires,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Clear remember me cookie and token
 */
function clearRememberToken($pdo, $user_id = null) {
    // Clear cookie
    setcookie('remember_token', '', time() - 3600, '/');
    
    // Clear from database if user_id provided
    if ($user_id && $pdo) {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
}

/**
 * Check for remember token and restore session if valid
 * Call this at the start of session_start pages
 */
function checkRememberMe($pdo) {
    // Already logged in
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check for remember cookie
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $user = validateRememberToken($pdo, $_COOKIE['remember_token']);
    
    if ($user) {
        // Restore session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['badge'] = $user['badge'];
        $_SESSION['full_name'] = $user['full_name'] ?? $user['venue_name'] ?? $user['username'];
        $_SESSION['avatar'] = $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name']);
        $_SESSION['language'] = $user['preferred_language'] ?? 'tr';
        
        if ($user['role'] == 'venue' || $user['role'] == 'admin') {
            $_SESSION['venue_name'] = $user['venue_name'];
        }
        
        // Refresh token for security (token rotation)
        $new_token = createRememberToken($pdo, $user['id']);
        setRememberCookie($new_token);
        
        return true;
    }
    
    // Invalid token, clear it
    clearRememberToken(null, null);
    return false;
}
