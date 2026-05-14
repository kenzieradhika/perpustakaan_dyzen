<?php
require_once '../../config/data_base.php';
require_once '../../config/logger.php';
requireAdmin();

$log_type = $_GET['type'] ?? 'access';
$lines = (int)($_GET['lines'] ?? 200);
$search = $_GET['search'] ?? '';

// Ambil logs dari Logger class
$logs = Logger::getLogs($log_type, $lines, $search);
?>

<!DOCTYPE html>
<html>
<head>
    <title>System Logs - PERPUSTAKAAN DYZEN</title>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <h1>System Logs</h1>
        
        <!-- Filter form -->
        <form method="GET">
            <select name="type">
                <option value="access" <?= $log_type == 'access' ? 'selected' : '' ?>>Access Logs</option>
                <option value="security" <?= $log_type == 'security' ? 'selected' : '' ?>>Security Logs</option>
                <option value="database" <?= $log_type == 'database' ? 'selected' : '' ?>>Database Logs</option>
                <option value="php-error" <?= $log_type == 'php-error' ? 'selected' : '' ?>>PHP Errors</option>
            </select>
            <input type="text" name="search" placeholder="Search..." value="<?= e($search) ?>">
            <button type="submit">Filter</button>
        </form>
        
        <!-- Display logs -->
        <div class="logs-container">
            <?php foreach ($logs as $log): ?>
                <div class="log-entry">
                    <pre><?= e($log) ?></pre>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>