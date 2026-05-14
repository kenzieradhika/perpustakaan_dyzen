<?php
require_once '../config/data_base.php';

// Log the logout
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        INSERT INTO login_logs (user_id, ip_address, user_agent, status) 
        VALUES (?, ?, ?, 'logout')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to landing page
header('Location: ' . BASE_URL . 'landing/halaman_awal.php');
exit();
?>