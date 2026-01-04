<?php
/**
 * Booking API
 * Handles room booking operations
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'check_availability':
        checkAvailability();
        break;
    case 'create':
        createBooking();
        break;
    case 'cancel':
        cancelBooking();
        break;
    case 'get_details':
        getBookingDetails();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Check room availability
 */
function checkAvailability() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $check_in = isset($data['check_in']) ? $data['check_in'] : '';
    $check_out = isset($data['check_out']) ? $data['check_out'] : '';
    $room_type = isset($data['room_type']) ? $data['room_type'] : null;

    if (empty($check_in) || empty($check_out)) {
        sendResponse(false, 'Check-in and check-out dates are required');
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("CALL CheckRoomAvailability(?, ?, ?)");
        $stmt->execute([$check_in, $check_out, $room_type]);
        $rooms = $stmt->fetchAll();

        sendResponse(true, 'Available rooms retrieved', ['rooms' => $rooms]);
    } catch (Exception $e) {
        error_log("Availability check error: " . $e->getMessage());
        sendResponse(false, 'An error occurred while checking availability');
    }
}

/**
 * Create new booking
 */
function createBooking() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(false, 'Please login to make a booking');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $_SESSION['user_id'];
    $room_id = isset($data['room_id']) ? $data['room_id'] : null;
    $check_in = isset($data['check_in']) ? $data['check_in'] : '';
    $check_out = isset($data['check_out']) ? $data['check_out'] : '';
    $num_adults = isset($data['num_adults']) ? intval($data['num_adults']) : 1;
    $num_children = isset($data['num_children']) ? intval($data['num_children']) : 0;
    $special_requests = isset($data['special_requests']) ? $data['special_requests'] : '';

    if (empty($check_in) || empty($check_out)) {
        sendResponse(false, 'Check-in and check-out dates are required');
        return;
    }

    try {
        $db = getDB();
        
        // Calculate nights and total amount
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $total_nights = $check_in_date->diff($check_out_date)->days;

        if ($total_nights < 1) {
            sendResponse(false, 'Invalid date range');
            return;
        }

        // Get room price
        $stmt = $db->prepare("SELECT price_per_night FROM rooms WHERE room_id = ? AND is_active = TRUE");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();

        if (!$room) {
            sendResponse(false, 'Room not found');
            return;
        }

        $price_per_night = $room['price_per_night'];
        $total_amount = $price_per_night * $total_nights;

        // Generate booking reference
        $stmt = $db->query("SELECT GenerateBookingReference() as ref");
        $booking_ref = $stmt->fetch()['ref'];

        // Create booking
        $stmt = $db->prepare("
            INSERT INTO bookings (booking_reference, user_id, room_id, check_in_date, check_out_date, 
                                 num_adults, num_children, total_nights, price_per_night, total_amount, 
                                 special_requests, booking_status, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
        ");
        
        $stmt->execute([
            $booking_ref, $user_id, $room_id, $check_in, $check_out,
            $num_adults, $num_children, $total_nights, $price_per_night, 
            $total_amount, $special_requests
        ]);

        $booking_id = $db->lastInsertId();

        sendResponse(true, 'Booking created successfully', [
            'booking_id' => $booking_id,
            'booking_reference' => $booking_ref,
            'total_amount' => $total_amount,
            'redirect' => '../payment.php?booking_id=' . $booking_id
        ]);

    } catch (Exception $e) {
        error_log("Booking creation error: " . $e->getMessage());
        sendResponse(false, 'An error occurred while creating booking');
    }
}

/**
 * Cancel booking
 */
function cancelBooking() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(false, 'Unauthorized');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $booking_id = isset($data['booking_id']) ? intval($data['booking_id']) : 0;
    $user_id = $_SESSION['user_id'];

    try {
        $db = getDB();
        
        // Verify booking belongs to user
        $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch();

        if (!$booking) {
            sendResponse(false, 'Booking not found');
            return;
        }

        if ($booking['booking_status'] !== 'pending') {
            sendResponse(false, 'Only pending bookings can be cancelled');
            return;
        }

        // Cancel booking
        $stmt = $db->prepare("
            UPDATE bookings 
            SET booking_status = 'cancelled', cancelled_at = NOW() 
            WHERE booking_id = ?
        ");
        $stmt->execute([$booking_id]);

        sendResponse(true, 'Booking cancelled successfully');

    } catch (Exception $e) {
        error_log("Booking cancellation error: " . $e->getMessage());
        sendResponse(false, 'An error occurred while cancelling booking');
    }
}

/**
 * Get booking details
 */
function getBookingDetails() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(false, 'Unauthorized');
        return;
    }

    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    $user_id = $_SESSION['user_id'];

    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT b.*, r.room_number, r.room_name, r.room_type, r.room_image,
                   u.name as guest_name, u.email as guest_email, u.phone as guest_phone
            FROM bookings b
            LEFT JOIN rooms r ON b.room_id = r.room_id
            LEFT JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_id = ? AND b.user_id = ?
        ");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch();

        if (!$booking) {
            sendResponse(false, 'Booking not found');
            return;
        }

        sendResponse(true, 'Booking details retrieved', ['booking' => $booking]);

    } catch (Exception $e) {
        error_log("Get booking details error: " . $e->getMessage());
        sendResponse(false, 'An error occurred while retrieving booking details');
    }
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>

