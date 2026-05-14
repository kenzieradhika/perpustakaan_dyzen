<?php
require_once '../../config/data_base.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all fines for user
$stmt = $pdo->prepare("
    SELECT d.*, b.judul as buku_judul, p.tgl_kembali as due_date,
           DATEDIFF(CURDATE(), p.tgl_kembali) as actual_days_late
    FROM denda d
    JOIN peminjaman p ON d.peminjaman_id = p.id
    JOIN buku b ON p.buku_id = b.id
    WHERE d.user_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$user_id]);
$fines = $stmt->fetchAll();

// Calculate totals
$total_unpaid = 0;
$total_paid = 0;
foreach ($fines as $fine) {
    if ($fine['status_bayar'] == 'belum') {
        $total_unpaid += $fine['jumlah_denda'];
    } else {
        $total_paid += $fine['jumlah_denda'];
    }
}

// Check if user has any active loans that might generate new fines
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM peminjaman 
    WHERE user_id = ? AND status = 'dipinjam' AND tgl_kembali < CURDATE()
");
$stmt->execute([$user_id]);
$overdue_count = $stmt->fetch()['total'];

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Denda - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
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
        .fine-card {
            transition: transform 0.3s;
        }
        .fine-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Cek Denda</h1>
                <p class="text-text-light">Informasi denda keterlambatan pengembalian buku</p>
            </div>
        </header>
        
        <!-- Summary Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="fine-card bg-gradient-to-r from-danger to-red-600 rounded-lg p-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-white text-opacity-90">Total Denda Belum Bayar</p>
                        <p class="text-3xl font-bold">Rp <?= number_format($total_unpaid, 0, ',', '.') ?></p>
                    </div>
                    <svg class="w-12 h-12 text-white text-opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            
            <div class="fine-card bg-gradient-to-r from-success to-green-600 rounded-lg p-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-white text-opacity-90">Total Denda Sudah Dibayar</p>
                        <p class="text-3xl font-bold">Rp <?= number_format($total_paid, 0, ',', '.') ?></p>
                    </div>
                    <svg class="w-12 h-12 text-white text-opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            
            <div class="fine-card bg-gradient-to-r from-warning to-yellow-600 rounded-lg p-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-white text-opacity-90">Buku Terlambat</p>
                        <p class="text-3xl font-bold"><?= $overdue_count ?> Buku</p>
                    </div>
                    <svg class="w-12 h-12 text-white text-opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Warning if overdue -->
        <?php if ($overdue_count > 0): ?>
            <div class="bg-red-100 border-l-4 border-danger text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="font-semibold">Perhatian!</span>
                    <span class="ml-2">Anda memiliki <?= $overdue_count ?> buku yang terlambat. Segera kembalikan untuk menghindari denda lebih besar.</span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Denda Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <h3 class="font-bold text-lg p-6 border-b">Riwayat Denda</h3>
            <div class="overflow-x-auto">
                <table id="dendaTable" class="w-full">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-6 py-3 text-left">Tanggal</th>
                            <th class="px-6 py-3 text-left">Judul Buku</th>
                            <th class="px-6 py-3 text-center">Hari Terlambat</th>
                            <th class="px-6 py-3 text-center">Jumlah Denda</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Tanggal Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fines as $fine): ?>
                        <tr class="border-b hover:bg-light">
                            <td class="px-6 py-3"><?= date('d/m/Y', strtotime($fine['created_at'])) ?></td>
                            <td class="px-6 py-3">
                                <span class="font-semibold"><?= e($fine['buku_judul']) ?></span>
                            </td>
                            <td class="px-6 py-3 text-center"><?= $fine['hari_terlambat'] ?> hari</td>
                            <td class="px-6 py-3 text-center font-bold <?= $fine['status_bayar'] == 'belum' ? 'text-danger' : 'text-success' ?>">
                                Rp <?= number_format($fine['jumlah_denda'], 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="badge <?= $fine['status_bayar'] == 'belum' ? 'badge-danger' : 'badge-success' ?>">
                                    <?= $fine['status_bayar'] == 'belum' ? 'Belum Dibayar' : 'Lunas' ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <?= $fine['tgl_bayar'] ? date('d/m/Y', strtotime($fine['tgl_bayar'])) : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (empty($fines)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-text-light">
                                <svg class="w-16 h-16 mx-auto text-accent mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p>Belum ada riwayat denda.</p>
                                <p class="text-sm mt-1">Selamat! Anda selalu mengembalikan buku tepat waktu.</p>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Informasi Denda -->
        <div class="bg-light rounded-lg p-6 mt-6">
            <h3 class="font-bold text-lg mb-3">📌 Informasi Denda</h3>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-primary mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Denda keterlambatan: <strong>Rp <?= number_format(DENDA_PER_HARI, 0, ',', '.') ?></strong> per hari</span>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-primary mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Denda dihitung otomatis saat buku dikembalikan</span>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-primary mt-0.5" fill="none" stroke="currentColor"viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Pembayaran denda dilakukan di perpustakaan</span>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-primary mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Denda harus lunas sebelum dapat meminjam buku lagi</span>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dendaTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                pageLength: 10,
                order: [[0, 'desc']],
                responsive: true
            });
        });
    </script>
</body>
</html>