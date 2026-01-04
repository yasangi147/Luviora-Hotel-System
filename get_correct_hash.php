<?php
// Generate the correct hash for admin123
$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);
echo "Correct hash for 'admin123':\n";
echo $hash . "\n\n";

// Verify it works
if (password_verify('admin123', $hash)) {
    echo "✓ Verification successful!\n";
} else {
    echo "✗ Verification failed!\n";
}
?>

