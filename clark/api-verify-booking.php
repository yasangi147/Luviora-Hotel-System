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
        SELECT b.*, u.name as guest_name, u.email, u.phone, r.room_number, r.room_name, r.room_type, r.floor,
               qr.qr_id, qr.qr_code_data, qr.qr_code_hash, qr.status as qr_status, qr.expiry_time
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN qr_codes qr ON b.booking_id = qr.booking_id
        WHERE b.booking_reference = ? OR b.booking_id = ?
    ");
    $stmt->execute([$bookingRef, $bookingRef]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking not found. Please check the booking reference and try again.');
    }

    // Prepare booking data for response
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
        'booking_status' => $booking['booking_status'],
        'payment_status' => $booking['payment_status'],
        'qr_valid' => $booking['qr_id'] ? true : false,
        'qr_status' => $booking['qr_status'],
        'expiry_time' => $booking['expiry_time']
    ];

    // Parse QR code data if available
    if ($booking['qr_code_data']) {
        $qrDataParsed = json_decode($booking['qr_code_data'], true);
        if ($qrDataParsed) {
            $bookingData['qr_data'] = $qrDataParsed;
        }
    }

    $response['success'] = true;
    $response['message'] = 'Booking verified successfully';
    $response['data'] = $bookingData;

} catch (Exception $e) {
    error_log("Booking Verification Error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

