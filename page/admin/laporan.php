<?php
require_once '../../config/data_base.php';
requireAdmin();

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'loans';

// Get data based on report type
$loans_data = [];
$fines_data = [];
$users_data = [];
$books_data = [];

if ($report_type == 'loans') {
    // Peminjaman data
    $stmt = $pdo->prepare("
        SELECT 
            DATE(p.created_at) as tanggal,
            COUNT(*) as total_pinjam,
            COUNT(DISTINCT p.user_id) as total_member,
            SUM(CASE WHEN p.status = 'dikembalikan' THEN 1 ELSE 0 END) as total_kembali
        FROM peminjaman p
        WHERE DATE(p.created_at) BETWEEN ? AND ?
        GROUP BY DATE(p.created_at)
        ORDER BY tanggal DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $loans_data = $stmt->fetchAll();
    
    // Detail peminjaman
    $stmt = $pdo->prepare("
        SELECT p.*, u.nama as user_name, u.nisn, u.kelas, b.judul as buku_judul, b.pengarang
        FROM peminjaman p
        JOIN users u ON p.user_id = u.id
        JOIN buku b ON p.buku_id = b.id
        WHERE DATE(p.created_at) BETWEEN ? AND ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $loans_detail = $stmt->fetchAll();
    
} elseif ($report_type == 'fines') {
    // Denda data
    $stmt = $pdo->prepare("
        SELECT 
            DATE(d.created_at) as tanggal,
            COUNT(*) as total_denda,
            SUM(d.jumlah_denda) as total_nominal,
            SUM(CASE WHEN d.status_bayar = 'sudah' THEN d.jumlah_denda ELSE 0 END) as total_terbayar,
            SUM(CASE WHEN d.status_bayar = 'belum' THEN d.jumlah_denda ELSE 0 END) as total_belum_bayar
        FROM denda d
        WHERE DATE(d.created_at) BETWEEN ? AND ?
        GROUP BY DATE(d.created_at)
        ORDER BY tanggal DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $fines_data = $stmt->fetchAll();
    
    // Detail denda
    $stmt = $pdo->prepare("
        SELECT d.*, u.nama as user_name, u.nisn, u.kelas, 
               b.judul as buku_judul, p.tgl_kembali as due_date
        FROM denda d
        JOIN users u ON d.user_id = u.id
        JOIN peminjaman p ON d.peminjaman_id = p.id
        JOIN buku b ON p.buku_id = b.id
        WHERE DATE(d.created_at) BETWEEN ? AND ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $fines_detail = $stmt->fetchAll();
    
} elseif ($report_type == 'users') {
    // User statistics
    $stmt = $pdo->prepare("
        SELECT 
            u.kelas,
            COUNT(*) as total_user,
            SUM(CASE WHEN u.status = 'aktif' THEN 1 ELSE 0 END) as aktif,
            SUM(CASE WHEN u.status = 'banned' THEN 1 ELSE 0 END) as banned,
            COUNT(DISTINCT p.user_id) as pernah_pinjam
        FROM users u
        LEFT JOIN peminjaman p ON u.id = p.user_id
        WHERE u.role = 'user'
        GROUP BY u.kelas
        ORDER BY u.kelas
    ");
    $stmt->execute();
    $users_data = $stmt->fetchAll();
    
} elseif ($report_type == 'books') {
    // Book statistics
    $stmt = $pdo->prepare("
        SELECT 
            b.kategori,
            COUNT(*) as total_buku,
            SUM(b.stok) as total_stok,
            SUM(b.stok_tersedia) as stok_tersedia,
            COUNT(DISTINCT p.id) as total_dipinjam
        FROM buku b
        LEFT JOIN peminjaman p ON b.id = p.buku_id AND p.status = 'dipinjam'
        WHERE b.status = 'aktif'
        GROUP BY b.kategori
        ORDER BY total_buku DESC
    ");
    $stmt->execute();
    $books_data = $stmt->fetchAll();
}

// Calculate totals
$totalPinjaman = array_sum(array_column($loans_data, 'total_pinjam'));
$totalPengembalian = array_sum(array_column($loans_data, 'total_kembali'));
$totalDenda = array_sum(array_column($fines_data, 'total_nominal'));
$totalDendaTerbayar = array_sum(array_column($fines_data, 'total_terbayar'));

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        @media print {
            .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6 no-print">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-playfair font-bold text-primary">Laporan Perpustakaan</h1>
                    <p class="text-text-light">Analisis data peminjaman, denda, dan statistik</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="window.print()" class="btn-outline">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </button>
                    <button onclick="exportToExcel()" class="btn-primary">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export Excel
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Filter Form -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 no-print">
            <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-text font-semibold mb-2">Tipe Laporan</label>
                    <select name="report_type" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <option value="loans" <?= $report_type == 'loans' ? 'selected' : '' ?>>Peminjaman</option>
                        <option value="fines" <?= $report_type == 'fines' ? 'selected' : '' ?>>Denda</option>
                        <option value="users" <?= $report_type == 'users' ? 'selected' : '' ?>>Statistik User</option>
                        <option value="books" <?= $report_type == 'books' ? 'selected' : '' ?>>Statistik Buku</option>
                    </select>
                </div>
                <div>
                    <label class="block text-text font-semibold mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?= e($start_date) ?>"
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-text font-semibold mb-2">Tanggal Akhir</label>
                    <input type="date" name="end_date" value="<?= e($end_date) ?>"
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full">Tampilkan Laporan</button>
                </div>
            </form>
        </div>
        
        <?php if ($report_type == 'loans'): ?>
            <!-- Loans Report -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-r from-primary to-secondary rounded-lg p-6 text-white">
                    <p class="text-white text-opacity-90">Total Peminjaman</p>
                    <p class="text-3xl font-bold"><?= number_format($totalPinjaman) ?></p>
                </div>
                <div class="bg-gradient-to-r from-success to-green-600 rounded-lg p-6 text-white">
                    <p class="text-white text-opacity-90">Total Pengembalian</p>
                    <p class="text-3xl font-bold"><?= number_format($totalPengembalian) ?></p>
                </div>
                <div class="bg-gradient-to-r from-warning to-yellow-600 rounded-lg p-6 text-white">
                    <p class="text-white text-opacity-90">Rata-rata per Hari</p>
                    <p class="text-3xl font-bold"><?= number_format(round($totalPinjaman / max(1, count($loans_data)), 1)) ?></p>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h3 class="font-bold text-lg mb-4">Grafik Peminjaman</h3>
                <canvas id="loansChart" height="200"></canvas>
            </div>
            
            <!-- Detail Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <h3 class="font-bold text-lg p-6 border-b">Detail Peminjaman</h3>
                <div class="overflow-x-auto">
                    <table id="loansTable" class="w-full">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-6 py-3 text-left">Tanggal</th>
                                <th class="px-6 py-3 text-left">Member</th>
                                <th class="px-6 py-3 text-left">Buku</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Jatuh Tempo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loans_detail as $loan): ?>
                            <tr class="border-b">
                                <td class="px-6 py-3"><?= date('d/m/Y', strtotime($loan['created_at'])) ?></td>
                                <td class="px-6 py-3">
                                    <?= e($loan['user_name']) ?><br>
                                    <span class="text-xs text-text-light"><?= e($loan['nisn']) ?> | <?= e($loan['kelas']) ?></span>
                                </td>
                                <td class="px-6 py-3">
                                    <?= e($loan['buku_judul']) ?><br>
                                    <span class="text-xs text-text-light"><?= e($loan['pengarang']) ?></span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span class="badge <?= $loan['status'] == 'dipinjam' ? 'badge-warning' : 'badge-success' ?>">
                                        <?= $loan['status'] == 'dipinjam' ? 'Dipinjam' : 'Dikembalikan' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ($report_type == 'fines'): ?>
            <!-- Fines Report -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-r from-danger to-red-600 rounded-lg p-6 text-white">
                    <p class="text-white text-opacity-90">Total Denda</p>
                    <p class="text-3xl font-bold">Rp <?= number_format($totalDenda, 0, ',', '.') ?></p>
                </div>
                <div class="bg-gradient-to-r from-success to-green-600 rounded-lg p-6 text-white">
                    <p class="text-white text-opacity-90">Denda Terbayar</p>
                    <p class="text-3xl font-bold">Rp <?= number_format($totalDendaTerbayar, 0, ',', '.') ?></p>
                </div>
                <div class="bg-gradient-to-r from-warning to-yellow-600 rounded-lg p-6 text-white">
                    <p class="text-white text-opacity-90">Denda Belum Bayar</p>
                    <p class="text-3xl font-bold">Rp <?= number_format($totalDenda - $totalDendaTerbayar, 0, ',', '.') ?></p>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h3 class="font-bold text-lg mb-4">Grafik Denda</h3>
                <canvas id="finesChart" height="200"></canvas>
            </div>
            
            <!-- Detail Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <h3 class="font-bold text-lg p-6 border-b">Detail Denda</h3>
                <div class="overflow-x-auto">
                    <table id="finesTable" class="w-full">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-6 py-3 text-left">Tanggal</th>
                                <th class="px-6 py-3 text-left">Member</th>
                                <th class="px-6 py-3 text-left">Buku</th>
                                <th class="px-6 py-3 text-center">Hari</th>
                                <th class="px-6 py-3 text-center">Jumlah</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fines_detail as $fine): ?>
                            <tr class="border-b">
                                <td class="px-6 py-3"><?= date('d/m/Y', strtotime($fine['created_at'])) ?></td>
                                <td class="px-6 py-3">
                                    <?= e($fine['user_name']) ?><br>
                                    <span class="text-xs text-text-light"><?= e($fine['nisn']) ?> | <?= e($fine['kelas']) ?></span>
                                </td>
                                <td class="px-6 py-3"><?= e($fine['buku_judul']) ?></td>
                                <td class="px-6 py-3 text-center"><?= $fine['hari_terlambat'] ?> hari</td>
                                <td class="px-6 py-3 text-center text-danger font-bold">
                                    Rp <?= number_format($fine['jumlah_denda'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span class="badge <?= $fine['status_bayar'] == 'sudah' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $fine['status_bayar'] == 'sudah' ? 'Lunas' : 'Belum Bayar' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ($report_type == 'users'): ?>
            <!-- Users Statistics -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <h3 class="font-bold text-lg p-6 border-b">Statistik Member per Kelas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-6 py-3 text-left">Kelas</th>
                                <th class="px-6 py-3 text-center">Total Member</th>
                                <th class="px-6 py-3 text-center">Aktif</th>
                                <th class="px-6 py-3 text-center">Banned</th>
                                <th class="px-6 py-3 text-center">Pernah Pinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_data as $data): ?>
                            <tr class="border-b">
                                <td class="px-6 py-3 font-semibold"><?= e($data['kelas']) ?></td>
                                <td class="px-6 py-3 text-center"><?= $data['total_user'] ?></td>
                                <td class="px-6 py-3 text-center text-success"><?= $data['aktif'] ?></td>
                                <td class="px-6 py-3 text-center text-danger"><?= $data['banned'] ?></td>
                                <td class="px-6 py-3 text-center"><?= $data['pernah_pinjam'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ($report_type == 'books'): ?>
            <!-- Books Statistics -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <h3 class="font-bold text-lg p-6 border-b">Statistik Buku per Kategori</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-6 py-3 text-left">Kategori</th>
                                <th class="px-6 py-3 text-center">Jumlah Judul</th>
                                <th class="px-6 py-3 text-center">Total Stok</th>
                                <th class="px-6 py-3 text-center">Stok Tersedia</th>
                                <th class="px-6 py-3 text-center">Sedang Dipinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books_data as $data): ?>
                            <tr class="border-b">
                                <td class="px-6 py-3 font-semibold"><?= e($data['kategori']) ?></td>
                                <td class="px-6 py-3 text-center"><?= $data['total_buku'] ?></td>
                                <td class="px-6 py-3 text-center"><?= $data['total_stok'] ?></td>
                                <td class="px-6 py-3 text-center text-success"><?= $data['stok_tersedia'] ?></td>
                                <td class="px-6 py-3 text-center text-warning"><?= $data['total_dipinjam'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        <?php if ($report_type == 'loans' && !empty($loans_data)): ?>
        // Loans Chart
        const loansCtx = document.getElementById('loansChart').getContext('2d');
        new Chart(loansCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($loans_data, 'tanggal')) ?>,
                datasets: [{
                    label: 'Jumlah Peminjaman',
                    data: <?= json_encode(array_column($loans_data, 'total_pinjam')) ?>,
                    borderColor: '#09637E',
                    backgroundColor: 'rgba(9, 99, 126, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        $('#loansTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' },
            pageLength: 10,
            order: [[0, 'desc']]
        });
        <?php endif; ?>
        
        <?php if ($report_type == 'fines' && !empty($fines_data)): ?>
        // Fines Chart
        const finesCtx = document.getElementById('finesChart').getContext('2d');
        new Chart(finesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($fines_data, 'tanggal')) ?>,
                datasets: [{
                    label: 'Total Denda',
                    data: <?= json_encode(array_column($fines_data, 'total_nominal')) ?>,
                    backgroundColor: '#E53E3E',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        $('#finesTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' },
            pageLength: 10,
            order: [[0, 'desc']]
        });
        <?php endif; ?>
        
        function exportToExcel() {
            const table = document.querySelector('table');
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/,/g, ' ');
                    row.push('"' + text + '"');
                }
                csv.push(row.join(','));
            }
            
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `laporan_<?= $report_type ?>_<?= date('Y-m-d') ?>.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>