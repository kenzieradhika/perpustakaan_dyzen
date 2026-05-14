#!/usr/bin/env php
<?php
/**
 * Auto Ban System - PERPUSTAKAAN DYZEN
 * Jalankan setiap hari via Cron Job
 * 
 * Cara setup di Windows: Task Scheduler
 * Cara setup di Linux: crontab -e
 * 0 2 * * * php /path/to/auto_ban_cron.php
 */

require_once __DIR__ . '/../config/data_base.php';
require_once __DIR__ . '/../config/logger.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting Auto Ban System...\n";

// Get users with overdue books (lebih dari 7 hari)
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.nama, u.email, u.kelas, 
           COUNT(p.id) as overdue_books,
           MIN(p.tgl_kembali) as earliest_due,
           MAX(DATEDIFF(CURDATE(), p.tgl_kembali)) as max_days_overdue
    FROM users u
    JOIN peminjaman p ON u.id = p.user_id
    WHERE u.status = 'aktif' 
      AND u.role = 'user'
      AND p.status = 'dipinjam'
      AND p.tgl_kembali < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY u.id
    HAVING max_days_overdue >= 7
");

$stmt->execute();
$overdueUsers = $stmt->fetchAll();

echo "Found " . count($overdueUsers) . " users with overdue > 7 days\n";

$banned_count = 0;
$warning_count = 0;

foreach ($overdueUsers as $user) {
    $days_overdue = $user['max_days_overdue'];
    
    // If overdue more than 7 days, BAN the user
    if ($days_overdue >= 7) {
        // Update user status to banned
        $update = $pdo->prepare("UPDATE users SET status = 'banned', updated_at = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        // Add notification for user
        $pesan = "Akun Anda telah dibanned OTOMATIS oleh sistem karena memiliki buku yang tidak dikembalikan selama {$days_overdue} hari. Hubungi admin untuk informasi lebih lanjut.";
        $notif = $pdo->prepare("INSERT INTO peringatan (user_id, pesan, tipe) VALUES (?, ?, 'danger')");
        $notif->execute([$user['id'], $pesan]);
        
        // Log to laporan
        $log = $pdo->prepare("INSERT INTO laporan (tipe, keterangan, generated_by) VALUES ('auto_ban', ?, NULL)");
        $log->execute(["User {$user['nama']} (ID: {$user['id']}) auto banned - Overdue {$days_overdue} hari"]);
        
        echo "✅ BANNED: {$user['nama']} - Overdue {$days_overdue} hari\n";
        $banned_count++;
        
    } elseif ($days_overdue >= 3 && $days_overdue < 7) {
        // Send warning notification for users with overdue 3-6 days
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) as total FROM peringatan 
            WHERE user_id = ? AND pesan LIKE '%warning%' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $stmt_check->execute([$user['id']]);
        
        if ($stmt_check->fetch()['total'] == 0) {
            $pesan_warning = "Peringatan! Anda memiliki {$days_overdue} buku yang belum dikembalikan (sudah terlambat {$days_overdue} hari). Segera kembalikan buku untuk menghindari denda dan pemblokiran akun.";
            $notif = $pdo->prepare("INSERT INTO peringatan (user_id, pesan, tipe) VALUES (?, ?, 'warning')");
            $notif->execute([$user['id'], $pesan_warning]);
            
            echo "⚠️ WARNING sent to: {$user['nama']} - Overdue {$days_overdue} hari\n";
            $warning_count++;
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Auto Ban System completed!\n";
echo "Summary: {$banned_count} users banned, {$warning_count} warnings sent\n";

// Log to logger
Logger::backup("Auto ban system executed", [
    'banned' => $banned_count,
    'warnings' => $warning_count
]);

?>