<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$bookingId = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE bookings SET booking_status = ?, payment_status = ?, special_requests = ? WHERE booking_id = ?");
    $stmt->execute([$_POST['booking_status'], $_POST['payment_status'], $_POST['special_requests'], $bookingId]);
    header('Location: booking-details.php?id=' . $bookingId);
    exit;
}

$stmt = $db->prepare("SELECT b.*, r.room_number, r.room_type, u.name as guest_name
                      FROM bookings b
                      LEFT JOIN rooms r ON b.room_id = r.room_id
                      LEFT JOIN users u ON b.user_id = u.user_id
                      WHERE b.booking_id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: reservations.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking | Luviora Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        .form-section {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f5f5;
        }

        .section-header i {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 12px;
        }

        .section-header h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 16px;
        }

        .required-badge {
            background: #dc3545;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(160, 82, 45, 0.15);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-card h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6c757d;
            font-size: 14px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }

        .action-buttons {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            position: sticky;
            top: 90px;
        }

        .btn-save {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 10px;
        }

        .btn-save:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(160, 82, 45, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
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
                    <div>
                        <h1><i class="fas fa-edit"></i> Edit Booking</h1>
                        <p>Update booking status and payment information</p>
                    </div>
                    <a href="booking-details.php?id=<?php echo $bookingId; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Details
                    </a>
                </div>

                <form method="POST">
                    <div class="row">
                        <!-- Main Form Section -->
                        <div class="col-lg-8">
                            <!-- Booking Information Card -->
                            <div class="info-card">
                                <h6><i class="fas fa-info-circle"></i> Booking Information</h6>
                                <div class="info-item">
                                    <span class="info-label">Booking ID:</span>
                                    <span class="info-value">#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Guest Name:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($booking['guest_name'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Room:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($booking['room_number'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($booking['room_type'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Check-in:</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Check-out:</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                                </div>
                            </div>

                            <!-- Status Section -->
                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-tasks"></i>
                                    <h5>Booking Status</h5>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-check"></i>
                                            Booking Status
                                            <span class="required-badge">REQUIRED</span>
                                        </label>
                                        <select name="booking_status" class="form-select" required>
                                            <option value="pending" <?php echo $booking['booking_status'] === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                            <option value="confirmed" <?php echo $booking['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>✅ Confirmed</option>
                                            <option value="checked_in" <?php echo $booking['booking_status'] === 'checked_in' ? 'selected' : ''; ?>>🔑 Checked In</option>
                                            <option value="checked_out" <?php echo $booking['booking_status'] === 'checked_out' ? 'selected' : ''; ?>>🚪 Checked Out</option>
                                            <option value="cancelled" <?php echo $booking['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                                        </select>
                                        <small class="text-muted">Current booking status</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-credit-card"></i>
                                            Payment Status
                                            <span class="required-badge">REQUIRED</span>
                                        </label>
                                        <select name="payment_status" class="form-select" required>
                                            <option value="unpaid" <?php echo $booking['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>❌ Unpaid</option>
                                           
                                            <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>✅ Paid</option>
                                           
                                        </select>
                                        <small class="text-muted">Payment status</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Special Requests Section -->
                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-comment-dots"></i>
                                    <h5>Special Requests & Notes</h5>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-sticky-note"></i>
                                        Special Requests
                                    </label>
                                    <textarea name="special_requests" class="form-control" rows="5" placeholder="Enter any special requests or notes for this booking..."><?php echo htmlspecialchars($booking['special_requests'] ?? ''); ?></textarea>
                                    <small class="text-muted">Add any special requests or internal notes</small>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Actions -->
                        <div class="col-lg-4">
                            <div class="action-buttons">
                                <h6 class="mb-3" style="color: var(--primary-color); font-weight: 600;">
                                    <i class="fas fa-cog"></i> Actions
                                </h6>
                                <button type="submit" class="btn-save">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <a href="booking-details.php?id=<?php echo $bookingId; ?>" class="btn-cancel">
                                    <i class="fas fa-times"></i> Cancel
                                </a>

                                <div class="mt-4 pt-4" style="border-top: 2px solid #f0f0f0;">
                                    <h6 style="color: #6c757d; font-size: 14px; margin-bottom: 15px;">
                                        <i class="fas fa-info-circle"></i> Quick Info
                                    </h6>
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Total Amount</small>
                                        <strong style="color: var(--primary-color); font-size: 18px;">
                                            $<?php echo number_format($booking['total_amount'], 2); ?>
                                        </strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Booking Date</small>
                                        <strong><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>