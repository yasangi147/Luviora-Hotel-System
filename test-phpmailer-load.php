<?php
/**
 * Test PHPMailer Loading
 */

echo "<h1>Testing PHPMailer Installation</h1>";

// Check if vendor/autoload.php exists
$autoloadPath = __DIR__ . '/vendor/autoload.php';
echo "<p><strong>Autoload Path:</strong> $autoloadPath</p>";

if (file_exists($autoloadPath)) {
    echo "<p style='color: green;'>✅ Autoload file exists</p>";
    
    // Load the autoloader
    require_once $autoloadPath;
    echo "<p style='color: green;'>✅ Autoload file loaded</p>";
    
    // Try to create PHPMailer instance
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        echo "<p style='color: green;'>✅ PHPMailer class loaded successfully!</p>";
        echo "<p><strong>PHPMailer Version:</strong> " . $mail::VERSION . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating PHPMailer instance: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Autoload file NOT found</p>";
}

// Check PHPMailer files
echo "<h2>PHPMailer Files Check</h2>";
$phpmailerDir = __DIR__ . '/vendor/phpmailer/phpmailer';
echo "<p><strong>PHPMailer Directory:</strong> $phpmailerDir</p>";

if (is_dir($phpmailerDir)) {
    echo "<p style='color: green;'>✅ PHPMailer directory exists</p>";
    
    $files = scandir($phpmailerDir);
    echo "<p><strong>Files in directory:</strong></p><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ PHPMailer directory NOT found</p>";
}
?>

