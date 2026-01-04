<?php
/**
 * Payment Processing API
 * Handles payment gateway integration and verification
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_payment':
            createPayment();
            break;
        case 'verify_payment':
            verifyPayment();
            break;
        case 'get_payment_details':
            getPaymentDetails();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Create payment record
 */
function createPayment() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $bookingId = $data['booking_id'] ?? 0;
    $amount = $data['amount'] ?? 0;
    $paymentMethod = $data['payment_method'] ?? 'stripe';
    
    if (!$bookingId || !$amount) {
        echo json_encode(['success' => false, 'message' => 'Booking ID and amount are required']);
        return;
    }
    
    try {
        $db = getDB();
        
        // Create payment record
        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, created_at)
            VALUES (?, ?, ?, 'pending', ?, NOW())
        ");
        
        $transactionId = 'TXN_' . time() . '_' . rand(1000, 9999);
        $stmt->execute([$bookingId, $amount, $paymentMethod, $transactionId]);
        $paymentId = $db->lastInsertId();
        
        // In production, integrate with actual payment gateway
        // For now, simulate payment gateway response
        $paymentGatewayUrl = simulatePaymentGateway($paymentId, $amount, $paymentMethod);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'payment_id' => $paymentId,
                'transaction_id' => $transactionId,
                'payment_url' => $paymentGatewayUrl,
                'amount' => $amount,
                'payment_method' => $paymentMethod
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error creating payment: ' . $e->getMessage()]);
    }
}

/**
 * Verify payment after gateway callback
 */
function verifyPayment() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $paymentId = $data['payment_id'] ?? 0;
    $transactionId = $data['transaction_id'] ?? '';
    $status = $data['status'] ?? 'failed';
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    try {
        $db = getDB();
        
        // Update payment status
        $stmt = $db->prepare("
            UPDATE payments 
            SET payment_status = ?, 
                transaction_id = ?,
                payment_date = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$status, $transactionId, $paymentId]);
        
        if ($status === 'completed') {
            // Get booking ID
            $stmt = $db->prepare("SELECT booking_id FROM payments WHERE payment_id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                // Update booking status to confirmed
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET booking_status = 'confirmed',
                        payment_status = 'paid'
                    WHERE booking_id = ?
                ");
                $stmt->execute([$payment['booking_id']]);
                
                // Generate QR code
                $qrCode = generateQRCode($payment['booking_id']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => [
                        'booking_id' => $payment['booking_id'],
                        'qr_code' => $qrCode,
                        'status' => 'confirmed'
                    ]
                ]);
                return;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment status updated',
            'data' => ['status' => $status]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error verifying payment: ' . $e->getMessage()]);
    }
}

/**
 * Get payment details
 */
function getPaymentDetails() {
    $paymentId = $_GET['payment_id'] ?? 0;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        return;
    }
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT p.*, b.booking_reference, b.total_amount as booking_amount
            FROM payments p
            LEFT JOIN bookings b ON p.booking_id = b.booking_id
            WHERE p.payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            echo json_encode(['success' => true, 'data' => $payment]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching payment details: ' . $e->getMessage()]);
    }
}

/**
 * Simulate payment gateway (replace with actual integration)
 */
function simulatePaymentGateway($paymentId, $amount, $method) {
    // In production, integrate with:
    // - Stripe: https://stripe.com/docs/api
    // - PayHere: https://www.payhere.lk/developers
    // - PayPal: https://developer.paypal.com/
    
    // For now, return a mock payment page URL
    return "payment-gateway.php?payment_id={$paymentId}&amount={$amount}&method={$method}";
}

/**
 * Generate QR code for booking
 */
function generateQRCode($bookingId) {
    try {
        $db = getDB();
        
        // Get booking details
        $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            return null;
        }
        
        // Create encrypted QR data
        $qrData = json_encode([
            'booking_id' => $bookingId,
            'booking_reference' => $booking['booking_reference'],
            'check_in' => $booking['check_in_date'],
            'check_out' => $booking['check_out_date'],
            'timestamp' => time()
        ]);
        
        // Encrypt QR data
        $encryptedData = hash('sha256', $qrData . 'LUVIORA_SECRET_KEY');
        
        // Calculate expiry (check-out date + 1 day)
        $expiryDate = date('Y-m-d H:i:s', strtotime($booking['check_out_date'] . ' +1 day'));
        
        // Store QR code in database
        $stmt = $db->prepare("
            INSERT INTO qr_codes (booking_id, code_data, expiry_time, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                code_data = VALUES(code_data),
                expiry_time = VALUES(expiry_time)
        ");
        $stmt->execute([$bookingId, $encryptedData, $expiryDate]);
        
        // Generate QR code URL using Google Charts API
        $qrCodeUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($encryptedData);
        
        return [
            'qr_code_url' => $qrCodeUrl,
            'qr_data' => $encryptedData,
            'expiry_date' => $expiryDate
        ];
    } catch (Exception $e) {
        error_log("QR Code generation error: " . $e->getMessage());
        return null;
    }
}
?>

