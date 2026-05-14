<?php
require_once '../../config/data_base.php';
requireAdmin();

$user_id = $_SESSION['user_id'];
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Profile</h2>";

// Cek data user
$stmt = $pdo->prepare("SELECT id, nama, email, foto FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

echo "<h3>Data dari Database:</h3>";
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h3>Path Foto:</h3>";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "<br>";
echo "Foto filename: " . ($user['foto'] ?? 'NULL') . "<br>";
echo "Full path: " . UPLOAD_PATH . $user['foto'] . "<br>";
echo "File exists: " . (file_exists(UPLOAD_PATH . $user['foto']) ? 'YES' : 'NO') . "<br>";

echo "<h3>Folder Uploads:</h3>";
$files = scandir(UPLOAD_PATH);
echo "<pre>";
print_r($files);
echo "</pre>";

// Test upload sederhana
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_foto'])) {
    $result = secureFileUpload($_FILES['test_foto'], UPLOAD_PATH);
    echo "<h3>Hasil Upload Test:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET foto = ? WHERE id = ?");
        $stmt->execute([$result['filename'], $user_id]);
        echo "<p style='color:green'>✅ Database updated! Foto: " . $result['filename'] . "</p>";
        echo "<img src='../../uploads/covers/" . $result['filename'] . "' style='width:150px;height:150px;object-fit:cover;border-radius:50%;'>";
    }
}
?>

<h3>Test Upload Foto Baru</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_foto" accept="image/jpeg,image/png,image/webp">
    <button type="submit">Upload Test</button>
</form>

<h3>Foto Saat Ini:</h3>
<?php if (!empty($user['foto']) && file_exists(UPLOAD_PATH . $user['foto'])): ?>
    <img src="../../uploads/covers/<?= e($user['foto']) ?>" style="width:150px;height:150px;object-fit:cover;border-radius:50%;">
    <p>✅ Foto ditemukan di folder</p>
<?php else: ?>
    <p style="color:red">❌ Foto TIDAK ditemukan di folder atau database kosong</p>
<?php endif; ?>