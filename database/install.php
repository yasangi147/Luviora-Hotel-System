<?php
/**
 * Database Installation Script
 * Luviora Hotel Management System
 * 
 * This script will:
 * 1. Create the database
 * 2. Create all tables
 * 3. Insert sample data
 * 4. Create stored procedures and functions
 * 5. Create views
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$sql_file = __DIR__ . '/luviora_hotel_system.sql';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Installation - Luviora Hotel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .install-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #667eea;
            font-weight: bold;
        }
        .step {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .step.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .step.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .step.info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }
        .btn-install {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            font-size: 18px;
        }
        .credentials-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="logo">
                <h1><i class="fas fa-hotel"></i> Luviora Hotel System</h1>
                <p class="text-muted">Database Installation Wizard</p>
            </div>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
                echo '<div id="installation-log">';
                
                try {
                    // Step 1: Connect to MySQL
                    echo '<div class="step info"><i class="fas fa-spinner fa-spin"></i> Connecting to MySQL server...</div>';
                    flush();
                    
                    $conn = new mysqli($db_host, $db_user, $db_pass);
                    
                    if ($conn->connect_error) {
                        throw new Exception("Connection failed: " . $conn->connect_error);
                    }
                    
                    echo '<div class="step success"><i class="fas fa-check-circle"></i> Connected to MySQL server successfully</div>';
                    flush();
                    
                    // Step 2: Read SQL file
                    echo '<div class="step info"><i class="fas fa-spinner fa-spin"></i> Reading SQL file...</div>';
                    flush();
                    
                    if (!file_exists($sql_file)) {
                        throw new Exception("SQL file not found: " . $sql_file);
                    }
                    
                    $sql = file_get_contents($sql_file);
                    echo '<div class="step success"><i class="fas fa-check-circle"></i> SQL file loaded successfully</div>';
                    flush();
                    
                    // Step 3: Execute SQL statements
                    echo '<div class="step info"><i class="fas fa-spinner fa-spin"></i> Executing SQL statements...</div>';
                    flush();
                    
                    // Split SQL into individual statements
                    $statements = array_filter(
                        array_map('trim', explode(';', $sql)),
                        function($stmt) {
                            return !empty($stmt) && 
                                   !preg_match('/^--/', $stmt) && 
                                   !preg_match('/^\/\*/', $stmt);
                        }
                    );
                    
                    $success_count = 0;
                    $error_count = 0;
                    
                    foreach ($statements as $statement) {
                        if (trim($statement) !== '') {
                            if ($conn->multi_query($statement . ';')) {
                                do {
                                    if ($result = $conn->store_result()) {
                                        $result->free();
                                    }
                                } while ($conn->more_results() && $conn->next_result());
                                $success_count++;
                            } else {
                                $error_count++;
                                error_log("SQL Error: " . $conn->error . " in statement: " . substr($statement, 0, 100));
                            }
                        }
                    }
                    
                    echo '<div class="step success"><i class="fas fa-check-circle"></i> Executed ' . $success_count . ' SQL statements successfully</div>';
                    
                    if ($error_count > 0) {
                        echo '<div class="step error"><i class="fas fa-exclamation-triangle"></i> ' . $error_count . ' statements had errors (check error log)</div>';
                    }
                    flush();
                    
                    // Step 4: Verify installation
                    echo '<div class="step info"><i class="fas fa-spinner fa-spin"></i> Verifying installation...</div>';
                    flush();
                    
                    $conn->select_db('luviora_hotel_system');
                    
                    $tables = ['users', 'rooms', 'room_specs', 'room_spec_map', 'bookings', 'payments', 'qr_codes', 'feedback', 'reports', 'activity_log'];
                    $tables_created = 0;
                    
                    foreach ($tables as $table) {
                        $result = $conn->query("SHOW TABLES LIKE '$table'");
                        if ($result && $result->num_rows > 0) {
                            $tables_created++;
                        }
                    }
                    
                    echo '<div class="step success"><i class="fas fa-check-circle"></i> Verified ' . $tables_created . ' out of ' . count($tables) . ' tables created</div>';
                    flush();
                    
                    // Step 5: Installation complete
                    echo '<div class="step success" style="margin-top: 20px; font-size: 18px; font-weight: bold;">
                            <i class="fas fa-check-circle"></i> Installation completed successfully!
                          </div>';
                    
                    echo '<div class="credentials-box">
                            <h5><i class="fas fa-key"></i> Default Login Credentials</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Admin:</strong><br>
                                    Email: admin@luviora.com<br>
                                    Password: admin123
                                </div>
                                <div class="col-md-6">
                                    <strong>Clark:</strong><br>
                                    Email: clark@luviora.com<br>
                                    Password: admin123
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <strong>Staff:</strong><br>
                                    Email: staff@luviora.com<br>
                                    Password: admin123
                                </div>
                                <div class="col-md-6">
                                    <strong>Guest:</strong><br>
                                    Email: john.doe@example.com<br>
                                    Password: admin123
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Important:</strong> Please change these passwords immediately after first login!
                            </div>
                          </div>';
                    
                    echo '<div class="text-center mt-4">
                            <a href="../index.html" class="btn btn-primary btn-lg">
                                <i class="fas fa-home"></i> Go to Homepage
                            </a>
                            <a href="../admin/login.php" class="btn btn-success btn-lg ms-2">
                                <i class="fas fa-sign-in-alt"></i> Admin Login
                            </a>
                          </div>';
                    
                    $conn->close();
                    
                } catch (Exception $e) {
                    echo '<div class="step error"><i class="fas fa-times-circle"></i> Error: ' . $e->getMessage() . '</div>';
                    echo '<div class="text-center mt-4">
                            <button onclick="location.reload()" class="btn btn-warning">
                                <i class="fas fa-redo"></i> Try Again
                            </button>
                          </div>';
                }
                
                echo '</div>';
                
            } else {
                // Show installation form
                ?>
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Before You Begin</h5>
                    <p>This wizard will install the Luviora Hotel Management System database. Please ensure:</p>
                    <ul>
                        <li>XAMPP/MySQL server is running</li>
                        <li>You have MySQL root access</li>
                        <li>No existing database named 'luviora_hotel_system' (it will be dropped)</li>
                    </ul>
                </div>

                <div class="step">
                    <h6><i class="fas fa-database"></i> Database Information</h6>
                    <p class="mb-0">
                        <strong>Database Name:</strong> luviora_hotel_system<br>
                        <strong>Host:</strong> <?php echo $db_host; ?><br>
                        <strong>User:</strong> <?php echo $db_user; ?>
                    </p>
                </div>

                <div class="step">
                    <h6><i class="fas fa-table"></i> What Will Be Created</h6>
                    <ul class="mb-0">
                        <li>10 Database Tables</li>
                        <li>2 Views for Reporting</li>
                        <li>4 Stored Procedures</li>
                        <li>1 Function</li>
                        <li>2 Triggers</li>
                        <li>Sample Data (4 users, 10 rooms, 20 specifications)</li>
                    </ul>
                </div>

                <form method="POST" class="text-center mt-4">
                    <button type="submit" name="install" class="btn btn-install btn-primary btn-lg">
                        <i class="fas fa-download"></i> Install Database
                    </button>
                </form>
                <?php
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

