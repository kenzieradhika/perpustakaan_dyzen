<?php
require_once '../../config/data_base.php';
requireAdmin();

// Cek CSRF token
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

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan.";
    header('Location: list_user.php');
    exit();
}

// Check if user is banned
if ($user['status'] != 'banned') {
    $_SESSION['error'] = "User {$user['nama']} tidak dalam status banned.";
    header('Location: list_user.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update user status
    $stmt = $pdo->prepare("UPDATE users SET status = 'aktif', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    // Add notification
    $pesan = "Akun Anda telah diaktifkan kembali oleh administrator. Anda sekarang dapat login dan meminjam buku kembali.";
    $stmt = $pdo->prepare("INSERT INTO peringatan (user_id, pesan, tipe) VALUES (?, ?, 'success')");
    $stmt->execute([$id, $pesan]);
    
    // Log activity
    $log_message = "User {$user['nama']} (NISN: {$user['nisn']}) diunban oleh admin";
    $stmt = $pdo->prepare("INSERT INTO laporan (tipe, keterangan, generated_by) VALUES ('user_management', ?, ?)");
    $stmt->execute([$log_message, $_SESSION['user_id']]);
    
    $pdo->commit();
    $_SESSION['success'] = "User {$user['nama']} berhasil diaktifkan kembali.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Unban error: " . $e->getMessage());
    $_SESSION['error'] = "Gagal mengaktifkan user. Silakan coba lagi.";
}

header('Location: list_user.php');
exit();
?>