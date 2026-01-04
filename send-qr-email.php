<?php
/**
 * Send QR Code Email
 * Sends the QR code image to the guest's email address
 */

// Start output buffering to catch any unexpected output
ob_start();

// Disable error display, only log errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Set JSON header first
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    ob_end_flush();
    exit;
}

// Get POST data
$rawInput = file_get_contents('php://input');
error_log("Raw input received: " . $rawInput);

$input = json_decode($rawInput, true);
error_log("Decoded input: " . print_r($input, true));

$qrCodeUrl = $input['qrCodeUrl'] ?? '';
$guestEmail = $input['guestEmail'] ?? '';
$bookingRef = $input['bookingRef'] ?? '';
$guestName = $input['guestName'] ?? '';
$roomName = $input['roomName'] ?? '';
$checkIn = $input['checkIn'] ?? '';
$checkOut = $input['checkOut'] ?? '';

error_log("Guest Email: " . $guestEmail);
error_log("Booking Ref: " . $bookingRef);
error_log("QR Code URL: " . $qrCodeUrl);

// Validate inputs
if (empty($qrCodeUrl) || empty($guestEmail) || empty($bookingRef)) {
    $missingFields = [];
    if (empty($qrCodeUrl)) $missingFields[] = 'qrCodeUrl';
    if (empty($guestEmail)) $missingFields[] = 'guestEmail';
    if (empty($bookingRef)) $missingFields[] = 'bookingRef';

    error_log("Missing fields: " . implode(', ', $missingFields));
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Missing required information: ' . implode(', ', $missingFields),
        'debug' => 'Received data: ' . json_encode($input)
    ]);
    ob_end_flush();
    exit;
}

// Validate email
if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    ob_end_flush();
    exit;
}

try {
    // Download QR code image
    $qrImageData = @file_get_contents($qrCodeUrl);

    if ($qrImageData === false) {
        throw new Exception('Failed to download QR code image. Please check your internet connection.');
    }

    // Save QR code temporarily
    $tempQrPath = 'temp/qr_' . $bookingRef . '_' . time() . '.png';
    $tempDir = __DIR__ . '/temp';

    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    file_put_contents(__DIR__ . '/' . $tempQrPath, $qrImageData);

    // Email configuration
    $fromEmail = 'luviorahotel@gmail.com';
    $fromName = 'Luviora Hotel';
    $subject = "Your Digital Room Key - Booking #{$bookingRef}";
    
    // Email body (HTML)
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                margin: 20px;
            }
            .header {
                background: linear-gradient(135deg, #a0522d 0%, #8b4513 100%);
                color: white;
                padding: 30px;
                text-align: center;
                border-radius: 10px 10px 0 0;
                margin: -30px -30px 30px -30px;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
            }
            .booking-ref {
                background: #d4a574;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 8px;
                font-size: 20px;
                font-weight: bold;
                margin: 20px 0;
                letter-spacing: 2px;
            }
            .info-box {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #a0522d;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #e0e0e0;
            }
            .info-row:last-child {
                border-bottom: none;
            }
            .label {
                font-weight: 600;
                color: #666;
            }
            .value {
                color: #333;
                font-weight: 500;
            }
            .qr-section {
                text-align: center;
                padding: 30px;
                background: #f9f9f9;
                border-radius: 8px;
                margin: 20px 0;
            }
            .qr-section h2 {
                color: #a0522d;
                margin-bottom: 15px;
            }
            .instructions {
                background: #fff3cd;
                border: 1px solid #ffc107;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .instructions h3 {
                color: #856404;
                margin-top: 0;
            }
            .instructions ol {
                margin: 10px 0;
                padding-left: 20px;
            }
            .instructions li {
                margin: 8px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                color: #666;
                font-size: 12px;
                border-top: 1px solid #e0e0e0;
                margin-top: 30px;
            }
            .button {
                display: inline-block;
                background: #a0522d;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Luviora Hotel</h1>
                <p>Your Digital Room Key & Booking Confirmation</p>
            </div>
            
            <p>Dear {$guestName},</p>
            
            <p>Thank you for choosing Luviora Hotel! Your booking has been confirmed.</p>
            
            <div class='booking-ref'>
                📋 Booking Reference: {$bookingRef}
            </div>
            
            <div class='info-box'>
                <h3 style='margin-top: 0; color: #a0522d;'>🔑 Your Reservation Details</h3>
                <div class='info-row'>
                    <span class='label'>Room:</span>
                    <span class='value'>{$roomName}</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Check-In:</span>
                    <span class='value'>{$checkIn} at 2:00 PM</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Check-Out:</span>
                    <span class='value'>{$checkOut} at 11:00 AM</span>
                </div>
            </div>
            
            <div class='qr-section'>
                <h2>📱 Your Digital Room Key</h2>
                <p>Please find your QR code attached to this email.</p>
                <p style='color: #666; font-size: 14px;'>Save this QR code to your mobile device for easy check-in.</p>
            </div>
            
            <div class='instructions'>
                <h3>📋 How to Use Your QR Code:</h3>
                <ol>
                    <li><strong>Save</strong> the attached QR code image to your mobile device</li>
                    <li><strong>Arrive</strong> at the hotel at your scheduled check-in time</li>
                    <li><strong>Show</strong> the QR code at our check-in kiosk or to our staff</li>
                    <li><strong>Access</strong> your room - it will be activated automatically</li>
                    <li><strong>Enjoy</strong> your stay!</li>
                </ol>
                <p style='margin: 15px 0 0 0;'><strong>Note:</strong> Your QR code will be active from {$checkIn} at 2:00 PM</p>
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact us.</p>
            
            <div class='footer'>
                <p><strong>Luviora Hotel</strong></p>
                <p>Email: luviorahotel@gmail.com | Phone: +1 (555) 123-4567</p>
                <p>Thank you for choosing Luviora Hotel. We look forward to welcoming you!</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Plain text version
    $textBody = "Dear {$guestName},\n\n";
    $textBody .= "Thank you for choosing Luviora Hotel! Your booking has been confirmed.\n\n";
    $textBody .= "Booking Reference: {$bookingRef}\n\n";
    $textBody .= "Your Reservation Details:\n";
    $textBody .= "Room: {$roomName}\n";
    $textBody .= "Check-In: {$checkIn} at 2:00 PM\n";
    $textBody .= "Check-Out: {$checkOut} at 11:00 AM\n\n";
    $textBody .= "Your Digital Room Key QR code is attached to this email.\n\n";
    $textBody .= "How to Use Your QR Code:\n";
    $textBody .= "1. Save the attached QR code image to your mobile device\n";
    $textBody .= "2. Arrive at the hotel at your scheduled check-in time\n";
    $textBody .= "3. Show the QR code at our check-in kiosk or to our staff\n";
    $textBody .= "4. Access your room - it will be activated automatically\n";
    $textBody .= "5. Enjoy your stay!\n\n";
    $textBody .= "Note: Your QR code will be active from {$checkIn} at 2:00 PM\n\n";
    $textBody .= "If you have any questions, please contact us at luviorahotel@gmail.com\n\n";
    $textBody .= "Thank you for choosing Luviora Hotel!\n";
    
    // Try to use PHPMailer if available
    $phpMailerPath = __DIR__ . '/vendor/autoload.php';
    $mailSent = false;
    $sendMethod = '';
    $errorMessage = '';

    if (file_exists($phpMailerPath)) {
        // Use PHPMailer
        require_once $phpMailerPath;

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings with timeout
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'luviorahotel@gmail.com';
            $mail->Password   = 'cjnh bphr qvpz wypr';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->Timeout    = 30; // 30 seconds timeout
            $mail->SMTPKeepAlive = false;

            // Disable SSL verification (for local development)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($guestEmail, $guestName);
            $mail->addReplyTo('luviorahotel@gmail.com', 'Luviora Hotel');

            // Attachments
            $mail->addAttachment(__DIR__ . '/' . $tempQrPath, "Luviora-QR-{$bookingRef}.png");

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            $mailSent = true;
            $sendMethod = 'PHPMailer (Gmail SMTP)';

        } catch (Exception $e) {
            error_log("PHPMailer error: {$mail->ErrorInfo}");
            error_log("Exception message: " . $e->getMessage());
            $mailSent = false;
            $errorMessage = $e->getMessage();
        }
    }

    // Fallback to PHP mail() if PHPMailer not available or failed
    if (!$mailSent) {
        $boundary = md5(time());

        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: luviorahotel@gmail.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

        // Build the email message
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $textBody . "\r\n\r\n";

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";

        // Attach QR code image
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: image/png; name=\"Luviora-QR-{$bookingRef}.png\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"Luviora-QR-{$bookingRef}.png\"\r\n\r\n";
        $message .= chunk_split(base64_encode($qrImageData)) . "\r\n";

        $message .= "--{$boundary}--";

        // Try to send email using PHP mail() function
        $mailSent = @mail($guestEmail, $subject, $message, $headers);
        if ($mailSent) {
            $sendMethod = 'PHP mail()';
        }
    }

    // Clean up temporary file
    if (file_exists(__DIR__ . '/' . $tempQrPath)) {
        @unlink(__DIR__ . '/' . $tempQrPath);
    }

    // Clean any unexpected output before sending JSON
    ob_clean();

    if ($mailSent) {
        echo json_encode([
            'success' => true,
            'message' => "QR code has been sent to {$guestEmail}. Please check your inbox and spam folder.",
            'method' => $sendMethod
        ]);
        ob_end_flush();
    } else {
        // If all methods fail, save to file as backup AND show helpful error
        $emailsDir = __DIR__ . '/emails';
        if (!is_dir($emailsDir)) {
            mkdir($emailsDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $emailFile = $emailsDir . "/email_{$bookingRef}_{$timestamp}.html";
        $qrFile = $emailsDir . "/qr_{$bookingRef}_{$timestamp}.png";

        // Save QR code
        file_put_contents($qrFile, $qrImageData);

        // Save email HTML
        $emailContent = "<!-- EMAIL DETAILS -->\n";
        $emailContent .= "<!-- To: {$guestEmail} -->\n";
        $emailContent .= "<!-- From: {$fromName} <{$fromEmail}> -->\n";
        $emailContent .= "<!-- Subject: {$subject} -->\n";
        $emailContent .= "<!-- QR Code: qr_{$bookingRef}_{$timestamp}.png -->\n";
        $emailContent .= "<!-- Timestamp: " . date('Y-m-d H:i:s') . " -->\n\n";
        $emailContent .= $htmlBody;

        file_put_contents($emailFile, $emailContent);

        // Provide detailed error message
        $errorMsg = isset($errorMessage) ? $errorMessage : 'Email server connection failed';

        echo json_encode([
            'success' => true,
            'message' => "✅ QR Code saved! Email sending temporarily unavailable.\n\nYour QR code has been saved and you can download it from the confirmation page.\n\nNote: Email server is currently unreachable. This may be due to:\n• Internet connection issues\n• Firewall blocking SMTP\n• Gmail security settings\n\nYour booking is confirmed and QR code is ready to use!",
            'method' => 'File Save (Email server unavailable)',
            'saved_to' => "emails/qr_{$bookingRef}_{$timestamp}.png",
            'error_detail' => $errorMsg
        ]);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log("Email sending error: " . $e->getMessage());

    // Clean any unexpected output before sending JSON
    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Flush the output buffer
ob_end_flush();
?>

