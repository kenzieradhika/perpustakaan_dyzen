<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/data_base.php';

echo "<h2>🔍 TEST HAPUS USER DIRECT</h2>";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo "❌ Tidak ada ID! Gunakan: test_hapus_direct.php?id=5<br>";
    exit();
}

echo "Testing hapus user ID: $id<br><br>";

// Cek user
$stmt = $pdo->prepare("SELECT id, nama, status FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ User tidak ditemukan!<br>";
    exit();
}

echo "User yang akan dihapus: {$user['nama']} (Status: {$user['status']})<br><br>";

// Cek peminjaman aktif
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'");
$stmt->execute([$id]);
$activeLoans = $stmt->fetch()['total'];

if ($activeLoans > 0) {
    echo "❌ User masih memiliki $activeLoans buku yang dipinjam! Tidak bisa dihapus.<br>";
    exit();
}

echo "✅ Tidak ada peminjaman aktif, bisa dihapus.<br><br>";

// Lakukan hapus
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    echo "✅ Query berhasil!<br>";
    echo "Rows affected: " . $stmt->rowCount() . "<br>";
    
    // Verifikasi
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $exists = $stmt->fetch()['total'];
    
    if ($exists == 0) {
        echo "🎉 SUKSES! User berhasil dihapus!<br>";
    } else {
        echo "❌ GAGAL! User masih ada!<br>";
    }
} else {
    echo "❌ Query gagal!<br>";
    print_r($stmt->errorInfo());
}
?>