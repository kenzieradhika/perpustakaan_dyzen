<?php
require_once '../../config/data_base.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// ============================================
// GET USER DATA
// ============================================
$stmt = $pdo->prepare("SELECT nama, foto, kelas, nisn, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch();

// ============================================
// STATISTICS CARDS
// ============================================

// Total buku dipinjam (aktif)
$activeLoans = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'");
$activeLoans->execute([$user_id]);
$activeCount = $activeLoans->fetch()['total'];

// Total buku yang sudah dikembalikan
$returnedLoans = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'dikembalikan'");
$returnedLoans->execute([$user_id]);
$returnedCount = $returnedLoans->fetch()['total'];

// Total denda belum dibayar
$totalDenda = $pdo->prepare("SELECT COALESCE(SUM(jumlah_denda), 0) as total FROM denda WHERE user_id = ? AND status_bayar = 'belum'");
$totalDenda->execute([$user_id]);
$dendaTotal = $totalDenda->fetch()['total'];

// Unread notifications
$unreadPeringatan = $pdo->prepare("SELECT COUNT(*) as total FROM peringatan WHERE user_id = ? AND is_read = 0");
$unreadPeringatan->execute([$user_id]);
$unreadCount = $unreadPeringatan->fetch()['total'];

// ============================================
// CURRENT LOANS (Buku yang sedang dipinjam)
// ============================================
$currentLoans = $pdo->prepare("
    SELECT p.*, b.judul, b.cover, b.pengarang, b.kategori,
           DATEDIFF(p.tgl_kembali, CURDATE()) as days_left
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.user_id = ? AND p.status = 'dipinjam'
    ORDER BY p.tgl_kembali ASC
");
$currentLoans->execute([$user_id]);
$loans = $currentLoans->fetchAll();

// Hitung buku yang hampir jatuh tempo (≤ 3 hari) dan terlambat
$nearDueCount = 0;
$overdueCount = 0;
foreach ($loans as $loan) {
    if ($loan['days_left'] >= 0 && $loan['days_left'] <= 3) {
        $nearDueCount++;
    }
    if ($loan['days_left'] < 0) {
        $overdueCount++;
    }
}

// ============================================
// RECOMMENDED BOOKS (SEDERHANA, TANPA SUBQUERY KOMPLEKS)
// ============================================

// Ambil kategori dari riwayat peminjaman user
$kategoriQuery = $pdo->prepare("
    SELECT DISTINCT b.kategori 
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.user_id = ? AND b.kategori IS NOT NULL AND b.kategori != ''
    LIMIT 3
");
$kategoriQuery->execute([$user_id]);
$userCategories = $kategoriQuery->fetchAll();

$recommendedBooks = [];

if (!empty($userCategories)) {
    // Buat list kategori
    $categories = [];
    foreach ($userCategories as $cat) {
        if (!empty($cat['kategori'])) {
            $categories[] = $cat['kategori'];
        }
    }
    
    if (!empty($categories)) {
        // Buat placeholder untuk IN clause
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $sql = "SELECT * FROM buku 
                WHERE status = 'aktif' AND stok_tersedia > 0 
                AND kategori IN ($placeholders) 
                LIMIT 4";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($categories);
        $recommendedBooks = $stmt->fetchAll();
    }
}

// Jika tidak ada rekomendasi, ambil buku random
if (empty($recommendedBooks)) {
    $randomBooks = $pdo->query("
        SELECT * FROM buku 
        WHERE status = 'aktif' AND stok_tersedia > 0 
        ORDER BY RAND() LIMIT 4
    ");
    $recommendedBooks = $randomBooks->fetchAll();
}

// ============================================
// BORROWING HISTORY (Riwayat 5 terbaru)
// ============================================
$history = $pdo->prepare("
    SELECT p.*, b.judul, b.pengarang,
           DATE_FORMAT(p.tgl_pinjam, '%d/%m/%Y') as tgl_pinjam_formatted,
           DATE_FORMAT(p.tgl_kembali, '%d/%m/%Y') as tgl_kembali_formatted,
           DATE_FORMAT(p.tgl_dikembalikan, '%d/%m/%Y') as tgl_dikembalikan_formatted,
           CASE 
               WHEN p.status = 'dipinjam' AND p.tgl_kembali < CURDATE() THEN 'Terlambat'
               WHEN p.status = 'dipinjam' THEN 'Dipinjam'
               ELSE 'Dikembalikan'
           END as status_label
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$history->execute([$user_id]);
$recentHistory = $history->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
            transition: margin-left 0.3s;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .stats-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.25rem;
            transition: all 0.3s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .loan-card {
            transition: all 0.2s;
        }
        
        .loan-card:hover {
            transform: translateX(5px);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-dipinjam { background: #FEF3C7; color: #D97706; }
        .status-terlambat { background: #FEE2E2; color: #DC2626; }
        .status-dikembalikan { background: #D1FAE5; color: #059669; }
        
        .greeting-badge {
            background: linear-gradient(135deg, #09637E, #088395);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            color: white;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <header class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl font-playfair font-bold text-primary">Dashboard Member</h1>
                        <span class="greeting-badge">
                            <?= htmlspecialchars($userData['kelas'] ?? 'Member') ?>
                        </span>
                    </div>
                    <p class="text-text-light">
                        Selamat datang kembali, <span class="font-semibold text-primary"><?= htmlspecialchars($_SESSION['user_name']) ?></span>!
                    </p>
                    <p class="text-text-light text-sm mt-1">
                        NISN: <?= htmlspecialchars($userData['nisn'] ?? '-') ?> | Kelas: <?= htmlspecialchars($userData['kelas'] ?? '-') ?>
                    </p>
                </div>
                
                <!-- Notification Bell -->
                <div class="relative">
                    <a href="peringatan_user.php" class="text-text-light hover:text-primary transition p-2 rounded-full hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </a>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <!-- Alert jika ada buku terlambat -->
        <?php if ($overdueCount > 0): ?>
        <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-semibold text-red-700">Perhatian!</p>
                    <p class="text-red-600 text-sm">Anda memiliki <?= $overdueCount ?> buku yang terlambat. Segera kembalikan untuk menghindari denda lebih besar.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <!-- Card 1: Buku Dipinjam -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-sm">Buku Dipinjam</p>
                        <p class="text-3xl font-bold text-primary mt-1"><?= $activeCount ?></p>
                        <?php if ($nearDueCount > 0): ?>
                            <p class="text-xs text-yellow-500 mt-1"><?= $nearDueCount ?> buku hampir jatuh tempo</p>
                        <?php endif; ?>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: Buku Dikembalikan -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-sm">Buku Dikembalikan</p>
                        <p class="text-3xl font-bold text-primary mt-1"><?= $returnedCount ?></p>
                        <p class="text-xs text-text-light mt-1">Total peminjaman selesai</p>
                    </div>
                    <div class="stats-icon bg-green-500 bg-opacity-10">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: Total Denda -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-sm">Total Denda</p>
                        <p class="text-xl font-bold text-red-500 mt-1">Rp <?= number_format($dendaTotal, 0, ',', '.') ?></p>
                        <p class="text-xs text-text-light mt-1">Belum dibayar</p>
                    </div>
                    <div class="stats-icon bg-red-500 bg-opacity-10">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card 4: Peringatan -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-sm">Peringatan Baru</p>
                        <p class="text-3xl font-bold text-yellow-500 mt-1"><?= $unreadCount ?></p>
                        <p class="text-xs text-text-light mt-1">Belum dibaca</p>
                    </div>
                    <div class="stats-icon bg-yellow-500 bg-opacity-10">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Current Loans Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg text-primary">Buku yang Sedang Dipinjam</h3>
                <?php if (!empty($loans)): ?>
                    <a href="kembalikan_buku.php" class="text-sm text-primary hover:text-secondary">Lihat semua →</a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($loans)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <p class="text-text-light">Tidak ada buku yang sedang dipinjam.</p>
                    <a href="list_buku.php" class="inline-block mt-3 text-primary hover:text-secondary">Pinjam buku sekarang →</a>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($loans as $loan): ?>
                        <?php 
                        $isOverdue = $loan['days_left'] < 0;
                        $isWarning = $loan['days_left'] >= 0 && $loan['days_left'] <= 3;
                        $borderClass = $isOverdue ? 'border-l-4 border-red-500' : ($isWarning ? 'border-l-4 border-yellow-500' : 'border-l-4 border-green-500');
                        ?>
                        <div class="loan-card <?= $borderClass ?> bg-gray-50 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <?php if (!empty($loan['cover']) && file_exists('../../uploads/covers/' . $loan['cover'])): ?>
                                        <img src="../../uploads/covers/<?= htmlspecialchars($loan['cover']) ?>" class="w-16 h-24 object-cover rounded-md">
                                    <?php else: ?>
                                        <div class="w-16 h-24 bg-gradient-to-br from-primary to-secondary rounded-md flex items-center justify-center">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-800"><?= htmlspecialchars($loan['judul']) ?></h4>
                                    <p class="text-text-light text-sm"><?= htmlspecialchars($loan['pengarang']) ?></p>
                                    <div class="flex flex-wrap gap-3 mt-2 text-xs">
                                        <span class="text-gray-500">Pinjam: <?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></span>
                                        <span class="text-gray-500">Jatuh tempo: <?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?></span>
                                    </div>
                                    <?php if ($isOverdue): ?>
                                        <p class="text-red-600 font-semibold text-sm mt-1">Terlambat <?= abs($loan['days_left']) ?> hari!</p>
                                    <?php elseif ($isWarning): ?>
                                        <p class="text-yellow-600 font-semibold text-sm mt-1">Sisa <?= $loan['days_left'] ?> hari lagi</p>
                                    <?php else: ?>
                                        <p class="text-green-600 font-semibold text-sm mt-1">Sisa <?= $loan['days_left'] ?> hari</p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="kembalikan_buku.php" class="text-primary hover:text-secondary">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Two Column Layout -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Recommended Books -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-lg text-primary mb-4">Rekomendasi untuk Anda</h3>
                <?php if (empty($recommendedBooks)): ?>
                    <p class="text-text-light text-center py-6">Belum ada rekomendasi. Pinjam buku dulu ya!</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recommendedBooks as $book): ?>
                            <div class="flex gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                                <div class="flex-shrink-0">
                                    <?php if (!empty($book['cover']) && file_exists('../../uploads/covers/' . $book['cover'])): ?>
                                        <img src="../../uploads/covers/<?= htmlspecialchars($book['cover']) ?>" class="w-12 h-16 object-cover rounded-md">
                                    <?php else: ?>
                                        <div class="w-12 h-16 bg-gradient-to-br from-primary to-secondary rounded-md flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-sm"><?= htmlspecialchars($book['judul']) ?></h4>
                                    <p class="text-text-light text-xs"><?= htmlspecialchars($book['pengarang']) ?></p>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full"><?= htmlspecialchars($book['kategori']) ?></span>
                                        <a href="pinjam_buku.php?id=<?= $book['id'] ?>" class="text-primary hover:text-secondary text-xs font-semibold">Pinjam →</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="list_buku.php" class="text-sm text-primary hover:text-secondary">Lihat semua koleksi →</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Borrowing History -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-lg text-primary mb-4">Riwayat Peminjaman Terbaru</h3>
                <?php if (empty($recentHistory)): ?>
                    <p class="text-text-light text-center py-6">Belum ada riwayat peminjaman.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recentHistory as $historyItem): ?>
                            <div class="flex justify-between items-center p-3 border-b border-gray-100">
                                <div>
                                    <p class="font-medium text-sm"><?= htmlspecialchars($historyItem['judul']) ?></p>
                                    <p class="text-text-light text-xs"><?= htmlspecialchars($historyItem['pengarang']) ?></p>
                                    <p class="text-gray-400 text-xs mt-1">Pinjam: <?= $historyItem['tgl_pinjam_formatted'] ?></p>
                                </div>
                                <div class="text-right">
                                    <?php
                                    $statusClass = '';
                                    if ($historyItem['status_label'] == 'Terlambat') {
                                        $statusClass = 'status-terlambat';
                                    } elseif ($historyItem['status_label'] == 'Dipinjam') {
                                        $statusClass = 'status-dipinjam';
                                    } else {
                                        $statusClass = 'status-dikembalikan';
                                    }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= $historyItem['status_label'] ?>
                                    </span>
                                    <?php if ($historyItem['status'] == 'dikembalikan' && $historyItem['tgl_dikembalikan_formatted']): ?>
                                        <p class="text-gray-400 text-xs mt-1">Kembali: <?= $historyItem['tgl_dikembalikan_formatted'] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="history_peminjaman.php" class="text-sm text-primary hover:text-secondary">Lihat semua riwayat →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>