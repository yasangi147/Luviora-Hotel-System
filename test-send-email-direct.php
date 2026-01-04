<?php
/**
 * Direct Test for Email Sending
 * This file tests the email sending functionality directly
 */

// Test data
$testData = [
    'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TEST',
    'guestEmail' => 'yasangiuduwawala@gmail.com', // Your test email
    'bookingRef' => 'TEST' . time(),
    'guestName' => 'Test User',
    'roomName' => 'Superior Double Room',
    'checkIn' => 'Oct 28, 2025',
    'checkOut' => 'Oct 30, 2025'
];

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
file_put_contents('php://input', json_encode($testData));

// Include the email sending script
ob_start();
include 'send-qr-email.php';
$response = ob_get_clean();

// Display result
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Send Test</title>
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
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #bee5eb;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Send Test Results</h1>
        
        <div class="info">
            <h3>Test Data Sent:</h3>
            <pre><?php echo json_encode($testData, JSON_PRETTY_PRINT); ?></pre>
        </div>
        
        <h3>Response from send-qr-email.php:</h3>
        <pre><?php echo htmlspecialchars($response); ?></pre>
        
        <?php
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['success'])) {
            if ($responseData['success']) {
                echo '<div class="success">';
                echo '<h3>✅ Success!</h3>';
                echo '<p><strong>Message:</strong> ' . htmlspecialchars($responseData['message']) . '</p>';
                if (isset($responseData['method'])) {
                    echo '<p><strong>Method:</strong> ' . htmlspecialchars($responseData['method']) . '</p>';
                }
                if (isset($responseData['note'])) {
                    echo '<p><strong>Note:</strong> ' . htmlspecialchars($responseData['note']) . '</p>';
                }
                echo '</div>';
                
                // Check if email was saved to file
                if (strpos($responseData['method'], 'File Save') !== false) {
                    echo '<div class="info">';
                    echo '<h3>📁 Email Saved to File</h3>';
                    echo '<p>Check the <code>emails/</code> folder for the saved email.</p>';
                    echo '<p>To send real emails, install PHPMailer (see INSTALL_PHPMAILER.md)</p>';
                    echo '</div>';
                } else {
                    echo '<div class="success">';
                    echo '<h3>📧 Email Sent!</h3>';
                    echo '<p>Check the inbox of: <strong>' . htmlspecialchars($testData['guestEmail']) . '</strong></p>';
                    echo '<p>Also check spam folder!</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '<h3>❌ Error</h3>';
                echo '<p><strong>Message:</strong> ' . htmlspecialchars($responseData['message']) . '</p>';
                if (isset($responseData['debug'])) {
                    echo '<p><strong>Debug:</strong> ' . htmlspecialchars($responseData['debug']) . '</p>';
                }
                echo '</div>';
            }
        } else {
            echo '<div class="error">';
            echo '<h3>❌ Invalid Response</h3>';
            echo '<p>The response is not valid JSON. This usually means there\'s a PHP error.</p>';
            echo '</div>';
        }
        ?>
        
        <hr>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>If email was saved to file: Check <code>emails/</code> folder</li>
            <li>If email was sent: Check inbox of <?php echo htmlspecialchars($testData['guestEmail']); ?></li>
            <li>To enable real email sending: Install PHPMailer (see INSTALL_PHPMAILER.md)</li>
            <li>Test from confirmation page: Complete a booking and click "Send to Email"</li>
        </ol>
    </div>
</body>
</html>

