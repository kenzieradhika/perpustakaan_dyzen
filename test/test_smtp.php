<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/mail_config.php';

echo "<h2>Test SMTP Connection</h2>";
echo "<pre>";
$result = testSMTPConnection();
echo $result;
echo "</pre>";
?>