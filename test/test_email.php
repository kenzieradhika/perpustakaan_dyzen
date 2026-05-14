<?php
require_once 'config/mail_config.php';

echo "<h2>📧 Email Configuration Test</h2>";
echo "<hr>";
echo "<pre>";
echo testSMTPConnection();
echo "</pre>";
?>