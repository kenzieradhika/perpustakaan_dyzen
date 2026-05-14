<?php
require_once '../../config/data_base.php';
requireAdmin();

// Handle payment
if (isset($_GET['bayar']) && isset($_GET['id'])) {
    $denda_id = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("
        UPDATE denda 
        SET status_bayar = 'sudah', tgl_bayar = CURDATE() 
        WHERE id = ? AND status_bayar = 'belum'
    ");
    $stmt->execute([$denda_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Pembayaran denda berhasil dicatat.";
    }
    header('Location: denda_siswa.php');
    exit();
}

// Get all unpaid fines
$fines = $pdo->query("
    SELECT d.*, u.nama as user_name, u.nisn, u.kelas, 
           b.judul as buku_judul, p.tgl_kembali as due_date,
           DATEDIFF(CURDATE(), p.tgl_kembali) as actual_days_late
    FROM denda d
    JOIN users u ON d.user_id = u.id
    JOIN peminjaman p ON d.peminjaman_id = p.id
    JOIN buku b ON p.buku_id = b.id
    WHERE d.status_bayar = 'belum'
    ORDER BY d.created_at DESC
")->fetchAll();

// Get payment history
$paymentHistory = $pdo->query("
    SELECT d.*, u.nama as user_name, b.judul as buku_judul,
           d.tgl_bayar
    FROM denda d
    JOIN users u ON d.user_id = u.id
    JOIN peminjaman p ON d.peminjaman_id = p.id
    JOIN buku b ON p.buku_id = b.id
    WHERE d.status_bayar = 'sudah'
    ORDER BY d.tgl_bayar DESC
    LIMIT 20
")->fetchAll();

// Calculate total unpaid
$totalUnpaid = $pdo->query("
    SELECT COALESCE(SUM(jumlah_denda), 0) as total 
    FROM denda WHERE status_bayar = 'belum'
")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denda Siswa - PERPUSTAKAAN DYZEN</title>
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
        .btn-success {
            background: linear-gradient(135deg, #10B981, #059669);
            transition: all 0.3s;
        }
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Denda Siswa</h1>
                <p class="text-text-light">Kelola denda keterlambatan pengembalian buku</p>
            </div>
        </header>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= e($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Summary Card dengan SVG Baru -->
        <div class="bg-gradient-to-r from-danger to-red-600 rounded-lg shadow-sm p-6 mb-6 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-white text-opacity-90">Total Denda Belum Dibayar</p>
                    <p class="text-3xl font-bold">Rp <?= number_format($totalUnpaid, 0, ',', '.') ?></p>
                </div>
                <!-- SVG Icon - Currency/Money (dari request) -->
                <svg width="64px" height="64px" viewBox="0 0 24 24" stroke-width="1.5" fill="none" xmlns="http://www.w3.org/2000/svg" color="#ffffff" stroke="#ffffff">
                    <path d="M9 12C9.00007 12.8416 9 15.107 9 16.3941C9 16.7255 9.26863 16.9943 9.59998 16.9962C12.5662 17.0136 15 17.072 15 14.5C15 11.7564 12 12 9 12ZM9 12L9.00003 7.60592C9.00003 7.27453 9.26867 7.00571 9.60005 7.00377C12.5662 6.98641 15 6.92799 15 9.5C15 12.2436 12 12 9 12Z" stroke="#ffffff" stroke-width="1.5"></path>
                    <path d="M12 7L12 5.5" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M12 18.5L12 17" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22Z" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
        </div>
        
        <!-- Unpaid Fines Table -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <!-- SVG Icon - Alert/Warning -->
                <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <h3 class="font-bold text-lg">Denda Belum Dibayar</h3>
            </div>
            <div class="overflow-x-auto">
                <table id="finesTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr>
                            <th class="px-6 py-3 text-left">Siswa</th>
                            <th class="px-6 py-3 text-left">Kelas</th>
                            <th class="px-6 py-3 text-left">Buku</th>
                            <th class="px-6 py-3 text-center">Hari Terlambat</th>
                            <th class="px-6 py-3 text-center">Jumlah Denda</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fines as $fine): ?>
                        <tr class="border-b hover:bg-light">
                            <td class="px-6 py-3">
                                <div class="font-semibold"><?= e($fine['user_name']) ?></div>
                                <div class="text-xs text-text-light">NISN: <?= e($fine['nisn']) ?></div>
                            </td>
                            <td class="px-6 py-3"><?= e($fine['kelas']) ?></td>
                            <td class="px-6 py-3"><?= e($fine['buku_judul']) ?></td>
                            <td class="px-6 py-3 text-center">
                                <span class="text-danger font-bold"><?= $fine['hari_terlambat'] ?> hari</span>
                            </td>
                            <td class="px-6 py-3 text-center text-danger font-bold">
                                Rp <?= number_format($fine['jumlah_denda'], 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <a href="?bayar=1&id=<?= $fine['id'] ?>" 
                                   onclick="return confirm('Tandai pembayaran denda ini sebagai LUNAS?')"
                                   class="btn-success inline-block px-4 py-1 rounded text-white text-sm">
                                    Tandai Bayar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fines)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-text-light">
                                Tidak ada denda yang belum dibayar
                            </td>
                        </table>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <!-- SVG Icon - History/Document -->
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1M9 8h6M9 12h6M9 16h6M15 21l3-3m0 0l-3-3m3 3a9 9 0 00-9-9 9 9 0 00-9 9 9 9 0 009 9 9 9 0 009-9z"></path>
                </svg>
                <h3 class="font-bold text-lg">Riwayat Pembayaran Denda</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-6 py-3 text-left">Tanggal Bayar</th>
                            <th class="px-6 py-3 text-left">Siswa</th>
                            <th class="px-6 py-3 text-left">Buku</th>
                            <th class="px-6 py-3 text-center">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentHistory as $payment): ?>
                        <tr class="border-b">
                            <td class="px-6 py-3"><?= date('d/m/Y', strtotime($payment['tgl_bayar'])) ?></td>
                            <td class="px-6 py-3"><?= e($payment['user_name']) ?></td>
                            <td class="px-6 py-3"><?= e($payment['buku_judul']) ?></td>
                            <td class="px-6 py-3 text-center text-success font-bold">
                                Rp <?= number_format($payment['jumlah_denda'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($paymentHistory)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-text-light">
                                Belum ada riwayat pembayaran
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#finesTable').DataTable({
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