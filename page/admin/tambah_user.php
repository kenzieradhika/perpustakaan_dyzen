<?php
require_once '../../config/data_base.php';
requireAdmin();

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
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validations
        if (empty($nama) || empty($nisn) || empty($kelas) || empty($email) || empty($password)) {
            $error = 'Semua field wajib diisi.';
        } elseif (!validateEmail($email)) {
            $error = 'Format email tidak valid.';
        } elseif (!validatePasswordStrength($password)) {
            $error = 'Password minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.';
        } elseif ($password !== $confirm_password) {
            $error = 'Password dan konfirmasi password tidak cocok.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar.';
            }
            
            // Check if NISN exists
            if (empty($error)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE nisn = ?");
                $stmt->execute([$nisn]);
                if ($stmt->fetch()) {
                    $error = 'NISN sudah terdaftar.';
                }
            }
            
            if (empty($error)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Handle photo upload
                $foto_filename = null;
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = secureFileUpload($_FILES['foto'], UPLOAD_PATH);
                    if ($upload_result['success']) {
                        $foto_filename = $upload_result['filename'];
                    } else {
                        $error = $upload_result['message'];
                    }
                }
                
                if (empty($error)) {
                    // Insert user
                    $stmt = $pdo->prepare("
                        INSERT INTO users (nama, email, password, role, nisn, kelas, foto, status) 
                        VALUES (?, ?, ?, 'user', ?, ?, ?, 'aktif')
                    ");
                    
                    if ($stmt->execute([$nama, $email, $hashed_password, $nisn, $kelas, $foto_filename])) {
                        $success = 'User berhasil ditambahkan! Password: ' . $password;
                        // Clear form
                        $_POST = [];
                    } else {
                        $error = 'Gagal menambahkan user. Silakan coba lagi.';
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
    <title>Tambah User - PERPUSTAKAAN DYZEN</title>
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
                <h1 class="text-2xl font-playfair font-bold text-primary">Tambah User Baru</h1>
                <p class="text-text-light">Tambahkan member baru ke perpustakaan</p>
            </div>
        </header>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= e($success) ?>
                    <div class="mt-2 text-sm">
                        <strong>Informasi Login:</strong><br>
                        Email: <?= e($_POST['email'] ?? '') ?><br>
                        Password: <?= e($_POST['password'] ?? '') ?>
                    </div>
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
                        <label class="block text-text font-semibold mb-2">Nama Lengkap *</label>
                        <input type="text" name="nama" required 
                               value="<?= e($_POST['nama'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">NISN *</label>
                        <input type="text" name="nisn" required 
                               value="<?= e($_POST['nisn'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Kelas *</label>
                        <select name="kelas" required class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <option value="">Pilih Kelas</option>
                            <option value="X RPL 1" <?= ($_POST['kelas'] ?? '') == 'X RPL 1' ? 'selected' : '' ?>>X RPL 1</option>
                            <option value="X RPL 2" <?= ($_POST['kelas'] ?? '') == 'X RPL 2' ? 'selected' : '' ?>>X RPL 2</option>
                            <option value="XI RPL 1" <?= ($_POST['kelas'] ?? '') == 'XI RPL 1' ? 'selected' : '' ?>>XI RPL 1</option>
                            <option value="XI RPL 2" <?= ($_POST['kelas'] ?? '') == 'XI RPL 2' ? 'selected' : '' ?>>XI RPL 2</option>
                            <option value="XII RPL 1" <?= ($_POST['kelas'] ?? '') == 'XII RPL 1' ? 'selected' : '' ?>>XII RPL 1</option>
                            <option value="XII RPL 2" <?= ($_POST['kelas'] ?? '') == 'XII RPL 2' ? 'selected' : '' ?>>XII RPL 2</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Email *</label>
                        <input type="email" name="email" required 
                               value="<?= e($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-text font-semibold mb-2">Foto Profil</label>
                        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" id="fotoInput"
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <p class="text-text-light text-xs mt-1">Format: JPG, PNG, WEBP. Maksimal 2MB</p>
                        <div id="previewContainer" class="mt-2 hidden">
                            <img id="imagePreview" class="preview-image">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Password *</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required 
                                   class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                            <button type="button" id="togglePassword" class="absolute right-3 top-2 text-text-light">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <div id="passwordStrength" class="password-strength"></div>
                        <p class="text-text-light text-xs mt-1">Minimal 8 karakter, huruf besar, huruf kecil, dan angka</p>
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Konfirmasi Password *</label>
                        <input type="password" name="confirm_password" id="confirmPassword" required 
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary">
                        <p id="passwordMatch" class="text-xs mt-1"></p>
                    </div>
                </div>
                
                <div class="md:col-span-2 flex justify-end space-x-3 pt-4">
                    <a href="list_user.php" class="btn-outline">Batal</a>
                    <button type="submit" class="btn-primary">Tambah User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const fotoInput = document.getElementById('fotoInput');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        
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
        
        // Password strength checker
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const strengthDiv = document.getElementById('passwordStrength');
        const matchP = document.getElementById('passwordMatch');
        
        function checkPasswordStrength(pwd) {
            let strength = 0;
            if (pwd.length >= 8) strength++;
            if (pwd.match(/[A-Z]/)) strength++;
            if (pwd.match(/[a-z]/)) strength++;
            if (pwd.match(/[0-9]/)) strength++;
            
            strengthDiv.className = 'password-strength';
            if (pwd.length === 0) {
                strengthDiv.style.width = '0';
                return;
            }
            
            if (strength <= 2) {
                strengthDiv.style.width = '33%';
                strengthDiv.style.background = 'var(--color-danger)';
            } else if (strength === 3) {
                strengthDiv.style.width = '66%';
                strengthDiv.style.background = 'var(--color-warning)';
            } else {
                strengthDiv.style.width = '100%';
                strengthDiv.style.background = 'var(--color-success)';
            }
        }
        
        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0) {
                if (password.value === confirmPassword.value) {
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
        
        password.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        // Toggle password visibility
        const toggleBtn = document.getElementById('togglePassword');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
            });
        }
    </script>
</body>
</html>