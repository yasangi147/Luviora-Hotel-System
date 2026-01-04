<?php
/**
 * Modify Booking API
 * Handles booking modification requests
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Start output buffering to prevent any unexpected output
ob_start();

/**
 * Send JSON response
 */
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $bookingRef = $input['booking_reference'] ?? '';
    $guestEmail = $input['guest_email'] ?? '';
    $modificationType = $input['modification_type'] ?? 'general'; // dates, room, guests, general
    $requestDetails = $input['request_details'] ?? '';
    
    if (empty($bookingRef) || empty($guestEmail)) {
        sendResponse(false, 'Booking reference and email are required');
    }
    
    $db = getDB();
    
    // Verify booking exists and belongs to the email
    $stmt = $db->prepare("
        SELECT b.*, r.room_name, r.room_type, u.email
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_reference = ?
    ");
    $stmt->execute([$bookingRef]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendResponse(false, 'Booking not found');
    }
    
    // Verify email matches (either from booking or user account)
    $bookingEmail = $booking['email'] ?? $booking['guest_email'] ?? '';
    if (strtolower($bookingEmail) !== strtolower($guestEmail)) {
        sendResponse(false, 'Email does not match booking records');
    }
    
    // Check if booking can be modified
    if ($booking['booking_status'] === 'cancelled') {
        sendResponse(false, 'Cannot modify a cancelled booking');
    }
    
    if ($booking['booking_status'] === 'checked_out') {
        sendResponse(false, 'Cannot modify a completed booking');
    }
    
    // Ensure table exists
    createModificationTable($db);

    // Create modification request in database
    $stmt = $db->prepare("
        INSERT INTO booking_modifications
        (booking_id, modification_type, request_details, requested_at, status)
        VALUES (?, ?, ?, NOW(), 'pending')
    ");

    $stmt->execute([
        $booking['booking_id'],
        $modificationType,
        $requestDetails
    ]);

    $modificationId = $db->lastInsertId();

    // Send notification email to hotel staff
    sendModificationNotificationEmail($booking, $modificationType, $requestDetails);

    // Send confirmation email to guest
    sendModificationConfirmationEmail($booking, $guestEmail, $modificationId);

    sendResponse(true, 'Modification request submitted successfully', [
        'modification_id' => $modificationId,
        'booking_reference' => $bookingRef,
        'status' => 'pending'
    ]);
    
} catch (Exception $e) {
    error_log("Modify booking error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while processing your request: ' . $e->getMessage());
}

/**
 * Create booking_modifications table if it doesn't exist
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

/**
 * Send modification notification to hotel staff
 */
function sendModificationNotificationEmail($booking, $modificationType, $requestDetails) {
    // This would integrate with your email system
    // For now, just log it
    error_log("Modification request for booking {$booking['booking_reference']}: Type={$modificationType}");
}

/**
 * Send confirmation email to guest
 */
function sendModificationConfirmationEmail($booking, $guestEmail, $modificationId) {
    // This would integrate with your email system
    // For now, just log it
    error_log("Modification confirmation sent to {$guestEmail} for modification ID {$modificationId}");
}
?>

