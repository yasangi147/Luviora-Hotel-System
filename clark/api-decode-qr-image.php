<?php
require_once '../config/database.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

$db = getDB();
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_FILES['qr_image'])) {
        throw new Exception('No image file provided');
    }

    $uploadedFile = $_FILES['qr_image'];

    // Validate file
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $uploadedFile['error']);
    }

    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!in_array($uploadedFile['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Please upload PNG, JPG, or JPEG');
    }

    // Move to temp directory
    $tempPath = sys_get_temp_dir() . '/' . uniqid('qr_') . '_' . basename($uploadedFile['name']);
    if (!move_uploaded_file($uploadedFile['tmp_name'], $tempPath)) {
        throw new Exception('Failed to process uploaded image');
    }

    // Try to decode QR code using multiple methods
    $qrData = null;

    // Method 1: Try using PHP-QR-Code library if available
    if (function_exists('imagecreatefromstring')) {
        $qrData = decodeQRCodeFromImage($tempPath);
    }

    // Method 2: If Method 1 fails, try using online QR decoder API
    if (!$qrData) {
        $qrData = decodeQRCodeViaAPI($tempPath);
    }

    // Clean up temp file
    @unlink($tempPath);

    if (!$qrData) {
        error_log("QR decoding failed for file: " . $uploadedFile['name']);
        throw new Exception('Could not decode QR code from image. The QR code may be unclear, damaged, or the image quality is too low. Please try: 1) Uploading a clearer image, 2) Ensuring good lighting when taking the photo, 3) Using a different QR code image.');
    }

    // Parse the QR data
    $bookingRef = '';
    $qrDataParsed = json_decode($qrData, true);

    if ($qrDataParsed && isset($qrDataParsed['booking_reference'])) {
        $bookingRef = $qrDataParsed['booking_reference'];
    } elseif ($qrDataParsed && isset($qrDataParsed['type']) && $qrDataParsed['type'] === 'LUVIORA_BOOKING') {
        $bookingRef = $qrDataParsed['booking_ref'];
    } elseif ($qrDataParsed && isset($qrDataParsed['booking_ref'])) {
        $bookingRef = $qrDataParsed['booking_ref'];
    } else {
        $bookingRef = $qrData;
    }

    if (empty($bookingRef)) {
        throw new Exception('Could not extract booking reference from QR code');
    }

    // Find booking and verify
    $stmt = $db->prepare("
        SELECT b.*, u.name as guest_name, u.email, u.phone, r.room_number, r.room_name, r.room_type, r.floor,
               qr.qr_id, qr.qr_code_data, qr.qr_code_hash, qr.status as qr_status, qr.expiry_time
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN qr_codes qr ON b.booking_id = qr.booking_id
        WHERE b.booking_reference = ?
    ");
    $stmt->execute([$bookingRef]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking not found for reference: ' . $bookingRef);
    }

    $response['success'] = true;
    $response['message'] = 'QR code decoded successfully';
    $response['booking_ref'] = $bookingRef;  // Return booking reference for next step
    $response['data'] = [
        'booking_id' => $booking['booking_id'],
        'booking_reference' => $booking['booking_reference'],
        'guest_name' => $booking['guest_name'],
        'email' => $booking['email'],
        'phone' => $booking['phone'],
        'room_id' => $booking['room_id'],
        'room_number' => $booking['room_number'],
        'room_name' => $booking['room_name'],
        'room_type' => $booking['room_type'],
        'floor' => $booking['floor'],
        'check_in_date' => $booking['check_in_date'],
        'check_out_date' => $booking['check_out_date'],
        'total_nights' => $booking['total_nights'],
        'total_amount' => $booking['total_amount'],
        'booking_status' => $booking['booking_status'],
        'payment_status' => $booking['payment_status'],
        'qr_valid' => $booking['qr_id'] ? true : false
    ];

} catch (Exception $e) {
    error_log("QR Image Decode Error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

// Function to decode QR code from image using GD library
function decodeQRCodeFromImage($imagePath) {
    // This is a placeholder - actual QR decoding requires external library
    // For now, we'll use the API method
    return null;
}

// Function to decode QR code using online API
function decodeQRCodeViaAPI($imagePath) {
    try {
        // Read image file
        $imageData = file_get_contents($imagePath);
        if (!$imageData) {
            error_log("Failed to read image file: " . $imagePath);
            return null;
        }

        // Method 1: Try using cURL with file upload
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.qrserver.com/v1/read-qr-code/',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => new CURLFile($imagePath)],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("QR API Response Code: " . $httpCode);
        error_log("QR API Response: " . substr($response, 0, 500));

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if ($result && isset($result[0]['symbol'][0]['data'])) {
                $decodedData = $result[0]['symbol'][0]['data'];
                error_log("Successfully decoded QR code: " . substr($decodedData, 0, 100));
                return $decodedData;
            }
        }

        // Method 2: If Method 1 fails, try base64 encoding
        error_log("Method 1 failed, trying Method 2 with base64");
        $base64Image = base64_encode($imageData);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.qrserver.com/v1/read-qr-code/',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['file' => 'data:image/png;base64,' . $base64Image]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if ($result && isset($result[0]['symbol'][0]['data'])) {
                $decodedData = $result[0]['symbol'][0]['data'];
                error_log("Successfully decoded QR code via base64: " . substr($decodedData, 0, 100));
                return $decodedData;
            }
        }

        error_log("Both methods failed. HTTP Code: " . $httpCode . ", cURL Error: " . $curlError);
        return null;
    } catch (Exception $e) {
        error_log("QR API Decode Error: " . $e->getMessage());
        return null;
    }
}
?>

