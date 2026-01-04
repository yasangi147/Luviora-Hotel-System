<?php
/**
 * Advanced Modify Booking API
 * Handles comprehensive booking modifications: dates, room type, guest count
 */

session_start();
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

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
        sendResponse(false, 'Please login to modify bookings');
    }

    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    // Get modification type
    $modificationType = $input['modification_type'] ?? 'general';

    if ($modificationType === 'dates' || $modificationType === 'room' || $modificationType === 'guests') {
        // Handle specific modification types
        handleSpecificModification($db, $input);
    } else {
        // Handle general modification request
        handleGeneralModification($db, $input);
    }

} catch (Exception $e) {
    error_log("Modify booking error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while processing your request: ' . $e->getMessage());
}

/**
 * Handle specific modifications (dates, room, guests)
 */
function handleSpecificModification($db, $input) {
    $bookingId = intval($input['booking_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    $modificationType = $input['modification_type'];

    // Verify booking belongs to user
    $stmt = $db->prepare("
        SELECT b.*, r.room_name, r.room_type, r.price_per_night, r.room_id as current_room_id
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        sendResponse(false, 'Booking not found');
    }

    // Check if booking can be modified
    if (!in_array($booking['booking_status'], ['pending', 'confirmed'])) {
        sendResponse(false, 'This booking cannot be modified. Status: ' . $booking['booking_status']);
    }

    // Check if modification is within allowed timeframe (e.g., 24 hours before check-in)
    $checkInDate = new DateTime($booking['check_in_date']);
    $now = new DateTime();
    $hoursUntilCheckIn = $now->diff($checkInDate)->days * 24;

    if ($hoursUntilCheckIn < 24) {
        sendResponse(false, 'Modifications must be made at least 24 hours before check-in. Please contact support.');
    }

    // Process based on modification type
    switch ($modificationType) {
        case 'dates':
            modifyDates($db, $booking, $input);
            break;
        case 'room':
            modifyRoom($db, $booking, $input);
            break;
        case 'guests':
            modifyGuests($db, $booking, $input);
            break;
        default:
            sendResponse(false, 'Invalid modification type');
    }
}

/**
 * Modify booking dates
 */
function modifyDates($db, $booking, $input) {
    $newCheckIn = $input['new_check_in'] ?? null;
    $newCheckOut = $input['new_check_out'] ?? null;

    if (!$newCheckIn || !$newCheckOut) {
        sendResponse(false, 'New check-in and check-out dates are required');
    }

    // Validate dates
    $checkInDate = new DateTime($newCheckIn);
    $checkOutDate = new DateTime($newCheckOut);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($checkInDate < $today) {
        sendResponse(false, 'Check-in date cannot be in the past');
    }

    if ($checkOutDate <= $checkInDate) {
        sendResponse(false, 'Check-out date must be after check-in date');
    }

    // Check if room is available for new dates
    $roomId = $booking['current_room_id'];
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as conflict_count
        FROM bookings
        WHERE room_id = ?
        AND booking_id != ?
        AND booking_status IN ('confirmed', 'checked_in', 'pending')
        AND (
            (check_in_date <= ? AND check_out_date > ?)
            OR (check_in_date < ? AND check_out_date >= ?)
            OR (check_in_date >= ? AND check_out_date <= ?)
        )
    ");
    $stmt->execute([$roomId, $booking['booking_id'], $newCheckIn, $newCheckIn, $newCheckOut, $newCheckOut, $newCheckIn, $newCheckOut]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['conflict_count'] > 0) {
        sendResponse(false, 'This room is not available for the selected dates. Please choose different dates or a different room.');
    }

    // Calculate new pricing
    $nights = $checkInDate->diff($checkOutDate)->days;
    $newTotalAmount = $booking['price_per_night'] * $nights;
    $priceDifference = $newTotalAmount - $booking['total_amount'];

    // Begin transaction
    $db->beginTransaction();

    try {
        // Update booking
        $stmt = $db->prepare("
            UPDATE bookings 
            SET check_in_date = ?,
                check_out_date = ?,
                total_nights = ?,
                total_amount = ?,
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->execute([$newCheckIn, $newCheckOut, $nights, $newTotalAmount, $booking['booking_id']]);

        // Log modification
        logModification($db, $booking['booking_id'], 'dates', 
            "Changed dates from {$booking['check_in_date']} - {$booking['check_out_date']} to {$newCheckIn} - {$newCheckOut}");

        $db->commit();

        sendResponse(true, 'Booking dates updated successfully', [
            'booking_id' => $booking['booking_id'],
            'new_check_in' => $newCheckIn,
            'new_check_out' => $newCheckOut,
            'new_nights' => $nights,
            'new_total_amount' => $newTotalAmount,
            'price_difference' => $priceDifference,
            'price_change_message' => $priceDifference > 0 
                ? "Additional payment of $" . number_format($priceDifference, 2) . " required"
                : ($priceDifference < 0 
                    ? "Refund of $" . number_format(abs($priceDifference), 2) . " will be processed"
                    : "No price change")
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Modify room type
 */
function modifyRoom($db, $booking, $input) {
    $newRoomId = intval($input['new_room_id'] ?? 0);

    if (!$newRoomId) {
        sendResponse(false, 'New room ID is required');
    }

    // Get new room details
    $stmt = $db->prepare("SELECT * FROM rooms WHERE room_id = ? AND is_active = TRUE");
    $stmt->execute([$newRoomId]);
    $newRoom = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newRoom) {
        sendResponse(false, 'Selected room not found');
    }

    // Check if new room is available for booking dates
    $stmt = $db->prepare("
        SELECT COUNT(*) as conflict_count
        FROM bookings
        WHERE room_id = ?
        AND booking_id != ?
        AND booking_status IN ('confirmed', 'checked_in', 'pending')
        AND (
            (check_in_date <= ? AND check_out_date > ?)
            OR (check_in_date < ? AND check_out_date >= ?)
            OR (check_in_date >= ? AND check_out_date <= ?)
        )
    ");
    $stmt->execute([
        $newRoomId, 
        $booking['booking_id'], 
        $booking['check_in_date'], $booking['check_in_date'],
        $booking['check_out_date'], $booking['check_out_date'],
        $booking['check_in_date'], $booking['check_out_date']
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['conflict_count'] > 0) {
        sendResponse(false, 'Selected room is not available for your booking dates');
    }

    // Calculate new pricing
    $newTotalAmount = $newRoom['price_per_night'] * $booking['total_nights'];
    $priceDifference = $newTotalAmount - $booking['total_amount'];

    // Begin transaction
    $db->beginTransaction();

    try {
        // Update old room status
        $stmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
        $stmt->execute([$booking['current_room_id']]);

        // Update booking
        $stmt = $db->prepare("
            UPDATE bookings 
            SET room_id = ?,
                price_per_night = ?,
                total_amount = ?,
                updated_at = NOW()
            WHERE booking_id = ?
        ");
        $stmt->execute([$newRoomId, $newRoom['price_per_night'], $newTotalAmount, $booking['booking_id']]);

        // Update new room status
        $stmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE room_id = ?");
        $stmt->execute([$newRoomId]);

        // Log modification
        logModification($db, $booking['booking_id'], 'room', 
            "Changed room from {$booking['room_name']} to {$newRoom['room_name']}");

        $db->commit();

        sendResponse(true, 'Room changed successfully', [
            'booking_id' => $booking['booking_id'],
            'new_room_id' => $newRoomId,
            'new_room_name' => $newRoom['room_name'],
            'new_room_type' => $newRoom['room_type'],
            'new_price_per_night' => $newRoom['price_per_night'],
            'new_total_amount' => $newTotalAmount,
            'price_difference' => $priceDifference
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Modify guest count
 */
function modifyGuests($db, $booking, $input) {
    $newAdults = intval($input['new_adults'] ?? 1);
    $newChildren = intval($input['new_children'] ?? 0);

    // Validate guest count against room capacity
    $totalGuests = $newAdults + $newChildren;
    
    $stmt = $db->prepare("SELECT max_occupancy FROM rooms WHERE room_id = ?");
    $stmt->execute([$booking['current_room_id']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($totalGuests > $room['max_occupancy']) {
        sendResponse(false, "Guest count ({$totalGuests}) exceeds room capacity ({$room['max_occupancy']}). Please select a larger room.");
    }

    // Update booking
    $stmt = $db->prepare("
        UPDATE bookings 
        SET num_adults = ?,
            num_children = ?,
            updated_at = NOW()
        WHERE booking_id = ?
    ");
    $stmt->execute([$newAdults, $newChildren, $booking['booking_id']]);

    // Log modification
    logModification($db, $booking['booking_id'], 'guests', 
        "Changed guests from {$booking['num_adults']} adults, {$booking['num_children']} children to {$newAdults} adults, {$newChildren} children");

    sendResponse(true, 'Guest count updated successfully', [
        'booking_id' => $booking['booking_id'],
        'new_adults' => $newAdults,
        'new_children' => $newChildren,
        'total_guests' => $totalGuests
    ]);
}

/**
 * Handle general modification request
 */
function handleGeneralModification($db, $input) {
    // This is the original simple modification request
    $bookingRef = $input['booking_reference'] ?? '';
    $guestEmail = $input['guest_email'] ?? '';
    $modificationType = $input['modification_type'] ?? 'general';
    $requestDetails = $input['request_details'] ?? '';

    if (empty($bookingRef) || empty($guestEmail) || empty($requestDetails)) {
        sendResponse(false, 'Booking reference, email, and request details are required');
    }

    // Verify booking
    $stmt = $db->prepare("
        SELECT b.*, u.email
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_reference = ? AND u.email = ?
    ");
    $stmt->execute([$bookingRef, $guestEmail]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        sendResponse(false, 'Booking not found or email does not match');
    }

    if (!in_array($booking['booking_status'], ['pending', 'confirmed'])) {
        sendResponse(false, 'This booking cannot be modified');
    }

    // Ensure table exists
    createModificationTable($db);

    // Create modification request
    $stmt = $db->prepare("
        INSERT INTO booking_modifications 
        (booking_id, modification_type, request_details, requested_at, status)
        VALUES (?, ?, ?, NOW(), 'pending')
    ");
    $stmt->execute([$booking['booking_id'], $modificationType, $requestDetails]);

    $modificationId = $db->lastInsertId();

    sendResponse(true, 'Modification request submitted successfully', [
        'modification_id' => $modificationId,
        'booking_reference' => $bookingRef,
        'status' => 'pending'
    ]);
}

/**
 * Log modification in database
 */
function logModification($db, $bookingId, $type, $details) {
    createModificationTable($db);
    
    $stmt = $db->prepare("
        INSERT INTO booking_modifications 
        (booking_id, modification_type, request_details, requested_at, processed_at, status, admin_notes)
        VALUES (?, ?, ?, NOW(), NOW(), 'approved', 'Auto-processed')
    ");
    $stmt->execute([$bookingId, $type, $details]);
}

/**
 * Create modification table if not exists
 */
function createModificationTable($db) {
    $sql = "
    CREATE TABLE IF NOT EXISTS booking_modifications (
        modification_id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        modification_type VARCHAR(50) NOT NULL,
        request_details TEXT,
        requested_at DATETIME NOT NULL,
        processed_at DATETIME NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_notes TEXT NULL,
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
    )";
    $db->exec($sql);
}

