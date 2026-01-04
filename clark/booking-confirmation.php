<?php
/**
 * Booking Confirmation - Display QR code and booking summary
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$booking_id = intval($_GET['booking_id'] ?? 0);

if (!$booking_id) {
    header('Location: manual-checkin.php');
    exit;
}

// Get booking details
$stmt = $db->prepare("
    SELECT b.*, u.name as guest_name, u.email, r.room_name, r.room_number, r.room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: manual-checkin.php');
    exit;
}

// Get extra services
$stmt = $db->prepare("
    SELECT bs.*, es.service_name, es.icon
    FROM booking_services bs
    JOIN extra_services es ON bs.service_id = es.service_id
    WHERE bs.booking_id = ?
");
$stmt->execute([$booking_id]);
$services = $stmt->fetchAll();

// Generate QR code data with room key and booking summary
$qr_data = json_encode([
    'booking_id' => $booking_id,
    'booking_reference' => $booking['booking_reference'],
    'guest_name' => $booking['guest_name'],
    'guest_email' => $booking['email'],
    'room_id' => $booking['room_id'],
    'room_number' => $booking['room_number'],
    'room_name' => $booking['room_name'],
    'room_type' => $booking['room_type'],
    'check_in_date' => $booking['check_in_date'],
    'check_out_date' => $booking['check_out_date'],
    'total_nights' => $booking['total_nights'],
    'total_amount' => $booking['total_amount'],
    'booking_status' => $booking['booking_status'],
    'payment_status' => $booking['payment_status'],
    'generated_at' => date('Y-m-d H:i:s'),
    'expiry_date' => date('Y-m-d', strtotime($booking['check_out_date'] . ' +1 day'))
]);

// Generate QR code using QR Server API
$qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($qr_data);

// Save QR code to database if not exists
$stmt = $db->prepare("SELECT qr_id FROM qr_codes WHERE booking_id = ?");
$stmt->execute([$booking_id]);
$existing_qr = $stmt->fetch();

if (!$existing_qr) {
    $qr_hash = hash('sha256', $qr_data);
    $expiry_time = date('Y-m-d H:i:s', strtotime($booking['check_out_date'] . ' 11:00 AM'));

    $stmt = $db->prepare("
        INSERT INTO qr_codes (booking_id, qr_code_data, qr_code_hash, qr_image_path, expiry_time, status, generated_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$booking_id, $qr_data, $qr_hash, $qr_code_url, $expiry_time]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Clark Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/clark-style.css">
    <link rel="stylesheet" href="common-design-system.css">

    <style>
        .confirmation-container {
            max-width: 1000px;
            margin: 40px auto;
        }

        .confirmation-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .confirmation-header {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .confirmation-body {
            padding: 40px;
        }

        .qr-section {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .qr-code-image {
            max-width: 300px;
            margin: 20px auto;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .booking-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .detail-card h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .detail-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .detail-value {
            color: var(--gray-600);
        }

        .services-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .btn-group-custom {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-custom {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.3);
            color: white;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/topbar.php'; ?>

            <div class="content-wrapper">
                <div class="confirmation-container">
                    <div class="confirmation-card">
                        <!-- Header -->
                        <div class="confirmation-header">
                            <h2><i class="fas fa-check-circle"></i> Booking Confirmed!</h2>
                            <p style="margin: 10px 0 0 0; opacity: 0.9;">Your manual check-in has been successfully processed</p>
                        </div>

                        <!-- Body -->
                        <div class="confirmation-body">
                            <!-- QR Code Section -->
                            <div class="qr-section">
                                <h5 style="margin-bottom: 20px; font-weight: 600;">
                                    <i class="fas fa-qrcode"></i> Room Access QR Code
                                </h5>
                                <p style="color: #666; margin-bottom: 15px;">Scan this QR code to access your room</p>
                                <div class="qr-code-image" id="qrCodeContainer">
                                    <img id="qrCodeImage" src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="QR Code" style="width: 100%; height: auto;">
                                </div>
                                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                                    <button class="btn btn-primary" onclick="downloadQRCode()">
                                        <i class="fas fa-download"></i> Download QR Code
                                    </button>
                                    <button class="btn btn-secondary" onclick="printQRCode()">
                                        <i class="fas fa-print"></i> Print QR Code
                                    </button>
                                </div>
                                <p style="color: #999; font-size: 12px; margin-top: 15px;">
                                    <i class="fas fa-info-circle"></i> QR Code expires on <?php echo date('M d, Y', strtotime($booking['check_out_date'] . ' +1 day')); ?>
                                </p>
                            </div>

                            <!-- Booking Details -->
                            <div class="booking-details">
                                <!-- Guest & Room Info -->
                                <div class="detail-card">
                                    <h6><i class="fas fa-user"></i> Guest Information</h6>
                                    <div class="detail-row">
                                        <span class="detail-label">Name:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Email:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Booking Ref:</span>
                                        <span class="detail-value"><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></span>
                                    </div>
                                </div>

                                <!-- Room & Dates -->
                                <div class="detail-card">
                                    <h6><i class="fas fa-door-open"></i> Room & Dates</h6>
                                    <div class="detail-row">
                                        <span class="detail-label">Room:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($booking['room_number'] . ' - ' . $booking['room_name']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Check-in:</span>
                                        <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Check-out:</span>
                                        <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Nights:</span>
                                        <span class="detail-value"><?php echo $booking['total_nights']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Extra Services -->
                            <?php if (!empty($services)): ?>
                            <div class="services-list">
                                <h6 style="margin-bottom: 15px; font-weight: 600;">
                                    <i class="fas fa-concierge-bell"></i> Extra Services
                                </h6>
                                <?php foreach ($services as $service): ?>
                                <div class="service-item">
                                    <span><i class="fas <?php echo $service['icon']; ?>"></i> <?php echo htmlspecialchars($service['service_name']); ?></span>
                                    <span style="font-weight: 600;">$<?php echo number_format($service['total_price'], 2); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Price Summary -->
                            <div class="detail-card" style="background: linear-gradient(135deg, #f8f9fa 0%, #e8eef5 100%);">
                                <h6 style="color: var(--primary-color); margin-bottom: 15px; font-weight: 600;">
                                    <i class="fas fa-receipt"></i> Price Summary
                                </h6>
                                <div class="detail-row">
                                    <span class="detail-label">Subtotal:</span>
                                    <span class="detail-value">$<?php echo number_format($booking['subtotal'], 2); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Tax (10%):</span>
                                    <span class="detail-value">$<?php echo number_format($booking['tax_amount'], 2); ?></span>
                                </div>
                                <div class="detail-row" style="padding-top: 15px; border-top: 2px solid #e0e0e0; margin-top: 15px; font-size: 16px;">
                                    <span class="detail-label">Total Amount:</span>
                                    <span style="color: var(--primary-color); font-weight: 700;">$<?php echo number_format($booking['total_amount'], 2); ?></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="btn-group-custom" style="margin-top: 30px;">
                                <a href="manual-checkin.php" class="btn btn-custom btn-secondary-custom">
                                    <i class="fas fa-arrow-left"></i> Back to Check-in
                                </a>
                                <a href="index.php" class="btn btn-custom btn-primary-custom">
                                    <i class="fas fa-home"></i> Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Download QR Code
        function downloadQRCode() {
            const qrImage = document.getElementById('qrCodeImage');
            const link = document.createElement('a');
            link.href = qrImage.src;
            link.download = 'QR_Code_<?php echo htmlspecialchars($booking['booking_reference']); ?>.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Print QR Code
        function printQRCode() {
            const qrContainer = document.getElementById('qrCodeContainer');
            const printWindow = window.open('', '', 'height=400,width=400');
            printWindow.document.write('<html><head><title>QR Code - <?php echo htmlspecialchars($booking['booking_reference']); ?></title>');
            printWindow.document.write('<style>body { text-align: center; padding: 20px; font-family: Arial, sans-serif; }');
            printWindow.document.write('h2 { color: #a0522d; margin-bottom: 20px; }');
            printWindow.document.write('img { max-width: 400px; border: 2px solid #a0522d; padding: 10px; }');
            printWindow.document.write('p { color: #666; margin-top: 20px; }');
            printWindow.document.write('</style></head><body>');
            printWindow.document.write('<h2>Room Access QR Code</h2>');
            printWindow.document.write('<p><strong>Booking Reference:</strong> <?php echo htmlspecialchars($booking['booking_reference']); ?></p>');
            printWindow.document.write('<p><strong>Guest:</strong> <?php echo htmlspecialchars($booking['guest_name']); ?></p>');
            printWindow.document.write(qrContainer.innerHTML);
            printWindow.document.write('<p style="margin-top: 30px; font-size: 12px; color: #999;">');
            printWindow.document.write('Expires: <?php echo date('M d, Y', strtotime($booking['check_out_date'] . ' +1 day')); ?>');
            printWindow.document.write('</p></body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>

