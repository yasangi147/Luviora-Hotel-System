<?php
/**
 * Guests Management
 * Luviora Hotel Management System
 */

require_once 'auth_check.php';
require_once '../config/database.php';

$db = getDB();

// Get filter parameters
$searchQuery = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT u.*, 
           COUNT(DISTINCT b.booking_id) as total_bookings,
           COALESCE(SUM(CASE WHEN b.booking_status IN ('confirmed', 'checked_in', 'checked_out') THEN b.total_amount ELSE 0 END), 0) as total_spent,
           MAX(b.created_at) as last_booking_date
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.user_id
    WHERE u.role = 'guest'
";

$params = [];

if ($searchQuery) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$guests = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'guest'");
$totalGuests = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'guest' AND status = 'active'");
$activeGuests = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(DISTINCT user_id) as total FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$recentGuests = $stmt->fetch()['total'];

$pageTitle = "Guests Management";
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

        .filter-item .form-control {
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

        .filter-item .form-control:focus {
            outline: none;
            border-color: #a0522d;
            box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1);
            background: white;
        }

        .filter-item .form-control:hover {
            border-color: #a0522d;
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
        
        .guest-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .guest-info {
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
        
        .btn-view:hover {
            background: #138496;
            color: white;
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
                        <h1><i class="fas fa-users"></i> Guests Management</h1>
                        <p>Manage hotel guests and customer information</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-4 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-users text-primary"></i>
                            <div>
                                <h4><?php echo $totalGuests; ?></h4>
                                <p>Total Guests</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-user-check text-success"></i>
                            <div>
                                <h4><?php echo $activeGuests; ?></h4>
                                <p>Active Guests</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="quick-stat">
                            <i class="fas fa-calendar-check text-info"></i>
                            <div>
                                <h4><?php echo $recentGuests; ?></h4>
                                <p>Recent Bookings (30 days)</p>
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
                                           placeholder="Search by name, email, or phone..." 
                                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-filter">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="guests.php" class="btn btn-reset">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Guests Table -->
                <div class="data-table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-table"></i> All Guests (<?php echo count($guests); ?>)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Contact</th>
                                    <th>Total Bookings</th>
                                    <th>Total Spent</th>
                                    <th>Last Booking</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($guests)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-users" style="font-size: 48px; color: var(--gray-400);"></i>
                                        <p style="margin-top: 15px; color: var(--gray-600);">No guests found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($guests as $guest): ?>
                                <tr>
                                    <td>
                                        <div class="guest-info">
                                            <div class="guest-avatar">
                                                <?php echo strtoupper(substr($guest['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($guest['name']); ?></strong><br>
                                                <small style="color: var(--gray-600);">ID: <?php echo $guest['user_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-envelope" style="color: var(--gray-500);"></i> 
                                        <?php echo htmlspecialchars($guest['email']); ?><br>
                                        <i class="fas fa-phone" style="color: var(--gray-500);"></i> 
                                        <?php echo htmlspecialchars($guest['phone'] ?? 'N/A'); ?>
                                    </td>
                                    <td><strong><?php echo $guest['total_bookings']; ?></strong> bookings</td>
                                    <td><strong>$<?php echo number_format($guest['total_spent'], 2); ?></strong></td>
                                    <td>
                                        <?php if ($guest['last_booking_date']): ?>
                                            <?php echo date('M d, Y', strtotime($guest['last_booking_date'])); ?>
                                        <?php else: ?>
                                            <span style="color: var(--gray-500);">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $guest['status']; ?>">
                                            <?php echo ucfirst($guest['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="guest-details.php?id=<?php echo $guest['user_id']; ?>"
                                               class="action-btn btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
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

