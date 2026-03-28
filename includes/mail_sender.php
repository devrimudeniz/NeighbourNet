<?php
require_once 'SimpleSMTP.php';
require_once __DIR__ . '/env.php';

function send_smtp_email($to, $subject, $message, $fromName = 'Kalkan Social') {
    $host = env_value('SMTP_HOST', 'localhost');
    $port = (int) env_value('SMTP_PORT', 465);
    $username = env_value('SMTP_USERNAME', '');
    $password = env_value('SMTP_PASSWORD', '');
    $fromName = env_value('SMTP_FROM_NAME', $fromName);

    try {
        $smtp = new SimpleSMTP($host, $port, $username, $password);
        return $smtp->send($to, $subject, $message, $fromName);
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}
?>
