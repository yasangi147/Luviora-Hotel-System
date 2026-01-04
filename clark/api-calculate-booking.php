<?php
/**
 * API: Calculate Booking Amount
 * Calculates total amount based on room, dates, and extra services
 */

require_once 'auth_check.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$db = getDB();

try {
    $room_id = intval($_POST['room_id'] ?? 0);
    $check_in_date = $_POST['check_in_date'] ?? '';
    $check_out_date = $_POST['check_out_date'] ?? '';
    $service_ids = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];
    $num_guests = intval($_POST['num_guests'] ?? 1);

    if (!$room_id || !$check_in_date || !$check_out_date) {
        throw new Exception('Missing required parameters');
    }

    // Calculate number of nights
    $check_in = new DateTime($check_in_date);
    $check_out = new DateTime($check_out_date);
    $nights = $check_out->diff($check_in)->days;

    if ($nights <= 0) {
        throw new Exception('Check-out date must be after check-in date');
    }

    // Get room price
    $stmt = $db->prepare("SELECT price_per_night, room_name, room_number FROM rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();

    if (!$room) {
        throw new Exception('Room not found');
    }

    // Calculate room total
    $room_total = $room['price_per_night'] * $nights;

    // Calculate services total
    $services_total = 0;
    $services_breakdown = [];

    if (!empty($service_ids)) {
        $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
        $stmt = $db->prepare("SELECT * FROM extra_services WHERE service_id IN ($placeholders) AND is_active = 1");
        $stmt->execute($service_ids);
        $services = $stmt->fetchAll();

        foreach ($services as $service) {
            $service_price = 0;

            switch ($service['pricing_type']) {
                case 'per_booking':
                    $service_price = $service['base_price'];
                    break;
                case 'per_night':
                    $service_price = $service['base_price'] * $nights;
                    break;
                case 'per_person':
                    $service_price = $service['base_price'] * $num_guests;
                    break;
                case 'per_person_per_night':
                    $service_price = $service['base_price'] * $num_guests * $nights;
                    break;
            }

            $services_total += $service_price;
            $services_breakdown[] = [
                'service_id' => $service['service_id'],
                'service_name' => $service['service_name'],
                'pricing_type' => $service['pricing_type'],
                'base_price' => floatval($service['base_price']),
                'calculated_price' => floatval($service_price)
            ];
        }
    }

    // Calculate subtotal and tax
    $subtotal = $room_total + $services_total;
    $tax_rate = 0.10; // 10% tax
    $tax_amount = $subtotal * $tax_rate;
    $total_amount = $subtotal + $tax_amount;

    echo json_encode([
        'success' => true,
        'nights' => $nights,
        'room' => [
            'room_id' => $room_id,
            'room_name' => $room['room_name'],
            'room_number' => $room['room_number'],
            'price_per_night' => floatval($room['price_per_night']),
            'room_total' => floatval($room_total)
        ],
        'services' => $services_breakdown,
        'services_total' => floatval($services_total),
        'subtotal' => floatval($subtotal),
        'tax_amount' => floatval($tax_amount),
        'total_amount' => floatval($total_amount)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

