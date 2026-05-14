<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/data_base.php';

echo "<h2>🔍 TEST BAN USER DIRECT</h2>";

// Ambil ID user dari URL atau default ke user pertama
$id = isset($_GET['id']) ? (int)$_GET['id'] : 2;

echo "Testing user ID: $id<br><br>";

// Cek user sebelum
$stmt = $pdo->prepare("SELECT id, nama, status FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ User dengan ID $id tidak ditemukan!<br>";
    echo "Daftar user yang tersedia:<br>";
    $stmt = $pdo->query("SELECT id, nama, status FROM users WHERE role = 'user'");
    $users = $stmt->fetchAll();
    foreach ($users as $u) {
        echo "- ID: {$u['id']}, Nama: {$u['nama']}, Status: {$u['status']}<br>";
    }
    exit();
}

echo "User SEBELUM: " . print_r($user, true) . "<br><br>";

// Lakukan ban
$stmt = $pdo->prepare("UPDATE users SET status = 'banned', updated_at = NOW() WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    echo "✅ Query berhasil!<br>";
    echo "Rows affected: " . $stmt->rowCount() . "<br><br>";
} else {
    echo "❌ Query gagal!<br>";
    print_r($stmt->errorInfo());
    exit();
}

// Cek user setelah
$stmt = $pdo->prepare("SELECT id, nama, status FROM users WHERE id = ?");
$stmt->execute([$id]);
$userAfter = $stmt->fetch();

echo "User SESUDAH: " . print_r($userAfter, true) . "<br><br>";

if ($userAfter['status'] == 'banned') {
    echo "🎉 SUKSES! User berhasil di-ban!<br>";
    echo "<a href='test_unban_direct.php?id=$id'>Klik untuk Unban</a>";
} else {
    echo "❌ GAGAL! Status masih: " . $userAfter['status'] . "<br>";
}
?>