<?php
/**
 * Test Booking Confirmation Email
 * This page tests the email sending functionality with sample booking data
 */

require_once 'config/email.php';

// Sample booking data
$testBookingData = [
    'booking_reference' => 'TEST-' . time(),
    'guest_name' => 'Test User',
    'guest_email' => 'yasangiuduwawala@gmail.com', // Your email for testing
    'room_name' => 'Superior Room',
    'room_type' => 'Deluxe',
    'room_number' => '205',
    'check_in' => 'Nov 10, 2025',
    'check_out' => 'Nov 12, 2025',
    'nights' => 2,
    'num_adults' => 2,
    'num_children' => 0,
    'total_amount' => '250.00'
];

// Generate a test QR code URL
$qrData = json_encode([
    'type' => 'LUVIORA_BOOKING',
    'booking_ref' => $testBookingData['booking_reference'],
    'room_name' => $testBookingData['room_name'],
    'guest_name' => $testBookingData['guest_name']
]);

$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Booking Email - Luviora Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #a0522d;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #a0522d;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        
        .info-box h3 {
            color: #a0522d;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .btn {
            background: linear-gradient(135deg, #a0522d 0%, #8b4513 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(160, 82, 45, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .result {
            margin-top: 25px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .result h4 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .result p {
            margin: 5px 0;
            line-height: 1.6;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .qr-preview {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-preview img {
            max-width: 200px;
            border: 2px solid #a0522d;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test Booking Email</h1>
        <p class="subtitle">Send a test booking confirmation email with QR code</p>
        
        <div class="info-box">
            <h3>Test Booking Details</h3>
            <div class="info-row">
                <span class="info-label">Booking Reference:</span>
                <span class="info-value"><?php echo $testBookingData['booking_reference']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Guest Name:</span>
                <span class="info-value"><?php echo $testBookingData['guest_name']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email To:</span>
                <span class="info-value"><?php echo $testBookingData['guest_email']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Room:</span>
                <span class="info-value"><?php echo $testBookingData['room_name']; ?> (<?php echo $testBookingData['room_type']; ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-in:</span>
                <span class="info-value"><?php echo $testBookingData['check_in']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-out:</span>
                <span class="info-value"><?php echo $testBookingData['check_out']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Amount:</span>
                <span class="info-value">$<?php echo $testBookingData['total_amount']; ?></span>
            </div>
        </div>
        
        <div class="qr-preview">
            <h3 style="color: #a0522d; margin-bottom: 10px;">QR Code Preview</h3>
            <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
        </div>
        
        <button class="btn" onclick="sendTestEmail()" id="sendBtn">
            <span id="btnText">📨 Send Test Email</span>
        </button>
        
        <div class="result" id="result"></div>
    </div>
    
    <script>
        function sendTestEmail() {
            const btn = document.getElementById('sendBtn');
            const btnText = document.getElementById('btnText');
            const result = document.getElementById('result');
            
            // Disable button and show loading
            btn.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span> Sending Email...';
            result.style.display = 'none';
            
            // Prepare data
            const data = {
                bookingData: <?php echo json_encode($testBookingData); ?>,
                qrCodeUrl: '<?php echo $qrCodeUrl; ?>'
            };
            
            // Send request
            fetch('send-test-booking-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btnText.textContent = '📨 Send Test Email';
                result.style.display = 'block';
                
                if (data.success) {
                    result.className = 'result success';
                    result.innerHTML = `
                        <h4>✅ Email Sent Successfully!</h4>
                        <p><strong>Sent to:</strong> ${data.email}</p>
                        <p><strong>Method:</strong> ${data.method || 'PHPMailer'}</p>
                        <p><strong>Subject:</strong> ${data.subject}</p>
                        <p style="margin-top: 15px;">Please check your inbox at <strong>${data.email}</strong></p>
                        <p>Also check your spam/junk folder if you don't see it.</p>
                    `;
                } else {
                    result.className = 'result error';
                    result.innerHTML = `
                        <h4>❌ Email Sending Failed</h4>
                        <p><strong>Error:</strong> ${data.message}</p>
                        <p style="margin-top: 15px;">Please check:</p>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>Email configuration in config/email.php</li>
                            <li>Internet connection</li>
                            <li>Gmail app password is correct</li>
                            <li>Firewall settings</li>
                        </ul>
                    `;
                }
            })
            .catch(error => {
                btn.disabled = false;
                btnText.textContent = '📨 Send Test Email';
                result.style.display = 'block';
                result.className = 'result error';
                result.innerHTML = `
                    <h4>❌ Network Error</h4>
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p>Could not connect to the server. Please check your connection.</p>
                `;
            });
        }
    </script>
</body>
</html>

