<?php
require_once '../../config/data_base.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    if (isset($_GET['csrf_token']) && verifyCsrfToken($_GET['csrf_token'])) {
        $stmt = $pdo->prepare("UPDATE peringatan SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        header('Location: peringatan_user.php');
        exit();
    }
}

// Mark single as read
if (isset($_GET['read']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if (isset($_GET['csrf_token']) && verifyCsrfToken($_GET['csrf_token'])) {
        $stmt = $pdo->prepare("UPDATE peringatan SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        header('Location: peringatan_user.php');
        exit();
    }
}

// Get all notifications
$stmt = $pdo->prepare("
    SELECT * FROM peringatan 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Count unread
$unread_count = 0;
foreach ($notifications as $notif) {
    if (!$notif['is_read']) $unread_count++;
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peringatan - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        .notification-item {
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .notification-item.unread {
            background-color: #f0f7ff;
            border-left-color: var(--color-primary);
        }
        .notification-item:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-playfair font-bold text-primary">Peringatan & Notifikasi</h1>
                    <p class="text-text-light">Informasi penting tentang peminjaman dan akun Anda</p>
                </div>
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_read=all&csrf_token=<?= $csrf_token ?>" 
                       class="btn-outline text-sm"
                       onclick="return confirm('Tandai semua notifikasi sebagai sudah dibaca?')">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Tandai Semua Dibaca
                    </a>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <?php if (empty($notifications)): ?>
                <div class="p-12 text-center">
                    <svg class="w-20 h-20 mx-auto text-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-text-light">Tidak ada notifikasi.</p>
                    <p class="text-sm text-text-light mt-1">Semua notifikasi akan muncul di sini.</p>
                </div>
            <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item p-4 <?= !$notif['is_read'] ? 'unread' : '' ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <?php if ($notif['tipe'] == 'danger'): ?>
                                            <svg class="w-5 h-5 text-danger mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        <?php elseif ($notif['tipe'] == 'warning'): ?>
                                            <svg class="w-5 h-5 text-warning mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5 text-info mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        <?php endif; ?>
                                        <span class="text-xs text-text-light"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></span>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="ml-2 px-2 py-0.5 bg-primary text-white text-xs rounded-full">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-text"><?= nl2br(e($notif['pesan'])) ?></p>
                                </div>
                                <?php if (!$notif['is_read']): ?>
                                    <a href="?read=1&id=<?= $notif['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                       class="text-primary hover:text-secondary ml-4"
                                       title="Tandai sudah dibaca">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Info Box -->
        <div class="bg-light rounded-lg p-6 mt-6">
            <h3 class="font-bold text-lg mb-3">📌 Informasi Notifikasi</h3>
            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-start space-x-2">
                    <div class="w-3 h-3 bg-danger rounded-full mt-1.5"></div>
                    <span class="text-text">Notifikasi <strong>Danger</strong>: Peringatan serius (ban akun, denda besar)</span>
                </div>
                <div class="flex items-start space-x-2">
                    <div class="w-3 h-3 bg-warning rounded-full mt-1.5"></div>
                    <span class="text-text">Notifikasi <strong>Warning</strong>: Peringatan penting (buku hampir jatuh tempo)</span>
                </div>
                <div class="flex items-start space-x-2">
                    <div class="w-3 h-3 bg-info rounded-full mt-1.5"></div>
                    <span class="text-text">Notifikasi <strong>Info</strong>: Informasi umum (aktivasi akun, konfirmasi)</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>