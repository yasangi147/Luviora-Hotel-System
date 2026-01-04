<?php
/**
 * Clark Authentication Check
 * Include this file at the top of all clark pages to ensure user is logged in
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if clark is logged in
if (!isset($_SESSION['clark_logged_in']) || $_SESSION['clark_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Refresh session timeout (30 minutes of inactivity)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();
?>

