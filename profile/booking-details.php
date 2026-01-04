<?php
/**
 * Booking Details Page
 * Display detailed information about a specific booking
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$db = getDB();
$booking = null;
$error = '';

try {
    // Get booking details
    $stmt = $db->prepare("
        SELECT b.*, r.room_number, r.room_name, r.room_type, r.price_per_night, r.room_image, r.description,
               qr.qr_id, qr.qr_code_data, qr.qr_image_path, qr.status as qr_status
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN qr_codes qr ON b.booking_id = qr.booking_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    // Parse QR code data if available
    $qrData = null;
    $qrCodeUrl = null;
    if ($booking && $booking['qr_code_data']) {
        $qrData = json_decode($booking['qr_code_data'], true);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($booking['qr_code_data']);
    }

    if (!$booking) {
        $error = "Booking not found";
    }
} catch (Exception $e) {
    error_log("Booking details error: " . $e->getMessage());
    $error = "Error loading booking details";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Luviora Hotel</title>
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
    <!-- Loading States CSS -->
    <link href="../css/loading-states.css" rel="stylesheet" type="text/css" />
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

        h1, h2, h3, h4, h5 {
            font-family: 'Playfair Display', serif;
        }

        .details-container {
            padding: 60px 0 80px 0;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);
            color: var(--white);
            padding: 50px 0;
            margin-bottom: 50px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.2);
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .page-header p {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 500;
        }

        .detail-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(160, 82, 45, 0.1);
            padding: 35px;
            margin-bottom: 28px;
            border-left: 5px solid var(--accent-coral);
            transition: all 0.3s ease;
        }

        .detail-card:hover {
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.15);
        }

        .detail-card h3 {
            color: var(--primary-brown);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 18px;
            border-bottom: 2px solid #f0f0f0;
            font-size: 24px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
            }
        }

        .detail-item {
            padding: 18px;
            background: linear-gradient(135deg, rgba(160, 82, 45, 0.03) 0%, rgba(195, 131, 112, 0.03) 100%);
            border-radius: 10px;
            border: 1px solid rgba(160, 82, 45, 0.08);
        }

        .detail-label {
            font-size: 11px;
            color: var(--secondary-brown);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }

        .detail-value {
            font-size: 16px;
            color: var(--dark-text);
            font-weight: 600;
            font-family: 'Lato', sans-serif;
            line-height: 1.6;
        }

        .room-image-section {
            margin-bottom: 35px;
            border-radius: 12px;
            overflow: hidden;
        }

        .room-image-section img {
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.2);
            transition: transform 0.3s ease;
        }

        .room-image-section img:hover {
            transform: scale(1.02);
        }

        .status-badge {
            display: inline-block;
            padding: 12px 26px;
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

        .action-buttons {
            display: flex;
            gap: 18px;
            margin-top: 35px;
            flex-wrap: wrap;
        }

        .btn-custom {
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--accent-coral) 100%);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(160, 82, 45, 0.2);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(160, 82, 45, 0.3);
            background: linear-gradient(135deg, var(--accent-coral) 0%, var(--primary-brown) 100%);
        }

        .btn-secondary-custom {
            background: var(--white);
            color: var(--primary-brown);
            border: 2px solid var(--primary-brown);
        }

        .btn-secondary-custom:hover {
            background: var(--primary-brown);
            color: var(--white);
        }

        .btn-danger-custom {
            background: #dc3545;
            color: var(--white);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .btn-danger-custom:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.3);
        }

        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/profile_header.php'; ?>
    <!-- header Ends -->

    <div class="details-container" style="margin-top: 100px;">
        <div class="container">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-info-circle" style="color: white; padding-left: 20px;"></i> <span style="color: white; padding-left: 20px;"> Booking Details</span></h1>
                    </div>
                    <a href="bookings.php" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> <span style="color: black; margin-right: 20px;">Back to Bookings </span>
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($booking): ?>
                <!-- Room Image -->
                <?php if (isset($booking['room_image']) && $booking['room_image']): ?>
                    <div class="room-image-section">
                        <img src="../<?php echo htmlspecialchars($booking['room_image']); ?>" alt="<?php echo htmlspecialchars($booking['room_name']); ?>">
                    </div>
                <?php endif; ?>

                <!-- QR Code Section -->
                <?php if ($qrCodeUrl): ?>
                    <div class="detail-card" style="text-align: center; border-left: 5px solid #28a745;">
                        <h3><i class="fas fa-qrcode"></i> 🔑 YOUR DIGITAL ROOM KEY & BOOKING SUMMARY</h3>
                        <p style="color: #666; margin-bottom: 20px; font-size: 15px;">Scan this QR code at check-in to access your room and view booking details</p>

                        <div style="display: inline-block; padding: 25px; background: white; border: 3px solid var(--accent-coral); border-radius: 12px; margin: 25px 0; box-shadow: 0 4px 15px rgba(160, 82, 45, 0.15);">
                            <img id="qrCodeImage" src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code for Booking <?php echo htmlspecialchars($booking['booking_reference']); ?>" style="width: 300px; height: 300px; display: block;">
                        </div>

                        <!-- QR Details -->
                        <div style="background: rgba(160, 82, 45, 0.05); border-radius: 10px; padding: 25px; margin: 25px auto; max-width: 600px; text-align: left;">
                            <p style="margin: 8px 0; font-size: 15px; color: #2E2E2E;"><strong>📋 Booking Reference:</strong> <?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                            <p style="margin: 8px 0; font-size: 15px; color: #2E2E2E;"><strong>🏨 Room:</strong> <?php echo htmlspecialchars($booking['room_name'] ?? 'TBA'); ?> (<?php echo htmlspecialchars($booking['room_type'] ?? 'N/A'); ?>)</p>
                            <p style="margin: 8px 0; font-size: 15px; color: #2E2E2E;"><strong>✅ Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?> at 2:00 PM</p>
                            <p style="margin: 8px 0; font-size: 15px; color: #2E2E2E;"><strong>⏰ Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?> at 11:00 AM</p>
                            <p style="margin: 8px 0; font-size: 15px; color: #2E2E2E;"><strong>👥 Guests:</strong> <?php echo $booking['num_adults']; ?> Adult<?php echo $booking['num_adults'] > 1 ? 's' : ''; ?>, <?php echo $booking['num_children']; ?> Child<?php echo $booking['num_children'] !== 1 ? 'ren' : ''; ?></p>
                            <p style="margin: 8px 0; font-size: 15px; color: #2E2E2E;"><strong>💰 Total Paid:</strong> $<?php echo number_format($booking['total_amount'] ?? 0, 2); ?></p>
                        </div>

                        <!-- Action Buttons -->
                        <div style="margin-top: 25px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                            <button class="btn btn-custom btn-primary-custom" onclick="downloadQRCode()" style="min-width: 180px;">
                                <i class="fas fa-download"></i> Download QR Code
                            </button>
                            <button class="btn btn-custom btn-primary-custom" onclick="printQRCode()" style="min-width: 180px;">
                                <i class="fas fa-print"></i> Print QR Code
                            </button>
                            <a href="qr-code.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-custom btn-secondary-custom" style="min-width: 180px; text-decoration: none; display: inline-block; line-height: 1.5;">
                                <i class="fas fa-expand"></i> View Full Size
                            </a>
                        </div>

                        <!-- Instructions -->
                        <div style="background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; border-radius: 8px; padding: 20px; margin-top: 30px; text-align: left;">
                            <h4 style="color: #28a745; font-size: 14px; text-transform: uppercase; font-weight: 700; margin-bottom: 15px; font-family: 'Poppins', sans-serif;">
                                <i class="fas fa-info-circle"></i> HOW TO USE YOUR QR CODE:
                            </h4>
                            <ol style="margin: 0; padding-left: 20px; line-height: 2;">
                                <li style="font-size: 15px; color: #2E2E2E;"><strong>Save</strong> this QR code to your mobile device (download or screenshot)</li>
                                <li style="font-size: 15px; color: #2E2E2E;"><strong>Arrive</strong> at hotel entrance at your scheduled time</li>
                                <li style="font-size: 15px; color: #2E2E2E;"><strong>Scan</strong> QR code at the check-in kiosk or show to staff</li>
                                <li style="font-size: 15px; color: #2E2E2E;"><strong>Access</strong> your room - it will be activated automatically</li>
                                <li style="font-size: 15px; color: #2E2E2E;"><strong>Enjoy</strong> your stay - no need to visit the front desk!</li>
                            </ol>
                            <p style="font-style: italic; font-size: 13px; color: #666; margin-top: 15px; margin-bottom: 0;">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i> QR code will be active from <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?> at 2:00 PM
                            </p>
                        </div>

                        <p style="font-size: 13px; color: #999; margin-top: 20px;">
                            <i class="fas fa-shield-alt"></i> QR Code Status: <span style="color: #28a745; font-weight: 700;"><?php echo ucfirst($booking['qr_status'] ?? 'Active'); ?></span>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Booking Information -->
                <div class="detail-card">
                    <h3><i class="fas fa-calendar-alt"></i> Booking Information</h3>
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Booking Reference</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div><span class="status-badge status-<?php echo $booking['booking_status']; ?>"><?php echo str_replace('_', ' ', $booking['booking_status']); ?></span></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Check-in Date</div>
                            <div class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Check-out Date</div>
                            <div class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Room Information -->
                <div class="detail-card">
                    <h3><i class="fas fa-door-open"></i> Room Information</h3>
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Room Name</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['room_name'] ?? 'TBA'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Room Number</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['room_number'] ?? 'To be assigned'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Room Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['room_type'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Price per Night</div>
                            <div class="detail-value">$<?php echo number_format($booking['price_per_night'] ?? 0, 2); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Guest Information -->
                <div class="detail-card">
                    <h3><i class="fas fa-users"></i> Guest Information</h3>
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Adults</div>
                            <div class="detail-value"><?php echo $booking['num_adults']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Children</div>
                            <div class="detail-value"><?php echo $booking['num_children']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value" style="color: var(--primary-brown); font-size: 20px;">$<?php echo number_format($booking['total_amount'] ?? 0, 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value"><?php echo ucfirst($booking['payment_status'] ?? 'Pending'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="bookings.php" class="btn btn-secondary-custom">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                    <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                        <button class="btn btn-danger-custom" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                            <i class="fas fa-times"></i> Cancel Booking
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/profile_footer.php'; ?>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugin.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/custom-nav.js"></script>
    <script src="../js/auth.js"></script>
    <!-- Loading States JavaScript -->
    <script src="../js/loading-states.js"></script>

    <script>
        // Download QR Code
        function downloadQRCode() {
            const qrImage = document.getElementById('qrCodeImage');
            const bookingRef = '<?php echo htmlspecialchars($booking['booking_reference']); ?>';

            // Create a temporary canvas to convert the image
            fetch(qrImage.src)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `Luviora-QR-${bookingRef}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    // Show success message
                    alert('✅ QR Code downloaded successfully!\n\nSave this to your mobile device for check-in.');
                })
                .catch(error => {
                    console.error('Download error:', error);
                    alert('❌ Failed to download QR code. Please try right-clicking the QR code and selecting "Save Image As..."');
                });
        }

        // Print QR Code
        function printQRCode() {
            const qrImage = document.getElementById('qrCodeImage');
            const bookingRef = '<?php echo htmlspecialchars($booking['booking_reference']); ?>';
            const roomName = '<?php echo htmlspecialchars($booking['room_name'] ?? 'TBA'); ?>';
            const checkIn = '<?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?>';
            const checkOut = '<?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>';

            // Create print window
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QR Code - ${bookingRef}</title>
                    <style>
                        body {
                            font-family: 'Arial', sans-serif;
                            text-align: center;
                            padding: 40px;
                            margin: 0;
                        }
                        h1 {
                            color: #a0522d;
                            font-size: 28px;
                            margin-bottom: 10px;
                        }
                        h2 {
                            color: #666;
                            font-size: 18px;
                            font-weight: normal;
                            margin-bottom: 30px;
                        }
                        .qr-container {
                            border: 3px solid #C38370;
                            padding: 30px;
                            display: inline-block;
                            border-radius: 12px;
                            margin: 20px 0;
                        }
                        .qr-container img {
                            width: 400px;
                            height: 400px;
                        }
                        .details {
                            background: #f9f9f9;
                            padding: 25px;
                            border-radius: 8px;
                            margin: 30px auto;
                            max-width: 500px;
                            text-align: left;
                        }
                        .details p {
                            margin: 10px 0;
                            font-size: 16px;
                            color: #333;
                        }
                        .details strong {
                            color: #a0522d;
                        }
                        .instructions {
                            background: #e8f5e9;
                            padding: 20px;
                            border-radius: 8px;
                            margin: 30px auto;
                            max-width: 500px;
                            text-align: left;
                        }
                        .instructions h3 {
                            color: #28a745;
                            font-size: 16px;
                            margin-top: 0;
                        }
                        .instructions ol {
                            margin: 10px 0;
                            padding-left: 20px;
                        }
                        .instructions li {
                            margin: 8px 0;
                            font-size: 14px;
                        }
                        @media print {
                            body {
                                padding: 20px;
                            }
                        }
                    </style>
                </head>
                <body>
                    <h1>🏨 Luviora Hotel</h1>
                    <h2>Digital Room Key & Booking Summary</h2>

                    <div class="qr-container">
                        <img src="${qrImage.src}" alt="QR Code">
                    </div>

                    <div class="details">
                        <p><strong>📋 Booking Reference:</strong> ${bookingRef}</p>
                        <p><strong>🏨 Room:</strong> ${roomName}</p>
                        <p><strong>✅ Check-in:</strong> ${checkIn} at 2:00 PM</p>
                        <p><strong>⏰ Check-out:</strong> ${checkOut} at 11:00 AM</p>
                    </div>

                    <div class="instructions">
                        <h3>📱 How to Use:</h3>
                        <ol>
                            <li>Save this QR code to your mobile device</li>
                            <li>Arrive at hotel entrance at your scheduled time</li>
                            <li>Scan QR code at check-in kiosk or show to staff</li>
                            <li>Your room will be activated automatically</li>
                            <li>Enjoy your stay!</li>
                        </ol>
                    </div>

                    <p style="color: #999; font-size: 12px; margin-top: 30px;">
                        Luviora Hotel | info@luviorahotel.com | +94 082 1234 567
                    </p>
                </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();

            // Wait for image to load then print
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                fetch('../api/booking.php?action=cancel', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({booking_id: bookingId})
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('✅ ' + result.message);
                        setTimeout(() => window.location.href = 'bookings.php', 1500);
                    } else {
                        alert('❌ ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ An error occurred while cancelling the booking');
                });
            }
        }
    </script>
</body>
</html>

