<?php
require_once '../../config/data_base.php';
requireAdmin();

// Test ban user dengan ID 4 (ganti sesuai user Anda)
$test_id = 4;
$test_action = 'ban';

$stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
$result = $stmt->execute([$test_id]);

if ($result) {
    echo "✅ User ID $test_id berhasil dibanned!";
} else {
    echo "❌ Gagal! Error: " . print_r($stmt->errorInfo(), true);
}

// Cek hasil
$stmt = $pdo->prepare("SELECT id, nama, status FROM users WHERE id = ?");
$stmt->execute([$test_id]);
$user = $stmt->fetch();
echo "<br>Status sekarang: " . $user['status'];
?>