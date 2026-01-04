<?php
/**
 * Advanced Cancel Booking API
 * Handles booking cancellations with policy checks and refund calculation
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

try {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(false, 'Please login to cancel bookings');
    }

    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    // Support both booking_id (from logged-in user) and booking_reference (from email)
    $bookingId = intval($input['booking_id'] ?? 0);
    $bookingRef = $input['booking_reference'] ?? '';
    $cancellationReason = $input['cancellation_reason'] ?? '';
    $userId = $_SESSION['user_id'];

    if (!$bookingId && !$bookingRef) {
        sendResponse(false, 'Booking ID or reference is required');
    }

    if (empty($cancellationReason)) {
        sendResponse(false, 'Cancellation reason is required');
    }

    // Get booking details
    if ($bookingId) {
        $stmt = $db->prepare("
            SELECT b.*, r.room_name, r.room_number
            FROM bookings b
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_id = ? AND b.user_id = ?
        ");
        $stmt->execute([$bookingId, $userId]);
    } else {
        $stmt = $db->prepare("
            SELECT b.*, r.room_name, r.room_number
            FROM bookings b
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_reference = ? AND b.user_id = ?
        ");
        $stmt->execute([$bookingRef, $userId]);
    }

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        sendResponse(false, 'Booking not found or does not belong to you');
    }

    // Check if booking can be cancelled
    if ($booking['booking_status'] === 'cancelled') {
        sendResponse(false, 'This booking is already cancelled');
    }

    if ($booking['booking_status'] === 'checked_out') {
        sendResponse(false, 'Cannot cancel a completed booking');
    }

    if ($booking['booking_status'] === 'checked_in') {
        sendResponse(false, 'Cannot cancel a booking that is currently in progress. Please contact reception.');
    }

    // Calculate refund eligibility based on cancellation policy
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
        if ($booking['room_id']) {
            $stmt = $db->prepare("
                UPDATE rooms 
                SET status = 'available'
                WHERE room_id = ? AND status IN ('reserved', 'occupied')
            ");
            $stmt->execute([$booking['room_id']]);
        }

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

        // Prepare response message
        $message = "Your booking has been cancelled successfully.";
        if ($isRefundable) {
            $message .= " A refund of $" . number_format($refundAmount, 2) . " will be processed within 5-7 business days.";
        } else {
            $message .= " Unfortunately, this booking is non-refundable as it's within 7 days of check-in.";
        }

        sendResponse(true, $message, [
            'booking_reference' => $booking['booking_reference'],
            'cancellation_id' => $cancellationId,
            'refund_amount' => $refundAmount,
            'refund_percentage' => $refundPercentage,
            'is_refundable' => $isRefundable,
            'refund_status' => $refundStatus,
            'days_until_checkin' => $daysUntilCheckIn,
            'cancellation_policy' => 'Free cancellation up to 7 days before check-in'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Cancel booking error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while cancelling your booking: ' . $e->getMessage());
}

