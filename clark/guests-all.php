<?php
require_once '../config/database.php';
require_once 'auth_check.php';

$db = getDB();

// Get filter parameters
$searchQuery = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

// Build query with filters
$query = "SELECT u.*, COUNT(b.booking_id) as total_bookings
          FROM users u
          LEFT JOIN bookings b ON u.user_id = b.user_id
          WHERE u.role = 'guest'";

$params = [];

// Add search filter
if (!empty($searchQuery)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Add status filter
if ($statusFilter !== 'all') {
    $query .= " AND u.status = ?";
    $params[] = $statusFilter;
}

$query .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Guests | Clark Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/clark-style.css">
    
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

        /* Page Header Improvements */
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-200);
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: var(--primary-color);
            font-size: 32px;
        }

        .page-header p {
            font-size: 14px;
            color: var(--gray-600);
            margin: 0;
        }

        /* Filter Section - Single Horizontal Line */
        .data-table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-100);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-body {
            padding: 25px;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: nowrap;
            width: 100%;
        }

        .filter-row .input-group {
            flex: 1 1 auto;
            min-width: 0;
        }

        .filter-row .form-select {
            flex: 0 0 200px;
            min-width: 150px;
        }

        .filter-row .filter-buttons {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
            align-items: center;
        }

        /* Enhanced Input Group Styling */
        .input-group-text {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border: 2px solid var(--gray-200);
            border-right: none;
            border-radius: 10px 0 0 10px !important;
            color: var(--primary-color);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .input-group .form-control {
            border: 2px solid var(--gray-200);
            border-left: none;
            border-radius: 0 10px 10px 0 !important;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            color: var(--gray-800);
        }

        .input-group:focus-within .form-control {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
            background: white;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
        }

        /* Enhanced Select Styling */
        .form-select {
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            color: var(--gray-800);
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
        }

        /* Enhanced Button Styling */
        .filter-row .btn {
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
            text-decoration: none;
        }

        .filter-row .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
        }

        .filter-row .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 69, 19, 0.4);
            color: white;
        }

        .filter-row .btn-secondary {
            background: var(--gray-300);
            color: var(--gray-700);
        }

        .filter-row .btn-secondary:hover {
            background: var(--gray-400);
            color: white;
        }

        /* Responsive Filter Row */
        @media (max-width: 1200px) {
            .filter-row {
                flex-wrap: wrap;
            }
            .filter-row .form-select {
                flex: 1 1 calc(50% - 15px);
                min-width: 150px;
            }
            .filter-row .filter-buttons {
                flex: 1 1 100%;
                margin-top: 10px;
                justify-content: flex-end;
            }
        }

        @media (max-width: 768px) {
            .filter-row .input-group,
            .filter-row .form-select {
                flex: 1 1 100%;
            }
            .filter-row .filter-buttons {
                flex: 1 1 100%;
                margin-top: 15px;
            }
            .filter-row .btn {
                flex: 1;
            }
        }

        /* Enhanced Table Section */
        .table-header {
            padding: 25px 30px;
            border-bottom: 2px solid var(--gray-200);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .table-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-header h3 i {
            color: var(--primary-color);
            font-size: 22px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            margin: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
        }

        .table thead th {
            padding: 18px 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--gray-100);
        }

        .table tbody tr:nth-child(even) {
            background-color: var(--gray-50);
        }

        .table tbody tr:hover {
            background-color: var(--gray-100);
            transform: scale(1.001);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table tbody td {
            padding: 18px 20px;
            font-size: 14px;
            color: var(--gray-800);
            vertical-align: middle;
        }

        .table tbody td strong {
            color: var(--gray-900);
            font-weight: 700;
        }

        /* Status Badge Styling */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .status-badge.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .status-badge.inactive {
            background: linear-gradient(135deg, var(--gray-400) 0%, var(--gray-500) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3);
        }

        /* Empty State */
        .table tbody td[colspan] {
            text-align: center;
            padding: 60px 20px !important;
        }

        .table tbody td[colspan] i {
            font-size: 64px;
            color: var(--gray-300);
            display: block;
            margin-bottom: 20px;
        }

        .table tbody td[colspan] p {
            font-size: 16px;
            color: var(--gray-600);
            margin: 0;
        }

        /* Spacing Improvements */
        .content-wrapper {
            padding: 25px;
        }

        .mb-4 {
            margin-bottom: 25px !important;
        }

        /* Responsive Improvements */
        @media (max-width: 1200px) {
            .table thead th,
            .table tbody td {
                padding: 14px 16px;
                font-size: 13px;
            }
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .table-header {
                padding: 20px;
            }

            .table-header h3 {
                font-size: 18px;
            }

            .table thead th,
            .table tbody td {
                padding: 12px 14px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> All Guests</h1>
                    <p>View all registered guests</p>
                </div>

                <!-- Search and Filter Bar -->
                <div class="data-table-card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="filter-row">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search"
                                           placeholder="Search by name, email, or phone..."
                                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                                <select class="form-select" name="status">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="guests-all.php" class="btn btn-secondary">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-users"></i> All Guests (<?php echo count($data); ?> found)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Bookings</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data)): ?>
                                    <?php foreach ($data as $row): ?>
                                    <tr>                                        <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['total_bookings'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 60px 20px !important;">
                                            <i class="fas fa-users" style="font-size: 64px; color: var(--gray-300); display: block; margin-bottom: 20px;"></i>
                                            <p style="font-size: 16px; color: var(--gray-600); margin: 0;">No guests found</p>
                                        </td>
                                    </tr>
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