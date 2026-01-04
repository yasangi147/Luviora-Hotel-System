<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$pageTitle = 'Booking Details';
$db = getDB();

$bookingId = $_GET['id'] ?? null;
$booking = null;

if ($bookingId) {
    try {
        $stmt = $db->prepare("
            SELECT b.*, 
                   u.name as guest_name, u.email, u.phone,
                   r.room_number, r.room_name, r.room_type, r.floor
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Booking Details Error: " . $e->getMessage());
    }
}

if (!$booking) {
    header('Location: reservations-all.php');
    exit;
}

$nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Clark Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/clark-style.css">
    <link rel="stylesheet" href="common-design-system.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content-wrapper">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-file-alt"></i> Booking Details</h4>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="data-table-card mb-4">
                        <div class="table-header">
                            <h3><i class="fas fa-info-circle"></i> Booking Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Booking Reference:</strong><br>
                                    <span class="text-primary" style="font-size: 20px; font-weight: 700;">
                                        <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Booking Status:</strong><br>
                                    <span class="badge badge-<?php echo $booking['booking_status']; ?>" style="font-size: 14px;">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-calendar-check"></i> Check-in Date:</strong><br>
                                    <?php echo date('F d, Y', strtotime($booking['check_in_date'])); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-calendar-times"></i> Check-out Date:</strong><br>
                                    <?php echo date('F d, Y', strtotime($booking['check_out_date'])); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-moon"></i> Number of Nights:</strong><br>
                                    <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-users"></i> Number of Guests:</strong><br>
                                    <?php echo $booking['number_of_guests'] ?? 1; ?> guest<?php echo ($booking['number_of_guests'] ?? 1) > 1 ? 's' : ''; ?>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <strong><i class="fas fa-dollar-sign"></i> Total Amount:</strong><br>
                                    <span style="font-size: 24px; font-weight: 700; color: #28a745;">
                                        $<?php echo number_format($booking['total_amount'], 2); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-credit-card"></i> Payment Status:</strong><br>
                                    <span class="badge badge-<?php echo $booking['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-calendar-plus"></i> Booked On:</strong><br>
                                    <?php echo date('F d, Y h:i A', strtotime($booking['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="data-table-card">
                        <div class="table-header">
                            <h3><i class="fas fa-hotel"></i> Room Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Room Number:</strong><br>
                                    <span style="font-size: 20px; font-weight: 700;">
                                        <?php echo htmlspecialchars($booking['room_number']); ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Room Name:</strong><br>
                                    <?php echo htmlspecialchars($booking['room_name']); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Room Type:</strong><br>
                                    <?php echo htmlspecialchars($booking['room_type']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Floor:</strong><br>
                                    Floor <?php echo $booking['floor']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="data-table-card mb-4">
                        <div class="table-header">
                            <h3><i class="fas fa-user"></i> Guest Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($booking['guest_name']); ?>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Email:</strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($booking['email']); ?>">
                                    <?php echo htmlspecialchars($booking['email']); ?>
                                </a>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Phone:</strong><br>
                                <a href="tel:<?php echo htmlspecialchars($booking['phone']); ?>">
                                    <?php echo htmlspecialchars($booking['phone']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="data-table-card">
                        <div class="table-header">
                            <h3><i class="fas fa-cog"></i> Actions</h3>
                        </div>
                        <div class="p-4">
                            <?php if ($booking['booking_status'] === 'confirmed'): ?>
                            <form method="POST" action="manual-checkin.php" class="mb-2">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-sign-in-alt"></i> Check In
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($booking['booking_status'] === 'checked_in'): ?>
                            <form method="POST" action="manual-checkout.php" class="mb-2">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-sign-out-alt"></i> Check Out
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <button onclick="window.print()" class="btn btn-info w-100 mb-2">
                                <i class="fas fa-print"></i> Print Details
                            </button>
                            
                            <a href="reservations-all.php" class="btn btn-secondary w-100">
                                <i class="fas fa-list"></i> All Bookings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

