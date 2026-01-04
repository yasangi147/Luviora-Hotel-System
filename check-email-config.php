<?php
/**
 * Email Configuration Checker
 * Diagnoses email sending issues
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
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
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ccc;
        }
        .check-item.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .icon {
            font-size: 20px;
            margin-right: 10px;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        .section {
            margin: 30px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration Diagnostic</h1>
        
        <div class="section">
            <h2>1. PHP Configuration</h2>
            <?php
            $checks = [];
            
            // Check PHP version
            $phpVersion = phpversion();
            $checks[] = [
                'name' => 'PHP Version',
                'status' => version_compare($phpVersion, '7.0', '>=') ? 'success' : 'error',
                'message' => "PHP $phpVersion " . (version_compare($phpVersion, '7.0', '>=') ? '✅' : '❌ (Need 7.0+)')
            ];
            
            // Check if mail function exists
            $checks[] = [
                'name' => 'mail() function',
                'status' => function_exists('mail') ? 'success' : 'error',
                'message' => function_exists('mail') ? '✅ Available' : '❌ Not available'
            ];
            
            // Check OpenSSL
            $checks[] = [
                'name' => 'OpenSSL Extension',
                'status' => extension_loaded('openssl') ? 'success' : 'warning',
                'message' => extension_loaded('openssl') ? '✅ Loaded (required for SMTP)' : '⚠️ Not loaded (SMTP may fail)'
            ];
            
            // Check sockets
            $checks[] = [
                'name' => 'Sockets Extension',
                'status' => extension_loaded('sockets') ? 'success' : 'warning',
                'message' => extension_loaded('sockets') ? '✅ Loaded' : '⚠️ Not loaded'
            ];
            
            foreach ($checks as $check) {
                echo "<div class='check-item {$check['status']}'>";
                echo "<strong>{$check['name']}:</strong> {$check['message']}";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>2. PHPMailer Installation</h2>
            <?php
            $phpMailerPath = __DIR__ . '/vendor/autoload.php';
            $phpMailerExists = file_exists($phpMailerPath);
            
            echo "<div class='check-item " . ($phpMailerExists ? 'success' : 'error') . "'>";
            echo "<strong>PHPMailer:</strong> ";
            if ($phpMailerExists) {
                echo "✅ Installed at: <code>$phpMailerPath</code>";
                
                // Try to load it
                require_once $phpMailerPath;
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    echo "<br>✅ PHPMailer class loaded successfully";
                } else {
                    echo "<br>❌ PHPMailer class not found";
                }
            } else {
                echo "❌ Not found at: <code>$phpMailerPath</code>";
            }
            echo "</div>";
            ?>
        </div>
        
        <div class="section">
            <h2>3. Email Configuration</h2>
            <?php
            $configPath = __DIR__ . '/config/email.php';
            if (file_exists($configPath)) {
                echo "<div class='check-item success'>";
                echo "<strong>Config File:</strong> ✅ Found at <code>$configPath</code>";
                echo "</div>";
                
                require_once $configPath;
                
                $emailChecks = [
                    ['name' => 'SMTP Host', 'value' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined'],
                    ['name' => 'SMTP Port', 'value' => defined('SMTP_PORT') ? SMTP_PORT : 'Not defined'],
                    ['name' => 'SMTP Username', 'value' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'Not defined'],
                    ['name' => 'SMTP Password', 'value' => defined('SMTP_PASSWORD') ? (SMTP_PASSWORD ? '****** (Set)' : 'Empty') : 'Not defined'],
                    ['name' => 'From Email', 'value' => defined('FROM_EMAIL') ? FROM_EMAIL : 'Not defined'],
                ];
                
                foreach ($emailChecks as $check) {
                    $status = ($check['value'] !== 'Not defined' && $check['value'] !== 'Empty') ? 'success' : 'error';
                    echo "<div class='check-item $status'>";
                    echo "<strong>{$check['name']}:</strong> {$check['value']}";
                    echo "</div>";
                }
            } else {
                echo "<div class='check-item error'>";
                echo "<strong>Config File:</strong> ❌ Not found at <code>$configPath</code>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>4. Network Connectivity</h2>
            <?php
            // Test if we can reach Gmail SMTP
            $host = 'smtp.gmail.com';
            $port = 587;
            $timeout = 5;
            
            echo "<div class='check-item'>";
            echo "<strong>Testing connection to $host:$port...</strong><br>";
            
            $startTime = microtime(true);
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            if ($connection) {
                fclose($connection);
                echo "<div class='check-item success'>";
                echo "✅ Successfully connected to Gmail SMTP<br>";
                echo "Response time: {$responseTime}ms";
                echo "</div>";
            } else {
                echo "<div class='check-item error'>";
                echo "❌ Cannot connect to Gmail SMTP<br>";
                echo "Error: $errstr (Code: $errno)<br>";
                echo "<strong>This is likely why email sending fails!</strong><br><br>";
                echo "Possible causes:<br>";
                echo "• Firewall blocking port 587<br>";
                echo "• No internet connection<br>";
                echo "• ISP blocking SMTP<br>";
                echo "• Antivirus blocking connection";
                echo "</div>";
            }
            echo "</div>";
            ?>
        </div>
        
        <div class="section">
            <h2>5. File Permissions</h2>
            <?php
            $tempDir = __DIR__ . '/temp';
            $emailsDir = __DIR__ . '/emails';
            
            // Check temp directory
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0755, true);
            }
            $tempWritable = is_writable($tempDir);
            
            echo "<div class='check-item " . ($tempWritable ? 'success' : 'error') . "'>";
            echo "<strong>Temp Directory:</strong> ";
            echo $tempWritable ? "✅ Writable" : "❌ Not writable";
            echo " (<code>$tempDir</code>)";
            echo "</div>";
            
            // Check emails directory
            if (!is_dir($emailsDir)) {
                @mkdir($emailsDir, 0755, true);
            }
            $emailsWritable = is_writable($emailsDir);
            
            echo "<div class='check-item " . ($emailsWritable ? 'success' : 'error') . "'>";
            echo "<strong>Emails Directory:</strong> ";
            echo $emailsWritable ? "✅ Writable" : "❌ Not writable";
            echo " (<code>$emailsDir</code>)";
            echo "</div>";
            ?>
        </div>
        
        <div class="section">
            <h2>📋 Summary & Recommendations</h2>
            <?php
            $canConnectSMTP = isset($connection) && $connection !== false;
            
            if ($phpMailerExists && $canConnectSMTP) {
                echo "<div class='check-item success'>";
                echo "<h3>✅ Email System Should Work!</h3>";
                echo "<p>All checks passed. Email sending should work properly.</p>";
                echo "<p><strong>Next step:</strong> Try sending a test email using the button below.</p>";
                echo "</div>";
            } else {
                echo "<div class='check-item error'>";
                echo "<h3>⚠️ Issues Detected</h3>";
                echo "<ul>";
                if (!$phpMailerExists) {
                    echo "<li>PHPMailer is not installed</li>";
                }
                if (!$canConnectSMTP) {
                    echo "<li><strong>Cannot connect to Gmail SMTP server</strong> - This is the main issue!</li>";
                    echo "<li>Your firewall or network is blocking SMTP connections</li>";
                    echo "<li>Email sending will fail until this is resolved</li>";
                }
                echo "</ul>";
                echo "<p><strong>Workaround:</strong> The system will save QR codes to the server instead of emailing them.</p>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="section">
            <a href="test-email-quick.php" class="btn">🧪 Test Email Sending</a>
            <a href="confirmation.php" class="btn">📋 Go to Confirmation Page</a>
        </div>
    </div>
</body>
</html>

