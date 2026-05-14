<?php
/**
 * PERPUSTAKAAN DYZEN - Database Configuration & Security
 * @version 1.0.0
 * @security All security headers and configurations
 */

// ============================================
// SECURITY HEADERS
// ============================================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Cross-Origin-Resource-Policy: same-origin");
header("Cross-Origin-Embedder-Policy: require-corp");
header("Cross-Origin-Opener-Policy: same-origin");

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.tailwindcss.com https://unpkg.com https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' https://fonts.gstatic.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");

// Strict Transport Security (HSTS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// ============================================
// ERROR HANDLING (Production Mode)
// ============================================
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_reporting(E_ALL);

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr in $errfile on line $errline");
    if (ini_get('display_errors') == 0) {
        return true;
    }
    return false;
});

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'perpustakaan_dyzen');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// APPLICATION CONFIGURATION
// ============================================
define('APP_NAME', 'PERPUSTAKAAN DYZEN');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/perpustakaan_dyzen/');
define('BASE_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', BASE_PATH . 'uploads/covers/');
define('LOG_PATH', BASE_PATH . 'logs/');

// File Upload Limits
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Business Rules
define('DENDA_PER_HARI', 1000); // Rp 1.000/hari
define('MAX_PINJAM_HARI', 7); // 7 days
define('MAX_LOGIN_ATTEMPT', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes in seconds
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_BOOKS_PER_USER', 3); // Max 3 books at a time

// ============================================
// CREATE UPLOAD & LOG DIRECTORIES
// ============================================
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// ============================================
// PDO DATABASE CONNECTION
// ============================================
try {
    $dsn = "mysql:host=" . DB_HOST . 
           ";dbname=" . DB_NAME . 
           ";charset=" . DB_CHARSET . 
           ";collation=utf8mb4_unicode_ci";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'error' => 'Database connection failed',
        'message' => 'Please check your database configuration'
    ]));
}

// ============================================
// SESSION SECURITY CONFIGURATION
// ============================================
// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout check
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['session_expired'] = true;
}
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically
if (!isset($_SESSION['session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = time();
} elseif (time() - $_SESSION['session_regenerated'] > 300) { // Every 5 minutes
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = time();
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Generate CSRF Token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login middleware
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
    
    // Check if user is banned
    global $pdo;
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user && $user['status'] === 'banned') {
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login.php?error=account_banned');
        exit();
    }
}

/**
 * Require admin middleware
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'page/user/index_user.php');
        exit();
    }
}

/**
 * Escape output for XSS prevention
 */
function e($string, $flags = ENT_QUOTES) {
    return htmlspecialchars($string ?? '', $flags, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

/**
 * Log login attempts for brute force protection
 */
function logLoginAttempt($pdo, $user_id, $ip, $user_agent, $status) {
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $ip, $user_agent, $status]);
}

/**
 * Check if IP is locked out
 */
function isIpLockedOut($pdo, $ip) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts, MAX(created_at) as last_attempt 
        FROM login_logs 
        WHERE ip_address = ? AND status = 'failed' 
        AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$ip, LOCKOUT_TIME]);
    $result = $stmt->fetch();
    
    if ($result['attempts'] >= MAX_LOGIN_ATTEMPT) {
        $timeSinceLastAttempt = time() - strtotime($result['last_attempt']);
        if ($timeSinceLastAttempt < LOCKOUT_TIME) {
            return LOCKOUT_TIME - $timeSinceLastAttempt;
        }
    }
    return false;
}

/**
 * Secure file upload
 */
function secureFileUpload($file, $uploadPath) {
    // Check file error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File terlalu besar. Maksimal 2MB'];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Ekstensi file tidak diizinkan'];
    }
    
    // Generate secure filename
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $filepath = $uploadPath . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
    
    return ['success' => true, 'filename' => $filename];
}

/**
 * Get user data
 */
function getUserData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}