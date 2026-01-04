<?php
/**
 * Helper Functions for Luviora Hotel System
 * Includes QR code generation, email sending, and utility functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Generate QR Code URL using Google Charts API
 * @param string $data Data to encode in QR code
 * @param int $size Size of QR code (default: 300)
 * @return string QR code image URL
 */
function generateQRCode($data, $size = 300) {
    $encodedData = urlencode($data);
    return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedData}&choe=UTF-8";
}

/**
 * Generate booking reference number
 * @return string Unique booking reference
 */
function generateBookingReference() {
    $prefix = 'LUV';
    $date = date('ymd');
    $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $reference = $prefix . $date . $random;

    // Check if reference exists
    $db = getDB();
    $stmt = $db->prepare("SELECT booking_id FROM bookings WHERE booking_reference = ?");
    $stmt->execute([$reference]);

    // If exists, generate new one
    if ($stmt->rowCount() > 0) {
        return generateBookingReference();
    }

    return $reference;
}

/**
 * Send booking confirmation email
 * @param array $bookingData Booking details
 * @param string $qrCodeUrl QR code URL
 * @return bool Success status
 */
function sendBookingConfirmationEmail($bookingData, $qrCodeUrl) {
    $to = $bookingData['email'];
    $subject = "Booking Confirmation - " . $bookingData['booking_reference'] . " | Luviora Hotel";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Luviora Hotel <noreply@luviorahotel.com>" . "\r\n";
    
    // Email body
    $message = getBookingEmailTemplate($bookingData, $qrCodeUrl);
    
    // Send email
    $sent = mail($to, $subject, $message, $headers);
    
    // Log email
    logEmail($bookingData['booking_id'], $to, $subject, $sent ? 'sent' : 'failed');
    
    return $sent;
}

/**
 * Get booking email template
 * @param array $bookingData Booking details
 * @param string $qrCodeUrl QR code URL
 * @return string HTML email content
 */
function getBookingEmailTemplate($bookingData, $qrCodeUrl) {
    $checkIn = date('F d, Y', strtotime($bookingData['check_in_date']));
    $checkOut = date('F d, Y', strtotime($bookingData['check_out_date']));
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #a0522d; color: white; padding: 30px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .booking-details { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #a0522d; }
            .detail-row { padding: 10px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: bold; color: #a0522d; }
            .qr-code { text-align: center; margin: 30px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Booking Confirmation</h1>
                <p>Thank you for choosing Luviora Hotel</p>
            </div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($bookingData['guest_name']) . ',</p>
                <p>Your booking has been confirmed! We look forward to welcoming you to Luviora Hotel.</p>
                
                <div class="booking-details">
                    <h2 style="color: #a0522d; margin-top: 0;">Booking Details</h2>
                    
                    <div class="detail-row">
                        <span class="detail-label">Booking Reference:</span>
                        <span>' . htmlspecialchars($bookingData['booking_reference']) . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Room:</span>
                        <span>' . htmlspecialchars($bookingData['room_name']) . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Check-in:</span>
                        <span>' . $checkIn . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Check-out:</span>
                        <span>' . $checkOut . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Guests:</span>
                        <span>' . $bookingData['num_adults'] . ' Adult(s), ' . $bookingData['num_children'] . ' Child(ren)</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Total Nights:</span>
                        <span>' . $bookingData['total_nights'] . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span style="font-size: 18px; color: #a0522d; font-weight: bold;">$' . number_format($bookingData['total_amount'], 2) . '</span>
                    </div>
                </div>
                
                <div class="qr-code">
                    <p><strong>Your Booking QR Code</strong></p>
                    <img src="' . $qrCodeUrl . '" alt="Booking QR Code" style="max-width: 250px;">
                    <p style="font-size: 12px; color: #666;">Present this QR code at check-in</p>
                </div>
                
                <p><strong>Important Information:</strong></p>
                <ul>
                    <li>Check-in time: 2:00 PM</li>
                    <li>Check-out time: 12:00 PM</li>
                    <li>Please bring a valid ID for verification</li>
                    <li>Early check-in/late check-out subject to availability</li>
                </ul>
                
                <p>If you have any questions, please contact us at:</p>
                <p>
                    <strong>Email:</strong> info@luviorahotel.com<br>
                    <strong>Phone:</strong> +94 082 1234 567
                </p>
            </div>
            
            <div class="footer">
                <p>© 2025 Luviora Hotel. All rights reserved.</p>
                <p>23/B Galle Road, Colombo, Sri Lanka</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

/**
 * Log email to database
 * @param int $bookingId Booking ID
 * @param string $email Recipient email
 * @param string $subject Email subject
 * @param string $status Email status
 */
function logEmail($bookingId, $email, $subject, $status) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO email_logs (booking_id, recipient_email, subject, email_type, status, sent_at) VALUES (?, ?, ?, 'booking_confirmation', ?, NOW())");
    $stmt->execute([$bookingId, $email, $subject, $status]);
}

/**
 * Format currency
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string Formatted currency
 */
function formatCurrency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

/**
 * Sanitize input
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if room is available for date range
 * @param int $roomId Room ID
 * @param string $checkIn Check-in date
 * @param string $checkOut Check-out date
 * @return bool Availability status
 */
function isRoomAvailable($roomId, $checkIn, $checkOut) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as conflict_count
        FROM bookings
        WHERE room_id = ?
        AND booking_status IN ('confirmed', 'checked_in', 'pending')
        AND (
            (check_in_date <= ? AND check_out_date > ?)
            OR (check_in_date < ? AND check_out_date >= ?)
            OR (check_in_date >= ? AND check_out_date <= ?)
        )
    ");
    $stmt->execute([$roomId, $checkIn, $checkIn, $checkOut, $checkOut, $checkIn, $checkOut]);
    $row = $stmt->fetch();

    return $row['conflict_count'] == 0;
}

/**
 * Get available rooms for date range
 * @param string $checkIn Check-in date
 * @param string $checkOut Check-out date
 * @param int $numGuests Number of guests
 * @return array Available rooms
 */
function getAvailableRooms($checkIn, $checkOut, $numGuests = 2) {
    $db = getDB();

    // Get available rooms using correct availability logic
    // A room is available if it has NO overlapping bookings
    //
    // Two bookings overlap if:
    // - Existing booking check-in < Requested check-out AND
    // - Existing booking check-out > Requested check-in
    //
    // Example: If someone books Nov 10-12:
    // - Available for Nov 3-9 (existing check-in 10 >= requested check-out 9? NO, but existing check-out 12 > requested check-in 3? YES - WAIT)
    // - Let's think differently:
    //   * Requested: Nov 3-9 (check-in: Nov 3, check-out: Nov 9)
    //   * Existing: Nov 10-12 (check-in: Nov 10, check-out: Nov 12)
    //   * Does it overlap? existing check-in (10) < requested check-out (9)? NO - So NO overlap! ✓
    //
    // Correct logic: A booking DOES NOT overlap if:
    // - Existing check-out <= Requested check-in (existing booking ends before or when new one starts)
    // - OR Existing check-in >= Requested check-out (existing booking starts after or when new one ends)

    $query = "
        SELECT DISTINCT
            r.room_id,
            r.room_number,
            r.room_name,
            r.room_type,
            r.floor,
            r.price_per_night,
            r.max_occupancy,
            r.size_sqm,
            r.bed_type,
            r.view_type,
            r.room_style,
            r.ideal_for,
            r.description,
            r.room_image,
            r.additional_images,
            r.rating,
            r.popularity_score,
            r.is_pet_friendly,
            r.is_accessible,
            r.is_smoking_allowed,
            r.free_cancellation,
            r.breakfast_included,
            GROUP_CONCAT(DISTINCT rs.spec_name SEPARATOR ', ') as amenities
        FROM rooms r
        LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
        LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
        WHERE r.is_active = TRUE
        AND r.max_occupancy >= ?
        AND r.room_id NOT IN (
            -- Exclude rooms that have OVERLAPPING bookings
            -- A booking overlaps if:
            -- existing check-in < requested check-out AND existing check-out > requested check-in
            SELECT DISTINCT room_id
            FROM bookings
            WHERE booking_status IN ('confirmed', 'checked_in', 'pending')
            AND check_in_date < ?
            AND check_out_date > ?
        )
        GROUP BY r.room_id
        ORDER BY r.popularity_score DESC, r.rating DESC, r.price_per_night ASC
        LIMIT 50
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$numGuests, $checkOut, $checkIn]);

    return $stmt->fetchAll();
}
?>

