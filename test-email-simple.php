<?php
/**
 * Simple Email Test - Step 1: Check if PHPMailer works at all
 * This tests basic email sending without QR codes
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Test - Luviora Hotel</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #a0522d;
            border-bottom: 3px solid #ff6b6b;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #bee5eb;
            margin: 20px 0;
        }
        .step {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin: 20px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #a0522d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #8b4513;
        }
        form {
            margin: 20px 0;
        }
        input[type='email'] {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #ff5252;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 Email Test - Step 1: Basic Email Sending</h1>
";

// Check if PHPMailer exists
$phpMailerPath = __DIR__ . '/vendor/autoload.php';
$phpMailerExists = file_exists($phpMailerPath);

echo "<div class='info'>";
echo "<h3>🔍 System Check:</h3>";
echo "<p><strong>PHPMailer Path:</strong> " . $phpMailerPath . "</p>";
echo "<p><strong>PHPMailer Installed:</strong> " . ($phpMailerExists ? '✅ YES' : '❌ NO') . "</p>";
echo "</div>";

if (!$phpMailerExists) {
    echo "<div class='error'>";
    echo "<h3>❌ PHPMailer Not Found!</h3>";
    echo "<p>PHPMailer is not installed. Please install it first.</p>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
}

// If form is submitted, try to send email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
    
    if (!$testEmail) {
        echo "<div class='error'>";
        echo "<h3>❌ Invalid Email Address</h3>";
        echo "<p>Please enter a valid email address.</p>";
        echo "</div>";
    } else {
        echo "<div class='step'>";
        echo "<h3>🚀 Attempting to Send Test Email...</h3>";
        echo "<p><strong>Recipient:</strong> " . htmlspecialchars($testEmail) . "</p>";
        echo "</div>";
        
        require_once $phpMailerPath;
        
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        
        $mail = new PHPMailer(true);
        
        try {
            // Enable verbose debug output
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'html';
            
            echo "<div class='info'>";
            echo "<h3>📋 SMTP Debug Output:</h3>";
            echo "<pre>";
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'luviorahotel@gmail.com';
            $mail->Password   = 'cjnh bphr qvpz wypr';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Recipients
            $mail->setFrom('luviorahotel@gmail.com', 'Luviora Hotel Test');
            $mail->addAddress($testEmail);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from Luviora Hotel System';
            $mail->Body    = '<h1>✅ Email Test Successful!</h1>
                              <p>If you see this email, your email configuration is working correctly!</p>
                              <p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>
                              <p><strong>From:</strong> Luviora Hotel System</p>
                              <hr>
                              <p style="color: #666; font-size: 12px;">This is a test email from the Luviora Hotel booking system.</p>';
            $mail->AltBody = 'Email Test Successful! If you see this, your email setup works. Sent at: ' . date('Y-m-d H:i:s');
            
            $mail->send();
            
            echo "</pre>";
            echo "</div>";
            
            echo "<div class='success'>";
            echo "<h3>✅ EMAIL SENT SUCCESSFULLY!</h3>";
            echo "<p><strong>Recipient:</strong> " . htmlspecialchars($testEmail) . "</p>";
            echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
            echo "<hr>";
            echo "<h4>📬 Next Steps:</h4>";
            echo "<ol>";
            echo "<li>Check the inbox of <strong>" . htmlspecialchars($testEmail) . "</strong></li>";
            echo "<li>Also check your <strong>SPAM/JUNK</strong> folder</li>";
            echo "<li>If you received the email, your email system is working!</li>";
            echo "<li>If not, check the debug output above for errors</li>";
            echo "</ol>";
            echo "</div>";
            
            echo "<div class='info'>";
            echo "<h3>🎯 What This Means:</h3>";
            echo "<p>✅ PHPMailer is installed correctly</p>";
            echo "<p>✅ Gmail SMTP connection is working</p>";
            echo "<p>✅ Email credentials are valid</p>";
            echo "<p>✅ Your server can send emails</p>";
            echo "<hr>";
            echo "<p><strong>Next:</strong> If the email arrived, the issue is likely with the QR code attachment or the send-qr-email.php script.</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "</pre>";
            echo "</div>";
            
            echo "<div class='error'>";
            echo "<h3>❌ EMAIL SENDING FAILED!</h3>";
            echo "<p><strong>Error Message:</strong></p>";
            echo "<pre>" . htmlspecialchars($mail->ErrorInfo) . "</pre>";
            echo "<hr>";
            echo "<h4>🔧 Common Solutions:</h4>";
            echo "<ul>";
            echo "<li><strong>Authentication Failed:</strong> Check if the Gmail App Password is correct</li>";
            echo "<li><strong>Connection Timeout:</strong> Check your internet connection or firewall</li>";
            echo "<li><strong>SMTP Blocked:</strong> Some servers block outgoing SMTP connections</li>";
            echo "<li><strong>2-Step Verification:</strong> Make sure it's enabled in your Google Account</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<div class='info'>";
            echo "<h3>📝 How to Fix Gmail Issues:</h3>";
            echo "<ol>";
            echo "<li>Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>";
            echo "<li>Enable <strong>2-Step Verification</strong> (required)</li>";
            echo "<li>Scroll to <strong>App Passwords</strong></li>";
            echo "<li>Generate a new password for 'Mail' / 'Other'</li>";
            echo "<li>Update the password in <code>send-qr-email.php</code> (line 298)</li>";
            echo "</ol>";
            echo "</div>";
        }
    }
}

// Show test form
echo "<hr>";
echo "<h3>🧪 Send Test Email:</h3>";
echo "<form method='POST'>";
echo "<p>Enter your email address to receive a test email:</p>";
echo "<input type='email' name='test_email' placeholder='your-email@example.com' required>";
echo "<button type='submit'>📧 Send Test Email</button>";
echo "</form>";

echo "<hr>";
echo "<div class='info'>";
echo "<h3>📚 Testing Steps:</h3>";
echo "<ol>";
echo "<li><strong>Step 1:</strong> Send a test email using the form above (no QR code)</li>";
echo "<li><strong>Step 2:</strong> If successful, test with QR code attachment</li>";
echo "<li><strong>Step 3:</strong> Test from the actual booking confirmation page</li>";
echo "</ol>";
echo "</div>";

echo "<a href='test-send-email-direct.php' class='btn'>🔬 Test with QR Code</a>";
echo "<a href='confirmation.php' class='btn'>📋 Go to Confirmation Page</a>";

echo "</div></body></html>";
?>

