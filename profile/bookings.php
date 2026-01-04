<?php
/**
 * My Bookings Page
 * Display user's bookings with QR codes
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$db = getDB();
$bookings = [];
$error = '';

try {
    // Get user's bookings with room image and QR code data
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_name, r.room_type, r.price_per_night, r.room_image,
               qr.qr_id, qr.qr_code_data, qr.qr_image_path, qr.status as qr_status
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN qr_codes qr ON b.booking_id = qr.booking_id
        WHERE b.user_id = ?
        ORDER BY b.check_in_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Bookings error: " . $e->getMessage());
    $error = "Error loading bookings";
}

// Handle QR code download
if (isset($_GET['download_qr']) && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);

    try {
        $stmt = $db->prepare("
            SELECT qr_image_path FROM qr_codes
            WHERE booking_id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$booking_id]);
        $qr = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($qr && file_exists($qr['qr_image_path'])) {
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="booking_' . $booking_id . '_qr.png"');
            readfile($qr['qr_image_path']);
            exit;
        }
    } catch (Exception $e) {
        error_log("QR download error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Luviora Hotel</title>
    <link rel="shortcut icon" type="image/x-icon" href="../images/favicon.png" />
    <!-- Bootstrap core CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!--Default CSS-->
    <link href="../css/default.css" rel="stylesheet" type="text/css" />
    <!--Custom CSS-->
    <link href="../css/style.css" rel="stylesheet" type="text/css" />
    <!--Plugin CSS-->
    <link href="../css/plugin.css" rel="stylesheet" type="text/css" />
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css" />
    <!-- Modern Header Theme -->
    <link href="../css/modern-header.css" rel="stylesheet" type="text/css" />
    <!-- Footer Coral Theme -->
    <link href="../css/footer-coral.css" rel="stylesheet" type="text/css" />
<!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@400;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-brown: #a0522d;
            --secondary-brown: #8b6f47;
            --accent-coral: #C38370;
            --light-bg: #FAF9F6;
            --dark-text: #2E2E2E;
            --white: #FFFFFF;
        }

        * {
            font-family: 'Lato', sans-serif;
        }

        h1, h2, h3, h4, h5, .booking-ref {
            font-family: 'Playfair Display', serif;
        }

        .bookings-container {
            padding: 60px 0 80px 0;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);
            color: var(--white);
            padding: 40px 35px;
            margin-bottom: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(160, 82, 45, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header-content {
            flex: 1;
            min-width: 250px;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            font-size: 38px;
        }

        .page-header p {
            font-family: 'Lato', sans-serif;
            font-size: 15px;
            opacity: 0.95;
            font-weight: 500;
            color: var(--white);
            margin: 0;
        }

        .booking-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(160, 82, 45, 0.08);
            margin-bottom: 28px;
            overflow: hidden;
            transition: all 0.3s ease;
            border-left: 5px solid var(--accent-coral);
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.12);
        }

        .booking-header {
            background: linear-gradient(135deg, rgba(160, 82, 45, 0.08) 0%, rgba(195, 131, 112, 0.08) 100%);
            padding: 22px 25px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-ref {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-brown);
            margin: 0;
        }

        .booking-status {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Poppins', sans-serif;
        }

        .status-confirmed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-checked_in { background: #d1ecf1; color: #0c5460; }
        .status-checked_out { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .booking-body {
            padding: 28px;
        }

        .room-info {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
            align-items: flex-start;
        }

        .room-image {
            width: 160px;
            height: 110px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }

        .room-details h5 {
            margin: 0 0 12px 0;
            color: var(--primary-brown);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 18px;
        }

        .room-details p {
            color: var(--secondary-brown);
            font-family: 'Lato', sans-serif;
            font-size: 14px;
            margin: 0;
            font-weight: 500;
        }

        .booking-dates {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
            padding: 20px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .date-item {
            flex: 1;
        }

        .date-label {
            font-size: 11px;
            color: var(--secondary-brown);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 8px;
        }

        .date-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark-text);
            font-family: 'Lato', sans-serif;
        }

        .booking-footer {
            border-top: 2px solid #f0f0f0;
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(160, 82, 45, 0.03) 0%, rgba(195, 131, 112, 0.03) 100%);
            flex-wrap: wrap;
            gap: 15px;
        }

        .total-amount {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary-brown);
            font-family: 'Playfair Display', serif;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(160, 82, 45, 0.1);
        }

        .empty-state i {
            font-size: 100px;
            color: #ddd;
            margin-bottom: 25px;
            display: block;
        }

        .empty-state h3 {
            color: var(--dark-text);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--secondary-brown);
            font-family: 'Lato', sans-serif;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .btn-light {
            background: var(--white);
            color: var(--primary-brown);
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 28px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);;
        }

        .btn-light:hover {
            background: var(--accent-coral);
            color: var(--white);
            border-color: var(--accent-coral);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);
            border-color: transparent;
            color: var(--white);
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 28px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(160, 82, 45, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(160, 82, 45, 0.3);
            background: linear-gradient(135deg, var(--accent-coral) 0%, var(--primary-brown) 100%);
        }

        .btn-primary.btn-lg {
            padding: 14px 32px;
            font-size: 14px;
        }

        .btn-outline-secondary {
            color: var(--primary-brown);
            border-color: var(--primary-brown);
            background: transparent;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 28px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-outline-secondary:hover {
            background: var(--primary-brown);
            border-color: var(--primary-brown);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .btn-outline-secondary.btn-lg {
            padding: 14px 32px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/profile_header.php'; ?>
    <!-- header Ends -->

    <div class="bookings-container" style="margin-top: 100px;">
        <div class="container">
            <div class="page-header">
                <div class="page-header-content">
                    <h1>
                        <i class="fas fa-calendar-alt"></i> My Bookings
                    </h1>
                    <p>View and manage your hotel reservations</p>
                </div>
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="booking-card">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Bookings Yet</h3>
                        <p>You haven't made any reservations yet.</p>
                        <a href="../availability.php" class="btn btn-primary btn-lg mt-3">
                            <i class="fas fa-search"></i> Find Available Rooms
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="booking-ref">
                                    <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                </div>
                                <span class="booking-status status-<?php echo $booking['booking_status']; ?>">
                                    <?php echo str_replace('_', ' ', $booking['booking_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="booking-body">
                            <div class="room-info">
                                <?php if (isset($booking['room_image']) && $booking['room_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($booking['room_image']); ?>" alt="Room" class="room-image">
                                <?php else: ?>
                                    <div class="room-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-bed fa-2x" style="color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="room-details">
                                    <h5><?php echo htmlspecialchars($booking['room_name'] ?? 'Room TBA'); ?></h5>
                                    <p class="mb-1">
                                        <i class="fas fa-door-open"></i> 
                                        <?php echo htmlspecialchars($booking['room_number'] ?? 'To be assigned'); ?> - 
                                        <?php echo htmlspecialchars($booking['room_type'] ?? 'N/A'); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-users"></i> 
                                        <?php echo $booking['num_adults']; ?> Adults
                                        <?php if ($booking['num_children'] > 0): ?>
                                            , <?php echo $booking['num_children']; ?> Children
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="booking-dates">
                                <div class="date-item">
                                    <div class="date-label">Check-in</div>
                                    <div class="date-value">
                                        <i class="fas fa-calendar-check"></i> 
                                        <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?>
                                    </div>
                                </div>
                                <div class="date-item">
                                    <div class="date-label">Check-out</div>
                                    <div class="date-value">
                                        <i class="fas fa-calendar-times"></i> 
                                        <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                    </div>
                                </div>
                                <div class="date-item">
                                    <div class="date-label">Total Nights</div>
                                    <div class="date-value">
                                        <i class="fas fa-moon"></i> 
                                        <?php echo $booking['total_nights']; ?> Night<?php echo $booking['total_nights'] > 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($booking['special_requests']): ?>
                                <div class="alert alert-info">
                                    <strong><i class="fas fa-info-circle"></i> Special Requests:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="booking-footer">
                            <div class="total-amount">
                                $<?php echo number_format($booking['total_amount'], 2); ?>
                            </div>
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <a href="booking-details.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm" style="background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%); color: white; border: none; font-weight: 700; font-family: 'Poppins', sans-serif; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; border-radius: 6px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(160, 82, 45, 0.15);">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                    <button class="btn btn-sm" onclick="viewQRCode(<?php echo $booking['booking_id']; ?>)" style="background: #4caf50; color: white; border: none; font-weight: 700; font-family: 'Poppins', sans-serif; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; border-radius: 6px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15);">
                                        <i class="fas fa-qrcode"></i> View QR Code
                                    </button>
                                <?php endif; ?>
                                <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn btn-sm" onclick="openModifyModal(<?php echo htmlspecialchars(json_encode($booking)); ?>)" style="background: #3498db; color: white; border: none; font-weight: 700; font-family: 'Poppins', sans-serif; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; border-radius: 6px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(52, 152, 219, 0.15);">
                                        <i class="fas fa-edit"></i> Modify
                                    </button>
                                    <button class="btn btn-sm" onclick="openCancelModal(<?php echo htmlspecialchars(json_encode($booking)); ?>)" style="background: #e74c3c; color: white; border: none; font-weight: 700; font-family: 'Poppins', sans-serif; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; border-radius: 6px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(231, 76, 60, 0.15);">
                                        <i class="fas fa-times-circle"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="text-center mt-5 mb-4">
                <a href="../index.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/profile_footer.php'; ?>

    <!-- Modify Booking Modal -->
    <div class="modal fade" id="modifyBookingModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%); color: white;">
                    <h5 class="modal-title" style="font-family: 'Playfair Display', serif; font-weight: 700;">
                        <i class="fas fa-edit"></i> Modify Booking
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="modifyBookingContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white;">
                    <h5 class="modal-title" style="font-family: 'Playfair Display', serif; font-weight: 700;">
                        <i class="fas fa-times-circle"></i> Cancel Booking
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="cancelBookingContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugin.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/custom-nav.js"></script>
    <script src="../js/auth.js"></script>
    <script src="booking-actions.js"></script>
</body>
</html>

