<?php
/**
 * Cancel Booking API
 * Handles booking cancellation requests
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
    $cancellationReason = $input['cancellation_reason'] ?? '';
    
    if (empty($bookingRef) || empty($guestEmail)) {
        sendResponse(false, 'Booking reference and email are required');
    }
    
    $db = getDB();
    
    // Verify booking exists and belongs to the email
    $stmt = $db->prepare("
        SELECT b.*, r.room_name, r.room_type, r.room_number, u.email
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
    
    // Check if booking can be cancelled
    if ($booking['booking_status'] === 'cancelled') {
        sendResponse(false, 'This booking is already cancelled');
    }
    
    if ($booking['booking_status'] === 'checked_out') {
        sendResponse(false, 'Cannot cancel a completed booking');
    }
    
    if ($booking['booking_status'] === 'checked_in') {
        sendResponse(false, 'Cannot cancel an active booking. Please contact the hotel directly.');
    }
    
    // Calculate refund eligibility
    $checkInDate = new DateTime($booking['check_in_date']);
    $today = new DateTime();
    $daysUntilCheckIn = $today->diff($checkInDate)->days;
    
    // Cancellation policy: Free cancellation 7+ days before check-in
    $isRefundable = $daysUntilCheckIn >= 7;
    $refundPercentage = $isRefundable ? 100 : 0;
    $refundAmount = ($booking['total_amount'] * $refundPercentage) / 100;
    
    // Ensure table exists
    createCancellationTable($db);

    // Begin transaction
    $db->beginTransaction();

    try {
        // Update booking status
        $stmt = $db->prepare("
            UPDATE bookings
            SET booking_status = 'cancelled',
                cancelled_at = NOW(),
                cancellation_reason = ?
            WHERE booking_id = ?
        ");
        $stmt->execute([$cancellationReason, $booking['booking_id']]);

        // Update room status back to available if it was reserved
        $stmt = $db->prepare("
            UPDATE rooms
            SET status = 'available'
            WHERE room_id = ? AND status IN ('reserved', 'occupied')
        ");
        $stmt->execute([$booking['room_id']]);

        // Create cancellation record
        $stmt = $db->prepare("
            INSERT INTO booking_cancellations
            (booking_id, cancelled_by, cancellation_reason, refund_amount, refund_percentage, cancelled_at, refund_status)
            VALUES (?, 'guest', ?, ?, ?, NOW(), ?)
        ");

        $refundStatus = $isRefundable ? 'pending' : 'not_applicable';

        $stmt->execute([
            $booking['booking_id'],
            $cancellationReason,
            $refundAmount,
            $refundPercentage,
            $refundStatus
        ]);

        $cancellationId = $db->lastInsertId();

        // Commit transaction
        $db->commit();
        
        // Send cancellation confirmation email
        sendCancellationConfirmationEmail($booking, $guestEmail, $refundAmount, $refundPercentage);
        
        // Send notification to hotel staff
        sendCancellationNotificationEmail($booking, $cancellationReason);
        
        sendResponse(true, 'Booking cancelled successfully', [
            'booking_reference' => $bookingRef,
            'cancellation_id' => $cancellationId,
            'refund_amount' => $refundAmount,
            'refund_percentage' => $refundPercentage,
            'is_refundable' => $isRefundable,
            'refund_status' => $refundStatus,
            'message' => $isRefundable 
                ? "Your booking has been cancelled. A refund of $" . number_format($refundAmount, 2) . " will be processed within 5-7 business days."
                : "Your booking has been cancelled. Unfortunately, this booking is non-refundable as it's within 7 days of check-in."
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Cancel booking error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while cancelling your booking: ' . $e->getMessage());
}

/**
 * Create booking_cancellations table if it doesn't exist
 */
function createCancellationTable($db) {
    $sql = "
    CREATE TABLE IF NOT EXISTS booking_cancellations (
        cancellation_id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        cancelled_by ENUM('guest', 'admin', 'system') DEFAULT 'guest',
        cancellation_reason TEXT,
        refund_amount DECIMAL(10,2) DEFAULT 0,
        refund_percentage INT DEFAULT 0,
        cancelled_at DATETIME NOT NULL,
        refund_status ENUM('pending', 'processed', 'not_applicable') DEFAULT 'pending',
        refund_processed_at DATETIME NULL,
        admin_notes TEXT NULL,
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
    )";
    $db->exec($sql);
}

/**
 * Send cancellation confirmation to guest
 */
function sendCancellationConfirmationEmail($booking, $guestEmail, $refundAmount, $refundPercentage) {
    // This would integrate with your email system
    error_log("Cancellation confirmation sent to {$guestEmail} for booking {$booking['booking_reference']}");
}

/**
 * Send cancellation notification to hotel staff
 */
function sendCancellationNotificationEmail($booking, $reason) {
    // This would integrate with your email system
    error_log("Cancellation notification for booking {$booking['booking_reference']}: {$reason}");
}
?>

