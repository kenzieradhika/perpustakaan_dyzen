<?php
/**
 * PERPUSTAKAAN DYZEN - Mail Configuration
 * File: config/mail_config.php
 */

// Load autoload dari Composer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================
// KONFIGURASI EMAIL
// ============================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'maskengg94@gmail.com');      // Email pengirim
define('SMTP_PASS', 'elou ldob orpd qjjh');       // App Password (spasi diabaikan)
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);
define('MAIL_FROM', 'maskengg94@gmail.com');      // ← HARUS SAMA dengan SMTP_USER!
define('MAIL_FROM_NAME', 'PERPUSTAKAAN DYZEN');

/**
 * Kirim email reset password
 */
function sendResetEmail($email, $nama, $reset_link) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $nama);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password - ' . MAIL_FROM_NAME;
        
        // Email template
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family: Arial, sans-serif;">
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="background: linear-gradient(135deg, #09637E, #088395); padding: 20px; text-align: center;">
                    <h1 style="color: white;">📚 PERPUSTAKAAN DYZEN</h1>
                </div>
                <div style="background: #f9f9f9; padding: 30px;">
                    <h2>Halo ' . htmlspecialchars($nama) . '!</h2>
                    <p>Klik link di bawah ini untuk mereset password Anda:</p>
                    <a href="' . $reset_link . '" style="background: #09637E; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 5px;">Reset Password</a>
                    <p style="margin-top: 20px;">Atau copy link: ' . $reset_link . '</p>
                    <p style="color: red; font-size: 12px;">⚠️ Link berlaku 1 jam.</p>
                    <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                </div>
                <div style="text-align: center; padding: 20px; font-size: 12px; color: #666;">
                    <p>&copy; 2024-2026 PERPUSTAKAAN DYZEN</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = "Halo {$nama},\n\nReset password: {$reset_link}\n\nLink berlaku 1 jam.\n\nAbaikan jika bukan Anda.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Test koneksi SMTP
 */
function testSMTPConnection() {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        $mail->smtpConnect();
        return "✅ SMTP connection successful! Email is ready to use.";
        
    } catch (Exception $e) {
        return "❌ Connection failed: " . $mail->ErrorInfo;
    }
}
?>