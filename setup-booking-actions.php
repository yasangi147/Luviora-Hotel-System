<?php
/**
 * Setup Booking Actions Tables
 * Run this once to create the necessary tables for modify/cancel booking functionality
 */

require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Booking Actions - Luviora Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .result {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }

        .info {
            background: #d1ecf1;
            border: 2px solid #bee5eb;
            color: #0c5460;
        }

        .result h3 {
            margin-bottom: 10px;
        }

        .result ul {
            margin-left: 20px;
        }

        .result li {
            margin: 5px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-container {
            text-align: center;
            margin-top: 30px;
        }

        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Setup Booking Actions Tables</h1>
        <p class="subtitle">Create tables for modify and cancel booking functionality</p>

        <?php
        try {
            $db = getDB();
            
            // Read SQL file
            $sqlFile = __DIR__ . '/database/booking_actions_tables.sql';
            
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL file not found: $sqlFile");
            }
            
            $sql = file_get_contents($sqlFile);
            
            // Split by semicolons and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && 
                           !preg_match('/^--/', $stmt) && 
                           !preg_match('/^USE/', $stmt);
                }
            );
            
            $results = [];
            $errors = [];
            
            foreach ($statements as $statement) {
                try {
                    $db->exec($statement);
                    
                    // Extract table/view name from statement
                    if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
                        $results[] = "✅ Table created: {$matches[1]}";
                    } elseif (preg_match('/CREATE OR REPLACE VIEW (\w+)/i', $statement, $matches)) {
                        $results[] = "✅ View created: {$matches[1]}";
                    }
                } catch (PDOException $e) {
                    $errors[] = "❌ Error: " . $e->getMessage();
                }
            }
            
            if (empty($errors)) {
                echo '<div class="result success">';
                echo '<h3><i class="fas fa-check-circle"></i> Setup Completed Successfully!</h3>';
                echo '<ul>';
                foreach ($results as $result) {
                    echo "<li>$result</li>";
                }
                echo '</ul>';
                echo '</div>';
                
                // Verify tables exist
                echo '<div class="result info">';
                echo '<h3><i class="fas fa-info-circle"></i> Verification</h3>';
                
                $tables = ['booking_modifications', 'booking_cancellations'];
                foreach ($tables as $table) {
                    $stmt = $db->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        echo "<p>✅ Table <strong>$table</strong> exists</p>";
                        
                        // Show table structure
                        $stmt = $db->query("DESCRIBE $table");
                        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        echo "<p style='margin-left: 20px; font-size: 13px;'>Columns: " . implode(', ', $columns) . "</p>";
                    } else {
                        echo "<p>❌ Table <strong>$table</strong> not found</p>";
                    }
                }
                echo '</div>';
                
            } else {
                echo '<div class="result error">';
                echo '<h3><i class="fas fa-exclamation-triangle"></i> Setup Completed with Errors</h3>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo "<li>$error</li>";
                }
                echo '</ul>';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="result error">';
            echo '<h3><i class="fas fa-times-circle"></i> Setup Failed</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>

        <div class="btn-container">
            <a href="test-booking-actions.php" class="btn">
                <i class="fas fa-vial"></i> Test Booking Actions
            </a>
        </div>
    </div>
</body>
</html>

