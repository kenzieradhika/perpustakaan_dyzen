<?php
require_once '../../config/data_base.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug logging
    error_log("POST data received: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page.';
        error_log("CSRF token invalid");
    } else {
        $nama = sanitize($_POST['nama']);
        $nisn = sanitize($_POST['nisn']);
        $kelas = sanitize($_POST['kelas']);
        $email = sanitize($_POST['email']);
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validations
        if (empty($nama) || empty($nisn) || empty($kelas) || empty($email)) {
            $error = 'Semua field wajib diisi.';
        } elseif (!validateEmail($email)) {
            $error = 'Format email tidak valid.';
        }
        
        // Check if email exists for other users (skip if same email)
        if (empty($error) && $email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar pada user lain.';
            }
        }
        
        // Check if NISN exists for other users (skip if same NISN)
        if (empty($error) && $nisn !== $user['nisn']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE nisn = ? AND id != ?");
            $stmt->execute([$nisn, $user_id]);
            if ($stmt->fetch()) {
                $error = 'NISN sudah terdaftar pada user lain.';
            }
        }
        
        // Verify current password if changing password
        if (!empty($new_password) && empty($error)) {
            if (empty($current_password)) {
                $error = 'Password saat ini wajib diisi untuk mengganti password.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Password saat ini salah.';
            } elseif (!validatePasswordStrength($new_password)) {
                $error = 'Password baru minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Konfirmasi password baru tidak cocok.';
            }
        }
        
        // Handle photo upload
        $foto_filename = $user['foto'];
        
        // Cek apakah ada file yang diupload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE && empty($error)) {
            error_log("Processing file upload...");
            
            if ($_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_result = secureFileUpload($_FILES['foto'], UPLOAD_PATH);
                error_log("Upload result: " . print_r($upload_result, true));
                
                if ($upload_result['success']) {
                    // Delete old photo if exists
                    if (!empty($foto_filename) && file_exists(UPLOAD_PATH . $foto_filename)) {
                        unlink(UPLOAD_PATH . $foto_filename);
                    }
                    $foto_filename = $upload_result['filename'];
                } else {
                    $error = $upload_result['message'];
                }
            } else {
                $error = 'Error upload file: ' . $_FILES['foto']['error'];
            }
        }
        
        // Remove photo if checkbox checked
        if (isset($_POST['remove_foto']) && $_POST['remove_foto'] == '1' && empty($error)) {
            if (!empty($foto_filename) && file_exists(UPLOAD_PATH . $foto_filename)) {
                unlink(UPLOAD_PATH . $foto_filename);
            }
            $foto_filename = null;
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
                $result = $stmt->execute([$nama, $nisn, $kelas, $email, $hashed_password, $foto_filename, $user_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nama = ?, nisn = ?, kelas = ?, email = ?, 
                        foto = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([$nama, $nisn, $kelas, $email, $foto_filename, $user_id]);
            }
            
            if ($result) {
                // Update session
                $_SESSION['user_name'] = $nama;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_foto'] = $foto_filename;
                
                $success = 'Profile berhasil diperbarui!';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                // Reset form after success
                $_POST = array();
                
                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = 'Gagal memperbarui profile. Silakan coba lagi.';
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
    <title>Profile User - PERPUSTAKAAN DYZEN</title>
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
        .profile-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--color-primary);
        }
        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #22c55e;
            color: #166534;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_user.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Profile Saya</h1>
                <p class="text-text-light">Kelola informasi akun Anda</p>
            </div>
        </header>
        
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Profile Info Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <?php if (!empty($user['foto']) && file_exists('../../uploads/covers/' . $user['foto'])): ?>
                        <img src="../../uploads/covers/<?= e($user['foto']) ?>" class="profile-photo mx-auto mb-4" alt="Profile Photo">
                    <?php else: ?>
                        <div class="profile-photo mx-auto mb-4 bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                            <span class="text-white text-5xl font-bold"><?= strtoupper(substr($user['nama'], 0, 1)) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="font-bold text-xl"><?= e($user['nama']) ?></h3>
                    <p class="text-text-light"><?= e($user['kelas']) ?></p>
                    <p class="text-text-light text-sm mt-1">NISN: <?= e($user['nisn']) ?></p>
                    
                    <div class="mt-4 p-3 bg-light rounded-lg text-left">
                        <p class="text-sm text-text mb-1">
                            <span class="font-semibold">Email:</span> <?= e($user['email']) ?>
                        </p>
                        <p class="text-sm text-text mb-1">
                            <span class="font-semibold">Status:</span> 
                            <span class="badge <?= $user['status'] == 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                <?= $user['status'] == 'aktif' ? 'Aktif' : 'Banned' ?>
                            </span>
                        </p>
                        <p class="text-sm text-text">
                            <span class="font-semibold">Member sejak:</span> <?= date('d F Y', strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-bold text-lg mb-4">Edit Profile</h3>
                    
                    <?php if ($success): ?>
                        <div class="alert-success">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><?= e($success) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert-error">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><?= e($error) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Nama Lengkap *</label>
                            <input type="text" name="nama" required 
                                   value="<?= e($_POST['nama'] ?? $user['nama']) ?>"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">NISN *</label>
                            <input type="text" name="nisn" required 
                                   value="<?= e($_POST['nisn'] ?? $user['nisn']) ?>"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary"
                                   readonly>
                            <p class="text-text-light text-xs mt-1">NISN tidak dapat diubah. Hubungi admin jika ada kesalahan.</p>
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Kelas *</label>
                            <select name="kelas" required class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                                <option value="">Pilih Kelas</option>
                                <option value="X RPL 1" <?= ($_POST['kelas'] ?? $user['kelas']) == 'X RPL 1' ? 'selected' : '' ?>>X RPL 1</option>
                                <option value="X RPL 2" <?= ($_POST['kelas'] ?? $user['kelas']) == 'X RPL 2' ? 'selected' : '' ?>>X RPL 2</option>
                                <option value="XI RPL 1" <?= ($_POST['kelas'] ?? $user['kelas']) == 'XI RPL 1' ? 'selected' : '' ?>>XI RPL 1</option>
                                <option value="XI RPL 2" <?= ($_POST['kelas'] ?? $user['kelas']) == 'XI RPL 2' ? 'selected' : '' ?>>XI RPL 2</option>
                                <option value="XII RPL 1" <?= ($_POST['kelas'] ?? $user['kelas']) == 'XII RPL 1' ? 'selected' : '' ?>>XII RPL 1</option>
                                <option value="XII RPL 2" <?= ($_POST['kelas'] ?? $user['kelas']) == 'XII RPL 2' ? 'selected' : '' ?>>XII RPL 2</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Email *</label>
                            <input type="email" name="email" required 
                                   value="<?= e($_POST['email'] ?? $user['email']) ?>"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Foto Profil</label>
                            <div class="flex flex-col space-y-3">
                                <?php if (!empty($user['foto'])): ?>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="remove_foto" value="1" class="mr-2">
                                        <span class="text-sm text-text-light">Hapus foto saat ini</span>
                                    </label>
                                <?php endif; ?>
                                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" id="fotoInput"
                                       class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                                <p class="text-text-light text-xs mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB</p>
                            </div>
                            <div id="previewContainer" class="mt-2 hidden">
                                <img id="imagePreview" class="preview-image">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h3 class="font-bold text-lg mb-4">Ganti Password</h3>
                        <p class="text-sm text-text-light mb-2">Kosongkan jika tidak ingin mengganti password</p>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Password Saat Ini</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-text font-semibold mb-2">Password Baru</label>
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
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <a href="index_user.php" class="btn-outline">Batal</a>
                            <button type="submit" class="btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const fotoInput = document.getElementById('fotoInput');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const currentPassword = document.getElementById('current_password');
        const matchP = document.getElementById('passwordMatch');
        
        if (fotoInput) {
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
        }
        
        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0 || newPassword.value.length > 0) {
                if (newPassword.value === confirmPassword.value) {
                    matchP.innerHTML = '✓ Password cocok';
                    matchP.style.color = '#10b981';
                } else {
                    matchP.innerHTML = '✗ Password tidak cocok';
                    matchP.style.color = '#ef4444';
                }
            } else {
                matchP.innerHTML = '';
            }
        }
        
        if (newPassword) {
            newPassword.addEventListener('input', checkPasswordMatch);
        }
        if (confirmPassword) {
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }
    </script>
</body>
</html>