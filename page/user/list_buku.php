<?php
require_once '../../config/data_base.php';
requireLogin();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? sanitize($_GET['kategori']) : '';

$query = "SELECT * FROM buku WHERE status = 'aktif'";
$params = [];

if ($search) {
    $query .= " AND (judul LIKE ? OR pengarang LIKE ? OR isbn LIKE ?)";
    $params[] = "%$search%";
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

// Get categories for filter
$categories = $pdo->query("
    SELECT DISTINCT kategori, COUNT(*) as total 
    FROM buku 
    WHERE kategori IS NOT NULL AND status = 'aktif'
    GROUP BY kategori
    ORDER BY kategori
")->fetchAll();

// Get user's active loans count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM peminjaman 
    WHERE user_id = ? AND status = 'dipinjam'
");
$stmt->execute([$_SESSION['user_id']]);
$activeLoans = $stmt->fetch()['total'];

// Check if user has unpaid fines
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM denda 
    WHERE user_id = ? AND status_bayar = 'belum'
");
$stmt->execute([$_SESSION['user_id']]);
$hasFine = $stmt->fetch()['total'] > 0;

$canBorrow = !$hasFine && $activeLoans < MAX_BOOKS_PER_USER;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.css" rel="stylesheet">
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
            transition: all 0.3s;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Katalog Buku</h1>
                <p class="text-text-light">Jelajahi koleksi buku yang tersedia</p>
            </div>
        </header>
        
        <!-- Warning Messages -->
        <?php if ($hasFine): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Anda memiliki denda yang belum dibayar. Selesaikan denda terlebih dahulu untuk dapat meminjam buku.</span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($activeLoans >= MAX_BOOKS_PER_USER): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Anda已达到 batas maksimal peminjaman (<?= MAX_BOOKS_PER_USER ?> buku). Kembalikan beberapa buku terlebih dahulu.</span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Search & Filter -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Cari judul, pengarang, atau ISBN..." 
                           value="<?= e($search) ?>"
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                </div>
                <div class="md:w-64">
                    <select name="kategori" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['kategori']) ?>" <?= $kategori == $cat['kategori'] ? 'selected' : '' ?>>
                            <?= e($cat['kategori']) ?> (<?= $cat['total'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary px-6">Cari</button>
                    <?php if ($search || $kategori): ?>
                        <a href="list_buku.php" class="btn-outline ml-2">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Books Grid -->
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
                        <h3 class="font-bold text-lg mb-1 line-clamp-2"><?= e($book['judul']) ?></h3>
                        <p class="text-text-light text-sm"><?= e($book['pengarang']) ?></p>
                        <p class="text-text-light text-xs mt-1"><?= e($book['penerbit']) ?> | <?= $book['tahun_terbit'] ?></p>
                        
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="badge badge-info text-xs"><?= e($book['kategori']) ?></span>
                            <?php if ($book['stok_tersedia'] > 0): ?>
                                <span class="badge badge-success text-xs">Tersedia</span>
                            <?php else: ?>
                                <span class="badge badge-danger text-xs">Habis</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-success font-bold">Stok: <?= $book['stok_tersedia'] ?></span>
                            <?php if ($canBorrow && $book['stok_tersedia'] > 0): ?>
                                <a href="pinjam_buku.php?id=<?= $book['id'] ?>" 
                                   class="btn-primary text-sm py-1 px-3">
                                    Pinjam
                                </a>
                            <?php else: ?>
                                <button disabled class="btn-outline text-sm py-1 px-3 opacity-50 cursor-not-allowed">
                                    Tidak Tersedia
                                </button>
                            <?php endif; ?>
                        </div>
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
                <p class="text-text-light">Tidak ada buku yang ditemukan.</p>
                <p class="text-text-light text-sm mt-2">Coba kata kunci pencarian yang lain.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>