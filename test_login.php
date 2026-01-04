<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Test - Luviora Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:active {
            transform: translateY(0);
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
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
        .credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .credentials h3 {
            margin-bottom: 10px;
            color: #333;
            font-size: 16px;
        }
        .credentials table {
            width: 100%;
            font-size: 12px;
        }
        .credentials td {
            padding: 5px;
        }
        .credentials td:first-child {
            font-weight: 600;
            color: #667eea;
        }
        .quick-fill {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .quick-fill button {
            flex: 1;
            padding: 8px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        .quick-fill button:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Login Test</h1>
        <p class="subtitle">Test the login functionality with default credentials</p>

        <div class="credentials">
            <h3>Default Credentials:</h3>
            <table>
                <tr>
                    <td>Admin:</td>
                    <td>admin@luviora.com / admin123</td>
                </tr>
                <tr>
                    <td>Clark:</td>
                    <td>clark@luviora.com / admin123</td>
                </tr>
                <tr>
                    <td>Staff:</td>
                    <td>staff@luviora.com / admin123</td>
                </tr>
                <tr>
                    <td>Guest:</td>
                    <td>john.doe@example.com / admin123</td>
                </tr>
            </table>
        </div>

        <div class="quick-fill">
            <button onclick="fillCredentials('admin@luviora.com', 'admin123')">Admin</button>
            <button onclick="fillCredentials('clark@luviora.com', 'admin123')">Clark</button>
            <button onclick="fillCredentials('staff@luviora.com', 'admin123')">Staff</button>
            <button onclick="fillCredentials('john.doe@example.com', 'admin123')">Guest</button>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn">Test Login</button>
        </form>

        <div id="result" class="result"></div>
    </div>

    <script>
        function fillCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const resultDiv = document.getElementById('result');

            // Show loading
            resultDiv.style.display = 'block';
            resultDiv.className = 'result';
            resultDiv.innerHTML = '⏳ Testing login...';

            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✓ Login Successful!</strong><br><br>
                        <strong>User Details:</strong><br>
                        Name: ${data.data.name}<br>
                        Email: ${data.data.email}<br>
                        Role: ${data.data.role}<br>
                        Redirect: ${data.data.redirect}<br><br>
                        <a href="${data.data.redirect}" style="color: #155724; font-weight: 600;">Go to Dashboard →</a>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <strong>✗ Login Failed!</strong><br><br>
                        ${data.message}<br><br>
                        <small>Make sure you've run the password fix script first:<br>
                        <a href="fix_passwords.php" style="color: #721c24; font-weight: 600;">Run Password Fix →</a></small>
                    `;
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <strong>✗ Error!</strong><br><br>
                    ${error.message}<br><br>
                    <small>Make sure the API is accessible and the database is connected.</small>
                `;
            }
        });
    </script>
</body>
</html>

