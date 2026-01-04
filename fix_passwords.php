<?php
/**
 * Password Fix Script
 * This script will update all user passwords to "admin123" with proper bcrypt hashing
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Password Fix - Luviora Hotel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        code {
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
            font-size: 12px;
            word-break: break-all;
        }
        table {
            width: 100%;
            background: white;
            margin: 20px 0;
            border-collapse: collapse;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            font-weight: bold;
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>";

echo "<h2>🔧 Luviora Hotel - Password Fix Script</h2>";

try {
    $db = getDB();

    // First, check what the old hash was
    $stmt = $db->prepare("SELECT password FROM users WHERE email = 'admin@luviora.com' LIMIT 1");
    $stmt->execute();
    $oldUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($oldUser) {
        echo "<p><strong>Old Password Hash:</strong></p>";
        echo "<code>{$oldUser['password']}</code><br><br>";

        // Test if old hash works with "password"
        if (password_verify('password', $oldUser['password'])) {
            echo "<div class='error'><strong>⚠ Problem Found!</strong><br>";
            echo "The old hash corresponds to password 'password', NOT 'admin123'.<br>";
            echo "This is why login was failing!</div>";
        }
    }

    // Generate correct password hash for "admin123"
    $correctHash = password_hash('admin123', PASSWORD_DEFAULT);

    echo "<p><strong>New Password Hash Generated for 'admin123':</strong></p>";
    echo "<code>$correctHash</code><br><br>";

    // Update all users with the correct password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email IN ('admin@luviora.com', 'clark@luviora.com', 'staff@luviora.com', 'john.doe@example.com')");
    $stmt->execute([$correctHash]);

    echo "<div class='success'><strong>✓ Password updated successfully for all users!</strong></div>";

    // Verify the update
    echo "<h3>📊 Verification:</h3>";
    $stmt = $db->query("SELECT name, email, role FROM users WHERE email IN ('admin@luviora.com', 'clark@luviora.com', 'staff@luviora.com', 'john.doe@example.com')");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Name</th><th>Email</th><th>Role</th><th>Password</th></tr>";

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><strong>{$user['role']}</strong></td>";
        echo "<td><code>admin123</code></td>";
        echo "</tr>";
    }

    echo "</table>";

    // Test login
    echo "<h3>🧪 Login Test:</h3>";
    $testEmail = 'admin@luviora.com';
    $testPassword = 'admin123';

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($testPassword, $user['password'])) {
        echo "<div class='success'>";
        echo "<strong>✓ Login Test PASSED!</strong><br><br>";
        echo "You can now login with:<br>";
        echo "<strong>Email:</strong> admin@luviora.com<br>";
        echo "<strong>Password:</strong> admin123";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>✗ Login Test FAILED!</strong><br>";
        echo "Please contact support.";
        echo "</div>";
    }

    echo "<hr>";
    echo "<h3>🔑 All Default Credentials:</h3>";
    echo "<table>";
    echo "<tr><th>Role</th><th>Email</th><th>Password</th><th>Login URL</th></tr>";
    echo "<tr><td><strong>Admin</strong></td><td>admin@luviora.com</td><td><code>admin123</code></td><td><a href='admin/login.php' class='btn'>Admin Login</a></td></tr>";
    echo "<tr><td><strong>Clark</strong></td><td>clark@luviora.com</td><td><code>admin123</code></td><td><a href='clark/login.php' class='btn'>Clark Login</a></td></tr>";
    echo "<tr><td><strong>Staff</strong></td><td>staff@luviora.com</td><td><code>admin123</code></td><td><a href='staff/login.php' class='btn'>Staff Login</a></td></tr>";
    echo "<tr><td><strong>Guest</strong></td><td>john.doe@example.com</td><td><code>admin123</code></td><td><a href='profile/index.php' class='btn'>Profile</a></td></tr>";
    echo "</table>";

    echo "<br><div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='index.html' class='btn'>← Back to Homepage</a> ";
    echo "<a href='admin/login.php' class='btn'>Admin Login →</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>


