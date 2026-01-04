<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'clark') {
    header('Location: login.php');
    exit;
}

$page_title = 'Bookings Management';
$db = getDB();

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

try {
    $stmt = $db->query("SELECT booking_status, COUNT(*) as count FROM bookings GROUP BY booking_status");
    $stats = [];
    while ($row = $stmt->fetch()) {
        $stats[$row['booking_status']] = $row['count'];
    }
    
    $where_clauses = [];
    $params = [];
    
    if ($status_filter !== 'all') {
        $where_clauses[] = "b.booking_status = :status";
        $params[':status'] = $status_filter;
    }
    
    if (!empty($search)) {
        $where_clauses[] = "(b.booking_reference LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    
    $sql = "
        SELECT b.*, 
               u.name as guest_name, 
               u.email as guest_email, 
               u.phone as guest_phone,
               r.room_number, 
               r.room_type
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        $where_sql
        ORDER BY b.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Bookings Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Luviora Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="css/clark.css" rel="stylesheet" />
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-calendar-check"></i> Bookings Management</h1>
                <p>View and manage all hotel bookings</p>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <p>Total Bookings</p>
                        <h3><?php echo array_sum($stats); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="border-top-color: #3498db;">
                        <p>Confirmed</p>
                        <h3><?php echo $stats['confirmed'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="border-top-color: #f39c12;">
                        <p>Pending</p>
                        <h3><?php echo $stats['pending'] ?? 0; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="border-top-color: #27ae60;">
                        <p>Checked In</p>
                        <h3><?php echo $stats['checked_in'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="all">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Ref, Name, Email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> All Bookings (<?php echo count($bookings); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($bookings) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Guest Details</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Nights</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($booking['guest_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($booking['room_number']): ?>
                                            <strong><?php echo htmlspecialchars($booking['room_number']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['room_type']); ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><?php echo $booking['total_nights']; ?> nights</td>
                                    <td><strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'checked_in' => 'success',
                                            'checked_out' => 'secondary',
                                            'cancelled' => 'danger'
                                        ];
                                        $color = $status_colors[$booking['booking_status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $booking['booking_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                        <a href="check-in.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($booking['booking_status'] === 'checked_in'): ?>
                                        <a href="check-out.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No bookings found.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

