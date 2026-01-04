<?php
/**
 * Guest Details Page
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$guestId = $_GET['id'] ?? 0;

// Get guest details
$stmt = $db->prepare("
    SELECT u.*, 
           COUNT(DISTINCT b.booking_id) as total_bookings,
           COALESCE(SUM(CASE WHEN b.booking_status IN ('confirmed', 'checked_in', 'checked_out') THEN b.total_amount ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.user_id
    WHERE u.user_id = ? AND u.role = 'guest'
    GROUP BY u.user_id
");
$stmt->execute([$guestId]);
$guest = $stmt->fetch();

if (!$guest) {
    header('Location: guests.php');
    exit;
}

// Get guest bookings
$stmt = $db->prepare("
    SELECT b.*, r.room_name, r.room_number, r.room_type
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.user_id = ?
    ORDER BY b.check_in_date DESC
    LIMIT 10
");
$stmt->execute([$guestId]);
$bookings = $stmt->fetchAll();

$pageTitle = "Guest Details";
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
        .guest-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .guest-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .info-value {
            color: var(--gray-600);
        }
        
        .stat-box {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
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
                <div class="page-header">
                    <h1><i class="fas fa-user"></i> Guest Details</h1>
                    <a href="guests.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Guests
                    </a>
                </div>
                
                <!-- Guest Header -->
                <div class="guest-header">
                    <div class="guest-avatar">
                        <?php echo strtoupper(substr($guest['name'], 0, 1)); ?>
                    </div>
                    <h2><?php echo htmlspecialchars($guest['name']); ?></h2>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($guest['email']); ?>
                    </p>
                </div>
                
                <!-- Guest Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h4 style="margin-bottom: 20px;"><i class="fas fa-user-circle"></i> Personal Information</h4>
                            <div class="info-row">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($guest['name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($guest['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($guest['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status:</span>
                                <span class="info-value">
                                    <span class="badge badge-<?php echo $guest['status']; ?>">
                                        <?php echo ucfirst($guest['status']); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $guest['total_bookings']; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number">$<?php echo number_format($guest['total_spent'], 0); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>
                </div>
                
                <!-- Booking History -->
                <div class="info-card">
                    <h4 style="margin-bottom: 20px;"><i class="fas fa-history"></i> Recent Bookings</h4>
                    <?php if (empty($bookings)): ?>
                        <p style="text-align: center; color: var(--gray-600); padding: 20px;">
                            No bookings found for this guest
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Booking Ref</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($booking['room_name'] ?? 'TBA'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $booking['booking_status']; ?>">
                                                <?php echo str_replace('_', ' ', ucfirst($booking['booking_status'])); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

