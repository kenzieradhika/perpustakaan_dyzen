<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/data_base.php';
requireAdmin();

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Debug Info:</h3>";
    echo "<pre>";
    echo "POST: ";
    print_r($_POST);
    echo "\nFILES: ";
    print_r($_FILES);
    echo "\n</pre>";
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_result = secureFileUpload($_FILES['foto'], UPLOAD_PATH);
        echo "<pre>Upload Result: ";
        print_r($upload_result);
        echo "</pre>";
        
        if ($upload_result['success']) {
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET foto = ? WHERE id = ?");
            if ($stmt->execute([$upload_result['filename'], $user_id])) {
                $message = "<p style='color:green'>✅ Berhasil! Foto tersimpan: " . $upload_result['filename'] . "</p>";
                // Update session
                $_SESSION['user_foto'] = $upload_result['filename'];
            } else {
                $message = "<p style='color:red'>❌ Gagal update database!</p>";
                print_r($stmt->errorInfo());
            }
        } else {
            $message = "<p style='color:red'>❌ " . $upload_result['message'] . "</p>";
        }
    } else {
        $message = "<p style='color:red'>❌ Tidak ada file yang diupload atau error: " . ($_FILES['foto']['error'] ?? 'no file') . "</p>";
    }
}

// Get current user data
$stmt = $pdo->prepare("SELECT id, nama, email, foto FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Upload Foto</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .preview { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-top: 10px; }
        .success { background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Test Upload Foto Profile</h2>
    
    <?= $message ?>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>
        <button type="submit">Upload</button>
    </form>
    
    <h3>Data User Saat Ini:</h3>
    <p>ID: <?= $user['id'] ?></p>
    <p>Nama: <?= e($user['nama']) ?></p>
    <p>Email: <?= e($user['email']) ?></p>
    <p>Foto di database: <strong><?= e($user['foto'] ?? '(kosong)') ?></strong></p>
    
    <h3>Preview:</h3>
    <?php if (!empty($user['foto']) && file_exists(UPLOAD_PATH . $user['foto'])): ?>
        <img src="../../uploads/covers/<?= e($user['foto']) ?>" class="preview">
        <p>✅ File ditemukan di folder: <?= UPLOAD_PATH . $user['foto'] ?></p>
    <?php else: ?>
        <p style="color:orange">⚠️ Belum ada foto atau file tidak ditemukan</p>
        <p>Folder uploads: <?= UPLOAD_PATH ?></p>
    <?php endif; ?>
    
    <h3>Isi Folder Uploads:</h3>
    <?php
    if (is_dir(UPLOAD_PATH)) {
        $files = scandir(UPLOAD_PATH);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && $file != '.htaccess') {
                echo "<li>$file</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>Folder tidak ditemukan: " . UPLOAD_PATH . "</p>";
    }
    ?>
</body>
</html>