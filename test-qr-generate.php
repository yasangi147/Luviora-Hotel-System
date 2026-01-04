<?php
/**
 * Test QR Code Generator
 * Generates a test QR code with the correct JSON format for testing Clark scanner
 */

// Sample booking data
$qrDataArray = [
    'type' => 'LUVIORA_BOOKING',
    'booking_ref' => 'LUV' . date('Ymd') . rand(1000, 9999),
    'room_key' => [
        'room_name' => 'Deluxe Ocean View Suite',
        'room_type' => 'Deluxe Suite',
        'check_in' => date('Y-m-d'),
        'check_out' => date('Y-m-d', strtotime('+3 days')),
        'activation_time' => '14:00',
        'checkout_time' => '11:00'
    ],
    'booking_summary' => [
        'guest_name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+1 234 567 8900',
        'adults' => 2,
        'children' => 1,
        'nights' => 3,
        'total_amount' => 450.00
    ]
];

$qrData = json_encode($qrDataArray);
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($qrData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test QR Code Generator - Luviora Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f5dc 0%, #d4a574 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .qr-display {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        .qr-display img {
            border: 3px solid #a0522d;
            border-radius: 10px;
            padding: 15px;
            background: white;
        }
        .json-display {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 20px 0;
        }
        .btn-test {
            background: #a0522d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn-test:hover {
            background: #8b4513;
            color: white;
        }
        .alert-info {
            background: linear-gradient(135deg, #e6f7ff 0%, #cceeff 100%);
            border-left: 5px solid #0066cc;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 style="color: #a0522d; text-align: center; margin-bottom: 30px;">
            <i class="fas fa-qrcode"></i> Test QR Code Generator
        </h1>

        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Testing Instructions:</h5>
            <ol>
                <li>This page generates a test QR code with the correct JSON format</li>
                <li>Download or screenshot the QR code below</li>
                <li>Go to Clark Dashboard → QR Scan Check-in</li>
                <li>Upload the QR code image or scan it with camera</li>
                <li>The popup should display Room Key and Booking Summary sections</li>
            </ol>
        </div>

        <div class="qr-display">
            <h4 style="color: #a0522d; margin-bottom: 20px;">
                <i class="fas fa-key"></i> Test QR Code
            </h4>
            <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="Test QR Code" id="qrImage">
            <div style="margin-top: 20px;">
                <button class="btn-test" onclick="downloadQR()">
                    <i class="fas fa-download"></i> Download QR Code
                </button>
            </div>
        </div>

        <h5 style="color: #a0522d; margin-top: 30px;">
            <i class="fas fa-code"></i> QR Code Data (JSON):
        </h5>
        <div class="json-display">
            <?php echo htmlspecialchars(json_encode($qrDataArray, JSON_PRETTY_PRINT)); ?>
        </div>

        <h5 style="color: #a0522d; margin-top: 30px;">
            <i class="fas fa-info-circle"></i> Booking Details:
        </h5>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
            <p><strong>Booking Reference:</strong> <?php echo $qrDataArray['booking_ref']; ?></p>
            <p><strong>Guest Name:</strong> <?php echo $qrDataArray['booking_summary']['guest_name']; ?></p>
            <p><strong>Room:</strong> <?php echo $qrDataArray['room_key']['room_name']; ?></p>
            <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($qrDataArray['room_key']['check_in'])); ?></p>
            <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($qrDataArray['room_key']['check_out'])); ?></p>
            <p><strong>Total Amount:</strong> $<?php echo number_format($qrDataArray['booking_summary']['total_amount'], 2); ?></p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="clark/qr-scan-checkin.php" class="btn-test">
                <i class="fas fa-sign-in-alt"></i> Test Check-in Scanner
            </a>
            <a href="clark/qr-scan-checkout.php" class="btn-test">
                <i class="fas fa-sign-out-alt"></i> Test Check-out Scanner
            </a>
            <a href="clark/qr-scan.php" class="btn-test">
                <i class="fas fa-qrcode"></i> Test General Scanner
            </a>
        </div>

        <div class="alert alert-warning" style="margin-top: 30px; background: linear-gradient(135deg, #fff5e6 0%, #ffe6cc 100%); border-left: 5px solid #ff9800;">
            <h6><i class="fas fa-exclamation-triangle"></i> Important Notes:</h6>
            <ul>
                <li>This is a TEST booking reference - it won't exist in the database</li>
                <li>The scanner will show an error "Booking not found" - this is expected</li>
                <li>The QR code parsing should work correctly and extract the JSON data</li>
                <li>To test with real data, create a new booking through the booking system</li>
                <li>New bookings will generate QR codes with the correct format</li>
            </ul>
        </div>
    </div>

    <script>
        function downloadQR() {
            const qrImage = document.getElementById('qrImage');
            fetch(qrImage.src)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'Luviora-Test-QR-<?php echo $qrDataArray['booking_ref']; ?>.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    alert('✅ QR Code downloaded successfully!\n\nYou can now upload this to the Clark scanner for testing.');
                })
                .catch(error => {
                    console.error('Download error:', error);
                    alert('❌ Failed to download. Please right-click the QR code and select "Save Image As..."');
                });
        }
    </script>
</body>
</html>

