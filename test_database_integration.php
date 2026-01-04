<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, device-scale=1.0">
    <title>Database Integration Test - Luviora Hotel</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .test-section {
            margin: 30px 0;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        
        .test-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .room-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 2px solid #e0e0e0;
        }
        
        .room-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .room-card p {
            margin: 5px 0;
            color: #666;
        }
        
        .price {
            font-size: 1.5em;
            color: #28a745;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏨 Database Integration Test</h1>
        <p class="subtitle">Luviora Hotel Reservation System</p>
        
        <?php
        require_once 'config/database.php';
        
        $tests = [];
        $allPassed = true;
        
        // Test 1: Database Connection
        try {
            $db = getDB();
            $tests[] = [
                'name' => 'Database Connection',
                'status' => 'success',
                'message' => 'Successfully connected to database'
            ];
        } catch (Exception $e) {
            $tests[] = [
                'name' => 'Database Connection',
                'status' => 'error',
                'message' => 'Failed to connect: ' . $e->getMessage()
            ];
            $allPassed = false;
        }
        
        // Test 2: Rooms Table
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM rooms");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $roomCount = $result['count'];
            
            if ($roomCount > 0) {
                $tests[] = [
                    'name' => 'Rooms Table',
                    'status' => 'success',
                    'message' => "Found {$roomCount} rooms in database"
                ];
            } else {
                $tests[] = [
                    'name' => 'Rooms Table',
                    'status' => 'warning',
                    'message' => 'No rooms found in database. Please add rooms.'
                ];
            }
        } catch (Exception $e) {
            $tests[] = [
                'name' => 'Rooms Table',
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
            $allPassed = false;
        }
        
        // Test 3: Room Specs Table
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM room_specs");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $specsCount = $result['count'];
            
            $tests[] = [
                'name' => 'Room Specs Table',
                'status' => 'success',
                'message' => "Found {$specsCount} room specifications"
            ];
        } catch (Exception $e) {
            $tests[] = [
                'name' => 'Room Specs Table',
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
        
        // Test 4: API Availability
        $apiTests = [
            'api/availability.php' => 'Availability API',
            'api/create-booking.php' => 'Booking API',
            'api/save-session.php' => 'Session API'
        ];
        
        foreach ($apiTests as $file => $name) {
            if (file_exists($file)) {
                $tests[] = [
                    'name' => $name,
                    'status' => 'success',
                    'message' => 'File exists and ready'
                ];
            } else {
                $tests[] = [
                    'name' => $name,
                    'status' => 'error',
                    'message' => 'File not found'
                ];
                $allPassed = false;
            }
        }
        
        // Test 5: JavaScript Files
        $jsTests = [
            'js/room-selection.js' => 'Room Selection JS',
            'js/booking.js' => 'Booking JS',
            'js/confirmation.js' => 'Confirmation JS'
        ];
        
        foreach ($jsTests as $file => $name) {
            if (file_exists($file)) {
                $tests[] = [
                    'name' => $name,
                    'status' => 'success',
                    'message' => 'File exists and ready'
                ];
            } else {
                $tests[] = [
                    'name' => $name,
                    'status' => 'error',
                    'message' => 'File not found'
                ];
                $allPassed = false;
            }
        }
        
        // Test 6: Upload Directory
        if (is_dir('uploads/qr_codes')) {
            if (is_writable('uploads/qr_codes')) {
                $tests[] = [
                    'name' => 'QR Code Directory',
                    'status' => 'success',
                    'message' => 'Directory exists and is writable'
                ];
            } else {
                $tests[] = [
                    'name' => 'QR Code Directory',
                    'status' => 'warning',
                    'message' => 'Directory exists but may not be writable'
                ];
            }
        } else {
            $tests[] = [
                'name' => 'QR Code Directory',
                'status' => 'error',
                'message' => 'Directory does not exist. Please create uploads/qr_codes/'
            ];
        }
        
        // Display Test Results
        foreach ($tests as $test) {
            echo '<div class="test-section">';
            echo '<h2>' . htmlspecialchars($test['name']) . '</h2>';
            echo '<span class="status ' . $test['status'] . '">' . strtoupper($test['status']) . '</span>';
            echo '<p style="margin-top: 10px;">' . htmlspecialchars($test['message']) . '</p>';
            echo '</div>';
        }
        
        // Overall Status
        if ($allPassed) {
            echo '<div class="info-box" style="background: #d4edda; border-color: #28a745;">';
            echo '<h2 style="color: #155724; margin-bottom: 10px;">✅ All Tests Passed!</h2>';
            echo '<p style="color: #155724;">Your database integration is working correctly. You can now test the reservation flow.</p>';
            echo '</div>';
        } else {
            echo '<div class="info-box" style="background: #f8d7da; border-color: #dc3545;">';
            echo '<h2 style="color: #721c24; margin-bottom: 10px;">❌ Some Tests Failed</h2>';
            echo '<p style="color: #721c24;">Please fix the errors above before testing the reservation system.</p>';
            echo '</div>';
        }
        
        // Display Sample Rooms
        if ($roomCount > 0) {
            echo '<div class="test-section">';
            echo '<h2>📋 Sample Rooms from Database</h2>';
            
            $stmt = $db->query("SELECT * FROM rooms WHERE is_active = TRUE LIMIT 6");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="room-grid">';
            foreach ($rooms as $room) {
                echo '<div class="room-card">';
                echo '<h3>' . htmlspecialchars($room['room_name']) . '</h3>';
                echo '<p><strong>Room Number:</strong> ' . htmlspecialchars($room['room_number']) . '</p>';
                echo '<p><strong>Type:</strong> ' . htmlspecialchars($room['room_type']) . '</p>';
                echo '<p><strong>Bed:</strong> ' . htmlspecialchars($room['bed_type']) . '</p>';
                echo '<p><strong>Max Guests:</strong> ' . htmlspecialchars($room['max_occupancy']) . '</p>';
                echo '<p><strong>Size:</strong> ' . htmlspecialchars($room['size_sqm']) . ' m²</p>';
                echo '<div class="price">$' . number_format($room['price_per_night'], 2) . '/night</div>';
                echo '<p style="font-size: 0.9em; color: #888;">' . htmlspecialchars(substr($room['description'], 0, 100)) . '...</p>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <div style="text-align: center; margin-top: 40px;">
            <h2 style="margin-bottom: 20px;">🚀 Ready to Test?</h2>
            <a href="availability.php" class="btn btn-success">Start Reservation Flow</a>
            <a href="room-select.php" class="btn">View Room Selection</a>
            <a href="DATABASE_INTEGRATION_COMPLETE.md" class="btn">Read Documentation</a>
        </div>
        
        <div class="info-box" style="margin-top: 30px;">
            <h3 style="margin-bottom: 10px;">📖 Testing Instructions:</h3>
            <ol style="margin-left: 20px; color: #666;">
                <li>Click "Start Reservation Flow" to begin at availability.php</li>
                <li>Select check-in and check-out dates</li>
                <li>Choose number of guests and rooms</li>
                <li>Click "CHECK AVAILABILITY"</li>
                <li>Click "CONTINUE TO ROOM SELECTION"</li>
                <li>Browse rooms loaded from database</li>
                <li>Test filters (price, type, bed type)</li>
                <li>Select a room and click "CONTINUE TO BOOKING"</li>
                <li>Fill booking form and submit</li>
                <li>View confirmation with QR code</li>
            </ol>
        </div>
    </div>
</body>
</html>

