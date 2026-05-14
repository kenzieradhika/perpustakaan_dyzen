<?php
require_once '../../config/data_base.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$selected_book = null;

// Check if user has any unpaid fines
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM denda 
    WHERE user_id = ? AND status_bayar = 'belum'
");
$stmt->execute([$user_id]);
$hasFine = $stmt->fetch()['total'] > 0;

// Check current active loans count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM peminjaman 
    WHERE user_id = ? AND status = 'dipinjam'
");
$stmt->execute([$user_id]);
$activeLoans = $stmt->fetch()['total'];

$canBorrow = !$hasFine && $activeLoans < MAX_BOOKS_PER_USER;

// Get book if selected
if (isset($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT * FROM buku 
        WHERE id = ? AND status = 'aktif' AND stok_tersedia > 0
    ");
    $stmt->execute([$book_id]);
    $selected_book = $stmt->fetch();
}

// Handle borrow request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif (!$canBorrow) {
        if ($hasFine) {
            $error = 'Anda memiliki denda yang belum dibayar. Selesaikan denda terlebih dahulu.';
        } elseif ($activeLoans >= MAX_BOOKS_PER_USER) {
            $error = 'Anda已达到 batas maksimal peminjaman (' . MAX_BOOKS_PER_USER . ' buku). Kembalikan beberapa buku terlebih dahulu.';
        }
    } else {
        $book_id = (int)$_POST['book_id'];
        
        // Get book stock
        $stmt = $pdo->prepare("SELECT stok_tersedia FROM buku WHERE id = ? AND status = 'aktif'");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book || $book['stok_tersedia'] <= 0) {
            $error = 'Buku tidak tersedia untuk dipinjam.';
        } else {
            // Calculate due date (7 days from now)
            $tgl_pinjam = date('Y-m-d');
            $tgl_kembali = date('Y-m-d', strtotime('+' . MAX_PINJAM_HARI . ' days'));
            
            // Start transaction
            $pdo->beginTransaction();
            try {
                // Create peminjaman record
                $stmt = $pdo->prepare("
                    INSERT INTO peminjaman (user_id, buku_id, tgl_pinjam, tgl_kembali, status)
                    VALUES (?, ?, ?, ?, 'dipinjam')
                ");
                $stmt->execute([$user_id, $book_id, $tgl_pinjam, $tgl_kembali]);
                
                // Update book stock
                $stmt = $pdo->prepare("
                    UPDATE buku SET stok_tersedia = stok_tersedia - 1 
                    WHERE id = ?
                ");
                $stmt->execute([$book_id]);
                
                $pdo->commit();
                $success = 'Buku berhasil dipinjam! Harap kembalikan sebelum ' . date('d/m/Y', strtotime($tgl_kembali));
                
                // Clear selected book
                $selected_book = null;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal meminjam buku. Silakan coba lagi.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get available books for browsing
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? sanitize($_GET['kategori']) : '';

$query = "
    SELECT * FROM buku 
    WHERE status = 'aktif' AND stok_tersedia > 0
";
$params = [];

if ($search) {
    $query .= " AND (judul LIKE ? OR pengarang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($kategori) {
    $query .= " AND kategori = ?";
    $params[] = $kategori;
}

$query .= " ORDER BY judul ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories
$categories = $pdo->query("
    SELECT DISTINCT kategori 
    FROM buku 
    WHERE kategori IS NOT NULL AND status = 'aktif'
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - PERPUSTAKAAN DYZEN</title>
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
        .book-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .warning-banner {
            background: linear-gradient(135deg, var(--color-warning), #f6ad55);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Pinjam Buku</h1>
                <p class="text-text-light">Pilih buku yang ingin Anda pinjam</p>
            </div>
        </header>
        
        <!-- Warning Messages -->
        <?php if ($hasFine): ?>
            <div class="warning-banner text-white rounded-lg p-4 mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Anda memiliki denda yang belum dibayar. Selesaikan denda terlebih dahulu untuk dapat meminjam buku.</span>
                </div>
                <a href="cek_denda.php" class="bg-white text-warning px-4 py-2 rounded-lg font-semibold">Cek Denda</a>
            </div>
        <?php endif; ?>
        
        <?php if ($activeLoans >= MAX_BOOKS_PER_USER): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Anda sudah mencapai batas maksimal peminjaman (<?= MAX_BOOKS_PER_USER ?> buku). Kembalikan beberapa buku terlebih dahulu.</span>
                </div>
            </div>
        <?php endif; ?>
        
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
        
        <!-- Search & Filter -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="" class="grid md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" placeholder="Cari judul atau pengarang..." 
                           value="<?= e($search) ?>"
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                </div>
                <div>
                    <select name="kategori" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['kategori']) ?>" <?= $kategori == $cat['kategori'] ? 'selected' : '' ?>>
                            <?= e($cat['kategori']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full">Cari Buku</button>
                </div>
            </form>
        </div>
        
        <!-- Available Books Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($books as $book): ?>
            <div class="book-card bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="flex p-4 gap-4">
                    <?php if ($book['cover']): ?>
                        <img src="../../uploads/covers/<?= e($book['cover']) ?>" class="w-24 h-32 object-cover rounded">
                    <?php else: ?>
                        <div class="w-24 h-32 bg-gradient-to-br from-primary to-secondary rounded flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex-1">
                        <h3 class="font-bold text-lg mb-1"><?= e($book['judul']) ?></h3>
                        <p class="text-text-light text-sm"><?= e($book['pengarang']) ?></p>
                        <p class="text-text-light text-xs mt-1"><?= e($book['penerbit']) ?> | <?= $book['tahun_terbit'] ?></p>
                        <div class="flex items-center justify-between mt-3">
                            <span class="badge badge-info"><?= e($book['kategori']) ?></span>
                            <span class="text-success font-bold">Stok: <?= $book['stok_tersedia'] ?></span>
                        </div>
                        
                        <?php if ($canBorrow && !$hasFine && $activeLoans < MAX_BOOKS_PER_USER): ?>
                            <?php if ($selected_book && $selected_book['id'] == $book['id']): ?>
                                <form method="POST" action="" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <div class="bg-light p-3 rounded-lg mb-2">
                                        <p class="text-sm text-text mb-2">Konfirmasi peminjaman:</p>
                                        <p class="text-xs text-text-light">Durasi: <?= MAX_PINJAM_HARI ?> hari</p>
                                        <p class="text-xs text-text-light">Jatuh tempo: <?= date('d/m/Y', strtotime('+' . MAX_PINJAM_HARI . ' days')) ?></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="pinjam_buku.php" class="btn-outline text-sm py-1">Batal</a>
                                        <button type="submit" class="btn-primary text-sm py-1">Konfirmasi Pinjam</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <a href="?id=<?= $book['id'] . ($search ? '&search=' . urlencode($search) : '') . ($kategori ? '&kategori=' . urlencode($kategori) : '') ?>" 
                                   class="btn-primary text-sm py-1 mt-3 inline-block text-center w-full">
                                    Pinjam Buku
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button disabled class="btn-outline text-sm py-1 mt-3 opacity-50 cursor-not-allowed w-full">
                                Tidak Dapat Dipinjam
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($books)): ?>
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="w-20 h-20 mx-auto text-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-text-light">Tidak ada buku yang tersedia untuk dipinjam.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>