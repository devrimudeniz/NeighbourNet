<?php
/**
 * OAuth Configuration for Social Login
 * Google and Facebook OAuth credentials
 */
require_once __DIR__ . '/env.php';

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', env_value('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env_value('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', env_value('GOOGLE_REDIRECT_URI', app_url() . '/api/auth_google.php'));

// Facebook OAuth Configuration
define('FACEBOOK_APP_ID', env_value('FACEBOOK_APP_ID', ''));
define('FACEBOOK_APP_SECRET', env_value('FACEBOOK_APP_SECRET', ''));
define('FACEBOOK_REDIRECT_URI', env_value('FACEBOOK_REDIRECT_URI', app_url() . '/api/auth_facebook.php'));

// Site URL
define('SITE_URL', app_url());
?>
