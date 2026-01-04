<?php
/**
 * Test PHPMailer Installation
 * This script tests if PHPMailer is properly installed and configured
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>PHPMailer Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #a0522d;
            border-bottom: 3px solid #d4a574;
            padding-bottom: 10px;
        }
        .test-item {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 5px solid #ccc;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .button {
            background: #a0522d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .button:hover {
            background: #8b4513;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 PHPMailer Installation Test</h1>";

// Test 1: Check if vendor/autoload.php exists
echo "<div class='test-item";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo " success'>";
    echo "<strong>✅ Test 1: Autoloader Found</strong><br>";
    echo "File exists at: <code>" . $autoloadPath . "</code>";
} else {
    echo " error'>";
    echo "<strong>❌ Test 1: Autoloader Missing</strong><br>";
    echo "File not found at: <code>" . $autoloadPath . "</code><br>";
    echo "Please create this file to enable PHPMailer.";
}
echo "</div>";

// Test 2: Check if PHPMailer directory exists
echo "<div class='test-item";
$phpMailerDir = __DIR__ . '/vendor/phpmailer/phpmailer';
if (is_dir($phpMailerDir)) {
    echo " success'>";
    echo "<strong>✅ Test 2: PHPMailer Directory Found</strong><br>";
    echo "Directory exists at: <code>" . $phpMailerDir . "</code>";
} else {
    echo " error'>";
    echo "<strong>❌ Test 2: PHPMailer Directory Missing</strong><br>";
    echo "Directory not found at: <code>" . $phpMailerDir . "</code>";
}
echo "</div>";

// Test 3: Check if PHPMailer.php exists
echo "<div class='test-item";
$phpMailerFile = $phpMailerDir . '/PHPMailer.php';
if (file_exists($phpMailerFile)) {
    echo " success'>";
    echo "<strong>✅ Test 3: PHPMailer.php Found</strong><br>";
    echo "Main file exists at: <code>" . $phpMailerFile . "</code>";
} else {
    echo " error'>";
    echo "<strong>❌ Test 3: PHPMailer.php Missing</strong><br>";
    echo "Main file not found at: <code>" . $phpMailerFile . "</code>";
}
echo "</div>";

// Test 4: Try to load PHPMailer
echo "<div class='test-item";
if (file_exists($autoloadPath)) {
    try {
        require_once $autoloadPath;
        
        // Try to instantiate PHPMailer
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        
        $mail = new PHPMailer(true);
        echo " success'>";
        echo "<strong>✅ Test 4: PHPMailer Loaded Successfully</strong><br>";
        echo "PHPMailer class is available and can be instantiated.";
    } catch (Exception $e) {
        echo " error'>";
        echo "<strong>❌ Test 4: PHPMailer Load Failed</strong><br>";
        echo "Error: " . $e->getMessage();
    }
} else {
    echo " warning'>";
    echo "<strong>⚠️ Test 4: Skipped (No Autoloader)</strong><br>";
    echo "Cannot test PHPMailer without autoloader.";
}
echo "</div>";

// Test 5: Check email configuration
echo "<div class='test-item";
$emailConfigPath = __DIR__ . '/config/email.php';
if (file_exists($emailConfigPath)) {
    echo " success'>";
    echo "<strong>✅ Test 5: Email Configuration Found</strong><br>";
    echo "Config file exists at: <code>" . $emailConfigPath . "</code><br>";
    
    require_once $emailConfigPath;
    echo "<br><strong>SMTP Settings:</strong><br>";
    echo "Host: <code>" . (defined('SMTP_HOST') ? SMTP_HOST : 'Not defined') . "</code><br>";
    echo "Port: <code>" . (defined('SMTP_PORT') ? SMTP_PORT : 'Not defined') . "</code><br>";
    echo "Username: <code>" . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'Not defined') . "</code><br>";
    echo "Encryption: <code>" . (defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'Not defined') . "</code>";
} else {
    echo " error'>";
    echo "<strong>❌ Test 5: Email Configuration Missing</strong><br>";
    echo "Config file not found at: <code>" . $emailConfigPath . "</code>";
}
echo "</div>";

// Test 6: Try to send a test email
if (isset($_POST['send_test'])) {
    echo "<div class='test-item";
    
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        require_once $emailConfigPath;
        
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;
            
            // Recipients
            $testEmail = $_POST['test_email'] ?? 'test@example.com';
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($testEmail);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from Luviora Hotel System';
            $mail->Body    = '
                <html>
                <body style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2 style="color: #a0522d;">✅ Email System is Working!</h2>
                    <p>This is a test email from the Luviora Hotel booking system.</p>
                    <p>If you received this email, it means:</p>
                    <ul>
                        <li>PHPMailer is properly installed</li>
                        <li>SMTP configuration is correct</li>
                        <li>Email sending is functional</li>
                    </ul>
                    <p><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
                    <hr>
                    <p style="color: #666; font-size: 12px;">
                        Luviora Hotel System<br>
                        Automated Test Email
                    </p>
                </body>
                </html>
            ';
            $mail->AltBody = 'This is a test email from Luviora Hotel System. Email system is working correctly!';
            
            $mail->send();
            
            echo " success'>";
            echo "<strong>✅ Test 6: Test Email Sent Successfully!</strong><br>";
            echo "A test email has been sent to: <code>" . htmlspecialchars($testEmail) . "</code><br>";
            echo "Please check your inbox and spam folder.";
            
        } catch (Exception $e) {
            echo " error'>";
            echo "<strong>❌ Test 6: Test Email Failed</strong><br>";
            echo "Error: " . $mail->ErrorInfo . "<br>";
            echo "Exception: " . $e->getMessage();
        }
    } else {
        echo " warning'>";
        echo "<strong>⚠️ Test 6: Cannot Send (PHPMailer Not Loaded)</strong>";
    }
    echo "</div>";
}

// Summary
echo "<div class='test-item info'>";
echo "<strong>📊 Summary:</strong><br>";

$allTestsPassed = file_exists($autoloadPath) && 
                  is_dir($phpMailerDir) && 
                  file_exists($phpMailerFile) && 
                  file_exists($emailConfigPath);

if ($allTestsPassed) {
    echo "✅ All configuration tests passed!<br>";
    echo "PHPMailer is properly installed and configured.<br>";
    echo "You can now send real emails from your booking system.";
} else {
    echo "⚠️ Some tests failed. Please check the errors above.<br>";
    echo "Follow the INSTALL_PHPMAILER.md guide to complete installation.";
}
echo "</div>";

// Test form
echo "
<form method='POST'>
    <h3>🧪 Send Test Email</h3>
    <p>Enter an email address to test email sending:</p>
    <input type='email' name='test_email' placeholder='your-email@example.com' 
           style='padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;' required>
    <button type='submit' name='send_test' class='button'>📧 Send Test Email</button>
</form>
";

echo "
        <div style='margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;'>
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>If all tests passed, go to your booking confirmation page</li>
                <li>Click 'Send to Email' button</li>
                <li>Check if email is received (check spam folder too)</li>
                <li>If email is not sent, check the error messages above</li>
            </ol>
            <p><a href='confirmation.php' style='color: #a0522d;'>→ Go to Confirmation Page</a> (requires active booking)</p>
            <p><a href='INSTALL_PHPMAILER.md' style='color: #a0522d;'>→ View Installation Guide</a></p>
        </div>
    </div>
</body>
</html>";
?>


