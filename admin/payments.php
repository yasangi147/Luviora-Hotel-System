<?php
/**
 * Payments Management
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query (from bookings table - clark approach)
$query = "
    SELECT b.*, u.name as guest_name, u.email as guest_email,
           r.room_name, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE 1=1
";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND b.payment_status = ?";
    $params[] = $statusFilter;
}

if ($searchQuery) {
    $query .= " AND (b.booking_reference LIKE ? OR u.name LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($dateFrom) {
    $query .= " AND DATE(b.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $query .= " AND DATE(b.created_at) <= ?";
    $params[] = $dateTo;
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get statistics (from bookings table)
$stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as total_amount FROM bookings");
$totalStats = $stmt->fetch();
$totalPayments = $totalStats['total'];
$totalRevenue = $totalStats['total_amount'];

$stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as total_amount FROM bookings WHERE payment_status = 'paid'");
$paidStats = $stmt->fetch();
$paidPayments = $paidStats['total'];
$paidRevenue = $paidStats['total_amount'];

$stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as total_amount FROM bookings WHERE payment_status = 'partial'");
$partialStats = $stmt->fetch();
$partialPayments = $partialStats['total'];
$partialAmount = $partialStats['total_amount'];

$stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE payment_status = 'unpaid'");
$unpaidPayments = $stmt->fetch()['total'];

$pageTitle = "Payments Management";
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
        
        .payment-method-icon {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
        }
        
        .method-credit_card { background: #4a90e2; }
        .method-debit_card { background: #7b68ee; }
        .method-paypal { background: #003087; }
        .method-bank_transfer { background: #28a745; }
        .method-cash { background: #6c757d; }
        
        .payment-info {
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .btn-receipt {
            background: #28a745;
            color: white;
        }
        
        .btn-refund {
            background: #dc3545;
            color: white;
        }
        
        .btn-view:hover { background: #138496; color: white; }
        .btn-receipt:hover { background: #218838; color: white; }
        .btn-refund:hover { background: #c82333; color: white; }
        
        .badge-paid {
            background: #d4edda;
            color: #155724;
        }

        .badge-partial {
            background: #fff3cd;
            color: #856404;
        }

        .badge-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-refunded {
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
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-credit-card"></i> Payments Management</h1>
                        <p>Manage transactions and financial records</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6" style="width: 300px;">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-content">
                                <h3 style="font-size: 22px;">$<?php echo number_format($totalRevenue, 2); ?></h3>
                                <p>Total Revenue</p>
                                <small><?php echo $totalPayments; ?> transactions</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6" style="width: 300px;">
                        <div class="stat-card stat-card-success">
                            <div class="stat-content">
                                <h3 style="font-size: 22px;">$<?php echo number_format($paidRevenue, 2); ?></h3>
                                <p>Paid</p>
                                <small><?php echo $paidPayments; ?> bookings</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6" style="width: 300px;">
                        <div class="stat-card stat-card-info">
                            <div class="stat-content">
                                <h3 style="font-size: 22px;"><?php echo $unpaidPayments; ?></h3>
                                <p>Unpaid</p>
                                <small>Bookings</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
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
                                           placeholder="Search booking, guest, transaction..." 
                                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                                <div class="filter-item">
                                    <label><i class="fas fa-info-circle"></i> Status</label>
                                    <select class="form-select" name="status">
                                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                       
                                        <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                       
                                    </select>
                                </div>
                                <input type="date" class="date-input form-control" name="date_from" 
                                       placeholder="From Date" value="<?php echo htmlspecialchars($dateFrom); ?>">
                                <input type="date" class="date-input form-control" name="date_to" 
                                       placeholder="To Date" value="<?php echo htmlspecialchars($dateTo); ?>">
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="payments.php" class="btn btn-reset">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Payments Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> All Bookings (<?php echo count($payments); ?>)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Amount</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Payment Status</th>
                                    <th>Booking Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-credit-card" style="font-size: 48px; color: var(--gray-400);"></i>
                                        <p style="margin-top: 15px; color: var(--gray-600);">No bookings found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['booking_reference']); ?></strong><br>
                                        <small style="color: var(--gray-600);">ID: <?php echo $payment['booking_id']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['guest_name']); ?><br>
                                        <small style="color: var(--gray-600);"><?php echo htmlspecialchars($payment['guest_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['room_name'] ?? 'N/A'); ?><br>
                                        <small style="color: var(--gray-600);">Room <?php echo htmlspecialchars($payment['room_number'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <strong style="font-size: 16px; color: var(--primary-color);">
                                            $<?php echo number_format($payment['total_amount'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($payment['check_in_date'])); ?><br>
                                        <small style="color: var(--gray-600);"><?php echo date('H:i A', strtotime($payment['check_in_date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($payment['check_out_date'])); ?><br>
                                        <small style="color: var(--gray-600);"><?php echo date('H:i A', strtotime($payment['check_out_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $payment['payment_status']; ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: #e3f2fd; color: #1976d2;">
                                            <?php echo ucfirst(str_replace('_', ' ', $payment['booking_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="payment-details.php?id=<?php echo $payment['booking_id']; ?>"
                                               class="action-btn btn-view" title="View Payment Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($payment['payment_status'] === 'paid'): ?>
                                            
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
</body>
</html>

