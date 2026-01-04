<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$bookingId = $_GET['id'] ?? 0;

if (!$bookingId) {
    header('Location: reservations.php');
    exit;
}

try {
    // Get booking details first
    $stmt = $db->prepare("SELECT booking_status FROM bookings WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $_SESSION['error'] = 'Booking not found.';
        header('Location: reservations.php');
        exit;
    }
    
    if ($booking['booking_status'] === 'cancelled') {
        $_SESSION['error'] = 'Booking is already cancelled.';
        header('Location: booking-details.php?id=' . $bookingId);
        exit;
    }
    
    // Update booking status to cancelled
    $stmt = $db->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    
    // Update room status back to available if it was reserved
    $stmt = $db->prepare("
        UPDATE rooms r
        INNER JOIN bookings b ON r.room_id = b.room_id
        SET r.status = 'available'
        WHERE b.booking_id = ? AND r.status = 'reserved'
    ");
    $stmt->execute([$bookingId]);
    
    // Deactivate QR code if exists
    $stmt = $db->prepare("UPDATE qr_codes SET status = 'cancelled' WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    
    $_SESSION['success'] = 'Booking cancelled successfully.';
    header('Location: booking-details.php?id=' . $bookingId);
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error cancelling booking: ' . $e->getMessage();
    header('Location: booking-details.php?id=' . $bookingId);
    exit;
}

