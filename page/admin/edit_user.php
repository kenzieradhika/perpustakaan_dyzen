<?php
require_once '../../config/data_base.php';
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: list_user.php');
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan.";
    header('Location: list_user.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $nama = sanitize($_POST['nama']);
        $nisn = sanitize($_POST['nisn']);
        $kelas = sanitize($_POST['kelas']);
        $email = sanitize($_POST['email']);
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validations
        if (empty($nama) || empty($nisn) || empty($kelas) || empty($email)) {
            $error = 'Semua field wajib diisi.';
        } elseif (!validateEmail($email)) {
            $error = 'Format email tidak valid.';
        } elseif (!empty($new_password)) {
            if (!validatePasswordStrength($new_password)) {
                $error = 'Password minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Password dan konfirmasi password tidak cocok.';
            }
        }
        
        // Check if email exists for other users
        if (empty($error)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar pada user lain.';
            }
        }
        
        // Check if NISN exists for other users
        if (empty($error)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE nisn = ? AND id != ?");
            $stmt->execute([$nisn, $id]);
            if ($stmt->fetch()) {
                $error = 'NISN sudah terdaftar pada user lain.';
            }
        }
        
        if (empty($error)) {
            // Handle photo upload
            $foto_filename = $user['foto'];
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                if ($foto_filename && file_exists(UPLOAD_PATH . $foto_filename)) {
                    unlink(UPLOAD_PATH . $foto_filename);
                }
                
                $upload_result = secureFileUpload($_FILES['foto'], UPLOAD_PATH);
                if ($upload_result['success']) {
                    $foto_filename = $upload_result['filename'];
                } else {
                    $error = $upload_result['message'];
                }
            }
            
            // Remove photo if checkbox checked
            if (isset($_POST['remove_foto']) && $_POST['remove_foto'] == '1') {
                if ($foto_filename && file_exists(UPLOAD_PATH . $foto_filename)) {
                    unlink(UPLOAD_PATH . $foto_filename);
                }
                $foto_filename = null;
            }
        }
        
        if (empty($error)) {
            // Update query
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nama = ?, nisn = ?, kelas = ?, email = ?, 
                        password = ?, foto = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([$nama, $nisn, $kelas, $email, $hashed_password, $foto_filename, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nama = ?, nisn = ?, kelas = ?, email = ?, 
                        foto = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([$nama, $nisn, $kelas, $email, $foto_filename, $id]);
            }
            
            if ($result) {
                $success = 'Data user berhasil diperbarui!';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Gagal memperbarui data. Silakan coba lagi.';
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
    <title>Edit User - PERPUSTAKAAN DYZEN</title>
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
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Edit User</h1>
                <p class="text-text-light">Perbarui data member perpustakaan</p>
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
            
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-text font-semibold mb-2">Nama Lengkap *</label>
                            <input type="text" name="nama" required 
                                   value="<?= e($user['nama']) ?>"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">NISN *</label>
                            <input type="text" name="nisn" required 
                                   value="<?= e($user['nisn']) ?>"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Kelas *</label>
                            <select name="kelas" required class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                                <option value="">Pilih Kelas</option>
                                <option value="X RPL 1" <?= $user['kelas'] == 'X RPL 1' ? 'selected' : '' ?>>X RPL 1</option>
                                <option value="X RPL 2" <?= $user['kelas'] == 'X RPL 2' ? 'selected' : '' ?>>X RPL 2</option>
                                <option value="XI RPL 1" <?= $user['kelas'] == 'XI RPL 1' ? 'selected' : '' ?>>XI RPL 1</option>
                                <option value="XI RPL 2" <?= $user['kelas'] == 'XI RPL 2' ? 'selected' : '' ?>>XI RPL 2</option>
                                <option value="XII RPL 1" <?= $user['kelas'] == 'XII RPL 1' ? 'selected' : '' ?>>XII RPL 1</option>
                                <option value="XII RPL 2" <?= $user['kelas'] == 'XII RPL 2' ? 'selected' : '' ?>>XII RPL 2</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Email *</label>
                            <input type="email" name="email" required 
                                   value="<?= e($user['email']) ?>"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-text font-semibold mb-2">Foto Profil</label>
                            <div class="flex items-center space-x-4">
                                <?php if ($user['foto']): ?>
                                    <div class="current-photo">
                                        <img src="../../uploads/covers/<?= e($user['foto']) ?>" class="preview-image">
                                        <label class="flex items-center mt-2">
                                            <input type="checkbox" name="remove_foto" value="1" class="mr-2">
                                            <span class="text-sm text-text-light">Hapus foto</span>
                                        </label>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" id="fotoInput"
                                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                                    <p class="text-text-light text-xs mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB</p>
                                </div>
                            </div>
                            <div id="previewContainer" class="mt-2 hidden">
                                <img id="imagePreview" class="preview-image">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Password Baru (Kosongkan jika tidak diubah)</label>
                            <input type="password" name="new_password" id="new_password"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <p class="text-text-light text-xs mt-1">Minimal 8 karakter, huruf besar, huruf kecil, dan angka</p>
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <p id="passwordMatch" class="text-xs mt-1"></p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <a href="list_user.php" class="btn-outline">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const fotoInput = document.getElementById('fotoInput');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchP = document.getElementById('passwordMatch');
        
        fotoInput.addEventListener('change', function(e) {
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
        
        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0 || newPassword.value.length > 0) {
                if (newPassword.value === confirmPassword.value) {
                    matchP.innerHTML = '✓ Password cocok';
                    matchP.style.color = 'var(--color-success)';
                } else {
                    matchP.innerHTML = '✗ Password tidak cocok';
                    matchP.style.color = 'var(--color-danger)';
                }
            } else {
                matchP.innerHTML = '';
            }
        }
        
        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>