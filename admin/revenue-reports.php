<?php
require_once 'auth_check.php';
require_once '../config/database.php';
$db = getDB();

// Get filter parameters
$dateFilter = $_GET['date_filter'] ?? 'this_month';
$customStartDate = $_GET['start_date'] ?? '';
$customEndDate = $_GET['end_date'] ?? '';

// Build date range based on filter
$today = date('Y-m-d');
switch ($dateFilter) {
    case 'today':
        $dateFrom = $today;
        $dateTo = $today;
        break;
    case 'this_week':
        $dateFrom = date('Y-m-d', strtotime('monday this week'));
        $dateTo = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'this_month':
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
        break;
    case 'this_year':
        $dateFrom = date('Y-01-01');
        $dateTo = date('Y-12-31');
        break;
    case 'custom':
        $dateFrom = $customStartDate ?: date('Y-m-01');
        $dateTo = $customEndDate ?: date('Y-m-t');
        break;
    default: // all_time
        $dateFrom = '2000-01-01';
        $dateTo = date('Y-m-d', strtotime('+1 day'));
}

// Get monthly revenue data
$stmt = $db->prepare("
    SELECT
        DATE_FORMAT(b.created_at, '%Y-%m') as month,
        SUM(b.total_amount) as total_revenue,
        COUNT(DISTINCT b.booking_id) as total_bookings
    FROM bookings b
    WHERE b.payment_status = 'paid'
    AND DATE(b.created_at) >= ?
    AND DATE(b.created_at) <= ?
    GROUP BY DATE_FORMAT(b.created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute([$dateFrom, $dateTo]);
$monthlyRevenue = $stmt->fetchAll();

// Get revenue by room type
$stmt = $db->prepare("
    SELECT
        r.room_type,
        SUM(b.total_amount) as total_revenue,
        COUNT(b.booking_id) as bookings
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.payment_status = 'paid'
    AND DATE(b.created_at) >= ?
    AND DATE(b.created_at) <= ?
    GROUP BY r.room_type
    ORDER BY total_revenue DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$revenueByRoomType = $stmt->fetchAll();

// Get revenue by booking status
$stmt = $db->prepare("
    SELECT
        b.booking_status,
        SUM(b.total_amount) as total_revenue,
        COUNT(b.booking_id) as bookings
    FROM bookings b
    WHERE b.payment_status = 'paid'
    AND DATE(b.created_at) >= ?
    AND DATE(b.created_at) <= ?
    GROUP BY b.booking_status
    ORDER BY total_revenue DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$revenueByStatus = $stmt->fetchAll();

// Calculate statistics
$totalRevenue = array_sum(array_column($monthlyRevenue, 'total_revenue'));
$totalBookings = array_sum(array_column($monthlyRevenue, 'total_bookings'));
$avgRevenuePerBooking = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

// Get current month revenue
$currentMonthRevenue = $db->query("
    SELECT SUM(total_amount) as total
    FROM bookings
    WHERE payment_status = 'paid'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
")->fetch()['total'] ?? 0;

// Prepare chart data
$monthLabels = array_map(function($item) {
    return date('M Y', strtotime($item['month'] . '-01'));
}, $monthlyRevenue);
$monthValues = array_column($monthlyRevenue, 'total_revenue');

$statusLabels = array_map(function($item) {
    return ucfirst(str_replace('_', ' ', $item['booking_status']));
}, $revenueByStatus);
$statusValues = array_column($revenueByStatus, 'total_revenue');

$roomTypeLabels = array_column($revenueByRoomType, 'room_type');
$roomTypeValues = array_column($revenueByRoomType, 'total_revenue');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Reports | Luviora Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        
        .chart-container {
            position: relative;
            height: 400px;
            padding: 30px 20px;
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
                    <div>
                        <h1><i class="fas fa-chart-line"></i> Revenue Reports</h1>
                    </div>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
                                <input type="date" class="date-input form-control" name="start_date" id="customDatesDiv" 
                                       value="<?php echo htmlspecialchars($customStartDate); ?>"
                                       style="display: <?php echo $dateFilter === 'custom' ? 'flex' : 'none'; ?>;"
                                       placeholder="Start Date">
                                <input type="date" class="date-input form-control" name="end_date" id="customDatesDiv2" 
                                       value="<?php echo htmlspecialchars($customEndDate); ?>"
                                       style="display: <?php echo $dateFilter === 'custom' ? 'flex' : 'none'; ?>;"
                                       placeholder="End Date">
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="revenue-reports.php" class="btn btn-reset">
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

                <!-- Revenue Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">$<?php echo number_format($totalRevenue, 2); ?></div>
                                <div class="stat-label">Total Revenue (12 Months)</div>
                            </div>
                            <div class="stat-icon primary">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">$<?php echo number_format($currentMonthRevenue, 2); ?></div>
                                <div class="stat-label">Current Month Revenue</div>
                            </div>
                            <div class="stat-icon success">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($totalBookings); ?></div>
                                <div class="stat-label">Total Bookings</div>
                            </div>
                            <div class="stat-icon warning">
                                <i class="fas fa-receipt"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-value">$<?php echo number_format($avgRevenuePerBooking, 2); ?></div>
                                <div class="stat-label">Avg Revenue/Booking</div>
                            </div>
                            <div class="stat-icon info">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Trend -->
                <div class="data-table-card mb-4">
                    <div class="table-header">
                        <h3><i class="fas fa-chart-line"></i> Monthly Revenue Trend (Last 12 Months)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>

                <!-- Revenue by Booking Status & Room Type -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="data-table-card">
                            <div class="table-header">
                                <h3><i class="fas fa-chart-pie"></i> Revenue by Booking Status</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="data-table-card">
                            <div class="table-header">
                                <h3><i class="fas fa-bed"></i> Revenue by Room Type</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="roomTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Revenue Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> Monthly Revenue Breakdown</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total Revenue</th>
                                    <th>Total Bookings</th>
                                    <th>Avg Revenue/Booking</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($monthlyRevenue)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-chart-line" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 15px; color: #666;">No revenue data found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($monthlyRevenue as $data): ?>
                                <tr>
                                    <td><strong><?php echo date('F Y', strtotime($data['month'] . '-01')); ?></strong></td>
                                    <td><strong style="color: #28a745;">$<?php echo number_format($data['total_revenue'], 2); ?></strong></td>
                                    <td><?php echo number_format($data['total_bookings']); ?> bookings</td>
                                    <td>$<?php echo number_format($data['total_revenue'] / max($data['total_bookings'], 1), 2); ?></td>
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
        // Monthly Revenue Chart
        const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthLabels); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($monthValues); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Booking Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusValues); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#11998e'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Room Type Chart
        const roomTypeCtx = document.getElementById('roomTypeChart').getContext('2d');
        new Chart(roomTypeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($roomTypeLabels); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($roomTypeValues); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#11998e'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script>
        // Show/hide custom date range based on selection
        document.querySelectorAll('input[name="date_filter"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const customRange = document.getElementById('customDateRange');
                if (this.value === 'custom') {
                    customRange.style.display = 'flex';
                } else {
                    customRange.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>