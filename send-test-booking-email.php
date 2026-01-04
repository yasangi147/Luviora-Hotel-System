<?php
/**
 * Send Test Booking Email
 * Backend script to send test booking confirmation email
 */

// Start output buffering to catch any unexpected output
ob_start();

// Disable error display, only log errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON header
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    ob_end_flush();
    exit;
}

try {
    // Get POST data
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['bookingData']) || !isset($input['qrCodeUrl'])) {
        throw new Exception('Missing required data');
    }
    
    $bookingData = $input['bookingData'];
    $qrCodeUrl = $input['qrCodeUrl'];
    
    // Validate email
    if (!isset($bookingData['guest_email']) || !filter_var($bookingData['guest_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Load email configuration
    require_once 'config/email.php';
    
    // Send the email
    $result = sendBookingConfirmationEmail($bookingData, $qrCodeUrl);
    
    // Clean any unexpected output before sending JSON
    ob_clean();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully',
            'email' => $bookingData['guest_email'],
            'subject' => 'Booking Confirmation - ' . $bookingData['booking_reference'],
            'method' => 'PHPMailer SMTP'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email. Please check email configuration and logs.'
        ]);
    }
    
    ob_end_flush();
    
} catch (Exception $e) {
    error_log("Test email error: " . $e->getMessage());
    
    // Clean any unexpected output before sending JSON
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    ob_end_flush();
}
?>

