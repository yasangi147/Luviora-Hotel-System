<?php
/**
 * QR Code Details - View detailed QR code information
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$qrId = intval($_GET['id'] ?? 0);

if (!$qrId) {
    header('Location: qr-codes.php');
    exit;
}

// Get QR code details
$stmt = $db->prepare("
    SELECT qr.*, 
           b.booking_reference, b.check_in_date, b.check_out_date, b.booking_status, 
           b.payment_status, b.total_nights, b.total_amount, b.special_requests,
           u.name as guest_name, u.email as guest_email, u.phone as guest_phone,
           r.room_name, r.room_number, r.room_type, r.floor
    FROM qr_codes qr
    JOIN bookings b ON qr.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE qr.qr_id = ?
");
$stmt->execute([$qrId]);
$qr = $stmt->fetch();

if (!$qr) {
    header('Location: qr-codes.php');
    exit;
}

// Get scan history
$stmt = $db->prepare("
    SELECT * FROM qr_codes 
    WHERE qr_id = ?
    ORDER BY last_scanned_at DESC
");
$stmt->execute([$qrId]);
$qrData = $stmt->fetch();

$pageTitle = "QR Code Details";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Luviora Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <style>
        .detail-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
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
        }
        
        .qr-code-display {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f5f1eb 0%, #faf8f5 100%);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .qr-code-display img {
            max-width: 300px;
            border: 3px solid var(--primary-color);
            border-radius: 8px;
            padding: 10px;
            background: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-used {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #e2e3e5;
            color: #383d41;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1><i class="fas fa-qrcode"></i> QR Code Details</h1>
                    <div style="display: flex; justify-content: flex-end;">
                        <a href="qr-codes.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to QR Codes
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <!-- QR Code Display -->
                    <div class="col-md-4">
                        <div class="qr-code-display">
                            <h5 style="margin-bottom: 20px;">QR Code Image</h5>
                            <?php
                                // Always use API to generate QR code for display
                                $qrImageSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr['qr_code_data']);
                            ?>
                            <img src="<?php echo $qrImageSrc; ?>" alt="QR Code" style="max-width: 300px; border: 3px solid var(--primary-color); border-radius: 8px; padding: 10px; background: white;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 300 300%22%3E%3Crect fill=%22%23ddd%22 width=%22300%22 height=%22300%22/%3E%3Ctext x=%22150%22 y=%22150%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2224%22%3EQR Code%3C/text%3E%3C/svg%3E'">
                            <div style="margin-top: 20px;">
                                <a href="api-download-qr.php?id=<?php echo $qr['qr_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- QR Code Information -->
                    <div class="col-md-8">
                        <!-- QR Code Status -->
                        <div class="detail-card">
                            <h5 style="margin-bottom: 20px;"><i class="fas fa-info-circle"></i> QR Code Information</h5>
                            <div class="detail-row">
                                <span class="detail-label">QR Code ID:</span>
                                <span class="detail-value"><strong>QR-<?php echo $qr['qr_id']; ?></strong></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value">
                                    <span class="status-badge status-<?php echo $qr['status']; ?>">
                                        <?php echo ucfirst($qr['status']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Generated:</span>
                                <span class="detail-value"><?php echo date('M d, Y H:i:s', strtotime($qr['generated_at'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Expires:</span>
                                <span class="detail-value"><?php echo date('M d, Y H:i:s', strtotime($qr['expiry_time'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Scan Count:</span>
                                <span class="detail-value"><strong><?php echo $qr['scan_count']; ?></strong> scans</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Last Scanned:</span>
                                <span class="detail-value">
                                    <?php echo $qr['last_scanned_at'] ? date('M d, Y H:i:s', strtotime($qr['last_scanned_at'])) : 'Never'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Booking Information -->
                        <div class="detail-card">
                            <h5 style="margin-bottom: 20px;"><i class="fas fa-file-alt"></i> Booking Information</h5>
                            <div class="detail-row">
                                <span class="detail-label">Booking Reference:</span>
                                <span class="detail-value"><strong><?php echo htmlspecialchars($qr['booking_reference']); ?></strong></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Booking Status:</span>
                                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $qr['booking_status'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment Status:</span>
                                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $qr['payment_status'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Check-in Date:</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($qr['check_in_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Check-out Date:</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($qr['check_out_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total Nights:</span>
                                <span class="detail-value"><?php echo $qr['total_nights']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total Amount:</span>
                                <span class="detail-value"><strong>$<?php echo number_format($qr['total_amount'], 2); ?></strong></span>
                            </div>
                        </div>
                        
                        <!-- Guest Information -->
                        <div class="detail-card">
                            <h5 style="margin-bottom: 20px;"><i class="fas fa-user"></i> Guest Information</h5>
                            <div class="detail-row">
                                <span class="detail-label">Guest Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($qr['guest_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($qr['guest_email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($qr['guest_phone'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        
                        <!-- Room Information -->
                        <div class="detail-card">
                            <h5 style="margin-bottom: 20px;"><i class="fas fa-door-open"></i> Room Information</h5>
                            <div class="detail-row">
                                <span class="detail-label">Room Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($qr['room_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Room Number:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($qr['room_number'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Room Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($qr['room_type'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Floor:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($qr['floor'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

