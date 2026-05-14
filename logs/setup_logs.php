<?php
/**
 * Setup Logs Directory - PERPUSTAKAAN DYZEN
 * Jalankan file ini SEKALI saat pertama kali install aplikasi
 */

$log_dir = __DIR__ . '/logs';

// Buat folder logs
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
    echo "✅ Folder logs created\n";
}

// Buat file-file log
$log_files = ['php-error.log', 'database.log', 'security.log', 'access.log', 'backup.log'];
foreach ($log_files as $file) {
    $filepath = $log_dir . '/' . $file;
    if (!file_exists($filepath)) {
        file_put_contents($filepath, "");
        chmod($filepath, 0644);
        echo "✅ Created: $file\n";
    }
}

// Buat folder archive
$archive_dir = $log_dir . '/archive';
if (!file_exists($archive_dir)) {
    mkdir($archive_dir, 0755, true);
    echo "✅ Archive folder created\n";
}

// Buat .htaccess untuk proteksi
$htaccess = $log_dir . '/.htaccess';
if (!file_exists($htaccess)) {
    $content = "Order Deny,Allow\nDeny from all";
    file_put_contents($htaccess, $content);
    echo "✅ .htaccess created\n";
}

echo "\n🎉 Logs system setup complete!\n";
echo "⚠️ Delete this file after setup for security.\n";
?>