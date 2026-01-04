<?php
/**
 * Email Configuration
 * Configure SMTP settings for sending emails
 */

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');  // Gmail SMTP host
define('SMTP_PORT', 587);                // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'luviorahotel@gmail.com');  // Your Gmail address
define('SMTP_PASSWORD', 'bdsn soso sqkw mxpt');     // Gmail App Password
define('SMTP_ENCRYPTION', 'tls');        // 'tls' or 'ssl'

// Sender information
define('FROM_EMAIL', 'luviorahotel@gmail.com');
define('FROM_NAME', 'Luviora Hotel');
define('REPLY_TO_EMAIL', 'luviorahotel@gmail.com');
define('REPLY_TO_NAME', 'Luviora Hotel Support');

// Email templates directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../email_templates/');

/**
 * Send email using PHPMailer
 * 
 * @param string $to Recipient email
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @param array $attachments Array of file paths to attach
 * @return bool Success status
 */
function sendEmail($to, $toName, $subject, $body, $attachments = []) {
    // Load PHPMailer classes directly
    $phpMailerDir = __DIR__ . '/../vendor/phpmailer/phpmailer/';

    if (!file_exists($phpMailerDir . 'PHPMailer.php')) {
        // Fallback to PHP mail() function
        return sendEmailFallback($to, $toName, $subject, $body);
    }

    // Require PHPMailer files directly
    require_once $phpMailerDir . 'Exception.php';
    require_once $phpMailerDir . 'PHPMailer.php';
    require_once $phpMailerDir . 'SMTP.php';

    // Use full class names
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->addReplyTo(REPLY_TO_EMAIL, REPLY_TO_NAME);
        
        // Attachments
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            }
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;

    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    } catch (\Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Fallback email function using PHP mail()
 */
function sendEmailFallback($to, $toName, $subject, $body) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . REPLY_TO_EMAIL . "\r\n";
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Send booking confirmation email
 * @param array $bookingData Booking information
 * @param string $qrCodeUrl URL or path to QR code image
 */
function sendBookingConfirmationEmail($bookingData, $qrCodeUrl) {
    $subject = "Booking Confirmation - " . $bookingData['booking_reference'];

    // Download QR code from URL if it's a URL
    $qrImageData = null;
    $tempQrPath = null;

    if (filter_var($qrCodeUrl, FILTER_VALIDATE_URL)) {
        // It's a URL, download the image
        $qrImageData = @file_get_contents($qrCodeUrl);

        if ($qrImageData !== false) {
            // Save temporarily
            $tempDir = __DIR__ . '/../temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempQrPath = $tempDir . '/qr_' . $bookingData['booking_reference'] . '_' . time() . '.png';
            file_put_contents($tempQrPath, $qrImageData);
        }
    } else {
        // It's a file path
        $tempQrPath = __DIR__ . '/../' . $qrCodeUrl;
    }

    $body = getBookingConfirmationTemplate($bookingData, $qrCodeUrl);

    $attachments = [];
    if ($tempQrPath && file_exists($tempQrPath)) {
        $attachments[] = $tempQrPath;
    }

    $result = sendEmail(
        $bookingData['guest_email'],
        $bookingData['guest_name'],
        $subject,
        $body,
        $attachments
    );

    // Clean up temporary file if we created one
    if ($qrImageData !== null && $tempQrPath && file_exists($tempQrPath)) {
        @unlink($tempQrPath);
    }

    return $result;
}

/**
 * Get booking confirmation email template
 */
function getBookingConfirmationTemplate($data, $qrImagePath) {
    // Check if it's already a URL or a file path
    if (filter_var($qrImagePath, FILTER_VALIDATE_URL)) {
        $qrImageUrl = $qrImagePath;
    } else {
        $qrImageUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . dirname($_SERVER['PHP_SELF']) . '/../' . $qrImagePath;
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background: #ffffff;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #a0522d 0%, #8b4513 100%);
                color: #ffffff;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 700;
            }
            .header p {
                margin: 5px 0 0;
                font-size: 14px;
                opacity: 0.9;
            }
            .content {
                padding: 30px 20px;
            }
            .greeting {
                font-size: 18px;
                color: #a0522d;
                margin-bottom: 20px;
            }
            .booking-details {
                background: #faf8f5;
                border-left: 4px solid #a0522d;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
            }
            .booking-details h3 {
                margin-top: 0;
                color: #a0522d;
                font-size: 18px;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e0e0e0;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .detail-label {
                font-weight: 600;
                color: #666;
            }
            .detail-value {
                color: #333;
                text-align: right;
            }
            .qr-section {
                text-align: center;
                padding: 30px 20px;
                background: #f9f9f9;
                margin: 20px 0;
                border-radius: 5px;
            }
            .qr-section h3 {
                color: #a0522d;
                margin-bottom: 15px;
            }
            .qr-section img {
                max-width: 250px;
                border: 3px solid #a0522d;
                border-radius: 10px;
                padding: 10px;
                background: white;
            }
            .qr-section p {
                color: #666;
                font-size: 14px;
                margin-top: 15px;
            }
            .info-box {
                background: #fff3cd;
                border: 1px solid #ffc107;
                padding: 15px;
                margin: 20px 0;
                border-radius: 5px;
            }
            .info-box h4 {
                margin-top: 0;
                color: #856404;
            }
            .info-box ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            .info-box li {
                color: #856404;
                margin: 5px 0;
            }
            .footer {
                background: #333;
                color: #ffffff;
                text-align: center;
                padding: 20px;
                font-size: 14px;
            }
            .footer p {
                margin: 5px 0;
            }
            .footer a {
                color: #d4a574;
                text-decoration: none;
            }
            .total-amount {
                background: #a0522d;
                color: white;
                padding: 15px;
                text-align: center;
                font-size: 24px;
                font-weight: 700;
                margin: 20px 0;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Booking Confirmed!</h1>
                <p>Luviora Hotel - Luxury Redefined</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    Dear {$data['guest_name']},
                </div>
                
                <p>Thank you for choosing Luviora Hotel! We're delighted to confirm your reservation.</p>
                
                <div class='booking-details'>
                    <h3>📋 Booking Details</h3>
                    <div class='detail-row'>
                        <span class='detail-label'>Booking Reference:</span>
                        <span class='detail-value'><strong>{$data['booking_reference']}</strong></span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Room:</span>
                        <span class='detail-value'>{$data['room_name']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Room Number:</span>
                        <span class='detail-value'>{$data['room_number']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Check-in:</span>
                        <span class='detail-value'>{$data['check_in']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Check-out:</span>
                        <span class='detail-value'>{$data['check_out']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Guests:</span>
                        <span class='detail-value'>{$data['num_adults']} Adults, {$data['num_children']} Children</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Nights:</span>
                        <span class='detail-value'>{$data['nights']}</span>
                    </div>
                </div>
                
                <div class='total-amount'>
                    Total Amount: \${$data['total_amount']}
                </div>
                
                <div class='qr-section'>
                    <h3>🎫 Your Check-in QR Code</h3>
                    <img src='{$qrImageUrl}' alt='Check-in QR Code' />
                    <p>Present this QR code at our front desk for a quick and contactless check-in experience.</p>
                </div>
                
                <div class='info-box'>
                    <h4>📌 Important Information</h4>
                    <ul>
                        <li><strong>Check-in Time:</strong> 2:00 PM</li>
                        <li><strong>Check-out Time:</strong> 12:00 PM (Noon)</li>
                        <li><strong>Cancellation Policy:</strong> Free cancellation up to 24 hours before check-in</li>
                        <li><strong>ID Requirement:</strong> Valid government-issued ID required at check-in</li>
                        <li><strong>Early Check-in:</strong> Subject to availability (additional charges may apply)</li>
                        <li><strong>Late Check-out:</strong> Available upon request (additional charges may apply)</li>
                    </ul>
                </div>
                
                <p style='margin-top: 30px;'>
                    If you have any questions or special requests, please don't hesitate to contact us.
                </p>
                
                <p>We look forward to welcoming you to Luviora Hotel!</p>
                
                <p style='margin-top: 20px;'>
                    <strong>Warm regards,</strong><br>
                    The Luviora Hotel Team
                </p>
            </div>
            
            <div class='footer'>
                <p><strong>Luviora Hotel</strong></p>
                <p>23/B Galle Road, Colombo, Sri Lanka</p>
                <p>Tel: <a href='tel:+94082123456'>+94 082 1234 567</a> | Email: <a href='mailto:info@luviorahotel.com'>info@luviorahotel.com</a></p>
                <p style='margin-top: 15px; font-size: 12px; opacity: 0.8;'>
                    © 2025 Luviora Hotel. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>

