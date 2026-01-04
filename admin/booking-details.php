<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$bookingId = $_GET['id'] ?? 0;

// Get booking details with room image and QR code info
$stmt = $db->prepare("
    SELECT b.*,
           u.name as guest_name,
           u.email,
           u.phone,
           r.room_name,
           r.room_number,
           r.room_type,
           r.room_image,
           r.description as room_description,
           qr.qr_id,
           qr.qr_code_data,
           qr.status as qr_status,
           qr.generated_at as qr_generated_at
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN qr_codes qr ON b.booking_id = qr.booking_id
    WHERE b.booking_id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: reservations.php');
    exit;
}

// Generate QR code URL if available
$qrCodeUrl = null;
if ($booking['qr_code_data']) {
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($booking['qr_code_data']);
}

$pageTitle = "Booking Details";
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
        .booking-details-container {
            padding: 20px 0;
        }

        .detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(160, 82, 45, 0.08);
            padding: 30px;
            margin-bottom: 25px;
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s ease;
        }

        .detail-card:hover {
            box-shadow: 0 4px 20px rgba(160, 82, 45, 0.12);
            transform: translateY(-2px);
        }

        .detail-card h3 {
            color: var(--primary-color);
            font-family: var(--font-heading);
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f5f5;
            font-size: 22px;
        }

        .detail-card h3 i {
            margin-right: 10px;
            color: var(--accent-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-item {
            padding: 18px;
            background: linear-gradient(135deg, rgba(160, 82, 45, 0.03) 0%, rgba(212, 165, 116, 0.03) 100%);
            border-radius: 10px;
            border: 1px solid rgba(160, 82, 45, 0.08);
            transition: all 0.2s ease;
        }

        .info-item:hover {
            background: linear-gradient(135deg, rgba(160, 82, 45, 0.05) 0%, rgba(212, 165, 116, 0.05) 100%);
            border-color: rgba(160, 82, 45, 0.15);
        }

        .info-label {
            font-size: 11px;
            color: var(--secondary-color);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-family: var(--font-modern);
        }

        .info-value {
            font-size: 16px;
            color: var(--gray-800);
            font-weight: 600;
            font-family: var(--font-body);
            line-height: 1.5;
        }

        .info-value.large {
            font-size: 24px;
            color: var(--primary-color);
        }

        .room-image-container {
            position: relative;
            width: 100%;
            height: 350px;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(160, 82, 45, 0.15);
        }

        .room-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .room-image-container:hover img {
            transform: scale(1.05);
        }

        .room-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .room-image-placeholder i {
            font-size: 80px;
            color: white;
            opacity: 0.5;
        }

        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: var(--font-modern);
        }

        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-checked_in { background: #d1ecf1; color: #0c5460; }
        .status-checked_out { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-unpaid { background: #f8d7da; color: #721c24; }

        .qr-code-section {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(40, 167, 69, 0.02) 100%);
            border-radius: 12px;
            border: 2px dashed #28a745;
        }

        .qr-code-section img {
            width: 250px;
            height: 250px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .btn-custom {
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-family: var(--font-modern);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            font-size: 13px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(160, 82, 45, 0.2);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(160, 82, 45, 0.3);
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--primary-color) 100%);
        }

        .btn-secondary-custom {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-secondary-custom:hover {
            background: var(--primary-color);
            color: white;
        }

        .special-requests-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--info);
            margin-top: 15px;
        }

        .special-requests-box p {
            margin: 0;
            color: var(--gray-700);
            line-height: 1.8;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .timeline-icon i {
            color: white;
            font-size: 16px;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-label {
            font-size: 12px;
            color: var(--gray-600);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .timeline-value {
            font-size: 15px;
            color: var(--gray-800);
            font-weight: 600;
            margin-top: 3px;
        }

        .summary-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(160, 82, 45, 0.2);
            margin-bottom: 25px;
        }

        .summary-card h4 {
            color: white;
            font-family: var(--font-heading);
            margin-bottom: 20px;
            font-size: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .summary-item:last-child {
            border-bottom: none;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }

        .summary-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .summary-value {
            font-size: 16px;
            font-weight: 700;
        }

        .summary-value.total {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="content-wrapper">
            <div class="container-fluid booking-details-container">
                <!-- Page Header -->
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h1><i class="fas fa-file-invoice"></i> Booking Details</h1>
                    <a href="reservations.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-8">
                        <!-- Room Image -->
                        <?php if (!empty($booking['room_image'])): ?>
                            <div class="room-image-container">
                                <img src="../<?php echo htmlspecialchars($booking['room_image']); ?>"
                                     alt="<?php echo htmlspecialchars($booking['room_name']); ?>"
                                     onerror="this.parentElement.innerHTML='<div class=\'room-image-placeholder\'><i class=\'fas fa-bed\'></i></div>'">
                            </div>
                        <?php else: ?>
                            <div class="room-image-container">
                                <div class="room-image-placeholder">
                                    <i class="fas fa-bed"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Booking Information -->
                        <div class="detail-card">
                            <h3><i class="fas fa-calendar-check"></i> Booking Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Booking Reference</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Booking Status</div>
                                    <div><span class="status-badge status-<?php echo $booking['booking_status']; ?>"><?php echo str_replace('_', ' ', $booking['booking_status']); ?></span></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Check-in Date</div>
                                    <div class="info-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Check-out Date</div>
                                    <div class="info-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Total Nights</div>
                                    <div class="info-value"><?php echo $booking['total_nights']; ?> Night<?php echo $booking['total_nights'] > 1 ? 's' : ''; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Created At</div>
                                    <div class="info-value"><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Guest Information -->
                        <div class="detail-card">
                            <h3><i class="fas fa-user"></i> Guest Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Guest Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email Address</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['email']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Phone Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['phone']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Number of Guests</div>
                                    <div class="info-value"><?php echo $booking['num_adults']; ?> Adult<?php echo $booking['num_adults'] > 1 ? 's' : ''; ?>, <?php echo $booking['num_children']; ?> Child<?php echo $booking['num_children'] != 1 ? 'ren' : ''; ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Room Information -->
                        <div class="detail-card">
                            <h3><i class="fas fa-door-open"></i> Room Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Room Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Room Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['room_number']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Room Type</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['room_type']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Price per Night</div>
                                    <div class="info-value">$<?php echo number_format($booking['price_per_night'], 2); ?></div>
                                </div>
                            </div>
                            <?php if (!empty($booking['room_description'])): ?>
                                <div class="special-requests-box" style="border-left-color: var(--primary-color);">
                                    <strong style="color: var(--primary-color); display: block; margin-bottom: 10px;">
                                        <i class="fas fa-info-circle"></i> Room Description
                                    </strong>
                                    <p><?php echo nl2br(htmlspecialchars($booking['room_description'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Special Requests -->
                        <?php if (!empty($booking['special_requests'])): ?>
                            <div class="detail-card">
                                <h3><i class="fas fa-comment-dots"></i> Special Requests</h3>
                                <div class="special-requests-box">
                                    <p><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column -->
                    <div class="col-lg-4">
                        <!-- Payment Summary -->
                        <div class="summary-card">
                            <h4><i class="fas fa-receipt"></i> Payment Summary</h4>
                            <div class="summary-item">
                                <span class="summary-label">Price per Night</span>
                                <span class="summary-value">$<?php echo number_format($booking['price_per_night'], 2); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Number of Nights</span>
                                <span class="summary-value"><?php echo $booking['total_nights']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total Amount</span>
                                <span class="summary-value total">$<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div class="detail-card">
                            <h3><i class="fas fa-credit-card"></i> Payment Status</h3>
                            <div class="text-center" style="padding: 20px 0;">
                                <span class="status-badge status-<?php echo $booking['payment_status']; ?>" style="font-size: 14px; padding: 15px 30px;">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- QR Code Section -->
                        <?php if ($qrCodeUrl && $booking['booking_status'] === 'confirmed'): ?>
                            <div class="detail-card">
                                <h3><i class="fas fa-qrcode"></i> Digital Room Key</h3>
                                <div class="qr-code-section">
                                    <p style="color: #28a745; font-weight: 600; margin-bottom: 10px;">
                                        <i class="fas fa-check-circle"></i> QR Code Active
                                    </p>
                                    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code">
                                    <p style="font-size: 12px; color: #666; margin-top: 15px;">
                                        Generated: <?php echo $booking['qr_generated_at'] ? date('M d, Y H:i', strtotime($booking['qr_generated_at'])) : 'N/A'; ?>
                                    </p>
                                    <p style="font-size: 12px; color: #666;">
                                        Status: <strong style="color: #28a745;"><?php echo ucfirst($booking['qr_status'] ?? 'Active'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Actions -->
                        <div class="detail-card">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                            <div class="action-buttons" style="flex-direction: column;">
                                <a href="edit-booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-custom btn-primary-custom w-100">
                                    <i class="fas fa-edit"></i> Edit Booking
                                </a>
                                <a href="reservations.php" class="btn btn-custom btn-secondary-custom w-100">
                                    <i class="fas fa-list"></i> View All Bookings
                                </a>
                                <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                                    <button class="btn btn-custom w-100" style="background: #dc3545; color: white;" onclick="if(confirm('Are you sure you want to cancel this booking?')) { window.location.href='cancel-booking.php?id=<?php echo $booking['booking_id']; ?>'; }">
                                        <i class="fas fa-times-circle"></i> Cancel Booking
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>