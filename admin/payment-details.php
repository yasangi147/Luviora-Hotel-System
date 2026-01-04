<?php
/**
 * Payment Details Page
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

// Get payment status color
$statusColors = [
    'paid' => '#d4edda',
    'partial' => '#fff3cd',
    'unpaid' => '#f8d7da',
    'refunded' => '#e2e3e5'
];

$statusTextColors = [
    'paid' => '#155724',
    'partial' => '#856404',
    'unpaid' => '#721c24',
    'refunded' => '#383d41'
];

$statusBg = $statusColors[$booking['payment_status']] ?? '#e2e3e5';
$statusText = $statusTextColors[$booking['payment_status']] ?? '#383d41';

$pageTitle = "Payment Details";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Luviora Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <style>
        .detail-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .detail-header h3 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .amount-highlight {
            font-size: 28px;
            color: #a0522d;
            font-weight: 700;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #e0e0e0;
            color: #000;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-edit {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit:hover {
            background: #138496;
            color: white;
        }
        
        .btn-receipt {
            background: #28a745;
            color: white;
        }
        
        .btn-receipt:hover {
            background: #218838;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1><i class="fas fa-receipt"></i> Payment Details</h1>
                        <p>Booking Reference: <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></p>
                    </div>
                    <a href="payments.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Payments
                    </a>
                </div>
                
                <!-- Payment Summary -->
                <div class="detail-card">
                    <div class="detail-header">
                        <h3><i class="fas fa-credit-card"></i> Payment Summary</h3>
                        <span class="status-badge" style="background: <?php echo $statusBg; ?>; color: <?php echo $statusText; ?>;">
                            <?php echo ucfirst($booking['payment_status']); ?>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="amount-highlight">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value"><?php echo ucfirst($booking['payment_status']); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Price Per Night</div>
                            <div class="detail-value">$<?php echo number_format($booking['price_per_night'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Number of Nights</div>
                            <div class="detail-value"><?php echo $nights; ?> nights</div>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Details -->
                <div class="detail-card">
                    <div class="detail-header">
                        <h3><i class="fas fa-calendar"></i> Booking Details</h3>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Check-in Date</div>
                            <div class="detail-value"><?php echo date('M d, Y H:i A', strtotime($booking['check_in_date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Check-out Date</div>
                            <div class="detail-value"><?php echo date('M d, Y H:i A', strtotime($booking['check_out_date'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Booking Status</div>
                            <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $booking['booking_status'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Booking Date</div>
                            <div class="detail-value"><?php echo date('M d, Y H:i A', strtotime($booking['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Guest Details -->
                <div class="detail-card">
                    <div class="detail-header">
                        <h3><i class="fas fa-user"></i> Guest Information</h3>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Guest Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['guest_email']); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['guest_phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Adults / Children</div>
                            <div class="detail-value"><?php echo $booking['num_adults']; ?> adults / <?php echo $booking['num_children']; ?> children</div>
                        </div>
                    </div>
                </div>
                
                <!-- Room Details -->
                <div class="detail-card">
                    <div class="detail-header">
                        <h3><i class="fas fa-bed"></i> Room Information</h3>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Room Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Room Number</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['room_number'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Room Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['room_type'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Special Requests -->
                <?php if ($booking['special_requests']): ?>
                <div class="detail-card">
                    <div class="detail-header">
                        <h3><i class="fas fa-sticky-note"></i> Special Requests</h3>
                    </div>
                    <div class="detail-item">
                        <div class="detail-value"><?php echo htmlspecialchars($booking['special_requests']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="detail-card">
                    <div class="action-buttons">
                        <a href="edit-booking.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn btn-edit">
                            <i class="fas fa-edit"></i> Edit Booking
                        </a>
                        <?php if ($booking['payment_status'] === 'paid'): ?>
                        <a href="booking-receipt.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn btn-receipt">
                            <i class="fas fa-download"></i> Download Receipt
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

