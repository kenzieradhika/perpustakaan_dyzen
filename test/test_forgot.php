<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/data_base.php';
require_once 'config/mail_config.php';

echo "<h2>Debug Forgot Password</h2>";

// Email test (GANTI dengan email yang ada di database Anda!)
$test_email = 'admin@dyzen.com';  // ← GANTI INI!

echo "Mencari email: " . $test_email . "<br>";

$stmt = $pdo->prepare("SELECT id, nama, email FROM users WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch();

if ($user) {
    echo "✅ User ditemukan!<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Nama: " . $user['nama'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    echo "Token generated: " . $token . "<br>";
    
    // Simpan token
    $stmt = $pdo->prepare("UPDATE users SET token_reset = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$token, $user['id']]);
    
    if ($result) {
        echo "✅ Token berhasil disimpan ke database!<br>";
        
        $reset_link = BASE_URL . "auth/reset_password.php?token=" . $token;
        echo "Reset link: <a href='$reset_link' target='_blank'>$reset_link</a><br>";
        
        // Kirim email
        $email_sent = sendResetEmail($test_email, $user['nama'], $reset_link);
        
        if ($email_sent) {
            echo "✅ Email berhasil dikirim! Cek inbox/spam Anda.<br>";
        } else {
            echo "❌ Gagal mengirim email. Cek konfigurasi SMTP.<br>";
        }
        
    } else {
        echo "❌ Gagal menyimpan token ke database!<br>";
        print_r($stmt->errorInfo());
    }
    
} else {
    echo "❌ User dengan email '$test_email' tidak ditemukan!<br>";
    echo "Silakan buat user terlebih dahulu.<br>";
}
?>