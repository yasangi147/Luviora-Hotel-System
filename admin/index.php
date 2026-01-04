<?php
/**
 * Admin Dashboard - Main Page
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

// Get database connection
$db = getDB();

// Get filter parameters
$dateFilter = $_GET['date_filter'] ?? 'all_time';
$customStartDate = $_GET['start_date'] ?? '';
$customEndDate = $_GET['end_date'] ?? '';

// Build date condition based on filter
$dateCondition = "1=1";
$dateParams = [];

switch ($dateFilter) {
    case 'today':
        $dateCondition = "DATE(created_at) = CURDATE()";
        break;
    case 'this_week':
        $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'this_month':
        $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        break;
    case 'this_year':
        $dateCondition = "YEAR(created_at) = YEAR(CURDATE())";
        break;
    case 'custom':
        if (!empty($customStartDate) && !empty($customEndDate)) {
            $dateCondition = "DATE(created_at) BETWEEN ? AND ?";
            $dateParams = [$customStartDate, $customEndDate];
        }
        break;
    default:
        $dateCondition = "1=1";
}

// Fetch dashboard statistics
try {
    // Total Bookings
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE $dateCondition");
    $stmt->execute($dateParams);
    $totalBookings = $stmt->fetch()['total'];

    // Confirmed Bookings
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'confirmed' AND $dateCondition");
    $stmt->execute($dateParams);
    $confirmedBookings = $stmt->fetch()['total'];

    // Pending Bookings
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'pending' AND $dateCondition");
    $stmt->execute($dateParams);
    $pendingBookings = $stmt->fetch()['total'];

    // Cancelled Bookings
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE booking_status = 'cancelled' AND $dateCondition");
    $stmt->execute($dateParams);
    $cancelledBookings = $stmt->fetch()['total'];
    
    // Total Rooms
    $stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE is_active = TRUE");
    $totalRooms = $stmt->fetch()['total'];
    
    // Available Rooms
    $stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available' AND is_active = TRUE");
    $availableRooms = $stmt->fetch()['total'];
    
    // Occupied Rooms
    $stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied' AND is_active = TRUE");
    $occupiedRooms = $stmt->fetch()['total'];
    
    // Total Revenue (from completed bookings)
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE booking_status IN ('confirmed', 'checked_in', 'checked_out') AND $dateCondition");
    $stmt->execute($dateParams);
    $totalRevenue = $stmt->fetch()['total'];

    // Today's Revenue
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE DATE(created_at) = CURDATE() AND booking_status IN ('confirmed', 'checked_in')");
    $todayRevenue = $stmt->fetch()['total'];
    
    // Total Guests
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'guest'");
    $totalGuests = $stmt->fetch()['total'];
    
    // Active QR Codes
    $stmt = $db->query("SELECT COUNT(*) as total FROM qr_codes WHERE status = 'active'");
    $activeQRCodes = $stmt->fetch()['total'];

    // Maintenance Issues
    $stmt = $db->query("SELECT COUNT(*) as total FROM maintenance_issues WHERE status = 'reported' OR status = 'assigned'");
    $pendingMaintenanceIssues = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM maintenance_issues WHERE status = 'in_progress'");
    $inProgressMaintenanceIssues = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM maintenance_issues WHERE priority = 'urgent' AND status != 'completed'");
    $urgentMaintenanceIssues = $stmt->fetch()['total'];

    // Occupancy Rate
    $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
    
    // Recent Bookings (for table)
    $stmt = $db->query("
        SELECT b.booking_reference, b.check_in_date, b.check_out_date, b.booking_status, 
               b.total_amount, u.name as guest_name, r.room_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $recentBookings = $stmt->fetchAll();
    
    // Upcoming Check-ins (for table)
    $stmt = $db->query("
        SELECT b.booking_reference, b.check_in_date, u.name as guest_name,
               r.room_name, r.room_number, b.num_adults, b.num_children
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.check_in_date >= CURDATE() AND b.booking_status IN ('pending', 'confirmed')
        ORDER BY b.check_in_date ASC
        LIMIT 10
    ");
    $upcomingCheckins = $stmt->fetchAll();
    
    // Booking Status Distribution (for pie chart)
    $stmt = $db->prepare("
        SELECT booking_status, COUNT(*) as count
        FROM bookings
        WHERE $dateCondition
        GROUP BY booking_status
    ");
    $stmt->execute($dateParams);
    $bookingStatusData = $stmt->fetchAll();

    // Monthly Revenue (for bar chart - last 6 months or filtered period)
    if ($dateFilter === 'custom' && !empty($customStartDate) && !empty($customEndDate)) {
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                   SUM(total_amount) as revenue
            FROM bookings
            WHERE DATE(created_at) BETWEEN ? AND ?
                  AND booking_status IN ('confirmed', 'checked_in', 'checked_out')
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$customStartDate, $customEndDate]);
    } else {
        $stmt = $db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                   SUM(total_amount) as revenue
            FROM bookings
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  AND booking_status IN ('confirmed', 'checked_in', 'checked_out')
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
    }
    $monthlyRevenueData = $stmt->fetchAll();

    // Payment Statistics (from bookings table)
    $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as total_amount FROM bookings WHERE payment_status = 'paid'");
    $paidPaymentStats = $stmt->fetch();
    $paidPayments = $paidPaymentStats['total'] ?? 0;
    $paidPaymentAmount = $paidPaymentStats['total_amount'] ?? 0;

    $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as total_amount FROM bookings WHERE payment_status = 'partial'");
    $partialPaymentStats = $stmt->fetch();
    $partialPayments = $partialPaymentStats['total'] ?? 0;
    $partialPaymentAmount = $partialPaymentStats['total_amount'] ?? 0;

    $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as total_amount FROM bookings WHERE payment_status = 'unpaid'");
    $unpaidPaymentStats = $stmt->fetch();
    $unpaidPayments = $unpaidPaymentStats['total'] ?? 0;
    $unpaidPaymentAmount = $unpaidPaymentStats['total_amount'] ?? 0;

    $stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE payment_status = 'refunded'");
    $refundedPayments = $stmt->fetch()['total'] ?? 0;

    // Recent Payments (for table)
    $stmt = $db->query("
        SELECT b.booking_id, b.booking_reference, b.total_amount, b.payment_status, b.booking_status, b.created_at,
               u.name as guest_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recentPayments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = "Error loading dashboard data.";
}

$pageTitle = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Luviora Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
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
        .date-input:focus {
            outline: none;
            border-color: #a0522d;
            box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1);
            background: white;
        }

        .filter-item .form-select:hover,
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
                        <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
                    </div>
                </div>

                <!-- Date Filter -->
                <div class="modern-filter-container mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="filter-row">
                                <div class="filter-item">
                                    <label><i class="fas fa-calendar-alt"></i> Date Range</label>
                                    <select class="form-select" name="date_filter" id="dateFilter" onchange="toggleCustomDates()">
                                        <option value="all_time" <?php echo $dateFilter === 'all_time' ? 'selected' : ''; ?>>All Time</option>
                                        <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                                        <option value="this_week" <?php echo $dateFilter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                        <option value="this_month" <?php echo $dateFilter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                        <option value="this_year" <?php echo $dateFilter === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                                        <option value="custom" <?php echo $dateFilter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                    </select>
                                </div>
                                <input type="date" class="form-control date-input" name="start_date" id="customDatesDiv" 
                                       value="<?php echo htmlspecialchars($customStartDate); ?>"
                                       style="display: <?php echo $dateFilter === 'custom' ? 'flex' : 'none'; ?>;"
                                       placeholder="Start Date">
                                <input type="date" class="form-control date-input" name="end_date" id="customDatesDiv2" 
                                       value="<?php echo htmlspecialchars($customEndDate); ?>"
                                       style="display: <?php echo $dateFilter === 'custom' ? 'flex' : 'none'; ?>;"
                                       placeholder="End Date">
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="index.php" class="btn btn-reset">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                function toggleCustomDates() {
                    const filter = document.getElementById('dateFilter').value;
                    const customDiv = document.getElementById('customDatesDiv');
                    const customDiv2 = document.getElementById('customDatesDiv2');
                    if (filter === 'custom') {
                        customDiv.style.display = 'flex';
                        customDiv2.style.display = 'flex';
                    } else {
                        customDiv.style.display = 'none';
                        customDiv2.style.display = 'none';
                    }
                }
                </script>

                <!-- Main Statistics Cards (3 Most Important) -->
                <div class="stats-grid">
                    <!-- Total Bookings Card -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($totalBookings); ?></div>
                                <div class="stat-label">Total Bookings</div>
                            </div>
                            <div class="stat-icon primary">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Revenue Card -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">$<?php echo number_format($totalRevenue, 2); ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-icon success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Guests Card -->
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($totalGuests); ?></div>
                                <div class="stat-label">Total Guests</div>
                            </div>
                            <div class="stat-icon info">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row - Side by Side -->
                <div class="row mb-4 g-4">
                    <!-- Booking Status Distribution (Pie Chart) -->
                    <div class="col-md-6">
                        <div class="data-table-card">
                            <div class="table-header">
                                <h3><i class="fas fa-chart-pie"></i> Booking Status Distribution</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="bookingStatusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Revenue (Bar Chart) -->
                    <div class="col-md-6">
                        <div class="data-table-card">
                            <div class="table-header">
                                <h3><i class="fas fa-chart-bar"></i> Monthly Revenue (Last 6 Months)</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="monthlyRevenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Tabs Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="data-table-card">
                            <div class="table-header">
                                <h3><i class="fas fa-credit-card"></i> Payment Overview</h3>
                                <a href="payments.php" class="btn btn-sm btn-primary">View All Payments</a>
                            </div>

                            <!-- Payment Stats Cards -->
                            <div class="row g-3 p-4 border-bottom">
                                <div class="col-md-3">
                                    <div class="payment-stat-card">
                                        <div class="stat-icon" style="background: #4caf50;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>$<?php echo number_format($paidPaymentAmount, 2); ?></h4>
                                            <p>Paid</p>
                                            <small><?php echo $paidPayments; ?> bookings</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="payment-stat-card">
                                        <div class="stat-icon" style="background: #ff9800;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4>$<?php echo number_format($partialPaymentAmount, 2); ?></h4>
                                            <p>Partial</p>
                                            <small><?php echo $partialPayments; ?> bookings</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="payment-stat-card">
                                        <div class="stat-icon" style="background: #f44336;">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4><?php echo $unpaidPayments; ?></h4>
                                            <p>Unpaid</p>
                                            <small>$<?php echo number_format($unpaidPaymentAmount, 2); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="payment-stat-card">
                                        <div class="stat-icon" style="background: #2196f3;">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4><?php echo $refundedPayments; ?></h4>
                                            <p>Refunded</p>
                                            <small>Bookings</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Payments Table -->
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Booking Ref</th>
                                            <th>Guest</th>
                                            <th>Amount</th>
                                            <th>Payment Status</th>
                                            <th>Booking Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentPayments)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 40px;">
                                                <i class="fas fa-credit-card" style="font-size: 48px; color: var(--gray-400);"></i>
                                                <p style="margin-top: 15px; color: var(--gray-600);">No payments found</p>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($recentPayments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['booking_reference']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['guest_name']); ?></td>
                                            <td><strong>$<?php echo number_format($payment['total_amount'], 2); ?></strong></td>
                                            <td><?php echo ucfirst($payment['payment_status']); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['booking_status'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <!-- Recent Bookings Table -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="data-table-card">
                            <div class="table-header">
                                <h3><i class="fas fa-list"></i> Recent Bookings</h3>
                                <a href="reservations.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Check-in</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                            <td><span class="badge badge-<?php echo $booking['booking_status']; ?>"><?php echo ucfirst($booking['booking_status']); ?></span></td>
                                            <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Check-ins Table -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="data-table-card">
                            <div class="table-header">
                                <h3><i class="fas fa-calendar-alt"></i> Upcoming Check-ins</h3>
                                <a href="reservations.php?filter=upcoming" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Check-in Date</th>
                                            <th>Guests</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingCheckins as $checkin): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($checkin['booking_reference']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($checkin['guest_name']); ?></td>
                                            <td><?php echo htmlspecialchars($checkin['room_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($checkin['room_number'] ?? ''); ?>)</td>
                                            <td><?php echo date('M d, Y', strtotime($checkin['check_in_date'])); ?></td>
                                            <td><?php echo $checkin['num_adults']; ?> Adults, <?php echo $checkin['num_children']; ?> Children</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Booking Status Pie Chart
    const bookingStatusCtx = document.getElementById('bookingStatusChart').getContext('2d');
    new Chart(bookingStatusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($bookingStatusData, 'booking_status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($bookingStatusData, 'count')); ?>,
                backgroundColor: [
                    '#ffc107', // pending - warning
                    '#28a745', // confirmed - success
                    '#17a2b8', // checked_in - info
                    '#6c757d', // checked_out - secondary
                    '#dc3545', // cancelled - danger
                    '#6f42c1'  // no_show - purple
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Monthly Revenue Bar Chart
    const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    new Chart(monthlyRevenueCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($monthlyRevenueData, 'month')); ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?php echo json_encode(array_column($monthlyRevenueData, 'revenue')); ?>,
                backgroundColor: '#a0522d',
                borderColor: '#8b6f47',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
</body>
</html>

