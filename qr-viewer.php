<?php
/**
 * QR Code Viewer - Displays booking details when QR code is scanned
 * This page shows the booking summary and room key in a nice, structured format
 */

// Get QR data from URL parameter
$qrData = isset($_GET['data']) ? $_GET['data'] : '';

// Decode JSON data
$bookingData = null;
$error = null;

if (!empty($qrData)) {
    $bookingData = json_decode($qrData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = "Invalid QR code data";
    }
} else {
    $error = "No QR code data provided";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Luviora Hotel</title>
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f0e8 0%, #e8dcc8 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(160, 82, 45, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #a0522d 0%, #8b4513 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .error-message {
            background: #fee;
            border: 2px solid #fcc;
            color: #c33;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .section {
            margin-bottom: 30px;
            background: #f9f9f9;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #a0522d;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: #a0522d;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 24px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }
        
        .info-value {
            font-weight: 500;
            color: #333;
            font-size: 14px;
            text-align: right;
        }
        
        .booking-ref {
            background: linear-gradient(135deg, #d4a574 0%, #c9984a 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 2px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-active {
            background: #fff3cd;
            color: #856404;
        }
        
        .total-amount {
            background: #a0522d;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        
        .total-amount .label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .total-amount .amount {
            font-size: 32px;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
        }
        
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .icon-circle {
            width: 50px;
            height: 50px;
            background: #a0522d;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
        }
        
        @media (max-width: 600px) {
            .container {
                border-radius: 0;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-value {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 Luviora Hotel</h1>
            <p>Booking Details & Room Key</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php elseif ($bookingData): ?>
                
                <!-- Booking Reference -->
                <div class="booking-ref">
                    📋 <?php echo htmlspecialchars($bookingData['booking_ref']); ?>
                </div>
                
                <!-- Room Key Section -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-key"></i>
                        <span>Digital Room Key</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Room Name</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($bookingData['room_key']['room_name']); ?></strong></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Room Type</span>
                        <span class="info-value"><?php echo htmlspecialchars($bookingData['room_key']['room_type']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Check-In</span>
                        <span class="info-value"><?php echo date('l, M d, Y', strtotime($bookingData['room_key']['check_in'])); ?> at <?php echo $bookingData['room_key']['activation_time']; ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Check-Out</span>
                        <span class="info-value"><?php echo date('l, M d, Y', strtotime($bookingData['room_key']['check_out'])); ?> at <?php echo $bookingData['room_key']['checkout_time']; ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <?php
                            $today = date('Y-m-d');
                            $checkIn = $bookingData['room_key']['check_in'];
                            $checkOut = $bookingData['room_key']['check_out'];
                            
                            if ($today < $checkIn) {
                                echo '<span class="status-badge status-confirmed">Confirmed</span>';
                            } elseif ($today >= $checkIn && $today <= $checkOut) {
                                echo '<span class="status-badge status-active">Active</span>';
                            } else {
                                echo '<span class="status-badge">Completed</span>';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <!-- Booking Summary Section -->
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Booking Summary</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Guest Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($bookingData['booking_summary']['guest_name']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($bookingData['booking_summary']['email']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($bookingData['booking_summary']['phone']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Adults</span>
                        <span class="info-value"><?php echo $bookingData['booking_summary']['adults']; ?> Adult<?php echo $bookingData['booking_summary']['adults'] > 1 ? 's' : ''; ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Children</span>
                        <span class="info-value"><?php echo $bookingData['booking_summary']['children']; ?> Child<?php echo $bookingData['booking_summary']['children'] !== 1 ? 'ren' : ''; ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Number of Nights</span>
                        <span class="info-value"><?php echo $bookingData['booking_summary']['nights']; ?> Night<?php echo $bookingData['booking_summary']['nights'] > 1 ? 's' : ''; ?></span>
                    </div>
                </div>
                
                <!-- Total Amount -->
                <div class="total-amount">
                    <div class="label">Total Amount Paid</div>
                    <div class="amount">$<?php echo number_format($bookingData['booking_summary']['total_amount'], 2); ?></div>
                </div>
                
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing Luviora Hotel</p>
            <p>For assistance, contact us at luviorahotel@gmail.com</p>
        </div>
    </div>
</body>
</html>

