<?php
/**
 * Download QR Code Image
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$qrId = intval($_GET['id'] ?? 0);

if (!$qrId) {
    http_response_code(400);
    die('Invalid QR Code ID');
}

// Get QR code details
$stmt = $db->prepare("
    SELECT qr.*, b.booking_reference
    FROM qr_codes qr
    JOIN bookings b ON qr.booking_id = b.booking_id
    WHERE qr.qr_id = ?
");
$stmt->execute([$qrId]);
$qr = $stmt->fetch();

if (!$qr) {
    http_response_code(404);
    die('QR Code not found');
}

// Generate QR code image from API
$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr['qr_code_data']);

// Download the image from QR Server API
$imageData = @file_get_contents($qrImageUrl);

if (!$imageData) {
    http_response_code(500);
    die('Failed to generate QR code image');
}

// Set headers for download
$filename = 'QR-' . $qr['booking_reference'] . '-' . date('Y-m-d-His') . '.png';

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output the image data
echo $imageData;
exit;
?>

