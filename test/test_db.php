<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/data_base.php';

echo "<h2>Test Database Connection</h2>";

try {
    // Cek koneksi
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Koneksi database BERHASIL<br>";
    
    // Cek tabel users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "✅ Tabel users ada, total " . $result['total'] . " user<br>";
    
    // Tampilkan beberapa user
    $stmt = $pdo->query("SELECT id, nama, email FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    echo "<br>📋 Daftar user di database:<br>";
    echo "<ul>";
    foreach($users as $user) {
        echo "<li>ID: {$user['id']} - Nama: {$user['nama']} - Email: {$user['email']}</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>