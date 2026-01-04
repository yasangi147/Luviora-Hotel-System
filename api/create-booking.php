<?php
/**
 * Create Booking API
 * Handles booking creation, payment processing, QR generation, and email sending
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Generate booking reference
 */
function generateBookingReference($db) {
    $stmt = $db->query("SELECT GenerateBookingRef() as ref");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['ref'];
}

/**
 * Generate QR code using Google Charts API
 */
function generateQRCode($bookingData) {
    // Create QR data
    $qrData = json_encode([
        'booking_ref' => $bookingData['booking_reference'],
        'guest_name' => $bookingData['guest_name'],
        'room_number' => $bookingData['room_number'],
        'check_in' => $bookingData['check_in'],
        'check_out' => $bookingData['check_out'],
        'total_amount' => $bookingData['total_amount'],
        'timestamp' => time()
    ]);
    
    // Generate hash for security
    $qrHash = hash('sha256', $qrData . 'LUVIORA_SECRET_KEY');
    
    // Encode data for QR
    $encodedData = base64_encode($qrData);
    
    // Google Charts QR Code API URL
    $qrCodeUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($encodedData);
    
    // Download and save QR code image
    $qrImageData = file_get_contents($qrCodeUrl);
    $qrImagePath = 'uploads/qr_codes/qr_' . $bookingData['booking_reference'] . '.png';
    $fullPath = __DIR__ . '/../' . $qrImagePath;
    
    // Create directory if it doesn't exist
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents($fullPath, $qrImageData);
    
    return [
        'qr_code_data' => $qrData,
        'qr_code_hash' => $qrHash,
        'qr_image_path' => $qrImagePath,
        'qr_code_url' => $qrCodeUrl
    ];
}

/**
 * Send confirmation email
 */
function sendConfirmationEmail($bookingData, $qrImagePath) {
    // For now, just log the email
    // In production, use PHPMailer
    
    $emailContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #a0522d; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .booking-details { background: white; padding: 15px; margin: 10px 0; }
            .qr-code { text-align: center; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Booking Confirmation</h1>
                <p>Luviora Hotel</p>
            </div>
            <div class='content'>
                <h2>Dear {$bookingData['guest_name']},</h2>
                <p>Thank you for choosing Luviora Hotel! Your booking has been confirmed.</p>
                
                <div class='booking-details'>
                    <h3>Booking Details:</h3>
                    <p><strong>Booking Reference:</strong> {$bookingData['booking_reference']}</p>
                    <p><strong>Room:</strong> {$bookingData['room_name']}</p>
                    <p><strong>Room Number:</strong> {$bookingData['room_number']}</p>
                    <p><strong>Check-in:</strong> {$bookingData['check_in']}</p>
                    <p><strong>Check-out:</strong> {$bookingData['check_out']}</p>
                    <p><strong>Guests:</strong> {$bookingData['num_adults']} Adults, {$bookingData['num_children']} Children</p>
                    <p><strong>Total Amount:</strong> \${$bookingData['total_amount']}</p>
                </div>
                
                <div class='qr-code'>
                    <h3>Your Check-in QR Code:</h3>
                    <img src='cid:qr_code' alt='QR Code' style='max-width: 300px;' />
                    <p>Present this QR code at check-in for a contactless experience.</p>
                </div>
                
                <div class='booking-details'>
                    <h3>Important Information:</h3>
                    <ul>
                        <li>Check-in time: 2:00 PM</li>
                        <li>Check-out time: 12:00 PM</li>
                        <li>Free cancellation up to 24 hours before check-in</li>
                        <li>Valid ID required at check-in</li>
                    </ul>
                </div>
            </div>
            <div class='footer'>
                <p>Luviora Hotel | 23/B Galle Road, Colombo</p>
                <p>Tel: +94 082 1234 567 | Email: info@luviorahotel.com</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Log email to database
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO email_logs (booking_id, user_id, recipient_email, subject, email_type, status, sent_at)
            VALUES (?, ?, ?, ?, 'booking_confirmation', 'sent', NOW())
        ");
        $stmt->execute([
            $bookingData['booking_id'],
            $bookingData['user_id'],
            $bookingData['guest_email'],
            'Booking Confirmation - ' . $bookingData['booking_reference']
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the booking
        error_log("Email logging failed: " . $e->getMessage());
    }
    
    return true;
}

/**
 * Create booking
 */
function createBooking() {
    try {
        $db = getDB();
        
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['room_id', 'check_in', 'check_out', 'guest_name', 'guest_email', 'guest_phone', 'num_adults', 'total_amount'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                sendResponse(false, "Missing required field: $field");
            }
        }
        
        // Extract data
        $roomId = (int)$data['room_id'];
        $checkIn = $data['check_in'];
        $checkOut = $data['check_out'];
        $guestName = trim($data['guest_name']);
        $guestEmail = trim($data['guest_email']);
        $guestPhone = trim($data['guest_phone']);
        $numAdults = (int)$data['num_adults'];
        $numChildren = isset($data['num_children']) ? (int)$data['num_children'] : 0;
        $specialRequests = isset($data['special_requests']) ? trim($data['special_requests']) : '';
        $totalAmount = (float)$data['total_amount'];
        $paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : 'credit_card';
        
        // Calculate nights
        $checkInDate = new DateTime($checkIn);
        $checkOutDate = new DateTime($checkOut);
        $nights = $checkInDate->diff($checkOutDate)->days;
        
        // Get room details
        $stmt = $db->prepare("SELECT * FROM rooms WHERE room_id = ? AND is_active = TRUE");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            sendResponse(false, 'Room not found or not available');
        }
        
        $pricePerNight = $room['price_per_night'];
        
        // Create or get user
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$guestEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = $user['user_id'];
        } else {
            // Create new guest user
            $stmt = $db->prepare("
                INSERT INTO users (name, email, phone, password, role, status)
                VALUES (?, ?, ?, ?, 'guest', 'active')
            ");
            $tempPassword = password_hash('guest123', PASSWORD_DEFAULT);
            $stmt->execute([$guestName, $guestEmail, $guestPhone, $tempPassword]);
            $userId = $db->lastInsertId();
        }
        
        // Generate booking reference
        $bookingReference = generateBookingReference($db);
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Call stored procedure to create booking
            $stmt = $db->prepare("CALL CreateBookingWithAvailability(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @booking_id)");
            $stmt->execute([
                $bookingReference,
                $userId,
                $roomId,
                $checkIn,
                $checkOut,
                $numAdults,
                $numChildren,
                $nights,
                $pricePerNight,
                $totalAmount,
                $specialRequests
            ]);
            $stmt->closeCursor();
            
            // Get the booking ID
            $stmt = $db->query("SELECT @booking_id as booking_id");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $bookingId = $result['booking_id'];
            
            // Update booking status to confirmed
            $stmt = $db->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid' WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            
            // Create payment record
            $transactionId = 'TXN' . time() . rand(1000, 9999);
            $stmt = $db->prepare("
                INSERT INTO payments (booking_id, transaction_id, payment_method, amount, currency, payment_status, payment_date)
                VALUES (?, ?, ?, ?, 'USD', 'completed', NOW())
            ");
            $stmt->execute([$bookingId, $transactionId, $paymentMethod, $totalAmount]);
            
            // Prepare booking data for QR and email
            $bookingData = [
                'booking_id' => $bookingId,
                'booking_reference' => $bookingReference,
                'user_id' => $userId,
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'room_id' => $roomId,
                'room_name' => $room['room_name'],
                'room_number' => $room['room_number'],
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'num_adults' => $numAdults,
                'num_children' => $numChildren,
                'total_amount' => $totalAmount,
                'nights' => $nights
            ];
            
            // Generate QR code
            $qrData = generateQRCode($bookingData);
            
            // Save QR code to database
            $expiryTime = date('Y-m-d H:i:s', strtotime($checkOut . ' +1 day'));
            $stmt = $db->prepare("
                INSERT INTO qr_codes (booking_id, qr_code_data, qr_code_hash, qr_image_path, expiry_time, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $bookingId,
                $qrData['qr_code_data'],
                $qrData['qr_code_hash'],
                $qrData['qr_image_path'],
                $expiryTime
            ]);
            
            // Commit transaction
            $db->commit();
            
            // Send confirmation email (async in production)
            sendConfirmationEmail($bookingData, $qrData['qr_image_path']);
            
            // Return success response
            sendResponse(true, 'Booking created successfully', [
                'booking_id' => $bookingId,
                'booking_reference' => $bookingReference,
                'qr_code_url' => $qrData['qr_code_url'],
                'qr_image_path' => $qrData['qr_image_path'],
                'transaction_id' => $transactionId
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        sendResponse(false, 'Error creating booking: ' . $e->getMessage());
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    createBooking();
} else {
    sendResponse(false, 'Invalid request method');
}
?>

