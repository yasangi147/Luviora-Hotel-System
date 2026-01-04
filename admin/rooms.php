<?php
/**
 * Rooms Management - All Rooms
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();
$message = '';
$messageType = '';

// Handle room actions (deactivate, activate, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_id = $_POST['room_id'] ?? 0;

    try {
        if ($action === 'deactivate' && $room_id) {
            // Check if room is available (not occupied)
            $stmt = $db->prepare("SELECT status FROM rooms WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $room = $stmt->fetch();

            if ($room && $room['status'] !== 'available') {
                $message = 'Cannot deactivate room. Room must be available (not occupied, reserved, or in maintenance).';
                $messageType = 'danger';
            } else {
                $stmt = $db->prepare("UPDATE rooms SET is_active = FALSE WHERE room_id = ?");
                $stmt->execute([$room_id]);
                $message = 'Room deactivated successfully!';
                $messageType = 'success';
            }
        } elseif ($action === 'activate' && $room_id) {
            $stmt = $db->prepare("UPDATE rooms SET is_active = TRUE WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $message = 'Room activated successfully!';
            $messageType = 'success';
        } elseif ($action === 'delete' && $room_id) {
            // Check if room has any bookings
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $bookingCount = $stmt->fetch()['count'];

            if ($bookingCount > 0) {
                $message = 'Cannot delete room with existing bookings. Please deactivate instead.';
                $messageType = 'danger';
            } else {
                // Delete room spec mappings first
                $stmt = $db->prepare("DELETE FROM room_spec_map WHERE room_id = ?");
                $stmt->execute([$room_id]);

                // Delete the room
                $stmt = $db->prepare("DELETE FROM rooms WHERE room_id = ?");
                $stmt->execute([$room_id]);
                $message = 'Room deleted successfully!';
                $messageType = 'success';
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? 'all';
$bedTypeFilter = $_GET['bed_type'] ?? 'all';
$priceMax = $_GET['price_max'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$activeFilter = $_GET['active'] ?? 'active'; // New filter for active/inactive rooms

// Get distinct bed types for filter dropdown
$bedTypesQuery = $db->query("SELECT DISTINCT bed_type FROM rooms WHERE bed_type IS NOT NULL AND bed_type != '' ORDER BY bed_type");
$bedTypes = $bedTypesQuery->fetchAll(PDO::FETCH_COLUMN);

// Build query
$query = "SELECT r.*,
          GROUP_CONCAT(rs.spec_name SEPARATOR ', ') as amenities
          FROM rooms r
          LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
          LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
          WHERE 1=1";

$params = [];

// Filter by active/inactive status
if ($activeFilter === 'active') {
    $query .= " AND r.is_active = TRUE";
} elseif ($activeFilter === 'inactive') {
    $query .= " AND r.is_active = FALSE";
}
// 'all' shows both active and inactive

if ($categoryFilter !== 'all') {
    $query .= " AND r.room_type = ?";
    $params[] = $categoryFilter;
}

if ($bedTypeFilter !== 'all') {
    $query .= " AND r.bed_type = ?";
    $params[] = $bedTypeFilter;
}

if ($priceMax !== '') {
    $query .= " AND r.price_per_night <= ?";
    $params[] = $priceMax;
}

if ($statusFilter !== 'all') {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$query .= " GROUP BY r.room_id ORDER BY r.room_number ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Get statistics (only active rooms)
$stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE is_active = TRUE");
$totalRooms = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available' AND is_active = TRUE");
$availableRooms = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied' AND is_active = TRUE");
$occupiedRooms = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'maintenance' AND is_active = TRUE");
$maintenanceRooms = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE is_active = FALSE");
$deactivatedRooms = $stmt->fetch()['total'];

$pageTitle = "Rooms Management";
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
        :root {
            --primary-color: #8B4513;
            --primary-dark: #6B3410;
            --primary-light: #A0522D;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        /* Page Header with Add Room Button */
        .page-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            width: 100%;
        }

        .page-header-content > div:first-child {
            flex: 1;
        }

        .btn-add-room {
            padding: 13px 32px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
            white-space: nowrap;
            font-family: inherit;
            margin-left: auto;
            flex-shrink: 0;
        }

        .btn-add-room:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 69, 19, 0.4);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: white;
        }

        .btn-add-room:active {
            transform: translateY(0);
        }

        .btn-add-room i {
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .page-header-content {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-add-room {
                width: 100%;
                margin-left: 0;
                justify-content: center;
            }
        }

        /* Modern Filter Container - Elevated Card Design */
        .modern-filter-container {
            background: white;
            border-radius: 16px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(160, 82, 45, 0.1);
            transition: all 0.3s ease;
        }

        .modern-filter-container:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
        }

        .modern-filter-container .card-body {
            padding: 25px;
        }

        /* Filter Row - Single Horizontal Line */
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: nowrap;
            width: 100%;
        }

        .filter-item {
            flex: 1 1 auto;
            min-width: 0;
        }

        .filter-item label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-item label i {
            margin-right: 6px;
            color: #a0522d;
        }

        .filter-item .form-select,
        .filter-item .form-control,
        .filter-item input,
        .filter-item select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            color: #495057;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: inherit;
        }

        .filter-item .form-select:focus,
        .filter-item .form-control:focus,
        .filter-item input:focus,
        .filter-item select:focus {
            outline: none;
            border-color: #a0522d;
            box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1);
            background: white;
        }

        .filter-item .form-select:hover,
        .filter-item .form-control:hover,
        .filter-item input:hover,
        .filter-item select:hover {
            border-color: #a0522d;
        }

        .filter-item input::placeholder {
            color: #9ca3af;
        }

        /* Filter Buttons Container */
        .filter-buttons {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
            align-items: center;
        }

        /* Modern Filter Button - Brown/Orange Theme */
        .btn-filter {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            background: linear-gradient(135deg, #a0522d 0%, #8b6f47 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(160, 82, 45, 0.3);
            text-decoration: none;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(160, 82, 45, 0.4);
            background: linear-gradient(135deg, #8b4513 0%, #a0522d 100%);
            color: white;
            text-decoration: none;
        }

        .btn-filter:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(160, 82, 45, 0.3);
        }

        .btn-filter i {
            font-size: 14px;
        }

        /* Modern Reset Button - Gray/Neutral */
        .btn-reset {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            background: white;
            color: #6c757d;
            text-decoration: none;
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            border-color: #a0522d;
            background: #f8f9fa;
            color: #a0522d;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }

        .btn-reset:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-reset i {
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .filter-row {
                flex-wrap: wrap;
            }
            .filter-buttons {
                flex: 1 1 100%;
                margin-top: 10px;
                justify-content: flex-end;
            }
        }

        @media (max-width: 768px) {
            .modern-filter-container .card-body {
                padding: 20px;
            }

            .filter-row {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .filter-item {
                flex: 1 1 100%;
            }

            .filter-buttons {
                flex: 1 1 100%;
                margin-top: 0;
            }

            .btn-filter,
            .btn-reset {
                flex: 1;
                justify-content: center;
            }
        }
        
        /* Enhanced Rooms Grid */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 28px;
        }
        
        .room-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--gray-100);
            position: relative;
        }

        .room-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }

        /* Deactivated Room Styling */
        .room-card.deactivated {
            opacity: 0.6;
            background: #f5f5f5;
            border: 2px dashed #999;
        }

        .room-card.deactivated:hover {
            transform: translateY(-4px);
            opacity: 0.8;
        }

        .room-card.deactivated .room-image::after {
            content: 'DEACTIVATED';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 2px;
            z-index: 3;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .room-image {
            position: relative;
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--gray-200) 0%, var(--gray-300) 100%);
        }
        
        .room-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.1) 100%);
            z-index: 1;
        }
        
        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .room-card:hover .room-image img {
            transform: scale(1.1);
        }
        
        .room-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 7px 16px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .status-available {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .status-occupied {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .status-reserved {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .status-maintenance {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .status-cleaning {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }
        
        .room-number-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(10px);
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .room-content {
            padding: 24px;
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 15px;
        }
        
        .room-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 4px 0;
            line-height: 1.3;
        }
        
        .room-type {
            font-size: 11px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .room-price {
            text-align: right;
            flex-shrink: 0;
        }
        
        .price-amount {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 2px;
        }
        
        .price-label {
            font-size: 10px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .room-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
            padding: 18px 0;
            border-top: 2px solid var(--gray-100);
            border-bottom: 2px solid var(--gray-100);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--gray-700);
            font-weight: 500;
        }
        
        .detail-item i {
            color: var(--primary-color);
            width: 18px;
            text-align: center;
            font-size: 14px;
        }
        
        .room-amenities {
            margin: 18px 0;
        }
        
        .amenities-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .amenities-list {
            font-size: 12px;
            color: var(--gray-600);
            line-height: 1.7;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .room-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        .action-btn {
            padding: 11px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-family: inherit;
            white-space: nowrap;
        }

        .btn-edit {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
        }

        .btn-view {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.3);
        }

        .btn-deactivate {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
        }

        .btn-activate {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
            color: white;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.4);
            color: white;
        }

        .btn-deactivate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
            color: white;
        }

        .btn-activate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            color: white;
        }

        /* Form buttons inside grid */
        .room-actions form {
            margin: 0;
            display: contents;
        }

        .room-actions form button {
            width: 100%;
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
                        <h1><i class="fas fa-hotel"></i> Rooms Management</h1>
                        <p>Manage hotel rooms and inventory</p>
                    </div>
                    <a href="room-form.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Room
                    </a>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-door-open text-primary"></i>
                            <div>
                                <h4><?php echo $totalRooms; ?></h4>
                                <p>Active Rooms</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-check-circle text-success"></i>
                            <div>
                                <h4><?php echo $availableRooms; ?></h4>
                                <p>Available</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-bed text-danger"></i>
                            <div>
                                <h4><?php echo $occupiedRooms; ?></h4>
                                <p>Occupied</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-ban text-secondary"></i>
                            <div>
                                <h4><?php echo $deactivatedRooms; ?></h4>
                                <p>Deactivated</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="modern-filter-container mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="filter-row">
                                <div class="filter-item">
                                    <label><i class="fas fa-tag"></i> Room Category</label>
                                    <select class="form-select" name="category">
                                        <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                        <option value="Single" <?php echo $categoryFilter === 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Double" <?php echo $categoryFilter === 'Double' ? 'selected' : ''; ?>>Double</option>
                                        <option value="Suite" <?php echo $categoryFilter === 'Suite' ? 'selected' : ''; ?>>Suite</option>
                                        <option value="Deluxe" <?php echo $categoryFilter === 'Deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                                        <option value="Presidential" <?php echo $categoryFilter === 'Presidential' ? 'selected' : ''; ?>>Presidential</option>
                                    </select>
                                </div>
                                <div class="filter-item">
                                    <label><i class="fas fa-bed"></i> Bed Type</label>
                                    <select class="form-select" name="bed_type">
                                        <option value="all" <?php echo $bedTypeFilter === 'all' ? 'selected' : ''; ?>>All Bed Types</option>
                                        <?php foreach ($bedTypes as $bedType): ?>
                                            <option value="<?php echo htmlspecialchars($bedType); ?>" <?php echo $bedTypeFilter === $bedType ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bedType); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-item">
                                    <label><i class="fas fa-dollar-sign"></i> Max Price</label>
                                    <input type="number" class="form-control" name="price_max" placeholder="Any Price" value="<?php echo htmlspecialchars($priceMax); ?>">
                                </div>
                                <div class="filter-item">
                                    <label><i class="fas fa-info-circle"></i> Status</label>
                                    <select class="form-select" name="status">
                                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="occupied" <?php echo $statusFilter === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                        <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="cleaning" <?php echo $statusFilter === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                                    </select>
                                </div>
                                <div class="filter-item">
                                    <label><i class="fas fa-toggle-on"></i> Active Status</label>
                                    <select class="form-select" name="active">
                                        <option value="active" <?php echo $activeFilter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                                        <option value="inactive" <?php echo $activeFilter === 'inactive' ? 'selected' : ''; ?>>Deactivated Only</option>
                                        <option value="all" <?php echo $activeFilter === 'all' ? 'selected' : ''; ?>>All Rooms</option>
                                    </select>
                                </div>
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="rooms.php" class="btn btn-reset">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Rooms Grid -->
                <div class="rooms-grid">
                    <?php foreach ($rooms as $room): ?>
                    <div class="room-card <?php echo !$room['is_active'] ? 'deactivated' : ''; ?>">
                        <div class="room-image">
                            <img src="../<?php echo htmlspecialchars($room['room_image'] ?: 'images/room1.jpeg'); ?>"
                                 alt="<?php echo htmlspecialchars($room['room_name']); ?>">
                            <div class="room-number-badge">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                            <div class="room-status-badge status-<?php echo $room['status']; ?>">
                                <?php echo ucfirst($room['status']); ?>
                            </div>
                        </div>
                        <div class="room-content">
                            <div class="room-header">
                                <div>
                                    <h3 class="room-title"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                                    <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                </div>
                                <div class="room-price">
                                    <div class="price-amount">$<?php echo number_format($room['price_per_night'], 0); ?></div>
                                    <div class="price-label">per night</div>
                                </div>
                            </div>
                            
                            <div class="room-details">
                                <div class="detail-item">
                                    <i class="fas fa-bed"></i>
                                    <span><?php echo htmlspecialchars($room['bed_type'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $room['max_occupancy']; ?> Guests</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-expand-arrows-alt"></i>
                                    <span><?php echo $room['size_sqm']; ?> m²</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Floor <?php echo $room['floor']; ?></span>
                                </div>
                            </div>
                            
                            <div class="room-amenities">
                                <div class="amenities-label">Amenities:</div>
                                <div class="amenities-list">
                                    <?php echo htmlspecialchars(substr($room['amenities'] ?? 'No amenities listed', 0, 100)); ?>
                                    <?php echo strlen($room['amenities'] ?? '') > 100 ? '...' : ''; ?>
                                </div>
                            </div>

                            <div class="room-actions">
                                <a href="room-details.php?id=<?php echo $room['room_id']; ?>" class="action-btn btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="room-form.php?id=<?php echo $room['room_id']; ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if ($room['is_active']): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate this room?');">
                                        <input type="hidden" name="action" value="deactivate">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <button type="submit" class="action-btn btn-deactivate">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to activate this room?');">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <button type="submit" class="action-btn btn-activate">
                                            <i class="fas fa-check-circle"></i> Activate
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to DELETE this room? This action cannot be undone!');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                    <button type="submit" class="action-btn btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($rooms)): ?>
                <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px;">
                    <i class="fas fa-hotel" style="font-size: 64px; color: var(--gray-400);"></i>
                    <p style="margin-top: 20px; font-size: 18px; color: var(--gray-600);">No rooms found</p>
                    <a href="room-form.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Add Your First Room
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>

