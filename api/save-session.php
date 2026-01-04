<?php
/**
 * Save Session API
 * Saves reservation data to PHP session
 */

session_start();
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    // Save to session
    $_SESSION['reservation'] = $data;
    
    echo json_encode([
        'success' => true,
        'message' => 'Reservation data saved',
        'data' => $_SESSION['reservation']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No data received'
    ]);
}
?>

