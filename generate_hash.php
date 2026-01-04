<?php
/**
 * Generate Password Hash for admin123
 */

// Generate hash for "admin123"
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Password Hash Generator</h2>";
echo "<p><strong>Password:</strong> $password</p>";
echo "<p><strong>Hash:</strong></p>";
echo "<code style='background: #f0f0f0; padding: 10px; display: block; word-break: break-all;'>$hash</code>";

echo "<hr>";
echo "<h3>Verification Test:</h3>";
if (password_verify($password, $hash)) {
    echo "<p style='color: green;'>✓ Hash verification PASSED!</p>";
} else {
    echo "<p style='color: red;'>✗ Hash verification FAILED!</p>";
}

echo "<hr>";
echo "<h3>Testing Old Hash:</h3>";
$oldHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "<p>Old hash from database:</p>";
echo "<code style='background: #f0f0f0; padding: 10px; display: block; word-break: break-all;'>$oldHash</code>";

echo "<p>Testing with 'admin123':</p>";
if (password_verify('admin123', $oldHash)) {
    echo "<p style='color: green;'>✓ Old hash works with 'admin123'</p>";
} else {
    echo "<p style='color: red;'>✗ Old hash does NOT work with 'admin123'</p>";
}

echo "<p>Testing with 'password':</p>";
if (password_verify('password', $oldHash)) {
    echo "<p style='color: green;'>✓ Old hash works with 'password'</p>";
} else {
    echo "<p style='color: red;'>✗ Old hash does NOT work with 'password'</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 {
        color: #333;
        border-bottom: 3px solid #667eea;
        padding-bottom: 10px;
    }
    code {
        font-size: 12px;
    }
</style>

