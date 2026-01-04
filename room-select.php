<?php
/**
 * Room Selection Page - Luviora Hotel System
 * Displays available rooms from database with filtering
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// ========================================
// HANDLE ROOM SELECTION (Book Now Button)
// ========================================
$bookingError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_room_id'])) {
    $selectedRoomId = intval($_POST['selected_room_id']);

    // Get the selected room details from database
    try {
        $db = getDB();

        // Fetch room details (removed status check - availability is already checked)
        $stmt = $db->prepare("SELECT * FROM rooms WHERE room_id = ? AND is_active = TRUE");
        $stmt->execute([$selectedRoomId]);
        $selectedRoom = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selectedRoom) {
            // Get room amenities/features from room_spec_map
            $amenitiesStmt = $db->prepare("
                SELECT rs.spec_name
                FROM room_spec_map rsm
                JOIN room_specs rs ON rsm.spec_id = rs.spec_id
                WHERE rsm.room_id = ? AND rs.is_active = TRUE
                ORDER BY rs.display_order
            ");
            $amenitiesStmt->execute([$selectedRoomId]);
            $amenitiesArray = $amenitiesStmt->fetchAll(PDO::FETCH_COLUMN);
            $amenitiesString = implode(',', $amenitiesArray);

            // Get reservation data for calculating total
            $reservationData = null;
            if (isset($_SESSION['reservationData'])) {
                // Handle both JSON string and array formats
                if (is_string($_SESSION['reservationData'])) {
                    $reservationData = json_decode($_SESSION['reservationData'], true);
                } else {
                    $reservationData = $_SESSION['reservationData'];
                }
            }

            if (!$reservationData) {
                $bookingError = "Reservation data not found. Please start over.";
            } else {
                $nights = $reservationData['nights'];
                $pricePerNight = floatval($selectedRoom['price_per_night']);
                $totalAmount = $pricePerNight * $nights;

                // Prepare complete room data for booking page
                $roomDataForBooking = [
                    'room_id' => $selectedRoom['room_id'],
                    'room_number' => $selectedRoom['room_number'],
                    'room_name' => $selectedRoom['room_name'],
                    'room_type' => $selectedRoom['room_type'],
                    'price' => $pricePerNight, // Changed from price_per_night to match booking.php
                    'total_amount' => $totalAmount,
                    'bed_type' => $selectedRoom['bed_type'],
                    'view_type' => $selectedRoom['view_type'],
                    'max_occupancy' => $selectedRoom['max_occupancy'],
                    'size_sqm' => $selectedRoom['size_sqm'],
                    'image' => $selectedRoom['room_image'], // Changed from room_image to match booking.php
                    'description' => $selectedRoom['description'],
                    'amenities' => $amenitiesString,
                    'floor' => $selectedRoom['floor'],
                    'room_style' => $selectedRoom['room_style'],
                    'ideal_for' => $selectedRoom['ideal_for'],
                    'rating' => $selectedRoom['rating'],
                    'is_pet_friendly' => $selectedRoom['is_pet_friendly'],
                    'is_accessible' => $selectedRoom['is_accessible'],
                    'is_smoking_allowed' => $selectedRoom['is_smoking_allowed'],
                    'free_cancellation' => $selectedRoom['free_cancellation'],
                    'breakfast_included' => $selectedRoom['breakfast_included']
                ];

                // Store selected room in session (as array for booking.php compatibility)
                $_SESSION['selected_rooms'] = [$roomDataForBooking]; // Array format expected by booking.php

                // Also keep old format for backward compatibility
                $_SESSION['selectedRoom'] = json_encode($roomDataForBooking);

                // Convert reservationData to the format expected by booking.php
                if (is_string($_SESSION['reservationData'])) {
                    $reservationData = json_decode($_SESSION['reservationData'], true);
                } else {
                    $reservationData = $_SESSION['reservationData'];
                }

                // Convert keys from availability.php format to booking.php format
                $_SESSION['reservationData'] = [
                    'check_in' => $reservationData['checkInDate'] ?? $reservationData['check_in'] ?? '',
                    'check_out' => $reservationData['checkOutDate'] ?? $reservationData['check_out'] ?? '',
                    'nights' => $reservationData['nights'] ?? 0,
                    'adults' => $reservationData['adults'] ?? 1,
                    'children' => $reservationData['children'] ?? 0,
                    'rooms' => $reservationData['rooms'] ?? 1
                ];

                // Redirect to booking page
                header('Location: booking.php');
                exit;
            }
        } else {
            $bookingError = "Selected room is not available. Please choose another room.";
        }
    } catch (PDOException $e) {
        $bookingError = "An error occurred while processing your request. Please try again.";
        error_log("Room selection error: " . $e->getMessage());
    }
}

// Get reservation data from session
$reservationData = null;
if (isset($_SESSION['reservationData'])) {
    // Handle both JSON string and array formats
    if (is_string($_SESSION['reservationData'])) {
        $reservationData = json_decode($_SESSION['reservationData'], true);
    } else {
        $reservationData = $_SESSION['reservationData'];
    }
}

// If no reservation data, redirect to availability page
if (!$reservationData) {
    header('Location: availability.php');
    exit;
}

// Extract reservation details
$checkIn = $reservationData['checkInDate'];
$checkOut = $reservationData['checkOutDate'];
$adults = $reservationData['adults'];
$children = $reservationData['children'];
$nights = $reservationData['nights'];
$numGuests = $adults + $children;

// Get available rooms from database
$availableRooms = getAvailableRooms($checkIn, $checkOut, $numGuests);

// ========================================
// FETCH FILTER OPTIONS FROM DATABASE
// ========================================

// Get all unique room types from available rooms
$roomTypes = [];
foreach ($availableRooms as $room) {
    if (!empty($room['room_type']) && !in_array($room['room_type'], $roomTypes)) {
        $roomTypes[] = $room['room_type'];
    }
}
sort($roomTypes);

// Get all unique view types from available rooms
$viewTypes = [];
foreach ($availableRooms as $room) {
    if (!empty($room['view_type']) && $room['view_type'] !== 'none' && !in_array($room['view_type'], $viewTypes)) {
        $viewTypes[] = $room['view_type'];
    }
}
sort($viewTypes);

// Get all unique bed types from available rooms
$bedTypes = [];
foreach ($availableRooms as $room) {
    if (!empty($room['bed_type'])) {
        // Extract bed type (e.g., "King" from "1 King Bed")
        if (preg_match('/(King|Queen|Twin|Double|Single)/i', $room['bed_type'], $matches)) {
            $bedType = ucfirst(strtolower($matches[1]));
            if (!in_array($bedType, $bedTypes)) {
                $bedTypes[] = $bedType;
            }
        }
    }
}
sort($bedTypes);

// Get all room specifications/amenities from database
try {
    $db = getDB(); // Get database connection
    $stmt = $db->query("SELECT spec_id, spec_name, spec_icon FROM room_specs WHERE is_active = TRUE ORDER BY display_order, spec_name");
    $roomFeatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $roomFeatures = [];
    error_log("Error fetching room specs: " . $e->getMessage());
}

// Calculate price range from available rooms
$minPrice = !empty($availableRooms) ? min(array_column($availableRooms, 'price_per_night')) : 0;
$maxPrice = !empty($availableRooms) ? max(array_column($availableRooms, 'price_per_night')) : 1000;

// Define price range brackets
$priceRanges = [
    ['min' => 0, 'max' => 100, 'label' => '$0 - $100'],
    ['min' => 100, 'max' => 150, 'label' => '$100 - $150'],
    ['min' => 150, 'max' => 200, 'label' => '$150 - $200'],
    ['min' => 200, 'max' => 300, 'label' => '$200 - $300'],
    ['min' => 300, 'max' => 500, 'label' => '$300 - $500'],
    ['min' => 500, 'max' => 99999, 'label' => '$500+']
];

// Filter price ranges to only show relevant ones
$relevantPriceRanges = [];
foreach ($priceRanges as $range) {
    // Check if any room falls in this price range
    foreach ($availableRooms as $room) {
        if ($room['price_per_night'] >= $range['min'] && $room['price_per_night'] < $range['max']) {
            $relevantPriceRanges[] = $range;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zxx">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Select Your Room | Luviora Hotel</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!--Default CSS-->
    <link href="css/default.css" rel="stylesheet" type="text/css" />
    <!--Custom CSS-->
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <!--Plugin CSS-->
    <link href="css/plugin.css" rel="stylesheet" type="text/css" />
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css" />
    <!-- Rounded Corners CSS -->
    <link href="css/rounded-corners.css" rel="stylesheet" type="text/css" />
    <!-- Modern Header Theme -->
    <link href="css/modern-header.css" rel="stylesheet" type="text/css" />
    <!-- Footer Coral Theme -->
    <link href="css/footer-coral.css" rel="stylesheet" type="text/css" />
    <!-- News and Reviews Coral Theme -->
    <link href="css/news-reviews-coral.css" rel="stylesheet" type="text/css" />
    <!-- Elegant Color Scheme -->
    <link href="css/elegant-colors.css" rel="stylesheet" type="text/css" />
    <!-- Luxury About Styles -->
    <link href="css/aboutus-luxury.css" rel="stylesheet" type="text/css" />
    <!-- Elegant Rooms Styles -->
    <link href="css/elegant-rooms.css" rel="stylesheet" type="text/css" />
    <!-- Modern Reviews & Gallery Styles -->
    <link href="css/modern-reviews-gallery.css" rel="stylesheet" type="text/css" />
    
    <style>
      #back-to-top a {
        display: block;
        width: 40px;
        height: 40px;
        background: #a0522d;
        position: relative;
      }

      h1, h2 {
        font-weight: 700;
        color: #343a40;
        font-family: 'Playfair Display', serif;
        margin: 0 0 15px;
        line-height: 1.4;
        text-transform: uppercase;
      }

      .nav-btn .btn-orange {
        background: #C38370 !important; 
        color: #FAF9F6 !important;
        border: none !important;
        padding: 12px 28px;
        border-radius: 4px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: all 0.3s ease;
        margin-left: 15px;
        box-shadow: 0 2px 10px rgba(195, 131, 112, 0.2);
      }

      .nav-btn .btn-orange:hover {
        background: #FFFFFF !important;
        color: #000000 !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
      }

      .table-item .form-btn .btn-orange {
        background-color:  #C38370 !important;
        color: #FAF9F6 !important;
        padding: 10px 25px;
        transition: all 0.3s ease;
      }

      .table-item .form-btn .btn.btn-orange:hover {
        background-color:  #a55d42 !important;
        color:  #ffffff !important;
        border-color:  #d19a7a00 !important;
        box-shadow:  0 8px 15px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
      } 

      .btn-orange:not(.navbar .btn-orange):not(.nav .btn-orange):hover {
        background: #a55d42 !important;
        color: #ffffff !important;
        border-color: #d19a7a00 !important;
        box-shadow:  0 8px 15px rgba(0, 0, 0, 0.15);
        transition: 0.3s ease;
      }

      .footer-logo{
        padding-bottom: 90px;
        padding-top: 10px;
        margin-top: -10px;
      }

      .section-btn {
        text-align: center;
        padding-top: 40px;
      }

      section.breadcrumb-outer {
        background: url(images/gallery/gallery22.jpg) no-repeat;
        background-size: cover;
        background-position: center;
        position: relative;
        text-align: center;
        padding: 260px 0 150px;
      }

      .award-content{
        background-color: #a55d42;
      }

      /* RESERVATION STEPS INDICATOR STYLES */
      .reservation-steps {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 60px 0 50px;
        position: relative;
      }

      .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
      }

      .step-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        color: #d4a574;
        background-color: rgba(212, 165, 116, 0.3);
        border: 3px solid rgba(212, 165, 116, 0.5);
        transition: all 0.3s ease;
        margin-bottom: 15px;
      }

      .step-item.active .step-circle {
        background-color: #a0522d;
        color: #ffffff;
        border-color: #a0522d;
        box-shadow: 0 4px 15px rgba(160, 82, 45, 0.3);
      }

      .step-label {
        font-size: 14px;
        font-weight: 600;
        color: #8b6f47;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .step-item.active .step-label {
        color: #a0522d;
        font-weight: 700;
      }

      .step-connector {
        height: 3px;
        width: 150px;
        background-color: rgba(212, 165, 116, 0.3);
        position: relative;
        margin: 0 -20px;
        top: -45px;
        z-index: 1;
      }

      .step-item.active + .step-connector {
        background-color: #a0522d;
      }

      /* MODERN FILTER SECTION STYLES */
      body {
        background-color: #faf8f5;
      }

      .modern-filter-section {
        background: #ffffff;
        border: 1px solid #d4a574;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 40px;
        box-shadow: 0 4px 20px rgba(107, 87, 68, 0.08);
      }

      /* Filter Search Bar */
      .filter-search-bar { position: relative; margin-bottom: 25px; }
      .filter-search-input { width: 100%; padding: 15px 50px 15px 20px; font-size: 16px; border: 1px solid #d4a574; border-radius: 8px; background: #ffffff; color: #6b5744; transition: all 0.3s ease; }
      .filter-search-input:focus { outline: none; border-color: #a0522d; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1); }
      .filter-search-input::placeholder { color: #8b6f47; opacity: 0.7; }
      .filter-search-icon { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #a0522d; font-size: 18px; pointer-events: none; }

      /* Inline Filters */
      .inline-filters-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px; }
      .inline-filter-item { position: relative; }
      .inline-filter-label { display: block; font-size: 11px; font-weight: 700; color: #a0522d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
      .inline-filter-select { width: 100%; padding: 1px 35px 12px 12px; font-size: 14px; font-weight: 600; color: #6b5744; background: #ffffff; border: 1px solid #d4a574; border-radius: 8px; cursor: pointer; appearance: none; transition: all 0.3s ease; }
      .inline-filter-select:focus { outline: none; border-color: #a0522d; box-shadow: 0 0 0 3px rgba(160, 82, 45, 0.1); }
      .inline-filter-arrow { position: absolute; right: 12px; bottom: 12px; color: #8b6f47; font-size: 10px; pointer-events: none; }

      /* Tag Filters */
      .tag-filters-section { margin-bottom: 25px; }
      .tag-category { margin-bottom: 20px; }
      .tag-category-label { font-size: 12px; font-weight: 700; color: #8b6f47; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; display: block; }
      .tag-filters-container { display: flex; flex-wrap: wrap; gap: 10px; }
      .filter-tag { padding: 8px 18px; font-size: 13px; font-weight: 600; color: #6b5744; background: #f5f1eb; border: 1px solid #d4a574; border-radius: 20px; cursor: pointer; transition: all 0.3s ease; user-select: none; }
      .filter-tag:hover { background: #8b4513; color: #ffffff; border-color: #8b4513; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(139, 69, 19, 0.2); }
      .filter-tag.active { background: #a0522d; color: #ffffff; border-color: #a0522d; }

      /* Filter Actions */
      .filter-actions { display: flex; gap: 15px; justify-content: center; }
      .btn-apply-filters { padding: 14px 40px; background: #a0522d; color: #ffffff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(160, 82, 45, 0.2); }
      .btn-apply-filters:hover { background: #8b4513; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(139, 69, 19, 0.3); }
      .btn-reset-filters { padding: 14px 40px; background: transparent; color: #8b6f47; border: 1px solid #8b6f47; border-radius: 8px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: all 0.3s ease; }
      .btn-reset-filters:hover { background: rgba(160, 82, 45, 0.1); color: #a0522d; border-color: #a0522d; }

      /* Results Summary */
      .results-summary-bar { background: #faf8f5; padding: 15px 25px; border-radius: 8px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
      .results-text { font-size: 16px; font-weight: 600; color: #6b5744; }
      .results-count { color: #a0522d; font-weight: 700; }

      /* Continue Button */
      .continue-room-section { margin-top: 50px; margin-bottom: 50px; text-align: center; padding: 30px; background: #ffffff; border-radius: 12px; border: 1px solid #d4a574; }
      .btn-continue-booking { padding: 18px 60px; background: #a0522d; color: #ffffff; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 6px 20px rgba(160, 82, 45, 0.3); text-decoration: none; display: inline-block; }
      .btn-continue-booking:hover { background: #8b4513; transform: translateY(-3px); box-shadow: 0 8px 25px rgba(139, 69, 19, 0.4); color: #ffffff; text-decoration: none; }
      .btn-continue-booking:disabled { background: #d4a574; cursor: not-allowed; opacity: 0.6; transform: none; }

      /* No Results */
      .no-results-message { text-align: center; padding: 60px 20px; display: none; }
      .no-results-message.show { display: block; }
      .no-results-icon { font-size: 64px; color: #d4a574; margin-bottom: 20px; }
      .no-results-text { font-size: 20px; font-weight: 600; color: #6b5744; margin-bottom: 10px; }
      .no-results-subtext { font-size: 14px; color: #8b6f47; }

      /* Room Card */
      .room-card-elegant { transition: all 0.3s ease; }
      .room-card-elegant.hidden { display: none !important; }
      .room-card-elegant.selected { border: 3px solid #a0522d; box-shadow: 0 8px 30px rgba(160, 82, 45, 0.3); }

      /* Ensure hidden cards are truly hidden */
      .rooms-masonry .room-card-elegant.hidden {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      /* Responsive */
      @media (max-width: 1200px) { .inline-filters-row { grid-template-columns: repeat(3, 1fr); } }
      @media (max-width: 768px) { .inline-filters-row { grid-template-columns: 1fr; } .modern-filter-section { padding: 20px; } .filter-actions { flex-direction: column; } .btn-apply-filters, .btn-reset-filters { width: 100%; } .results-summary-bar { flex-direction: column; gap: 10px; text-align: center; } }
    </style>
</head>
  <body>
    <!-- Preloader -->
    <div id="preloader">
      <div id="status"></div>
    </div>

    <?php include 'includes/header.php'; ?>

    <!-- breadcrumb -->
    <section class="breadcrumb-outer">
      <div class="container">
        <div class="breadcrumb-content">
          <h2>Reservation</h2>
          <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Select A Room</li>
            </ul>
          </nav>
        </div>
      </div>
    </section>

    <!-- reservation-main -->
    <section class="content reservation-main">
      <div class="container">

        <!-- Error Message Display -->
        <?php if (isset($bookingError) && $bookingError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 20px; border-left: 4px solid #dc3545;">
          <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>
          <strong>Booking Error:</strong> <?php echo htmlspecialchars($bookingError); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <?php endif; ?>

        <!-- Reservation Steps Indicator -->
        <div class="reservation-steps">
          <div class="step-item">
            <div class="step-circle">1</div>
            <div class="step-label">Check Availability</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item active">
            <div class="step-circle">2</div>
            <div class="step-label">Select Room</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-circle">3</div>
            <div class="step-label">Booking</div>
          </div>
          <div class="step-connector"></div>
          <div class="step-item">
            <div class="step-circle">4</div>
            <div class="step-label">Confirmation</div>
          </div>
        </div>

        <h2 style="text-align: center; color: #6b5744; font-family: 'Playfair Display', serif; font-size: 36px; margin-bottom: 40px; font-weight: 700;">Make Your Reservation</h2>

        <!-- Modern Filter Section -->
        <div class="modern-filter-section">

          <!-- Inline Filters Row - Now Tag-Based Like ROOM TYPE & FEATURES -->
          <div class="tag-filters-section">
            

            <!-- Price Range Tags -->
            <?php if (!empty($relevantPriceRanges)): ?>
            <div class="tag-category">
              <span class="tag-category-label">💰 PRICE RANGE</span>
              <div class="tag-filters-container">
                <?php foreach ($relevantPriceRanges as $range): ?>
                <span class="filter-tag" data-filter="price" data-value="<?php echo $range['min'] . '-' . $range['max']; ?>">
                  <?php echo htmlspecialchars($range['label']); ?>
                </span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- View Type Tags -->
            <?php if (!empty($viewTypes)): ?>
            <div class="tag-category">
              <span class="tag-category-label">🪟 VIEW TYPE</span>
              <div class="tag-filters-container">
                <?php foreach ($viewTypes as $viewType): ?>
                <span class="filter-tag" data-filter="view" data-value="<?php echo strtolower($viewType); ?>">
                  <?php echo ucfirst($viewType); ?> View
                </span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Bed Type Tags -->
            <?php if (!empty($bedTypes)): ?>
            <div class="tag-category">
              <span class="tag-category-label">🛏️ BED TYPE</span>
              <div class="tag-filters-container">
                <?php foreach ($bedTypes as $bedType): ?>
                <span class="filter-tag" data-filter="bed" data-value="<?php echo strtolower($bedType); ?>">
                  <?php echo $bedType; ?>
                </span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Sort By Tags -->
            <div class="tag-category">
              <span class="tag-category-label">📊 SORT BY</span>
              <div class="tag-filters-container">
                <span class="filter-tag" data-filter="sort" data-value="price-low">Price: Low to High</span>
                <span class="filter-tag" data-filter="sort" data-value="price-high">Price: High to Low</span>
                <span class="filter-tag" data-filter="sort" data-value="rating">Rating</span>
                <span class="filter-tag" data-filter="sort" data-value="popular">Popularity</span>
                <span class="filter-tag" data-filter="sort" data-value="size">Room Size</span>
              </div>
            </div>
          </div>

          <!-- Tag-Based Filters -->
          <div class="tag-filters-section">
            <!-- Room Type Tags -->
            <?php if (!empty($roomTypes)): ?>
            <div class="tag-category">
              <span class="tag-category-label">🏨 ROOM TYPE</span>
              <div class="tag-filters-container">
                <?php foreach ($roomTypes as $roomType): ?>
                <span class="filter-tag" data-filter="type" data-value="<?php echo htmlspecialchars($roomType); ?>">
                  <?php echo htmlspecialchars($roomType); ?>
                </span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Features Tags -->
            <?php if (!empty($roomFeatures)): ?>
            <div class="tag-category">
              <span class="tag-category-label">✨ FEATURES</span>
              <div class="tag-filters-container">
                <?php foreach ($roomFeatures as $feature): ?>
                <span class="filter-tag" data-filter="feature" data-value="<?php echo htmlspecialchars($feature['spec_name']); ?>">
                  <i class="<?php echo htmlspecialchars($feature['spec_icon']); ?>"></i>
                  <?php echo htmlspecialchars($feature['spec_name']); ?>
                </span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Action Buttons -->
          <div class="filter-actions">
            <button id="applyFiltersBtn" class="btn-apply-filters" type="button">APPLY FILTERS</button>
            <button id="resetFiltersBtn" class="btn-reset-filters" type="button">RESET FILTERS</button>
          </div>
        </div>

        <!-- Results Summary Bar -->
        <div class="results-summary-bar">
          <div class="results-text">
            Showing <span class="results-count" id="visibleRoomsCount"><?php echo count($availableRooms); ?></span> of <span class="results-count" id="totalRoomsCount"><?php echo count($availableRooms); ?></span> rooms
          </div>
          <div class="results-text">
            <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($checkIn)); ?> |
            <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($checkOut)); ?> |
            <strong>Guests:</strong> <?php echo $adults; ?> Adult(s), <?php echo $children; ?> Child(ren)
          </div>
        </div>

        <!-- No Results Message -->
        <div id="noResultsMessage" class="no-results-message <?php echo count($availableRooms) == 0 ? 'show' : ''; ?>">
          <div class="no-results-icon">
            <i class="fas fa-search"></i>
          </div>
          <div class="no-results-text">No rooms available</div>
          <div class="no-results-subtext">Sorry, no rooms are available for your selected dates. Please try different dates.</div>
          <div style="margin-top: 30px;">
            <a href="availability.php" class="btn-apply-filters">Change Dates</a>
          </div>
        </div>

        <!-- Room List -->
        <div class="room-list mar-top-60">
          <div class="row">
            <div class="col-lg-12">
              <div class="rooms-masonry" id="roomsGrid">
                <?php foreach ($availableRooms as $room):
                  $totalAmount = $room['price_per_night'] * $nights;
                ?>
                <div class="room-card-elegant animate-on-scroll"
                     data-room-id="<?php echo $room['room_id']; ?>"
                     data-price="<?php echo $room['price_per_night']; ?>"
                     data-view="<?php echo strtolower($room['view_type'] ?? 'none'); ?>"
                     data-bed="<?php echo strtolower($room['bed_type'] ?? ''); ?>"
                     data-room-name="<?php echo htmlspecialchars($room['room_name']); ?>"
                     data-room-type="<?php echo htmlspecialchars($room['room_type'] ?? ''); ?>"
                     data-room-image="<?php echo htmlspecialchars($room['room_image'] ?: 'images/room1.jpeg'); ?>"
                     data-room-description="<?php echo htmlspecialchars($room['description'] ?? 'Luxurious accommodation'); ?>"
                     data-amenities="<?php echo htmlspecialchars($room['amenities'] ?? ''); ?>"
                     data-max-occupancy="<?php echo $room['max_occupancy']; ?>"
                     data-size="<?php echo $room['size_sqm'] ?? 0; ?>"
                     data-total-amount="<?php echo $totalAmount; ?>">
                  <div class="room-image-elegant">
                    <img src="<?php echo htmlspecialchars($room['room_image'] ?: 'images/room1.jpeg'); ?>" alt="<?php echo htmlspecialchars($room['room_name']); ?>" />
                    <div class="luxury-badge">Luxury</div>
                    <?php if (isset($room['popularity_score']) && $room['popularity_score'] > 80): ?>
                    <div class="room-highlight">Most Popular</div>
                    <?php endif; ?>
                    <div class="wishlist-heart">
                      <i class="far fa-heart"></i>
                    </div>
                    <button class="quick-view-btn" data-room-id="<?php echo $room['room_id']; ?>">Quick View</button>
                  </div>
                  <div class="room-content-elegant">
                    <div class="price-elegant">
                      <span class="price-number">$<?php echo number_format($room['price_per_night'], 0); ?></span>
                      <span class="price-text">Per Night</span>
                    </div>
                    <div class="total-price-elegant" style="margin-top: 5px; font-size: 14px; color: #a0522d; font-weight: 600;">
                      Total: $<?php echo number_format($totalAmount, 2); ?> for <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                    </div>
                    <h3 class="room-title-elegant"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                    <div class="rating-elegant">
                      <div class="stars-elegant">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <i class="fas fa-star star-elegant"></i>
                        <?php endfor; ?>
                      </div>
                      <span class="rating-text"><?php echo number_format($room['rating'], 1); ?> rating</span>
                    </div>
                    <p class="room-description"><?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>...</p>
                    <div class="amenities-elegant">
                      <div class="amenity-icon">
                        <i class="fas fa-bed"></i>
                        <span><?php echo htmlspecialchars($room['bed_type']); ?></span>
                      </div>
                      <div class="amenity-icon">
                        <i class="fas fa-wifi"></i>
                        <span>Free WiFi</span>
                      </div>
                      <div class="amenity-icon">
                        <i class="fas fa-eye"></i>
                        <span><?php echo ucfirst($room['view_type']); ?> View</span>
                      </div>
                      <div class="amenity-icon">
                        <i class="fas fa-expand-arrows-alt"></i>
                        <span><?php echo $room['size_sqm']; ?>m²</span>
                      </div>
                    </div>
                    <div class="room-actions">
                      <form method="POST" action="room-select.php" style="display: inline;">
                        <input type="hidden" name="selected_room_id" value="<?php echo $room['room_id']; ?>">
                        <button type="submit" class="btn-book-elegant">Book Now</button>
                      </form>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" role="dialog" aria-labelledby="quickViewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document" style="width:1400px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="quickViewModalLabel">Room Details</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <img id="modalRoomImage" src="" alt="Room" class="img-fluid" style="border-radius: 8px;">
              </div>
              <div class="col-md-6">
                <h3 id="modalRoomName" style="color: #a0522d; margin-bottom: 15px;"></h3>
                <div id="modalRoomPrice" style="font-size: 24px; color: #6b5744; font-weight: 600; margin-bottom: 10px;"></div>
                <div id="modalTotalPrice" style="font-size: 18px; color: #a0522d; font-weight: 600; margin-bottom: 20px;"></div>
                <p id="modalRoomDescription" style="color: #666; margin-bottom: 20px;"></p>
                <div style="margin-bottom: 15px;">
                  <strong style="color: #6b5744;">Room Details:</strong>
                  <ul id="modalRoomDetails" style="list-style: none; padding-left: 0; margin-top: 10px;">
                  </ul>
                </div>
                <div id="modalAmenities" style="margin-bottom: 20px;">
                  <strong style="color: #6b5744;">Amenities:</strong>
                  <div id="modalAmenitiesList" style="margin-top: 10px;"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <form method="POST" action="room-select.php" style="display: inline;">
              <input type="hidden" name="selected_room_id" id="modalRoomId">
              <button type="submit" class="btn btn-primary" style="background-color: #a0522d; border-color: #a0522d;">Book This Room</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Back to top -->
    <div id="back-to-top">
      <a href="#"></a>
    </div>

    <!-- Scripts -->
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugin.js"></script>
    <script src="js/main.js"></script>
    <script src="js/custom-nav.js"></script>

    <script>
    console.log("=== FILTER SCRIPT LOADED ===");

    document.addEventListener('DOMContentLoaded', function() {
      console.log("DOM loaded, initializing filters...");

      // ========================================
      // GET ALL ELEMENTS
      // ========================================
      const allRoomCards = document.querySelectorAll('.room-card-elegant');
      const roomsGrid = document.getElementById('roomsGrid');
      const searchInput = document.getElementById('roomSearchInput');
      const applyBtn = document.getElementById('applyFiltersBtn');
      const resetBtn = document.getElementById('resetFiltersBtn');
      const filterTags = document.querySelectorAll('.filter-tag');

      console.log("Found " + allRoomCards.length + " room cards");
      console.log("Found " + filterTags.length + " filter tags (including guests, price, view, bed, sort, type, feature)");

      // Filter state
      let activeFilters = {
        search: '',
        guests: '',
        price: '',
        view: '',
        bed: '',
        sort: 'price-low',
        tags: []
      };

      // ========================================
      // APPLY FILTERS FUNCTION
      // ========================================
      function applyFilters() {
        console.log("Applying filters with state:", activeFilters);
        let visibleCount = 0;

        allRoomCards.forEach(function(card) {
          let show = true;

          // Get card data
          const cardPrice = parseFloat(card.getAttribute('data-price')) || 0;
          const cardView = (card.getAttribute('data-view') || '').toLowerCase();
          const cardBed = (card.getAttribute('data-bed') || '').toLowerCase();
          const cardName = (card.getAttribute('data-room-name') || '').toLowerCase();
          const cardType = (card.getAttribute('data-room-type') || '').toLowerCase();
          const cardAmenities = (card.getAttribute('data-amenities') || '').toLowerCase();
          const cardOccupancy = parseInt(card.getAttribute('data-max-occupancy')) || 2;

          console.log("Checking card:", cardName, "Price:", cardPrice, "Occupancy:", cardOccupancy);

          // Search filter
          if (activeFilters.search) {
            const searchTerm = activeFilters.search.toLowerCase();
            if (!cardName.includes(searchTerm) && !cardAmenities.includes(searchTerm)) {
              show = false;
              console.log("✗ Failed search filter");
            }
          }

          // Guests filter (NEW - WAS MISSING!)
          if (activeFilters.guests && show) {
            const guestCount = parseInt(activeFilters.guests);
            if (guestCount === 4) {
              // "4+ Guests" option
              if (cardOccupancy < 4) {
                show = false;
                console.log("✗ Failed guests filter (need 4+, has " + cardOccupancy + ")");
              }
            } else {
              // Specific guest count
              if (cardOccupancy < guestCount) {
                show = false;
                console.log("✗ Failed guests filter (need " + guestCount + ", has " + cardOccupancy + ")");
              }
            }
          }

          // Price filter
          if (activeFilters.price && show) {
            const [min, max] = activeFilters.price.split('-').map(Number);
            if (max === 99999) {
              // "$500+" option
              if (cardPrice < min) {
                show = false;
                console.log("✗ Failed price filter (need $" + min + "+, has $" + cardPrice + ")");
              }
            } else {
              // Standard range
              if (cardPrice < min || cardPrice >= max) {
                show = false;
                console.log("✗ Failed price filter (need $" + min + "-$" + max + ", has $" + cardPrice + ")");
              }
            }
          }

          // View filter
          if (activeFilters.view && show) {
            if (cardView !== activeFilters.view.toLowerCase()) {
              show = false;
              console.log("✗ Failed view filter (need '" + activeFilters.view + "', has '" + cardView + "')");
            }
          }

          // Bed filter
          if (activeFilters.bed && show) {
            if (!cardBed.includes(activeFilters.bed.toLowerCase())) {
              show = false;
              console.log("✗ Failed bed filter (need '" + activeFilters.bed + "', has '" + cardBed + "')");
            }
          }

          // Tag filters (IMPROVED - Better amenity matching)
          if (activeFilters.tags.length > 0 && show) {
            let hasAllTags = true;
            activeFilters.tags.forEach(function(tag) {
              const tagLower = tag.toLowerCase();
              // Check if tag exists in room type, amenities, or name
              const inType = cardType.includes(tagLower);
              const inAmenities = cardAmenities.includes(tagLower);
              const inName = cardName.includes(tagLower);

              if (!inType && !inAmenities && !inName) {
                hasAllTags = false;
                console.log("✗ Missing tag: '" + tag + "'");
              }
            });
            if (!hasAllTags) {
              show = false;
              console.log("✗ Failed tag filter");
            }
          }

          // Show/hide card
          if (show) {
            card.style.display = '';
            card.classList.remove('hidden');
            visibleCount++;
          } else {
            card.style.display = 'none';
            card.classList.add('hidden');
          }
        });

        console.log("Visible rooms:", visibleCount);

        // Update count
        const countElement = document.getElementById('visibleRoomsCount');
        if (countElement) {
          countElement.textContent = visibleCount;
        }

        // Show/hide no results
        const noResults = document.getElementById('noResultsMessage');
        if (noResults) {
          if (visibleCount === 0) {
            noResults.classList.add('show');
          } else {
            noResults.classList.remove('show');
          }
        }

        // Sort rooms
        sortRooms();
      }

      // ========================================
      // SORT ROOMS FUNCTION
      // ========================================
      function sortRooms() {
        const visible = Array.from(allRoomCards).filter(card => !card.classList.contains('hidden'));
        
        visible.sort(function(a, b) {
          const priceA = parseFloat(a.getAttribute('data-price')) || 0;
          const priceB = parseFloat(b.getAttribute('data-price')) || 0;
          
          if (activeFilters.sort === 'price-low') return priceA - priceB;
          if (activeFilters.sort === 'price-high') return priceB - priceA;
          return 0;
        });

        visible.forEach(function(card) {
          if (roomsGrid) roomsGrid.appendChild(card);
        });
      }

      // ========================================
      // EVENT LISTENERS
      // ========================================

      // Search input
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          console.log("Search input changed:", this.value);
          activeFilters.search = this.value;
          applyFilters();
        });
      }

      // Filter tags - Handle ALL filter types (guests, price, view, bed, sort, type, feature)
      filterTags.forEach(function(tag) {
        tag.addEventListener('click', function() {
          const filterType = this.getAttribute('data-filter');
          const filterValue = this.getAttribute('data-value');
          const tagText = this.textContent.trim();

          console.log("Tag clicked:", tagText, "| Type:", filterType, "| Value:", filterValue);

          // Handle different filter types
          if (filterType === 'guests') {
            // Single selection for guests
            document.querySelectorAll('.filter-tag[data-filter="guests"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeFilters.guests = filterValue;
            console.log("Guests filter set to:", filterValue);
            applyFilters();

          } else if (filterType === 'price') {
            // Single selection for price
            document.querySelectorAll('.filter-tag[data-filter="price"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeFilters.price = filterValue;
            console.log("Price filter set to:", filterValue);
            applyFilters();

          } else if (filterType === 'view') {
            // Single selection for view
            document.querySelectorAll('.filter-tag[data-filter="view"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeFilters.view = filterValue;
            console.log("View filter set to:", filterValue);
            applyFilters();

          } else if (filterType === 'bed') {
            // Single selection for bed
            document.querySelectorAll('.filter-tag[data-filter="bed"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeFilters.bed = filterValue;
            console.log("Bed filter set to:", filterValue);
            applyFilters();

          } else if (filterType === 'sort') {
            // Single selection for sort
            document.querySelectorAll('.filter-tag[data-filter="sort"]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeFilters.sort = filterValue;
            console.log("Sort filter set to:", filterValue);
            sortRooms();

          } else {
            // Multi-selection for type and feature tags (original behavior)
            this.classList.toggle('active');
            const tagValue = (filterValue || tagText).toLowerCase();

            if (this.classList.contains('active')) {
              if (!activeFilters.tags.includes(tagValue)) {
                activeFilters.tags.push(tagValue);
              }
            } else {
              activeFilters.tags = activeFilters.tags.filter(t => t !== tagValue);
            }

            console.log("Active tags:", activeFilters.tags);
            applyFilters();
          }
        });
      });

      // Apply button
      if (applyBtn) {
        applyBtn.addEventListener('click', function() {
          console.log("Apply button clicked");
          applyFilters();
        });
      }

      // Reset button
      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          console.log("Reset button clicked");

          // Reset search input
          if (searchInput) searchInput.value = '';

          // Reset all filter tags (remove active class)
          filterTags.forEach(function(tag) {
            tag.classList.remove('active');
          });

          // Reset filter state
          activeFilters = {
            search: '',
            guests: '',
            price: '',
            view: '',
            bed: '',
            sort: 'price-low',
            tags: []
          };

          console.log("All filters reset");
          applyFilters();
        });
      }

      // Quick View buttons
      const quickViewButtons = document.querySelectorAll('.quick-view-btn');
      quickViewButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();

          const roomCard = this.closest('.room-card-elegant');

          // Get all room data from data attributes
          const roomId = roomCard.getAttribute('data-room-id');
          const roomName = roomCard.getAttribute('data-room-name');
          const roomImage = roomCard.getAttribute('data-room-image');
          const price = roomCard.getAttribute('data-price');
          const totalAmount = roomCard.getAttribute('data-total-amount');
          const bedType = roomCard.getAttribute('data-bed');
          const viewType = roomCard.getAttribute('data-view');
          const maxOccupancy = roomCard.getAttribute('data-max-occupancy');
          const roomSize = roomCard.getAttribute('data-size');
          const amenities = roomCard.getAttribute('data-amenities');
          const description = roomCard.getAttribute('data-room-description');
          const roomType = roomCard.getAttribute('data-room-type');

          console.log("Quick view clicked for:", roomName);

          // Populate modal with data
          document.getElementById('modalRoomImage').src = roomImage || 'images/room1.jpeg';
          document.getElementById('modalRoomImage').alt = roomName;
          document.getElementById('modalRoomName').textContent = roomName;
          document.getElementById('modalRoomPrice').textContent = '$' + parseFloat(price).toFixed(2) + ' Per Night';
          document.getElementById('modalTotalPrice').textContent = 'Total: $' + parseFloat(totalAmount).toFixed(2) + ' for <?php echo $nights; ?> night<?php echo $nights > 1 ? "s" : ""; ?>';
          document.getElementById('modalRoomDescription').textContent = description || 'Luxurious accommodation with modern amenities.';
          document.getElementById('modalRoomId').value = roomId;

          // Populate room details list
          const detailsList = document.getElementById('modalRoomDetails');
          detailsList.innerHTML = '';

          if (roomType) {
            const li = document.createElement('li');
            li.innerHTML = '<i class="fas fa-door-open" style="color: #a0522d; margin-right: 10px;"></i> Room Type: <strong>' + roomType + '</strong>';
            li.style.marginBottom = '8px';
            detailsList.appendChild(li);
          }

          if (bedType) {
            const li = document.createElement('li');
            li.innerHTML = '<i class="fas fa-bed" style="color: #a0522d; margin-right: 10px;"></i> Bed: <strong>' + bedType + '</strong>';
            li.style.marginBottom = '8px';
            detailsList.appendChild(li);
          }

          if (viewType && viewType !== 'none') {
            const li = document.createElement('li');
            li.innerHTML = '<i class="fas fa-eye" style="color: #a0522d; margin-right: 10px;"></i> View: <strong>' + viewType.charAt(0).toUpperCase() + viewType.slice(1) + '</strong>';
            li.style.marginBottom = '8px';
            detailsList.appendChild(li);
          }

          if (maxOccupancy) {
            const li = document.createElement('li');
            li.innerHTML = '<i class="fas fa-users" style="color: #a0522d; margin-right: 10px;"></i> Max Occupancy: <strong>' + maxOccupancy + ' guests</strong>';
            li.style.marginBottom = '8px';
            detailsList.appendChild(li);
          }

          if (roomSize) {
            const li = document.createElement('li');
            li.innerHTML = '<i class="fas fa-ruler-combined" style="color: #a0522d; margin-right: 10px;"></i> Size: <strong>' + roomSize + ' sqm</strong>';
            li.style.marginBottom = '8px';
            detailsList.appendChild(li);
          }

          // Populate amenities
          const amenitiesList = document.getElementById('modalAmenitiesList');
          amenitiesList.innerHTML = '';

          if (amenities) {
            const amenitiesArray = amenities.split(',').map(a => a.trim()).filter(a => a);
            if (amenitiesArray.length > 0) {
              amenitiesArray.forEach(function(amenity) {
                const badge = document.createElement('span');
                badge.textContent = amenity;
                badge.style.cssText = 'display: inline-block; background: #f5f1eb; color: #6b5744; padding: 5px 12px; border-radius: 15px; margin-right: 8px; margin-bottom: 8px; font-size: 13px; border: 1px solid #d4a574;';
                amenitiesList.appendChild(badge);
              });
            } else {
              amenitiesList.innerHTML = '<span style="color: #999;">No amenities listed</span>';
            }
          } else {
            amenitiesList.innerHTML = '<span style="color: #999;">No amenities listed</span>';
          }

          // Show modal using Bootstrap
          $('#quickViewModal').modal('show');
        });
      });

      console.log("=== FILTERS INITIALIZED SUCCESSFULLY ===");

      // ========================================
      // BOOK NOW BUTTON - PREVENT DOUBLE SUBMISSION
      // ========================================
      const bookNowForms = document.querySelectorAll('.room-actions form');

      bookNowForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
          const submitBtn = this.querySelector('.btn-book-elegant');

          // Prevent double submission
          if (submitBtn.disabled) {
            e.preventDefault();
            return false;
          }

          // Disable button and show loading state
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
          submitBtn.style.opacity = '0.7';
          submitBtn.style.cursor = 'not-allowed';

          console.log("Book Now clicked - submitting form...");

          // Form will submit normally
          return true;
        });
      });
    });
    </script>

    <!-- Login and Register Modals -->
    <?php include 'includes/modals.php'; ?>

    <!-- Authentication JavaScript -->
    <script src="js/auth.js"></script>
</body>
</html>

