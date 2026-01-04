<?php
/**
 * QR Code Management - Active QR Codes
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'active';
$searchQuery = $_GET['search'] ?? '';

// Build query with CORRECT column names from database
$query = "
    SELECT qr.*, b.booking_reference, b.check_in_date, b.check_out_date, b.booking_status,
           u.name as guest_name, u.email as guest_email,
           r.room_name, r.room_number
    FROM qr_codes qr
    JOIN bookings b ON qr.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE 1=1
";

$params = [];

if ($statusFilter === 'active') {
    $query .= " AND qr.status = 'active' AND qr.expiry_time > NOW()";
} elseif ($statusFilter === 'expired') {
    $query .= " AND (qr.status = 'expired' OR qr.expiry_time <= NOW())";
} elseif ($statusFilter === 'all') {
    // No additional filter - show all
}

if ($searchQuery) {
    $query .= " AND (b.booking_reference LIKE ? OR u.name LIKE ? OR r.room_number LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY qr.generated_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$qrCodes = $stmt->fetchAll();

// Get statistics with CORRECT column names
$stmt = $db->query("SELECT COUNT(*) as total FROM qr_codes WHERE status = 'active' AND expiry_time > NOW()");
$activeQRCodes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM qr_codes WHERE status = 'expired' OR expiry_time <= NOW()");
$expiredQRCodes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM qr_codes WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$todayQRCodes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM qr_codes");
$totalQRCodes = $stmt->fetch()['total'];

$pageTitle = "QR Code Management";
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
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .qr-code-preview {
            width: 60px;
            height: 60px;
            border: 2px solid var(--gray-300);
            border-radius: 8px;
            padding: 5px;
            background: white;
        }
        
        .qr-code-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .qr-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
        }
        
        .btn-revoke {
            background: #dc3545;
            color: white;
        }
        
        .btn-view:hover { background: #138496; color: white; }
        .btn-download:hover { background: #218838; color: white; }
        .btn-revoke:hover { background: #c82333; color: white; }
        
        .validity-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .validity-active {
            background: #d4edda;
            color: #155724;
        }
        
        .validity-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .validity-expiring {
            background: #fff3cd;
            color: #856404;
        }
        
        /* Table styling improvements */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 12px 15px;
            white-space: nowrap;
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
        }
        
        /* Column width controls */
        .table th.validity-column,
        .table td.validity-column {
            min-width: 200px;
            max-width: 200px;
        }
        
        .validity-dates {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .validity-date {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--gray-700);
        }
        
        .validity-date i {
            width: 16px;
            color: var(--gray-600);
        }
        
        .validity-date strong {
            color: var(--dark);
            margin-right: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-qrcode"></i> QR Code Management</h1>
                        <p>Manage digital room keys and access codes</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-qrcode text-primary"></i>
                            <div>
                                <h4><?php echo $totalQRCodes; ?></h4>
                                <p>Total QR Codes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-check-circle text-success"></i>
                            <div>
                                <h4><?php echo $activeQRCodes; ?></h4>
                                <p>Active Codes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-calendar-day text-info"></i>
                            <div>
                                <h4><?php echo $todayQRCodes; ?></h4>
                                <p>Generated Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-times-circle text-danger"></i>
                            <div>
                                <h4><?php echo $expiredQRCodes; ?></h4>
                                <p>Expired/Revoked</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-5" style="width: 555px;">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by booking reference, guest name, or room number..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Codes</option>
                                    <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired/Revoked</option>
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Codes</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- QR Codes Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> QR Codes (<?php echo count($qrCodes); ?>)</h3>
                        <div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>QR Code</th>
                                    <th>Booking</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th class="validity-column">Validity Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($qrCodes)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-qrcode" style="font-size: 48px; color: var(--gray-400);"></i>
                                        <p style="margin-top: 15px; color: var(--gray-600);">No QR codes found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($qrCodes as $qr): ?>
                                <?php
                                    $isActive = $qr['status'] === 'active' && strtotime($qr['expiry_time']) > time();
                                    $isExpiringSoon = $isActive && strtotime($qr['expiry_time']) < strtotime('+24 hours');
                                    $validityClass = !$isActive ? 'validity-expired' : ($isExpiringSoon ? 'validity-expiring' : 'validity-active');
                                    $validityText = !$isActive ? 'Expired/Revoked' : ($isExpiringSoon ? 'Expiring Soon' : 'Active');
                                    
                                    // QR image path - use external API if no local path
                                    $qrImageSrc = !empty($qr['qr_image_path']) ? '../' . htmlspecialchars($qr['qr_image_path']) : 
                                                  'https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=' . urlencode($qr['qr_code_data']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="qr-info">
                                            <div class="qr-code-preview">
                                                <img src="<?php echo $qrImageSrc; ?>" 
                                                     alt="QR Code" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22%3EQR%3C/text%3E%3C/svg%3E'">
                                            </div>
                                            <div>
                                                <strong>QR-<?php echo $qr['qr_id']; ?></strong><br>
                                                <small style="color: var(--gray-600);">
                                                    Created: <?php echo date('M d, Y H:i', strtotime($qr['generated_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($qr['booking_reference']); ?></strong><br>
                                        <span class="badge badge-<?php echo $qr['booking_status']; ?>">
                                            <?php echo ucfirst($qr['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($qr['guest_name']); ?><br>
                                        <small style="color: var(--gray-600);"><?php echo htmlspecialchars($qr['guest_email']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($qr['room_name'] ?? 'N/A'); ?></strong><br>
                                        <small style="color: var(--gray-600);">Room <?php echo htmlspecialchars($qr['room_number'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td class="validity-column">
                                        <div class="validity-dates">
                                            <div class="validity-date">
                                                <i class="far fa-calendar-plus"></i>
                                                <span><strong>From:</strong> <?php echo date('M d, Y', strtotime($qr['check_in_date'])); ?></span>
                                            </div>
                                            <div class="validity-date">
                                                <i class="far fa-calendar-minus"></i>
                                                <span><strong>Until:</strong> <?php echo date('M d, Y H:i', strtotime($qr['expiry_time'])); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="validity-indicator <?php echo $validityClass; ?>">
                                            <i class="fas fa-circle" style="font-size: 6px;"></i>
                                            <?php echo $validityText; ?>
                                        </span>
                                    </td>
                                   
                                    <td>
                                        <div class="action-buttons">
                                            <a href="qr-details.php?id=<?php echo $qr['qr_id']; ?>"
                                               class="action-btn btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="api-download-qr.php?id=<?php echo $qr['qr_id']; ?>"
                                               class="action-btn btn-download" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php if ($isActive): ?>
                                            <button onclick="revokeQR(<?php echo $qr['qr_id']; ?>)"
                                                    class="action-btn btn-revoke" title="Revoke">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function revokeQR(qrId) {
            if (confirm('Are you sure you want to revoke this QR code? This action cannot be undone.')) {
                alert('QR Code revocation functionality will be implemented in the backend.');
            }
        }
    </script>
</body>
</html>

