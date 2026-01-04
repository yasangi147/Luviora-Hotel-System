<?php
/**
 * Get QR Code API
 * Returns QR code image path for a booking
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

try {
    $db = getDB();
    
    // Verify booking belongs to user
    $stmt = $db->prepare("SELECT booking_id FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    // Get QR code
    $stmt = $db->prepare("
        SELECT qr_image_path FROM qr_codes 
        WHERE booking_id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$booking_id]);
    $qr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($qr && file_exists($qr['qr_image_path'])) {
        echo json_encode([
            'success' => true,
            'qr_path' => $qr['qr_image_path']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'QR code not found']);
    }
} catch (Exception $e) {
    error_log("QR API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>

