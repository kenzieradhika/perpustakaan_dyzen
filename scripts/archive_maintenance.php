#!/usr/bin/env php
<?php
/**
 * Archive Maintenance Script - PERPUSTAKAAN DYZEN
 * Run daily via cron to maintain log archives
 */

require_once __DIR__ . '/../config/data_base.php';
require_once __DIR__ . '/../config/logger.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting archive maintenance...\n";

// Force log rotation for all logs
$log_files = ['php-error.log', 'database.log', 'security.log', 'access.log', 'backup.log'];
foreach ($log_files as $file) {
    $filepath = __DIR__ . '/../logs/' . $file;
    if (file_exists($filepath) && filesize($filepath) > 1048576) { // 1MB
        echo "Rotating: {$file}\n";
        Logger::access("Manual rotation triggered by cron", ['file' => $file]);
    }
}

// Clean up old archives
Logger::backup("Archive maintenance completed");

echo "[" . date('Y-m-d H:i:s') . "] Archive maintenance completed!\n";
?>