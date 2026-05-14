<?php
require_once 'config/data_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_foto'])) {
    $result = secureFileUpload($_FILES['test_foto'], UPLOAD_PATH);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Upload</title>
</head>
<body>
    <h2>Test Upload Foto</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="test_foto" accept="image/jpeg,image/png,image/webp">
        <button type="submit">Upload</button>
    </form>
</body>
</html>