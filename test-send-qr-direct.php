<?php
/**
 * Direct test of send-qr-email.php
 * This will show us exactly what's being output
 */

// Capture all output
ob_start();

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create test data
$testData = [
    'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TEST-' . time(),
    'guestEmail' => 'yasangiuduwawala@gmail.com',
    'bookingRef' => 'TEST-' . time(),
    'guestName' => 'Test User',
    'roomName' => 'Superior Room',
    'checkIn' => 'Nov 10, 2025',
    'checkOut' => 'Nov 12, 2025'
];

// Simulate POST input
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($testData);

// Mock file_get_contents for php://input
function mock_file_get_contents($filename) {
    if ($filename === 'php://input') {
        return $GLOBALS['HTTP_RAW_POST_DATA'];
    }
    return file_get_contents($filename);
}

// Include the file
include 'send-qr-email.php';

// Get the output
$output = ob_get_clean();

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Test Results</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #a0522d; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .success { color: green; }
        .error { color: red; }
        .section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Direct Test Results</h1>
        
        <div class="section">
            <h2>Test Data Sent:</h2>
            <pre><?php echo json_encode($testData, JSON_PRETTY_PRINT); ?></pre>
        </div>
        
        <div class="section">
            <h2>Raw Output from send-qr-email.php:</h2>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        </div>
        
        <div class="section">
            <h2>Output Length:</h2>
            <p><?php echo strlen($output); ?> bytes</p>
        </div>
        
        <div class="section">
            <h2>Is Valid JSON?</h2>
            <?php
            $decoded = json_decode($output, true);
            if ($decoded !== null) {
                echo '<p class="success">✅ YES - Valid JSON</p>';
                echo '<h3>Decoded Data:</h3>';
                echo '<pre>' . json_encode($decoded, JSON_PRETTY_PRINT) . '</pre>';
            } else {
                echo '<p class="error">❌ NO - Invalid JSON</p>';
                echo '<p><strong>JSON Error:</strong> ' . json_last_error_msg() . '</p>';
                echo '<h3>First 500 characters:</h3>';
                echo '<pre>' . htmlspecialchars(substr($output, 0, 500)) . '</pre>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>Character Analysis (first 100 bytes):</h2>
            <pre><?php
            $first100 = substr($output, 0, 100);
            for ($i = 0; $i < strlen($first100); $i++) {
                $char = $first100[$i];
                $ord = ord($char);
                echo sprintf("Pos %3d: '%s' (ASCII %3d)\n", $i, 
                    ($ord < 32 || $ord > 126) ? '\\x' . dechex($ord) : $char, 
                    $ord);
            }
            ?></pre>
        </div>
    </div>
</body>
</html>

