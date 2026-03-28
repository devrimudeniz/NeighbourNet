<?php
/**
 * Kalkan Social - Email Helper
 * SMTP Email sending using PHPMailer
 */
require_once __DIR__ . '/env.php';

require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SMTP Configuration
define('SMTP_HOST', env_value('SMTP_HOST', 'localhost'));
define('SMTP_PORT', (int) env_value('SMTP_PORT', 465));
define('SMTP_USERNAME', env_value('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env_value('SMTP_PASSWORD', ''));
define('SMTP_FROM_EMAIL', env_value('SMTP_FROM_EMAIL', 'hello@example.com'));
define('SMTP_FROM_NAME', env_value('SMTP_FROM_NAME', env_value('SITE_NAME', 'Kalkan Social')));

/**
 * Send verification code email
 */
function sendVerificationCode($to, $code, $lang = 'tr') {
    $subject = $lang == 'en' ? 'Verify Your Email - Kalkan Social' : 'Email Doğrulama - Kalkan Social';
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%); color: white; padding: 40px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 40px 30px; }
            .code-box { background: #f1f5f9; border: 2px dashed #8b5cf6; border-radius: 12px; padding: 30px; text-align: center; margin: 30px 0; }
            .code { font-size: 36px; font-weight: bold; color: #8b5cf6; letter-spacing: 8px; font-family: monospace; }
            .footer { padding: 20px; text-align: center; color: #64748b; font-size: 12px; background: #f8fafc; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Kalkan Social</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>" . 
                    ($lang == 'en' ? 'Email Verification' : 'Email Doğrulama') . 
                "</p>
            </div>
            <div class='content'>
                <h2 style='color: #1e293b; margin-top: 0;'>" . 
                    ($lang == 'en' ? 'Welcome to Kalkan Social!' : 'Kalkan Social\'e Hoş Geldin!') . 
                "</h2>
                <p style='color: #475569; line-height: 1.6;'>" . 
                    ($lang == 'en' 
                        ? 'Please use the verification code below to complete your registration:'
                        : 'Kaydınızı tamamlamak için aşağıdaki doğrulama kodunu kullanın:') . 
                "</p>
                <div class='code-box'>
                    <div class='code'>$code</div>
                </div>
                <p style='color: #64748b; font-size: 14px;'>" . 
                    ($lang == 'en' 
                        ? 'This code will expire in 15 minutes.'
                        : 'Bu kod 15 dakika içinde geçerliliğini yitirecektir.') . 
                "</p>
            </div>
            <div class='footer'>
                " . ($lang == 'en' 
                    ? 'If you didn\'t request this code, please ignore this email.'
                    : 'Bu kodu siz talep etmediyseniz, lütfen bu e-postayı görmezden gelin.') . "
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($to, $subject, $message);
}

/**
 * Send email via PHPMailer with SMTP
 */
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        $mail->send();
        error_log("Email sent successfully to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Generate 6-digit verification code
 */
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}
