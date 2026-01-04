<?php
/**
 * Logout Handler
 * Clears user session and redirects to home page
 */

session_start();

// Destroy session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
?>

