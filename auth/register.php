<?php
require_once '../config/data_base.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . 'page/admin/index_admin.php');
    } else {
        header('Location: ' . BASE_URL . 'page/user/index_user.php');
    }
    exit();
}

$error = '';
$success = '';
$nisn_error = '';
$email_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
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
                $email_error = 'Email sudah terdaftar.';
                $error = 'Email sudah terdaftar.';
            }
            
            // Check if NISN exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE nisn = ?");
            $stmt->execute([$nisn]);
            if ($stmt->fetch()) {
                $nisn_error = 'NISN sudah terdaftar.';
                $error = 'NISN sudah terdaftar.';
            }
            
            if (empty($error)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert user
                $stmt = $pdo->prepare("
                    INSERT INTO users (nama, email, password, role, nisn, kelas, status) 
                    VALUES (?, ?, ?, 'user', ?, ?, 'aktif')
                ");
                
                if ($stmt->execute([$nama, $email, $hashed_password, $nisn, $kelas])) {
                    $success = 'Pendaftaran berhasil! Silakan login.';
                    
                    // Clear form
                    $_POST = [];
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=login.php");
                } else {
                    $error = 'Terjadi kesalahan. Silakan coba lagi.';
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
    <title>Daftar - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            min-height: 100vh;
        }
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .register-card {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .password-strength {
            height: 4px;
            margin-top: 0.5rem;
            border-radius: 2px;
            transition: all 0.3s;
        }
        .strength-weak { width: 33%; background: var(--color-danger); }
        .strength-medium { width: 66%; background: var(--color-warning); }
        .strength-strong { width: 100%; background: var(--color-success); }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <svg class="w-16 h-16" viewBox="0 0 24 24" fill="none">
                        <path d="M4 6H20V18H4V6Z" stroke="var(--color-primary)" stroke-width="2"/>
                        <path d="M8 4V8M16 4V8" stroke="var(--color-secondary)" stroke-width="2"/>
                        <path d="M12 11V16M9.5 13.5H14.5" stroke="var(--color-accent)" stroke-width="2"/>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-primary font-playfair">Daftar Akun</h2>
                <p class="text-text-light mt-2">Bergabung menjadi member PERPUSTAKAAN DYZEN</p>
            </div>
            
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
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="mb-4">
                    <label class="block text-text font-semibold mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama" required 
                           value="<?= e($_POST['nama'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary transition">
                </div>
                
                <div class="mb-4">
                    <label class="block text-text font-semibold mb-2">NISN *</label>
                    <input type="text" name="nisn" required 
                           value="<?= e($_POST['nisn'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary transition <?= $nisn_error ? 'border-danger' : '' ?>">
                    <?php if ($nisn_error): ?>
                        <p class="text-danger text-sm mt-1"><?= e($nisn_error) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4">
                    <label class="block text-text font-semibold mb-2">Kelas *</label>
                    <select name="kelas" required class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary transition">
                        <option value="">Pilih Kelas</option>
                        <option value="X RPL 1" <?= ($_POST['kelas'] ?? '') == 'X RPL 1' ? 'selected' : '' ?>>X RPL 1</option>
                        <option value="X RPL 2" <?= ($_POST['kelas'] ?? '') == 'X RPL 2' ? 'selected' : '' ?>>X RPL 2</option>
                        <option value="XI RPL 1" <?= ($_POST['kelas'] ?? '') == 'XI RPL 1' ? 'selected' : '' ?>>XI RPL 1</option>
                        <option value="XI RPL 2" <?= ($_POST['kelas'] ?? '') == 'XI RPL 2' ? 'selected' : '' ?>>XI RPL 2</option>
                        <option value="XII RPL 1" <?= ($_POST['kelas'] ?? '') == 'XII RPL 1' ? 'selected' : '' ?>>XII RPL 1</option>
                        <option value="XII RPL 2" <?= ($_POST['kelas'] ?? '') == 'XII RPL 2' ? 'selected' : '' ?>>XII RPL 2</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-text font-semibold mb-2">Email *</label>
                    <input type="email" name="email" required 
                           value="<?= e($_POST['email'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary transition <?= $email_error ? 'border-danger' : '' ?>">
                    <?php if ($email_error): ?>
                        <p class="text-danger text-sm mt-1"><?= e($email_error) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4">
                    <label class="block text-text font-semibold mb-2">Password *</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required 
                               class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary transition">
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
                
                <div class="mb-6">
                    <label class="block text-text font-semibold mb-2">Konfirmasi Password *</label>
                    <input type="password" name="confirm_password" id="confirmPassword" required 
                           class="w-full px-4 py-2 border border-accent rounded-lg focus:outline-none focus:border-primary transition">
                    <p id="passwordMatch" class="text-xs mt-1"></p>
                </div>
                
                <button type="submit" class="w-full btn-primary py-3">
                    Daftar Sekarang
                </button>
                
                <p class="text-center mt-6 text-text-light">
                    Sudah punya akun? <a href="login.php" class="text-secondary hover:text-primary">Masuk di sini</a>
                </p>
            </form>
        </div>
    </div>
    
    <script>
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
                strengthDiv.classList.add('strength-weak');
            } else if (strength === 3) {
                strengthDiv.classList.add('strength-medium');
            } else {
                strengthDiv.classList.add('strength-strong');
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
        toggleBtn.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
        });
    </script>
</body>
</html>