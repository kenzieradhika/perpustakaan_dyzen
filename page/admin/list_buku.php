<?php
require_once '../../config/data_base.php';
requireAdmin();

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? sanitize($_GET['kategori']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT * FROM buku WHERE 1=1";
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

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT kategori FROM buku WHERE kategori IS NOT NULL")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Buku - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
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
        .book-cover {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-playfair font-bold text-primary">Data Buku</h1>
                    <p class="text-text-light">Kelola koleksi buku perpustakaan</p>
                </div>
                <a href="tambah_buku.php" class="btn-primary">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Buku
                </a>
            </div>
        </header>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
                <div>
                    <input type="text" name="search" placeholder="Cari judul, pengarang, ISBN..." 
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
                    <select name="status" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?= $status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $status == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full">Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Books Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="booksTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr>
                            <th class="px-6 py-3 text-left">Cover</th>
                            <th class="px-6 py-3 text-left">Judul</th>
                            <th class="px-6 py-3 text-left">Pengarang</th>
                            <th class="px-6 py-3 text-left">Kategori</th>
                            <th class="px-6 py-3 text-center">Stok</th>
                            <th class="px-6 py-3 text-center">Tersedia</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr class="border-b hover:bg-light">
                            <td class="px-6 py-3">
                                <?php if ($book['cover']): ?>
                                    <img src="../../uploads/covers/<?= e($book['cover']) ?>" class="book-cover">
                                <?php else: ?>
                                    <div class="book-cover bg-accent bg-opacity-20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3">
                                <div class="font-semibold"><?= e($book['judul']) ?></div>
                                <div class="text-xs text-text-light">ISBN: <?= e($book['isbn']) ?></div>
                            </td>
                            <td class="px-6 py-3"><?= e($book['pengarang']) ?></td>
                            <td class="px-6 py-3">
                                <span class="badge badge-info"><?= e($book['kategori']) ?></span>
                            </td>
                            <td class="px-6 py-3 text-center"><?= $book['stok'] ?></td>
                            <td class="px-6 py-3 text-center">
                                <span class="font-bold <?= $book['stok_tersedia'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $book['stok_tersedia'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="badge <?= $book['status'] == 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $book['status'] == 'aktif' ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="edit_buku.php?id=<?= $book['id'] ?>" class="text-primary hover:text-secondary">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button onclick="confirmDelete(<?= $book['id'] ?>, '<?= addslashes($book['judul']) ?>')" class="text-danger hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content p-6">
            <h3 class="text-xl font-bold mb-4">Konfirmasi Hapus</h3>
            <p id="deleteMessage" class="mb-6"></p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeModal()" class="btn-outline">Batal</button>
                <a href="#" id="deleteLink" class="btn-primary bg-danger hover:bg-red-700">Hapus</a>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#booksTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                pageLength: 10,
                responsive: true
            });
        });
        
        function confirmDelete(id, judul) {
            const modal = document.getElementById('deleteModal');
            const message = document.getElementById('deleteMessage');
            const deleteLink = document.getElementById('deleteLink');
            
            message.innerHTML = `Apakah Anda yakin ingin menghapus buku "<strong>${judul}</strong>"?`;
            deleteLink.href = `hapus_buku.php?id=${id}`;
            modal.classList.add('active');
        }
        
        function closeModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('active');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>