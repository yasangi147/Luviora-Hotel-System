<?php
require_once '../config/database.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

$db = getDB();
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $bookingRef = trim($input['booking_ref'] ?? '');

    if (empty($bookingRef)) {
        throw new Exception('Booking reference is required');
    }

    // Find booking by reference
    $stmt = $db->prepare("
        SELECT b.*, u.name as guest_name, u.email, u.phone, r.room_number, r.room_name, r.room_type, r.floor
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_reference = ? OR b.booking_id = ?
        AND b.booking_status = 'checked_in'
    ");
    $stmt->execute([$bookingRef, $bookingRef]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking not found or guest not checked in');
    }

    // Update booking status to checked_out
    $updateStmt = $db->prepare("
        UPDATE bookings
        SET booking_status = 'checked_out', check_out_time = NOW()
        WHERE booking_id = ?
    ");
    $updateStmt->execute([$booking['booking_id']]);

    // Update room status to cleaning
    $roomStmt = $db->prepare("
        UPDATE rooms
        SET status = 'cleaning'
        WHERE room_id = ?
    ");
    $roomStmt->execute([$booking['room_id']]);

    // Update guest status to inactive
    $userStmt = $db->prepare("
        UPDATE users
        SET status = 'inactive'
        WHERE user_id = ?
    ");
    $userStmt->execute([$booking['user_id']]);

    // Prepare response data
    $bookingData = [
        'booking_id' => $booking['booking_id'],
        'booking_reference' => $booking['booking_reference'],
        'guest_name' => $booking['guest_name'],
        'email' => $booking['email'],
        'phone' => $booking['phone'],
        'room_id' => $booking['room_id'],
        'room_number' => $booking['room_number'],
        'room_name' => $booking['room_name'],
        'room_type' => $booking['room_type'],
        'floor' => $booking['floor'],
        'check_in_date' => $booking['check_in_date'],
        'check_out_date' => $booking['check_out_date'],
        'total_nights' => $booking['total_nights'],
        'total_amount' => $booking['total_amount'],
        'num_adults' => $booking['num_adults'],
        'num_children' => $booking['num_children'],
        'booking_status' => 'checked_out',
        'check_out_time' => date('Y-m-d H:i:s')
    ];

    $response['success'] = true;
    $response['message'] = 'Check-out successful';
    $response['data'] = $bookingData;

} catch (Exception $e) {
    error_log("Check-out Error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

