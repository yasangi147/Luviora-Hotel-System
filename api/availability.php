<?php
/**
 * Availability API
 * Handles room availability checking and filtering
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Check room availability for date range
 */
function checkAvailability() {
    try {
        $db = getDB();
        
        // Get parameters
        $checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : null;
        $checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : null;
        $numRooms = isset($_GET['num_rooms']) ? (int)$_GET['num_rooms'] : 1;
        $numGuests = isset($_GET['num_guests']) ? (int)$_GET['num_guests'] : 2;
        
        // Validate dates
        if (!$checkIn || !$checkOut) {
            sendResponse(false, 'Check-in and check-out dates are required');
        }
        
        $checkInDate = new DateTime($checkIn);
        $checkOutDate = new DateTime($checkOut);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($checkInDate < $today) {
            sendResponse(false, 'Check-in date cannot be in the past');
        }
        
        if ($checkOutDate <= $checkInDate) {
            sendResponse(false, 'Check-out date must be after check-in date');
        }
        
        // Calculate nights
        $nights = $checkInDate->diff($checkOutDate)->days;
        
        // Call stored procedure to get available rooms
        $stmt = $db->prepare("CALL CheckRoomAvailabilityRange(?, ?, ?, ?)");
        $stmt->execute([$checkIn, $checkOut, $numRooms, $numGuests]);
        
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        // Process room data
        foreach ($rooms as &$room) {
            // Parse additional images if JSON
            if ($room['additional_images']) {
                $room['additional_images'] = json_decode($room['additional_images'], true);
            }
            
            // Calculate total price for the stay
            $room['total_price'] = $room['price_per_night'] * $nights;
            $room['nights'] = $nights;
            
            // Add amenities array
            $room['amenities_array'] = $room['amenities'] ? explode(', ', $room['amenities']) : [];
        }
        
        sendResponse(true, count($rooms) . ' rooms available', [
            'rooms' => $rooms,
            'search_params' => [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'num_rooms' => $numRooms,
                'num_guests' => $numGuests,
                'nights' => $nights
            ]
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error checking availability: ' . $e->getMessage());
    }
}

/**
 * Get all available rooms with filters
 */
function getAvailableRooms() {
    try {
        $db = getDB();
        
        // Get filter parameters
        $checkIn = isset($_GET['check_in']) ? $_GET['check_in'] : null;
        $checkOut = isset($_GET['check_out']) ? $_GET['check_out'] : null;
        $roomType = isset($_GET['room_type']) ? $_GET['room_type'] : null;
        $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
        $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
        $viewType = isset($_GET['view_type']) ? $_GET['view_type'] : null;
        $bedType = isset($_GET['bed_type']) ? $_GET['bed_type'] : null;
        $roomStyle = isset($_GET['room_style']) ? $_GET['room_style'] : null;
        $idealFor = isset($_GET['ideal_for']) ? $_GET['ideal_for'] : null;
        $minGuests = isset($_GET['min_guests']) ? (int)$_GET['min_guests'] : 1;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'price-low';
        
        // Build query
        $query = "SELECT DISTINCT
            r.room_id,
            r.room_number,
            r.room_name,
            r.room_type,
            r.floor,
            r.price_per_night,
            r.max_occupancy,
            r.size_sqm,
            r.bed_type,
            r.view_type,
            r.room_style,
            r.ideal_for,
            r.description,
            r.room_image,
            r.additional_images,
            r.rating,
            r.popularity_score,
            r.is_pet_friendly,
            r.is_accessible,
            r.is_smoking_allowed,
            r.free_cancellation,
            r.breakfast_included,
            GROUP_CONCAT(DISTINCT rs.spec_name SEPARATOR ', ') as amenities
        FROM rooms r
        LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
        LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
        WHERE r.is_active = TRUE";
        
        $params = [];
        
        // Add filters
        if ($minGuests) {
            $query .= " AND r.max_occupancy >= ?";
            $params[] = $minGuests;
        }
        
        if ($roomType) {
            $query .= " AND r.room_type = ?";
            $params[] = $roomType;
        }
        
        if ($minPrice) {
            $query .= " AND r.price_per_night >= ?";
            $params[] = $minPrice;
        }
        
        if ($maxPrice) {
            $query .= " AND r.price_per_night <= ?";
            $params[] = $maxPrice;
        }
        
        if ($viewType) {
            $query .= " AND r.view_type = ?";
            $params[] = $viewType;
        }
        
        if ($bedType) {
            $query .= " AND r.bed_type LIKE ?";
            $params[] = "%$bedType%";
        }
        
        if ($roomStyle) {
            $query .= " AND r.room_style = ?";
            $params[] = $roomStyle;
        }
        
        if ($idealFor) {
            $query .= " AND r.ideal_for = ?";
            $params[] = $idealFor;
        }
        
        // Exclude booked rooms if dates provided
        if ($checkIn && $checkOut) {
            $query .= " AND r.room_id NOT IN (
                SELECT DISTINCT room_id
                FROM bookings
                WHERE booking_status IN ('confirmed', 'checked_in')
                AND (
                    (check_in_date <= ? AND check_out_date > ?)
                    OR (check_in_date < ? AND check_out_date >= ?)
                    OR (check_in_date >= ? AND check_out_date <= ?)
                )
            )";
            $params[] = $checkIn;
            $params[] = $checkIn;
            $params[] = $checkOut;
            $params[] = $checkOut;
            $params[] = $checkIn;
            $params[] = $checkOut;
        }
        
        $query .= " GROUP BY r.room_id";
        
        // Add sorting
        switch ($sortBy) {
            case 'price-high':
                $query .= " ORDER BY r.price_per_night DESC";
                break;
            case 'rating':
                $query .= " ORDER BY r.rating DESC, r.popularity_score DESC";
                break;
            case 'popular':
                $query .= " ORDER BY r.popularity_score DESC, r.rating DESC";
                break;
            case 'size':
                $query .= " ORDER BY r.size_sqm DESC";
                break;
            case 'price-low':
            default:
                $query .= " ORDER BY r.price_per_night ASC";
                break;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process room data
        $nights = 1;
        if ($checkIn && $checkOut) {
            $checkInDate = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);
            $nights = $checkInDate->diff($checkOutDate)->days;
        }
        
        foreach ($rooms as &$room) {
            if ($room['additional_images']) {
                $room['additional_images'] = json_decode($room['additional_images'], true);
            }
            $room['total_price'] = $room['price_per_night'] * $nights;
            $room['nights'] = $nights;
            $room['amenities_array'] = $room['amenities'] ? explode(', ', $room['amenities']) : [];
        }
        
        sendResponse(true, count($rooms) . ' rooms found', [
            'rooms' => $rooms,
            'total_count' => count($rooms),
            'filters_applied' => array_filter([
                'room_type' => $roomType,
                'price_range' => ($minPrice || $maxPrice) ? "$minPrice-$maxPrice" : null,
                'view_type' => $viewType,
                'bed_type' => $bedType,
                'room_style' => $roomStyle,
                'ideal_for' => $idealFor,
                'min_guests' => $minGuests,
                'sort_by' => $sortBy
            ])
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error fetching rooms: ' . $e->getMessage());
    }
}

/**
 * Get room details by ID
 */
function getRoomDetails() {
    try {
        $db = getDB();
        $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
        
        if (!$roomId) {
            sendResponse(false, 'Room ID is required');
        }
        
        $stmt = $db->prepare("
            SELECT r.*, GROUP_CONCAT(DISTINCT rs.spec_name SEPARATOR ', ') as amenities
            FROM rooms r
            LEFT JOIN room_spec_map rsm ON r.room_id = rsm.room_id
            LEFT JOIN room_specs rs ON rsm.spec_id = rs.spec_id
            WHERE r.room_id = ?
            GROUP BY r.room_id
        ");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            sendResponse(false, 'Room not found');
        }
        
        if ($room['additional_images']) {
            $room['additional_images'] = json_decode($room['additional_images'], true);
        }
        $room['amenities_array'] = $room['amenities'] ? explode(', ', $room['amenities']) : [];
        
        sendResponse(true, 'Room details retrieved', $room);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error fetching room details: ' . $e->getMessage());
    }
}

// Route requests
$action = isset($_GET['action']) ? $_GET['action'] : 'check';

switch ($action) {
    case 'check':
        checkAvailability();
        break;
    case 'filter':
        getAvailableRooms();
        break;
    case 'details':
        getRoomDetails();
        break;
    default:
        sendResponse(false, 'Invalid action');
}
?>

