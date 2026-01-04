<?php
/**
 * Occupancy Reports
 * Luviora Hotel Management System
 */

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

// Get total rooms
$stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE is_active = TRUE");
$totalRooms = $stmt->fetch()['total'];

// Get occupancy data for the period
$stmt = $db->prepare("
    SELECT DATE(check_in_date) as date, COUNT(*) as occupied_rooms
    FROM bookings
    WHERE booking_status IN ('confirmed', 'checked_in', 'checked_out')
    AND check_in_date <= ?
    AND check_out_date >= ?
    GROUP BY DATE(check_in_date)
    ORDER BY date ASC
");
$stmt->execute([$dateTo, $dateFrom]);
$occupancyData = $stmt->fetchAll();

// Calculate average occupancy rate
$stmt = $db->prepare("
    SELECT AVG(occupancy_rate) as avg_rate
    FROM (
        SELECT DATE(check_in_date) as date, 
               (COUNT(*) / ?) * 100 as occupancy_rate
        FROM bookings
        WHERE booking_status IN ('confirmed', 'checked_in', 'checked_out')
        AND check_in_date <= ?
        AND check_out_date >= ?
        GROUP BY DATE(check_in_date)
    ) as daily_rates
");
$stmt->execute([$totalRooms, $dateTo, $dateFrom]);
$avgOccupancyRate = $stmt->fetch()['avg_rate'] ?? 0;

// Get occupancy by room type
$stmt = $db->prepare("
    SELECT r.room_type, COUNT(DISTINCT b.booking_id) as bookings
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.booking_status IN ('confirmed', 'checked_in', 'checked_out')
    AND b.check_in_date <= ?
    AND b.check_out_date >= ?
    GROUP BY r.room_type
");
$stmt->execute([$dateTo, $dateFrom]);
$occupancyByType = $stmt->fetchAll();

// Get monthly comparison (last 6 months)
$stmt = $db->query("
    SELECT DATE_FORMAT(check_in_date, '%Y-%m') as month,
           COUNT(*) as bookings,
           (COUNT(*) / $totalRooms) * 100 as occupancy_rate
    FROM bookings
    WHERE booking_status IN ('confirmed', 'checked_in', 'checked_out')
    AND check_in_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(check_in_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyData = $stmt->fetchAll();

$pageTitle = "Occupancy Reports";
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
                        <h1><i class="fas fa-chart-bar"></i> Occupancy Reports</h1>
                        <p>Analyze room occupancy and utilization rates</p>
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
                                    <a href="occupancy-reports.php" class="btn btn-reset">
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
                
                <!-- Summary Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6" style="width: 245px;">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-icon">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div class="stat-content" style="padding-left: 10px;">
                                <h3><?php echo $totalRooms; ?></h3>
                                <p>Total Rooms</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6" style="width: 260px;">
                        <div class="stat-card stat-card-success">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-content" style="padding-left: 10px;">
                                <h3><?php echo number_format($avgOccupancyRate, 1); ?>%</h3>
                                <p>Avg Occupancy Rate</p>
                                
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6" style="width: 260px;">
                        <div class="stat-card stat-card-info">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content" style="padding-left: 10px;">
                                <h3><?php echo count($occupancyData); ?></h3>
                                <p>Days Analyzed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6" style="width: 245px;">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-icon">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="stat-content" style="padding-left: 10px;">
                                <h3><?php echo count($occupancyByType); ?></h3>
                                <p>Room Types</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <!-- Occupancy by Room Type - Pie Chart -->
                    <div class="col-xl-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3><i class="fas fa-chart-pie"></i> Occupancy by Room Type</h3>
                            </div>
                            <div class="chart-body">
                                <canvas id="roomTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Occupancy Trend - Bar Chart -->
                    <div class="col-xl-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3><i class="fas fa-chart-bar"></i> Monthly Occupancy Trend (Last 6 Months)</h3>
                            </div>
                            <div class="chart-body">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Data Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> Occupancy by Room Type</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Room Type</th>
                                    <th>Total Bookings</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalBookings = array_sum(array_column($occupancyByType, 'bookings'));
                                foreach ($occupancyByType as $type): 
                                    $percentage = $totalBookings > 0 ? ($type['bookings'] / $totalBookings) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($type['room_type']); ?></strong></td>
                                    <td><?php echo $type['bookings']; ?> bookings</td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%; background: var(--primary-color);"
                                                 aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Room Type Pie Chart
        const roomTypeCtx = document.getElementById('roomTypeChart').getContext('2d');
        new Chart(roomTypeCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($occupancyByType, 'room_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($occupancyByType, 'bookings')); ?>,
                    backgroundColor: [
                        '#a0522d',
                        '#8b6f47',
                        '#d4a574',
                        '#6b5744',
                        '#c19a6b',
                        '#b8860b'
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
        
        // Monthly Trend Bar Chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(monthlyTrendCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($m) { 
                    return date('M Y', strtotime($m['month'] . '-01')); 
                }, $monthlyData)); ?>,
                datasets: [{
                    label: 'Occupancy Rate (%)',
                    data: <?php echo json_encode(array_column($monthlyData, 'occupancy_rate')); ?>,
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
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
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

