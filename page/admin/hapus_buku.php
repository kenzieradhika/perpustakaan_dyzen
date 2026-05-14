<?php
require_once '../../config/data_base.php';
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: list_buku.php');
    exit();
}

// Check if book has active loans
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE buku_id = ? AND status = 'dipinjam'");
$stmt->execute([$id]);
$activeLoans = $stmt->fetch()['total'];

if ($activeLoans > 0) {
    $_SESSION['error'] = "Buku sedang dipinjam dan tidak dapat dihapus.";
    header('Location: list_buku.php');
    exit();
}

// Soft delete - update status to nonaktif
$stmt = $pdo->prepare("UPDATE buku SET status = 'nonaktif', updated_at = NOW() WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['success'] = "Buku berhasil dinonaktifkan.";
header('Location: list_buku.php');
exit();
?>