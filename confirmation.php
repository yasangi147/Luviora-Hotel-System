<?php
/**
 * Confirmation Page - Booking Confirmed
 * Displays booking confirmation with QR code for room key
 */

session_start();
require_once 'config/database.php';

// Check if booking was just completed
if (!isset($_SESSION['booking_id']) || !isset($_SESSION['booking_reference'])) {
    // Redirect to home if no booking found
    header('Location: index.html');
    exit;
}

$bookingId = $_SESSION['booking_id'];
$bookingReference = $_SESSION['booking_reference'];

try {
    $db = getDB();

    // Get booking details with user and room information
    $stmt = $db->prepare("
        SELECT
            b.*,
            u.name as user_name,
            u.email as user_email,
            u.phone as user_phone,
            r.room_name,
            r.room_type,
            r.room_image,
            r.price_per_night
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = ? AND b.booking_reference = ?
    ");

    $stmt->execute([$bookingId, $bookingReference]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found");
    }

    // Extract booking details
    $firstName = explode(' ', $booking['user_name'])[0];
    $lastName = explode(' ', $booking['user_name'], 2)[1] ?? '';
    $email = $booking['user_email'];
    $phone = $booking['user_phone'];
    $roomName = $booking['room_name'];
    $roomType = $booking['room_type'];
    $roomImage = $booking['room_image'];
    $checkIn = $booking['check_in_date'];
    $checkOut = $booking['check_out_date'];
    $nights = $booking['total_nights'];
    $adults = $booking['num_adults'];
    $children = $booking['num_children'];
    $pricePerNight = $booking['price_per_night'];
    $totalAmount = $booking['total_amount'];
    $specialRequests = $booking['special_requests'];

    // Calculate subtotal and tax
    $subtotal = $pricePerNight * $nights;
    $taxAmount = round($subtotal * 0.18, 2); // 18% tax

    // Format dates for display
    $checkInFormatted = date('M d, Y', strtotime($checkIn));
    $checkOutFormatted = date('M d, Y', strtotime($checkOut));
    $checkInFull = date('l, M d, Y', strtotime($checkIn));
    $checkOutFull = date('l, M d, Y', strtotime($checkOut));

    // QR Code activation time (check-in date at 2:00 PM)
    $activationTime = date('M d, Y', strtotime($checkIn)) . ' at 2:00 PM';
    $validUntil = date('M d, Y', strtotime($checkOut)) . ' at 11:00 AM';

    // Generate QR Code data with Booking Summary and Room Key
    $qrDataArray = [
        'type' => 'LUVIORA_BOOKING',
        'booking_ref' => $bookingReference,
        'room_name' => $roomName,
        'room_type' => $roomType,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guest_name' => $firstName . ' ' . $lastName,
        'email' => $email,
        'phone' => $phone,
        'adults' => $adults,
        'children' => $children,
        'nights' => $nights,
        'total_amount' => $totalAmount
    ];

    // Convert to JSON for QR code
    $qrData = json_encode($qrDataArray);

    // Create viewer URL - when QR is scanned, it will show a nice formatted page
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $viewerUrl = "{$protocol}://{$host}/qr-viewer.php?data=" . urlencode($qrData);

    
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);

    // Booking link
    $bookingLink = "https://www.luviorahotel.com/qr/bookingdetail/#{$bookingReference}";

    // Prepare data for JavaScript
    $bookingDataJson = json_encode([
        'bookingId' => $bookingReference,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'roomName' => $roomName,
        'roomType' => $roomType,
        'checkIn' => $checkInFormatted,
        'checkOut' => $checkOutFormatted,
        'nights' => $nights,
        'adults' => $adults,
        'children' => $children,
        'pricePerNight' => $pricePerNight,
        'subtotal' => $subtotal,
        'tax' => $taxAmount,
        'total' => $totalAmount,
        'specialRequests' => $specialRequests
    ]);

    // Save QR Code to database
    try {
        // Generate QR code hash for validation
        $qrCodeHash = hash('sha256', $qrData);

        // Set expiry time (checkout date at 11:00 AM)
        $expiryTime = date('Y-m-d H:i:s', strtotime($checkOut . ' 11:00 AM'));

        // Save QR code to database
        $qrStmt = $db->prepare("
            INSERT INTO qr_codes (booking_id, qr_code_data, qr_code_hash, qr_image_path, expiry_time, status)
            VALUES (?, ?, ?, ?, ?, 'active')
        ");

        // Store the QR code data as JSON
        $qrCodePath = 'qr_codes/' . $bookingReference . '_' . time() . '.png';

        $qrStmt->execute([
            $bookingId,
            $qrData,
            $qrCodeHash,
            $qrCodePath,
            $expiryTime
        ]);

        error_log("QR Code saved to database for booking: " . $bookingReference);
    } catch (Exception $e) {
        error_log("Error saving QR code to database: " . $e->getMessage());
        // Continue even if QR code save fails - booking is still valid
    }

    // Send confirmation email with QR code automatically
    $emailSent = false;
    $emailError = '';

    try {
        require_once 'config/email.php';

        // Prepare booking data for email
        $bookingDataForEmail = [
            'booking_reference' => $bookingReference,
            'guest_name' => $firstName . ' ' . $lastName,
            'guest_email' => $email,
            'room_name' => $roomName,
            'room_type' => $roomType,
            'room_number' => $booking['room_number'] ?? 'TBA', // Room number to be assigned
            'check_in' => $checkInFormatted,
            'check_out' => $checkOutFormatted,
            'nights' => $nights,
            'num_adults' => $adults,
            'num_children' => $children,
            'total_amount' => number_format($totalAmount, 2),
            'qr_code_url' => $qrCodeUrl
        ];

        // Send booking confirmation email with QR code
        $emailSent = sendBookingConfirmationEmail($bookingDataForEmail, $qrCodeUrl);

        if ($emailSent) {
            error_log("Confirmation email sent successfully to: " . $email);
        } else {
            error_log("Failed to send confirmation email to: " . $email);
            $emailError = "Email sending failed - please use the 'Send to Email' button to resend";
        }

    } catch (Exception $e) {
        error_log("Error sending confirmation email: " . $e->getMessage());
        $emailError = $e->getMessage();
        // Continue even if email fails - booking is still valid
    }

    // Clear booking session data (keep user logged in if applicable)
    unset($_SESSION['booking_id']);
    unset($_SESSION['booking_reference']);
    unset($_SESSION['selected_rooms']);
    unset($_SESSION['reservationData']);

} catch (Exception $e) {
    error_log("Confirmation page error: " . $e->getMessage());
    // Redirect to home on error
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zxx">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Booking Confirmed | Luviora Hotel</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!--Default CSS-->
    <link href="css/default.css" rel="stylesheet" type="text/css" />
    <!--Custom CSS-->
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <!--Plugin CSS-->
    <link href="css/plugin.css" rel="stylesheet" type="text/css" />
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css" />
    <!-- Rounded Corners CSS -->
    <link href="css/rounded-corners.css" rel="stylesheet" type="text/css" />
    <!-- Coral Theme CSS -->

    <!-- Modern Header Theme -->
    <link href="css/modern-header.css" rel="stylesheet" type="text/css" />
    <!-- Footer Coral Theme -->
    <link href="css/footer-coral.css" rel="stylesheet" type="text/css" />
    <!-- News and Reviews Coral Theme -->
    <link href="css/news-reviews-coral.css" rel="stylesheet" type="text/css" />
    <!-- Elegant Color Scheme -->
    <link href="css/elegant-colors.css" rel="stylesheet" type="text/css" />
    <!-- Luxury About Styles -->
    <link href="css/aboutus-luxury.css" rel="stylesheet" type="text/css" />
    <!-- Elegant Rooms Styles -->
    <link href="css/elegant-rooms.css" rel="stylesheet" type="text/css" />

    <!-- Modern Reviews & Gallery Styles -->
    <link href="css/modern-reviews-gallery.css" rel="stylesheet" type="text/css" />
    
    <style>
      :root {
        --primary-terracotta: #a0522d;
        --secondary-sienna: #8b4513;
        --accent-gold: #d4a574;
        --text-dark-brown: #6b5744;
        --text-warm-brown: #8b6f47;
        --bg-cream: #faf8f5;
        --bg-white: #ffffff;
        --bg-beige: #f5f1eb;
        --success-green: #28a745;
      }

      body {
        background-color: var(--bg-cream);
        font-family: 'Poppins', sans-serif;
      }

      #back-to-top a {
        display: block;
        width: 40px;
        height: 40px;
        background: #a0522d;
        position: relative;
      }

      h1, h2 {
        font-weight: 700;
        color: #6b5744;
        font-family: 'Playfair Display', serif;
        margin: 0 0 15px;
        line-height: 1.4;
        text-transform: uppercase;
      }

      .nav-btn .btn-orange {
        background: #C38370 !important; 
        color: #FAF9F6 !important;
        border: none !important;
        padding: 12px 28px;
        border-radius: 4px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: all 0.3s ease;
        margin-left: 15px;
        box-shadow: 0 2px 10px rgba(195, 131, 112, 0.2);
      }

      /* Hover effect */
      .nav-btn .btn-orange:hover {
        background: #FFFFFF !important;
        color: #000000 !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
      }

      .table-item .form-btn .btn-orange {
        background-color:  #C38370 !important;
        color: #FAF9F6 !important;
        padding: 10px 25px;
        transition: all 0.3s ease;
      }

      .table-item .form-btn .btn.btn-orange:hover {
        background-color:  #a55d42 !important;
        color:  #ffffff !important;
        border-color:  #d19a7a00 !important;
        box-shadow:  0 8px 15px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
      } 

      /* Override template hover */
      .btn-orange:not(.navbar .btn-orange):not(.nav .btn-orange):hover {
        background: #a55d42 !important;
        color: #ffffff !important;
        border-color: #d19a7a00 !important;
        box-shadow:  0 8px 15px rgba(0, 0, 0, 0.15);
        transition: 0.3s ease;
      }

      .footer-logo{
        padding-bottom: 90px;
        padding-top: 10px;
        margin-top: -10px;
      }

      .section-btn {
        text-align: center;
        padding-top: 40px;
      }

      section.breadcrumb-outer {
        background: url(images/gallery/gallery27.jpg) no-repeat;
        background-size: cover;
        background-position: center;
        position: relative;
        text-align: center;
        padding: 260px 0 150px;
      }

      .award-content{
        background-color: #a55d42;
      }

      /* ========================================
         RESERVATION STEPS INDICATOR STYLES
      ======================================== */
      .reservation-steps {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 60px 0 50px;
        position: relative;
      }

      .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
      }

      .step-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        color: #d4a574;
        background-color: rgba(212, 165, 116, 0.3);
        border: 3px solid rgba(212, 165, 116, 0.5);
        transition: all 0.3s ease;
        margin-bottom: 15px;
      }

      .step-item.active .step-circle {
        background-color: #a0522d;
        color: #ffffff;
        border-color: #a0522d;
        box-shadow: 0 4px 15px rgba(160, 82, 45, 0.3);
      }

      .step-item.completed .step-circle {
        background-color: #d4a574;
        border: 3px solid rgba(212, 165, 116, 0.5);
        color: #ffffff;
      }

      .step-label {
        font-size: 14px;
        font-weight: 600;
        color: #8b6f47;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .step-item.active .step-label {
        color: #a0522d;
        font-weight: 700;
      }

      .step-connector {
        height: 3px;
        width: 150px;
        background-color: rgba(212, 165, 116, 0.3);
        position: relative;
        margin: 0 -20px;
        top: -45px;
        z-index: 1;
      }

      .step-item.active + .step-connector,
      .step-item.completed + .step-connector {
        background-color: #d4a574;
      }

      /* Success Banner */
      .success-banner {
        background: linear-gradient(135deg, #28a745 0%, #a0522d 100%);
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        margin-bottom: 40px;
        box-shadow: 0 10px 40px rgba(107, 87, 68, 0.1);
        animation: slideDown 0.6s ease;
      }

      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .success-icon-circle {
        width: 80px;
        height: 80px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
      }

      .success-icon-circle i {
        font-size: 40px;
        color: #28a745;
      }

      .success-banner h2 {
        color: white;
        font-size: 32px;
        margin-bottom: 15px;
      }

      .success-banner p {
        color: white;
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 15px;
      }

      .booking-id-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        padding: 12px 25px;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 700;
        color: white;
      }

      /* Main Container */
      .confirmation-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
      }

      /* QR Code Card */
      .qr-code-card {
        background: white;
        border: 2px solid var(--accent-gold);
        border-radius: 12px;
        padding: 40px;
        margin-bottom: 40px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(160, 82, 45, 0.15);
      }

      .qr-code-card h3 {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        color: var(--text-dark-brown);
        margin-bottom: 30px;
      }

      .qr-code-display {
        display: inline-block;
        padding: 20px;
        background: white;
        border: 2px solid var(--accent-gold);
        border-radius: 8px;
        margin-bottom: 20px;
      }

      .qr-code-display img {
        width: 300px;
        height: 300px;
        display: block;
      }

      .qr-details {
        background: var(--bg-beige);
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
      }

      .qr-details p {
        font-size: 15px;
        color: var(--text-dark-brown);
        line-height: 2;
        margin: 0;
      }

      .qr-details strong {
        font-weight: 700;
      }

      .qr-action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        margin: 25px 0;
      }

      .qr-btn {
        background: var(--primary-terracotta);
        color: white;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
      }

      .qr-btn:hover {
        background: var(--secondary-sienna);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(160, 82, 45, 0.3);
      }

      .qr-instructions {
        background: rgba(160, 82, 45, 0.1);
        border-left: 4px solid var(--primary-terracotta);
        border-radius: 8px;
        padding: 20px;
        margin-top: 25px;
        text-align: left;
      }

      .qr-instructions h4 {
        color: var(--primary-terracotta);
        font-size: 14px;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 15px;
      }

      .qr-instructions ol {
        margin: 0;
        padding-left: 20px;
      }

      .qr-instructions li {
        font-size: 15px;
        color: var(--text-dark-brown);
        line-height: 2;
      }

      .qr-instructions .note {
        font-style: italic;
        font-size: 13px;
        color: var(--text-warm-brown);
        margin-top: 10px;
      }

      /* Two Column Layout */
      .booking-details-grid {
        display: grid;
        grid-template-columns: 60% 40%;
        gap: 30px;
        margin-bottom: 40px;
      }

      /* Cards */
      .detail-card {
        background: white;
        border: 1px solid var(--accent-gold);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(107, 87, 68, 0.08);
        box-sizing: border-box;
        overflow: hidden;
      }

      .detail-card h3 {
        font-size: 24px;
        color: var(--text-dark-brown);
        border-bottom: 2px solid var(--bg-beige);
        padding-bottom: 15px;
        margin-bottom: 25px;
      }

      .room-display {
        margin-bottom: 25px;
      }

      .room-display img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 15px;
      }

      .room-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
      }

      .room-title h4 {
        font-size: 18px;
        color: var(--text-dark-brown);
        margin: 0;
      }

      .room-stars {
        color: var(--accent-gold);
      }

      .room-amenities {
        font-size: 13px;
        color: var(--text-warm-brown);
      }

      /* Booking Details Grid */
      .booking-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        width: 100%;
        box-sizing: border-box;
      }

      .info-item {
        padding: 15px;
        background: var(--bg-beige);
        border-radius: 8px;
        box-sizing: border-box;
        min-width: 0;
        overflow: hidden;
      }

      .info-item:nth-child(even) {
        background: white;
        border: 1px solid var(--bg-beige);
      }

      .info-item label {
        display: block;
        font-size: 12px;
        text-transform: uppercase;
        color: var(--text-warm-brown);
        margin-bottom: 5px;
        font-weight: 600;
        letter-spacing: 0.5px;
      }

      .info-item .value {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-dark-brown);
        word-wrap: break-word;
        overflow-wrap: break-word;
      }

      /* Guest Info Table */
      .guest-info-table {
        width: 100%;
      }

      .guest-info-table tr {
        border-bottom: 1px solid var(--bg-beige);
      }

      .guest-info-table td {
        padding: 12px 0;
        font-size: 14px;
      }

      .guest-info-table td:first-child {
        color: var(--text-warm-brown);
        font-weight: 600;
        width: 40%;
      }

      .guest-info-table td:last-child {
        color: var(--text-dark-brown);
      }

      /* Price Breakdown */
      .price-breakdown {
        background: white;
        border: 1px solid var(--accent-gold);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
      }

      .price-breakdown h3 {
        font-size: 24px;
        margin-bottom: 20px;
        color: var(--text-dark-brown);
      }

      .price-line {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-size: 15px;
        color: var(--text-dark-brown);
      }

      .price-line.indent {
        padding-left: 20px;
        font-size: 14px;
        color: var(--text-warm-brown);
      }

      .price-line.indent i {
        color: var(--accent-gold);
        margin-right: 8px;
      }

      .price-line.subtotal {
        border-top: 1px dashed var(--accent-gold);
        margin-top: 15px;
        padding-top: 15px;
        font-weight: 600;
      }

      .price-line.total {
        background: var(--bg-beige);
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
        font-size: 24px;
        font-weight: 700;
        color: var(--primary-terracotta);
        border-top: 2px solid var(--primary-terracotta);
      }

      .payment-info {
        background: var(--bg-beige);
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
      }

      .payment-info p {
        font-size: 13px;
        color: var(--text-dark-brown);
        margin: 5px 0;
      }

      .status-badge {
        display: inline-block;
        background: #28a745;
        color: white;
        padding: 5px 15px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 12px;
        margin-top: 5px;
      }

      /* Services List */
      .services-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
      }

      .service-item {
        padding: 10px;
        background: var(--bg-beige);
        border-radius: 6px;
        font-size: 14px;
        color: var(--text-dark-brown);
      }

      .service-item i {
        color: var(--accent-gold);
        margin-right: 8px;
      }

      /* Preferences Card */
      .preferences-section {
        margin-top: 20px;
      }

      .pref-group {
        margin-bottom: 20px;
      }

      .pref-group h5 {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-dark-brown);
        margin-bottom: 10px;
      }

      .pref-group ul {
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .pref-group li {
        padding: 5px 0 5px 20px;
        position: relative;
        font-size: 14px;
        color: var(--text-dark-brown);
      }

      .pref-group li::before {
        content: '•';
        position: absolute;
        left: 0;
        color: var(--primary-terracotta);
        font-weight: 700;
      }

      .special-request-box {
        background: var(--bg-beige);
        padding: 15px;
        border-radius: 8px;
        font-style: italic;
        font-size: 14px;
        color: var(--text-dark-brown);
      }

      /* Info Boxes Row */
      .info-boxes-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin: 40px 0;
      }

      .info-box {
        background: var(--bg-beige);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
      }

      .info-box h4 {
        font-size: 14px;
        text-transform: uppercase;
        color: var(--primary-terracotta);
        margin-bottom: 15px;
        font-weight: 700;
      }

      .info-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .info-box li {
        font-size: 13px;
        color: var(--text-dark-brown);
        padding: 5px 0;
      }

      /* Mobile App Banner */
      .app-banner {
        background: linear-gradient(135deg, #a0522d 0%, #8b4513 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        text-align: center;
        margin: 40px 0;
      }

      .app-banner h3 {
        color: white;
        font-size: 28px;
        margin-bottom: 15px;
      }

      .app-banner p {
        font-size: 16px;
        margin-bottom: 25px;
      }

      .app-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
      }

      .app-btn {
        background: white;
        color: var(--primary-terracotta);
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
      }

      .app-btn:hover {
        background: var(--bg-beige);
        color: var(--primary-terracotta);
        transform: translateY(-2px);
      }

      /* Action Buttons */
      .action-buttons-row {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        margin: 30px 0;
      }

      .action-btn {
        background: var(--primary-terracotta);
        color: white;
        padding: 15px 35px;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
      }

      .action-btn i {
        color: white;
      }

      .action-btn:hover {
        background: var(--secondary-sienna);
        color: white;
        transform: translateY(-2px);
      }

      .action-btn:hover i {
        color: white;
      }

      .action-btn.secondary {
        background: white;
        color: var(--primary-terracotta);
        border: 2px solid var(--primary-terracotta);
      }

      .action-btn.secondary:hover {
        background: var(--primary-terracotta);
        color: white;
      }

      /* Booking Link Box */
      .booking-link-box {
        background: white;
        border: 1px dashed var(--accent-gold);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        margin: 30px 0;
      }

      .booking-link-box p {
        margin-bottom: 10px;
        color: var(--text-dark-brown);
      }

      .booking-link {
        color: var(--primary-terracotta);
        font-weight: 700;
        word-break: break-all;
      }

      .copy-btn {
        margin-top: 10px;
        padding: 8px 20px;
        background: var(--primary-terracotta);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
      }

      /* Timeline */
      .timeline-card {
        background: white;
        border-left: 4px solid var(--primary-terracotta);
        border-radius: 8px;
        padding: 25px;
        margin: 30px 0;
      }

      .timeline-card h3 {
        font-size: 24px;
        margin-bottom: 25px;
      }

      .timeline-item {
        margin-bottom: 20px;
        padding-left: 30px;
        position: relative;
      }

      .timeline-item h5 {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-dark-brown);
        margin-bottom: 10px;
      }

      .timeline-item ul {
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .timeline-item li {
        padding: 5px 0;
        font-size: 14px;
        color: var(--text-dark-brown);
      }

      .timeline-item.completed li::before {
        content: '✓';
        margin-right: 8px;
        color: #28a745;
        font-weight: 700;
      }

      .timeline-item.upcoming li::before {
        content: '○';
        margin-right: 8px;
        color: var(--primary-terracotta);
      }

      /* Help Card */
      .help-card {
        background: var(--bg-beige);
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        margin: 30px 0;
      }

      .help-card h3 {
        font-size: 24px;
        margin-bottom: 15px;
      }

      .help-card p {
        font-size: 16px;
        color: var(--text-dark-brown);
        margin: 10px 0;
      }

      .help-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 20px;
      }

      /* Responsive */
      @media (max-width: 991px) {
        .booking-details-grid {
          grid-template-columns: 1fr;
        }

        .info-boxes-row {
          grid-template-columns: 1fr;
        }

        .step-connector {
          width: 80px;
        }
      }

      @media (max-width: 767px) {
        .success-banner {
          padding: 30px 20px;
        }

        .success-banner h2 {
          font-size: 24px;
        }

        .qr-code-card {
          padding: 25px 15px;
        }

        .qr-code-card h3 {
          font-size: 24px;
        }

        .qr-code-display img {
          width: 250px;
          height: 250px;
        }

        .booking-info-grid {
          grid-template-columns: 1fr;
        }

        .services-grid {
          grid-template-columns: 1fr;
        }

        .qr-action-buttons,
        .action-buttons-row,
        .app-buttons,
        .help-buttons {
          flex-direction: column;
        }

        .qr-btn,
        .action-btn,
        .app-btn {
          width: 100%;
        }

        .step-circle {
          width: 50px;
          height: 50px;
          font-size: 20px;
        }

        .step-connector {
          width: 50px;
          top: -40px;
        }

        .step-label {
          font-size: 12px;
        }
      }

      @media (max-width: 480px) {
        .step-circle {
          width: 40px;
          height: 40px;
          font-size: 16px;
          margin-bottom: 10px;
        }

        .step-connector {
          width: 30px;
          height: 2px;
          top: -30px;
          margin: 0 -10px;
        }

        .step-label {
          font-size: 10px;
        }

        .qr-code-display img {
          width: 200px;
          height: 200px;
        }
      }
    </style>
  
  </head>
  <body>
    <!-- Preloader -->
    <div id="preloader">
      <div id="status"></div>
    </div>
    <!-- Preloader Ends -->

    <header class="main_header_area">
      <div class="header-content">
        <div class="container">
          <div class="links links-left">
            <ul>
              <li>
                <a href="#"><i class="fa fa-envelope" aria-hidden="true"></i> info@luviorahotel.com</a>
              </li>
              <li>
                <a href="#"><i class="fa fa-phone" aria-hidden="true"></i> +94 082 1234 567</a>
              </li>
            </ul>
          </div>
          <?php include 'includes/auth_header.php'; ?>

            </ul>
          </div>
        </div>
      </div>

      <!-- Navigation Bar -->
      <div class="header_menu">
        <div class="container">
          <nav class="navbar navbar-default">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
              <a class="navbar-brand" href="index.html">
                <img alt="logo" src="images/luvioralogoblack.png" class="logo-black" style="width: 200px; height: 50px;"/>
                <img alt="logo1" src="images/luvioralogoblack.png" class="logo-black" style="width: 200px; height: 50px;"/>
              </a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
              <ul class="nav navbar-nav" id="responsive-menu">
                <li class="dropdown submenu ">
                  <a href="index.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Home</a>
                </li>

                <li class="submenu dropdown">
                  <a href="aboutus.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >About Us</a>
                </li>

                <li class="submenu dropdown">
                  <a href="roomlist-1.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Rooms</a>
                </li>

                <li class="submenu dropdown">
                  <a href="testimonial.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Testimonials</a>
                </li>

                <li class="submenu dropdown">
                  <a href="blog-full.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Blog</a>
                </li>

                <li class="submenu dropdown">
                  <a href="gallery.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Gallery</a>
                </li>

                <li class="submenu dropdown">
                  <a href="service.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Services</a>
                </li>

                <li class="submenu dropdown  active">
                  <a href="contact.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Contact Us</a>
                </li>

              </ul>
              <div class="nav-btn">
                <a href="availability.php" class="btn btn-orange" style="margin-right: 20px;">Book Now</a>
              </div>
            </div>
            <!-- /.navbar-collapse -->
            <div id="slicknav-mobile"></div>
          </nav>
        </div>
        <!-- /.container-fluid -->
      </div>
      <!-- Navigation Bar Ends -->
    </header>
    <!-- header Ends -->

    <!-- breadcrumbs starts -->
    <section class="breadcrumb-outer">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Booking Confirmed</h2>
          <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.html">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Confirmation</li>
            </ul>
          </nav>
        </div>
      </div>
    </section>
    <!-- breadcrumbs Ends -->

    <!-- content starts -->
    <section class="content">
      <div class="container">
        
        <!-- Progress Steps -->
        <div class="reservation-steps">
          <div class="step-item completed">
            <div class="step-circle"><i class="fa fa-check"></i></div>
            <div class="step-label">Check Availability</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item completed">
            <div class="step-circle"><i class="fa fa-check"></i></div>
            <div class="step-label">Select Room</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item completed">
            <div class="step-circle"><i class="fa fa-check"></i></div>
            <div class="step-label">Booking</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item active">
            <div class="step-circle">4</div>
            <div class="step-label">Confirmation</div>
          </div>
        </div>

        <div class="confirmation-container">
          
          <!-- Success Banner -->
          <div class="success-banner">
            <div class="success-icon-circle">
              <i class="fa fa-check"></i>
            </div>
            <h2>PAYMENT CONFIRMED</h2>
            <p>Thank you! Your payment has been successful and your booking is now confirmed.<br>
            A confirmation email has been sent to: <strong id="guestEmail"><?php echo htmlspecialchars($email); ?></strong></p>
            <div class="booking-id-badge">
              Booking ID: <span id="bookingIdDisplay">#<?php echo htmlspecialchars($bookingReference); ?></span>
            </div>
          </div>

          <!-- QR Code Card -->
          <div class="qr-code-card">
            <h3>🔑 YOUR DIGITAL ROOM KEY & BOOKING SUMMARY</h3>
            <p style="color: #666; margin-bottom: 20px;">Scan this QR code at check-in to access your room and view booking details</p>

            <div class="qr-code-display">
              <img id="qrCodeImage"
                   src="<?php echo htmlspecialchars($qrCodeUrl); ?>"
                   alt="QR Code for Booking <?php echo htmlspecialchars($bookingReference); ?>"
                   onerror="this.onerror=null; this.src='https://quickchart.io/qr?text=<?php echo urlencode($qrData); ?>&size=300';">
            </div>

            <div class="qr-details">
              <p><strong>📋 Booking Reference:</strong> <span id="qrBookingId"><?php echo htmlspecialchars($bookingReference); ?></span></p>
              <p><strong>🏨 Room:</strong> <span id="qrRoomName"><?php echo htmlspecialchars($roomName); ?> (<?php echo htmlspecialchars($roomType); ?>)</span></p>
              <p><strong>✅ Valid From:</strong> <span id="qrValidFrom"><?php echo htmlspecialchars($activationTime); ?></span></p>
              <p><strong>⏰ Valid Until:</strong> <span id="qrValidUntil"><?php echo htmlspecialchars($validUntil); ?></span></p>
              <p><strong>👤 Guest:</strong> <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></p>
              <p><strong>👥 Guests:</strong> <?php echo $adults; ?> Adult<?php echo $adults > 1 ? 's' : ''; ?>, <?php echo $children; ?> Child<?php echo $children !== 1 ? 'ren' : ''; ?></p>
            </div>

            <div class="qr-action-buttons">
              <button class="qr-btn" onclick="downloadQRCode()">
                <i class="fa fa-download"></i> Download QR Code
              </button>
              <button class="qr-btn" onclick="emailQRCode()">
                <i class="fa fa-envelope"></i> Send to Email
              </button>
            </div>

            <div class="qr-instructions">
              <h4>📱 WHAT'S IN YOUR QR CODE:</h4>
              <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                <p style="margin: 5px 0;"><strong>🔑 Room Key:</strong> Digital access to <?php echo htmlspecialchars($roomName); ?></p>
                <p style="margin: 5px 0;"><strong>📋 Booking Summary:</strong> Complete reservation details</p>
                <p style="margin: 5px 0;"><strong>👤 Guest Info:</strong> <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></p>
                <p style="margin: 5px 0;"><strong>📅 Stay Duration:</strong> <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?> (<?php echo htmlspecialchars($checkInFormatted); ?> - <?php echo htmlspecialchars($checkOutFormatted); ?>)</p>
                <p style="margin: 5px 0;"><strong>💰 Total Paid:</strong> $<?php echo number_format($totalAmount, 2); ?></p>
              </div>

              <h4>HOW TO USE YOUR QR CODE:</h4>
              <ol>
                <li><strong>Save</strong> this QR code to your mobile device (download or screenshot)</li>
                <li><strong>Arrive</strong> at hotel entrance at your scheduled time</li>
                <li><strong>Scan</strong> QR code at the check-in kiosk or show to staff</li>
                <li><strong>Access</strong> your room - it will be activated automatically</li>
                <li><strong>Enjoy</strong> your stay - no need to visit the front desk!</li>
              </ol>
              <p class="note">✅ QR code will be active from <span id="activationTime"><?php echo htmlspecialchars($activationTime); ?></span></p>
            </div>


          </div>

          <!-- Booking Details Grid -->
          <div class="booking-details-grid">
            
            <!-- Left Column - Reservation Details -->
            <div>
              <div class="detail-card">
                <h3>YOUR RESERVATION DETAILS</h3>

                <div class="room-display">
                  <img id="roomImageConfirm" src="<?php echo htmlspecialchars($roomImage); ?>" alt="<?php echo htmlspecialchars($roomName); ?>">
                  <div class="room-title">
                    <h4 id="roomNameConfirm"><?php echo strtoupper(htmlspecialchars($roomName)); ?></h4>
                    <div class="room-stars">
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                    </div>
                  </div>
                  <div class="room-amenities" id="roomAmenitiesConfirm"><?php echo htmlspecialchars($roomType); ?></div>
                </div>

                <div class="booking-info-grid">
                  <div class="info-item">
                    <label>CHECK-IN</label>
                    <div class="value" id="checkInDate"><?php echo htmlspecialchars($checkInFull); ?><br><small>After 2:00 PM</small></div>
                  </div>
                  <div class="info-item">
                    <label>CHECK-OUT</label>
                    <div class="value" id="checkOutDate"><?php echo htmlspecialchars($checkOutFull); ?><br><small>Before 11:00 AM</small></div>
                  </div>
                  <div class="info-item">
                    <label>DURATION</label>
                    <div class="value" id="durationNights"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></div>
                  </div>
                  <div class="info-item">
                    <label>GUESTS</label>
                    <div class="value" id="guestsCount"><?php echo str_pad($adults + $children, 2, '0', STR_PAD_LEFT); ?> Guest<?php echo ($adults + $children) > 1 ? 's' : ''; ?><br><small>(<?php echo $adults; ?> Adult<?php echo $adults > 1 ? 's' : ''; ?>, <?php echo $children; ?> Child<?php echo $children !== 1 ? 'ren' : ''; ?>)</small></div>
                  </div>
                  <div class="info-item">
                    <label>SPECIAL REQUESTS</label>
                    <div class="value" id="arrivalTimeConfirm"><?php echo !empty($specialRequests) ? htmlspecialchars($specialRequests) : 'None'; ?></div>
                  </div>
                  <div class="info-item">
                    <label>BOOKING STATUS</label>
                    <div class="value" id="roomPrefsConfirm"><span style="color: #28a745; font-weight: 700;">CONFIRMED</span></div>
                  </div>
                </div>
              </div>

              <!-- Special Requests -->
              <?php if (!empty($specialRequests)): ?>
              <div class="detail-card">
                <h3>SPECIAL REQUESTS</h3>
                <div class="special-request-box">
                  <?php echo nl2br(htmlspecialchars($specialRequests)); ?>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <!-- Right Column - Order Details & Price -->
            <div>
              <div class="detail-card">
                <h3>ORDER SUMMARY</h3>

                <table class="guest-info-table">
                  <tr>
                    <td>Booking Reference:</td>
                    <td id="orderBookingId"><strong><?php echo htmlspecialchars($bookingReference); ?></strong></td>
                  </tr>
                  <tr>
                    <td>Guest Name:</td>
                    <td id="orderFirstName"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></td>
                  </tr>
                  <tr>
                    <td>Email:</td>
                    <td id="orderEmail"><?php echo htmlspecialchars($email); ?></td>
                  </tr>
                  <tr>
                    <td>Phone:</td>
                    <td id="orderPhone"><?php echo htmlspecialchars($phone); ?></td>
                  </tr>
                  <tr>
                    <td>Room Type:</td>
                    <td id="orderCountry"><?php echo htmlspecialchars($roomName); ?></td>
                  </tr>
                  <tr>
                    <td>Check-In:</td>
                    <td><?php echo htmlspecialchars($checkInFormatted); ?></td>
                  </tr>
                  <tr>
                    <td>Check-Out:</td>
                    <td><?php echo htmlspecialchars($checkOutFormatted); ?></td>
                  </tr>
                </table>
              </div>

              <!-- Price Breakdown -->
              <div class="price-breakdown">
                <h3>PAYMENT SUMMARY</h3>

                <div class="price-line">
                  <span>Room Rate</span>
                  <span>$<span id="roomRate"><?php echo number_format($pricePerNight, 2); ?></span> × <span id="nightsCalc"><?php echo $nights; ?></span> night<?php echo $nights > 1 ? 's' : ''; ?> = $<span id="roomTotalCalc"><?php echo number_format($subtotal, 2); ?></span></span>
                </div>

                <div class="price-line subtotal">
                  <span>Subtotal</span>
                  <span>$<span id="subtotalCalc"><?php echo number_format($subtotal, 2); ?></span></span>
                </div>

                <div class="price-line">
                  <span>Taxes & Fees (18%)</span>
                  <span>$<span id="taxesCalc"><?php echo number_format($taxAmount, 2); ?></span></span>
                </div>

                <div class="price-line total">
                  <span>TOTAL PAID</span>
                  <span>$<span id="totalPaidCalc"><?php echo number_format($totalAmount, 2); ?></span></span>
                </div>

                <div class="payment-info">
                  <p><strong>Payment Method:</strong> <span id="paymentMethod">Credit Card</span></p>
                  <p><strong>Booking Reference:</strong> <span id="transactionId"><?php echo htmlspecialchars($bookingReference); ?></span></p>
                  <p><strong>Date:</strong> <span id="paymentDate"><?php echo date('F d, Y \a\t g:i A'); ?></span></p>
                  <p><strong>Status:</strong> <span class="status-badge"><i class="fa fa-check"></i> CONFIRMED</span></p>
                </div>
              </div>

              <!-- Guest Information -->
              <div class="detail-card">
                <h3>GUEST INFORMATION</h3>
                <div class="booking-info-grid">
                  <div class="info-item">
                    <label>NAME</label>
                    <div class="value"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></div>
                  </div>
                  <div class="info-item">
                    <label>EMAIL</label>
                    <div class="value"><?php echo htmlspecialchars($email); ?></div>
                  </div>
                  <div class="info-item">
                    <label>PHONE</label>
                    <div class="value"><?php echo htmlspecialchars($phone); ?></div>
                  </div>
                  <div class="info-item">
                    <label>BOOKING REF</label>
                    <div class="value"><?php echo htmlspecialchars($bookingReference); ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Important Information Boxes -->
          <div class="info-boxes-row">
            <div class="info-box">
              <h4>CHECK-IN INFO</h4>
              <ul>
                <li>Time: After 2 PM</li>
                <li>Use QR code</li>
                <li>No front desk</li>
                <li>Contactless</li>
              </ul>
            </div>
            <div class="info-box">
              <h4>DURING YOUR STAY</h4>
              <ul>
                <li>WiFi: Included</li>
                <li>Breakfast: 7-10AM</li>
                <li>Housekeeping: 10AM</li>
                <li>Concierge: 24/7</li>
              </ul>
            </div>
            <div class="info-box">
              <h4>CHECK-OUT INFO</h4>
              <ul>
                <li>Time: Before 11AM</li>
                <li>Use QR code</li>
                <li>Auto checkout</li>
                <li>Receipt via email</li>
              </ul>
            </div>
          </div>

          <!-- What's Next Timeline -->
          <div class="timeline-card">
            <h3>WHAT HAPPENS NEXT</h3>

            <div class="timeline-item completed">
              <h5>Now</h5>
              <ul>
                <li>Booking confirmed</li>
                <li>QR code generated</li>
                <li>Confirmation email sent</li>
              </ul>
            </div>

            <div class="timeline-item upcoming">
              <h5>Before Arrival (Oct 21)</h5>
              <ul>
                <li>Last day for free cancellation</li>
                <li>Save your QR code to mobile device</li>
              </ul>
            </div>

            <div class="timeline-item upcoming">
              <h5>Check-in Day (Oct 28)</h5>
              <ul>
                <li>Arrive at hotel</li>
                <li>Scan QR code at entrance</li>
                <li>Room access activated</li>
                <li>Enjoy your stay!</li>
              </ul>
            </div>
          </div>

          <!-- Cancellation Policy -->
          <div class="detail-card">
            <h3>CANCELLATION POLICY</h3>
            <p style="margin-bottom: 15px;">
              <i class="fa fa-check-circle" style="color: #28a745;"></i> 
              <strong>Free cancellation until October 21, 2025</strong>
            </p>
            <p style="margin-bottom: 20px;">
              <i class="fa fa-times-circle" style="color: #dc3545;"></i> 
              <strong>Non-refundable after October 21, 2025</strong>
            </p>
            <p style="font-size: 14px; color: var(--text-warm-brown); margin-bottom: 20px;">
              For cancellations or modifications, contact:<br>
              Phone: <strong>+94 082 1234 567</strong><br>
              Email: <strong>info@luviorahotel.com</strong>
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="action-buttons-row">
            <button class="action-btn" onclick="window.print()">
              <i class="fa fa-print"></i> Print Confirmation
            </button>
            <button class="action-btn" onclick="downloadPDF()">
              <i class="fa fa-file-pdf"></i> Download PDF
            </button>
           
          </div>

          <!-- Help & Support -->
          <div class="help-card">
            <h3>NEED ASSISTANCE?</h3>
            <p>Our team is here to help 24/7</p>
            <p>Phone: <strong>+94 082 1234 567</strong></p>
            <p>Email: <strong>info@luviorahotel.com</strong></p>
            <p>WhatsApp: <strong>+94 77 123 4567</strong></p>
            <div class="help-buttons">
              <a href="tel:+94082123456" class="qr-btn">
                <i class="fa fa-phone"></i> Call Support
              </a>
              
            </div>
          </div>

        </div>
      </div>
    </section>
    <!-- content Ends -->

    <!-- Footer Starts -->
    <footer>
      <div class="footer-top pad-bottom-20">
        <div class="container">
          <div class="footer-logo text-center">
            <img src="images/luvioralogo.png" alt="Luviora Hotel" style="width: 200px; height: 50px; margin-top: 10px;" />
          </div>
          <div class="footer-content">
            <div class="row">
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="footer-about">
                  <h4>Company Info</h4>
                  <p>Experience luxury and comfort at Luviora Hotel. Your perfect stay awaits with our world-class amenities and exceptional service.</p>
                </div>
                <div class="footer-payment">
                  <h4>We Accept</h4>
                  <ul>
                    <li><img src="images/icons/visa.png" alt="image" /></li>
                    <li><img src="images/icons/mastercard.png" alt="image" /></li>
                    <li><img src="images/icons/americanexpress.png" alt="image" /></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="quick-links">
                  <h4>Quick Links</h4>
                  <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="aboutus.html">About</a></li>
                    <li><a href="roomlist-1.html">Rooms</a></li>
                    <li><a href="testimonial.html">Testimonials</a></li>
                    <li><a href="blog-full.html">Blog</a></li>
                    <li><a href="gallery.html">Gallery</a></li>
                    <li><a href="service.html">Services</a></li>
                    <li><a href="contact.html">Contact</a></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="Rooms">
                  <h4>Rooms</h4>
                  <ul>
                    <li><a href="#">Single Rooms</a></li>
                    <li><a href="#">Double Rooms</a></li>
                    <li><a href="#">Studio Rooms</a></li>
                    <li><a href="#">Kingsize Rooms</a></li>
                    <li><a href="#">Presidentsuite Rooms</a></li>
                    <li><a href="#">Luxury Kings Rooms</a></li>
                    <li><a href="#">Connecting Rooms</a></li>
                    <li><a href="#">Murphy Rooms</a></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="footer-contact">
                  <h4>Contact info</h4>
                  <ul>
                    <li>Tel:  +94 082 1234 567</li>
                    <li>Email: info@luviorahotel.com</li>
                    <li>Fax:  +94 082 1234 567</li>
                    <li>Address: 23/B Galle Road, Colombo</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="footer-copyright pad-bottom-20">
        <div class="container">
          <div class="row">
            <div class="col-lg-4 mar-bottom-10">
              <div class="copyright-content">
                <p>Copyright 2025. Made with <span>♥</span>. All Rights Reserved. <a href="#">Luviora</a></p>
              </div>
            </div>
            <div class="col-lg-4 mar-bottom-10">
              <div class="tripadvisor-logo text-center">
                <img src="images/tripadvisor.png" alt="image" />
              </div>
            </div>
            <div class="col-lg-4 mar-bottom-10">
              
              <div class="playstore-links">
                <img src="images/icons/appstore.png" alt="image" class="mar-right-10" />
                <img src="images/icons/googleplay.png" alt="image" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
    <!-- Footer Ends -->

    <!-- Back to top start -->
    <div id="back-to-top">
      <a href="#"></a>
    </div>
    <!-- Back to top ends -->

    <div class="modal fade" id="login" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content login-content">
          <div class="login-image">
            <img src="images/logo-black.png" alt="image" />
          </div>
          <h3>Hello! Sign into your account</h3>
          <form>
            <div class="form-group">
              <input type="email" placeholder="Enter email address" />
            </div>
            <div class="form-group">
              <input type="password" placeholder="Enter password" />
            </div>
            <div class="form-group form-checkbox">
              <input type="checkbox" /> Remember Me
              <a href="#">Forgot password?</a>
            </div>
          </form>
          <div class="form-btn">
            <a href="#" class="btn btn-orange">LOGIN</a>
            <p>Need an Account?<a href="#"> Create your Luviora account</a></p>
          </div>
          <ul class="social-links">
            <li>
              <a href="#"><i class="fab fa-facebook" aria-hidden="true"></i></a>
            </li>
            <li>
              <a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a>
            </li>
            <li>
              <a href="#"><i class="fab fa-instagram" aria-hidden="true"></i></a>
            </li>
            <li>
              <a href="#"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="modal fade" id="register" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content login-content">
          <div class="login-image">
            <img src="images/logo-black.png" alt="image" />
          </div>
          <h3>Awesome! Create a Luviora Account</h3>
          <form>
            <div class="form-group">
              <input type="text" placeholder="Enter username" />
            </div>
            <div class="form-group">
              <input type="email" placeholder="Enter email address" />
            </div>
            <div class="form-group">
              <input type="password" placeholder="Enter password" />
            </div>
            <div class="form-group">
              <input type="password" placeholder="Confirm password" />
            </div>
          </form>
          <div class="form-btn">
            <a href="#" class="btn btn-orange">SIGN UP</a>
          </div>
          <ul class="social-links">
            <li>
              <a href="#"><i class="fab fa-facebook" aria-hidden="true"></i></a>
            </li>
            <li>
              <a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a>
            </li>
            <li>
              <a href="#"><i class="fab fa-instagram" aria-hidden="true"></i></a>
            </li>
            <li>
              <a href="#"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- *Scripts* -->
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugin.js"></script>
    <script src="js/main.js"></script>
    <script src="js/custom-nav.js"></script>
    
    <script>
      // Booking Data from PHP
      let bookingData = <?php echo $bookingDataJson; ?>;

      // QR Code is already generated by PHP, no need for JavaScript generation



      // Download QR Code
      function downloadQRCode() {
        const qrImage = document.getElementById('qrCodeImage');

        // Create a temporary canvas to convert the image
        fetch(qrImage.src)
          .then(response => response.blob())
          .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `Luviora-QR-${bookingData.bookingId}.png`;
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

      // Email QR Code
      function emailQRCode() {
        console.log('emailQRCode function called');
        console.log('Booking Data:', bookingData);

        const qrImage = document.getElementById('qrCodeImage');
        if (!qrImage) {
          alert('❌ Error: QR Code image not found on page');
          console.error('QR Code image element not found');
          return;
        }

        const button = event.target.closest('button');
        const originalText = button.innerHTML;

        // Show loading state
        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending Email...';
        button.disabled = true;

        // Prepare email data
        const emailData = {
          qrCodeUrl: qrImage.src,
          guestEmail: bookingData.email,
          bookingRef: bookingData.bookingId,
          guestName: bookingData.firstName + ' ' + bookingData.lastName,
          roomName: bookingData.roomName,
          checkIn: bookingData.checkIn,
          checkOut: bookingData.checkOut
        };

        console.log('Sending email data:', emailData);

        // Send email via AJAX
        fetch('send-qr-email.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(emailData)
        })
        .then(response => {
          console.log('Response status:', response.status);
          console.log('Response headers:', response.headers);
          return response.text();
        })
        .then(text => {
          console.log('Raw response:', text);

          let data;
          try {
            data = JSON.parse(text);
          } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Server returned invalid response. Please contact support.');
          }

          button.innerHTML = originalText;
          button.disabled = false;

          if (data.success) {
            // Check if email was actually sent or just saved to file
            if (data.method && data.method.includes('PHPMailer')) {
              // Real email was sent
              alert(
                '✅ EMAIL SENT SUCCESSFULLY!\n\n' +
                'Your QR code has been sent to:\n' +
                bookingData.email + '\n\n' +
                '📧 Please check your email inbox.\n' +
                '📌 Also check your spam/junk folder.\n\n' +
                'Method: ' + data.method
              );
            } else if (data.method && data.method.includes('PHP mail')) {
              // Sent using PHP mail()
              alert(
                '✅ EMAIL SENT!\n\n' +
                'Your QR code has been sent to:\n' +
                bookingData.email + '\n\n' +
                '📧 Please check your email inbox and spam folder.\n\n' +
                'Method: ' + data.method
              );
            } else if (data.method && data.method.includes('unavailable')) {
              // Email server unavailable but QR saved
              alert(
                '✅ QR CODE READY!\n\n' +
                'Your booking is confirmed!\n\n' +
                '📱 Your QR code is displayed above.\n' +
                '💾 You can download it using the Download button.\n' +
                '🖨️ You can also print it directly.\n\n' +
                '⚠️ Note: Email sending is temporarily unavailable.\n' +
                'This may be due to internet or server issues.\n\n' +
                '✅ Don\'t worry - your QR code is ready to use!'
              );
            } else {
              // Email was saved to file (testing mode)
              alert(
                '⚠️ TESTING MODE\n\n' +
                'Email was NOT actually sent.\n' +
                'It was saved to the "emails" folder for testing.\n\n' +
                '📝 To send real emails:\n' +
                '1. Make sure PHPMailer is properly installed\n' +
                '2. Check vendor/autoload.php exists\n' +
                '3. Verify email configuration\n\n' +
                'Email would be sent to: ' + bookingData.email
              );
            }
          } else {
            alert(
              '❌ FAILED TO SEND EMAIL\n\n' +
              'Error: ' + data.message + '\n\n' +
              'Please contact support or try again later.'
            );
          }
        })
        .catch(error => {
          console.error('Email error:', error);
          button.innerHTML = originalText;
          button.disabled = false;
          alert(
            '❌ NETWORK ERROR\n\n' +
            'Could not connect to email server.\n\n' +
            'Please check:\n' +
            '• Internet connection\n' +
            '• Server is running\n' +
            '• Try again in a few moments\n\n' +
            'Error: ' + error.message
          );
        });
      }







      // Download PDF
      function downloadPDF() {
        alert(
          '📄 DOWNLOAD CONFIRMATION PDF\n\n' +
          'This feature will generate a PDF with:\n' +
          '• Booking details\n' +
          '• QR code\n' +
          '• Guest information\n' +
          '• Payment summary\n\n' +
          'For now, please use Print to save as PDF:\n' +
          '1. Click "Print Confirmation"\n' +
          '2. Select "Save as PDF" as printer\n' +
          '3. Click Save'
        );
        
        // Trigger print dialog which can save as PDF
        setTimeout(function() {
          if (confirm('Would you like to open the Print dialog to save as PDF?')) {
            window.print();
          }
        }, 500);
      }

      // Email Confirmation
      function emailConfirmation() {
        const subject = encodeURIComponent('Luviora Hotel - Booking Confirmation #' + bookingData.bookingId);
        const body = encodeURIComponent(
          'LUVIORA HOTEL - BOOKING CONFIRMATION\n\n' +
          '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n' +
          'Dear ' + bookingData.firstName + ' ' + bookingData.lastName + ',\n\n' +
          'Your booking has been confirmed!\n\n' +
          '📋 BOOKING DETAILS:\n' +
          '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
          'Booking Reference: #' + bookingData.bookingId + '\n' +
          'Guest Name: ' + bookingData.firstName + ' ' + bookingData.lastName + '\n' +
          'Email: ' + bookingData.email + '\n' +
          'Phone: ' + bookingData.phone + '\n\n' +
          '🏨 ROOM INFORMATION:\n' +
          '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
          'Room: ' + bookingData.roomName + '\n' +
          'Type: ' + bookingData.roomType + '\n' +
          'Check-in: ' + bookingData.checkIn + ' (After 2:00 PM)\n' +
          'Check-out: ' + bookingData.checkOut + ' (Before 11:00 AM)\n' +
          'Duration: ' + bookingData.nights + ' night(s)\n' +
          'Guests: ' + bookingData.adults + ' Adult(s), ' + bookingData.children + ' Child(ren)\n\n' +
          '💰 PAYMENT SUMMARY:\n' +
          '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
          'Room Rate: $' + bookingData.pricePerNight + ' per night\n' +
          'Subtotal: $' + bookingData.subtotal + '\n' +
          'Tax (18%): $' + bookingData.tax + '\n' +
          'Total Paid: $' + bookingData.total + '\n\n' +
          '🔑 CHECK-IN INFORMATION:\n' +
          '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
          'Your Digital QR Code is ready!\n' +
          'Please save your QR code and present it at check-in.\n\n' +
          '📞 CONTACT US:\n' +
          '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
          'Phone: +94 082 1234 567\n' +
          'Email: info@luviorahotel.com\n' +
          'Address: 23/B Galle Road, Colombo\n\n' +
          'Thank you for choosing Luviora Hotel!\n' +
          'We look forward to welcoming you.\n\n' +
          'Best regards,\n' +
          'The Luviora Hotel Team\n\n' +
          '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
          '© 2025 Luviora Hotel. All rights reserved.'
        );
        
        // Open email client with pre-filled content
        window.location.href = `mailto:${bookingData.email}?subject=${subject}&body=${body}`;
        
        // Show confirmation
        setTimeout(function() {
          alert(
            '📧 EMAIL CLIENT OPENED\n\n' +
            'Your email client should open with:\n' +
            '• Complete booking details\n' +
            '• Pre-filled recipient: ' + bookingData.email + '\n\n' +
            'Please review and send the email.\n\n' +
            'Note: To send the QR code attachment,\n' +
            'use the "Send to Email" button instead.'
          );
        }, 1000);
      }

      // Initialize on page load
      document.addEventListener('DOMContentLoaded', function() {
        loadBookingData();

        // Check if accessed directly without booking
        if (!sessionStorage.getItem('completeBookingData')) {
          console.log('No booking data found - using demo data');
          // Optionally redirect to homepage
          // window.location.href = 'index.html';
        }
      });
    </script>

    <!-- Login and Register Modals -->
    <?php include 'includes/modals.php'; ?>

    <!-- Authentication JavaScript -->
    <script src="js/auth.js"></script>
  </body>
</html>
