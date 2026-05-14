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

// Prevent self deletion
if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = "Anda tidak dapat menghapus akun sendiri.";
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

// Check if user has active loans
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'");
$stmt->execute([$id]);
$activeLoans = $stmt->fetch()['total'];

if ($activeLoans > 0) {
    $_SESSION['error'] = "User masih memiliki " . $activeLoans . " buku yang dipinjam. Selesaikan peminjaman terlebih dahulu.";
    header('Location: list_user.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Delete user photo if exists
    if ($user['foto'] && file_exists(UPLOAD_PATH . $user['foto'])) {
        unlink(UPLOAD_PATH . $user['foto']);
    }
    
    // Delete user (CASCADE will delete related peminjaman, denda, peringatan)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    // Log deletion
    $log_message = "User {$user['nama']} (NISN: {$user['nisn']}) dihapus oleh admin";
    $stmt = $pdo->prepare("INSERT INTO laporan (tipe, keterangan, generated_by) VALUES ('user_management', ?, ?)");
    $stmt->execute([$log_message, $_SESSION['user_id']]);
    
    $pdo->commit();
    $_SESSION['success'] = "User {$user['nama']} berhasil dihapus.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Delete user error: " . $e->getMessage());
    $_SESSION['error'] = "Gagal menghapus user. Silakan coba lagi.";
}

header('Location: list_user.php');
exit();
?>