<?php
/**
 * Google Ads Configuration
 * Conversion tracking for registration (sign-up)
 */
require_once __DIR__ . '/env.php';
if (!defined('GOOGLE_ADS_CONVERSION_ID')) {
    define('GOOGLE_ADS_CONVERSION_ID', env_value('GOOGLE_ADS_CONVERSION_ID', ''));
}
if (!defined('GOOGLE_ADS_CONVERSION_LABEL')) {
    define('GOOGLE_ADS_CONVERSION_LABEL', env_value('GOOGLE_ADS_CONVERSION_LABEL', ''));
}
