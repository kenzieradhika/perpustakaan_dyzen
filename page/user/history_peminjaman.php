<?php
require_once '../../config/data_base.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get all borrowing history
$stmt = $pdo->prepare("
    SELECT p.*, b.judul, b.cover, b.pengarang, b.kategori,
           CASE 
               WHEN p.status = 'dipinjam' AND p.tgl_kembali < CURDATE() THEN 'terlambat'
               WHEN p.status = 'dipinjam' THEN 'aktif'
               ELSE 'selesai'
           END as status_detail,
           DATEDIFF(CURDATE(), p.tgl_kembali) as days_overdue
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$histories = $stmt->fetchAll();

// Statistics
$total_borrowed = count($histories);
$active_loans = 0;
$completed_loans = 0;
$overdue_loans = 0;

foreach ($histories as $history) {
    if ($history['status'] == 'dipinjam') {
        $active_loans++;
        if ($history['tgl_kembali'] < date('Y-m-d')) {
            $overdue_loans++;
        }
    } else {
        $completed_loans++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - PERPUSTAKAAN DYZEN</title>
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
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Riwayat Peminjaman</h1>
                <p class="text-text-light">Histori lengkap peminjaman buku Anda</p>
            </div>
        </header>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-primary"><?= $total_borrowed ?></p>
                <p class="text-text-light text-sm">Total Peminjaman</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-warning"><?= $active_loans ?></p>
                <p class="text-text-light text-sm">Sedang Dipinjam</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-success"><?= $completed_loans ?></p>
                <p class="text-text-light text-sm">Selesai</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-danger"><?= $overdue_loans ?></p>
                <p class="text-text-light text-sm">Terlambat</p>
            </div>
        </div>
        
        <!-- History Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="historyTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr>
                            <th class="px-6 py-3 text-left">Tanggal Pinjam</th>
                            <th class="px-6 py-3 text-left">Buku</th>
                            <th class="px-6 py-3 text-center">Jatuh Tempo</th>
                            <th class="px-6 py-3 text-center">Dikembalikan</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($histories as $history): ?>
                            <?php
                            $is_overdue = ($history['status'] == 'dipinjam' && $history['tgl_kembali'] < date('Y-m-d'));
                            $status_class = '';
                            $status_text = '';
                            
                            if ($history['status'] == 'dipinjam') {
                                if ($is_overdue) {
                                    $status_class = 'badge-danger';
                                    $status_text = 'Terlambat';
                                } else {
                                    $status_class = 'badge-warning';
                                    $status_text = 'Dipinjam';
                                }
                            } else {
                                $status_class = 'badge-success';
                                $status_text = 'Dikembalikan';
                            }
                            ?>
                            <tr class="border-b hover:bg-light">
                                <td class="px-6 py-3"><?= date('d/m/Y', strtotime($history['tgl_pinjam'])) ?></td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center space-x-3">
                                        <?php if ($history['cover']): ?>
                                            <img src="../../uploads/covers/<?= e($history['cover']) ?>" class="w-10 h-14 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-10 h-14 bg-accent bg-opacity-20 rounded flex items-center justify-center">
                                                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-semibold"><?= e($history['judul']) ?></p>
                                            <p class="text-text-light text-sm"><?= e($history['pengarang']) ?></p>
                                            <span class="badge badge-info text-xs"><?= e($history['kategori']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <p class="<?= $is_overdue ? 'text-danger font-bold' : 'text-text' ?>">
                                        <?= date('d/m/Y', strtotime($history['tgl_kembali'])) ?>
                                    </p>
                                    <?php if ($is_overdue): ?>
                                        <p class="text-danger text-xs">Terlambat <?= abs($history['days_overdue']) ?> hari</p>
                                    <?php elseif ($history['status'] == 'dipinjam'): ?>
                                        <p class="text-success text-xs">Sisa <?= ceil((strtotime($history['tgl_kembali']) - time()) / 86400) ?> hari</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <?= $history['tgl_dikembalikan'] ? date('d/m/Y', strtotime($history['tgl_dikembalikan'])) : '-' ?>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (empty($histories)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-text-light">
                                <svg class="w-20 h-20 mx-auto text-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p>Belum ada riwayat peminjaman.</p>
                                <a href="list_buku.php" class="btn-primary inline-block mt-4">Pinjam Buku Sekarang</a>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#historyTable').DataTable({
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