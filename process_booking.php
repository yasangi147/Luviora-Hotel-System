<?php
/**
 * Process Booking - Handles booking form submission
 * This file processes the booking form from booking.php and creates the booking in the database
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: availability.php');
    exit;
}

// Get reservation and room data from session
$reservationData = isset($_SESSION['reservation']) ? $_SESSION['reservation'] : null;

if (!$reservationData || !isset($reservationData['selectedRoom'])) {
    $_SESSION['error'] = "Session expired. Please start your booking again.";
    header('Location: availability.php');
    exit;
}

$selectedRoom = $reservationData['selectedRoom'];
$checkIn = $reservationData['checkIn'];
$checkOut = $reservationData['checkOut'];
$adults = $reservationData['adults'];
$children = $reservationData['children'];
$nights = $reservationData['nights'];

try {
    $db = getDB();
    
    // Sanitize and validate input
    $firstName = sanitizeInput($_POST['firstName'] ?? '');
    $lastName = sanitizeInput($_POST['lastName'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $country = sanitizeInput($_POST['country'] ?? '');
    $postalCode = sanitizeInput($_POST['postalCode'] ?? '');
    
    // Booking for other person
    $bookingForOther = isset($_POST['bookingForOther']) && $_POST['bookingForOther'] === 'on';
    $guestName = $bookingForOther ? sanitizeInput($_POST['guestName'] ?? '') : "$firstName $lastName";
    $guestEmail = $bookingForOther ? sanitizeInput($_POST['guestEmail'] ?? '') : $email;
    $guestPhone = $bookingForOther ? sanitizeInput($_POST['guestPhone'] ?? '') : $phone;
    
    // Arrival and special requests
    $arrivalTime = sanitizeInput($_POST['arrivalTime'] ?? '');
    $specialRequests = sanitizeInput($_POST['specialRequests'] ?? '');
    $dietaryRequirements = sanitizeInput($_POST['dietaryRequirements'] ?? '');
    
    // Room preferences
    $bedPreference = sanitizeInput($_POST['bedPreference'] ?? '');
    $floorPreference = sanitizeInput($_POST['floorPreference'] ?? '');
    $smokingPreference = isset($_POST['smokingRoom']) ? 'smoking' : 'non-smoking';
    
    // Payment method
    $paymentMethod = sanitizeInput($_POST['paymentMethod'] ?? 'credit_card');
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
        throw new Exception("Please fill in all required fields.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address.");
    }
    
    // Generate booking reference
    $bookingReference = generateBookingReference();
    
    // Check if room is still available
    if (!isRoomAvailable($selectedRoom['room_id'], $checkIn, $checkOut)) {
        throw new Exception("Sorry, this room is no longer available for the selected dates. Please select another room.");
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check if user exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Create new guest user
            $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $fullName = "$firstName $lastName";
            $fullAddress = trim("$address, $city, $country $postalCode");
            
            $stmt = $db->prepare("
                INSERT INTO users (name, email, phone, password, role, address, created_at)
                VALUES (?, ?, ?, ?, 'guest', ?, NOW())
            ");
            $stmt->execute([$fullName, $email, $phone, $password, $fullAddress]);
            $userId = $db->lastInsertId();
        } else {
            $userId = $user['user_id'];
        }
        
        // Calculate total amount
        $pricePerNight = $selectedRoom['price_per_night'];
        $totalAmount = $pricePerNight * $nights;
        
        // Combine special requests
        $allRequests = [];
        if (!empty($specialRequests)) $allRequests[] = "Special Requests: $specialRequests";
        if (!empty($dietaryRequirements)) $allRequests[] = "Dietary: $dietaryRequirements";
        if (!empty($bedPreference)) $allRequests[] = "Bed: $bedPreference";
        if (!empty($floorPreference)) $allRequests[] = "Floor: $floorPreference";
        if (!empty($smokingPreference)) $allRequests[] = "Smoking: $smokingPreference";
        if (!empty($arrivalTime)) $allRequests[] = "Arrival: $arrivalTime";
        $combinedRequests = implode(" | ", $allRequests);
        
        // Insert booking
        $stmt = $db->prepare("
            INSERT INTO bookings (
                booking_reference, user_id, room_id, check_in_date, check_out_date,
                num_adults, num_children, total_nights, price_per_night, total_amount,
                special_requests, booking_status, payment_status, payment_method, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'unpaid', ?, NOW())
        ");
        
        $stmt->execute([
            $bookingReference,
            $userId,
            $selectedRoom['room_id'],
            $checkIn,
            $checkOut,
            $adults,
            $children,
            $nights,
            $pricePerNight,
            $totalAmount,
            $combinedRequests,
            $paymentMethod
        ]);
        
        $bookingId = $db->lastInsertId();
        
        // Update room availability for each date in the range
        $currentDate = new DateTime($checkIn);
        $endDate = new DateTime($checkOut);
        
        while ($currentDate < $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Check if availability record exists
            $stmt = $db->prepare("
                SELECT availability_id FROM room_availability 
                WHERE room_id = ? AND date = ?
            ");
            $stmt->execute([$selectedRoom['room_id'], $dateStr]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing record
                $stmt = $db->prepare("
                    UPDATE room_availability 
                    SET status = 'booked', booking_id = ?
                    WHERE room_id = ? AND date = ?
                ");
                $stmt->execute([$bookingId, $selectedRoom['room_id'], $dateStr]);
            } else {
                // Insert new record
                $stmt = $db->prepare("
                    INSERT INTO room_availability (room_id, date, status, booking_id)
                    VALUES (?, ?, 'booked', ?)
                ");
                $stmt->execute([$selectedRoom['room_id'], $dateStr, $bookingId]);
            }
            
            $currentDate->modify('+1 day');
        }
        
        // Commit transaction
        $db->commit();
        
        // Store booking information in session
        $_SESSION['booking'] = [
            'booking_id' => $bookingId,
            'booking_reference' => $bookingReference,
            'guest_name' => $guestName,
            'guest_email' => $guestEmail,
            'room_name' => $selectedRoom['room_name'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_amount' => $totalAmount,
            'nights' => $nights
        ];
        
        // Generate QR code data
        $qrData = json_encode([
            'booking_reference' => $bookingReference,
            'guest_name' => $guestName,
            'room' => $selectedRoom['room_name'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_amount' => $totalAmount
        ]);
        
        $qrCodeUrl = generateQRCode($qrData, 300);
        $_SESSION['booking']['qr_code_url'] = $qrCodeUrl;
        
        // Send confirmation email
        try {
            $emailSent = sendBookingConfirmationEmail(
                $guestEmail,
                $guestName,
                $bookingReference,
                $selectedRoom['room_name'],
                $checkIn,
                $checkOut,
                $totalAmount,
                $qrCodeUrl
            );
            
            if ($emailSent) {
                // Log email
                $stmt = $db->prepare("
                    INSERT INTO email_logs (booking_id, recipient_email, email_type, sent_at, status)
                    VALUES (?, ?, 'booking_confirmation', NOW(), 'sent')
                ");
                $stmt->execute([$bookingId, $guestEmail]);
            }
        } catch (Exception $e) {
            // Email failed but booking succeeded - log error
            error_log("Email sending failed for booking $bookingReference: " . $e->getMessage());
        }
        
        // Redirect to confirmation page
        header('Location: confirmation.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // Store error in session and redirect back to booking page
    $_SESSION['error'] = $e->getMessage();
    header('Location: booking.php');
    exit;
}
?>

