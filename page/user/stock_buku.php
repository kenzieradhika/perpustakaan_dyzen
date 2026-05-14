<?php
require_once '../../config/data_base.php';
requireLogin();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? sanitize($_GET['kategori']) : '';
$min_stock = isset($_GET['min_stock']) ? (int)$_GET['min_stock'] : 0;

$query = "SELECT * FROM buku WHERE status = 'aktif'";
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

if ($min_stock > 0) {
    $query .= " AND stok_tersedia <= ?";
    $params[] = $min_stock;
}

$query .= " ORDER BY stok_tersedia ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories
$categories = $pdo->query("
    SELECT DISTINCT kategori 
    FROM buku 
    WHERE kategori IS NOT NULL AND status = 'aktif'
")->fetchAll();

// Stock summary
$total_books = $pdo->query("SELECT COUNT(*) as total FROM buku WHERE status = 'aktif'")->fetch()['total'];
$total_stock = $pdo->query("SELECT SUM(stok_tersedia) as total FROM buku WHERE status = 'aktif'")->fetch()['total'];
$low_stock = $pdo->query("SELECT COUNT(*) as total FROM buku WHERE status = 'aktif' AND stok_tersedia <= 2")->fetch()['total'];
$out_of_stock = $pdo->query("SELECT COUNT(*) as total FROM buku WHERE status = 'aktif' AND stok_tersedia = 0")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Buku - PERPUSTAKAAN DYZEN</title>
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
        .progress-bar {
            height: 8px;
            background: var(--color-light);
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Stok Buku</h1>
                <p class="text-text-light">Informasi ketersediaan stok buku di perpustakaan</p>
            </div>
        </header>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-primary"><?= $total_books ?></p>
                <p class="text-text-light text-sm">Total Judul Buku</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-success"><?= $total_stock ?></p>
                <p class="text-text-light text-sm">Total Stok Tersedia</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-warning"><?= $low_stock ?></p>
                <p class="text-text-light text-sm">Stok Menipis (≤2)</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-danger"><?= $out_of_stock ?></p>
                <p class="text-text-light text-sm">Stok Habis</p>
            </div>
        </div>
        
        <!-- Search & Filter -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
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
                    <select name="min_stock" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <option value="0">Semua Stok</option>
                        <option value="0" <?= $min_stock == 0 ? 'selected' : '' ?>>Semua</option>
                        <option value="2" <?= $min_stock == 2 ? 'selected' : '' ?>>Stok Menipis (≤2)</option>
                        <option value="0" <?= $min_stock == 0 && isset($_GET['min_stock']) && $_GET['min_stock'] == '0' ? 'selected' : '' ?>>Stok Habis (0)</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full">Filter</button>
                    <?php if ($search || $kategori || $min_stock): ?>
                        <a href="stock_buku.php" class="btn-outline w-full mt-2 text-center block">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Stock Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="stockTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr>
                            <th class="px-6 py-3 text-left">Judul Buku</th>
                            <th class="px-6 py-3 text-left">Pengarang</th>
                            <th class="px-6 py-3 text-left">Kategori</th>
                            <th class="px-6 py-3 text-center">Stok</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <?php
                            $stock_percentage = ($book['stok_tersedia'] / max(1, $book['stok'])) * 100;
                            $stock_status = '';
                            $status_color = '';
                            
                            if ($book['stok_tersedia'] == 0) {
                                $stock_status = 'Habis';
                                $status_color = 'text-danger';
                            } elseif ($book['stok_tersedia'] <= 2) {
                                $stock_status = 'Menipis';
                                $status_color = 'text-warning';
                            } else {
                                $stock_status = 'Tersedia';
                                $status_color = 'text-success';
                            }
                            ?>
                            <tr class="border-b hover:bg-light">
                                <td class="px-6 py-3">
                                    <div class="font-semibold"><?= e($book['judul']) ?></div>
                                    <div class="text-xs text-text-light">ISBN: <?= e($book['isbn']) ?></div>
                                </td>
                                <td class="px-6 py-3"><?= e($book['pengarang']) ?></td>
                                <td class="px-6 py-3">
                                    <span class="badge badge-info"><?= e($book['kategori']) ?></span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="text-center">
                                        <span class="font-bold <?= $status_color ?>"><?= $book['stok_tersedia'] ?></span>
                                        <span class="text-text-light text-sm"> / <?= $book['stok'] ?></span>
                                        <div class="progress-bar mt-1">
                                            <div class="progress-fill bg-gradient-to-r from-primary to-secondary" 
                                                 style="width: <?= $stock_percentage ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span class="badge 
                                        <?= $book['stok_tersedia'] == 0 ? 'badge-danger' : ($book['stok_tersedia'] <= 2 ? 'badge-warning' : 'badge-success') ?>">
                                        <?= $stock_status ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if (empty($books)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-text-light">
                                <svg class="w-20 h-20 mx-auto text-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p>Tidak ada buku yang ditemukan.</p>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="bg-light rounded-lg p-4 mt-6">
            <div class="flex flex-wrap gap-6 text-sm">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-success rounded-full mr-2"></div>
                    <span>Tersedia (Stok > 2)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-warning rounded-full mr-2"></div>
                    <span>Menipis (Stok 1-2)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-danger rounded-full mr-2"></div>
                    <span>Habis (Stok 0)</span>
                </div>
                <div class="flex items-center ml-auto">
                    <svg class="w-4 h-4 text-primary mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round"stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-text-light">Progress bar menunjukkan persentase stok tersedia</span>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#stockTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                pageLength: 10,
                order: [[3, 'asc']],
                responsive: true
            });
        });
    </script>
</body>
</html>