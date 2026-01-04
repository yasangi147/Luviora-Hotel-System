<?php
/**
 * Booking Receipt Page
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

// Get booking ID from URL
$bookingId = $_GET['id'] ?? null;

if (!$bookingId) {
    header('Location: payments.php');
    exit;
}

// Get booking and payment details
$stmt = $db->prepare("
    SELECT b.*, u.name as guest_name, u.email as guest_email, u.phone as guest_phone,
           r.room_name, r.room_number, r.room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.booking_id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: payments.php');
    exit;
}

// Calculate nights
$checkIn = new DateTime($booking['check_in_date']);
$checkOut = new DateTime($booking['check_out_date']);
$nights = $checkOut->diff($checkIn)->days;

$pageTitle = "Booking Receipt";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Luviora Hotel</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lato', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #a0522d 0%, #C38370 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .hotel-name {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .receipt-title {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .receipt-number {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .receipt-content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            color: #a0522d;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
        }
        
        .divider {
            border-top: 1px solid #e0e0e0;
            margin: 20px 0;
        }
        
        .total-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .total-row.final {
            font-size: 18px;
            font-weight: 700;
            color: #a0522d;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer {
            background: #f9f9f9;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #e0e0e0;
        }
        
        .print-button {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print {
            background: #a0522d;
            color: white;
        }
        
        .btn-print:hover {
            background: #8B5E3C;
        }
        
        .btn-back {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-back:hover {
            background: #d0d0d0;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
            
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="payment-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="hotel-name">🏨 LUVIORA HOTEL</div>
            <div class="receipt-title">BOOKING RECEIPT</div>
            <div class="receipt-number">Booking Reference: <?php echo htmlspecialchars($booking['booking_reference']); ?></div>
        </div>
        
        <!-- Content -->
        <div class="receipt-content">
            <!-- Guest Information -->
            <div class="section">
                <div class="section-title">Guest Information</div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_phone'] ?? 'N/A'); ?></span>
                </div>
            </div>
            
            <!-- Booking Details -->
            <div class="section">
                <div class="section-title">Booking Details</div>
                <div class="info-row">
                    <span class="info-label">Booking Reference:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Check-in:</span>
                    <span class="info-value"><?php echo date('M d, Y H:i A', strtotime($booking['check_in_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Check-out:</span>
                    <span class="info-value"><?php echo date('M d, Y H:i A', strtotime($booking['check_out_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Number of Nights:</span>
                    <span class="info-value"><?php echo $nights; ?> nights</span>
                </div>
            </div>
            
            <!-- Room Details -->
            <div class="section">
                <div class="section-title">Room Details</div>
                <div class="info-row">
                    <span class="info-label">Room:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Room Number:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['room_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Room Type:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['room_type'] ?? 'N/A'); ?></span>
                </div>
            </div>
            
            <!-- Payment Summary -->
            <div class="section">
                <div class="section-title">Payment Summary</div>
                <div class="total-section">
                    <div class="total-row">
                        <span>Room Rate (per night):</span>
                        <span>$<?php echo number_format($booking['price_per_night'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Number of Nights:</span>
                        <span><?php echo $nights; ?></span>
                    </div>
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($booking['price_per_night'] * $nights, 2); ?></span>
                    </div>
                    <div class="total-row final">
                        <span>Total Amount Paid:</span>
                        <span>$<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Status -->
            <div class="section">
                <div class="section-title">Payment Status</div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #4caf50; text-transform: uppercase;">✓ PAID</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value"><?php echo date('M d, Y H:i A', strtotime($booking['created_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Thank you for choosing Luviora Hotel!</p>
            <p>For inquiries, contact us at: info@luviora.com | Phone: +1-800-LUVIORA</p>
            <p style="margin-top: 10px; color: #ccc;">Receipt Generated: <?php echo date('M d, Y H:i A'); ?></p>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;500;600;700&display=swap" rel="stylesheet">
</body>
</html>

