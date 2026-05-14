<?php
require_once '../../config/data_base.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user's active loans
$stmt = $pdo->prepare("
    SELECT p.*, b.judul, b.cover, b.pengarang,
           DATEDIFF(CURDATE(), p.tgl_kembali) as days_late
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.user_id = ? AND p.status = 'dipinjam'
    ORDER BY p.tgl_kembali ASC
");
$stmt->execute([$user_id]);
$loans = $stmt->fetchAll();

// Handle return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['peminjaman_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $peminjaman_id = (int)$_POST['peminjaman_id'];
        
        // Get loan details
        $stmt = $pdo->prepare("
            SELECT p.*, b.stok 
            FROM peminjaman p
            JOIN buku b ON p.buku_id = b.id
            WHERE p.id = ? AND p.user_id = ? AND p.status = 'dipinjam'
        ");
        $stmt->execute([$peminjaman_id, $user_id]);
        $loan = $stmt->fetch();
        
        if (!$loan) {
            $error = 'Data peminjaman tidak ditemukan.';
        } else {
            $days_late = max(0, (int)$_POST['days_late']);
            $denda = $days_late * DENDA_PER_HARI;
            
            // Start transaction
            $pdo->beginTransaction();
            try {
                // Update peminjaman
                $stmt = $pdo->prepare("
                    UPDATE peminjaman 
                    SET tgl_dikembalikan = CURDATE(), status = 'dikembalikan'
                    WHERE id = ?
                ");
                $stmt->execute([$peminjaman_id]);
                
                // Update book stock
                $stmt = $pdo->prepare("
                    UPDATE buku SET stok_tersedia = stok_tersedia + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$loan['buku_id']]);
                
                // Insert denda if late
                if ($days_late > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO denda (peminjaman_id, user_id, hari_terlambat, jumlah_denda, status_bayar)
                        VALUES (?, ?, ?, ?, 'belum')
                    ");
                    $stmt->execute([$peminjaman_id, $user_id, $days_late, $denda]);
                    
                    // Add warning notification
                    $stmt = $pdo->prepare("
                        INSERT INTO peringatan (user_id, pesan, tipe)
                        VALUES (?, ?, 'danger')
                    ");
                    $pesan = "Anda terlambat mengembalikan buku '{$loan['judul']}' selama {$days_late} hari. Denda Rp " . number_format($denda, 0, ',', '.');
                    $stmt->execute([$user_id, $pesan]);
                    
                    $success = "Buku berhasil dikembalikan. Anda dikenakan denda Rp " . number_format($denda, 0, ',', '.');
                } else {
                    $success = "Buku berhasil dikembalikan. Terima kasih!";
                }
                
                $pdo->commit();
                
                // Refresh loans
                $stmt = $pdo->prepare("
                    SELECT p.*, b.judul, b.cover, b.pengarang,
                           DATEDIFF(CURDATE(), p.tgl_kembali) as days_late
                    FROM peminjaman p
                    JOIN buku b ON p.buku_id = b.id
                    WHERE p.user_id = ? AND p.status = 'dipinjam'
                    ORDER BY p.tgl_kembali ASC
                ");
                $stmt->execute([$user_id]);
                $loans = $stmt->fetchAll();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal mengembalikan buku. Silakan coba lagi.';
                error_log($e->getMessage());
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kembalikan Buku - PERPUSTAKAAN DYZEN</title>
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
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Kembalikan Buku</h1>
                <p class="text-text-light">Kembalikan buku yang sedang Anda pinjam</p>
            </div>
        </header>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= e($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($loans)): ?>
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="w-20 h-20 mx-auto text-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-text-light">Tidak ada buku yang sedang dipinjam.</p>
                <a href="list_buku.php" class="btn-primary mt-4 inline-block">Pinjam Buku</a>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($loans as $loan): ?>
                    <?php 
                    $isLate = $loan['days_late'] > 0;
                    $denda = $isLate ? $loan['days_late'] * DENDA_PER_HARI : 0;
                    ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden <?= $isLate ? 'border-l-4 border-danger' : 'border-l-4 border-success' ?>">
                        <div class="flex p-6 gap-6">
                            <?php if ($loan['cover']): ?>
                                <img src="../../uploads/covers/<?= e($loan['cover']) ?>" class="w-32 h-40 object-cover rounded">
                            <?php else: ?>
                                <div class="w-32 h-40 bg-gradient-to-br from-primary to-secondary rounded flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-1">
                                <h3 class="font-bold text-xl mb-2"><?= e($loan['judul']) ?></h3>
                                <p class="text-text-light"><?= e($loan['pengarang']) ?></p>
                                
                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <p class="text-text-light text-sm">Tanggal Pinjam</p>
                                        <p class="font-semibold"><?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-text-light text-sm">Jatuh Tempo</p>
                                        <p class="font-semibold <?= $isLate ? 'text-danger' : '' ?>">
                                            <?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?>
                                            <?php if ($isLate): ?>
                                                <span class="text-danger text-sm">(Terlambat <?= $loan['days_late'] ?> hari)</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if ($isLate): ?>
                                    <div class="mt-4 p-3 bg-red-50 rounded-lg">
                                        <p class="text-danger font-semibold">Denda Keterlambatan:</p>
                                        <p class="text-danger text-xl font-bold">Rp <?= number_format($denda, 0, ',', '.') ?></p>
                                        <p class="text-text-light text-xs mt-1">Denda Rp <?= number_format(DENDA_PER_HARI, 0, ',', '.') ?> per hari</p>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" class="mt-6" onsubmit="return confirm('Konfirmasi pengembalian buku ini?<?= $isLate ? ' Anda akan dikenakan denda.' : '' ?>')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="peminjaman_id" value="<?= $loan['id'] ?>">
                                    <input type="hidden" name="days_late" value="<?= $loan['days_late'] ?>">
                                    <button type="submit" class="btn-primary">
                                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                        Kembalikan Buku
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>