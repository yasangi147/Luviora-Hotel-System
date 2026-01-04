<?php
/**
 * Test PHPMailer Direct Loading
 */

echo "<h1>Testing PHPMailer Direct Loading</h1>";

// Load PHPMailer classes directly
$phpMailerDir = __DIR__ . '/vendor/phpmailer/phpmailer/';

echo "<p><strong>PHPMailer Directory:</strong> $phpMailerDir</p>";

// Check if files exist
$files = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];
foreach ($files as $file) {
    $path = $phpMailerDir . $file;
    if (file_exists($path)) {
        echo "<p style='color: green;'>✅ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file NOT found</p>";
    }
}

// Try to load them
try {
    require_once $phpMailerDir . 'Exception.php';
    echo "<p style='color: green;'>✅ Exception.php loaded</p>";
    
    require_once $phpMailerDir . 'PHPMailer.php';
    echo "<p style='color: green;'>✅ PHPMailer.php loaded</p>";
    
    require_once $phpMailerDir . 'SMTP.php';
    echo "<p style='color: green;'>✅ SMTP.php loaded</p>";
    
    // Try to create instance
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    echo "<p style='color: green;'>✅ PHPMailer instance created successfully!</p>";
    echo "<p><strong>PHPMailer Version:</strong> " . $mail::VERSION . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Now Testing Email Config</h2>";

try {
    require_once 'config/email.php';
    echo "<p style='color: green;'>✅ Email config loaded successfully!</p>";
    
    echo "<p><strong>SMTP Host:</strong> " . SMTP_HOST . "</p>";
    echo "<p><strong>SMTP Port:</strong> " . SMTP_PORT . "</p>";
    echo "<p><strong>From Email:</strong> " . FROM_EMAIL . "</p>";
    echo "<p><strong>From Name:</strong> " . FROM_NAME . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading email config: " . $e->getMessage() . "</p>";
}
?>

