<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Path yang benar dari ROOT folder
require_once 'config/data_base.php';

echo "<h2>🔍 TEST DATABASE CONNECTION</h2>";

// Cek koneksi
if (isset($pdo)) {
    echo "✅ Database connected!<br>";
    
    // Cek tabel users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total = $stmt->fetchColumn();
    echo "✅ Total users: " . $total . "<br>";
    
    // Cek struktur users
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "<h3>Struktur Tabel users:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "❌ Database connection failed!<br>";
}
?>