<?php
/**
 * QR Code Scan API - Handle QR code scanning via AJAX
 * Luviora Hotel System
 */

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
    $qrCode = trim($input['qr_code'] ?? '');

    if (empty($qrCode)) {
        throw new Exception('QR code is required');
    }

    error_log("API QR Scan: " . substr($qrCode, 0, 100));

    // Try to decode JSON if it's from QR code
    $qrData = json_decode($qrCode, true);
    $bookingRef = '';

    if ($qrData && isset($qrData['booking_reference'])) {
        // New format from manual booking
        $bookingRef = $qrData['booking_reference'];
        error_log("QR Format: New format with booking_reference: " . $bookingRef);
    } elseif ($qrData && isset($qrData['type']) && $qrData['type'] === 'LUVIORA_BOOKING') {
        // QR code contains full booking data
        $bookingRef = $qrData['booking_ref'];
        error_log("QR Format: LUVIORA_BOOKING format");
    } elseif ($qrData && isset($qrData['booking_ref'])) {
        // Old format
        $bookingRef = $qrData['booking_ref'];
        error_log("QR Format: Old format with booking_ref");
    } else {
        // Plain text booking reference
        $bookingRef = $qrCode;
        error_log("QR Format: Plain text booking reference");
    }

    if (empty($bookingRef)) {
        throw new Exception('Invalid QR code format - No booking reference found');
    }

    // Find booking by reference
    $stmt = $db->prepare("
        SELECT b.*, u.name as guest_name, u.email, u.phone, r.room_number, r.room_name, r.room_type, r.floor,
               qr.qr_id, qr.qr_code_data, qr.qr_code_hash, qr.status as qr_status, qr.expiry_time
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN qr_codes qr ON b.booking_id = qr.booking_id
        WHERE b.booking_reference = ?
    ");
    $stmt->execute([$bookingRef]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking not found for reference: ' . $bookingRef);
    }

    error_log("Booking found: " . $booking['booking_id']);

    // Prepare booking data for response
    $bookingData = [
        'booking_id' => $booking['booking_id'],
        'booking_reference' => $booking['booking_reference'],
        'guest_name' => $booking['guest_name'],
        'email' => $booking['email'],
        'phone' => $booking['phone'],
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
        'qr_status' => $booking['qr_status'],
        'qr_valid' => false
    ];

    // Verify QR code if it exists
    if ($booking['qr_id']) {
        if ($booking['qr_status'] === 'active' && strtotime($booking['expiry_time']) > time()) {
            if ($booking['qr_code_data']) {
                $calculatedHash = hash('sha256', $booking['qr_code_data']);
                if ($calculatedHash === $booking['qr_code_hash']) {
                    $bookingData['qr_valid'] = true;
                    $response['message'] = 'QR Code verified successfully';
                } else {
                    $response['message'] = 'QR Code hash mismatch - Possible tampering detected';
                }
            } else {
                $bookingData['qr_valid'] = true;
                $response['message'] = 'QR Code verified successfully';
            }
        } else {
            $response['message'] = 'QR Code has expired or is no longer valid';
        }
    } else {
        $response['message'] = 'Booking found but QR code not yet generated';
    }

    $response['success'] = true;
    $response['data'] = $bookingData;

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

