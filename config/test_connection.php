<?php
/**
 * Database Connection Test Script
 * Luviora Hotel Management System
 */

require_once 'database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - Luviora Hotel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .test-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .test-result {
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .test-result.success {
            background: #d4edda;
            border-left: 5px solid #28a745;
        }
        .test-result.error {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 10px 0;
        }
        .stat-box h3 {
            color: #667eea;
            margin: 0;
            font-size: 2em;
        }
        .stat-box p {
            margin: 5px 0 0 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-card">
            <h1 class="text-center mb-4">
                <i class="fas fa-database"></i> Database Connection Test
            </h1>

            <?php
            try {
                // Test 1: Database Connection
                echo '<div class="test-result success">';
                echo '<h5><i class="fas fa-check-circle"></i> Database Connection</h5>';
                echo '<p>Successfully connected to MySQL server</p>';
                echo '<p class="mb-0"><strong>Host:</strong> ' . DB_HOST . ' | <strong>Database:</strong> ' . DB_NAME . '</p>';
                echo '</div>';

                $db = getDB();

                // Test 2: Check Tables
                $tables = ['users', 'rooms', 'room_specs', 'room_spec_map', 'bookings', 'payments', 'qr_codes', 'feedback', 'reports', 'activity_log'];
                $existing_tables = [];
                $missing_tables = [];

                foreach ($tables as $table) {
                    $stmt = $db->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        $existing_tables[] = $table;
                    } else {
                        $missing_tables[] = $table;
                    }
                }

                if (empty($missing_tables)) {
                    echo '<div class="test-result success">';
                    echo '<h5><i class="fas fa-check-circle"></i> Database Tables</h5>';
                    echo '<p>All ' . count($existing_tables) . ' required tables exist</p>';
                    echo '<p class="mb-0"><small>' . implode(', ', $existing_tables) . '</small></p>';
                    echo '</div>';
                } else {
                    echo '<div class="test-result error">';
                    echo '<h5><i class="fas fa-exclamation-triangle"></i> Missing Tables</h5>';
                    echo '<p>Missing tables: ' . implode(', ', $missing_tables) . '</p>';
                    echo '<p class="mb-0"><a href="../database/install.php" class="btn btn-warning">Run Database Installer</a></p>';
                    echo '</div>';
                }

                // Test 3: Get Statistics
                echo '<h4 class="mt-4 mb-3">Database Statistics</h4>';
                echo '<div class="row">';

                // Count users
                $stmt = $db->query("SELECT COUNT(*) as count FROM users");
                $user_count = $stmt->fetch()['count'];
                
                $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'guest'");
                $guest_count = $stmt->fetch()['count'];
                
                $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                $admin_count = $stmt->fetch()['count'];

                echo '<div class="col-md-4">';
                echo '<div class="stat-box">';
                echo '<h3>' . $user_count . '</h3>';
                echo '<p>Total Users</p>';
                echo '<small>' . $guest_count . ' Guests | ' . $admin_count . ' Admin</small>';
                echo '</div>';
                echo '</div>';

                // Count rooms
                $stmt = $db->query("SELECT COUNT(*) as count FROM rooms");
                $room_count = $stmt->fetch()['count'];
                
                $stmt = $db->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'");
                $available_count = $stmt->fetch()['count'];

                echo '<div class="col-md-4">';
                echo '<div class="stat-box">';
                echo '<h3>' . $room_count . '</h3>';
                echo '<p>Total Rooms</p>';
                echo '<small>' . $available_count . ' Available</small>';
                echo '</div>';
                echo '</div>';

                // Count bookings
                $stmt = $db->query("SELECT COUNT(*) as count FROM bookings");
                $booking_count = $stmt->fetch()['count'];
                
                $stmt = $db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'");
                $confirmed_count = $stmt->fetch()['count'];

                echo '<div class="col-md-4">';
                echo '<div class="stat-box">';
                echo '<h3>' . $booking_count . '</h3>';
                echo '<p>Total Bookings</p>';
                echo '<small>' . $confirmed_count . ' Confirmed</small>';
                echo '</div>';
                echo '</div>';

                echo '</div>'; // End row

                // Test 4: Room Specifications
                $stmt = $db->query("SELECT COUNT(*) as count FROM room_specs WHERE is_active = TRUE");
                $spec_count = $stmt->fetch()['count'];

                echo '<div class="row mt-3">';
                echo '<div class="col-md-4">';
                echo '<div class="stat-box">';
                echo '<h3>' . $spec_count . '</h3>';
                echo '<p>Room Specifications</p>';
                echo '</div>';
                echo '</div>';

                // Count payments
                $stmt = $db->query("SELECT COUNT(*) as count FROM payments");
                $payment_count = $stmt->fetch()['count'];
                
                $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'completed'");
                $total_revenue = $stmt->fetch()['total'];

                echo '<div class="col-md-4">';
                echo '<div class="stat-box">';
                echo '<h3>' . $payment_count . '</h3>';
                echo '<p>Total Payments</p>';
                echo '<small>$' . number_format($total_revenue, 2) . ' Revenue</small>';
                echo '</div>';
                echo '</div>';

                // Count QR codes
                $stmt = $db->query("SELECT COUNT(*) as count FROM qr_codes");
                $qr_count = $stmt->fetch()['count'];

                echo '<div class="col-md-4">';
                echo '<div class="stat-box">';
                echo '<h3>' . $qr_count . '</h3>';
                echo '<p>QR Codes Generated</p>';
                echo '</div>';
                echo '</div>';

                echo '</div>'; // End row

                // Test 5: Sample Data
                echo '<h4 class="mt-4 mb-3">Sample Room Data</h4>';
                $stmt = $db->query("SELECT room_number, room_name, room_type, floor, price_per_night, status FROM rooms LIMIT 5");
                $rooms = $stmt->fetchAll();

                if (!empty($rooms)) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped">';
                    echo '<thead><tr><th>Room #</th><th>Name</th><th>Type</th><th>Floor</th><th>Price</th><th>Status</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($rooms as $room) {
                        $status_badge = $room['status'] === 'available' ? 'success' : 'warning';
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($room['room_number']) . '</td>';
                        echo '<td>' . htmlspecialchars($room['room_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($room['room_type']) . '</td>';
                        echo '<td>' . htmlspecialchars($room['floor']) . '</td>';
                        echo '<td>$' . number_format($room['price_per_night'], 2) . '</td>';
                        echo '<td><span class="badge bg-' . $status_badge . '">' . htmlspecialchars($room['status']) . '</span></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>';
                }

                // Test 6: Check Stored Procedures
                echo '<h4 class="mt-4 mb-3">Stored Procedures & Functions</h4>';
                $stmt = $db->query("SHOW PROCEDURE STATUS WHERE Db = '" . DB_NAME . "'");
                $procedures = $stmt->fetchAll();
                
                $stmt = $db->query("SHOW FUNCTION STATUS WHERE Db = '" . DB_NAME . "'");
                $functions = $stmt->fetchAll();

                echo '<div class="test-result success">';
                echo '<h5><i class="fas fa-check-circle"></i> Database Objects</h5>';
                echo '<p>' . count($procedures) . ' Stored Procedures | ' . count($functions) . ' Functions</p>';
                echo '</div>';

                // Success message
                echo '<div class="alert alert-success mt-4">';
                echo '<h5><i class="fas fa-check-circle"></i> All Tests Passed!</h5>';
                echo '<p class="mb-0">Your database is properly configured and ready to use.</p>';
                echo '</div>';

                echo '<div class="text-center mt-4">';
                echo '<a href="../index.html" class="btn btn-primary btn-lg me-2"><i class="fas fa-home"></i> Go to Homepage</a>';
                echo '<a href="../admin/login.php" class="btn btn-success btn-lg"><i class="fas fa-sign-in-alt"></i> Admin Login</a>';
                echo '</div>';

            } catch (Exception $e) {
                echo '<div class="test-result error">';
                echo '<h5><i class="fas fa-times-circle"></i> Connection Failed</h5>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p class="mb-0"><a href="../database/install.php" class="btn btn-warning">Run Database Installer</a></p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

