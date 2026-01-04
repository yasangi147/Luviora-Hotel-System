<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Booking Actions - Luviora Hotel</title>
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

        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .test-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }

        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .result.show {
            display: block;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-box strong {
            color: #2196F3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-vial"></i> Test Booking Actions</h1>
        <p class="subtitle">Test the Modify and Cancel Booking functionality</p>

        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Instructions:</strong><br>
            Enter a valid booking reference and email from your database to test the modify and cancel features.
        </div>

        <!-- Modify Booking Test -->
        <div class="test-section">
            <h2><i class="fas fa-edit"></i> Test Modify Booking</h2>
            
            <div class="form-group">
                <label for="modify-ref">Booking Reference:</label>
                <input type="text" id="modify-ref" placeholder="e.g., LUV-20251103-ABCD">
            </div>

            <div class="form-group">
                <label for="modify-email">Guest Email:</label>
                <input type="email" id="modify-email" placeholder="guest@example.com">
            </div>

            <div class="form-group">
                <label for="modify-details">Modification Request:</label>
                <textarea id="modify-details" placeholder="Describe the changes you'd like to make..."></textarea>
            </div>

            <button class="btn btn-primary" onclick="testModifyBooking()">
                <i class="fas fa-paper-plane"></i> Submit Modification Request
            </button>

            <div id="modify-result" class="result"></div>
        </div>

        <!-- Cancel Booking Test -->
        <div class="test-section">
            <h2><i class="fas fa-times-circle"></i> Test Cancel Booking</h2>
            
            <div class="form-group">
                <label for="cancel-ref">Booking Reference:</label>
                <input type="text" id="cancel-ref" placeholder="e.g., LUV-20251103-ABCD">
            </div>

            <div class="form-group">
                <label for="cancel-email">Guest Email:</label>
                <input type="email" id="cancel-email" placeholder="guest@example.com">
            </div>

            <div class="form-group">
                <label for="cancel-reason">Cancellation Reason:</label>
                <textarea id="cancel-reason" placeholder="Why are you cancelling?"></textarea>
            </div>

            <button class="btn btn-danger" onclick="testCancelBooking()">
                <i class="fas fa-ban"></i> Submit Cancellation Request
            </button>

            <div id="cancel-result" class="result"></div>
        </div>
    </div>

    <script>
        function showResult(elementId, success, message, data = null) {
            const resultDiv = document.getElementById(elementId);
            resultDiv.className = 'result show ' + (success ? 'success' : 'error');
            
            let html = `<strong>${success ? '✅ Success!' : '❌ Error!'}</strong><br>${message}`;
            
            if (data) {
                html += '<br><br><strong>Response Data:</strong><br>';
                html += '<pre style="background: rgba(0,0,0,0.05); padding: 10px; border-radius: 5px; overflow-x: auto;">';
                html += JSON.stringify(data, null, 2);
                html += '</pre>';
            }
            
            resultDiv.innerHTML = html;
        }

        function testModifyBooking() {
            const bookingRef = document.getElementById('modify-ref').value.trim();
            const guestEmail = document.getElementById('modify-email').value.trim();
            const requestDetails = document.getElementById('modify-details').value.trim();

            if (!bookingRef || !guestEmail || !requestDetails) {
                showResult('modify-result', false, 'Please fill in all fields');
                return;
            }

            const resultDiv = document.getElementById('modify-result');
            resultDiv.className = 'result show';
            resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting modification request...';

            fetch('api/modify-booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    booking_reference: bookingRef,
                    guest_email: guestEmail,
                    modification_type: 'general',
                    request_details: requestDetails
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('modify-result', data.success, data.message, data.data);
            })
            .catch(error => {
                showResult('modify-result', false, 'Network error: ' + error.message);
            });
        }

        function testCancelBooking() {
            const bookingRef = document.getElementById('cancel-ref').value.trim();
            const guestEmail = document.getElementById('cancel-email').value.trim();
            const cancellationReason = document.getElementById('cancel-reason').value.trim();

            if (!bookingRef || !guestEmail || !cancellationReason) {
                showResult('cancel-result', false, 'Please fill in all fields');
                return;
            }

            const resultDiv = document.getElementById('cancel-result');
            resultDiv.className = 'result show';
            resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing cancellation...';

            fetch('api/cancel-booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    booking_reference: bookingRef,
                    guest_email: guestEmail,
                    cancellation_reason: cancellationReason
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('cancel-result', data.success, data.message, data.data);
            })
            .catch(error => {
                showResult('cancel-result', false, 'Network error: ' + error.message);
            });
        }
    </script>
</body>
</html>

