<?php
require_once '../../config/data_base.php';
requireAdmin();

// Get statistics
$totalBuku = $pdo->query("SELECT COUNT(*) as total FROM buku WHERE status = 'aktif'")->fetch()['total'];
$totalUser = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'aktif'")->fetch()['total'];
$dipinjamHariIni = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE DATE(created_at) = CURDATE()")->fetch()['total'];
$dendaBelumBayar = $pdo->query("SELECT COALESCE(SUM(jumlah_denda), 0) as total FROM denda WHERE status_bayar = 'belum'")->fetch()['total'];

// Get total banned users
$totalBanned = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'banned'")->fetch()['total'];

// Get total books borrowed (active)
$totalDipinjam = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'")->fetch()['total'];

// Get recent loans
$recentLoans = $pdo->query("
    SELECT p.*, u.nama as user_name, b.judul as buku_judul 
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b ON p.buku_id = b.id
    ORDER BY p.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get unpaid fines
$unpaidFines = $pdo->query("
    SELECT d.*, u.nama as user_name, b.judul as buku_judul, p.tgl_kembali as due_date
    FROM denda d
    JOIN users u ON d.user_id = u.id
    JOIN peminjaman p ON d.peminjaman_id = p.id
    JOIN buku b ON p.buku_id = b.id
    WHERE d.status_bayar = 'belum'
    ORDER BY d.created_at DESC
    LIMIT 5
")->fetchAll();

// Get overdue books
$overdueBooks = $pdo->query("
    SELECT p.*, u.nama as user_name, b.judul as buku_judul,
           DATEDIFF(CURDATE(), p.tgl_kembali) as days_overdue
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b ON p.buku_id = b.id
    WHERE p.status = 'dipinjam' AND p.tgl_kembali < CURDATE()
    ORDER BY p.tgl_kembali ASC
    LIMIT 5
")->fetchAll();

// Monthly borrowing data for chart
$monthlyData = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%M') as month,
        COUNT(*) as total
    FROM peminjaman
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
")->fetchAll();

$months = [];
$counts = [];
foreach ($monthlyData as $data) {
    $months[] = $data['month'];
    $counts[] = $data['total'];
}

// If no data, provide default months
if (empty($months)) {
    $defaultMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $months = $defaultMonths;
    $counts = array_fill(0, 12, 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        }
        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
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
        .badge-warning {
            background: #FEF3C7;
            color: #D97706;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success {
            background: #D1FAE5;
            color: #059669;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-danger {
            background: #FEE2E2;
            color: #DC2626;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 1rem;
            color: var(--color-primary);
        }
        .table-hover tbody tr:hover {
            background-color: #F9FAFB;
            transition: background 0.2s;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <header class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-playfair font-bold text-primary">Dashboard Admin</h1>
                    <p class="text-text-light mt-1">Selamat datang, <span class="font-semibold text-primary"><?= e($_SESSION['user_name']) ?></span>!</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button id="notifBtn" class="text-text-light hover:text-primary transition p-2 rounded-full hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Profile -->
                    <div class="flex items-center space-x-3">
                        <?php if ($_SESSION['user_foto']): ?>
                            <img src="../../uploads/covers/<?= e($_SESSION['user_foto']) ?>" class="w-10 h-10 rounded-full object-cover border-2 border-primary">
                        <?php else: ?>
                            <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center text-white font-bold text-lg">
                                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Stats Cards - 6 Cards for better overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 mb-8">
            <!-- Card Total Buku -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-xs uppercase tracking-wide">Total Buku</p>
                        <p class="text-2xl font-bold text-primary mt-1"><?= number_format($totalBuku) ?></p>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card Total Member -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-xs uppercase tracking-wide">Member Aktif</p>
                        <p class="text-2xl font-bold text-primary mt-1"><?= number_format($totalUser) ?></p>
                        <p class="text-xs text-text-light mt-1">Banned: <?= $totalBanned ?></p>
                    </div>
                    <div class="stats-icon bg-secondary bg-opacity-10">
                        <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card Sedang Dipinjam -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-xs uppercase tracking-wide">Sedang Dipinjam</p>
                        <p class="text-2xl font-bold text-primary mt-1"><?= number_format($totalDipinjam) ?></p>
                    </div>
                    <div class="stats-icon bg-accent bg-opacity-10">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card Dipinjam Hari Ini -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-xs uppercase tracking-wide">Dipinjam Hari Ini</p>
                        <p class="text-2xl font-bold text-primary mt-1"><?= number_format($dipinjamHariIni) ?></p>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card Denda Belum Bayar -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-xs uppercase tracking-wide">Denda Belum Bayar</p>
                        <p class="text-xl font-bold text-danger mt-1">Rp <?= number_format($dendaBelumBayar, 0, ',', '.') ?></p>
                    </div>
                    <div class="stats-icon bg-danger bg-opacity-10">
                        <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Card Buku Terlambat -->
            <div class="stats-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-light text-xs uppercase tracking-wide">Buku Terlambat</p>
                        <p class="text-2xl font-bold text-warning mt-1"><?= number_format(count($overdueBooks)) ?></p>
                    </div>
                    <div class="stats-icon bg-warning bg-opacity-10">
                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Row 1: Chart + Overdue Books -->
        <div class="grid lg:grid-cols-2 gap-6 mb-8">
            <!-- Chart Section -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="section-title">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Grafik Peminjaman Per Bulan</span>
                </div>
                <canvas id="loanChart" height="250"></canvas>
            </div>
            
            <!-- Overdue Books -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="section-title">
                    <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Buku Terlambat</span>
                </div>
                <div class="overflow-x-auto">
                    <?php if (empty($overdueBooks)): ?>
                        <div class="text-center py-8 text-text-light">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>Tidak ada buku terlambat</p>
                        </div>
                    <?php else: ?>
                        <table class="w-full text-sm table-hover">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Peminjam</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Buku</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Terlambat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueBooks as $book): ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="py-3 px-2"><?= e($book['user_name']) ?></td>
                                    <td class="py-3 px-2"><?= e($book['buku_judul']) ?></td>
                                    <td class="py-3 px-2 text-danger font-semibold"><?= $book['days_overdue'] ?> hari</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Row 2: Recent Loans + Unpaid Fines -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Recent Loans -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="section-title">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Peminjaman Terbaru</span>
                </div>
                <div class="overflow-x-auto">
                    <?php if (empty($recentLoans)): ?>
                        <div class="text-center py-8 text-text-light">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>Belum ada peminjaman</p>
                        </div>
                    <?php else: ?>
                        <table class="w-full text-sm table-hover">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Member</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Buku</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Tanggal</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLoans as $loan): ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="py-3 px-2"><?= e($loan['user_name']) ?></td>
                                    <td class="py-3 px-2"><?= e($loan['buku_judul']) ?></td>
                                    <td class="py-3 px-2"><?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></td>
                                    <td class="py-3 px-2">
                                        <span class="<?= $loan['status'] == 'dipinjam' ? 'badge-warning' : 'badge-success' ?>">
                                            <?= $loan['status'] == 'dipinjam' ? 'Dipinjam' : 'Dikembalikan' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Unpaid Fines -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="section-title">
                    <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Denda Belum Dibayar</span>
                </div>
                <div class="overflow-x-auto">
                    <?php if (empty($unpaidFines)): ?>
                        <div class="text-center py-8 text-text-light">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>Tidak ada denda belum dibayar</p>
                        </div>
                    <?php else: ?>
                        <table class="w-full text-sm table-hover">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Member</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Buku</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Denda</th>
                                    <th class="text-left py-3 px-2 font-semibold text-text-light">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unpaidFines as $fine): ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="py-3 px-2"><?= e($fine['user_name']) ?></td>
                                    <td class="py-3 px-2"><?= e($fine['buku_judul']) ?></td>
                                    <td class="py-3 px-2 text-danger font-semibold">Rp <?= number_format($fine['jumlah_denda'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-2">
                                        <a href="denda_siswa.php" class="text-primary hover:text-secondary transition">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            Bayar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize chart
        const ctx = document.getElementById('loanChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Jumlah Peminjaman',
                    data: <?= json_encode($counts) ?>,
                    borderColor: '#09637E',
                    backgroundColor: 'rgba(9, 99, 126, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#09637E',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1F2937',
                        titleColor: '#F3F4F6',
                        bodyColor: '#D1D5DB',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#E5E7EB'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>