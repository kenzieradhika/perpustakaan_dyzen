<?php
require_once '../../config/data_base.php';
require_once '../../config/logger.php';

// PENTING: requireAdmin() HARUS dipanggil SEBELUM kode lainnya
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $judul = sanitize($_POST['judul']);
        $pengarang = sanitize($_POST['pengarang']);
        $penerbit = sanitize($_POST['penerbit']);
        $tahun_terbit = (int)$_POST['tahun_terbit'];
        $isbn = sanitize($_POST['isbn']);
        $kategori = sanitize($_POST['kategori']);
        $deskripsi = sanitize($_POST['deskripsi']);
        $stok = (int)$_POST['stok'];
        
        // Validate
        if (empty($judul) || empty($pengarang) || empty($isbn)) {
            $error = 'Judul, pengarang, dan ISBN wajib diisi.';
        } elseif ($stok < 1) {
            $error = 'Stok minimal 1.';
        } else {
            // Check if ISBN exists
            $stmt = $pdo->prepare("SELECT id FROM buku WHERE isbn = ?");
            $stmt->execute([$isbn]);
            if ($stmt->fetch()) {
                $error = 'ISBN sudah terdaftar.';
            } else {
                // Handle cover upload
                $cover_filename = null;
                if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = secureFileUpload($_FILES['cover'], UPLOAD_PATH);
                    if ($upload_result['success']) {
                        $cover_filename = $upload_result['filename'];
                    } else {
                        $error = $upload_result['message'];
                    }
                }
                
                if (empty($error)) {
                    // Insert book
                    $stmt = $pdo->prepare("
                        INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, kategori, deskripsi, cover, stok, stok_tersedia, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
                    ");
                    
                    if ($stmt->execute([$judul, $pengarang, $penerbit, $tahun_terbit, $isbn, $kategori, $deskripsi, $cover_filename, $stok, $stok])) {
                        // LOGGING HARUS DILAKUKAN SETELAH SUKSES (dan di dalam scope yang benar)
                        Logger::activity($_SESSION['user_id'], "Added new book", [
                            'judul' => $judul,
                            'isbn' => $isbn
                        ]);
                        Logger::access("Book added by admin", [
                            'admin_id' => $_SESSION['user_id'], 
                            'book_title' => $judul
                        ]);
                        
                        $success = 'Buku berhasil ditambahkan!';
                        // Clear form
                        $_POST = [];
                    } else {
                        $error = 'Gagal menambahkan buku. Silakan coba lagi.';
                    }
                }
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
    <title>Tambah Buku - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
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
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Tambah Buku Baru</h1>
                <p class="text-text-light">Tambahkan koleksi buku baru ke perpustakaan</p>
            </div>
        </header>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-6">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-text font-semibold mb-2">Judul Buku *</label>
                        <input type="text" name="judul" required 
                               value="<?= e($_POST['judul'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Pengarang *</label>
                        <input type="text" name="pengarang" required 
                               value="<?= e($_POST['pengarang'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Penerbit</label>
                        <input type="text" name="penerbit" 
                               value="<?= e($_POST['penerbit'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Tahun Terbit</label>
                        <select name="tahun_terbit" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <option value="">Pilih Tahun</option>
                            <?php 
                            $currentYear = date('Y');
                            for($year = $currentYear; $year >= 1900; $year--): 
                            ?>
                            <option value="<?= $year ?>" <?= ($_POST['tahun_terbit'] ?? date('Y')) == $year ? 'selected' : '' ?>>
                                <?= $year ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">ISBN *</label>
                        <input type="text" name="isbn" required 
                               value="<?= e($_POST['isbn'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-text font-semibold mb-2">Kategori</label>
                        <select name="kategori" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <option value="">Pilih Kategori</option>
                            <option value="Fiksi" <?= ($_POST['kategori'] ?? '') == 'Fiksi' ? 'selected' : '' ?>>Fiksi</option>
                            <option value="Non-Fiksi" <?= ($_POST['kategori'] ?? '') == 'Non-Fiksi' ? 'selected' : '' ?>>Non-Fiksi</option>
                            <option value="Sains" <?= ($_POST['kategori'] ?? '') == 'Sains' ? 'selected' : '' ?>>Sains</option>
                            <option value="Teknologi" <?= ($_POST['kategori'] ?? '') == 'Teknologi' ? 'selected' : '' ?>>Teknologi</option>
                            <option value="Sejarah" <?= ($_POST['kategori'] ?? '') == 'Sejarah' ? 'selected' : '' ?>>Sejarah</option>
                            <option value="Sastra" <?= ($_POST['kategori'] ?? '') == 'Sastra' ? 'selected' : '' ?>>Sastra</option>
                            <option value="Agama" <?= ($_POST['kategori'] ?? '') == 'Agama' ? 'selected' : '' ?>>Agama</option>
                            <option value="Referensi" <?= ($_POST['kategori'] ?? '') == 'Referensi' ? 'selected' : '' ?>>Referensi</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Stok *</label>
                        <input type="number" name="stok" required min="1"
                               value="<?= e($_POST['stok'] ?? 1) ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Cover Buku</label>
                        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" id="coverInput"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <p class="text-text-light text-xs mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB</p>
                        <div id="previewContainer" class="mt-2 hidden">
                            <img id="imagePreview" class="preview-image">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Deskripsi</label>
                        <textarea name="deskripsi" rows="4" 
                                  class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary"><?= e($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="md:col-span-2 flex justify-end space-x-3 pt-4">
                    <a href="list_buku.php" class="btn-outline">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Buku</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Image preview
        const coverInput = document.getElementById('coverInput');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        
        coverInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    </script>
</body>
</html>