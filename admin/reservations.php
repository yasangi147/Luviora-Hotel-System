<?php
/**
 * Reservations Management - All Bookings
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build query based on filters
$query = "
    SELECT b.*, u.name as guest_name, u.email as guest_email, u.phone as guest_phone,
           r.room_name, r.room_number, r.room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE 1=1
";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND b.booking_status = ?";
    $params[] = $statusFilter;
}

if ($searchQuery) {
    $query .= " AND (b.booking_reference LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($dateFilter) {
    $query .= " AND DATE(b.check_in_date) = ?";
    $params[] = $dateFilter;
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM bookings");
$totalBookings = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'pending'");
$pendingBookings = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'confirmed'");
$confirmedBookings = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'cancelled'");
$cancelledBookings = $stmt->fetch()['total'];

$pageTitle = "Reservations Management";
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
        .date-input {
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
        .date-input:focus {
            outline: none;
            border-color: #a0522d;
            box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1);
            background: white;
        }

        .filter-item .form-select:hover,
        .filter-item .form-control:hover,
        .date-input:hover {
            border-color: #a0522d;
        }

        .date-input {
            flex: 0 0 180px;
            min-width: 150px;
            display: flex;
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
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(160, 82, 45, 0.4);
            background: linear-gradient(135deg, #8b4513 0%, #a0522d 100%);
            color: white;
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
            .date-input {
                flex: 0 0 auto;
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

            .filter-item,
            .date-input {
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-cancel {
            background: #dc3545;
            color: white;
        }
        
        .btn-view:hover { background: #138496; }
        .btn-edit:hover { background: #e0a800; }
        .btn-cancel:hover { background: #c82333; }
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
                        <h1><i class="fas fa-calendar-check"></i> Reservations Management</h1>
                        <p>Manage all hotel bookings and reservations</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-list text-primary"></i>
                            <div>
                                <h4><?php echo $totalBookings; ?></h4>
                                <p>Total Bookings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-clock text-warning"></i>
                            <div>
                                <h4><?php echo $pendingBookings; ?></h4>
                                <p>Pending Confirmations</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-check-circle text-success"></i>
                            <div>
                                <h4><?php echo $confirmedBookings; ?></h4>
                                <p>Confirmed Bookings</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-times-circle text-danger"></i>
                            <div>
                                <h4><?php echo $cancelledBookings; ?></h4>
                                <p>Cancelled Bookings</p>
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
                                    <label><i class="fas fa-search"></i> Search</label>
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Reference, Guest Name, Email..." 
                                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                                <div class="filter-item">
                                    <label><i class="fas fa-info-circle"></i> Status</label>
                                    <select class="form-select" name="status">
                                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="checked_in" <?php echo $statusFilter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                        <option value="checked_out" <?php echo $statusFilter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <input type="date" class="date-input form-control" name="date" 
                                       value="<?php echo htmlspecialchars($dateFilter); ?>">
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="reservations.php" class="btn btn-reset">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Bookings Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> All Bookings (<?php echo count($bookings); ?>)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: var(--gray-400);"></i>
                                        <p style="margin-top: 15px; color: var(--gray-600);">No bookings found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['guest_name']); ?><br>
                                        <small style="color: var(--gray-600);"><?php echo htmlspecialchars($booking['guest_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?><br>
                                        <small style="color: var(--gray-600);"><?php echo htmlspecialchars($booking['room_number'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><?php echo $booking['num_adults']; ?> Adults, <?php echo $booking['num_children']; ?> Children</td>
                                    <td><span class="badge badge-<?php echo $booking['booking_status']; ?>"><?php echo ucfirst($booking['booking_status']); ?></span></td>
                                    <td><strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-booking.php?id=<?php echo $booking['booking_id']; ?>" class="action-btn btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
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
</body>
</html>

