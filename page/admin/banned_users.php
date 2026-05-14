<?php
require_once '../../config/data_base.php';
requireAdmin();

// Handle unban action
if (isset($_GET['unban']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Verify CSRF
    if (!isset($_GET['csrf_token']) || !verifyCsrfToken($_GET['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: banned_users.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update user status
        $stmt = $pdo->prepare("UPDATE users SET status = 'aktif', updated_at = NOW() WHERE id = ? AND role = 'user'");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            // Get user data
            $stmt = $pdo->prepare("SELECT nama, email FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            // Add notification
            $pesan = "Akun Anda telah diaktifkan kembali oleh administrator. Anda sekarang dapat login dan meminjam buku kembali.";
            $stmt = $pdo->prepare("INSERT INTO peringatan (user_id, pesan, tipe) VALUES (?, ?, 'success')");
            $stmt->execute([$id, $pesan]);
            
            $_SESSION['success'] = "User {$user['nama']} berhasil diaktifkan kembali";
        } else {
            $_SESSION['error'] = 'User tidak ditemukan';
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Unban error: " . $e->getMessage());
        $_SESSION['error'] = 'Gagal mengaktifkan user';
    }
    
    header('Location: banned_users.php');
    exit();
}

// Get all banned users
$stmt = $pdo->prepare("
    SELECT id, nama, email, nisn, kelas, status, updated_at, created_at
    FROM users 
    WHERE role = 'user' AND status = 'banned'
    ORDER BY updated_at DESC
");
$stmt->execute();
$bannedUsers = $stmt->fetchAll();

// Get banned statistics
$totalBanned = count($bannedUsers);
$autoBanned = 0;
$manualBanned = 0;

foreach ($bannedUsers as $user) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM peringatan 
        WHERE user_id = ? AND pesan LIKE '%otomatis%' AND tipe = 'danger'
    ");
    $stmt->execute([$user['id']]);
    if ($stmt->fetch()['total'] > 0) {
        $autoBanned++;
    } else {
        $manualBanned++;
    }
}

$csrf_token = generateCsrfToken();

// Display messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banned Users - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .alert-success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .btn-unban {
            transition: all 0.2s ease;
        }
        .btn-unban:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-playfair font-bold text-primary">Banned Users</h1>
                    <p class="text-text-light mt-1">Kelola akun pengguna yang sedang dibanned</p>
                </div>
                <a href="list_user.php" class="inline-flex items-center gap-2 px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke User List
                </a>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if ($success): ?>
        <div class="alert-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= e($success) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert-error">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card bg-gradient-to-r from-primary to-secondary rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="opacity-90 text-sm">Total Banned Users</p>
                        <p class="text-3xl font-bold mt-1"><?= number_format($totalBanned) ?></p>
                    </div>
                    <svg class="w-12 h-12 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            
            <div class="stat-card bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="opacity-90 text-sm">Auto Ban (Overdue >7 hari)</p>
                        <p class="text-3xl font-bold mt-1"><?= number_format($autoBanned) ?></p>
                    </div>
                    <svg class="w-12 h-12 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            
            <div class="stat-card bg-gradient-to-r from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="opacity-90 text-sm">Manual Ban</p>
                        <p class="text-3xl font-bold mt-1"><?= number_format($manualBanned) ?></p>
                    </div>
                    <svg class="w-12 h-12 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Banned Users Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="bannedTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">User</th>
                            <th class="px-6 py-4 text-left">NISN</th>
                            <th class="px-6 py-4 text-left">Kelas</th>
                            <th class="px-6 py-4 text-center">Status Ban</th>
                            <th class="px-6 py-4 text-center">Tanggal Ban</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bannedUsers as $user): 
                            $isAutoBan = false;
                            $stmtCheck = $pdo->prepare("
                                SELECT COUNT(*) as total FROM peringatan 
                                WHERE user_id = ? AND pesan LIKE '%otomatis%' AND tipe = 'danger'
                            ");
                            $stmtCheck->execute([$user['id']]);
                            $isAutoBan = $stmtCheck->fetch()['total'] > 0;
                        ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="font-semibold"><?= e($user['nama']) ?></div>
                                        <div class="text-xs text-text-light"><?= e($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?= e($user['nisn']) ?></td>
                            <td class="px-6 py-4"><?= e($user['kelas']) ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold <?= $isAutoBan ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= $isAutoBan ? 'Auto Ban' : 'Manual Ban' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm">
                                <?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="?unban=1&id=<?= $user['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                   onclick="return confirm('Aktifkan kembali user <?= e($user['nama']) ?>?')"
                                   class="btn-unban inline-flex items-center gap-1 px-4 py-1.5 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Unban User
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bannedUsers)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-text-light">
                                    <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-lg font-medium">Tidak ada user yang sedang dibanned</p>
                                    <p class="text-sm mt-1">Semua user dalam status aktif.</p>
                                    <a href="list_user.php" class="inline-flex items-center gap-2 mt-4 text-primary hover:text-secondary">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                        </svg>
                                        Kembali ke Data User
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 rounded-xl p-5 border border-blue-200">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-semibold text-blue-800">Informasi Ban Otomatis</p>
                    <p class="text-sm text-blue-700 mt-1">User akan dibanned otomatis oleh sistem jika memiliki buku yang tidak dikembalikan selama lebih dari 7 hari. Sistem akan memberikan notifikasi warning 3 hari sebelum ban.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#bannedTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                pageLength: 10,
                responsive: true,
                order: [[4, 'desc']]
            });
        });
    </script>
</body>
</html>