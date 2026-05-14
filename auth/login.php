<?php
require_once '../config/data_base.php';
require_once '../config/logger.php';

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
$remainingTime = 0;
$showCaptcha = false;

// Get client IP
$ip = $_SERVER['REMOTE_ADDR'];

// Check if IP is locked out
$lockoutTime = isIpLockedOut($pdo, $ip);
if ($lockoutTime) {
    $error = "Terlalu banyak percobaan login. Silakan coba lagi setelah " . ceil($lockoutTime / 60) . " menit.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lockoutTime) {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);
        
        // Log login attempt
        Logger::access("Login attempt", ['email' => $email, 'ip' => $ip]);
        
        // Check captcha if enabled
        if ($showCaptcha) {
            if (empty($_POST['captcha']) || $_POST['captcha'] != $_SESSION['captcha_result']) {
                $error = 'Captcha salah. Silakan coba lagi.';
            }
        }
        
        if (empty($error)) {
            // Check login attempts
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_logs 
                WHERE ip_address = ? AND status = 'failed' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([$ip]);
            $attempts = $stmt->fetch()['attempts'];
            
            if ($attempts >= MAX_LOGIN_ATTEMPT) {
                $error = 'Terlalu banyak percobaan login. Silakan tunggu 15 menit.';
                logLoginAttempt($pdo, null, $ip, $_SERVER['HTTP_USER_AGENT'], 'blocked');
                Logger::security("Login blocked - too many attempts", ['ip' => $ip]);
            } else {
                // Find user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Check if user is banned
                    if ($user['status'] === 'banned') {
                        $error = 'Akun Anda telah diblokir. Silakan hubungi administrator.';
                        logLoginAttempt($pdo, $user['id'], $ip, $_SERVER['HTTP_USER_AGENT'], 'failed');
                        Logger::security("Login failed - account banned", ['user_id' => $user['id'], 'email' => $email]);
                    } else {
                        // Successful login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['nama'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_foto'] = $user['foto'];
                        
                        // Regenerate session ID
                        session_regenerate_id(true);
                        
                        // Update last login
                        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Log success
                        logLoginAttempt($pdo, $user['id'], $ip, $_SERVER['HTTP_USER_AGENT'], 'success');
                        Logger::access("Login successful", ['user_id' => $user['id'], 'email' => $email]);
                        Logger::activity($user['id'], "User logged in", ['role' => $user['role']]);
                        
                        // Set remember me cookie
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                            // Store token in database (implement this)
                        }
                        
                        // Redirect based on role
                        if ($user['role'] === 'admin') {
                            header('Location: ' . BASE_URL . 'page/admin/index_admin.php');
                        } else {
                            header('Location: ' . BASE_URL . 'page/user/index_user.php');
                        }
                        exit();
                    }
                } else {
                    $error = 'Email atau password salah.';
                    logLoginAttempt($pdo, null, $ip, $_SERVER['HTTP_USER_AGENT'], 'failed');
                    Logger::security("Login failed", ['email' => $email, 'reason' => 'wrong password', 'ip' => $ip]);
                    
                    // Enable captcha after 3 failed attempts
                    if ($attempts + 1 >= 3) {
                        $showCaptcha = true;
                        // Generate simple math captcha
                        $num1 = rand(1, 10);
                        $num2 = rand(1, 10);
                        $_SESSION['captcha_result'] = $num1 + $num2;
                        $_SESSION['captcha_question'] = "$num1 + $num2 = ?";
                    }
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .split-layout {
            min-height: 100vh;
            display: flex;
        }
        @media (max-width: 768px) {
            .split-layout {
                flex-direction: column;
            }
        }
        .illustration-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .illustration-svg {
            max-width: 100%;
            height: auto;
            max-height: 500px;
        }
        .brand-text {
            font-family: 'Playfair Display', serif;
        }
    </style>
</head>
<body>
    <div class="split-layout">
        <!-- Left Side - Illustration Baru -->
        <div class="flex-1 bg-gradient-to-br from-primary to-secondary flex items-center justify-center p-8">
            <div class="text-center">
                <!-- SVG Ilustrasi Baru -->
                <div class="illustration-container">
                    <svg class="w-auto max-w-full h-auto illustration-svg" aria-hidden="true" width="524" height="540" viewBox="0 0 524 540" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M524 278C524 422.699 406.699 540 262 540C117.301 540 0 422.699 0 278C0 133.301 117.301 16 262 16C406.699 16 524 133.301 524 278Z" fill="url(#paint0_linear_383_573)"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M519.795 325C497.649 447.269 390.653 540 261.999 540C133.345 540 26.349 447.269 4.20312 325H519.795Z" fill="url(#paint1_linear_383_573)"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M351 81V240C351 252.15 341.15 262 329 262H211C198.85 262 189 252.15 189 240V81C189 36.2649 225.265 0 270 0C314.735 0 351 36.2649 351 81ZM270 18C235.206 18 207 46.2061 207 81V240C207 242.209 208.791 244 211 244H329C331.209 244 333 242.209 333 240V81C333 46.2061 304.794 18 270 18Z" fill="#374151"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M351 81V240C351 252.15 341.15 262 329 262H211C198.85 262 189 252.15 189 240V81C189 36.2649 225.265 0 270 0C314.735 0 351 36.2649 351 81ZM270 18C235.206 18 207 46.2061 207 81V240C207 242.209 208.791 244 211 244H329C331.209 244 333 242.209 333 240V81C333 46.2061 304.794 18 270 18Z" fill="url(#paint2_linear_383_573)" fill-opacity="0.7"/>
                        <path d="M195 165C195 162.791 193.209 161 191 161H140C137.791 161 136 162.791 136 165V377C136 379.209 137.791 381 140 381H191C193.209 381 195 379.209 195 377V165Z" fill="#111928"/>
                        <path d="M174 164C174 162.343 175.343 161 177 161H385C386.657 161 388 162.343 388 164V378C388 379.657 386.657 381 385 381H177C175.343 381 174 379.657 174 378V164Z" fill="#374151"/>
                        <path d="M174 164C174 162.343 175.343 161 177 161H385C386.657 161 388 162.343 388 164V378C388 379.657 386.657 381 385 381H177C175.343 381 174 379.657 174 378V164Z" fill="url(#paint3_linear_383_573)" fill-opacity="0.7"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M295.245 261.045C301.137 256.988 305 250.195 305 242.5C305 230.074 294.926 220 282.5 220C270.074 220 260 230.074 260 242.5C260 250.195 263.863 256.988 269.755 261.045L263.251 318.776C263.117 319.962 264.045 321 265.238 321H299.762C300.955 321 301.883 319.962 301.749 318.776L295.245 261.045Z" fill="#111928"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M18.2216 195.28C24.5045 171.843 48.6065 157.935 72.0548 164.215C95.5031 170.495 109.418 194.585 103.135 218.022C96.8525 241.459 72.7505 255.367 49.3022 249.087C25.8539 242.808 11.9386 218.717 18.2216 195.28ZM75.3443 151.944C45.1158 143.848 14.0446 161.778 5.94486 191.992C-2.15486 222.206 15.7841 253.262 46.0127 261.358C73.9906 268.851 102.69 254.049 113.231 227.848L200.083 251.109L190.626 286.388C190.323 287.517 190.994 288.678 192.124 288.981L200.308 291.173C201.438 291.475 202.6 290.805 202.903 289.675L212.36 254.397L229.496 258.986L223.603 280.972C223.3 282.101 223.97 283.262 225.101 283.565L233.285 285.757C234.415 286.059 235.577 285.389 235.879 284.26L241.773 262.274L258.909 266.864L249.452 302.142C249.149 303.272 249.82 304.433 250.95 304.735L259.134 306.927C260.264 307.23 261.426 306.56 261.729 305.43L273.927 259.926C274.23 258.797 273.559 257.636 272.429 257.333L116.634 215.608C121.208 187.282 103.673 159.531 75.3443 151.944Z" fill="#6B7280"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M18.2216 195.28C24.5045 171.843 48.6065 157.935 72.0548 164.215C95.5031 170.495 109.418 194.585 103.135 218.022C96.8525 241.459 72.7505 255.367 49.3022 249.087C25.8539 242.808 11.9386 218.717 18.2216 195.28ZM75.3443 151.944C45.1158 143.848 14.0446 161.778 5.94486 191.992C-2.15486 222.206 15.7841 253.262 46.0127 261.358C73.9906 268.851 102.69 254.049 113.231 227.848L200.083 251.109L190.626 286.388C190.323 287.517 190.994 288.678 192.124 288.981L200.308 291.173C201.438 291.475 202.6 290.805 202.903 289.675L212.36 254.397L229.496 258.986L223.603 280.972C223.3 282.101 223.97 283.262 225.101 283.565L233.285 285.757C234.415 286.059 235.577 285.389 235.879 284.26L241.773 262.274L258.909 266.864L249.452 302.142C249.149 303.272 249.82 304.433 250.95 304.735L259.134 306.927C260.264 307.23 261.426 306.56 261.729 305.43L273.927 259.926C274.23 258.797 273.559 257.636 272.429 257.333L116.634 215.608C121.208 187.282 103.673 159.531 75.3443 151.944Z" fill="url(#paint4_linear_383_573)"/>
                        <path d="M136 399C136 397.895 136.895 397 138 397H174.444C175.549 397 176.444 397.895 176.444 399V435.444C176.444 436.549 175.549 437.444 174.444 437.444H138C136.895 437.444 136 436.549 136 435.444V399Z" fill="#c8d8fa"/>
                        <path d="M136 399C136 397.895 136.895 397 138 397H174.444C175.549 397 176.444 397.895 176.444 399V435.444C176.444 436.549 175.549 437.444 174.444 437.444H138C136.895 437.444 136 436.549 136 435.444V399Z" fill="url(#paint5_linear_383_573)"/>
                        <path d="M154.486 428.111L154.911 420.455L148.626 424.671L146.371 420.663L153.081 417.222L146.371 413.782L148.626 409.774L154.911 413.99L154.486 406.333H158.978L158.572 413.99L164.857 409.774L167.112 413.782L160.383 417.222L167.112 420.663L164.857 424.671L158.572 420.455L158.978 428.111H154.486Z" fill="#F9FAFB"/>
                        <path d="M189 399C189 397.895 189.895 397 191 397H227.444C228.549 397 229.444 397.895 229.444 399V435.444C229.444 436.549 228.549 437.444 227.444 437.444H191C189.895 437.444 189 436.549 189 435.444V399Z" fill="#c8d8fa"/>
                        <path d="M189 399C189 397.895 189.895 397 191 397H227.444C228.549 397 229.444 397.895 229.444 399V435.444C229.444 436.549 228.549 437.444 227.444 437.444H191C189.895 437.444 189 436.549 189 435.444V399Z" fill="url(#paint6_linear_383_573)"/>
                        <path d="M207.373 428.111L207.798 420.455L201.513 424.671L199.258 420.663L205.968 417.222L199.258 413.782L201.513 409.774L207.798 413.99L207.373 406.333H211.865L211.458 413.99L217.743 409.774L219.999 413.782L213.27 417.222L219.999 420.663L217.743 424.671L211.458 420.455L211.865 428.111H207.373Z" fill="#F9FAFB"/>
                        <path d="M242 399C242 397.895 242.895 397 244 397H280.444C281.549 397 282.444 397.895 282.444 399V435.444C282.444 436.549 281.549 437.444 280.444 437.444H244C242.895 437.444 242 436.549 242 435.444V399Z" fill="#c8d8fa"/>
                        <path d="M242 399C242 397.895 242.895 397 244 397H280.444C281.549 397 282.444 397.895 282.444 399V435.444C282.444 436.549 281.549 437.444 280.444 437.444H244C242.895 437.444 242 436.549 242 435.444V399Z" fill="url(#paint7_linear_383_573)"/>
                        <path d="M260.264 428.111L260.689 420.455L254.404 424.671L252.148 420.663L258.859 417.222L252.148 413.782L254.404 409.774L260.689 413.99L260.264 406.333H264.756L264.349 413.99L270.634 409.774L272.889 413.782L266.16 417.222L272.889 420.663L270.634 424.671L264.349 420.455L264.756 428.111H260.264Z" fill="#F9FAFB"/>
                        <path d="M295.002 399C295.002 397.895 295.897 397 297.002 397H333.446C334.551 397 335.446 397.895 335.446 399V435.444C335.446 436.549 334.551 437.444 333.446 437.444H297.002C295.897 437.444 295.002 436.549 295.002 435.444V399Z" fill="#c8d8fa"/>
                        <path d="M295.002 399C295.002 397.895 295.897 397 297.002 397H333.446C334.551 397 335.446 397.895 335.446 399V435.444C335.446 436.549 334.551 437.444 333.446 437.444H297.002C295.897 437.444 295.002 436.549 295.002 435.444V399Z" fill="url(#paint8_linear_383_573)"/>
                        <path d="M313.152 428.111L313.577 420.455L307.292 424.671L305.037 420.663L311.747 417.222L305.037 413.782L307.292 409.774L313.577 413.99L313.152 406.333H317.644L317.238 413.99L323.523 409.774L325.778 413.782L319.049 417.222L325.778 420.663L323.523 424.671L317.238 420.455L317.644 428.111H313.152Z" fill="#F9FAFB"/>
                        <path d="M348.002 399C348.002 397.895 348.897 397 350.002 397H386.446C387.551 397 388.446 397.895 388.446 399V435.444C388.446 436.549 387.551 437.444 386.446 437.444H350.002C348.897 437.444 348.002 436.549 348.002 435.444V399Z" fill="#c8d8fa"/>
                        <path d="M348.002 399C348.002 397.895 348.897 397 350.002 397H386.446C387.551 397 388.446 397.895 388.446 399V435.444C388.446 436.549 387.551 437.444 386.446 437.444H350.002C348.897 437.444 348.002 436.549 348.002 435.444V399Z" fill="url(#paint9_linear_383_573)"/>
                        <path d="M366.043 428.111L366.468 420.455L360.183 424.671L357.928 420.663L364.638 417.222L357.928 413.782L360.183 409.774L366.468 413.99L366.043 406.333H370.535L370.128 413.99L376.413 409.774L378.668 413.782L371.94 417.222L378.668 420.663L376.413 424.671L370.128 420.455L370.535 428.111H366.043Z" fill="#F9FAFB"/>
                        <defs>
                            <linearGradient id="paint0_linear_383_573" x1="262" y1="16" x2="262" y2="540" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#1F2A37"/>
                                <stop offset="1" stop-color="#1F2A37" stop-opacity="0"/>
                            </linearGradient>
                            <linearGradient id="paint1_linear_383_573" x1="261.999" y1="325" x2="261.999" y2="499.549" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#2F3948"/>
                                <stop offset="1" stop-color="#2F3948" stop-opacity="0"/>
                            </linearGradient>
                            <linearGradient id="paint2_linear_383_573" x1="270" y1="165.5" x2="270.072" y2="69.9682" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#111928"/>
                                <stop offset="1" stop-color="#111928" stop-opacity="0"/>
                            </linearGradient>
                            <linearGradient id="paint3_linear_383_573" x1="274.434" y1="381" x2="235.605" y2="243.462" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#111928"/>
                                <stop offset="1" stop-color="#111928" stop-opacity="0"/>
                            </linearGradient>
                            <linearGradient id="paint4_linear_383_573" x1="214" y1="247.5" x2="83.5" y2="41" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#F9FAFB" stop-opacity="0"/>
                                <stop offset="1" stop-color="#F9FAFB"/>
                            </linearGradient>
                            <linearGradient id="paint5_linear_383_573" x1="156.222" y1="408.886" x2="156.222" y2="421.776" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#9ab7f6" stop-opacity="0"/>
                                <stop offset="1" stop-color="#9ab7f6"/>
                            </linearGradient>
                            <linearGradient id="paint6_linear_383_573" x1="209.222" y1="408.886" x2="209.222" y2="421.776" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#9ab7f6" stop-opacity="0"/>
                                <stop offset="1" stop-color="#9ab7f6"/>
                            </linearGradient>
                            <linearGradient id="paint7_linear_383_573" x1="262.222" y1="408.886" x2="262.222" y2="421.776" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#9ab7f6" stop-opacity="0"/>
                                <stop offset="1" stop-color="#9ab7f6"/>
                            </linearGradient>
                            <linearGradient id="paint8_linear_383_573" x1="315.224" y1="408.886" x2="315.224" y2="421.776" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#9ab7f6" stop-opacity="0"/>
                                <stop offset="1" stop-color="#9ab7f6"/>
                            </linearGradient>
                            <linearGradient id="paint9_linear_383_573" x1="368.224" y1="408.886" x2="368.224" y2="421.776" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#9ab7f6" stop-opacity="0"/>
                                <stop offset="1" stop-color="#9ab7f6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                
                <!-- Brand Text -->
                <h1 class="text-3xl font-bold mt-6 mb-2 brand-text text-white">PERPUSTAKAAN DYZEN</h1>
                <p class="text-white text-opacity-90">Sistem Perpustakaan Digital Modern</p>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-primary font-playfair">Masuk ke Akun</h2>
                    <p class="text-text-light mt-2">Belum punya akun? <a href="register.php" class="text-secondary hover:text-primary">Daftar sekarang</a></p>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['session_expired'])): ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                        Sesi Anda telah berakhir. Silakan login kembali.
                    </div>
                    <?php unset($_SESSION['session_expired']); ?>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Email</label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-3 border border-accent rounded-lg focus:outline-none focus:border-primary transition"
                               value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div>
                        <label class="block text-text font-semibold mb-2">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required 
                                   class="w-full px-4 py-3 border border-accent rounded-lg focus:outline-none focus:border-primary transition">
                            <button type="button" id="togglePassword" class="absolute right-3 top-3 text-text-light">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($showCaptcha): ?>
                    <div>
                        <label class="block text-text font-semibold mb-2">Captcha: <?= $_SESSION['captcha_question'] ?></label>
                        <input type="number" name="captcha" required 
                               class="w-full px-4 py-3 border border-accent rounded-lg focus:outline-none focus:border-primary transition">
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="mr-2">
                            <span class="text-text">Ingat saya</span>
                        </label>
                        <a href="forgot_password.php" class="text-secondary hover:text-primary">Lupa password?</a>
                    </div>
                    
                    <button type="submit" class="w-full btn-primary py-3">
                        Masuk
                    </button>
                </form>
                
                <div class="mt-8 text-center text-text-light text-sm">
                    <p>&copy; 2024-2026 PERPUSTAKAAN DYZEN. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
            });
        }
    </script>
</body>
</html>