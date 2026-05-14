<?php
require_once '../../config/data_base.php';
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: list_buku.php');
    exit();
}

// Get book data
$stmt = $pdo->prepare("SELECT * FROM buku WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: list_buku.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $status = sanitize($_POST['status']);
        
        if (empty($judul) || empty($pengarang) || empty($isbn)) {
            $error = 'Judul, pengarang, dan ISBN wajib diisi.';
        } elseif ($stok < 0) {
            $error = 'Stok tidak boleh negatif.';
        } else {
            // Check if ISBN exists for other books
            $stmt = $pdo->prepare("SELECT id FROM buku WHERE isbn = ? AND id != ?");
            $stmt->execute([$isbn, $id]);
            if ($stmt->fetch()) {
                $error = 'ISBN sudah terdaftar pada buku lain.';
            } else {
                // Handle cover upload
                $cover_filename = $book['cover'];
                if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    // Delete old cover if exists
                    if ($cover_filename && file_exists(UPLOAD_PATH . $cover_filename)) {
                        unlink(UPLOAD_PATH . $cover_filename);
                    }
                    
                    $upload_result = secureFileUpload($_FILES['cover'], UPLOAD_PATH);
                    if ($upload_result['success']) {
                        $cover_filename = $upload_result['filename'];
                    } else {
                        $error = $upload_result['message'];
                    }
                }
                
                // Remove cover if checkbox checked
                if (isset($_POST['remove_cover']) && $_POST['remove_cover'] == '1') {
                    if ($cover_filename && file_exists(UPLOAD_PATH . $cover_filename)) {
                        unlink(UPLOAD_PATH . $cover_filename);
                    }
                    $cover_filename = null;
                }
                
                if (empty($error)) {
                    // Calculate available stock (cannot exceed total stock)
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as borrowed 
                        FROM peminjaman 
                        WHERE buku_id = ? AND status = 'dipinjam'
                    ");
                    $stmt->execute([$id]);
                    $borrowed = $stmt->fetch()['borrowed'];
                    
                    $stok_tersedia = $stok - $borrowed;
                    if ($stok_tersedia < 0) $stok_tersedia = 0;
                    
                    // Update book
                    $stmt = $pdo->prepare("
                        UPDATE buku 
                        SET judul = ?, pengarang = ?, penerbit = ?, tahun_terbit = ?, 
                            isbn = ?, kategori = ?, deskripsi = ?, cover = ?, 
                            stok = ?, stok_tersedia = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$judul, $pengarang, $penerbit, $tahun_terbit, $isbn, 
                                       $kategori, $deskripsi, $cover_filename, $stok, 
                                       $stok_tersedia, $status, $id])) {
                        $success = 'Buku berhasil diperbarui!';
                        // Refresh book data
                        $stmt = $pdo->prepare("SELECT * FROM buku WHERE id = ?");
                        $stmt->execute([$id]);
                        $book = $stmt->fetch();
                    } else {
                        $error = 'Gagal memperbarui buku. Silakan coba lagi.';
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
    <title>Edit Buku - PERPUSTAKAAN DYZEN</title>
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
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .current-cover {
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Edit Buku</h1>
                <p class="text-text-light">Perbarui informasi buku</p>
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
                               value="<?= e($book['judul']) ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Pengarang *</label>
                        <input type="text" name="pengarang" required 
                               value="<?= e($book['pengarang']) ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Penerbit</label>
                        <input type="text" name="penerbit" 
                               value="<?= e($book['penerbit']) ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Tahun Terbit</label>
                        <input type="number" name="tahun_terbit" min="1900" max="2024"
                               value="<?= e($book['tahun_terbit']) ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">ISBN *</label>
                        <input type="text" name="isbn" required 
                               value="<?= e($book['isbn']) ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-text font-semibold mb-2">Kategori</label>
                        <select name="kategori" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <option value="">Pilih Kategori</option>
                            <option value="Fiksi" <?= $book['kategori'] == 'Fiksi' ? 'selected' : '' ?>>Fiksi</option>
                            <option value="Non-Fiksi" <?= $book['kategori'] == 'Non-Fiksi' ? 'selected' : '' ?>>Non-Fiksi</option>
                            <option value="Sains" <?= $book['kategori'] == 'Sains' ? 'selected' : '' ?>>Sains</option>
                            <option value="Teknologi" <?= $book['kategori'] == 'Teknologi' ? 'selected' : '' ?>>Teknologi</option>
                            <option value="Sejarah" <?= $book['kategori'] == 'Sejarah' ? 'selected' : '' ?>>Sejarah</option>
                            <option value="Sastra" <?= $book['kategori'] == 'Sastra' ? 'selected' : '' ?>>Sastra</option>
                            <option value="Agama" <?= $book['kategori'] == 'Agama' ? 'selected' : '' ?>>Agama</option>
                            <option value="Referensi" <?= $book['kategori'] == 'Referensi' ? 'selected' : '' ?>>Referensi</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Stok *</label>
                        <input type="number" name="stok" required min="0"
                               value="<?= e($book['stok']) ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <p class="text-text-light text-xs mt-1">Stok tersedia saat ini: <?= $book['stok_tersedia'] ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <option value="aktif" <?= $book['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $book['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Cover Buku</label>
                        <?php if ($book['cover']): ?>
                            <div class="current-cover mb-2">
                                <img src="../../uploads/covers/<?= e($book['cover']) ?>" class="preview-image">
                                <label class="flex items-center mt-2">
                                    <input type="checkbox" name="remove_cover" value="1" class="mr-2">
                                    <span class="text-sm text-text-light">Hapus cover saat ini</span>
                                </label>
                            </div>
                        <?php endif; ?>
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
                                  class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary"><?= e($book['deskripsi']) ?></textarea>
                    </div>
                </div>
                
                <div class="md:col-span-2 flex justify-end space-x-3 pt-4">
                    <a href="list_buku.php" class="btn-outline">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
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