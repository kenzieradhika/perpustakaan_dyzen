<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/data_base.php';

echo "<h2>🔍 TEST UNBAN USER DIRECT</h2>";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 2;

// Cek user sebelum
$stmt = $pdo->prepare("SELECT id, nama, status FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

echo "User SEBELUM: " . print_r($user, true) . "<br><br>";

// Lakukan unban
$stmt = $pdo->prepare("UPDATE users SET status = 'aktif', updated_at = NOW() WHERE id = ?");
$result = $stmt->execute([$id]);

if ($result) {
    echo "✅ Query berhasil!<br>";
    echo "Rows affected: " . $stmt->rowCount() . "<br><br>";
}

// Cek user setelah
$stmt = $pdo->prepare("SELECT id, nama, status FROM users WHERE id = ?");
$stmt->execute([$id]);
$userAfter = $stmt->fetch();

echo "User SESUDAH: " . print_r($userAfter, true) . "<br><br>";

if ($userAfter['status'] == 'aktif') {
    echo "🎉 SUKSES! User berhasil di-unban!<br>";
} else {
    echo "❌ GAGAL! Status masih: " . $userAfter['status'] . "<br>";
}
?>