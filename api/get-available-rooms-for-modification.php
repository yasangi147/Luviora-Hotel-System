<?php
/**
 * Get Available Rooms for Modification
 * Returns rooms available for the specified dates (excluding current booking)
 */

session_start();
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/../config/database.php';

function sendResponse($success, $message, $data = null) {
    ob_clean();
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    ob_end_flush();
    exit();
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(false, 'Please login to view available rooms');
    }

    $db = getDB();
    
    // Get parameters
    $bookingId = intval($_GET['booking_id'] ?? 0);
    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';
    $numGuests = intval($_GET['num_guests'] ?? 2);

    if (!$bookingId || !$checkIn || !$checkOut) {
        sendResponse(false, 'Booking ID, check-in and check-out dates are required');
    }

    // Verify booking belongs to user
    $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        sendResponse(false, 'Booking not found');
    }

    // Get available rooms (excluding current booking)
    $query = "
        SELECT DISTINCT
            r.room_id,
            r.room_number,
            r.room_name,
            r.room_type,
            r.floor,
            r.price_per_night,
            r.max_occupancy,
            r.size_sqm,
            r.bed_type,
            r.view_type,
            r.room_style,
            r.description,
            r.room_image,
            r.rating,
            r.is_pet_friendly,
            r.is_accessible,
            r.free_cancellation,
            r.breakfast_included,
            GROUP_CONCAT(DISTINCT rs.spec_name SEPARATOR ', ') as amenities
        FROM rooms r
        LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
        LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
        WHERE r.is_active = TRUE
        AND r.max_occupancy >= ?
        AND r.room_id NOT IN (
            SELECT DISTINCT room_id
            FROM bookings
            WHERE room_id IS NOT NULL
            AND booking_id != ?
            AND booking_status IN ('confirmed', 'checked_in', 'pending')
            AND (
                (check_in_date <= ? AND check_out_date > ?)
                OR (check_in_date < ? AND check_out_date >= ?)
                OR (check_in_date >= ? AND check_out_date <= ?)
            )
        )
        GROUP BY r.room_id
        ORDER BY r.price_per_night ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([
        $numGuests,
        $bookingId,
        $checkIn, $checkIn,
        $checkOut, $checkOut,
        $checkIn, $checkOut
    ]);

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate nights and total price for each room
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);
    $nights = $checkInDate->diff($checkOutDate)->days;

    foreach ($rooms as &$room) {
        $room['total_price'] = $room['price_per_night'] * $nights;
        $room['nights'] = $nights;
        $room['amenities_array'] = $room['amenities'] ? explode(', ', $room['amenities']) : [];
        
        // Calculate price difference from current booking
        $room['price_difference'] = $room['total_price'] - $booking['total_amount'];
        $room['is_current_room'] = ($room['room_id'] == $booking['room_id']);
    }

    sendResponse(true, 'Available rooms retrieved successfully', [
        'rooms' => $rooms,
        'nights' => $nights,
        'current_booking' => [
            'booking_id' => $booking['booking_id'],
            'current_room_id' => $booking['room_id'],
            'current_total' => $booking['total_amount']
        ]
    ]);

} catch (Exception $e) {
    error_log("Get available rooms error: " . $e->getMessage());
    sendResponse(false, 'An error occurred: ' . $e->getMessage());
}

