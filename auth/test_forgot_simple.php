<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Step 1: File berhasil diakses<br>";

require_once '../config/data_base.php';
echo "Step 2: Database connected<br>";

require_once '../config/mail_config.php';
echo "Step 3: Mail config loaded<br>";

echo "Step 4: Semua berhasil! File tidak error.";
?>