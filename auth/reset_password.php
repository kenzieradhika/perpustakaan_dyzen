<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/data_base.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';

// Verify token
if (empty($token)) {
    header('Location: login.php?error=invalid_token');
    exit();
}

// Check token in database
$stmt = $pdo->prepare("
    SELECT id, nama, email, token_reset 
    FROM users 
    WHERE token_reset = ? 
    AND token_reset IS NOT NULL
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php?error=invalid_token');
    exit();
}

// Check if token is expired (1 hour)
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE id = ? 
    AND token_reset = ? 
    AND updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$stmt->execute([$user['id'], $token]);
$valid_token = $stmt->fetch();

if (!$valid_token) {
    // Clear expired token
    $stmt = $pdo->prepare("UPDATE users SET token_reset = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);
    header('Location: login.php?error=token_expired');
    exit();
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Password wajib diisi';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password harus mengandung huruf besar';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password harus mengandung huruf kecil';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password harus mengandung angka';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok';
    } else {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Update password and clear token
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, token_reset = NULL, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$hashed_password, $user['id']]);
        
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PERPUSTAKAAN DYZEN</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #09637E, #088395);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        
        .reset-card {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 2rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .icon-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            text-align: center;
            color: #1A3C47;
            margin-bottom: 0.5rem;
        }
        
        .user-name {
            text-align: center;
            color: #09637E;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .subtitle {
            text-align: center;
            color: #6B9EA8;
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group label {
            display: block;
            font-weight: 600;
            color: #1A3C47;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #E2E8F0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #09637E;
            box-shadow: 0 0 0 3px rgba(9, 99, 126, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #09637E, #088395);
            color: white;
            padding: 0.875rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-login {
            width: 100%;
            background: #F1F5F9;
            color: #1E293B;
            padding: 0.875rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            display: inline-block;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-login:hover {
            background: #E2E8F0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .alert-success {
            background-color: #ECFDF5;
            border: 1px solid #10B981;
            color: #065F46;
        }
        
        .alert-error {
            background-color: #FEF2F2;
            border: 1px solid #EF4444;
            color: #991B1B;
        }
        
        .flex {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .font-semibold {
            font-weight: 600;
        }
        
        .text-sm {
            font-size: 0.875rem;
        }
        
        .mt-1 {
            margin-top: 0.25rem;
        }
        
        .mt-4 {
            margin-top: 1rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .password-strength {
            height: 4px;
            margin-top: 0.5rem;
            border-radius: 2px;
            transition: all 0.3s;
            width: 0%;
        }
        
        .strength-weak { width: 33%; background: #EF4444; border-radius: 2px; }
        .strength-medium { width: 66%; background: #F59E0B; border-radius: 2px; }
        .strength-strong { width: 100%; background: #10B981; border-radius: 2px; }
        
        .info-text {
            font-size: 0.7rem;
            color: #6B9EA8;
            margin-top: 0.25rem;
        }
        
        .password-match {
            font-size: 0.7rem;
            margin-top: 0.25rem;
        }
        
        .match-success {
            color: #10B981;
        }
        
        .match-error {
            color: #EF4444;
        }
        
        hr {
            margin: 1rem 0;
            border: none;
            border-top: 1px solid #E2E8F0;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="icon-wrapper">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 15V17M6 21H18C19.1046 21 20 20.1046 20 19V5C20 3.89543 19.1046 3 18 3H6C4.89543 3 4 3.89543 4 5V19C4 20.1046 4.89543 21 6 21ZM16 7H8V10H16V7Z" stroke="#09637E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 11V16M9.5 13.5H14.5" stroke="#088395" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        
        <h1>Reset Password</h1>
        <p class="user-name">Halo, <?= htmlspecialchars($user['nama']) ?>!</p>
        <p class="subtitle">Buat password baru untuk akun Anda</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <div class="flex">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#065F46"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Password berhasil direset!</p>
                        <p class="text-sm">Silakan login dengan password baru Anda.</p>
                    </div>
                </div>
            </div>
            
            <a href="login.php" class="btn-login">
                Login Sekarang →
            </a>
            
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <div class="flex">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#991B1B"/>
                    </svg>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            
            <form method="POST">
                <div class="input-group">
                    <label>Password Baru</label>
                    <input type="password" name="password" id="password" required placeholder="Minimal 8 karakter">
                    <div id="passwordStrength" class="password-strength"></div>
                    <p class="info-text">Password harus mengandung huruf besar, huruf kecil, dan angka</p>
                </div>
                
                <div class="input-group">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Ulangi password baru">
                    <div id="passwordMatch" class="password-match"></div>
                </div>
                
                <button type="submit" class="btn-submit">
                    Reset Password
                </button>
            </form>
            
            <div class="text-center mt-4">
                <a href="login.php" style="color: #088395; text-decoration: none; font-size: 0.875rem;">← Kembali ke Login</a>
            </div>
            
        <?php else: ?>
            <form method="POST">
                <div class="input-group">
                    <label>Password Baru</label>
                    <input type="password" name="password" id="password" required placeholder="Minimal 8 karakter">
                    <div id="passwordStrength" class="password-strength"></div>
                    <p class="info-text">Password harus mengandung huruf besar, huruf kecil, dan angka</p>
                </div>
                
                <div class="input-group">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Ulangi password baru">
                    <div id="passwordMatch" class="password-match"></div>
                </div>
                
                <button type="submit" class="btn-submit">
                    Reset Password
                </button>
            </form>
            
            <div class="text-center mt-4">
                <a href="login.php" style="color: #088395; text-decoration: none; font-size: 0.875rem;">← Kembali ke Login</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthDiv = document.getElementById('passwordStrength');
        const matchDiv = document.getElementById('passwordMatch');
        
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
                    matchDiv.innerHTML = '✓ Password cocok';
                    matchDiv.className = 'password-match match-success';
                } else {
                    matchDiv.innerHTML = '✗ Password tidak cocok';
                    matchDiv.className = 'password-match match-error';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        }
        
        password.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        confirmPassword.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>