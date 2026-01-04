<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$roomId = $_GET['id'] ?? null;

if (!$roomId) {
    header('Location: rooms.php');
    exit;
}

// Get room details with specs
$stmt = $db->prepare("
    SELECT r.*, 
           GROUP_CONCAT(rs.spec_name SEPARATOR '|') as amenities,
           GROUP_CONCAT(rs.spec_icon SEPARATOR '|') as amenity_icons
    FROM rooms r
    LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
    LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
    WHERE r.room_id = ?
    GROUP BY r.room_id
");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    header('Location: rooms.php');
    exit;
}

// Get booking history for this room
$bookingHistory = $db->prepare("
    SELECT b.*, u.name as guest_name, u.email as guest_email, u.phone as guest_phone
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    WHERE b.room_id = ?
    ORDER BY b.check_in_date DESC
    LIMIT 10
");
$bookingHistory->execute([$roomId]);
$bookings = $bookingHistory->fetchAll();

// Get maintenance history
try {
    $maintenanceHistory = $db->prepare("
        SELECT mi.*, u.name as reported_by_name
        FROM maintenance_issues mi
        JOIN users u ON mi.reported_by = u.user_id
        WHERE mi.room_id = ?
        ORDER BY mi.reported_at DESC
        LIMIT 5
    ");
    $maintenanceHistory->execute([$roomId]);
    $maintenanceRecords = $maintenanceHistory->fetchAll();
} catch (PDOException $e) {
    $maintenanceRecords = [];
}

// Split amenities
$amenitiesList = !empty($room['amenities']) ? explode('|', $room['amenities']) : [];
$amenityIcons = !empty($room['amenity_icons']) ? explode('|', $room['amenity_icons']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Details - <?php echo htmlspecialchars($room['room_number']); ?> | Luviora Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <style>
        .room-image-large {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .info-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
            font-weight: 500;
        }
        .amenity-badge {
            display: inline-block;
            padding: 8px 15px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            margin: 5px;
            font-size: 14px;
        }
        .amenity-badge i {
            color: var(--primary-color);
            margin-right: 5px;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-available { background: #28a745; }
        .status-occupied { background: #dc3545; }
        .status-maintenance { background: #ffc107; }
        .status-cleaning { background: #17a2b8; }
        .status-reserved { background: #6f42c1; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1><i class="fas fa-door-open"></i> Room Details - <?php echo htmlspecialchars($room['room_number']); ?></h1>
                    <div>
                        <a href="room-form.php?id=<?php echo $room['room_id']; ?>" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Edit Room
                        </a>
                        <a href="rooms.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Room Image -->
                    <div class="col-lg-8">
                        <div class="info-card">
                            <?php if (!empty($room['room_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($room['room_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($room['room_name']); ?>" 
                                     class="room-image-large"
                                     onerror="this.src='../images/placeholder-room.jpg'">
                            <?php else: ?>
                                <div class="room-image-large" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-bed" style="font-size: 80px; color: white; opacity: 0.5;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Room Description -->
                        <div class="info-card">
                            <h3><i class="fas fa-info-circle"></i> Description</h3>
                            <p style="color: #666; line-height: 1.8; margin-top: 15px;">
                                <?php echo nl2br(htmlspecialchars($room['description'] ?? 'No description available.')); ?>
                            </p>
                        </div>
                        
                        <!-- Amenities -->
                        <div class="info-card">
                            <h3><i class="fas fa-star"></i> Amenities & Features</h3>
                            <div style="margin-top: 15px;">
                                <?php if (!empty($amenitiesList)): ?>
                                    <?php foreach ($amenitiesList as $index => $amenity): ?>
                                        <span class="amenity-badge">
                                            <i class="<?php echo $amenityIcons[$index] ?? 'fas fa-check'; ?>"></i>
                                            <?php echo htmlspecialchars($amenity); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="color: #999;">No amenities listed</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Booking History -->
                        <div class="info-card">
                            <h3><i class="fas fa-history"></i> Recent Booking History</h3>
                            <div class="table-responsive" style="margin-top: 15px;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Guest</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; padding: 30px; color: #999;">
                                                No booking history available
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($booking['guest_email']); ?></small>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $booking['booking_status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $booking['booking_status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Maintenance History -->
                        <?php if (!empty($maintenanceRecords)): ?>
                        <div class="info-card">
                            <h3><i class="fas fa-tools"></i> Maintenance History</h3>
                            <div class="table-responsive" style="margin-top: 15px;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Issue</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Reported</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenanceRecords as $issue): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($issue['issue_title']); ?></strong><br>
                                                <small>By: <?php echo htmlspecialchars($issue['reported_by_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $issue['priority'] === 'urgent' ? 'danger' : 
                                                        ($issue['priority'] === 'high' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($issue['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $issue['status'] === 'completed' ? 'success' : 'secondary'; 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($issue['reported_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Room Information Sidebar -->
                    <div class="col-lg-4">
                        <div class="info-card">
                            <h3><i class="fas fa-info"></i> Room Information</h3>
                            <div style="margin-top: 20px;">
                                <div class="info-row">
                                    <span class="info-label">Room Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($room['room_number']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Room Name</span>
                                    <span class="info-value"><?php echo htmlspecialchars($room['room_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Room Type</span>
                                    <span class="info-value"><?php echo htmlspecialchars($room['room_type']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Floor</span>
                                    <span class="info-value">Floor <?php echo $room['floor']; ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Status</span>
                                    <span class="info-value">
                                        <span class="status-indicator status-<?php echo $room['status']; ?>"></span>
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-dollar-sign"></i> Pricing & Capacity</h3>
                            <div style="margin-top: 20px;">
                                <div class="info-row">
                                    <span class="info-label">Price per Night</span>
                                    <span class="info-value" style="color: #28a745; font-size: 18px; font-weight: 700;">
                                        $<?php echo number_format($room['price_per_night'], 2); ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Max Occupancy</span>
                                    <span class="info-value">
                                        <i class="fas fa-users"></i> <?php echo $room['max_occupancy']; ?> Guests
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Room Size</span>
                                    <span class="info-value"><?php echo $room['size_sqm']; ?> m²</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Bed Type</span>
                                    <span class="info-value">
                                        <i class="fas fa-bed"></i> <?php echo htmlspecialchars($room['bed_type']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-calendar"></i> Dates</h3>
                            <div style="margin-top: 20px;">
                                <div class="info-row">
                                    <span class="info-label">Created</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($room['created_at'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Last Updated</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($room['updated_at'])); ?></span>
                                </div>
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

