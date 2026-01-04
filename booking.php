<?php
/**
 * Booking Page - Complete Your Reservation
 * Handles booking form display and submission
 */

session_start();
require_once 'config/database.php';

// Initialize variables
$error = '';
$success = '';
$bookingData = [];
$selectedRooms = [];
$reservationData = [];

// Check if user came from room selection
if (!isset($_SESSION['reservationData']) || !isset($_SESSION['selected_rooms'])) {
    header('Location: room-select.php');
    exit;
}

// Get reservation data from session
$reservationData = $_SESSION['reservationData'];
$selectedRooms = $_SESSION['selected_rooms'];

// Calculate totals
$checkIn = $reservationData['check_in'];
$checkOut = $reservationData['check_out'];
$nights = $reservationData['nights'];
$adults = $reservationData['adults'];
$children = $reservationData['children'];
$totalGuests = $adults + $children;

// Calculate room total
$roomTotal = 0;
foreach ($selectedRooms as $room) {
    $roomTotal += $room['price'] * $nights;
}

// Get extra services from database
$extraServices = [];
try {
    $db = getDB();
    // Check if extra_services table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'extra_services'");
    if ($tableCheck->rowCount() > 0) {
        $stmt = $db->query("SELECT * FROM extra_services WHERE is_active = TRUE ORDER BY display_order ASC");
        $extraServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // If table doesn't exist, use empty array
    error_log("Extra services error: " . $e->getMessage());
    $extraServices = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    try {
        $db = getDB();
        $db->beginTransaction();

        // Sanitize and validate inputs
        $firstName = htmlspecialchars(trim($_POST['firstName'] ?? ''));
        $lastName = htmlspecialchars(trim($_POST['lastName'] ?? ''));
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $dob = $_POST['dob'] ?? '';
        $country = htmlspecialchars(trim($_POST['country'] ?? ''));
        $specialRequests = htmlspecialchars(trim($_POST['specialRequests'] ?? ''));

        // Arrival info
        $arrivalTime = $_POST['arrivalTime'] ?? '';
        $transportation = htmlspecialchars(trim($_POST['transportation'] ?? ''));

        // Room preferences
        $viewPreference = $_POST['viewPreference'] ?? 'any';
        $bedPreference = $_POST['bedPreference'] ?? 'any';
        $floorPreference = $_POST['floorPreference'] ?? 'any';
        $accessibleRoom = isset($_POST['accessibleRoom']) ? 1 : 0;
        $connectingRooms = isset($_POST['connectingRooms']) ? 1 : 0;
        $quietRoom = isset($_POST['quietRoom']) ? 1 : 0;
        $nearElevator = isset($_POST['nearElevator']) ? 1 : 0;

        // Dietary requirements
        $dietVegetarian = isset($_POST['dietVegetarian']) ? 1 : 0;
        $dietVegan = isset($_POST['dietVegan']) ? 1 : 0;
        $dietGlutenFree = isset($_POST['dietGlutenFree']) ? 1 : 0;
        $dietHalal = isset($_POST['dietHalal']) ? 1 : 0;
        $dietKosher = isset($_POST['dietKosher']) ? 1 : 0;
        $dietOther = isset($_POST['dietOther']) ? 1 : 0;
        $additionalRequests = htmlspecialchars(trim($_POST['additionalRequests'] ?? ''));

        // Payment method (DO NOT store card details)
        $paymentMethod = $_POST['paymentMethod'] ?? 'card';

        // Booking for other person
        $bookingForOther = isset($_POST['bookingForOther']) ? 1 : 0;
        $guestName = $bookingForOther ? htmlspecialchars(trim($_POST['guestName'] ?? '')) : '';
        $guestEmail = $bookingForOther ? filter_var(trim($_POST['guestEmail'] ?? ''), FILTER_SANITIZE_EMAIL) : '';

        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
            throw new Exception("Please fill in all required fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        // Get or create user
        // If user is logged in, use their ID
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            // Update user info with booking form data
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, date_of_birth = ?, country = ? WHERE user_id = ?");
            $stmt->execute([$firstName . ' ' . $lastName, $phone, $dob, $country, $userId]);
        } else {
            // Not logged in - get or create user by email
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $userId = $user['user_id'];
                // Update user info
                $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, date_of_birth = ?, country = ? WHERE user_id = ?");
                $stmt->execute([$firstName . ' ' . $lastName, $phone, $dob, $country, $userId]);
            } else {
                // Create new user
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, date_of_birth, country, role, status) VALUES (?, ?, ?, ?, ?, 'guest', 'active')");
                $stmt->execute([$firstName . ' ' . $lastName, $email, $phone, $dob, $country]);
                $userId = $db->lastInsertId();
            }
        }

        // Calculate services total
        $servicesTotal = 0;
        $selectedServices = [];

        if (isset($_POST['services']) && is_array($_POST['services'])) {
            foreach ($_POST['services'] as $serviceCode) {
                $quantity = intval($_POST['service_qty_' . $serviceCode] ?? 1);
                $selectedServices[] = [
                    'code' => $serviceCode,
                    'quantity' => $quantity
                ];
            }
        }

        // Get service prices from database (only if extra_services table exists)
        if (!empty($selectedServices) && !empty($extraServices)) {
            $serviceCodes = array_column($selectedServices, 'code');
            $placeholders = str_repeat('?,', count($serviceCodes) - 1) . '?';
            $stmt = $db->prepare("SELECT * FROM extra_services WHERE service_code IN ($placeholders) AND is_active = TRUE");
            $stmt->execute($serviceCodes);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($services as $service) {
                $qty = 1;
                foreach ($selectedServices as $selected) {
                    if ($selected['code'] === $service['service_code']) {
                        $qty = $selected['quantity'];
                        break;
                    }
                }

                $price = $service['base_price'];
                if ($service['pricing_type'] === 'per_night') {
                    $price *= $nights;
                } elseif ($service['pricing_type'] === 'per_person') {
                    $price *= $qty;
                } elseif ($service['pricing_type'] === 'per_person_per_night') {
                    $price *= $qty * $nights;
                }

                $servicesTotal += $price;
            }
        }

        // Calculate totals
        $subtotal = $roomTotal + $servicesTotal;
        $taxAmount = round($subtotal * 0.18, 2); // 18% tax
        $totalAmount = $subtotal + $taxAmount;

        // Generate booking reference
        $bookingReference = 'LUV' . date('ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Check if reference exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_reference = ?");
        $stmt->execute([$bookingReference]);
        while ($stmt->fetchColumn() > 0) {
            $bookingReference = 'LUV' . date('ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $stmt->execute([$bookingReference]);
        }

        // Get arrival time and transportation from POST
        $arrivalTime = isset($_POST['arrivalTime']) ? $_POST['arrivalTime'] : null;
        $transportation = isset($_POST['transportation']) ? $_POST['transportation'] : 'car';
        $bookingForOther = isset($_POST['bookingForOther']) && $_POST['bookingForOther'] === 'on' ? 1 : 0;
        $guestName = $bookingForOther ? ($firstName . ' ' . $lastName) : null;
        $guestEmail = $bookingForOther ? $email : null;

        // Set check-in and check-out times (default: 2 PM check-in, 11 AM check-out)
        $checkInTime = $checkIn . ' 14:00:00'; // 2 PM
        $checkOutTime = $checkOut . ' 11:00:00'; // 11 AM

        // Insert booking with all fields
        $stmt = $db->prepare("
            INSERT INTO bookings (
                booking_reference, user_id, room_id, check_in_date, check_out_date,
                check_in_time, check_out_time, arrival_time, transportation,
                num_adults, num_children, total_nights, price_per_night,
                subtotal, tax_amount, services_total, total_amount,
                special_requests, booking_for_other, guest_name, guest_email,
                booking_status, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paid')
        ");

        $avgPricePerNight = $roomTotal / $nights;
        $firstRoomId = $selectedRooms[0]['room_id'];

        $stmt->execute([
            $bookingReference, $userId, $firstRoomId, $checkIn, $checkOut,
            $checkInTime, $checkOutTime, $arrivalTime, $transportation,
            $adults, $children, $nights, $avgPricePerNight,
            $subtotal, $taxAmount, $servicesTotal, $totalAmount,
            $specialRequests, $bookingForOther, $guestName, $guestEmail
        ]);

        $bookingId = $db->lastInsertId();

        $db->commit();

        // Store booking ID in session for confirmation page
        $_SESSION['booking_id'] = $bookingId;
        $_SESSION['booking_reference'] = $bookingReference;

        // Redirect to confirmation page
        header('Location: confirmation.php');
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
        error_log("Booking error: " . $e->getMessage());
    }
}

// Prepare data for JavaScript
$bookingDataJson = json_encode([
    'checkIn' => date('M d, Y', strtotime($checkIn)),
    'checkOut' => date('M d, Y', strtotime($checkOut)),
    'nights' => $nights,
    'adults' => $adults,
    'children' => $children,
    'rooms' => $selectedRooms,
    'roomTotal' => $roomTotal,
    'roomRate' => count($selectedRooms) > 0 ? $selectedRooms[0]['price'] : 0,
    'roomName' => count($selectedRooms) > 0 ? $selectedRooms[0]['room_name'] : 'Selected Room',
    'roomImage' => count($selectedRooms) > 0 ? $selectedRooms[0]['image'] : 'images/detail-slider/slider1.jpg'
]);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Complete Your Booking | Luviora Hotel</title>
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

      #back-to-top a {
        display: block;
        width: 40px;
        height: 40px;
        background: #a0522d;
        position: relative;
      }

      h1, h2 {
        font-weight: 700;
        color: #343a40;
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
        background: #FFFFFF !important;  /* White background */
        color: #000000 !important;       /* Black text */
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15); /* Optional hover shadow for better effect */
      }

      .table-item .form-btn .btn-orange {
        background-color:  #C38370 !important;
        color: #FAF9F6 !important;
        padding: 10px 25px;
        transition: all 0.3s ease;
      }

      .table-item .form-btn .btn.btn-orange:hover {
        background-color:  #a55d42 !important; /* deep brown */
        color:  #ffffff !important;     /* keeps warm brown tone for text */
        border-color:  #d19a7a00 !important;
        box-shadow:  0 8px 15px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
      } 

      /* Override template hover */
      .btn-orange:not(.navbar .btn-orange):not(.nav .btn-orange):hover {
        background: #a55d42 !important; /* deep brown */
        color: #ffffff !important;     /* keeps warm brown tone for text */
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
        background: url(../images/breadcrumb.jpg) no-repeat;
        background-size: cover;
        background-position: center;
        position: relative;
        text-align: center;
        padding: 260px 0 150px;
      }

      .award-content{
        background-color: #a55d42;
      }
      
      
      :root {
        --primary-terracotta: #a0522d;
        --secondary-sienna: #8b4513;
        --accent-gold: #d4a574;
        --text-dark-brown: #6b5744;
        --text-warm-brown: #8b6f47;
        --bg-cream: #faf8f5;
        --bg-white: #ffffff;
        --bg-beige: #f5f1eb;
        --error-red: #d84315;
        --success-green: #4caf50;
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        font-family: 'Poppins', sans-serif;
        background: var(--bg-cream);
        color: var(--text-dark-brown);
        line-height: 1.6;
      }

      h1, h2, h3, h4, h5, h6 {
        font-family: 'Playfair Display', serif;
        color: var(--text-dark-brown);
        font-weight: 700;
      }

      /* Header Styles */
      .main_header_area {
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      }

      #back-to-top a {
        display: block;
        width: 40px;
        height: 40px;
        background: var(--primary-terracotta);
        position: relative;
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

      .nav-btn .btn-orange:hover {
        background: #FFFFFF !important;
        color: #000000 !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
      }

      /* Breadcrumb */
      section.breadcrumb-outer {
        background: url(images/gallery/gallery26.jpg) no-repeat;
        background-size: cover;
        background-position: center;
        position: relative;
        text-align: center;
        padding: 260px 0 150px;
      }

      .breadcrumb-outer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(107, 87, 68, 0.7);
      }

      .breadcrumb-content {
        position: relative;
        z-index: 1;
      }

      .breadcrumb-content h2 {
        color: white;
        margin-bottom: 15px;
      }

      .breadcrumb {
        background: transparent;
        justify-content: center;
      }

      .breadcrumb-item a {
        color: white;
      }

      .breadcrumb-item.active {
        color: var(--accent-gold);
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

      .step-item.active + .step-connector {
        background-color: #a0522d;
      }

      .step-item.completed .step-circle {
        background-color: #d4a574;
        border-color: 3px solid rgba(212, 165, 116, 0.5);
        color: #ffffff;
      }

      .step-item.completed + .step-connector {
        background-color: #d4a574;
      }

      /* Main Booking Container */
      .booking-main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 60px 20px;
      }

      .booking-layout {
        display: grid;
        grid-template-columns: 60% 40%;
        gap: 40px;
        align-items: start;
      }

      /* LEFT COLUMN */
      .booking-left-column {
        display: flex;
        flex-direction: column;
        gap: 30px;
      }

      /* Booking Details Bar */
      .booking-details-bar {
        background: white;
        border: 1px solid var(--accent-gold);
        border-radius: 12px;
        padding: 25px 30px;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
      }

      .detail-item label {
        display: block;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: var(--text-warm-brown);
        margin-bottom: 5px;
        font-weight: 600;
      }

      .detail-item .value {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-dark-brown);
      }

      /* Form Sections */
      .booking-section {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 20px rgba(107, 87, 68, 0.08);
      }

      .section-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
      }

      .section-number {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: var(--primary-terracotta);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 700;
        flex-shrink: 0;
      }

      .section-header h3 {
        font-size: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
      }

      /* Form Fields */
      .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
      }

      .form-row.single-column {
        grid-template-columns: 1fr;
      }

      .form-group {
        display: flex;
        flex-direction: column;
      }

      .form-group label {
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: var(--primary-terracotta);
        margin-bottom: 8px;
        font-weight: 600;
      }

      .form-group input,
      .form-group select,
      .form-group textarea {
        background: white;
        border: 1px solid var(--accent-gold);
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 15px;
        color: var(--text-dark-brown);
        font-weight: 500;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
      }

      .form-group input:focus,
      .form-group select:focus,
      .form-group textarea:focus {
        outline: none;
        border: 2px solid var(--primary-terracotta);
        box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1);
      }

      .form-group input.error,
      .form-group select.error,
      .form-group textarea.error {
        border-color: var(--error-red);
      }

      .form-group textarea {
        resize: vertical;
        min-height: 100px;
      }

      .error-message {
        color: var(--error-red);
        font-size: 12px;
        margin-top: 5px;
        display: none;
      }

      .form-group.has-error .error-message {
        display: block;
      }

      /* Info Box */
      .info-box {
        background: var(--bg-beige);
        border-left: 4px solid var(--primary-terracotta);
        border-radius: 8px;
        padding: 15px 20px;
        margin-top: 20px;
      }

      .info-box p {
        margin: 0;
        font-size: 13px;
        color: var(--text-dark-brown);
      }

      .info-box i {
        color: var(--primary-terracotta);
        margin-right: 8px;
      }

      /* Checkboxes */
      .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 15px;
      }

      .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .checkbox-item input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: var(--primary-terracotta);
        cursor: pointer;
      }

      .checkbox-item label {
        font-size: 14px;
        color: var(--text-dark-brown);
        cursor: pointer;
        margin: 0;
        text-transform: none;
      }

      /* Radio Buttons */
      .radio-group {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 15px;
      }

      .radio-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        border: 2px solid var(--accent-gold);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .radio-item:hover {
        border-color: var(--primary-terracotta);
        background: var(--bg-beige);
      }

      .radio-item input[type="radio"] {
        width: 20px;
        height: 20px;
        accent-color: var(--primary-terracotta);
        cursor: pointer;
      }

      .radio-item.selected {
        border-color: var(--primary-terracotta);
        background: rgba(160, 82, 45, 0.05);
      }

      .radio-item label {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-dark-brown);
        cursor: pointer;
        margin: 0;
        flex: 1;
        text-transform: none;
      }

      .radio-item .payment-logos {
        display: flex;
        gap: 8px;
        align-items: center;
      }

      .radio-item .payment-logos img {
        height: 24px;
      }

      /* Security Badge */
      .security-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #e8f5e9;
        color: var(--success-green);
        padding: 8px 15px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
      }

      /* Card Fields */
      .card-number-input {
        letter-spacing: 2px;
        font-family: 'Courier New', monospace;
      }

      .cvv-group {
        position: relative;
      }

      .cvv-info {
        font-size: 11px;
        color: var(--text-warm-brown);
        margin-top: 5px;
      }

      /* Terms Checkbox */
      .terms-checkbox {
        display: flex;
        align-items: start;
        gap: 10px;
        margin: 25px 0;
      }

      .terms-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: var(--primary-terracotta);
        cursor: pointer;
        margin-top: 2px;
        flex-shrink: 0;
      }

      .terms-checkbox label {
        font-size: 13px;
        color: var(--text-dark-brown);
        line-height: 1.5;
        cursor: pointer;
      }

      .terms-checkbox a {
        color: var(--primary-terracotta);
        text-decoration: underline;
      }

      .terms-checkbox a:hover {
        color: var(--secondary-sienna);
      }

      /* Complete Booking Button */
      .complete-booking-btn {
        width: 100%;
        padding: 20px;
        background: var(--primary-terracotta);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 6px 20px rgba(160, 82, 45, 0.3);
      }

      .complete-booking-btn:hover {
        background: var(--secondary-sienna);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(160, 82, 45, 0.4);
      }

      .complete-booking-btn:disabled {
        background: var(--accent-gold);
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
      }

      .booking-note {
        text-align: center;
        font-size: 13px;
        color: var(--text-warm-brown);
        margin-top: 15px;
      }

      /* RIGHT COLUMN */
      .booking-right-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
      }

      /* Booking Summary Card */
      .summary-card {
        background: white;
        border: 1px solid var(--accent-gold);
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 10px 40px rgba(107, 87, 68, 0.1);
      }

      .summary-card h3 {
        font-size: 24px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--bg-beige);
      }

      /* Room Display */
      .selected-room {
        margin-bottom: 20px;
      }

      .room-image {
        width: 100%;
        height: 180px;
        border-radius: 8px;
        object-fit: cover;
        margin-bottom: 15px;
      }

      .room-info h4 {
        font-size: 18px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .room-stars {
        color: var(--accent-gold);
        font-size: 14px;
      }

      .room-amenities {
        font-size: 13px;
        color: var(--text-warm-brown);
        margin-top: 5px;
      }

      /* Booking Details */
      .booking-details-list {
        margin: 20px 0;
      }

      .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 15px;
        background: var(--bg-beige);
        margin-bottom: 8px;
        border-radius: 6px;
      }

      .detail-row:nth-child(even) {
        background: white;
      }

      .detail-row .label {
        font-size: 13px;
        text-transform: uppercase;
        color: var(--text-warm-brown);
        letter-spacing: 0.5px;
      }

      .detail-row .value {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-dark-brown);
      }

      /* Extra Services */
      .extra-services-section h4 {
        font-size: 18px;
        margin-bottom: 15px;
      }

      .service-card {
        background: var(--bg-beige);
        border: 1px solid var(--accent-gold);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
      }

      .service-card:hover {
        border-color: var(--primary-terracotta);
        box-shadow: 0 4px 15px rgba(160, 82, 45, 0.1);
      }

      .service-card.selected {
        background: rgba(160, 82, 45, 0.1);
        border-color: var(--primary-terracotta);
      }

      .service-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
      }

      .service-name {
        font-weight: 600;
        color: var(--text-dark-brown);
        font-size: 15px;
      }

      .service-price {
        color: var(--primary-terracotta);
        font-weight: 700;
        font-size: 16px;
      }

      .service-description {
        font-size: 12px;
        color: var(--text-warm-brown);
        margin-bottom: 10px;
      }

      .service-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .service-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-terracotta);
      }

      .quantity-selector {
        display: none;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
      }

      .quantity-selector.active {
        display: flex;
      }

      .quantity-btn {
        width: 30px;
        height: 30px;
        border: 1px solid var(--primary-terracotta);
        background: white;
        color: var(--primary-terracotta);
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
      }

      .quantity-btn:hover {
        background: var(--primary-terracotta);
        color: white;
      }

      .quantity-value {
        font-weight: 600;
        min-width: 30px;
        text-align: center;
      }

      /* Price Breakdown */
      .price-breakdown {
        border-top: 2px solid var(--bg-beige);
        padding-top: 20px;
        margin-top: 20px;
      }

      .price-breakdown h4 {
        font-size: 18px;
        margin-bottom: 15px;
      }

      .price-line {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;
        color: var(--text-dark-brown);
      }

      .price-line.indent {
        padding-left: 20px;
        font-size: 13px;
        color: var(--text-warm-brown);
      }

      .price-line.subtotal {
        padding-top: 15px;
        margin-top: 15px;
        border-top: 1px solid var(--accent-gold);
        font-weight: 600;
      }

      .price-line.total {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary-terracotta);
        background: var(--bg-beige);
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
        border-top: 2px solid var(--primary-terracotta);
      }

      .price-update {
        animation: pulse 0.5s ease;
      }

      @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
      }

      /* QR Code Info Box */
      .qr-info-card {
        background: linear-gradient(135deg, var(--bg-beige) 0%, rgba(160, 82, 45, 0.1) 100%);
        border-left: 4px solid var(--primary-terracotta);
        border-radius: 8px;
        padding: 20px;
      }

      .qr-info-card h4 {
        font-size: 16px;
        margin-bottom: 15px;
        text-transform: uppercase;
      }

      .qr-info-card ul {
        list-style: none;
        padding: 0;
        margin: 0 0 15px 0;
      }

      .qr-info-card ul li {
        font-size: 13px;
        color: var(--text-dark-brown);
        margin-bottom: 8px;
        padding-left: 25px;
        position: relative;
      }

      .qr-info-card ul li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: var(--accent-gold);
        font-weight: 700;
      }

      .qr-app-link {
        font-size: 13px;
        color: var(--text-dark-brown);
      }

      .qr-app-link a {
        color: var(--primary-terracotta);
        text-decoration: underline;
      }

      /* Cancellation Policy */
      .policy-card {
        background: white;
        border: 1px solid var(--accent-gold);
        border-radius: 8px;
        padding: 20px;
      }

      .policy-card h4 {
        font-size: 14px;
        text-transform: uppercase;
        margin-bottom: 15px;
      }

      .policy-card ul {
        list-style: none;
        padding: 0;
        margin: 0 0 15px 0;
      }

      .policy-card ul li {
        font-size: 13px;
        margin-bottom: 8px;
        padding-left: 25px;
        position: relative;
      }

      .policy-card ul li.free::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: var(--success-green);
        font-weight: 700;
      }

      .policy-card ul li.non-refund::before {
        content: '✗';
        position: absolute;
        left: 0;
        color: var(--error-red);
        font-weight: 700;
      }

      .policy-card a {
        color: var(--primary-terracotta);
        text-decoration: none;
        font-size: 13px;
      }

      .policy-card a:hover {
        text-decoration: underline;
      }

      /* Help & Support */
      .support-card {
        background: var(--bg-beige);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
      }

      .support-card h4 {
        font-size: 14px;
        text-transform: uppercase;
        margin-bottom: 15px;
      }

      .support-card p {
        font-size: 15px;
        margin: 5px 0;
        color: var(--text-dark-brown);
      }

      .support-card .chat-btn {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 20px;
        background: var(--primary-terracotta);
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
      }

      .support-card .chat-btn:hover {
        background: var(--secondary-sienna);
        transform: translateY(-2px);
      }

      /* Loading Overlay */
      .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        flex-direction: column;
      }

      .loading-overlay.active {
        display: flex;
      }

      .loading-spinner {
        width: 60px;
        height: 60px;
        border: 5px solid rgba(255, 255, 255, 0.2);
        border-top-color: var(--accent-gold);
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }

      .loading-text {
        color: white;
        font-size: 18px;
        margin-top: 20px;
        font-weight: 600;
      }

      @keyframes spin {
        to { transform: rotate(360deg); }
      }

      /* Alert Messages */
      .alert-message {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 10px;
      }

      .alert-message.active {
        display: flex;
      }

      .alert-message.error {
        background: #ffebee;
        color: var(--error-red);
        border-left: 4px solid var(--error-red);
      }

      .alert-message.success {
        background: #e8f5e9;
        color: var(--success-green);
        border-left: 4px solid var(--success-green);
      }

      .alert-message i {
        font-size: 20px;
      }

      /* Trust Badges */
      .trust-badges {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid var(--accent-gold);
      }

      .trust-badges img {
        height: 30px;
        opacity: 0.7;
      }

      /* RESPONSIVE DESIGN */
      @media (max-width: 1199px) {
        .booking-layout {
          grid-template-columns: 55% 45%;
          gap: 30px;
        }
      }

      @media (max-width: 991px) {
        .booking-layout {
          grid-template-columns: 1fr;
        }

        .booking-right-column {
          order: -1;
        }

        .booking-details-bar {
          grid-template-columns: repeat(2, 1fr);
        }

        .step-connector {
          width: 80px;
        }
      }

      @media (max-width: 767px) {
        .breadcrumb-content h2 {
          font-size: 32px;
        }

        section.breadcrumb-outer {
          padding: 180px 0 100px;
        }

        .booking-section {
          padding: 25px 20px;
        }

        .form-row {
          grid-template-columns: 1fr;
        }

        .booking-details-bar {
          grid-template-columns: 1fr;
        }

        .section-header h3 {
          font-size: 16px;
        }

        .section-number {
          width: 40px;
          height: 40px;
          font-size: 18px;
        }

        .summary-card {
          padding: 20px;
        }

        .summary-card h3 {
          font-size: 20px;
        }

        .complete-booking-btn {
          padding: 16px;
          font-size: 14px;
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

        .reservation-steps {
          margin: 40px 0 30px;
        }
      }

      @media (max-width: 480px) {
        .booking-main-container {
          padding: 30px 15px;
        }

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
        </div>
      </div>
      <!-- Navigation Bar -->
      <div class="header_menu">
        <div class="container">
          <nav class="navbar navbar-default">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
              <a class="navbar-brand" href="index.php">
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

                <li class="submenu dropdown  active">
                  <a href="gallery.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Gallery</a>
                </li>

                <li class="submenu dropdown">
                  <a href="service.php" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
                    >Services</a>
                </li>

                <li class="submenu dropdown">
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

    <!-- Breadcrumb -->
    <section class="breadcrumb-outer">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Complete Your Booking</h2>
          <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Booking</li>
            </ul>
          </nav>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <section class="content">
      <div class="container">
        <!-- Progress Steps -->
        <div class="reservation-steps">
          <div class="step-item completed">
            <div class="step-circle">1</div>
            <div class="step-label">Check Availability</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item completed">
            <div class="step-circle">2</div>
            <div class="step-label">Select Room</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item active">
            <div class="step-circle">3</div>
            <div class="step-label">Booking</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-circle">4</div>
            <div class="step-label">Confirmation</div>
          </div>
        </div>

        <!-- Main Booking Container -->
        <div class="booking-main-container">
          <div id="alertContainer"></div>

          <div class="booking-layout">
            <!-- LEFT COLUMN -->
            <div class="booking-left-column">

              <!-- Booking Details Bar -->
              <div class="booking-details-bar">
                <div class="detail-item">
                  <label>CHECK-IN</label>
                  <div class="value" id="displayCheckIn"><?php echo date('M d, Y', strtotime($checkIn)); ?></div>
                </div>
                <div class="detail-item">
                  <label>CHECK-OUT</label>
                  <div class="value" id="displayCheckOut"><?php echo date('M d, Y', strtotime($checkOut)); ?></div>
                </div>
                <div class="detail-item">
                  <label>NIGHTS</label>
                  <div class="value" id="displayNights"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></div>
                </div>
                <div class="detail-item">
                  <label>GUESTS</label>
                  <div class="value" id="displayGuests"><?php echo $adults; ?> Adult<?php echo $adults > 1 ? 's' : ''; ?>, <?php echo $children; ?> Child<?php echo $children !== 1 ? 'ren' : ''; ?></div>
                </div>
              </div>

              <!-- Error/Success Messages -->
              <?php if ($error): ?>
              <div class="alert alert-danger" style="background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle" style="color: #f44336; margin-right: 10px;"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
              </div>
              <?php endif; ?>

              <!-- Booking Form -->
              <form method="POST" action="booking.php" id="bookingForm">

              <!-- Section 1: Personal Information -->
              <div class="booking-section">
                <div class="section-header">
                  <div class="section-number">1</div>
                  <h3>Personal Information</h3>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" id="firstName" name="firstName" placeholder="Enter first name" required>
                    <span class="error-message">First name is required</span>
                  </div>
                  <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" id="lastName" name="lastName" placeholder="Enter last name" required>
                    <span class="error-message">Last name is required</span>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="Enter email address" required>
                    <span class="error-message">Valid email is required</span>
                  </div>
                  <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" id="phone" name="phone" placeholder="+94 XXX XXX XXX" required>
                    <span class="error-message">Valid phone number is required</span>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="dob" name="dob">
                  </div>
                  <div class="form-group">
                    <label>Country</label>
                    <select id="country" name="country">
                      <option value="">Select Country</option>
                      <option value="sri-lanka">Sri Lanka</option>
                      <option value="india">India</option>
                      <option value="usa">United States</option>
                      <option value="uk">United Kingdom</option>
                      <option value="australia">Australia</option>
                      <option value="canada">Canada</option>
                      <option value="germany">Germany</option>
                      <option value="france">France</option>
                      <option value="japan">Japan</option>
                      <option value="china">China</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                </div>

                <div class="form-row single-column">
                  <div class="form-group">
                    <label>Special Requests / Message</label>
                    <textarea id="specialRequests" name="specialRequests" placeholder="Any special requests or requirements?"></textarea>
                  </div>
                </div>

                <div class="checkbox-item">
                  <input type="checkbox" id="bookingForOther" name="bookingForOther">
                  <label for="bookingForOther">Booking for someone else?</label>
                </div>

                <div id="guestFieldsContainer" style="display: none; margin-top: 20px;">
                  <div class="form-row">
                    <div class="form-group">
                      <label>Guest Name</label>
                      <input type="text" id="guestName" name="guestName" placeholder="Enter guest full name">
                    </div>
                    <div class="form-group">
                      <label>Guest Email</label>
                      <input type="email" id="guestEmail" name="guestEmail" placeholder="Enter guest email">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section 2: Arrival Information -->
              <div class="booking-section">
                <div class="section-header">
                  <div class="section-number">2</div>
                  <h3>Arrival Information</h3>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Estimated Arrival Time *</label>
                    <select id="arrivalTime" name="arrivalTime" required>
                      <option value="">Select arrival time</option>
                      <option value="morning">Morning (6AM-12PM)</option>
                      <option value="afternoon">Afternoon (12PM-6PM)</option>
                      <option value="evening">Evening (6PM-10PM)</option>
                      <option value="late-night">Late Night (After 10PM)</option>
                    </select>
                    <span class="error-message">Arrival time is required</span>
                  </div>
                  <div class="form-group">
                    <label>Transportation Needs</label>
                    <select id="transportation" name="transportation">
                      <option value="no">No</option>
                      <option value="airport-pickup">Airport Pickup</option>
                      <option value="taxi-service">Taxi Service</option>
                    </select>
                  </div>
                </div>

                <div class="info-box">
                  <p><i class="fas fa-info-circle"></i> Your QR code room key will be activated at your selected arrival time</p>
                </div>
              </div>

              <!-- Section 3: Room Preferences -->
              <div class="booking-section">
                <div class="section-header">
                  <div class="section-number">3</div>
                  <h3>Room Preferences</h3>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>View Preference</label>
                    <select id="viewPreference" name="viewPreference">
                      <option value="no-preference">No Preference</option>
                      <option value="ocean-view">Ocean View</option>
                      <option value="city-view">City View</option>
                      <option value="garden-view">Garden View</option>
                      <option value="mountain-view">Mountain View</option>
                      <option value="pool-view">Pool View</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Bed Type Preference</label>
                    <select id="bedPreference" name="bedPreference">
                      <option value="as-booked">As Booked</option>
                      <option value="single">Single</option>
                      <option value="double">Double</option>
                      <option value="queen">Queen</option>
                      <option value="king">King</option>
                      <option value="twin">Twin Beds</option>
                    </select>
                  </div>
                </div>

                <div class="form-row single-column">
                  <div class="form-group">
                    <label>Floor Preference</label>
                    <select id="floorPreference" name="floorPreference">
                      <option value="no-preference">No Preference</option>
                      <option value="low">Low Floor (1-3)</option>
                      <option value="mid">Mid Floor (4-7)</option>
                      <option value="high">High Floor (8+)</option>
                    </select>
                  </div>
                </div>

                <div class="checkbox-group">
                  <div class="checkbox-item">
                    <input type="checkbox" id="accessibleRoom" name="accessibleRoom">
                    <label for="accessibleRoom">Accessible Room Required</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="connectingRooms" name="connectingRooms">
                    <label for="connectingRooms">Connecting Rooms (if booking multiple)</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="quietRoom" name="quietRoom">
                    <label for="quietRoom">Quiet Room (Away from elevator/pool)</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="nearElevator" name="nearElevator">
                    <label for="nearElevator">Near Elevator</label>
                  </div>
                </div>
              </div>

              <!-- Section 4: Dietary & Special Requirements -->
              <div class="booking-section">
                <div class="section-header">
                  <div class="section-number">4</div>
                  <h3>Special Requirements</h3>
                </div>

                <div class="checkbox-group">
                  <div class="checkbox-item">
                    <input type="checkbox" id="dietVegetarian" name="dietVegetarian">
                    <label for="dietVegetarian">Vegetarian</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="dietVegan" name="dietVegan">
                    <label for="dietVegan">Vegan</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="dietGlutenFree" name="dietGlutenFree">
                    <label for="dietGlutenFree">Gluten-Free</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="dietHalal" name="dietHalal">
                    <label for="dietHalal">Halal</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="dietKosher" name="dietKosher">
                    <label for="dietKosher">Kosher</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="checkbox" id="dietOther" name="dietOther">
                    <label for="dietOther">Other (specify below)</label>
                  </div>
                </div>

                <div class="form-row single-column" style="margin-top: 20px;">
                  <div class="form-group">
                    <label>Additional Requests</label>
                    <textarea id="additionalRequests" name="additionalRequests" placeholder="Any other special requests or requirements? (e.g., early check-in, anniversary surprise, accessibility needs)"></textarea>
                  </div>
                </div>
              </div>

              <!-- Section 5: Payment Information -->
              <div class="booking-section">
                <div class="section-header">
                  <div class="section-number">5</div>
                  <h3>Payment Information</h3>
                </div>

                <div class="security-badge">
                  <i class="fas fa-lock"></i> Secure Payment
                </div>

                <div class="radio-group">
                  <div class="radio-item" data-payment="card">
                    <input type="radio" name="paymentMethod" id="paymentCard" value="card" checked>
                    <label for="paymentCard">Credit/Debit Card</label>
                    <div class="payment-logos">
                      <img src="images/icons/visa.png" alt="Visa" style="height: 20px;">
                      <img src="images/icons/mastercard.png" alt="Mastercard" style="height: 20px;">
                    </div>
                  </div>
                  <div class="radio-item" data-payment="paypal">
                    <input type="radio" name="paymentMethod" id="paymentPayPal" value="paypal">
                    <label for="paymentPayPal">PayPal</label>
                  </div>
                  <div class="radio-item" data-payment="payhere">
                    <input type="radio" name="paymentMethod" id="paymentPayHere" value="payhere">
                    <label for="paymentPayHere">PayHere (Sri Lankan Payment)</label>
                  </div>
                </div>

                <!-- Card Payment Fields -->
                <div id="cardFields" style="margin-top: 25px;">
                  <div class="form-row single-column">
                    <div class="form-group">
                      <label>Card Number *</label>
                      <input type="text" id="cardNumber" name="cardNumber" class="card-number-input" placeholder="1234 5678 9012 3456" maxlength="19">
                      <span class="error-message">Valid card number is required</span>
                    </div>
                  </div>

                  <div class="form-row single-column">
                    <div class="form-group">
                      <label>Cardholder Name *</label>
                      <input type="text" id="cardholderName" name="cardholderName" placeholder="Name as shown on card">
                      <span class="error-message">Cardholder name is required</span>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label>Expiry Month *</label>
                      <select id="expiryMonth" name="expiryMonth">
                        <option value="">MM</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                      </select>
                      <span class="error-message">Select expiry month</span>
                    </div>
                    <div class="form-group">
                      <label>Expiry Year *</label>
                      <select id="expiryYear" name="expiryYear">
                        <option value="">YYYY</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                        <option value="2029">2029</option>
                        <option value="2030">2030</option>
                        <option value="2031">2031</option>
                        <option value="2032">2032</option>
                        <option value="2033">2033</option>
                        <option value="2034">2034</option>
                        <option value="2035">2035</option>
                      </select>
                      <span class="error-message">Select expiry year</span>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group cvv-group">
                      <label>CVV *</label>
                      <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4">
                      <div class="cvv-info">3-4 digit code on back of card</div>
                      <span class="error-message">Valid CVV is required</span>
                    </div>
                    <div class="form-group">
                      <!-- Empty for layout -->
                    </div>
                  </div>
                </div>

                <div class="terms-checkbox">
                  <input type="checkbox" id="termsAgree" name="termsAgree">
                  <label for="termsAgree">I agree to the <a href="#" target="_blank">Terms & Conditions</a> and <a href="#" target="_blank">Privacy Policy</a></label>
                </div>
              </div>

              <!-- Complete Booking Button -->
              <div class="booking-section" style="padding: 30px 40px;">
                <button type="submit" name="submit_booking" class="complete-booking-btn" id="completeBookingBtn">
                  Complete Booking
                </button>
                <p class="booking-note">You won't be charged yet. Review your booking on the next page.</p>
              </div>

              </form>
              <!-- End of Booking Form -->

            </div>

            <!-- RIGHT COLUMN - STICKY -->
            <div class="booking-right-column">
              
              <!-- Booking Summary Card -->
              <div class="summary-card">
                <h3>Your Booking Summary</h3>

                <!-- Selected Room -->
                <div class="selected-room">
                  <img src="images/detail-slider/slider1.jpg" alt="Room" class="room-image" id="roomImage">
                  <div class="room-info">
                    <h4>
                      <span id="roomName">Super Deluxe Room</span>
                      <span class="room-stars">
                        <i class="fa fa-star"></i>
                        <i class="fa fa-star"></i>
                        <i class="fa fa-star"></i>
                        <i class="fa fa-star"></i>
                        <i class="fa fa-star"></i>
                      </span>
                    </h4>
                    <div class="room-amenities" id="roomAmenities">King Bed | 45m² | City View</div>
                  </div>
                </div>

                <!-- Booking Details -->
                <div class="booking-details-list">
                  <div class="detail-row">
                    <span class="label">Check-In</span>
                    <span class="value" id="summaryCheckIn">Wednesday, Oct 28, 2025<br><small>After 2:00 PM</small></span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Check-Out</span>
                    <span class="value" id="summaryCheckOut">Saturday, Oct 31, 2025<br><small>Before 11:00 AM</small></span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Duration</span>
                    <span class="value" id="summaryNights">3 Nights</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Guests</span>
                    <span class="value" id="summaryGuests">2 Adults, 1 Child</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Rooms</span>
                    <span class="value" id="summaryRooms">1 Room (Super Deluxe)</span>
                  </div>
                </div>
              </div>

              <!-- Extra Services -->
              <div class="summary-card extra-services-section">
                <h4>Enhance Your Stay</h4>

                <div class="service-card" data-service="airport" data-price="50">
                  <div class="service-header">
                    <span class="service-name">Airport Transfer</span>
                    <span class="service-price">$50</span>
                  </div>
                  <div class="service-description">Private car pickup from Colombo Airport</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceAirport">
                    <label for="serviceAirport">Add to booking</label>
                  </div>
                </div>

                <div class="service-card" data-service="breakfast" data-price="30" data-per-person="true">
                  <div class="service-header">
                    <span class="service-name">Breakfast Package</span>
                    <span class="service-price">$30/person/day</span>
                  </div>
                  <div class="service-description">Continental breakfast buffet daily</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceBreakfast">
                    <label for="serviceBreakfast">Add to booking</label>
                  </div>
                  <div class="quantity-selector" id="breakfastQty">
                    <button type="button" class="quantity-btn" onclick="updateQuantity('breakfast', -1)">-</button>
                    <span class="quantity-value" id="breakfastQuantity">1</span>
                    <button type="button" class="quantity-btn" onclick="updateQuantity('breakfast', 1)">+</button>
                  </div>
                </div>

                <div class="service-card" data-service="spa" data-price="80">
                  <div class="service-header">
                    <span class="service-name">Spa Treatment</span>
                    <span class="service-price">$80</span>
                  </div>
                  <div class="service-description">60-minute relaxation massage</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceSpa">
                    <label for="serviceSpa">Add to booking</label>
                  </div>
                </div>

                <div class="service-card" data-service="upgrade" data-price="40" data-per-night="true">
                  <div class="service-header">
                    <span class="service-name">Room Upgrade</span>
                    <span class="service-price">$40/night</span>
                  </div>
                  <div class="service-description">Upgrade to next room category</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceUpgrade">
                    <label for="serviceUpgrade">Add to booking</label>
                  </div>
                </div>

                <div class="service-card" data-service="early-checkin" data-price="25">
                  <div class="service-header">
                    <span class="service-name">Early Check-in</span>
                    <span class="service-price">$25</span>
                  </div>
                  <div class="service-description">Check-in from 10:00 AM</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceEarlyCheckin">
                    <label for="serviceEarlyCheckin">Add to booking</label>
                  </div>
                </div>

                <div class="service-card" data-service="late-checkout" data-price="25">
                  <div class="service-header">
                    <span class="service-name">Late Checkout</span>
                    <span class="service-price">$25</span>
                  </div>
                  <div class="service-description">Check-out until 4:00 PM</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceLateCheckout">
                    <label for="serviceLateCheckout">Add to booking</label>
                  </div>
                </div>

                <div class="service-card" data-service="champagne" data-price="60">
                  <div class="service-header">
                    <span class="service-name">Champagne & Flowers</span>
                    <span class="service-price">$60</span>
                  </div>
                  <div class="service-description">Welcome champagne and rose bouquet</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceChampagne">
                    <label for="serviceChampagne">Add to booking</label>
                  </div>
                </div>

                <div class="service-card" data-service="city-tour" data-price="40" data-per-person="true">
                  <div class="service-header">
                    <span class="service-name">City Tour</span>
                    <span class="service-price">$40/person</span>
                  </div>
                  <div class="service-description">Half-day Colombo city tour</div>
                  <div class="service-checkbox">
                    <input type="checkbox" id="serviceCityTour">
                    <label for="serviceCityTour">Add to booking</label>
                  </div>
                  <div class="quantity-selector" id="cityTourQty">
                    <button type="button" class="quantity-btn" onclick="updateQuantity('cityTour', -1)">-</button>
                    <span class="quantity-value" id="cityTourQuantity">1</span>
                    <button type="button" class="quantity-btn" onclick="updateQuantity('cityTour', 1)">+</button>
                  </div>
                </div>
              </div>

              <!-- Price Breakdown -->
              <div class="summary-card price-breakdown">
                <h4>Price Breakdown</h4>
                
                <div class="price-line">
                  <span>Room Rate</span>
                  <span>$<span id="roomRateCalc">150</span> × <span id="nightsCalc">3</span> nights = $<span id="roomTotal">450</span></span>
                </div>
                
                <div id="servicesBreakdown"></div>
                
                <div class="price-line subtotal">
                  <span>Subtotal</span>
                  <span>$<span id="subtotalAmount">450</span></span>
                </div>
                
                <div class="price-line">
                  <span>Taxes & Fees (18%)</span>
                  <span>$<span id="taxAmount">81</span></span>
                </div>
                
                <div class="price-line total">
                  <span>TOTAL AMOUNT</span>
                  <span>$<span id="totalAmount">531</span></span>
                </div>
              </div>

              <!-- QR Code Info -->
              <div class="qr-info-card">
                <h4>Your Digital Room Key</h4>
                <ul>
                  <li>QR code will be sent via email & SMS</li>
                  <li>Valid from <span id="qrCheckIn">Oct 28 at 2:00 PM</span></li>
                  <li>Valid until <span id="qrCheckOut">Oct 31 at 11:00 AM</span></li>
                  <li>Use at hotel entrance for check-in</li>
                </ul>
                
              </div>

              <!-- Cancellation Policy -->
              <div class="policy-card">
                <h4>Cancellation Policy</h4>
                <ul>
                  <li class="free">Free cancellation until Oct 21, 2025</li>
                  <li class="non-refund">Non-refundable after Oct 21, 2025</li>
                </ul>
                <a href="#">View Full Policy</a>
              </div>

              <!-- Help & Support -->
              <div class="support-card">
                <h4>Need Help?</h4>
                <p>Phone: +94 082 1234 567</p>
                <p>Email: info@luviorahotel.com</p>
                <a href="https://wa.me/94729444349" class="chat-btn">Contact Us on WhatsApp</a>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
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
                    <li><img src="images/icons/visa.png" alt="Visa" /></li>
                    <li><img src="images/icons/mastercard.png" alt="Mastercard" /></li>
                    <li><img src="images/icons/americanexpress.png" alt="American Express" /></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-3 col-md-6 mar-bottom-30">
                <div class="quick-links">
                  <h4>Quick Links</h4>
                  <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="aboutus.php">About</a></li>
                    <li><a href="roomlist-1.php">Rooms</a></li>
                    <li><a href="testimonial.php">Testimonials</a></li>
                    <li><a href="blog-full.php">Blog</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="service.php">Services</a></li>
                    <li><a href="contact.php">Contact</a></li>
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
                  <h4>Contact Info</h4>
                  <ul>
                    <li>Tel: +94 082 1234 567</li>
                    <li>Email: info@luviorahotel.com</li>
                    <li>Fax: +94 082 1234 567</li>
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
                <img src="images/tripadvisor.png" alt="TripAdvisor" />
              </div>
            </div>
            <div class="col-lg-4 mar-bottom-10">
              <div class="playstore-links">
                <img src="images/icons/appstore.png" alt="App Store" class="mar-right-10" />
                <img src="images/icons/googleplay.png" alt="Google Play" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Back to top -->
    <div id="back-to-top">
      <a href="#"></a>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="login" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content login-content">
          <div class="login-image">
            <img src="images/logo-black.png" alt="Logo" />
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
            <p>Need an Account? <a href="#">Create your Luviora account</a></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="register" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content login-content">
          <div class="login-image">
            <img src="images/logo-black.png" alt="Logo" />
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
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugin.js"></script>
    <script src="js/main.js"></script>
    <script src="js/custom-nav.js"></script>
    
    <script>
      // Booking Data from PHP
      let bookingData = <?php echo $bookingDataJson; ?>;

      // Services Data
      let servicesQuantities = {
        breakfast: 1,
        cityTour: 1
      };

      // Load booking data from sessionStorage
      function loadBookingData() {
        const storedData = sessionStorage.getItem('bookingData');
        if (storedData) {
          bookingData = { ...bookingData, ...JSON.parse(storedData) };
        }
        updateDisplayData();
      }

      // Update display with booking data
      function updateDisplayData() {
        document.getElementById('displayCheckIn').textContent = bookingData.checkIn;
        document.getElementById('displayCheckOut').textContent = bookingData.checkOut;
        document.getElementById('displayNights').textContent = `${bookingData.nights} Night${bookingData.nights > 1 ? 's' : ''}`;
        document.getElementById('displayGuests').textContent = `${bookingData.adults} Adult${bookingData.adults > 1 ? 's' : ''}, ${bookingData.children} Child${bookingData.children !== 1 ? 'ren' : ''}`;

        // Update summary
        document.getElementById('summaryNights').textContent = `${bookingData.nights} Night${bookingData.nights > 1 ? 's' : ''}`;
        document.getElementById('summaryGuests').textContent = `${bookingData.adults} Adult${bookingData.adults > 1 ? 's' : ''}, ${bookingData.children} Child${bookingData.children !== 1 ? 'ren' : ''}`;
        document.getElementById('roomName').textContent = bookingData.roomName;
        document.getElementById('roomImage').src = bookingData.roomImage;

        // Update summary check-in and check-out dates with day of week
        const checkInDate = new Date(bookingData.checkIn);
        const checkOutDate = new Date(bookingData.checkOut);
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        document.getElementById('summaryCheckIn').innerHTML = `${daysOfWeek[checkInDate.getDay()]}, ${bookingData.checkIn}<br><small>After 2:00 PM</small>`;
        document.getElementById('summaryCheckOut').innerHTML = `${daysOfWeek[checkOutDate.getDay()]}, ${bookingData.checkOut}<br><small>Before 11:00 AM</small>`;

        calculatePrices();
      }

      // Calculate prices
      function calculatePrices() {
        const roomTotal = bookingData.roomRate * bookingData.nights;
        let servicesTotal = 0;
        let servicesHtml = '';

        // Calculate each service
        document.querySelectorAll('.service-card').forEach(card => {
          const checkbox = card.querySelector('input[type="checkbox"]');
          if (checkbox && checkbox.checked) {
            const service = card.dataset.service;
            const basePrice = parseFloat(card.dataset.price);
            let price = basePrice;
            let serviceName = card.querySelector('.service-name').textContent;

            if (card.dataset.perPerson === 'true') {
              const qtyKey = service === 'breakfast' ? 'breakfast' : 'cityTour';
              const qty = servicesQuantities[qtyKey] || 1;
              if (service === 'breakfast') {
                price = basePrice * qty * bookingData.nights;
                servicesHtml += `<div class="price-line indent"><span>• ${serviceName} (${qty} × ${bookingData.nights} nights)</span><span>$${price}</span></div>`;
              } else {
                price = basePrice * qty;
                servicesHtml += `<div class="price-line indent"><span>• ${serviceName} (${qty} person${qty > 1 ? 's' : ''})</span><span>$${price}</span></div>`;
              }
            } else if (card.dataset.perNight === 'true') {
              price = basePrice * bookingData.nights;
              servicesHtml += `<div class="price-line indent"><span>• ${serviceName}</span><span>$${price}</span></div>`;
            } else {
              servicesHtml += `<div class="price-line indent"><span>• ${serviceName}</span><span>$${price}</span></div>`;
            }

            servicesTotal += price;
          }
        });

        // Update display
        document.getElementById('roomRateCalc').textContent = bookingData.roomRate;
        document.getElementById('nightsCalc').textContent = bookingData.nights;
        document.getElementById('roomTotal').textContent = roomTotal;

        if (servicesTotal > 0) {
          document.getElementById('servicesBreakdown').innerHTML = 
            `<div class="price-line"><span>Extra Services</span><span>$${servicesTotal}</span></div>${servicesHtml}`;
        } else {
          document.getElementById('servicesBreakdown').innerHTML = '';
        }

        const subtotal = roomTotal + servicesTotal;
        const tax = Math.round(subtotal * 0.18 * 100) / 100;
        const total = subtotal + tax;

        document.getElementById('subtotalAmount').textContent = subtotal;
        document.getElementById('taxAmount').textContent = tax.toFixed(2);
        document.getElementById('totalAmount').textContent = total.toFixed(2);

        // Add pulse animation
        document.querySelectorAll('.price-line.total').forEach(el => {
          el.classList.add('price-update');
          setTimeout(() => el.classList.remove('price-update'), 500);
        });
      }

      // Update quantity
      function updateQuantity(service, change) {
        const currentQty = servicesQuantities[service];
        const newQty = Math.max(1, Math.min(10, currentQty + change));
        servicesQuantities[service] = newQty;
        document.getElementById(`${service}Quantity`).textContent = newQty;
        calculatePrices();
      }

      // Form validation
      function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      }

      function validatePhone(phone) {
        return /^[\d\s\+\-\(\)]+$/.test(phone) && phone.replace(/\D/g, '').length >= 9;
      }

      function validateCardNumber(number) {
        // Luhn algorithm
        const digits = number.replace(/\s/g, '');
        if (!/^\d{13,19}$/.test(digits)) return false;
        
        let sum = 0;
        let isEven = false;
        
        for (let i = digits.length - 1; i >= 0; i--) {
          let digit = parseInt(digits[i]);
          if (isEven) {
            digit *= 2;
            if (digit > 9) digit -= 9;
          }
          sum += digit;
          isEven = !isEven;
        }
        
        return sum % 10 === 0;
      }

      function showError(fieldId, show = true) {
        const field = document.getElementById(fieldId);
        const formGroup = field.closest('.form-group');
        if (show) {
          field.classList.add('error');
          formGroup.classList.add('has-error');
        } else {
          field.classList.remove('error');
          formGroup.classList.remove('has-error');
        }
      }

      function showAlert(message, type = 'error') {
        const alertHtml = `
          <div class="alert-message ${type} active">
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
            <span>${message}</span>
          </div>
        `;
        document.getElementById('alertContainer').innerHTML = alertHtml;
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Auto hide after 5 seconds
        setTimeout(() => {
          const alert = document.querySelector('.alert-message');
          if (alert) alert.classList.remove('active');
        }, 5000);
      }

      function validateForm() {
        let isValid = true;

        // Personal Information
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();

        if (!firstName) {
          showError('firstName');
          isValid = false;
        } else {
          showError('firstName', false);
        }

        if (!lastName) {
          showError('lastName');
          isValid = false;
        } else {
          showError('lastName', false);
        }

        if (!email || !validateEmail(email)) {
          showError('email');
          isValid = false;
        } else {
          showError('email', false);
        }

        if (!phone || !validatePhone(phone)) {
          showError('phone');
          isValid = false;
        } else {
          showError('phone', false);
        }

        // Arrival Information
        const arrivalTime = document.getElementById('arrivalTime').value;
        if (!arrivalTime) {
          showError('arrivalTime');
          isValid = false;
        } else {
          showError('arrivalTime', false);
        }

        // Payment Information (if card selected)
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        if (paymentMethod === 'card') {
          const cardNumber = document.getElementById('cardNumber').value.trim();
          const cardholderName = document.getElementById('cardholderName').value.trim();
          const expiryMonth = document.getElementById('expiryMonth').value;
          const expiryYear = document.getElementById('expiryYear').value;
          const cvv = document.getElementById('cvv').value.trim();

          if (!cardNumber || !validateCardNumber(cardNumber)) {
            showError('cardNumber');
            isValid = false;
          } else {
            showError('cardNumber', false);
          }

          if (!cardholderName) {
            showError('cardholderName');
            isValid = false;
          } else {
            showError('cardholderName', false);
          }

          if (!expiryMonth) {
            showError('expiryMonth');
            isValid = false;
          } else {
            showError('expiryMonth', false);
          }

          if (!expiryYear) {
            showError('expiryYear');
            isValid = false;
          } else {
            showError('expiryYear', false);
          }

          if (!cvv || !/^\d{3,4}$/.test(cvv)) {
            showError('cvv');
            isValid = false;
          } else {
            showError('cvv', false);
          }
        }

        // Terms checkbox
        const termsAgree = document.getElementById('termsAgree').checked;
        if (!termsAgree) {
          showAlert('Please agree to the Terms & Conditions and Privacy Policy');
          isValid = false;
        }

        return isValid;
      }

      // Complete booking
      function completeBooking() {
        if (!validateForm()) {
          showAlert('Please fill in all required fields correctly');
          return;
        }

        // Show loading
        document.getElementById('loadingOverlay').classList.add('active');

        // Collect all form data
        const formData = {
          // Personal Info
          firstName: document.getElementById('firstName').value.trim(),
          lastName: document.getElementById('lastName').value.trim(),
          email: document.getElementById('email').value.trim(),
          phone: document.getElementById('phone').value.trim(),
          dob: document.getElementById('dob').value,
          country: document.getElementById('country').value,
          specialRequests: document.getElementById('specialRequests').value.trim(),
          
          // Guest Info (if applicable)
          bookingForOther: document.getElementById('bookingForOther').checked,
          guestName: document.getElementById('guestName').value.trim(),
          guestEmail: document.getElementById('guestEmail').value.trim(),
          
          // Arrival Info
          arrivalTime: document.getElementById('arrivalTime').value,
          transportation: document.getElementById('transportation').value,
          
          // Room Preferences
          viewPreference: document.getElementById('viewPreference').value,
          bedPreference: document.getElementById('bedPreference').value,
          floorPreference: document.getElementById('floorPreference').value,
          accessibleRoom: document.getElementById('accessibleRoom').checked,
          connectingRooms: document.getElementById('connectingRooms').checked,
          quietRoom: document.getElementById('quietRoom').checked,
          nearElevator: document.getElementById('nearElevator').checked,
          
          // Dietary Requirements
          dietVegetarian: document.getElementById('dietVegetarian').checked,
          dietVegan: document.getElementById('dietVegan').checked,
          dietGlutenFree: document.getElementById('dietGlutenFree').checked,
          dietHalal: document.getElementById('dietHalal').checked,
          dietKosher: document.getElementById('dietKosher').checked,
          dietOther: document.getElementById('dietOther').checked,
          additionalRequests: document.getElementById('additionalRequests').value.trim(),
          
          // Payment Info
          paymentMethod: document.querySelector('input[name="paymentMethod"]:checked').value,
          
          // Booking Details
          bookingData: bookingData,
          
          // Selected Services
          services: []
        };

        // Add card info if card payment
        if (formData.paymentMethod === 'card') {
          formData.cardNumber = document.getElementById('cardNumber').value.trim();
          formData.cardholderName = document.getElementById('cardholderName').value.trim();
          formData.expiryMonth = document.getElementById('expiryMonth').value;
          formData.expiryYear = document.getElementById('expiryYear').value;
          formData.cvv = document.getElementById('cvv').value.trim();
        }

        // Collect services
        document.querySelectorAll('.service-card input[type="checkbox"]:checked').forEach(checkbox => {
          const card = checkbox.closest('.service-card');
          const service = {
            name: card.querySelector('.service-name').textContent,
            price: card.dataset.price,
            service: card.dataset.service
          };
          
          if (card.dataset.perPerson === 'true') {
            const qtyKey = card.dataset.service === 'breakfast' ? 'breakfast' : 'cityTour';
            service.quantity = servicesQuantities[qtyKey];
          }
          
          formData.services.push(service);
        });

        // Store in sessionStorage
        sessionStorage.setItem('completeBookingData', JSON.stringify(formData));

        // Simulate API call
        setTimeout(() => {
          document.getElementById('loadingOverlay').classList.remove('active');
          
          // Redirect to confirmation page
          window.location.href = 'confirmation.php';
        }, 2000);
      }

      // Event Listeners
      document.addEventListener('DOMContentLoaded', function() {
        loadBookingData();

        // Booking for other checkbox
        document.getElementById('bookingForOther').addEventListener('change', function() {
          document.getElementById('guestFieldsContainer').style.display = this.checked ? 'block' : 'none';
        });

        // Payment method selection
        document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
          radio.addEventListener('change', function() {
            document.querySelectorAll('.radio-item').forEach(item => item.classList.remove('selected'));
            this.closest('.radio-item').classList.add('selected');
            
            // Show/hide card fields
            document.getElementById('cardFields').style.display = this.value === 'card' ? 'block' : 'none';
          });
        });

        // Service checkboxes
        document.querySelectorAll('.service-card input[type="checkbox"]').forEach(checkbox => {
          checkbox.addEventListener('change', function() {
            const card = this.closest('.service-card');
            const service = card.dataset.service;
            
            if (this.checked) {
              card.classList.add('selected');
              
              // Show quantity selector if per-person service
              if (card.dataset.perPerson === 'true') {
                const qtySelector = card.querySelector('.quantity-selector');
                if (qtySelector) qtySelector.classList.add('active');
              }
            } else {
              card.classList.remove('selected');
              
              // Hide quantity selector
              const qtySelector = card.querySelector('.quantity-selector');
              if (qtySelector) qtySelector.classList.remove('active');
            }
            
            calculatePrices();
          });
        });

        // Card number formatting
        document.getElementById('cardNumber').addEventListener('input', function() {
          let value = this.value.replace(/\s/g, '');
          let formatted = value.match(/.{1,4}/g);
          this.value = formatted ? formatted.join(' ') : '';
        });

        // Complete booking button
        document.getElementById('completeBookingBtn').addEventListener('click', completeBooking);

        // Initial radio selection
        document.querySelector('input[name="paymentMethod"]:checked').closest('.radio-item').classList.add('selected');
      });

      // Make updateQuantity available globally
      window.updateQuantity = updateQuantity;
    </script>

    <!-- Login and Register Modals -->
    <?php include 'includes/modals.php'; ?>

    <!-- Authentication JavaScript -->
    <script src="js/auth.js"></script>
</body>
</html>
