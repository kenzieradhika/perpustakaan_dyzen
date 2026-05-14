<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/data_base.php';
requireAdmin();

echo "<h2>🔍 TEST HAPUS ACTION</h2>";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';

echo "ID: $id<br>";

// Cek token
if (!verifyCsrfToken($token)) {
    echo "❌ CSRF Token INVALID!<br>";
    exit();
}
echo "✅ CSRF Token valid<br>";

// Cek peminjaman aktif
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'");
$stmt->execute([$id]);
$activeLoans = $stmt->fetch()['total'];

echo "Peminjaman aktif: $activeLoans<br>";

if ($activeLoans > 0) {
    echo "❌ User masih memiliki $activeLoans buku yang dipinjam! Tidak bisa dihapus.<br>";
    exit();
}

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ User tidak ditemukan!<br>";
    exit();
}

echo "User yang akan dihapus: " . $user['nama'] . "<br>";

// Lakukan hapus
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    echo "✅ Query berhasil!<br>";
    echo "Rows affected: " . $stmt->rowCount() . "<br>";
    
    // Cek apakah masih ada
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $exists = $stmt->fetch()['total'];
    
    if ($exists == 0) {
        echo "🎉 SUKSES! User berhasil dihapus!<br>";
    } else {
        echo "❌ GAGAL! User masih ada di database!<br>";
    }
} else {
    echo "❌ Query gagal!<br>";
    print_r($stmt->errorInfo());
}
?>