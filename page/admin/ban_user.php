<?php
require_once '../../config/data_base.php';
requireAdmin();

// Cek CSRF token dari GET parameter
if (!isset($_GET['csrf_token']) || !verifyCsrfToken($_GET['csrf_token'])) {
    $_SESSION['error'] = "Invalid security token. Silakan refresh halaman.";
    header('Location: list_user.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['error'] = "ID user tidak valid.";
    header('Location: list_user.php');
    exit();
}

// Prevent self ban
if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = "Anda tidak dapat mem-ban akun sendiri.";
    header('Location: list_user.php');
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan.";
    header('Location: list_user.php');
    exit();
}

// Check if user is already banned
if ($user['status'] == 'banned') {
    $_SESSION['error'] = "User {$user['nama']} sudah dalam status banned.";
    header('Location: list_user.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update user status to banned
    $stmt = $pdo->prepare("UPDATE users SET status = 'banned', updated_at = NOW() WHERE id = ? AND role = 'user'");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        // Add notification for the banned user
        $pesan = "Akun Anda telah dibanned oleh administrator karena melanggar peraturan perpustakaan. Hubungi admin untuk informasi lebih lanjut.";
        $stmt = $pdo->prepare("INSERT INTO peringatan (user_id, pesan, tipe) VALUES (?, ?, 'danger')");
        $stmt->execute([$id, $pesan]);
        
        // Log activity
        $log_message = "User {$user['nama']} (NISN: {$user['nisn']}) dibanned oleh admin";
        $stmt = $pdo->prepare("INSERT INTO laporan (tipe, keterangan, generated_by) VALUES ('user_management', ?, ?)");
        $stmt->execute([$log_message, $_SESSION['user_id']]);
        
        $_SESSION['success'] = "User {$user['nama']} berhasil dibanned.";
    } else {
        $_SESSION['error'] = "Gagal mengubah status user.";
    }
    
    $pdo->commit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Ban user error: " . $e->getMessage());
    $_SESSION['error'] = "Gagal mem-ban user. Silakan coba lagi.";
}

header('Location: list_user.php');
exit();
?>