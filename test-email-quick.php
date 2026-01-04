<?php
/**
 * Quick Email Test - Tests the send-qr-email.php endpoint
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Email Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #a0522d;
        }
        .btn {
            padding: 15px 30px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        .btn:hover {
            background: #ff5252;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        input {
            padding: 10px;
            width: 100%;
            max-width: 400px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Quick Email Test</h1>
        <p>This will test the email sending functionality with a sample QR code.</p>
        
        <div>
            <label><strong>Your Email:</strong></label><br>
            <input type="email" id="testEmail" placeholder="your-email@example.com" value="yasangiuduwawala@gmail.com">
        </div>
        
        <button class="btn" onclick="testEmail()">
            📧 Send Test Email with QR Code
        </button>
        
        <div id="result" class="result"></div>
    </div>

    <script>
        async function testEmail() {
            const email = document.getElementById('testEmail').value;
            const resultDiv = document.getElementById('result');
            const btn = event.target;
            
            if (!email) {
                alert('Please enter your email address');
                return;
            }
            
            // Disable button
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
            
            // Show loading
            resultDiv.className = 'result info';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Sending test email...';
            
            // Prepare test data
            const emailData = {
                qrCodeUrl: 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TEST-BOOKING-' + Date.now(),
                guestEmail: email,
                bookingRef: 'TEST-' + Date.now(),
                guestName: 'Test User',
                roomName: 'Superior Double Room',
                checkIn: 'Nov 10, 2025',
                checkOut: 'Nov 12, 2025'
            };
            
            console.log('Sending email data:', emailData);
            
            try {
                const response = await fetch('send-qr-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(emailData)
                });
                
                console.log('Response status:', response.status);
                
                const text = await response.text();
                console.log('Raw response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
                
                console.log('Parsed data:', data);
                
                // Show result
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <h3>✅ Success!</h3>
                        <p><strong>Message:</strong> ${data.message}</p>
                        <p><strong>Method:</strong> ${data.method}</p>
                        ${data.error_detail ? '<p><strong>Note:</strong> ' + data.error_detail + '</p>' : ''}
                        ${data.saved_to ? '<p><strong>Saved to:</strong> ' + data.saved_to + '</p>' : ''}
                        <hr>
                        <p><strong>What to do next:</strong></p>
                        <ul>
                            <li>Check your email inbox: <strong>${email}</strong></li>
                            <li>Also check your spam/junk folder</li>
                            <li>If email didn't arrive, the QR code was saved to the server</li>
                        </ul>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <h3>❌ Failed</h3>
                        <p><strong>Error:</strong> ${data.message}</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
                
            } catch (error) {
                console.error('Error:', error);
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <h3>❌ Network Error</h3>
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p>This usually means:</p>
                    <ul>
                        <li>The send-qr-email.php file has a syntax error</li>
                        <li>The server is not responding</li>
                        <li>There's a network connection issue</li>
                    </ul>
                    <p><strong>Check the browser console for more details.</strong></p>
                `;
            } finally {
                // Re-enable button
                btn.disabled = false;
                btn.innerHTML = '📧 Send Test Email with QR Code';
            }
        }
    </script>
</body>
</html>

