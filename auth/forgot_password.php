<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/data_base.php';
require_once '../config/mail_config.php';

$error = '';
$success = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Email wajib diisi';
    } else {
        $stmt = $pdo->prepare("SELECT id, nama, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE users SET token_reset = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
            
            $reset_link = BASE_URL . "auth/reset_password.php?token=" . $token;
            
            $email_sent = sendResetEmail($email, $user['nama'], $reset_link);
            
            if ($email_sent) {
                $success = true;
            } else {
                $error = 'Gagal kirim email. Silakan coba lagi.';
            }
        } else {
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - PERPUSTAKAAN DYZEN</title>
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
        
        .forgot-card {
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
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #088395;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #09637E;
            text-decoration: underline;
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
        
        .alert-success .flex,
        .alert-error .flex {
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
        
        .link-box {
            background: #ECFDF5;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .link-box p {
            font-size: 0.75rem;
            color: #065F46;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .link-box a {
            color: #065F46;
            word-break: break-all;
            font-size: 0.75rem;
            font-family: monospace;
        }
        
        hr {
            margin: 1rem 0;
            border: none;
            border-top: 1px solid #E2E8F0;
        }
    </style>
</head>
<body>
    <div class="forgot-card">
        <div class="icon-wrapper">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 15V17M6 21H18C19.1046 21 20 20.1046 20 19V5C20 3.89543 19.1046 3 18 3H6C4.89543 3 4 3.89543 4 5V19C4 20.1046 4.89543 21 6 21ZM16 7H8V10H16V7Z" stroke="#09637E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 14H16" stroke="#088395" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        
        <h1>Lupa Password?</h1>
        <p class="subtitle">Masukkan email Anda untuk mereset password</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <div class="flex">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#065F46"/>
                    </svg>
                    <div>
                        <p class="font-semibold">Link reset password telah dikirim!</p>
                        <p class="text-sm">Silakan cek email Anda untuk melanjutkan.</p>
                    </div>
                </div>
            </div>
            
            <?php if ($reset_link): ?>
            <div class="link-box">
                <p>🔗 Link Reset Password (Demo Mode)</p>
                <a href="<?= $reset_link ?>" target="_blank"><?= $reset_link ?></a>
            </div>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="login.php">← Kembali ke Login</a>
            </div>
            
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <div class="flex">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#991B1B"/>
                    </svg>
                    <p><?= $error ?></p>
                </div>
            </div>
            <div class="back-link">
                <a href="login.php">← Kembali ke Login</a>
            </div>
            
        <?php else: ?>
            <form method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="contoh: email@domain.com" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    Kirim Link Reset Password
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php">← Kembali ke Login</a>
            </div>
            
            <hr>
            
            <p class="subtitle" style="font-size: 0.7rem; margin-top: 1rem;">
                Link reset password akan dikirim ke email Anda dan berlaku selama 1 jam.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>